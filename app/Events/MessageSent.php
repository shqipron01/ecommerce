<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\ChatMessage;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new Channel('chat');
    }

    public function broadcastAs()
    {
        return 'MessageSent';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->message->id ?? null,
            'user_id' => $this->message->user_id ?? null,
            'message' => $this->message->message ?? '',
            'created_at' => isset($this->message->created_at) && method_exists($this->message->created_at, 'toDateTimeString')
                ? $this->message->created_at->toDateTimeString()
                : now()->toDateTimeString(),
        ];
    }
}
