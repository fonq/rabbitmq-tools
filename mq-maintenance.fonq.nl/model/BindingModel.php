<?php
namespace Model;

use Classes\RabbitMq;
use LogicException;

class BindingModel extends BaseModel
{
    private $source;
    private $vhost;
    private $destination;
    private $destination_type;
    private $routing_key;
    private $arguments;
    private $properties_key;

    /**
     * Creates an instance of BindingModel with a minimum amount of data to be pushed to the API.
     * @param string $vhost
     * @param string $source
     * @param string $destination
     * @param string $routing_key
     * @param array $arguments
     * @return BindingModel
     */
    static function create($vhost, $source, $destination, $routing_key, $arguments = []):self
    {
        return new self(
            [
                'vhost' => $vhost,
                'source' => $source,
                'destination_type' => 'queue',
                'destination' => $destination,
                'routing_key' => $routing_key,
                'arguments' => $arguments
            ]
        );
    }
    function __construct($binding = null)
    {
        // source = exchange, can be empty.
        if(isset($binding['source']))
        {
            $this->source = $binding['source'];
        }
        if(isset($binding['vhost']))
        {
            $this->vhost = $binding['vhost'];
        }
        if(isset($binding['destination']))
        {
            $this->destination = $binding['destination'];
        }
        if(isset($binding['destination_type']))
        {
            $this->destination_type = $binding['destination_type'];
        }
        if(isset($binding['routing_key']))
        {
            $this->routing_key = $binding['routing_key'];
        }
        if(isset($binding['arguments']))
        {
            $this->arguments = $binding['arguments'];
        }
        if(isset($binding['properties_key']))
        {
            $this->properties_key = $binding['properties_key'];
        }
    }

    /**
     * Removes itself from rabbitMQ
     * @throws \Classes\Exception\HttpException
     */
    function clear()
    {
        if(empty($this->getSource()))
        {
            throw new LogicException("This binding cannot be deleted, it is united with the queue.");
        }
        RabbitMq::instance()->clearBinding($this);
    }

    /**
     * @param string $vhost this is actually the vhost
     */
    function setVhost($vhost): void
    {
        $this->vhost = $vhost;
    }

    /**
 * @param string $destination this is actually the queue name
 */
    function setDestination($destination)
    {
        $this->destination = $destination;
    }

    /**
     * @param string $destination_type this is actually the queue name
     */
    function setDestinationType($destination_type)
    {
        $this->destination_type = $destination_type;
    }

    /**
     * @param string $source this is actually the exchange
     */
    function setSource($source)
    {
        $this->source = $source;
    }
    /**
     * @param string $routing_key this is actually the routing key
     */
    function setRoutingKey($routing_key)
    {
        $this->routing_key = $routing_key;
    }
    /**
     * @param string $properties_key this is actually the routing key
     */
    function setPropertiesKey($properties_key)
    {
        $this->properties_key = $properties_key;
    }

    function getSource()
    {
        return $this->source;
    }

    function getRoutingKey()
    {
        return $this->routing_key;
    }
    function getDestination()
    {
        return $this->destination;
    }
    function getArguments()
    {
        return $this->arguments;
    }
    function getVhost()
    {
        return $this->vhost;
    }
    function getPropertiesKey()
    {
        return $this->properties_key;
    }
    function toApi(): array
    {
        return [
            'vhost' => $this->vhost,
            'destination' => $this->destination,
            'destination_type' => 'q',
            'source' => $this->source,
            'routing_key' => $this->routing_key,
            'arguments' => new class{}
        ];
    }
}