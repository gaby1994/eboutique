<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use App\Entity\UserCart;
use App\Repository\UserCartRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class CartService
{
    private $session;
    private $em;
    private $userCartRepository;
    private ?User $user;

    public function __construct(
        RequestStack $requestStack,
        EntityManagerInterface $em,
        UserCartRepository $userCartRepository
    ) {
        $this->session = $requestStack->getSession();
        $this->em = $em;
        $this->userCartRepository = $userCartRepository;
        $this->user = null;
    }

    /**
     * Récupère le panier depuis la session
     */
    public function getCart(): array
    {
        return $this->session->get('cart', []);
    }

    /**
     * Ajoute un produit
     */
    public function add(int $id): void
    {
        $cart = $this->getCart();
        $cart[$id] = ($cart[$id] ?? 0) + 1;
        $this->session->set('cart', $cart);
        $this->persistIfUser($cart);
    }

    /**
     * Diminue la quantité d’un produit
     */
    public function decrease(int $id): void
    {
        $cart = $this->getCart();
        if (isset($cart[$id])) {
            $cart[$id]--;
            if ($cart[$id] <= 0) {
                unset($cart[$id]);
            }
            $this->session->set('cart', $cart);
            $this->persistIfUser($cart);
        }
    }

    /**
     * Retire complètement un produit
     */
    public function remove(int $id): void
    {
        $cart = $this->getCart();
        if (isset($cart[$id])) {
            unset($cart[$id]);
            $this->session->set('cart', $cart);
            $this->persistIfUser($cart);
        }
    }

    /**
     * Vide le panier
     */
    public function clear(): void
    {
        $this->session->remove('cart');
        if ($this->user) {
            $userCart = $this->userCartRepository->findOneBy(['user' => $this->user]);
            if ($userCart) {
                $userCart->setItems([]);
                $this->em->flush();
            }
        }
    }

    /**
     * Récupère les articles avec les données produits
     */
    public function getCartItemsWithProductData($productRepository): array
    {
        $cart = $this->getCart();
        $items = [];

        foreach ($cart as $productId => $quantity) {
            $product = $productRepository->find($productId);
            if ($product) {
                $items[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $product->getPriceHT() * $quantity,
                ];
            }
        }

        return $items;
    }

    /**
     * Calcule le total du panier
     */
    public function getTotal($productRepository): float
    {
        $total = 0;
        foreach ($this->getCart() as $productId => $quantity) {
            $product = $productRepository->find($productId);
            if ($product) {
                $total += $product->getPriceHT() * $quantity;
            }
        }

        return $total;
    }

    /**
     * Définir l'utilisateur courant
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Restaurer le panier depuis la base pour l'utilisateur connecté
     */
    public function restoreFromDatabase(User $user): void
    {
        $this->user = $user;
        $userCart = $this->userCartRepository->findOneBy(['user' => $user]);
        if ($userCart) {
            $this->session->set('cart', $userCart->getItems());
        }
    }

    /**
     * Persiste le panier en base pour l'utilisateur connecté
     */
    private function persistIfUser(array $cart): void
    {
        if (!$this->user) return;

        $userCart = $this->userCartRepository->findOneBy(['user' => $this->user]) ?? new UserCart();
        $userCart->setUser($this->user);
        $userCart->setItems($cart);
        $this->em->persist($userCart);
        $this->em->flush();
    }
}
