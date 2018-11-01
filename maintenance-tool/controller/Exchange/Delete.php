<?php
namespace Controller\Exchange;

use Classes\AbstractController;
use Classes\Api;
use Classes\StatusMessage;

class Delete extends AbstractController
{
    function getTitle(): string
    {
        return 'Delete exchange';
    }
    function getSelectedMenuItem()
    {
        return 'exchange';
    }
    function getContent(): string
    {
        $vhost = $_GET['vhost'];
        $exchange_name = $_GET['exchange_name'];

        try
        {
            $exchange = $this->getRabbitMq()->getExchange($vhost, $exchange_name);
            $exchange->delete();
            $this->addStatusMessage(new StatusMessage("Exchange deleted."));
        }
        catch (\Exception $e)
        {
            if($e->getCode() == Api::HTTP_ACCESS_REFUSED)
            {
                $this->addStatusMessage(new StatusMessage("Could not delete exchange, access refused."));
            }
            else
            {
                $this->addStatusMessage(new StatusMessage("Could not delete exchange, api responded with: ".$e->getMessage()));
            }
        }

        $this->redirect('/exchange/overview');
        exit();
    }
}

