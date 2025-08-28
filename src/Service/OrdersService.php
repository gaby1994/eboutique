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
    private $em;
    private $productRepository;

    public function __construct(EntityManagerInterface $em, ProductRepository $productRepository)
    {
        $this->em = $em;
        $this->productRepository = $productRepository;
    }

    public function createOrder(User $user, CustomerAddress $address, array $cart): Orders
    {
        $order = new Orders();
        $order->setUser($user);
        $order->setCustomerAddress($address);
        $order->setValid(true); // commande validée

        foreach ($cart as $productId => $quantity) {
            $product = $this->productRepository->find($productId);
            if ($product) {
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
}
