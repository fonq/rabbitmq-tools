<?php
require '../vendor/autoload.php';
require '../config.php';

use Classes\Logger;
use Classes\RabbitMq;
use Classes\Exception\HttpException;

$settings = [];
print_r($argv);
foreach ($argv ?? [] as $value)
{
    list($setting_key, $setting_value) = explode('=', $value);
    $settings[trim($setting_key)] = trim($setting_value);
}

if(!isset($settings['vhost']) || !isset($settings['queue']))
{
    echo "Usage: " . PHP_EOL;
    echo "requeue-all.php vhost=<vhostname> queue=<queuename>" . PHP_EOL;
    echo "Optional arguments are: " . PHP_EOL;
    echo "verbose - prints all api calls and responss to the screen. " . PHP_EOL;
    exit();
}
$verbose = in_array('verbose', $argv ?? []);

try
{
    RabbitMq::instance()->requeueAll($settings['vhost'], $settings['queue']);
    Logger::log("All messages requeued:  " . $e->getMessage(), Logger::VERBOSE, 'cron');
}
catch (HttpException $e)
{
    Logger::log("Could not requeue all messages:  " . $e->getMessage(), Logger::WARNING, 'cron');
}
