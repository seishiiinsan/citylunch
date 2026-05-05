<?php

namespace App\Controller;

use App\Entity\CartItem;
use App\Entity\Customer;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class CartController extends AbstractController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly ProductRepository $productRepository,
    ) {}

    #[Route('/cart', name: 'app_cart', methods: ['GET'])]
    public function index(): Response
    {
        /** @var Customer $customer */
        $customer = $this->getUser();
        $cart = $this->cartService->getOrCreateCart($customer);

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/cart/add/{productId}', name: 'app_cart_add', methods: ['POST'])]
    public function add(int $productId, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('cart_add', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_menu');
        }

        /** @var Customer $customer */
        $customer = $this->getUser();
        $product = $this->productRepository->find($productId)
            ?? throw $this->createNotFoundException();

        $cart = $this->cartService->getOrCreateCart($customer);

        try {
            $this->cartService->addItem($cart, $product);
            $this->addFlash('success', sprintf('"%s" ajouté au panier.', $product->getName()));
        } catch (\LogicException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('app_menu');
    }

    #[Route('/cart/remove/{itemId}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(int $itemId, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('cart_remove_' . $itemId, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_cart');
        }

        /** @var Customer $customer */
        $customer = $this->getUser();
        $cart = $this->cartService->getOrCreateCart($customer);

        $item = $cart->getItems()->filter(
            fn(CartItem $i) => $i->getId() === $itemId
        )->first() ?: null;

        if ($item === null) {
            $this->addFlash('error', 'Article introuvable dans votre panier.');
            return $this->redirectToRoute('app_cart');
        }

        $this->cartService->removeItem($cart, $item);
        $this->addFlash('success', 'Article retiré du panier.');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/update/{itemId}', name: 'app_cart_update', methods: ['POST'])]
    public function update(int $itemId, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('cart_update_' . $itemId, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_cart');
        }

        /** @var Customer $customer */
        $customer = $this->getUser();
        $cart = $this->cartService->getOrCreateCart($customer);

        $item = $cart->getItems()->filter(
            fn(CartItem $i) => $i->getId() === $itemId
        )->first() ?: null;

        if ($item === null) {
            $this->addFlash('error', 'Article introuvable dans votre panier.');
            return $this->redirectToRoute('app_cart');
        }

        $quantity = (int) $request->request->get('quantity', 1);

        try {
            $this->cartService->updateQty($cart, $item, $quantity);
        } catch (\LogicException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('app_cart');
    }
}
