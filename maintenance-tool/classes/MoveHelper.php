<?php
namespace Classes;

use Model\BindingModel;
use Model\MessageList;
use Model\MessageModel;
use LogicException;

class MoveHelper
{
    /**
     * This method:
     * 1. Creates a temporary topic exchange.
     * 2. Adds bindings between exchange and the destination queue.
     * 3. Forwards the messages to the exchange that will use the original routing_keys to move the messages.
     * 4. Deletes the exchange and because of that automatically also deletes the routing keys.
     *
     * @param $from_vhost
     * @param $from_queue
     * @param $to_vhost
     * @param $to_queue
     * @throws Exception\HttpException
     */
    public static function moveMessages($from_vhost, $from_queue, $to_vhost, $to_queue)
    {
        $to_exchange = self::createExchange($to_vhost);
        $messages = self::getMessages($from_vhost, $from_queue);

        self::createBindings($from_queue, $to_vhost, $to_exchange, $to_queue, $messages);

        // If the routing key is the same as the queue name we assume direct
        $messages = self::updateDirectMessages($from_queue, $to_queue, $messages);
        self::requeueMessages($to_vhost, $to_exchange, $to_queue, $messages);
        RabbitMq::instance()->deleteExchange($to_vhost, $to_exchange);
    }

    private static function updateDirectMessages($from_queue, $to_queue, $messages)
    {
        foreach ($messages as $message)
        {
            if(!$message instanceof MessageModel)
            {
                throw new LogicException("Expected an instance of MessageModel.");
            }
            if($message->getRoutingKey() == $from_queue)
            {
                $message->setRoutingKey($to_queue);
            }
        }
        return $messages;
    }

    private static function getMessages($from_vhost, $from_queue)
    {
        $messages = RabbitMq::instance()->getMessages('ack_requeue_false', $from_vhost, $from_queue);
        return $messages;
    }

    public static function requeueMessages($to_vhost, $to_exchange, $to_queue, $messages)
    {
        foreach ($messages as $message)
        {
            if(!$message instanceof MessageModel)
            {
                throw new LogicException("Expected an instance of MessageModel.");
            }
            $message->setExchange($to_exchange);
            $message->setVhost($to_vhost);
            $message->setDeliveryMode(2);

            if($message->getRoutingKey() == $to_queue)
            {
                RabbitMq::instance()->publishMessage($to_vhost, 'amq.default', $message);
            }
            else
            {
                RabbitMq::instance()->publishMessage($to_vhost, $to_exchange, $message);
            }
        }
    }

    public static function createBindings($from_queue, $vhost_name, $exchange_name, $to_queue, MessageList $messages)
    {
        $bindings_to_be_added = [];

        foreach ($messages as $message)
        {
            if(!$message instanceof MessageModel)
            {
                throw new LogicException("Expected an instance of MessageModel.");
            }

            if($from_queue == $message->getRoutingKey())
            {
                continue;
            }
            /*
            if($to_queue == $message->getRoutingKey())
            {
                continue;
            }
            */
            $bindings_to_be_added[$message->getRoutingKey()] = $message->getRoutingKey();
        }

        foreach($bindings_to_be_added as $routing_key)
        {
            $binding = new BindingModel();
            $binding->setSource($exchange_name);
            $binding->setVhost($vhost_name);
            $binding->setDestination($to_queue);
            $binding->setDestinationType('queue');
            $binding->setPropertiesKey($routing_key);
            $binding->setRoutingKey($routing_key);
            RabbitMq::instance()->addBinding($binding);
        }

    }
    public static function createExchange($vhost)
    {
        $tmpExchangeName = time() . 'temporaryexchange';
        $template_exchange = RabbitMq::instance()->getExchange($vhost, 'amq.topic');
        $template_exchange->setName($tmpExchangeName);
        RabbitMq::instance()->addExchange($template_exchange);
        return $tmpExchangeName;
    }
}