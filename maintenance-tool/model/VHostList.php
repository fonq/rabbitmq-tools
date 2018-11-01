<?php
namespace Model;

class VHostList extends BaseList
{
    function fromApi($data):BaseModel
    {
        return new VHostModel($data);
    }
    function current():VHostModel
    {
        return parent::current();
    }
}