#!/usr/bin/php
<?php
require_once '../vendor/autoload.php';
require_once '../config.php';

use Classes\RabbitMq;
use Classes\User;

$queue = 'failed.dead';
$vhost = '/';

if(!isset($argv[1]) || !isset($argv[2]))
{
    echo "Usage ./requeueall.php <user> <pass>" . PHP_EOL;
    exit();
}

User::setApiUser($argv[1]);
User::setApiPass($argv[2]);

if(!User::isLoggedIn())
{
    echo "Could not sing you in, are the username and password correct?";
}

try
{
    RabbitMq::instance()->requeueAll($vhost, $queue);
    echo "Requeueing done " . PHP_EOL;
}
catch (Exception $e)
{
    echo "Something went wrong ". $e->getMessage() . PHP_EOL;
}