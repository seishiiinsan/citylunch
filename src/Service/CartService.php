<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Customer;
use App\Entity\Product;
use App\Repository\CartItemRepository;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;

class CartService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CartRepository $cartRepository,
        private readonly CartItemRepository $cartItemRepository,
    ) {}

    /**
     * Récupère le panier du client, ou en crée un s'il n'en a pas.
     */
    public function getOrCreateCart(Customer $customer): Cart
    {
        $cart = $customer->getCart();

        if ($cart === null) {
            $cart = new Cart();
            $cart->setCustomer($customer);
            $customer->setCart($cart);
            $this->em->persist($cart);
            $this->em->flush();
        }

        return $cart;
    }

    /**
     * Ajoute un produit au panier.
     * Si le produit est déjà présent, incrémente la quantité.
     * Vérifie le stock disponible avant d'ajouter.
     *
     * @throws \LogicException si le stock est insuffisant
     */
    public function addItem(Cart $cart, Product $product, int $quantity = 1): void
    {
        $existingItem = $this->findItemByProduct($cart, $product);

        $currentQty = $existingItem ? $existingItem->getQuantity() : 0;
        $newQty = $currentQty + $quantity;

        if ($newQty > $product->getStock()) {
            throw new \LogicException(sprintf(
                'Stock insuffisant pour "%s" (demandé : %d, disponible : %d).',
                $product->getName(),
                $newQty,
                $product->getStock()
            ));
        }

        if ($existingItem) {
            $existingItem->setQuantity($newQty);
        } else {
            $item = new CartItem();
            $item->setProduct($product);
            $item->setQuantity($quantity);
            $cart->addItem($item);
            $this->em->persist($item);
        }

        $this->em->flush();
    }

    /**
     * Supprime une ligne du panier.
     */
    public function removeItem(Cart $cart, CartItem $item): void
    {
        $cart->removeItem($item);
        $this->em->flush();
    }

    /**
     * Met à jour la quantité d'une ligne.
     * Si la quantité est <= 0, supprime la ligne.
     *
     * @throws \LogicException si le stock est insuffisant
     */
    public function updateQty(Cart $cart, CartItem $item, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeItem($cart, $item);
            return;
        }

        if ($quantity > $item->getProduct()->getStock()) {
            throw new \LogicException(sprintf(
                'Stock insuffisant pour "%s" (demandé : %d, disponible : %d).',
                $item->getProduct()->getName(),
                $quantity,
                $item->getProduct()->getStock()
            ));
        }

        $item->setQuantity($quantity);
        $this->em->flush();
    }

    private function findItemByProduct(Cart $cart, Product $product): ?CartItem
    {
        foreach ($cart->getItems() as $item) {
            if ($item->getProduct()->getId() === $product->getId()) {
                return $item;
            }
        }

        return null;
    }
}
