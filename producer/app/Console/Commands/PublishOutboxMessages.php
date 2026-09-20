<?php

namespace App\Console\Commands;

use App\Models\OutboxMessage;
use App\Services\RabbitMqEventPublisher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('outbox:publish')]
#[Description('Publishes one pending outbox message to RabbitMQ.')]
class PublishOutboxMessages extends Command
{
    public function handle(RabbitMqEventPublisher $publisher): int
    {
        $outboxMessage = OutboxMessage::query()
            ->whereNull('published_at')
            ->where('available_at', '<=', now())
            ->orderBy('occurred_at')
            ->first();

        if ($outboxMessage === null) {
            $this->info('No pending outbox messages.');

            return self::SUCCESS;
        }

        try {
            $publisher->publish($outboxMessage);

            $outboxMessage->published_at = now();
            $outboxMessage->last_error = null;
            $outboxMessage->save();

            $this->info("Published outbox message {$outboxMessage->id}.");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $outboxMessage->attempts++;
            $outboxMessage->last_error = $exception->getMessage();
            $outboxMessage->available_at = now()->addSeconds(10);
            $outboxMessage->save();

            $this->error("Failed to publish outbox message {$outboxMessage->id}.");

            return self::FAILURE;
        }
    }
}