<?php
namespace Model;

use Classes\Utils;

class MessageModel extends BaseModel
{
    private $vhost;
    private $properties;
    private $delivery_mode;
    private $exchange;
    private $routing_key;
    private $payload;
    private $headers;
    private $payload_encoding = [];

    function __construct($message = null)
    {
        if (isset($message['properties'])) {
            $this->properties = $message['properties'];
        }
        if (isset($message['routing_key'])) {
            $this->routing_key = $message['routing_key'];
        }
        if (isset($message['payload'])) {
            $this->payload = $message['payload'];
        }
        if (isset($message['payload_encoding'])) {
            $this->payload_encoding = $message['payload_encoding'];
        }
    }

    function toApi(): array
    {
        return [
            "vhost" => $this->vhost,
            "name" => $this->exchange,
            "properties" => ["delivery_mode" => (int) $this->delivery_mode, "headers" => []],
            "routing_key" => $this->routing_key,
            "delivery_mode" => (int) $this->delivery_mode,
            "payload" => $this->payload,
            "headers" => $this->headers,
            "props" => [],
            "payload_encoding" => $this->payload_encoding ? $this->payload_encoding : 'string'
        ];
    }
    function getRoutingKey()
    {
        return $this->routing_key;
    }

    function getProperties()
    {
        return $this->properties;
    }
    function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }
    function getHeaders()
    {
        return $this->headers;
    }
    function setDeliveryMode($delivery_mode)
    {
        $this->delivery_mode = $delivery_mode;
    }
    function getDeliveryMode()
    {
        return $this->delivery_mode;
    }
    function setExchange($exchange)
    {
        $this->exchange = $exchange;
    }
    function getExchange()
    {
        return $this->exchange;
    }
    function setVhost($vhost)
    {
        $this->vhost = $vhost;
    }
    function getVhost()
    {
        return $this->vhost;
    }
    function setRoutingKey($routing_key)
    {
        $this->routing_key = $routing_key;
    }
    function setPayload($payload)
    {
        $this->payload = $payload;
    }
    function getPayload()
    {
        return $this->payload;
    }
    function getPrettyfiedPayload()
    {
        if (is_object(json_decode($this->payload)))
        {
            return Utils::prettifyJson($this->payload);
        }
        return $this->payload;

    }

    function setPayloadEncoding($payload_encoding)
    {
        $this->payload_encoding = $payload_encoding;
    }
    function getPayloadEncoding()
    {
        return $this->payload_encoding;
    }
    function setProperties($properties)
    {
        $this->properties = $properties;
    }

}