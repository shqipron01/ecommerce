<?php

namespace App\Http\Controllers\front;

use App\Http\Controllers\Controller;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index()
    {
        $messages = ChatMessage::orderBy('_id', 'desc')->take(50)->get();
        return response()->json($messages->reverse()->values());
    }

    public function store(Request $request)
    {
        
        $message = ChatMessage::on('mongodb')->create([
            'user_id' => $request->user_id,
            'message' => $request->message,
        ]);

        broadcast(new MessageSent($message))->toOthers();

        $botReplyText = $this->generateBotReply($request->message);

        $botMessage = [
            'user_id' => null,
            'message' => $botReplyText,
            'created_at' => now(),
        ];

        broadcast(new MessageSent((object) $botMessage))->toOthers();

        return response()->json([
            'message' => 'Mesazhi u ruajt me sukses!',
        ]);
    }

    private function generateBotReply($input)
    {
        $text = strtolower(trim($input));

        if (str_contains($text, 'hi') || str_contains($text, 'hello')) {
            return 'Hi there! How can I help you today?';
        }

        if (str_contains($text, 'price')) {
            return 'Prices vary depending on the product. Can you specify which one?';
        }

        if (str_contains($text, 'delivery')) {
            return 'We offer free delivery on orders over $50.';
        }

        if (str_contains($text, 'return')) {
            return 'You can return items within 14 days of purchase.';
        }

        return 'Thank you! We’ll get back to you soon.';
    }
}
