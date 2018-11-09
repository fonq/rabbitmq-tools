<?php
namespace Model;

abstract class BaseModel
{
    /**
     * Convert a model object to an array suitable for posting / putting to the API.
     */
    abstract function toApi():array;
    abstract function __construct($queue);
}

