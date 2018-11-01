<?php
namespace Model;

class BindingList extends BaseList
{
    function fromApi($data):BaseModel
    {
        return new BindingModel($data);
    }
    function current():BindingModel
    {
        return parent::current();
    }
}