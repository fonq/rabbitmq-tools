<?php
namespace Model;

use Classes\RabbitMq;
use LogicException;
use Classes\Exception\HttpException;

class VHostModel extends BaseModel
{
    private $cluster_state;
    private $message_stats;
    private $messages;
    private $messages_details;
    private $messages_ready;
    private $messages_ready_details;
    private $messages_unacknowledged;
    private $messages_unacknowledged_details;
    private $name;

    function toApi(): array
    {
        throw new LogicException(__METHOD__ . ' not implemented yet');
    }

    function __construct($vhost)
    {
        if(isset($vhost['cluster_state']))
        {
            $this->cluster_state = $vhost['cluster_state'];
        }

        if (isset($vhost['message_stats']))
        {
            $this->message_stats = $vhost['message_stats'];
        }

        if (isset($vhost['messages'])) {
            $this->messages = $vhost['messages'];
        }

        if (isset($vhost['messages_details']))
        {
            $this->messages_details = $vhost['messages_details'];
        }

        if (isset($vhost['messages_ready']))
        {
            $this->messages_ready = $vhost['messages_ready'];
        }
        if (isset($vhost['messages_ready_details']))
        {
            $this->messages_ready_details = $vhost['messages_ready_details'];
        }

        if (isset($vhost['messages_unacknowledged']))
        {
            $this->messages_unacknowledged = $vhost['messages_unacknowledged'];
        }

        if(isset($vhost['messages_unacknowledged_details']))
        {
            $this->messages_unacknowledged_details = $vhost['messages_unacknowledged_details'];
        }

        if(isset($vhost['name']))
        {
            $this->name = $vhost['name'];
        }
    }

    /**
     * @return QueueList
     * @throws HttpException
     */
    function getQueues():QueueList
    {
        return RabbitMq::instance()->getVhostQueues($this->getName());
    }
    public function getName()
    {
        return $this->name;
    }
}