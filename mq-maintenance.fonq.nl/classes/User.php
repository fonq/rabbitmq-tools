<?php
namespace Classes;

class User
{
    static function setApiUser($apiUser)
    {
        $_SESSION['api_user'] = $apiUser;
    }
    static function setApiPass($apiPass)
    {
        $_SESSION['api_password'] = $apiPass;
    }

    static function getApiUser()
    {
        if(!isset($_SESSION['api_user']))
        {
            return null;
        }
        return $_SESSION['api_user'];
    }
    static function getApiPass()
    {
        if(!isset($_SESSION['api_password']))
        {
            return null;
        }
        return $_SESSION['api_password'];
    }

    static function logout()
    {
        Logger::log("Logout user " . $_SESSION['api_user'], Logger::VERBOSE);
        $_SESSION['api_user'] = null;
        $_SESSION['api_password'] = null;
        // Sending a 401 header effectively logs a user out when using basic http authentication.
        header('HTTP/1.0 401 Unauthorized');
        session_destroy();
        // Give browser the opportunity to clear session cookie.
        echo "<script>window.location = '/'</script>";
        exit();
    }

    static function isLoggedIn():bool
    {
        // self::logout();

        return RabbitMq::instance()->isLoggedIn();
    }
    private static function showLoginScreen()
    {
        header('WWW-Authenticate: Basic realm="RabbitMQ maintenance"');
        header('HTTP/1.0 401 Unauthorized');
        exit();
    }

    static function login()
    {
        if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
        {
            Logger::log("Try to login user " . $_SERVER['PHP_AUTH_USER'], Logger::VERBOSE);
            User::setApiUser($_SERVER['PHP_AUTH_USER']);
            User::setApiPass($_SERVER['PHP_AUTH_PW']);
        }

        // Show login screen if login is not correct or no login available.
        if(!User::isLoggedIn())
        {
            if(isset($_SERVER['PHP_AUTH_USER']))
            {
                Logger::log("Login failed for user " . $_SERVER['PHP_AUTH_USER'], Logger::VERBOSE);
            }

            self::showLoginScreen();
        }
        Logger::log("Login success for user " . $_SERVER['PHP_AUTH_USER'], Logger::VERBOSE);
    }
}
