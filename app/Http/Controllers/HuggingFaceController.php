<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class HuggingFaceController extends Controller
{
    public function generateText(Request $request)
    {
        try {

            $prompt = $request->prompt;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.huggingface.token'),
                'Content-Type' => 'application/json'
            ])->post(
                'https://router.huggingface.co/v1/chat/completions',
                [
                    "model" => "meta-llama/Meta-Llama-3-8B-Instruct",
                    "messages" => [
                        [
                            "role" => "user",
                            "content" => $prompt
                        ]
                    ],
                    "max_tokens" => 500
                ]
            );

            $data = $response->json();

            return response()->json([
                "generated_text" => $data['choices'][0]['message']['content'] ?? "No response"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function generateImage(Request $request)
    {
        try {

            $prompt = $request->prompt;
            $imageFile = $request->file('image');
            $token = config('services.huggingface.token');

            $imageBase64 = null;

            if ($imageFile) {
                $imageBase64 = base64_encode(file_get_contents($imageFile->getRealPath()));
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ])->post(
                'https://router.huggingface.co/hf-inference/models/stabilityai/stable-diffusion-xl-base-1.0',
                [
                    "inputs" => $prompt,
                    "parameters" => [
                        "image" => $imageBase64
                    ]
                ]
            );

            if ($response->failed()) {
                return response()->json([
                    "error" => $response->body()
                ], $response->status());
            }

            $generatedImage = base64_encode($response->body());

            return response()->json([
                "image" => $generatedImage
            ]);
        } catch (\Exception $e) {

            return response()->json([
                "error" => $e->getMessage()
            ], 500);
        }
    }
}
