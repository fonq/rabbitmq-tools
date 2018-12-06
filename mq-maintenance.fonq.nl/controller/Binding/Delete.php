<?php
namespace Controller\Binding;

use Classes\AbstractController;
use Classes\StatusMessage;

class Delete extends AbstractController
{
    function getTitle(): string
    {
        return 'Delete binding';
    }
    function getSelectedMenuItem()
    {
        return 'queue';
    }
    function getContent(): string
    {
        $vhost = $_GET['vhost'];
        $source = $_GET['source'];
        $queue = $_GET['queue'];
        $properties_key = $_GET['properties_key'];

        try
        {
            $binding = $this->getRabbitMq()->getBinding($vhost, $source, $queue, $properties_key);
            $binding->clear();
            $this->addStatusMessage(new StatusMessage("Binding $properties_key deleted.", true));
        }
        catch (\Exception $e)
        {
            $this->addStatusMessage(new StatusMessage("Could not delete binding, api said: " . $e->getMessage() ));
        }

        $done_query = http_build_query([
            'vhost' => $_GET['vhost'],
            'queue' => $_GET['queue']
        ]);
        $this->redirect('/queue/change?' . $done_query);
        exit();
    }
}

