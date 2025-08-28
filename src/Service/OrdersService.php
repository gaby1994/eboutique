<?php

namespace App\Service;

use App\Entity\Orders;
use App\Entity\CommandLine;
use App\Entity\User;
use App\Entity\CustomerAddress;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class OrdersService
{
    private EntityManagerInterface $em;
    private ProductRepository $productRepository;

    public function __construct(EntityManagerInterface $em, ProductRepository $productRepository)
    {
        $this->em = $em;
        $this->productRepository = $productRepository;
    }

    /**
     * Crée une commande à partir d'un panier
     */
    public function createOrder(User $user, CustomerAddress $address, array $cart): Orders
    {
        $order = new Orders();
        $order->setUser($user);
        $order->setCustomerAddress($address);
        $order->setValid(true);

        foreach ($cart as $productId => $quantity) {
            $product = $this->productRepository->find($productId);
            if ($product && $quantity > 0) {
                $line = new CommandLine();
                $line->setProduct($product);
                $line->setQuantity($quantity);
                $order->addCommandLine($line);
            }
        }

        $this->em->persist($order);
        $this->em->flush();

        return $order;
    }

    /**
     * Supprime toutes les commandes dont le total est égal à 0
     */
    public function removeEmptyOrders(): void
    {
        $ordersRepo = $this->em->getRepository(Orders::class);
        $allOrders = $ordersRepo->findAll();

        foreach ($allOrders as $order) {
            $total = 0;

            foreach ($order->getCommandLines() as $line) {
                $product = $line->getProduct();
                $quantity = $line->getQuantity() ?? 0;
                $price = $product ? $product->getPriceHT() : 0;
                $total += $price * $quantity;
            }

            if ($total === 0) {
                $this->em->remove($order);
            }
        }

        $this->em->flush();
    }
}
