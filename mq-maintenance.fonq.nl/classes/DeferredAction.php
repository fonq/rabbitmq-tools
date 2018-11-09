<?php
namespace Classes;

class DeferredAction
{
    static function register($key, $url)
    {
        if(!isset($_SESSION['deferred_actions']))
        {
            $_SESSION['deferred_actions'] = [];
        }
        $_SESSION['deferred_actions'][$key] = $url;
    }
    static function get($key)
    {
        if(isset($_SESSION['deferred_actions'][$key]))
        {
            $url = $_SESSION['deferred_actions'][$key];
            unset($_SESSION['deferred_actions'][$key]);
            return $url;
        }
        return null;
    }
}