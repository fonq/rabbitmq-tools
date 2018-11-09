<?php
namespace Classes;

use LogicException;

abstract class AbstractController
{
    function __construct(){}
    abstract function getSelectedMenuItem();
    abstract function getTitle():string;
    abstract function getContent():string;

    function addStatusMessage(StatusMessage $statusMessage)
    {
        $_SESSION['status_message'] = serialize($statusMessage);
    }

    function getStatusMessage()
    {
        if(isset($_SESSION['status_message']))
        {
            // Required for de-serialisation
            new StatusMessage('Dummy message to autolad this class');
            new StatusMessageButton('Dummy message to autolad this class', '/dummy');

            $status_message_serialized = $_SESSION['status_message'];
            $status_message = unserialize($status_message_serialized);

            if($status_message instanceof StatusMessage)
            {
                $html = $status_message->getHtml();
                $_SESSION['status_message'] = null;
                return $html;
            }
            else
            {

                throw new LogicException("Expected an instance of StatusMessage ".print_r($status_message, true));
            }
        }
        return null;
    }

    function redirect($url)
    {
        header("Location: $url");
        exit();
    }

    function getRabbitMq():RabbitMq
    {
        return RabbitMq::instance();
    }
}