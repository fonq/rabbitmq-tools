<?php
namespace Classes;

use Model\BaseModel;
use Model\BindingList;
use Model\BindingModel;
use Model\ExchangeModel;
use Model\MessageList;
use Model\MessageModel;
use Model\QueueList;
use Model\QueueModel;
use Model\VHostList;
use Model\ExchangeList;
use LogicException;
use Model\VHostModel;
use InvalidArgumentException;

class RabbitMq
{
    private static $api;
    private static $instance;

    private static function api():Api
    {
        return self::$api;
    }
    public static function instance():RabbitMq
    {
        if(!self::$instance instanceof RabbitMq)
        {
            $config = getConfig();
            self::$instance = new self($config['api_user'], $config['api_password'], $config['api_hostname'], $config['api_port']);
        }
        return self::$instance;
    }
    private function __construct($api_user, $api_pass, $api_host, $api_port)
    {
        self::$api = new Api($api_user, $api_pass, $api_host, $api_port);
    }

    /**
     * @param string $ack_requeue - ackmode determines whether the messages will be removed from the queue. If ackmode is ack_requeue_true or reject_requeue_true they will be requeued - if ackmode is ack_requeue_false or reject_requeue_false they will be removed.
     * @param string $vhost_name
     * @param string $queue_name
     * @return MessageList
     * @throws Exception\HttpException
     * @throws InvalidArgumentException
     */
    function getMessages($ack_requeue, $vhost_name, $queue_name):MessageList
    {
        if(!in_array($ack_requeue, ['ack_requeue_false', 'ack_requeue_true']))
        {
            throw new InvalidArgumentException("False parameter given for ack_requeue, please resolve this bug. (see phpdoc)");
        }
        $endpoint = '/queues/' . rawurlencode($vhost_name) . '/' . rawurlencode($queue_name) . '/get';

        $model = (new class extends BaseModel{
            private $ack_requeue;

            function setAckRequeue($ack_requeue)
            {
                $this->ack_requeue = $ack_requeue;
            }
            function __construct($message = []) {}
            function toApi(): array
            {
                /**
                 * count controls the maximum number of messages to get.
                 * You may get fewer messages than this if the queue cannot immediately provide them.
                 *
                 * ackmode determines whether the messages will be removed from the queue.
                 * If ackmode is ack_requeue_true or reject_requeue_true they will be requeued - if ackmode is ack_requeue_false or reject_requeue_false they will be removed.
                 *
                 * encoding must be either "auto" (in which case the payload will be returned as a string if it is valid UTF-8,
                 * and base64 encoded otherwise), or "base64" (in which case the payload will always be base64 encoded).
                 *
                 * If truncate is present it will truncate the message payload if it is larger than the size given (in bytes).
                 */
                return [
                    "count" => 5000,
                    "ackmode" => $this->ack_requeue, // we are offering the messages manually
                    "encoding" => "auto",
                    "truncate" => 5000
                ];
            }
        });
        $model->setAckRequeue($ack_requeue);

        $messages = self::api()->post($endpoint, $model);
        if(empty($messages))
        {
            return new MessageList([]);
        }

        return new MessageList($messages);
    }

    /**
     * @return QueueList
     * @throws Exception\HttpException
     */
    function getQueues():QueueList
    {
        $queues = self::api()->get('/queues');
        return new QueueList($queues);
    }

    /**
     * @param $vhost_name
     * @return QueueList
     * @throws Exception\HttpException
     */
    function getVhostQueues($vhost_name)
    {
        $endpoint = '/queues/' . rawurlencode($vhost_name);
        $queues = self::api()->get($endpoint);
        return new QueueList($queues);
    }

    /**
     * @param ExchangeModel $exchange
     * @throws Exception\HttpException
     */
    function addExchange(ExchangeModel $exchange)
    {
        $endpoint = '/exchanges/' . rawurlencode($exchange->getVhost()) .
                    '/' . rawurlencode($exchange->getName());

        self::api()->put($endpoint, $exchange);
    }

    /**
     * @param $vhost
     * @param $exchange_name
     * @throws Exception\HttpException
     */
    function deleteExchange($vhost, $exchange_name):void
    {
        self::api()->delete('/exchanges/' . rawurlencode($vhost) . '/' . rawurlencode($exchange_name));
    }

    /**
     * @param $vhost
     * @param $exchange_name
     * @return ExchangeModel
     * @throws Exception\HttpException
     */
    function getExchange($vhost, $exchange_name):ExchangeModel
    {
        $exchange = self::api()->get('/exchanges/' . rawurlencode($vhost) . '/' . rawurlencode($exchange_name));
        return new ExchangeModel($exchange);
    }

    /**
     * @param $vhost
     * @param $exchange_name
     * @param MessageModel $model
     * @throws Exception\HttpException
     */
    function publishMessage($vhost, $exchange_name, MessageModel $model)
    {
        $endpoint = '/exchanges/' . rawurlencode($vhost) . '/' . rawurlencode($exchange_name) . '/publish';
        self::api()->post($endpoint, $model);
    }

    /**
     * @param null $vhost
     * @return ExchangeList
     * @throws Exception\HttpException
     */
    function getExchanges($vhost = null):ExchangeList
    {
        if($vhost === null)
        {
            $exchanges = self::api()->get('/exchanges');
        }
        else
        {
            $exchanges = self::api()->get('/exchanges/' . rawurlencode($vhost));
        }
        return new ExchangeList($exchanges);
    }

    /**
     * @param $vhost
     * @param $source
     * @param $queue
     * @param $properties_key
     * @return BindingModel
     * @throws Exception\HttpException
     */
    function getBinding($vhost, $source, $queue, $properties_key):BindingModel
    {
        $endpoint = '/bindings/' . rawurlencode($vhost) . '/e/' . rawurlencode($source) . '/q/' . rawurlencode($queue) . '/' . rawurlencode($properties_key);
        $binding = self::api()->get($endpoint);
        return new BindingModel($binding);
    }

    /**
     * Gives the bindings of a queue or of an exchange, depending on what you feed it.
     * @param BaseModel $model must be of type QueueModel or ExchangeModel
     * @return BindingList
     * @throws Exception\HttpException
     */
    function getBindings(BaseModel $model):BindingList
    {
        if($model instanceof QueueModel)
        {
            $endpoint = '/queues/' . rawurlencode($model->getVHost()) . '/' . rawurlencode($model->getName()) . '/bindings';
        }
        else if($model instanceof ExchangeModel)
        {
            $endpoint = '/exchanges/' . rawurlencode($model->getVHost()) . '/' . rawurlencode($model->getName()) . '/bindings/source';
        }
        else
        {
            throw new LogicException("Only QueueModel and ExchangeModel are supported");
        }
        $bindings = self::api()->get($endpoint);
        return new BindingList($bindings);
    }

    /**
     * @param BindingModel $binding
     * @throws Exception\HttpException
     */
    function addBinding(BindingModel $binding)
    {
        $endpoint = '/bindings/' . rawurlencode($binding->getVhost()) .
                    '/e/' . rawurlencode($binding->getSource()) .
                    '/q/' . rawurlencode($binding->getDestination());

        self::api()->post($endpoint, $binding);
    }

    /**
     * @param BindingModel $binding
     * @throws Exception\HttpException
     */
    function clearBinding(BindingModel $binding):void
    {
        $endpoint = '/bindings/' . rawurlencode($binding->getVhost()) .
            '/e/' . rawurlencode($binding->getSource()) .
            '/q/' . rawurlencode($binding->getDestination()) .
            '/' . $binding->getPropertiesKey();

        self::api()->delete($endpoint);
    }

    /**
     * @param QueueModel $originalQueue
     * @param $new_name
     * @return QueueModel
     * @throws Exception\HttpException
     */
    function copyQueue(QueueModel $originalQueue, $new_name):QueueModel
    {
        $newQueue = clone $originalQueue;
        $newQueue->setName($new_name);
        $this->createQueue($newQueue);

        $originalBindings = $originalQueue->getBindings();

        foreach ($originalBindings as $binding)
        {
            if(!$binding instanceof BindingModel)
            {
                throw new LogicException("Expected an instance of BindingModel");
            }
            if(empty($binding->getSource()))
            {
                // Every queue has a binding with the same name as the queue and an empty source.
                continue;
            }

            // Messages should end up in the same queue, rest of the properties should remain the same.
            $binding->setDestination($newQueue->getName());
            $this->addBinding($binding);
        }
        return $newQueue;
    }

    /**
     * @param QueueModel $newQueue
     * @return null
     * @throws Exception\HttpException
     */
    function createQueue(QueueModel $newQueue)
    {
        $endpoint = '/queues/' . rawurlencode($newQueue->getVHost()) . '/' . rawurlencode($newQueue->getName());
        self::api()->put($endpoint, $newQueue);
        return null;
    }
    /*
    function unbindQueue(QueueModel $newQueue)
    {
        $this->getExchanges();
        // /api/bindings/vhost/e/exchange/q/queue

        $endpoint = '/queues/' . rawurlencode($newQueue->getVHost()) . '/' . rawurlencode($newQueue->getName());
        self::api()->put($endpoint, $newQueue);
        return null;
    }
    */

    /**
     * @param $vhost_name
     * @param $queue_name
     * @return QueueModel
     * @throws Exception\HttpException
     */
    function getQueue($vhost_name, $queue_name)
    {
        $endpoint = '/queues/' . rawurlencode($vhost_name) . '/' . rawurlencode($queue_name);
        $queue = self::api()->get($endpoint);
        return new QueueModel($queue);
    }

    /**
     * @param $vhost_name
     * @param $queue_name
     * @throws Exception\HttpException
     */
    function deleteQueue($vhost_name, $queue_name):void
    {
        $endpoint = '/queues/' . rawurlencode($vhost_name) . '/' . rawurlencode($queue_name);
        self::api()->delete($endpoint);
    }

    /**
     * @param $vhost_name
     * @return VHostModel
     * @throws Exception\HttpException
     */
    function getVhost($vhost_name):VHostModel
    {
        $vhost = self::api()->get('/vhosts/' . rawurlencode($vhost_name));
        return new VHostModel($vhost);
    }

    /**
     * @return VHostList
     * @throws Exception\HttpException
     */
    function getVHosts():VHostList
    {
        $vhosts = self::api()->get('/vhosts');
        return new VHostList($vhosts);
    }
}

