<?php
namespace Classes;

use LogicException;

class StatusMessage
{
    private $text;
    private $autoHide;
    private $buttons = [];

    function __construct(string $text, bool $autoHide = false)
    {
        $this->text = $text;
        $this->autoHide = $autoHide;
        return $this;
    }
    function addButton(StatusMessageButton $button)
    {
        $this->buttons[] = $button;
        return $this;
    }
    function getHtml()
    {
        $autoHide_str = $this->autoHide ? ' autohide' : '';
        $aOut = [];
        $aOut[] = '<div class="form-popup-warn' . $autoHide_str . '">';
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