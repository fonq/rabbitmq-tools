<?php
namespace Controller\Queue;

use Classes\AbstractController;
use Classes\Exception\HttpException;
use Classes\MoveHelper;
use Classes\RabbitMq;
use Classes\StatusMessage;
use Classes\StatusMessageButton;
use Classes\Template;

class Change extends AbstractController
{
    private $queue_name;
    private $vhost_name;

    function getSelectedMenuItem()
    {
        return 'queue';
    }

    function __construct()
    {
        $this->queue_name = $_GET['queue'];
        $this->vhost_name = $_GET['vhost'];
        parent::__construct();
    }

    /**
     * @throws HttpException
     */
    private function applyChanges()
    {
        $currentQueue = RabbitMq::instance()->getQueue($this->vhost_name, $this->queue_name);

        // 1. Create a new queue with the same properties as the queue that is about to get changed.
        $tmpQueueName = $currentQueue->getName().'.'.time();
        RabbitMq::instance()->copyQueue($currentQueue, $tmpQueueName);

        // 2. Remove the bindings of the original queue to stop messages from landing there.
        try
        {
            $currentQueue->clearBindings();
        }
        catch (HttpException $exception)
        {
            $this->addStatusMessage(new StatusMessage('Could not complete the task, deletion of bindings on original queue failed. Got http statuscode '.$exception->getCode()));
        }

        // 3. Move the messages to the temporary queue.
        MoveHelper::moveMessages($currentQueue->getVHost(), $this->queue_name, $this->vhost_name, $tmpQueueName);

        // 4. Re create the original queue.
        $arguments = [];
        foreach ($_POST as $key => $value)
        {
            if(preg_match('/arguments_([0-9]+)_mfkey/', $key, $matches))
            {
                $id = $matches[1];
                $arguments[$value] = (int)$_POST["arguments_{$id}_mfvalue"];
            }
        }

        $currentQueue->setDurable($_POST['durable'] == 'true');
        $currentQueue->setAutoDelete($_POST['durable'] == 'true');
        $currentQueue->setArguments($arguments);

        // 5. Delete the queue that we want to change.
        RabbitMq::instance()->deleteQueue($this->vhost_name, $currentQueue->getName());

        // 6. Re-create the queue with the newly added or removed behaviors.
        RabbitMq::instance()->createQueue($currentQueue);

        // 7. Move the messages back to the original queue.
        MoveHelper::moveMessages($this->vhost_name, $tmpQueueName, $this->vhost_name, $currentQueue->getName());

        // 8. Delete the placeholder queue
        RabbitMq::instance()->deleteQueue($this->vhost_name, $tmpQueueName);

    }
    function doApplyChanges()
    {
        try{
            $this->applyChanges();
            $this->addStatusMessage(new StatusMessage("The queue {$this->queue_name} has been successfully changed."));
        }
        catch (HttpException $e)
        {
            $this->addStatusMessage(new StatusMessage("Could not change the queue, got the following error: ".$e->getMessage()));
        }
        $this->redirect('/');
    }
    function getTitle(): string
    {
        return 'Change ';
    }
    function doSureDelete()
    {
        $properties_key = $_GET['properties_key'];
        $ok_query = http_build_query([
            'vhost' => $_GET['vhost'],
            'source' => $_GET['source'],
            'queue' => $_GET['queue'],
            'properties_key' => $_GET['properties_key']
        ]);

        $cancel_query = http_build_query([
            'vhost' => $_GET['vhost'],
            'queue' => $_GET['queue']
        ]);

        $delete_question = 'Are you really sure that you want to delete this binding ' . $properties_key . '?';
        $this->addStatusMessage((new StatusMessage($delete_question))
            ->addButton(new StatusMessageButton('Yes i am sure', '/binding/delete?' . $ok_query))
            ->addButton(new StatusMessageButton('No take me out of here', '/queue/change?' . $cancel_query)));
    }
    function getContent(): string
    {
        $RabbitMq = RabbitMq::instance();
        try
        {
            $viewData = [
                'vhosts' => $RabbitMq->getVHosts(),
                'queue' => $RabbitMq->getQueue($this->vhost_name, $this->queue_name)
            ];
        }
        catch (\Exception $e)
        {
            $this->addStatusMessage(new StatusMessage("Could not load any queue's or vhost data, is the config correct?"));
            $viewData = [];
        }
        return Template::parse('queue/change.twig', $viewData);
    }
}

