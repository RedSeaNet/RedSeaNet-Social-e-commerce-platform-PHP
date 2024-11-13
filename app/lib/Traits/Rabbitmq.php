<?php

namespace Redseanet\Lib\Traits;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Database handler
 */
trait Rabbitmq
{
    protected $rabbitmqConnection = null;
    protected $rabbitmqChannel = null;

    protected function getRabbitmqConnection()
    {
        if (is_null($this->rabbitmqConnection)) {
            $this->rabbitmqConnection = $this->getContainer()->get('mqAdapter');
        }
        return $this->rabbitmqConnection;
    }

    protected function createRabbitmqChannel()
    {
        $this->rabbitmqChannel = $this->rabbitmqConnection->channel();
        return $this->rabbitmqChannel;
    }

    protected function declareRabbitmqQueue(
        $queue = 'msgs',
        $passive = false,
        $durable = false,
        $exclusive = false,
        $auto_delete = true,
        $nowait = false,
        $arguments = [],
        $ticket = null
    ) {
        $this->rabbitmqChannel->queue_declare($queue, $passive, $durable, $exclusive, $auto_delete, $nowait, $arguments, $ticket);
        return $this->rabbitmqChannel;
    }

    protected function declareRabbitmqExchange(
        $queue = 'msgs',
        $type = 'DIRECT',
        $exchange = 'router',
        $passive = false,
        $durable = false,
        $auto_delete = true,
        $internal = false,
        $nowait = false,
        $arguments = [],
        $ticket = null
    ) {
        $this->rabbitmqChannel->exchange_declare(
            $exchange,
            AMQPExchangeType::DIRECT,
            $passive,
            $durable,
            $auto_delete,
            $internal,
            $nowait,
            $arguments,
            $ticket
        );
        $this->rabbitmqChannel->queue_bind($queue, $exchange);
        return $this->rabbitmqChannel;
    }

    protected function sendPublishMqMessage($messageBody = '', $exchange = 'router')
    {
        $message = new AMQPMessage($messageBody, ['content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
        $this->rabbitmqChannel->basic_publish($message, $exchange);
    }

    protected function rabbitMqBasicConsume(
        $queue = 'msgs',
        $consumer_tag = 'consumer_tag',
        $no_local = false,
        $no_ack = false,
        $exclusive = false,
        $nowait = false,
        $callback = null,
        $ticket = null,
        $arguments = []
    ) {
        $this->rabbitmqChannel->basic_consume(
            $queue,
            $consumer_tag,
            $no_local,
            $no_ack,
            $exclusive,
            $nowait,
            $callback,
            $ticket,
            $arguments
        );
        $this->rabbitmqChannel->consume();
    }
}
