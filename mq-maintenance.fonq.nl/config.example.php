<?php
function getConfig()
{
    return [
        'env' => 'dev', // Shows a warning on top of the screen when set to live. [live|test|dev]
        'api_hostname' => 'localhost',
        'api_port' => '15672',
        'amqp_port' => '5672',
        'white_listed_ip_regexes' => [
            '/^10.11.[0-9]{1,3}.[0-9]{1,3}$/',
            '/^172.16.[0-9]{1,3}.[0-9]{1,3}$/'
        ]
    ];
}
