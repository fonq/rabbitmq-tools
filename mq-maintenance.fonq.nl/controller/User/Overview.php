<?php
namespace Controller\User;

use Classes\AbstractController;
use Classes\RabbitMq;
use Classes\Template;

class Overview extends AbstractController
{
    function getSelectedMenuItem()
    {
        return 'user';
    }
    function getTitle(): string
    {
        return 'All users';
    }
    /**
     * @return string
     * @throws \Classes\Exception\HttpException
     */
    function getContent(): string
    {
        $users = RabbitMq::instance()->getUsers();

        $viewData = [
            'users' => $users
        ];
        return Template::parse('user/overview.twig', $viewData);
    }
}
