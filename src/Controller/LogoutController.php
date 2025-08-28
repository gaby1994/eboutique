<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LogoutController extends AbstractController
{
    #[Route('/post-logout', name: 'post_logout')]
    public function postLogout(): Response
    {
        $this->addFlash('success', 'Vous avez été déconnecté avec succès.');
        return $this->redirectToRoute('app_login');
    }
}
