<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;
use Throwable;

#[Signature('events:consume-ping')]
#[Description('Consumes ping events from RabbitMQ and writes them to the application log.')]
class ConsumePingEvents extends Command
{
    public function handle(): int
    {
        $queueName = (string) config('queue.connections.rabbitmq.queue');

        $connection = new AMQPStreamConnection(
            (string) config('queue.connections.rabbitmq.hosts.0.host'),
            (int) config('queue.connections.rabbitmq.hosts.0.port'),
            (string) config('queue.connections.rabbitmq.hosts.0.user'),
            (string) config('queue.connections.rabbitmq.hosts.0.password'),
            (string) config('queue.connections.rabbitmq.hosts.0.vhost'),
        );

        $channel = $connection->channel();

        try {
            $channel->basic_qos(0, 1, false);

            $channel->basic_consume(
                $queueName,
                '',
                false,
                false,
                false,
                false,
                function (AMQPMessage $message): void {
                    try {
                        $event = json_decode(
                            $message->getBody(),
                            true,
                            512,
                            JSON_THROW_ON_ERROR,
                        );

                        if (! is_array($event)) {
                            throw new RuntimeException('The RabbitMQ message is not a JSON object.');
                        }

                        Log::info('Consumed ping event.', [
                            'event_id' => $event['id'] ?? null,
                            'event_type' => $event['type'] ?? null,
                            'payload' => $event['data'] ?? null,
                        ]);

                        $message->ack();
                    } catch (Throwable $exception) {
                        Log::error('Failed to consume ping event.', [
                            'error' => $exception->getMessage(),
                        ]);

                        $message->nack(true);
                    }
                },
            );

            $this->info("Waiting for messages from {$queueName}. Press Ctrl+C to stop.");

            while ($channel->is_consuming()) {
                $channel->wait();
            }

            return self::SUCCESS;
        } finally {
            $channel->close();
            $connection->close();
        }
    }
}