<?php
namespace Controller\Topology;

class ComboHelper
{
    public static $last_exchange_name;
    public static $last_vhost_name;
    public $vhost_name;
    public $exchange_name;
    public $binding_name;
    public $queue_name;

    /**
     * @return mixed
     */
    public function getVhostName()
    {
        return $this->vhost_name;
    }

    /**
     * @param mixed $vhost_name
     */
    public function setVhostName($vhost_name): void
    {
        $this->vhost_name = $vhost_name;
    }

    /**
     * @return mixed
     */
    public function getExchangeName()
    {
        return $this->exchange_name;
    }

    /**
     * @param mixed $exchange_name
     */
    public function setExchangeName($exchange_name): void
    {
        $this->exchange_name = $exchange_name;
    }

    /**
     * @return mixed
     */
    public function getBindingName()
    {
        return $this->binding_name;
    }

    /**
     * @param mixed $binding_name
     */
    public function setBindingName($binding_name): void
    {
        $this->binding_name = $binding_name;
    }

    /**
     * @return mixed
     */
    public function getQueueName()
    {
        return $this->queue_name;
    }

    /**
     * @param mixed $queue_name
     */
    public function setQueueName($queue_name): void
    {
        $this->queue_name = $queue_name;
    }
}
