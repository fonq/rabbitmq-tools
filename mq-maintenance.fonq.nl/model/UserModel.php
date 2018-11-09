<?php
namespace Model;

use LogicException;

class UserModel extends BaseModel
{
    private $name;
    private $password_hash;
    private $hashing_algorithm;

    function toApi(): array
    {
        throw new LogicException(__METHOD__ . ' not implemented yet');
    }

    function __construct($user)
    {
        if(isset($user['name']))
        {
            $this->name = $user['name'];
        }
        if(isset($user['password_hash']))
        {
            $this->password_hash = $user['password_hash'];
        }
        if(isset($user['hashing_algorithm']))
        {
            $this->hashing_algorithm = $user['hashing_algorithm'];
        }
    }
    function getName()
    {
        return $this->name;
    }
}