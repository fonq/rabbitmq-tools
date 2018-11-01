<?php
namespace Classes;

use ArrayIterator;
use Model\QueueModel;

class Iterator extends ArrayIterator
{
    function __construct(array $queues = array(), int $flags = 0)
    {
        $queueObjects = [];
        foreach ($queues as $queue)
        {
            $queueObjects[] = new QueueModel($queue);
        }
        parent::__construct($queueObjects, $flags);
    }
    function current():QueueModel
    {
        return parent::current();
    }
}