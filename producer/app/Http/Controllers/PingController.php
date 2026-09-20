<?php

namespace App\Http\Controllers;

use App\Models\OutboxMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PingController extends Controller
{
    public function ping(){
        return DB::transaction(function(){
            $messageId = (string) Str::uuid();
            OutboxMessage::create([
                'id' => $messageId,
                'event_type' => 'ping.created',
                'exchange' => 'demo.events',
                'routing_key' => 'ping.created',
                'payload' => [
                    'message_id' => $messageId,
                    'message' => 'ping',
                ],
                'occurred_at' => now(),
                'available_at' => now(),
            ]);

            return response()->json([
                'message_id' => $messageId,
                'status' => 'queued_for_publishing',
            ], 202);
        });
    }
}
