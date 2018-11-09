<?php
namespace Classes;

class StatusMessageButton
{
    private $label;
    private $url;
    function __construct($label, $url)
    {
        $this->label = $label;
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

}