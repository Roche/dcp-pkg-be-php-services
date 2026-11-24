<?php

namespace Dcp\Contracts;

interface MessageQueueInterface
{
    public function connectAs(string $connectionName = 'php-services-default'): self;

    public function getMessageId(mixed $message): string;

    public function convertMessage(mixed $message): array;

    public function publish(string $queue, array $message, bool $addSignature = false): bool;

    public function exchangePublish(string $exchange, array | string $routingKeys, array $message): bool;

    public function consume(string $queue, callable $callback, bool $signed = false): void;

    public function acknowledge(string $messageId): bool;

    public function reject(string $messageId, bool $requeue = true): bool;

    public function setQueue(string $queue): self;

    public function bindQueue(string $queue, string $exchange, string $routingKey): void;

    public function initializeExchange(string $exchange, string $exchangeType, bool $exchangeDurable): void;

    public function initializeQueue(string $queue): void;

    public function close(): void;
}
