<?php
namespace Controller\Message;

use Classes\AbstractController;
use Classes\RabbitMq;
use Classes\Template;

class Overview extends AbstractController
{
    function getSelectedMenuItem()
    {
        return 'deadletter';
    }
    function getTitle(): string
    {
        return 'Dead lettered messages';
    }
    /**
     * @return string
     * @throws \Classes\Exception\HttpException
     */
    function getContent(): string
    {
        $vhost = $_GET['vhost_name'];
        $queue = $_GET['queue_name'];
        $queue = RabbitMq::instance()->getQueue($vhost, $queue);

        $viewData = [
            'queue' => $queue,
            'messages' => $queue->getMessageList('ack_requeue_true')
        ];
        return Template::parse('message/overview.twig', $viewData);
    }
}

