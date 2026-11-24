<?php

namespace Dcp\Factories;

use Dcp\Contracts\MessageQueueInterface;
use Dcp\Services\MessageQueue\RabbitMq;
use Dcp\Services\MessageQueue\FileSystemMessageQueue;
use Dcp\Exceptions\InvalidClientTypeException;

class MessageQueueFactory
{
    public static function create(?string $queueDriver = null): MessageQueueInterface
    {
        $queue = $queueDriver ?? config('dcp.message_queue.driver');
        return match ($queue) {
            'rabbitmq' => new RabbitMq(),
            'file' => new FileSystemMessageQueue(),
            default => throw new InvalidClientTypeException("Invalid message queue type"),
        };
    }
}
