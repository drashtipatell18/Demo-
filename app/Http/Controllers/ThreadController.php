<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Thread;
use Illuminate\Http\Request;

class ThreadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required'
        ]);

        $thread = Thread::create([
            'user_id' => auth()->id(),
            'title' => substr($request->message, 0, 30)
        ]);

        Message::create([
            'thread_id' => $thread->id,
            'role' => 'user',
            'message' => $request->message
        ]);

        return response()->json([
            'status' => true,
            'thread' => $thread
        ]);
    }

    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'message' => 'required'
        ]);

        $thread = Thread::findOrFail($id);

        $msg = Message::create([
            'thread_id' => $thread->id,
            'role' => 'user',
            'message' => $request->message
        ]);

        return response()->json([
            'status' => true,
            'message' => $msg
        ]);
    }

    public function storeResponse(Request $request, $id)
    {
        try {

            $request->validate([
                'response' => 'required'
            ]);

            $thread = Thread::find($id);

            if (!$thread) {
                return response()->json([
                    'status' => false,
                    'message' => 'Thread not found'
                ], 404);
            }

            $msg = Message::create([
                'thread_id' => $id,
                'role' => 'model',
                'message' => $request->response
            ]);

            return response()->json([
                'status' => true,
                'data' => $msg
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
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
        $thread = Thread::with('messages')->findOrFail($id);
        return response()->json($thread);
    }

    public function update(Request $request, $id)
    {
        $thread = Thread::findOrFail($id);

        $thread->update([
            'title' => $request->title
        ]);

        return response()->json([
            'status' => true,
            'thread' => $thread
        ]);
    }

    public function destroy($id)
    {
        Thread::findOrFail($id)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Thread deleted'
        ]);
    }
}
