<?php
namespace Model;

class MessageList extends BaseList
{
    function fromApi($data):BaseModel
    {

        return new MessageModel($data);
    }
    function current():MessageModel
    {
        return parent::current();
    }
}