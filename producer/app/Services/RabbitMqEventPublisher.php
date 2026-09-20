<?php

namespace App\Services;

use App\Models\OutboxMessage;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;

class RabbitMqEventPublisher
{
    public function publish(OutboxMessage $outboxMessage): void
    {
        $connection = new AMQPStreamConnection(
            (string) config('queue.connections.rabbitmq.hosts.0.host'),
            (int) config('queue.connections.rabbitmq.hosts.0.port'),
            (string) config('queue.connections.rabbitmq.hosts.0.user'),
            (string) config('queue.connections.rabbitmq.hosts.0.password'),
            (string) config('queue.connections.rabbitmq.hosts.0.vhost'),
        );

        $channel = $connection->channel();

        try {
            $channel->confirm_select();

            $channel->set_return_listener(
                function (
                    int $replyCode,
                    string $replyText,
                    string $exchange,
                    string $routingKey,
                    AMQPMessage $message,
                ): void {
                    throw new RuntimeException(
                        "RabbitMQ could not route the message: {$replyCode} {$replyText}",
                    );
                },
            );

            $channel->set_nack_handler(
                function (AMQPMessage $message): void {
                    throw new RuntimeException('RabbitMQ negatively acknowledged the message.');
                },
            );

            $message = new AMQPMessage(
                json_encode([
                    'id' => $outboxMessage->id,
                    'type' => $outboxMessage->event_type,
                    'occurred_at' => $outboxMessage->occurred_at->toISOString(),
                    'data' => $outboxMessage->payload,
                ], JSON_THROW_ON_ERROR),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'message_id' => $outboxMessage->id,
                    'timestamp' => $outboxMessage->occurred_at->getTimestamp(),
                    'type' => $outboxMessage->event_type,
                ],
            );

            $channel->basic_publish(
                $message,
                $outboxMessage->exchange,
                $outboxMessage->routing_key,
                true,
            );

            $channel->wait_for_pending_acks_returns(5);
        } finally {
            $channel->close();
            $connection->close();
        }
    }
}