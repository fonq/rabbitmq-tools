<?php
namespace Model;

use Classes\RabbitMq;
use Classes\Exception\HttpException;

class QueueList extends BaseList
{
    function fromApi($data):BaseModel
    {
        return new QueueModel($data);
    }
    function current():QueueModel
    {
        return parent::current();
    }

    /**
     * @return array
     * @throws HttpException
     */
    static function getAllQueuesByBinding():array
    {
        $queues = RabbitMq::instance()->getQueues();

        $all_queue_bindings = [];
        foreach ($queues as $queue)
        {
            $bindings = $queue->getBindings();
            foreach ($bindings as $binding)
            {
                if(!$binding instanceof BindingModel)
                {
                    throw new \LogicException("Expected an instance of BindingModel");
                }
                if(!isset($all_queue_bindings[$binding->getVhost()]))
                {
                    $all_queue_bindings[$binding->getVhost()] = [];
                }
                if(!isset($all_queue_bindings[$binding->getVhost()][$binding->getRoutingKey()]))
                {
                    $all_queue_bindings[$binding->getVhost()][$binding->getRoutingKey()] = [];
                }
                $all_queue_bindings[$binding->getVhost()][$binding->getRoutingKey()][] =  $queue;
            }
        }
        return $all_queue_bindings;
    }
}