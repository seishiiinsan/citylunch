<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->redirectToRoute('app_menu');
    }

    #[Route('/menu', name: 'app_menu', methods: ['GET'])]
    public function menu(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAvailableToday();

        $plats = array_filter($products, fn(Product $p) => $p->getType() === Product::TYPE_PLAT);
        $desserts = array_filter($products, fn(Product $p) => $p->getType() === Product::TYPE_DESSERT);

        $today = new \DateTimeImmutable('today');

        $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        $months = [
            1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
            5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
            9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
        ];

        $dateFormatted = sprintf(
            '%s %s %s %s',
            $days[(int) $today->format('w')],
            $today->format('d'),
            $months[(int) $today->format('n')],
            $today->format('Y')
        );

        return $this->render('product/menu.html.twig', [
            'plats'         => $plats,
            'desserts'      => $desserts,
            'date'          => $today,
            'dateFormatted' => $dateFormatted,
        ]);
    }
}
