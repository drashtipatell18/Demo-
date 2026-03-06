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

            // Check HTTP response status
            if ($response->failed()) {
                $status = $response->status();
                $body = $response->json();

                // Handle specific Hugging Face errors
                if (isset($body['error'])) {
                    $errorMessage = $body['error'];

                    if (str_contains($errorMessage, 'depleted your monthly')) {
                        return response()->json([
                            "error" => "Your monthly usage limit has been reached. Please purchase credits or subscribe to PRO."
                        ], $status);
                    }

                    if (str_contains($errorMessage, 'access denied') || str_contains($errorMessage, 'not found')) {
                        return response()->json([
                            "error" => "The model you are trying to access is not available for your account. Please check your plan or model name."
                        ], $status);
                    }

                    return response()->json([
                        "error" => $errorMessage
                    ], $status);
                }

                // Generic HTTP error fallback
                return response()->json([
                    "error" => "Hugging Face API request failed with status $status."
                ], $status);
            }

            // Success: parse generated text
            $data = $response->json();

            $generatedText = $data['choices'][0]['message']['content'] ?? "No response";

            return response()->json([
                "generated_text" => $generatedText
            ]);
        } catch (\Exception $e) {
            // Catch all other exceptions
            return response()->json([
                "error" => "An unexpected error occurred: " . $e->getMessage()
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

            // Check if request failed
            if ($response->failed()) {
                $status = $response->status();
                $body = $response->json();

                if (isset($body['error'])) {
                    $errorMessage = $body['error'];

                    if (str_contains($errorMessage, 'depleted your monthly')) {
                        return response()->json([
                            "error" => "Your monthly usage limit has been reached. Please purchase credits or subscribe to PRO."
                        ], $status);
                    }

                    if (str_contains($errorMessage, 'access denied') || str_contains($errorMessage, 'not found')) {
                        return response()->json([
                            "error" => "The model you are trying to access is not available for your account. Please check your plan or model name."
                        ], $status);
                    }

                    return response()->json([
                        "error" => $errorMessage
                    ], $status);
                }

                return response()->json([
                    "error" => "Hugging Face API request failed with status $status."
                ], $status);
            }

            $filePath = null;
            $text = null;

            $contentType = $response->header('content-type');

            // CASE 1: Image binary response
            if (str_contains($contentType, 'image')) {
                $imageBinary = $response->body();
                $fileName = time() . '_' . uniqid() . '.png';
                file_put_contents(public_path('images/' . $fileName), $imageBinary);
                $filePath = url('images/' . $fileName);
            }

            // CASE 2: JSON response (text or base64 image)
            if (str_contains($contentType, 'application/json')) {
                $data = $response->json();

                if (isset($data['generated_text'])) {
                    $text = $data['generated_text'];
                }

                if (isset($data['image'])) {
                    $imageBinary = base64_decode($data['image']);
                    $fileName = time() . '_' . uniqid() . '.png';
                    file_put_contents(public_path('images/' . $fileName), $imageBinary);
                    $filePath = url('images/' . $fileName);
                }
            }

            return response()->json([
                "image" => $filePath,
                "text" => $text
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "error" => "An unexpected error occurred: " . $e->getMessage()
            ], 500);
        }
    }
}
