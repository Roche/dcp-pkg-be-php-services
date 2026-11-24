<?php

namespace Dcp\Services\MessageQueue;

use Dcp\Contracts\MessageQueueInterface;
use Illuminate\Support\Facades\Storage;
use Dcp\Helpers\Json;

class FileSystemMessageQueue implements MessageQueueInterface
{
    protected $storageDisk;
    protected $queuePath;
    protected $queueName;
    protected string $connectionName;

    public function __construct()
    {
        $this->storageDisk = Storage::disk('local');
        $this->queuePath = config('dcp.message_queue.file');
    }

    public function connectAs(string $connectionName = 'php-services-default'): self
    {
        $this->connectionName = $connectionName;
        return $this;
    }

    /**
     * Set the queue name/path.
     */
    public function setQueue(string $queueName): self
    {
        // Optionally, you can store the queue name if needed for other methods
        $this->queuePath = config('dcp.message_queue.file');
        $this->queueName = $queueName;
        return $this;
    }

    /**
     * Publish a message to a specified queue.
     */
    public function publish(string $queue, array $message, bool $addSignature = false): bool
    {
        $queueFile = "{$this->queuePath}/{$queue}.json";

        // Append each message as a new line in the JSON array format
        if (!$this->storageDisk->exists($queueFile)) {
            $this->storageDisk->put($queueFile, JSON::encode($message));
        } else {
            $existingMessages = JSON::decode($this->storageDisk->get($queueFile), true);
            $existingMessages[] = $message;
            $this->storageDisk->put($queueFile, JSON::encode($existingMessages));
        }

        return true;
    }

    /**
     * Publish a message to a specified queue via exchange.
     */
    public function exchangePublish(string $exchange, array | string $routingKeys, array $message): bool
    {
        $queueFile = "{$this->queuePath}/{$exchange}.json";

        // Append each message as a new line in the JSON array format
        if (!$this->storageDisk->exists($queueFile)) {
            $this->storageDisk->put($queueFile, JSON::encode($message));
        } else {
            $existingMessages = JSON::decode($this->storageDisk->get($queueFile), true);
            $existingMessages[] = $message;
            $this->storageDisk->put($queueFile, JSON::encode($existingMessages));
        }

        return true;
    }

    /**
     * Convert internal message to JSON
     */
    public function convertMessage($message): array
    {
        return $message;
    }

    /**
     * Consume messages from a specified queue and execute a callback.
     */
    public function consume(string $queue, callable $callback, bool $signed = false): void
    {
        $queueFile = "{$this->queuePath}/{$queue}.json";

        while (true) {
            if ($this->storageDisk->exists($queueFile)) {
                $messages = JSON::decode($this->storageDisk->get($queueFile), true) ?? [];

                foreach ($messages as $message) {
                    // Process each message
                    $callback($message);
                }

                // Clear the queue file after processing messages
                $this->storageDisk->put($queueFile, JSON::encode([]));
            }

            // Sleep for the defined interval (in seconds) before checking again
            sleep(2);
        }
    }

    /**
     * Return Message Id.
     */
    public function getMessageId(mixed $message): string
    {
        return hash('sha256', json_encode($message)); 
    }

    /**
     * Acknowledge a message.
     */
    public function acknowledge(string $messageId): bool
    {
        return true;
    }

    /**
     * Reject a message and optionally requeue it.
     */
    public function reject(string $messageId, bool $requeue = true): bool
    {
        return true;
    }

    /**
     * Close.
     */
    public function close(): void
    {
        //
    }

    /**
     * Bind a queue to an exchange (not applicable for file system, but required by interface).
     */
    public function bindQueue(string $queue, string $exchange, string $routingKey = ''): void
    {
        // No-op for file system implementation
    }

    /**
     * Initialize an exchange (not applicable for file system, but required by interface).
     */
    public function initializeExchange(string $exchange, string $exchangeType, bool $exchangeDurable): void
    {
        // No-op for file system implementation
    }

    /**
     * Initialize a queue (not applicable for file system, but required by interface).
     */
    public function initializeQueue(string $queue, array $options = []): void
    {
        // No-op for file system implementation
    }
}
