<?php
namespace Classes;

use LogicException;

class StatusMessage
{
    private $text;
    private $buttons = [];

    function __construct($text)
    {
        $this->text = $text;
        return $this;
    }
    function addButton(StatusMessageButton $button)
    {
        $this->buttons[] = $button;
        return $this;
    }
    function getHtml()
    {
        $aOut = [];
        $aOut[] = '<div class="form-popup-warn">';
        $aOut[] = '     '.$this->text;
        $aOut[] = '<br>';
        $aOut[] = '<br>';

        if(!empty($this->buttons))
        {
            foreach($this->buttons as $button)
            {
                if(!$button instanceof StatusMessageButton)
                {
                    throw new LogicException("Expected an instance of StatusMessageButton");
                }
                $aOut[] = '     <a href="' . $button->getUrl() . '">' . $button->getLabel() . '</a>';
            }
        }
        else
        {
            $aOut[] = '    <a href="#" id="close_dialog">Close</a>';
        }

        $aOut[] = '</div>';

        return join(PHP_EOL, $aOut);
    }
}