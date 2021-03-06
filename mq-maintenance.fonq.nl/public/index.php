<?php
use Classes\AbstractController;
use Classes\Template;
use Classes\User;
use Classes\Logger;

require_once '../vendor/php-amqplib/php-amqplib/PhpAmqpLib/Helper/Protocol/Protocol091.php';
require_once '../vendor/php-amqplib/php-amqplib/PhpAmqpLib/Channel/AbstractChannel.php';
require_once '../vendor/php-amqplib/php-amqplib/PhpAmqpLib/Channel/AMQPChannel.php';
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../vendor/autoload.php';
try
{

    if(file_exists('../config.php'))
    {
        require_once '../config.php';
    }
    else
    {
       throw new Exception("Config file missing, did you create one?");
    }

    $has_access = false;

    $white_listed_ip_regexes = getConfig()['white_listed_ip_regexes'] ?? [];

    foreach ($white_listed_ip_regexes as $regex)
    {
        if(preg_match($regex, $_SERVER['REMOTE_ADDR']))
        {
            $has_access = true;
        }
    }
    if(!$has_access)
    {
        throw new RuntimeException("Your ip address " . $_SERVER['REMOTE_ADDR'] . " is not whitelisted for this tool. Please open a vpn connection or contact the administrator.");
    }

    // Request URI without GET vars.
    $base_request_uri = strpos($_SERVER['REQUEST_URI'],'?') ? explode('?', $_SERVER['REQUEST_URI'])[0] : $_SERVER['REQUEST_URI'];


    if($base_request_uri == '/logout')
    {
        User::logout();
    }

    if(!User::isLoggedIn())
    {
        // If user is not logged in, show login screen.
        // If user is not logged in but login info is send, try to log the user in and proceed with request.
        User::login();
    }

    $url_parts = explode('/', $base_request_uri);
    unset($url_parts[0]);

    if($base_request_uri == '/')
    {
        $controllerClassName = '\\Controller\\Home';
    }
    else
    {
        $controllerClassName = '\\Controller\\' . ucfirst($url_parts[1]) . '\\' . ucfirst($url_parts[2]);
    }

    if(class_exists($controllerClassName))
    {
        $controller = new $controllerClassName();
    }
    else if(!class_exists($controllerClassName))
    {
        throw new Exception("Controller not found");
    }
    if(!$controller instanceof AbstractController)
    {
        throw new Exception("Controller class must implement AbstractController.");
    }

    if(isset($_REQUEST['_do']) && !empty($_REQUEST['_do']))
    {
        /**
         * Process input.
         */
        $method = 'do'.$_REQUEST['_do'];
        $controller->$method();
    }

    $parseData = [
        'env' => getConfig()['env'],
        'title' => $controller->getTitle(),
        'api_version' => $controller instanceof \Controller\ExceptionPage ? null : $controller->getApiVersion(),
        'content' => $controller->getContent(),
        'selected_menu_item' => $controller->getSelectedMenuItem(),
        'status_message' => $controller->getStatusMessage()
    ];
    echo Template::parse('layout.twig', $parseData);
}
catch (Exception $exception)
{
    Logger::log('Exception: ' . $exception->getMessage(), Logger::WARNING);
    $controller  = new \Controller\ExceptionPage();
    $controller->setException($exception);
    $parseData = [
        'title' => $controller->getTitle(),
        'content' => $controller->getContent(),
        'selected_menu_item' => $controller->getSelectedMenuItem(),
        'status_message' => $controller->getStatusMessage()
    ];
    echo Template::parse('layout-empty.twig', $parseData);
}



