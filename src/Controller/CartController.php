<?php

namespace App\Controller;

use App\Service\CartService;
use App\Service\OrdersService;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(CartService $cartService, ProductRepository $productRepository): Response
    {
        if ($this->getUser()) {
            $cartService->restoreFromDatabase($this->getUser());
        }

        $cartItems = $cartService->getCartItemsWithProductData($productRepository);
        $total = $cartService->getTotal($productRepository);

        return $this->render('cart/index.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total,
        ]);
    }

    #[Route('/cart/add/{id}', name: 'app_cart_add')]
    public function add(int $id, CartService $cartService): Response
    {
        if ($this->getUser()) {
            $cartService->restoreFromDatabase($this->getUser());
        }

        $cartService->add($id);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/decrease/{id}', name: 'app_cart_decrease')]
    public function decrease(int $id, CartService $cartService): Response
    {
        if ($this->getUser()) {
            $cartService->restoreFromDatabase($this->getUser());
        }

        $cartService->decrease($id);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/remove/{id}', name: 'app_cart_remove')]
    public function remove(int $id, CartService $cartService): Response
    {
        if ($this->getUser()) {
            $cartService->restoreFromDatabase($this->getUser());
        }

        $cartService->remove($id);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/clear', name: 'app_cart_clear')]
    public function clear(CartService $cartService): Response
    {
        if ($this->getUser()) {
            $cartService->restoreFromDatabase($this->getUser());
        }

        $cartService->clear();

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/checkout', name: 'app_cart_checkout')]
    public function checkout(CartService $cartService, ProductRepository $productRepository): Response
    {
        if ($this->getUser()) {
            $cartService->restoreFromDatabase($this->getUser());
        }

        $cartItems = $cartService->getCartItemsWithProductData($productRepository);

        if (empty($cartItems)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart');
        }

        $user = $this->getUser();
        if (!$user) {
            // Sauvegarde temporaire du panier dans la session avant redirection
            return $this->redirectToRoute('app_login');
        }

        $addresses = $user->getCustomerAddresses();

        return $this->render('cart/choose_address.html.twig', [
            'cartItems' => $cartItems,
            'addresses' => $addresses,
            'total' => $cartService->getTotal($productRepository),
        ]);
    }

    #[Route('/cart/confirm', name: 'app_cart_confirm', methods: ['POST'])]
    public function confirm(Request $request, CartService $cartService, OrdersService $ordersService, ProductRepository $productRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('info', 'Veuillez vous connecter pour valider votre panier.');
            return $this->redirectToRoute('app_login');
        }

        // Restaurer le panier depuis la base pour l'utilisateur connecté
        $cartService->restoreFromDatabase($user);

        $cart = $cartService->getCart();
        if (empty($cart)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart');
        }

        $addressId = $request->request->get('address_id');
        $address = $user->getCustomerAddresses()->filter(fn($a) => $a->getId() == $addressId)->first();

        if (!$address) {
            $this->addFlash('danger', 'Adresse invalide.');
            return $this->redirectToRoute('app_cart_checkout');
        }

        // Crée la commande à partir du panier
        $order = $ordersService->createOrder($user, $address, $cart);

        // Vider le panier après création de la commande
        $cartService->clear();

        $this->addFlash('success', 'Votre commande a été confirmée !');

        return $this->render('cart/checkout.html.twig', [
            'order' => $order,
            'cartItems' => $cartService->getCartItemsWithProductData($productRepository),
            'total' => $cartService->getTotal($productRepository),
        ]);
    }
}
