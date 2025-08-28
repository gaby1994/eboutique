<?php

namespace App\Controller;

use App\Form\CustomerAddressType;
use App\Form\ProfileType;
use App\Entity\CustomerAddress;
use App\Entity\Orders;
use App\Repository\CustomerAddressRepository;
use App\Repository\OrdersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function view(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $plainPassword)
                );
            }

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'profileForm' => $form->createView(),
        ]);
    }

    #[Route('/profile/addresses', name: 'app_profile_addresses')]
    public function addressesUser(CustomerAddressRepository $customerAddressRepository): Response
    {
        $user = $this->getUser();

        return $this->render('profile/user_addresses.html.twig', [
            'addresses' => $customerAddressRepository->findByUser($this->getUser()),
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/profile/addresses/new', name: 'app_profile_new_address')]
    public function newAddressUser(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        $customerAddress = new CustomerAddress();
        $customerAddress->setUser($user);

        $form = $this->createForm(CustomerAddressType::class, $customerAddress);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->getUser()) {
            $entityManager->persist($customerAddress);
            $entityManager->flush();

            return $this->redirectToRoute('app_profile_addresses');
        }

        return $this->render('profile/new_user_address.html.twig', [
            'customer_address' => $customerAddress,
            'form' => $form->createView(),
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/profile/addresses/delete', name: 'app_profile_delete_address')]
    public function deleteAddressUser(Request $request, CustomerAddress $customerAddress): Response
    {
        if ($this->isCsrfTokenValid('delete'.$customerAddress->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($customerAddress);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_profile_addresses');
    }

    #[Route('/profile/addresses/{id}/edit', name: 'app_profile_edit_address')]
    public function editAddressUser(CustomerAddress $customerAddress, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CustomerAddressType::class, $customerAddress);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Ici on n’utilise plus getDoctrine(), mais $entityManager
            $entityManager->persist($customerAddress);
            $entityManager->flush();

            return $this->redirectToRoute('app_profile_addresses');
        }

        return $this->render('profile/edit_user_address.html.twig', [
            'form' => $form->createView(),
            'customer_address' => $customerAddress,
        ]);
    }

    #[Route('/profile/addresses/{id}', name: 'app_profile_view_address')]
    public function showAddressUser(CustomerAddress $customerAddress): Response
    {
        $user = $this->getUser();

        if ($customerAddress->getUser() !== $user) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à cette adresse.");
        }

        return $this->render('profile/address_show.html.twig', [
            'customer_address' => $$customerAddress,
        ]);
    }

    #[Route('/profile/orders', name: 'app_profile_orders')]
    public function orders(OrdersRepository $ordersRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupère toutes les commandes de l'utilisateur
        $orders = $ordersRepository->findBy(['user' => $user]);

        return $this->render('profile/orders.html.twig', [
            'orders' => $orders,
        ]);
    }
}
