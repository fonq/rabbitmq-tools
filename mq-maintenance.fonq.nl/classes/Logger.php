<?php
namespace Classes;

use Monolog\Logger as MonoLogger;
use Monolog\Handler\StreamHandler;

class Logger
{
    const VERBOSE = 0;
    const WARNING = 300;
    private static $tee = false;

    /**
     * @param bool $print_output
     */
    public static function tee(bool $print_output)
    {
        self::$tee = $print_output;
    }
    /**
     * @param string message
     * @param int $level Logger::VERBOSE or Logger::WARNING
     * @param string $type [main|cron|whatever]
     */
    public static function log(string $message, int $level, string $type = 'main')
    {
        if(self::$tee)
        {
            echo $message . PHP_EOL;
        }
        try
        {
            $logger = new MonoLogger('logger');
            $logger->pushHandler(new StreamHandler('../log/' .  date('Ym') . '-' . $type . '-warnings.log', Logger::WARNING));
            $logger->pushHandler(new StreamHandler('../log/' .  date('Ym') . '-' . $type . '-verbose.log', MonoLogger::INFO));

            if($level >= self::WARNING)
            {
                $logger->warning($message);
            }
            else
            {
                // (Ab)using info level for my verbose log.
                $logger->info($message);
            }

        }
        catch (\Exception $e)
        {
            echo '<h1>Sorry!</h1>';
            echo 'An exception was thrown in the logger. Since the logger it self broke, logging the message is not really an option. <br>';
            echo 'Here is the message: ' . $e->getMessage() . "<br>";
            exit();
        }
    }
}