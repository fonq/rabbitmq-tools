<?php
namespace Model;

class ExchangeList extends BaseList
{
    function fromApi($data):BaseModel
    {
        return new ExchangeModel($data);
    }
    function current():ExchangeModel
    {
        return parent::current();
    }
}