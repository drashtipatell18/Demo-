<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendMessageRequest;
use App\Models\Message;
use App\Models\Thread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ThreadController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000'
        ]);

        DB::beginTransaction();

        try {

            $thread = Thread::create([
                'user_id' => auth()->id(),
                'title' => substr(strip_tags($request->message), 0, 30)
            ]);

            Message::create([
                'thread_id' => $thread->id,
                'role' => 'user',
                'message' => strip_tags($request->message),
                'type' => 'text'
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'thread' => $thread
            ], 201);
        } catch (\Throwable $e) {

            DB::rollBack();
            Log::error($e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Thread creation failed'
            ], 500);
        }
    }

    public function sendMessage(SendMessageRequest $request)
    {
        try {

            DB::beginTransaction();

            /* ---------- THREAD LOGIC ---------- */

            if ($request->thread_id) {
                $thread = Thread::where('id', $request->thread_id)
                    ->where('user_id', auth()->id())
                    ->firstOrFail();
            } else {
                $thread = Thread::create([
                    'user_id' => auth()->id(),
                    'title' => substr(strip_tags($request->message ?? 'New Chat'), 0, 30)
                ]);
            }

            /* ---------- FILE LOGIC ---------- */

            $filePath = null;
            $fileType = null;
            $originalName = null;
            $type = 'text';

            if ($request->hasFile('file')) {

                $file = $request->file('file');

                $filePath = $file->store('messages', 'public');
                $fileType = $file->getClientMimeType();
                $originalName = $file->getClientOriginalName();

                $type = str_contains($fileType, 'image') ? 'image' : 'file';
            }

            /* ---------- MESSAGE SAVE ---------- */

            $msg = Message::create([
                'thread_id' => $thread->id,
                'role' => 'user',
                'message' => $request->message,
                'file_path' => $filePath,
                'file_type' => $fileType,
                'original_name' => $originalName,
                'type' => $type
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'thread_id' => $thread->id,
                'message' => $msg
            ], 201);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeResponse(Request $request, $id)
    {
        try {

            $request->validate([
                'response' => 'nullable|string',
                'image_url' => 'nullable|url',
                'image_base64' => 'nullable|string'
            ]);

            $thread = Thread::findOrFail($id);

            DB::beginTransaction();

            $filePath = null;
            $type = 'text';

            if ($request->image_url) {
                $filePath = $this->storeImageFromUrl($request->image_url);
                $type = 'image';
            }

            if ($request->image_base64) {
                $filePath = $this->storeBase64Image($request->image_base64);
                $type = 'image';
            }

            $msg = Message::create([
                'thread_id' => $id,
                'role' => 'model',
                'message' => $request->response,
                'file_path' => $filePath,
                'type' => $type
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'data' => $msg
            ]);
        } catch (\Throwable $e) {
            dd($e->getMessage());
            DB::rollBack();

            Log::error('AI response error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to store AI response'
            ], 500);
        }
    }

    public function index()
    {
        return Thread::where('user_id', auth()->id())
            ->latest()
            ->get();
    }

    public function show($id)
    {
        $thread = Thread::where('id', $id)
            ->where('user_id', auth()->id())
            ->with('messages')
            ->firstOrFail();

        return response()->json($thread);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'is_pin' => 'nullable|boolean'
        ]);

        $thread = Thread::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($request->has('title')) {
            $thread->title = strip_tags($request->title);
        }

        if ($request->has('is_pin')) {
            $thread->is_pin = $request->is_pin;
        }

        $thread->save();

        return response()->json([
            'status' => true,
            'thread' => $thread
        ]);
    }

    public function destroy($id)
    {
        $thread = Thread::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $thread->delete();

        return response()->json([
            'status' => true,
            'message' => 'Thread deleted successfully'
        ]);
    }

    private function storeImageFromUrl($url)
    {
        $response = Http::get($url);

        $name = 'ai_' . time() . '.png';
        $path = 'messages/' . $name;

        Storage::disk('public')->put($path, $response->body());

        return $path;
    }

    private function storeBase64Image($base64)
    {
        preg_match('/data:image\/(\w+);base64,/', $base64, $type);

        $data = substr($base64, strpos($base64, ',') + 1);
        $data = base64_decode($data);

        $extension = $type[1] ?? 'png';

        $name = 'ai_' . time() . '.' . $extension;
        $path = 'messages/' . $name;

        Storage::disk('public')->put($path, $data);

        return $path;
    }
}
