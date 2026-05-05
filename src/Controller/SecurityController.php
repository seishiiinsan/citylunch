<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(): Response
    {
        return new Response('Login — à implémenter (phase 4)', 200);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): never
    {
        throw new \LogicException('Intercepté par le firewall Symfony.');
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(): Response
    {
        return new Response('Inscription — à implémenter (phase 4)', 200);
    }
}
