<?php
namespace Controller\Topology;

use Classes\AbstractController;
use Classes\RabbitMq;
use Classes\Template;
use Classes\Exception\HttpException;
use Model\QueueList;
use Model\QueueModel;

class Overview extends AbstractController
{
    function getTitle(): string
    {
        return 'Message topology';
    }
    function getSelectedMenuItem()
    {
        return 'topology';
    }

    /**
     * @return string
     * @throws HttpException
     */
    function getContent(): string
    {
        $vhosts = RabbitMq::instance()->getVHosts();
        $queues_by_binding = QueueList::getAllQueuesByBinding();

        $aRoutingKeysDone = [];
        $table = [];
        $combohelper = new ComboHelper();
        foreach($vhosts as $vhost)
        {
            $combohelper->setVhostName($vhost->getName());

            $exchanges = RabbitMq::instance()->getExchanges($vhost->getName());
            foreach($exchanges as $exchange)
            {
                if(empty($exchange->getName()))
                {
                    continue;
                }
                $combohelper->setExchangeName($exchange->getName());
                $bindings = RabbitMq::instance()->getBindings($exchange);

                foreach ($bindings as $binding)
                {
                    if(in_array($binding->getRoutingKey(), $aRoutingKeysDone))
                    {
                        continue;
                    }
                    $aRoutingKeysDone[] = $binding->getRoutingKey();
                    $combohelper->setBindingName($binding->getRoutingKey());
                    if(!isset($queues_by_binding[$vhost->getName()]))
                    {
                        continue;
                    }
                    if(!isset($queues_by_binding[$vhost->getName()][$binding->getRoutingKey()]))
                    {
                        continue;
                    }

                    $queues = $queues_by_binding[$vhost->getName()][$binding->getRoutingKey()];

                    foreach($queues as $queue)
                    {
                        if(!$queue instanceof QueueModel)
                        {
                            throw new \LogicException("Expected an instance of queue");
                        }

                        $combohelper->setQueueName($queue->getName());
                        $table[] = $combohelper;
                        $combohelper = new ComboHelper();
                    }
                }
            }
        }



        $viewData = [
            'table_data' => $table
        ];
        return Template::parse('topology/overview.twig', $viewData);
    }


}