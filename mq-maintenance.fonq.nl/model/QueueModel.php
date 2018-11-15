<?php
namespace Model;

use Classes\Exception\HttpException;
use Classes\Logger;
use Classes\RabbitMq;

class QueueModel extends BaseModel
{
    private $arguments;
    private $auto_delete;
    private $backing_queue_status;
    private $consumer_utilisation;
    private $consumers;
    private $durable;
    private $effective_policy_definition;
    private $exclusive;
    private $exclusive_consumer_tag;
    private $garbage_collection;
    private $head_message_timestamp;
    private $idle_since;
    private $memory;
    private $message_bytes;
    private $message_bytes_paged_out;
    private $message_bytes_persistent;
    private $message_bytes_ram;
    private $message_bytes_ready;
    private $message_bytes_unacknowledged;
    private $messages;
    private $messages_details;
    private $messages_paged_out;
    private $messages_persistent;
    private $messages_ram;
    private $messages_ready;
    private $messages_ready_details;
    private $messages_ready_ram;
    private $messages_unacknowledged;
    private $messages_unacknowledged_details;
    private $messages_unacknowledged_ram;
    private $name;
    private $node;
    private $operator_policy;
    private $policy;
    private $recoverable_slaves;
    private $reductions;
    private $reductions_details;
    private $state;
    private $vhost;

    function __construct($queue)
    {
        // echo "<pre>" . print_r($queue, true) . "</pre>";
        $this->arguments = $queue['arguments'];
        $this->auto_delete = $queue['auto_delete'];

        $this->durable = $queue['durable'];
        $this->exclusive = $queue['exclusive'];


        if(isset($queue['messages_unacknowledged_ram']))
        {
            $this->messages_unacknowledged_ram = $queue['messages_unacknowledged_ram'];
        }
        if(isset($queue['messages_unacknowledged_ram']))
        {
            $this->messages_unacknowledged_ram = $queue['messages_unacknowledged_ram'];
        }
        if(isset($queue['messages_unacknowledged_details']))
        {
            $this->messages_unacknowledged_details = $queue['messages_unacknowledged_details'];
        }
        if(isset($queue['messages_unacknowledged']))
        {
            $this->messages_unacknowledged = $queue['messages_unacknowledged'];
        }
        if(isset($queue['messages_ready_ram']))
        {
            $this->messages_ready_ram = $queue['messages_ready_ram'];
        }
        if(isset($queue['messages_ready_details']))
        {
            $this->messages_ready = $queue['messages_ready_details'];
        }
        if(isset($queue['messages_ready']))
        {
            $this->messages_ready = $queue['messages_ready'];
        }
        if(isset($queue['messages_ram']))
        {
            $this->messages_ram = $queue['messages_ram'];
        }
        if(isset($queue['messages_persistent']))
        {
            $this->messages_persistent = $queue['messages_persistent'];
        }
        if(isset($queue['messages_paged_out']))
        {
            $this->messages_paged_out = $queue['messages_paged_out'];
        }
        if(isset($queue['messages_details']))
        {
            $this->messages_details = $queue['messages_details'];
        }
        if(isset($queue['messages']))
        {
            $this->messages = $queue['messages'];
        }
        if(isset($queue['message_bytes_unacknowledged']))
        {
            $this->message_bytes_unacknowledged = $queue['message_bytes_unacknowledged'];
        }
        if(isset($queue['message_bytes_ready']))
        {
            $this->message_bytes_ready = $queue['message_bytes_ready'];
        }
        if(isset($queue['message_bytes_ram']))
        {
            $this->message_bytes_ram = $queue['message_bytes_ram'];
        }
        if(isset($queue['message_bytes_persistent']))
        {
            $this->message_bytes_persistent = $queue['message_bytes_persistent'];
        }
        if(isset($queue['message_bytes_paged_out']))
        {
            $this->message_bytes_paged_out = $queue['message_bytes_paged_out'];
        }
        if(isset($queue['message_bytes']))
        {
            $this->message_bytes = $queue['message_bytes'];
        }
        if(isset($queue['memory']))
        {
            $this->memory = $queue['memory'];
        }
        if(isset($queue['idle_since']))
        {
            $this->idle_since = $queue['idle_since'];
        }
        if(isset($queue['head_message_timestamp']))
        {
            $this->head_message_timestamp = $queue['head_message_timestamp'];
        }
        if(isset($queue['garbage_collection']))
        {
            $this->garbage_collection = $queue['garbage_collection'];
        }
        if(isset($queue['exclusive_consumer_tag']))
        {
            $this->exclusive_consumer_tag = $queue['exclusive_consumer_tag'];
        }
        if(isset($queue['effective_policy_definition']))
        {
            $this->effective_policy_definition = $queue['effective_policy_definition'];
        }
        if(isset($queue['consumers']))
        {
            $this->consumers = $queue['consumers'];
        }
        if(isset($queue['consumer_utilisation']))
        {
            $this->consumer_utilisation = $queue['consumer_utilisation'];
        }
        if(isset($queue['backing_queue_status']))
        {
            $this->backing_queue_status = $queue['backing_queue_status'];
        }
        if(isset($queue['name']))
        {
            $this->name = $queue['name'];
        }
        if(isset($queue['node']))
        {
            $this->node = $queue['node'];
        }
        if(isset($queue['operator_policy']))
        {
            $this->operator_policy = $queue['operator_policy'];
        }
        if(isset($queue['policy']))
        {
            $this->policy = $queue['policy'];
        }
        if(isset($queue['recoverable_slaves']))
        {
            $this->recoverable_slaves = $queue['recoverable_slaves'];
        }
        if(isset($queue['reductions']))
        {
            $this->reductions = $queue['reductions'];
        }
        if(isset($queue['reductions_details']))
        {
            $this->reductions_details = $queue['reductions_details'];
        }
        if(isset($queue['state']))
        {
            $this->state = $queue['state'];
        }

        $this->vhost = $queue['vhost'];
    }

    /**
     * @throws HttpException
     */
    function clearBindings()
    {
        $bindings = $this->getBindings();

        if($bindings->count() > 0)
        {
            foreach ($bindings as $binding)
            {
                if(empty($binding->getSource()))
                {
                    // The queue also had a binding for direct messages which cannot be deleted.
                    continue;
                }
                $binding->clear();
            }
        }
    }

    /**
     * @return BindingList
     * @throws HttpException
     */
    function getBindings()
    {
        return RabbitMq::instance()->getBindings($this);
    }

    static function getKnownArguments():array
    {
        $out = [
            'x-message-ttl' => ['datatype' => 'int', 'label' => 'Message TTL'],
            'x-expires'  => ['datatype' => 'int', 'label' => 'Auto expire'],
            'x-max-length' => ['datatype' => 'int', 'label' => 'Max length'],
            'x-max-length-bytes' => ['datatype' => 'int', 'label' => 'Max length bytes'],
            'x-overflow' => ['datatype' => 'string', 'label' => 'Overflow behaviour'],
            'x-dead-letter-exchange' => ['datatype' => 'string', 'label' => 'Dead letter exchange'],
            'x-dead-letter-routing-key' => ['datatype' => 'string', 'label' => 'Dead letter routing key'],
            'x-queue-master-locator' => ['datatype' => 'string', 'label' => 'Master locator'],
            'x-max-priority' => 'int'
        ];
        try{
            $api_version_uniform = RabbitMq::instance()->getApiVersion('api_version_uniform');
        }
        catch (\Exception $e)
        {
            // Just continue, exception is catched and logged elsewhere already.
        }

        if($api_version_uniform > 35000)
        {
            $out['x-max-priority'] = ['datatype' => 'string', 'label' => 'Maximum priority'];
        }

        // Since RabbitMQ 3.6.0, the broker has the concept of Lazy Queues
        if($api_version_uniform > 36000)
        {
            $out['x-queue-mode'] = ['datatype' => 'string', 'label' => 'Lazy mode'];
        }

        return $out;

    }

    function toApi(): array
    {
        Logger::log(__METHOD__ . " available arguments " . json_encode($this->arguments), Logger::VERBOSE);

        $aKnownArguments = self::getKnownArguments();

        $arguments = [];
        if(!empty($this->arguments))
        {
            foreach($this->arguments as $key => $value)
            {
                if(empty($value))
                {
                    continue;
                }
                if(!isset($aKnownArguments[$key]))
                {
                    throw new \LogicException("The specified argument $key is not known by the system.");
                }

                if($aKnownArguments[$key]['datatype'] == 'int')
                {
                    $arguments[$key] = (int) $value;
                }
                else
                {
                    $arguments[$key] = $value;
                }
            }
        }

        return [
            'arguments' => $arguments,
            'auto_delete' => $this->auto_delete,
            'backing_queue_status' => $this->backing_queue_status,
            'consumer_utilisation' => $this->consumer_utilisation,
            'consumers' => $this->consumers,
            'durable' => $this->durable,
            'effective_policy_definition' => $this->effective_policy_definition,
            'exclusive' => $this->exclusive,
            'exclusive_consumer_tag' => $this->exclusive_consumer_tag,
            'garbage_collection' => $this->garbage_collection,
            'head_message_timestamp' => $this->head_message_timestamp,
            'idle_since' => $this->idle_since,
            'memory' => $this->memory,
            'message_bytes' => $this->message_bytes,
            'message_bytes_paged_out' => $this->message_bytes_paged_out,
            'message_bytes_persistent' => $this->message_bytes_persistent,
            'message_bytes_ram' => $this->message_bytes_ram,
            'message_bytes_ready' => $this->message_bytes_ready,
            'message_bytes_unacknowledged' => $this->messages_unacknowledged,
            'messages' => $this->message_bytes_paged_out,
            'messages_details' => $this->messages_details,
            'messages_paged_out' => $this->messages_paged_out,
            'messages_persistent' => $this->messages_persistent,
            'messages_ram' => $this->messages_ram,
            'messages_ready' => $this->messages_ready,
            'messages_ready_details' => $this->messages_ready_details,
            'messages_ready_ram' => $this->messages_ready_ram,
            'messages_unacknowledged' => $this->messages_unacknowledged,
            'messages_unacknowledged_details' => $this->messages_unacknowledged_details,
            'messages_unacknowledged_ram' => $this->messages_unacknowledged_ram,
            'name' => $this->name,
            'node' => $this->node,
            'operator_policy' => $this->operator_policy,
            'policy' => $this->policy,
            'recoverable_slaves' => $this->recoverable_slaves,
            'reductions' => $this->reductions,
            'reductions_details' => $this->reductions_details,
            'state' => $this->state,
            'vhost' => $this->vhost,
        ];
    }
    function setArguments($arguments)
    {
        $this->arguments = $arguments;
    }
    function setAutoDelete($auto_delete)
    {
        $this->auto_delete = $auto_delete;
    }
    function setDurable($durable)
    {
        $this->durable = $durable;
    }
    function getDurable()
    {
        return $this->durable;
    }
    function getPolicy()
    {
        return $this->policy;
    }
    function countArguments()
    {
        return count($this->arguments);
    }
    function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param string $ack_requeue - ackmode determines whether the messages will be removed from the queue. If ackmode is ack_requeue_true or reject_requeue_true they will be requeued - if ackmode is ack_requeue_false or reject_requeue_false they will be removed.
     * @param int $limit
     * @return MessageList
     * @throws \Classes\Exception\HttpException
     */
    function getMessageList($ack_requeue, $limit = 50):MessageList
    {
        return RabbitMq::instance()->getMessages($ack_requeue, $this->getVHost(), $this->getName(), $limit);
    }

    /**
     * Returns the number of messages, not the actual messages.
     * @return int
     */
    function getMessages()
    {
        return $this->messages;
    }
    function getMessagesUnacknowledged()
    {
        return $this->messages_unacknowledged;
    }
    function getMessagesReady()
    {
        return $this->messages_ready;
    }
    function setVHost($vhost)
    {
        $this->vhost = $vhost;
    }
    function getVHost()
    {
        return $this->vhost;
    }
    function setName($name)
    {
        $this->name = $name;
    }
    function getName()
    {
        return $this->name;
    }
}