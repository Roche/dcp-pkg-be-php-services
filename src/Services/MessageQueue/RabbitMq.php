<?php

namespace Dcp\Services\MessageQueue;

use Dcp\Contracts\MessageQueueInterface;
use Dcp\Helpers\Json;
use Exception;
use JsonException;
use PhpAmqpLib\Channel\AbstractChannel;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPConnectionFactory;
use PhpAmqpLib\Connection\AMQPConnectionConfig;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMq implements MessageQueueInterface
{
    protected AbstractConnection $con;
    protected AMQPChannel $channel;
    protected AMQPConnectionConfig $config;
    protected string $connectionName;
    protected array $conInfo = [];

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->config = new AMQPConnectionConfig();

        $this->config->setHost(config('dcp.message_queue.rabbitmq.host'));
        $this->config->setPort(config('dcp.message_queue.rabbitmq.port'));
        $this->config->setUser(config('dcp.message_queue.rabbitmq.username'));
        $this->config->setPassword(config('dcp.message_queue.rabbitmq.password'));

        if (!empty(config('dcp.message_queue.rabbitmq.ssl'))) {
            $this->config->setIsSecure(true);
            $this->config->setSslVerify(config('dcp.message_queue.rabbitmq.ssl_verify'));
            $this->config->setSslCaPath(config('dcp.message_queue.rabbitmq.ssl'));
            $this->config->setSslPassPhrase(config('dcp.message_queue.rabbitmq.ssl_passphrase'));
        }
    }

    public function connectAs(string $connectionName = 'php-services-default'): self
    {
        $this->config->setConnectionName($connectionName);
        $this->connectionName = $connectionName;

        $this->con = (new AMQPConnectionFactory())->create($this->config);
        $this->channel = $this->con->channel();

        return $this;
    }

    public function getChannel(): AbstractChannel|AMQPChannel
    {
        return $this->channel;
    }

    public function setQueue(string $queueName): RabbitMq
    {
        $this->channel->queue_declare($queueName, false, true, false, false);
        $this->conInfo['queue_name'] = $queueName;
        return $this;
    }

    public function getQueue(): string
    {
        return $this->conInfo['queue_name'];
    }

    public function bindQueue(string $queue, string $exchange, string $routingKey): void
    {
        $this->channel->queue_bind($queue, $exchange, $routingKey);
    }

    public function initializeExchange(string $exchange, string $exchangeType, bool $exchangeDurable): void
    {
        $this->channel->exchange_declare(
            $exchange,
            $exchangeType,
            false,
            $exchangeDurable,
            false
        );
    }

    public function initializeQueue(string $queue): void
    {
        $this->channel->queue_declare($queue, false, true, false, false);
    }

    public function getMessageId(mixed $message): string
    {
        return $message->getDeliveryTag();
    }

    /**
     * Publish a message to a specified queue.
     */
    public function publish(string $queue, array $message, bool $addSignature = false): bool
    {
        $this->setQueue($queue);

        if (is_array($message)) {
            $message = Json::encode(
                $addSignature ? signMqMessage($message) : $message
            );
        }

        $msg = new AMQPMessage(
            $message,
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );

        $this->channel->basic_publish($msg, '', $queue);

        return true;
    }

    /**
     * Publish a message to a specified queue via exchange.
     */
    public function exchangePublish(string $exchange, array | string $routingKeys, array $message): bool
    {
        if (is_array($routingKeys)) {
            $routingKeys = implode(',', $routingKeys);
        }

        if (is_array($message)) {
            $message = Json::encode(
                $message
            );
        }

        $msg = new AMQPMessage(
            $message,
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );

        $this->channel->basic_publish($msg, $exchange, $routingKeys);

        return true;
    }

    /**
     * Convert internal message to JSON
     */
    public function convertMessage($message): array
    {
        return Json::decodeAssoc($message->getBody());
    }

    /**
     * Consume messages from a specified queue and execute a callback.
     */
    public function consume(string $queue, callable $callback, bool $signed = false): void
    {
        $this->setQueue($queue);

        $this->channel->basic_consume($queue, '', false, false, false, false, function (AMQPMessage $msg) use ($callback, $signed) {
            $callback(
                $signed ? $this->parseMessage($msg) : $msg
            );
        });

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    /**
     * Acknowledge a message.
     */
    public function acknowledge(string $messageId): bool
    {
        try {
            $this->channel->basic_ack($messageId);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Reject a message and optionally requeue it.
     */
    public function reject(string $messageId, bool $requeue = true): bool
    {
        try {
            $this->channel->basic_nack($messageId, false, $requeue);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Parse and validate the message body.
     */
    public function parseMessage(AMQPMessage $message): array|false
    {
        try {
            $jsonMsg = Json::decodeAssoc($message->body);
            $newHash = md5(Json::encode($jsonMsg['payload']) . env('app.key'));

            if ($newHash !== $jsonMsg['signature']) {
                return false;
            }

            return $jsonMsg['payload'];
        } catch (JsonException $e) {
            return false;
        }
    }

    /**
     * Close RabbitMQ connections.
     */
    public function close(): void
    {
        $this->channel->close();
        $this->con->close();
    }
}
