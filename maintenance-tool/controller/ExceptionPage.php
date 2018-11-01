<?php
namespace Controller;

use Classes\AbstractController;
use Classes\Template;

class ExceptionPage extends AbstractController
{
    private $exception;
    function setException(\Exception $exception)
    {
        $this->exception = $exception;
    }
    function getContent(): string
    {
        if(!$this->exception instanceof \Exception)
        {
            $this->exception = new \Exception('Exception inseption, Something went wrong when catching the exception.');
        }
        $view_data = [
            'exception' => $this->exception
        ];
        return Template::parse('exception_page.twig', $view_data);

        // TODO: Implement getContent() method.
    }
    function getTitle(): string
    {
        return 'Whoops';
    }
    function getSelectedMenuItem()
    {
      return '';
    }
}