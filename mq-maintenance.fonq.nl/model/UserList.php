<?php
namespace Model;

class UserList extends BaseList
{
    function fromApi($data):BaseModel
    {
        return new UserModel($data);
    }
    function current():UserModel
    {
        return parent::current();
    }
}