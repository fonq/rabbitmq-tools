<?php
namespace Controller\Message;

use Classes\AbstractController;
use Classes\DeferredAction;
use Classes\RabbitMq;
use Classes\StatusMessage;
use Classes\StatusMessageButton;
use Classes\Template;
use Model\MessageModel;

class Generate extends AbstractController
{
    /**
     * {"vhost":"/",
     * "name":"amq.default",
     * "properties":{"delivery_mode":1,"headers":{"dasfdfas":"dfasdasf"}},
     * "routing_key":"adyen.creditsalesinvoice ",
     * delivery_mode":"1",
     * "payload":"adsdasf",
     * "headers":{"dasfdfas":"dfasdasf"},
     * "props":{"dasdasf":"asdf"},"payload_encoding":"string"}
     */

    /**
     * @throws \Classes\Exception\HttpException
     */
    function doPublishMessages()
    {
        $properties = $headers = [];
        foreach($_POST as $sKey => $sValue)
        {
            if(preg_match('/headers_([0-9])+_mfkey/', $sKey, $matches) && !empty($sValue))
            {
                $key = $_POST['headers_' . $matches[1] . '_mfkey'];
                $value = $_POST['headers_' . $matches[1] . '_mfvalue'];
                $headers[$key] = $value;
            }
            else if(preg_match('/props_([0-9])+_mfkey/', $sKey, $matches) && !empty($sValue))
            {
                $key = $_POST['props_' . $matches[1] . '_mfkey'];
                $value = $_POST['props_' . $matches[1] . '_mfvalue'];
                $properties[$key] = $value;
            }
        }

        $message_count = $_POST['message_count'];
        $delivery_mode = $_POST['delivery_mode'];
        $payload = $_POST['payload'];
        $routing_key = $_POST['routing_key'];
        $vhost = $_POST['vhost_name'];
        $exchange = $_POST['exchange'];

        $message  = new MessageModel();
        $message->setHeaders($headers);
        $message->setProperties($properties);
        $message->setDeliveryMode($delivery_mode);

        $message->setExchange($exchange);
        $message->setVhost($vhost);
        $message->setRoutingKey($routing_key);
        $message->setPayload($payload);

        $rabbitmq = RabbitMq::instance();
        for($i = 0; $i < $message_count; $i++)
        {
            $rabbitmq->publishMessage($vhost, $exchange, $message);
        }

        $back_url = DeferredAction::get('after_add_test_messages');
        if($back_url)
        {
            $this->redirect($back_url);
        }

        $this->addStatusMessage(
            (new StatusMessage("$message_count messages published to $exchange with routing key $routing_key."))
                ->addButton(new StatusMessageButton('Close and reload', '/'))
        );
        $this->redirect('/');
        exit();
    }

    function getSelectedMenuItem()
    {
        return 'message';
    }
    function getTitle(): string
    {
        return 'Generate messages';
    }

    /**
     * @return string
     * @throws \Classes\Exception\HttpException
     */
    function getContent(): string
    {
        $vhost_name = isset($_REQUEST['vhost_name']) ? $_REQUEST['vhost_name'] : null;
        $queue_name = isset($_REQUEST['queue_name']) ? $_REQUEST['queue_name'] : null;
        $exchange = isset($_REQUEST['exchange']) ? $_REQUEST['exchange'] : null;
        $routing_key = isset($_REQUEST['routing_key']) ? $_REQUEST['routing_key'] : null;

        if($queue_name && !$routing_key)
        {
            $routing_key = $queue_name;
        }


        $rabbitApi = RabbitMq::instance();

        $viewData = [
            'vhost_name' => $vhost_name,
            'routing_key' => $routing_key,
            'current_exhange_name' => $exchange,
            'exchanges' => $rabbitApi->getExchanges($vhost_name),
            'vhosts' => $rabbitApi->getVhosts(),
        ];
        return Template::parse('message/generate.twig', $viewData);
    }
}

