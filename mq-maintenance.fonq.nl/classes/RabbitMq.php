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
use Model\UserList;
use Model\VHostList;
use Model\ExchangeList;
use LogicException;
use Model\VHostModel;
use InvalidArgumentException;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitMq
{
    private static $api;
    private static $instance;

    private static function api():Api
    {
        return self::$api;
    }
    public static function isLoggedIn():bool
    {
        return self::api()->isLoggedIn();
    }
    public static function instance():RabbitMq
    {
        if(!self::$instance instanceof RabbitMq)
        {
            $config = getConfig();
            self::$instance = new self($config['api_hostname'], $config['api_port']);
        }
        return self::$instance;
    }
    private function __construct($api_host, $api_port)
    {
        self::$api = new Api($api_host, $api_port);
    }

    /**
     * @param string $ack_requeue - ackmode determines whether the messages will be removed from the queue. If ackmode is ack_requeue_true or reject_requeue_true they will be requeued - if ackmode is ack_requeue_false or reject_requeue_false they will be removed.
     * @param string $vhost_name
     * @param string $queue_name
     * @param int $limit
     * @return MessageList
     * @throws Exception\HttpException
     * @throws InvalidArgumentException
     */
    function getMessages($ack_requeue, $vhost_name, $queue_name, $limit = 50):MessageList
    {
        if(!in_array($ack_requeue, ['ack_requeue_false', 'ack_requeue_true']))
        {
            throw new InvalidArgumentException("False parameter given for ack_requeue, please resolve this bug. (see phpdoc)");
        }
        $endpoint = '/queues/' . rawurlencode($vhost_name) . '/' . rawurlencode($queue_name) . '/get';

        $model = (new class extends BaseModel{
            private $ack_requeue;
            private $limit = 50;

            function setAckRequeue($ack_requeue)
            {
                $this->ack_requeue = $ack_requeue;
            }
            function setLimit($limit)
            {
                $this->limit = $limit;
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
                    // requeue < 3.6.12, ackmode >= 3.7
                    "requeue" => $this->ack_requeue == 'ack_requeue_true' ? true : false,
                    "ackmode" => $this->ack_requeue, // we are offering the messages manually
                    "count" => $this->limit,
                    "encoding" => "auto",
                    "truncate" => 5000
                ];
            }
        });

        $model->setAckRequeue($ack_requeue);
        $model->setLimit($limit);

        $messages = self::api()->post($endpoint, $model);
        if(empty($messages))
        {
            return new MessageList([]);
        }
        return new MessageList($messages);
    }

    /**
     * @param string $key specify what we should we return from the api call /api/overview
     * @return string|int
     * @throws Exception\HttpException
     */
    function getApiVersion($key = 'management_version')
    {
        $endpoint = '/overview';
        // Logging the output would be to verbose
        $data = self::api()->get($endpoint, ['no_log_output' => true]);

        if ($key == 'api_version_uniform')
        {
            $api_version_no_dots = str_replace('.', '', $data['management_version']);
            $api_version_uniform =  (int) str_pad($api_version_no_dots, 5, '0', STR_PAD_RIGHT);
            return $api_version_uniform;
        }
        return $data[$key];
    }
    /**
     * @param $vhost
     * @param $queue
     * @throws Exception\HttpException
     */
    function purgeQueue($vhost, $queue)
    {
        $endpoint = '/queues/' . rawurlencode($vhost) . '/' . rawurlencode($queue) . '/contents';
        self::api()->delete($endpoint);
    }

    /**
     * @return UserList
     * @throws Exception\HttpException
     */
    function getUsers():UserList
    {
        $queues = self::api()->get('/users/');
        return new UserList($queues);
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
     * @return bool Returns true when the message was routed.
     */
    function publishMessage($vhost, $exchange_name, MessageModel $model)
    {
        $endpoint = '/exchanges/' . rawurlencode($vhost) . '/' . rawurlencode($exchange_name) . '/publish';
        $response = self::api()->post($endpoint, $model);
        return $response['routed'] ?? false;
    }

    /**
     * Tries to Requeue all deadlettered messages in the given queue. When a message could not be routed (the api
     * returns routed=0) we will not acknowledge. This leaves the message in the current queue.
     *
     * @param $vhost_name
     * @param $from_queue
     * @return array containing the amount of messages delivered and the amount of messages that failed.
     * @throws Exception\HttpException
     */
    function requeueAll($vhost_name, $from_queue):bool
    {
        $connection = new AMQPStreamConnection(getConfig()['api_hostname'], getConfig()['amqp_port'], User::getApiUser(), User::getApiPass(), $vhost_name);
        $channel = $connection->channel();
        $delivered_message_counter = 0;
        $failed_message_counter = 0;
        while($original_message = $channel->basic_get($from_queue)) {
            if (!$original_message instanceof AMQPMessage) {
                throw new LogicException("Expected an instance of AMQPMessage.");
            }

            if (!isset($original_message->get_properties()['application_headers'])) {
                throw new LogicException("Could not requeue message, is this a dead lettered message? It should be.");
            }
            $AMQPTable = $original_message->get_properties()['application_headers'];

            if (!$AMQPTable instanceof AMQPTable)
            {
                throw new LogicException("Could not requeue message, is this a dead lettered message? It should be.");
            }
            $routing_key = $AMQPTable->getNativeData()['x-first-death-queue'];

            // Exchange is empty, which is default, which is direct, which means routing_key = queue_name
            $exchange = '';

            $original_properties = $original_message->get_properties();
            $message = new MessageModel();
            // Delivery mode is only available on newer versions of the API but mandatory.
            $message->setDeliveryMode($original_properties['delivery_mode'] ?? 2);
            $message->setPayloadEncoding($original_message->getContentEncoding());
            $message->setExchange($exchange);
            $message->setVhost($vhost_name);
            $message->setRoutingKey($routing_key);
            $message->setPayload($original_message->getBody());

            $message_delivered = $this->publishMessage($vhost_name, '', $message);

            if($message_delivered)
            {
                $delivered_message_counter ++;
                // Remove the message fom the dead letter queue
                // if no exception was trown from publishMessage
                // and when routed=1 was returned from the API.
                $channel->basic_ack($original_message->delivery_info['delivery_tag']);
            }
            else
            {
                $failed_message_counter ++;
            }
        }

        $channel->close();
        $connection->close();
        return ['delivered' => $message_delivered, 'failed' => $failed_message_counter];
    }
    /**
     * @param $vhost_name
     * @param $from_queue
     * @param $to_queue
     * @param $message_position
     * @param $payload
     * @return bool
     * @throws Exception\HttpException
     */
    function requeueMessage($vhost_name, $from_queue, $to_queue, $message_position, $payload):bool
    {
        $connection = new AMQPStreamConnection(getConfig()['api_hostname'], getConfig()['amqp_port'], User::getApiUser(), User::getApiPass(), $vhost_name);
        $channel = $connection->channel();
        $_SESSION['channel'] = $channel;

        for($i = 1; $i <= $message_position; $i++)
        {
            $original_message = $channel->basic_get($from_queue);

            if(!$original_message instanceof AMQPMessage)
            {
                throw new LogicException("Expected an instance of AMQPMessage.");
            }

            if($i == $message_position) {

                // Exchange is empty, which is default, which is direct, which means routing_key = queue_name
                $exchange = '';
                $routing_key = $to_queue;

                $original_properties = $original_message->get_properties();
                $message = new MessageModel();
                $message->setDeliveryMode($original_properties['delivery_mode'] ?? 2);
                $message->setPayloadEncoding($original_message->getContentEncoding());
                $message->setExchange($exchange);
                $message->setVhost($vhost_name);
                $message->setRoutingKey($routing_key);
                $message->setPayload($payload);

                $message_delivered = $this->publishMessage($vhost_name, '', $message);

                if($message_delivered)
                {
                    // Remove the message fom the dead letter queue
                    // if no exception was trown from publishMessage
                    // and when routed=1 was returned from the API.
                    $channel->basic_ack($original_message->delivery_info['delivery_tag']);
                }
                $channel->close();
                $connection->close();
                return $message_delivered;
            }
        }
        $channel->close();
        $connection->close();
        return false;
    }
    function deleteMessage($vhost_name, $queue_name, $message_position):bool
    {
        $connection = new AMQPStreamConnection(getConfig()['api_hostname'], getConfig()['amqp_port'], User::getApiUser(), User::getApiPass(), $vhost_name);
        $channel = $connection->channel();
        $_SESSION['channel'] = $channel;

        for($i = 1; $i <= $message_position; $i++)
        {
            $message = $channel->basic_get($queue_name);

            if(!$message instanceof AMQPMessage)
            {
                throw new LogicException("Expected an instance of AMQPMessage.");
            }

            if($i == $message_position) {
                $channel->basic_ack($message->delivery_info['delivery_tag']);
                return true;
            }
        }
        return false;
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
     * @param QueueModel $queue
     * @param BindingList $bindings
     * @throws Exception\HttpException
     */
    function addBindings(QueueModel $queue, BindingList $bindings)
    {
        foreach ($bindings as $binding)
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
            $binding->setDestination($queue->getName());
            $this->addBinding($binding);
        }
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

        $this->addBindings($newQueue, $originalQueue->getBindings());
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

