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

            /* ---------- FILE / LINK LOGIC ---------- */

            $filePath = null;
            $fileType = null;
            $originalName = null;
            $type = 'text';

            // ✅ Case 1: Real File Upload
            if ($request->hasFile('file')) {

                $file = $request->file('file');

                $filePath = $file->store('messages', 'public');
                $fileType = $file->getClientMimeType();
                $originalName = $file->getClientOriginalName();

                $type = $this->detectFileType($fileType);
            }

            // ✅ Case 2: File Field Contains URL (Text)
            elseif ($request->filled('file') && filter_var($request->file, FILTER_VALIDATE_URL)) {

                $filePath = $request->file;
                $originalName = $request->file;

                $type = $this->detectFileType(null, $request->file);
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
                'response'   => 'nullable|string',
                'media_url'  => 'nullable|url',
            ]);

            $thread = Thread::findOrFail($id);

            DB::beginTransaction();

            $filePath = null;
            $fileType = null;
            $originalName = null;
            $type = 'text';

            // Real file upload
            if ($request->hasFile('file')) {

                $file = $request->file('file');

                $filePath = $file->store('messages', 'public');
                $fileType = $file->getClientMimeType();
                $originalName = $file->getClientOriginalName();

                $type = $this->detectFileType($fileType);
            }

            // File field contains URL
            elseif ($request->filled('file') && filter_var($request->file, FILTER_VALIDATE_URL)) {

                $filePath = $request->file;
                $originalName = $request->file;

                $type = $this->detectFileType(null, $request->file);
            }

            $msg = Message::create([
                'thread_id' => $id,
                'role' => 'model',
                'message' => $request->response,
                'file_path' => $filePath,
                'file_type' => $fileType,
                'original_name' => $originalName,
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


    private function detectFileType($mimeType = null, $fileName = null)
    {
        if ($mimeType) {

            if (str_contains($mimeType, 'image')) {
                return 'image';
            }

            if (str_contains($mimeType, 'video')) {
                return 'video';
            }

            if (
                str_contains($mimeType, 'pdf') ||
                str_contains($mimeType, 'msword') ||
                str_contains($mimeType, 'officedocument') ||
                str_contains($mimeType, 'text')
            ) {
                return 'document';
            }

            return 'file';
        }

        // If checking from URL (extension based)
        if ($fileName) {

            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $images = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $videos = ['mp4', 'mov', 'avi', 'webm'];
            $documents = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];

            if (in_array($extension, $images)) {
                return 'image';
            }

            if (in_array($extension, $videos)) {
                return 'video';
            }

            if (in_array($extension, $documents)) {
                return 'document';
            }

            return 'file';
        }

        return 'text';
    }
}
