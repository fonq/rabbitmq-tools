<?php
namespace Controller\Message;

use Classes\AbstractController;
use Classes\Exception\HttpException;
use Classes\MoveHelper;
use Classes\RabbitMq;
use Classes\StatusMessage;
use Classes\Template;

class Move extends AbstractController
{
    function getSelectedMenuItem()
    {
        return 'message';
    }
    function getTitle(): string
    {
        return 'Move message';
    }
    function doMoveMessages()
    {
        try
        {
            $from_vhost = $_GET['from_vhost'];
            $from_queue = $_GET['from_queue'];

            $to_vhost = $_GET['to_vhost'];
            $to_queue = $_GET['to_queue'];

            MoveHelper::moveMessages($from_vhost, $from_queue, $to_vhost, $to_queue);
            $this->addStatusMessage(new StatusMessage("All messages where moved from $from_queue to $to_queue"));
        }
        catch (HttpException $e)
        {
            $this->addStatusMessage(new StatusMessage('Something went wrong while moving the messages, the API gave the following error: '.$e->getMessage()));
        }
        $this->redirect('/');
    }

    /**
     * @return string
     * @throws \Classes\Exception\HttpException
     */
    function getContent(): string
    {
        $from_vhost = $_GET['from_vhost'];
        $from_queue = $_GET['from_queue'];

        $to_vhost = isset($_GET['to_vhost']) ? $_GET['to_vhost'] : null;
        $to_queue = isset($_GET['to_queue']) ? $_GET['to_queue'] : null;

        $queue = RabbitMq::instance()->getQueue($from_vhost, $from_queue);

        $all_vhosts = RabbitMq::instance()->getVHosts();
        $all_vhost_queues = null;

        if($to_vhost)
        {
            $all_vhost_queues = RabbitMq::instance()->getVhost($to_vhost)->getQueues();
        }

        $viewData = [
            'from_queue' => $from_queue,
            'from_vhost' => $from_vhost,
            'to_queue' => $to_queue,
            'to_vhost' => $to_vhost,
            'queue' => $queue,
            'messages' => $queue->getMessageList('ack_requeue_true', 100000),
            'all_vhosts' => $all_vhosts,
            'all_vhost_queues' => $all_vhost_queues
        ];
        return Template::parse('message/move.twig', $viewData);
    }
}

