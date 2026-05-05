<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart', methods: ['GET'])]
    public function index(): Response
    {
        return new Response('Panier — à implémenter (phase 5)', 200);
    }

    #[Route('/cart/add/{productId}', name: 'app_cart_add', methods: ['POST'])]
    public function add(int $productId): Response
    {
        return new Response('Ajout panier — à implémenter (phase 5)', 200);
    }
}
