<?php
namespace Classes;

use Twig_Environment;
use Twig_Loader_Filesystem;
use Twig_Extension_Debug;
use Exception;

class Template
{
    public static function create():Twig_Environment
    {
        $aTemplatesDirs = [];
        $aTemplatesDirs[] = '../view';

        $oTwigLoader = new Twig_Loader_Filesystem($aTemplatesDirs);
        $aTwigConfig = array('cache' => '/tmp', 'debug' => true);

        $oTwig = new Twig_Environment($oTwigLoader, $aTwigConfig);
        $oTwig->addExtension(new Twig_Extension_Debug());

        return $oTwig;
    }

    public static function parse($sTemplate, $aData)
    {
        $oTwig = Template::create();
        try{
            return $oTwig->render($sTemplate, $aData);
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
        return 'Something went wrong with parsing the template';
    }
}