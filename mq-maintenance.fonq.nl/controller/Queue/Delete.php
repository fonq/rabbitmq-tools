<?php
namespace Controller\Queue;

use Classes\AbstractController;
use Classes\StatusMessage;

class Delete extends AbstractController
{
    private $queue_name;
    private $vhost_name;

    function __construct()
    {
        $this->queue_name = $_GET['queue'];
        $this->vhost_name = $_GET['vhost'];
        parent::__construct();
    }

    function getSelectedMenuItem()
    {
        return 'queue';
    }

    function getTitle(): string
    {
        return '';
    }

    function getContent(): string
    {
        try
        {
            $this->getRabbitMq()->deleteQueue($this->vhost_name, $this->queue_name);
            $status_message = 'Queue deleted successfully';
        }
        catch (\Exception $e)
        {
            $status_message= 'Queue deletion failed for the following reason: '.$e->getMessage();
        }

        $this->addStatusMessage(new StatusMessage($status_message));
        $this->redirect('/');
        exit();
    }
}

