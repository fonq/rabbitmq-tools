<?php
namespace Model;

use Classes\RabbitMq;
use Classes\Exception\HttpException;

class ExchangeModel extends BaseModel
{
    private $arguments;
    private $durable;
    private $internal;
    private $name;
    private $auto_delete;
    private $type;
    private $user_who_performed_action;
    private $vhost;

    function __construct($exchange = null)
    {
        if(isset($exchange['arguments']))
        {
            $this->arguments = $exchange['arguments'];
        }
        if(isset($exchange['durable']))
        {
            $this->durable = $exchange['durable'];
        }
        if(isset($exchange['internal']))
        {
            $this->internal = $exchange['internal'];
        }
        if(isset($exchange['name']))
        {
            $this->name = $exchange['name'];
        }
        if(isset($exchange['type']))
        {
            $this->type = $exchange['type'];
        }
        if(isset($exchange['user_who_performed_action']))
        {
            $this->user_who_performed_action = $exchange['user_who_performed_action'];
        }
        if(isset($exchange['user_who_performed_action']))
        {
            $this->user_who_performed_action = $exchange['user_who_performed_action'];
        }
        if(isset($exchange['vhost']))
        {
            $this->vhost = $exchange['vhost'];
        }
    }

    public function getArguments()
    {
        return $this->arguments;
    }
    public function setArguments($arguments): void
    {
        $this->arguments = $arguments;
    }
    public function getDurable()
    {
        return $this->durable;
    }
    public function setDurable($durable): void
    {
        $this->durable = $durable;
    }
    public function getInternal()
    {
        return $this->internal;
    }
    public function setInternal($internal): void
    {
        $this->internal = $internal;
    }
    public function getAutoDelete()
    {
        return $this->auto_delete;
    }
    public function setAutoDelete($auto_delete): void
    {
        $this->auto_delete = $auto_delete;
    }
    function setVhost($vhost)
    {
        $this->vhost = $vhost;
    }
    function getVhost()
    {
        return $this->vhost;
    }
    function setType($type)
    {
        $this->type = $type;
    }
    function getType()
    {
        return $this->type;
    }
    function getName()
    {
        return $this->name;
    }
    function setName($name)
    {
        $this->name = $name;
    }

    function isSystemExchange()
    {
        return in_array($this->getName(), [
            'amq.direct',
            'amq.fanout',
            'amq.headers',
            'amq.match',
            'amq.rabbitmq.trace',
            'amq.topic'
        ]);
    }

    /**
     * @throws HttpException
     */
    function delete()
    {
        RabbitMq::instance()->deleteExchange($this->vhost, $this->name);
    }

    /**
     * @return BindingList
     * @throws HttpException
     */
    function getBindings()
    {
        return RabbitMq::instance()->getBindings($this);
    }
    function toApi(): array
    {
        if($this->arguments == null)
        {
            $this->arguments = [];
        }
        return [
            'type' => $this->type,
            'auto_delete' => (bool) $this->auto_delete,
            'durable' => (bool) $this->durable,
            'internal' => (bool) $this->internal,
            'arguments' => $this->arguments,
        ];
    }

}