<?php
namespace Controller\Exchange;

use Classes\AbstractController;
use Classes\Exception\HttpException;
use Classes\RabbitMq;
use Classes\StatusMessage;
use Classes\StatusMessageButton;
use Classes\Template;
use Model\BindingModel;

class Edit extends AbstractController
{
    function doDeleteBinding()
    {
        $vhost = $_GET['vhost'];
        $exchange = $_GET['exchange'];
        $queue = $_GET['queue'];
        $properties_key = $_GET['properties_key'];

        try{
            $binding = RabbitMq::instance()->getBinding($vhost, $exchange, $queue, $properties_key);
            RabbitMq::instance()->clearBinding($binding);

            $this->redirect(        $cancelUrl = '/exchange/edit?' . http_build_query([
                    'vhost' => $vhost,
                    'exchange' => $exchange,
            ]));
        }
        catch (HttpException $exception)
        {
            $this->addStatusMessage(new StatusMessage("Failed to delete the routing key, we got this error: ".$exception->getMessage()));
        }
    }
    function doSureDelete()
    {
        $vhost = $_GET['vhost'];
        $exchange = $_GET['exchange'];
        $queue = $_GET['queue'];
        $properties_key = $_GET['properties_key'];

        $deleteUrl = '/exchange/edit?' . http_build_query([
            'vhost' => $vhost,
            'exchange' => $exchange,
            'queue' => $queue,
            'properties_key' => $properties_key,
            '_do' => 'DeleteBinding'
        ]);
        $cancelUrl = '/exchange/edit?' . http_build_query([
            'vhost' => $vhost,
            'exchange' => $exchange,
        ]);

        $this->addStatusMessage(
            (new StatusMessage("Are you sure that you want to delete this binding?"))
            ->addButton(new StatusMessageButton("Yes delete binding", $deleteUrl))
            ->addButton(new StatusMessageButton("Cancel", $cancelUrl))
        );

    }
    function doAddBinding()
    {
        $vhost = $_GET['vhost'];
        $exhange = $_GET['exchange'];
        $routing_key = $_GET['routing_key'];
        $queue = $_GET['queue'];

        try
        {
            $binding = BindingModel::create($vhost, $exhange, $queue, $routing_key, []);
            RabbitMq::instance()->addBinding($binding);
        }
        catch (\Exception $e)
        {
            $this->addStatusMessage(new StatusMessage("Failed to add the binding, we got this error: ".$e->getMessage()));
        }

    }
    /**
     * @return string
     * @throws \Classes\Exception\HttpException
     */
    function getContent(): string
    {
        $exchange = $_GET['exchange'];
        $vhost = $_GET['vhost'];

        $exchange = RabbitMq::instance()->getExchange($vhost, $exchange);
        $vhost_queues = RabbitMq::instance()->getVhostQueues($exchange->getVhost());

        $viewData = [
            'exchange' => $exchange,
            'vhost_queues' => $vhost_queues
        ];

        return Template::parse('exchange/edit.twig', $viewData);
    }
    function getTitle(): string
    {
        return 'Exchange: '.$_GET['exchange'];
    }
    function getSelectedMenuItem()
    {
        return 'exchange';
    }
}