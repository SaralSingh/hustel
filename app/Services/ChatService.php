<?php

namespace App\Services;

use App\Models\Room;
use App\Models\RoomMessage;
use App\Events\NewChatMessage;
use Illuminate\Database\Eloquent\Collection;
use App\Models\User;

class ChatService
{
    /**
     * Get the latest messages for a room.
     */
    public function getRoomMessages(string $roomId, int $limit = 50): Collection
    {
        return RoomMessage::where('room_id', $roomId)
            ->latest()
            ->take($limit)
            ->get()
            ->reverse()
            ->values(); // reset keys after reverse
    }

    /**
     * Save a new message and broadcast it to the room.
     */
    public function sendMessage(Room $room, ?User $user, string $userName, string $messageContent): RoomMessage
    {
        $message = $room->messages()->create([
            'user_id' => $user ? $user->id : null,
            'user_name' => $userName,
            'message' => $messageContent,
        ]);

        // Load relations if necessary before broadcasting
        // $message->load('user');

        broadcast(new NewChatMessage($message))->toOthers();

        return $message;
    }
}
