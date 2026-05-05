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

        return $this->render('product/menu.html.twig', [
            'plats' => $plats,
            'desserts' => $desserts,
            'date' => new \DateTimeImmutable('today'),
        ]);
    }
}
