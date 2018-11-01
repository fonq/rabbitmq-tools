<?php
namespace Controller\Exchange;

use Classes\AbstractController;
use Classes\Exception\HttpException;
use Classes\RabbitMq;
use Classes\StatusMessage;
use Classes\StatusMessageButton;
use Classes\Template;
use Model\ExchangeModel;

class Overview extends AbstractController
{
    function getSelectedMenuItem()
    {
        return 'exchange';
    }
    function doSureDelete()
    {
        $exchange_name = $_GET['exchange_name'];
        $vhost = $_GET['vhost'];

        $question = new StatusMessage("Are you sure that you want to delete $exchange_name?");
        $yes_button = new StatusMessageButton("Yes, please do so", '/exchange/delete?vhost=' . rawurlencode($vhost) . '&exchange_name=' . rawurlencode($exchange_name));
        $no_button = new StatusMessageButton("No, take me out of here", '/exchange/overview');

        $this->addStatusMessage(
            $question
            ->addButton($yes_button)
            ->addButton($no_button)
        );
    }

    function doAddExchange()
    {
        $exchange = new ExchangeModel();
        $exchange->setVhost($_POST['vhost']);
        $exchange->setName($_POST['name']);
        $exchange->setInternal($_POST['internal'] == 'true');
        $exchange->setType($_POST['type']);
        $exchange->setDurable($_POST['durable'] == 'true');
        $exchange->setAutoDelete($_POST['auto_delete'] == 'true');

        try
        {
            RabbitMq::instance()->addExchange($exchange);
            $this->addStatusMessage(new StatusMessage("Exchange added."));
        }
        catch (HttpException $e)
        {
            $this->addStatusMessage(new StatusMessage("Could not add exchange, api responded with: " . $e->getMessage()));
        }


        $this->redirect('/exchange/overview');
        exit();
    }
    function getTitle(): string
    {
        return 'Exchanges';
    }

    /**
     * @return string
     * @throws HttpException
     */
    function getContent(): string
    {
        $aViewData = [
            'all_vhosts' =>  RabbitMq::instance()->getVhosts(),
            'exchanges' => RabbitMq::instance()->getExchanges()
        ];
        return Template::parse('exchange/overview.twig', $aViewData);
    }

}