<?php
namespace Controller\Message;

use Classes\AbstractController;
use Classes\DeferredAction;
use Classes\Exception\HttpException;
use Classes\RabbitMq;
use Classes\StatusMessage;
use Classes\StatusMessageButton;
use Classes\Template;

class Overview extends AbstractController
{
    function getSelectedMenuItem()
    {
        return 'deadletter';
    }

    function getTitle(): string
    {
        return 'Queue contents';
    }

    function doPurge()
    {
        try
        {
            RabbitMq::instance()->purgeQueue($_GET['vhost_name'], $_GET['queue_name']);
            $this->addStatusMessage((new StatusMessage('Messages deleted, if the queue is very large this might take a few seconds.')));
        }
        catch (HttpException $e)
        {
            $this->addStatusMessage((new StatusMessage('Could not purge queue.')));
        }
        $noVars = [
            'vhost_name' => $_GET['vhost_name'],
            'queue_name' => $_GET['queue_name'],
            'limit' => $_GET['limit'],
        ];
        $this->redirect('/message/overview?' . http_build_query($noVars));
    }
    function doRequeueAll()
    {
        $vhost_name = $_GET['vhost_name'];
        $queue_name = $_GET['queue_name'];
        $limit = $_GET['limit'];

        try
        {
            RabbitMq::instance()->requeueAll($vhost_name, $queue_name);
            $this->addStatusMessage(new StatusMessage("All messages requeued"));
        }
        catch (HttpException $e)
        {
            $this->addStatusMessage(new StatusMessage("Could not requeue all messages:  " . $e->getMessage()));
        }

        $return_url = '/message/overview?' . http_build_query([
                'vhost_name' => $vhost_name,
                'queue_name' => $queue_name,
                'limit' => $limit,
            ]);

        $this->redirect($return_url);
        exit();
    }
    function doRequeue()
    {
        $vhost_name = $_GET['vhost_name'];
        $queue_name = $_GET['queue_name'];
        $delivery_tag = $_GET['delivery_tag'];
        $to_queue = $_GET['original_queue'];
        $limit = $_GET['limit'];

        try
        {
            RabbitMq::instance()->requeueMessage($vhost_name, $queue_name, $to_queue, $delivery_tag);
            $this->addStatusMessage(new StatusMessage("Message requeued"));
        }
        catch (HttpException $e)
        {
            $this->addStatusMessage(new StatusMessage("Could not requeue message:  " . $e->getMessage()));
        }

        $return_url = '/message/overview?' . http_build_query([
                'vhost_name' => $vhost_name,
                'queue_name' => $queue_name,
                'limit' => $limit,
            ]);

        $this->redirect($return_url);
        exit();
    }

    function doDeleteMessage()
    {
        $vhost_name = $_GET['vhost_name'];
        $queue_name = $_GET['queue_name'];
        $limit = $_GET['limit'];
        $delivery_tag = $_GET['delivery_tag'];

        $item_deleted = RabbitMq::instance()->deleteMessage($vhost_name, $queue_name, $delivery_tag);

        if($item_deleted)
        {
            $this->addStatusMessage(new StatusMessage("Message deleted"));
        }
        else
        {
            $this->addStatusMessage(new StatusMessage("Could not find message, could not delete."));
        }

        $return_url = '/message/overview?' . http_build_query([
                'vhost_name' => $vhost_name,
                'queue_name' => $queue_name,
                'limit' => $limit,
        ]);

        $this->redirect($return_url);
        exit();
    }
    function doConfirmPurge()
    {
        $noVars = [
            'vhost_name' => $_GET['vhost_name'],
            'queue_name' => $_GET['queue_name'],
            'limit' => $_GET['limit'],
        ];
        $yesVars = array_merge($noVars, ['_do' => 'Purge']);

        $text = "Are you 100% sure that you want to remove all messages from the <b>" . $_GET['queue_name'] . "</b> queue?";
        $yesButton = new StatusMessageButton('Yes', '/message/overview?' . http_build_query($yesVars));
        $noButton = new StatusMessageButton('No', '/message/overview?' . http_build_query($noVars));
        $this->addStatusMessage((new StatusMessage($text))->addButton($yesButton)->addButton($noButton));
    }
    /**
     * @return string
     * @throws \Classes\Exception\HttpException
     */
    function getContent(): string
    {
        DeferredAction::register('after_add_test_messages', $_SERVER['REQUEST_URI']);
        $vhost_name = isset($_GET['vhost_name']) ? $_GET['vhost_name'] : null;
        $queue_name = isset($_GET['queue_name']) ? $_GET['queue_name'] : null;
        $limit = isset($_GET['limit']) ? $_GET['limit'] : 50;

        $vhosts = RabbitMq::instance()->getVHosts();
        $queues = RabbitMq::instance()->getQueues();

        $queue = $messages = null;
        if($vhost_name && $queue_name)
        {
            $queue = RabbitMq::instance()->getQueue($vhost_name, $queue_name);
            $messages = $queue->getMessageList('ack_requeue_true', $limit);
        }


        $viewData = [
            'vhosts' => $vhosts,
            'queues' => $queues,
            'vhost_name' => $vhost_name,
            'queue_name' => $queue_name,
            'limit' => $limit,
            'queue' => $queue,
            'messages' => $messages
        ];
        return Template::parse('message/overview.twig', $viewData);
    }
}
