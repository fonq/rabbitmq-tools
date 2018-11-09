<?php
namespace Controller;

use Classes\AbstractController;
use Classes\StatusMessage;
use Classes\StatusMessageButton;
use Classes\Template;
use Classes\Exception\HttpException;

class Home extends AbstractController
{
    function getTitle(): string
    {
        return 'RabbitMQ maintenance';
    }

    function getSelectedMenuItem()
    {
        return 'queue';
    }

    function doConfirmDelete()
    {
        $queue = $_GET['queue'];
        $vhost = $_GET['vhost'];

        $delete_question = 'Are you really sure that you want to delete ' . $queue . '?';
        $delete_url = '/queue/delete?vhost=' . rawurlencode($vhost) . '&queue=' . rawurlencode($queue);

        $this->addStatusMessage((new StatusMessage($delete_question))
                                        ->addButton(new StatusMessageButton('Yes i am sure', $delete_url))
                                        ->addButton(new StatusMessageButton('No take me out of here', '/')));
        $this->redirect('/');
    }

    /**
     * @return string
     * @throws HttpException
     */
    function getContent(): string
    {
        $RabbitMq = $this->getRabbitMq();

        $viewData = [
            'queues' => $RabbitMq->getQueues()
        ];
        return Template::parse('home.twig', $viewData);
    }
}

