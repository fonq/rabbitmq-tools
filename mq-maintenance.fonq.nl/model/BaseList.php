<?php
namespace Model;

use ArrayIterator;

abstract class BaseList extends ArrayIterator
{
    abstract function fromApi($apiResult):BaseModel;

    function __construct(array $apiresultset = array(), int $flags = 0)
    {
        $objects = [];
        foreach ($apiresultset as $apiresult) {
            $objects[] = $this->fromApi($apiresult);
        }
        parent::__construct($objects, $flags);
    }
}