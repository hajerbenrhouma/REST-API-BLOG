<?php

namespace App\Controller;


use App\Security\UserConfirmationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontController extends AbstractController
{

    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        return new Response("THIS IS HOME FRONT");
    }

    /**
     * @Route("/confirm-user/{token}", name="confirm_user")
     */
    public function confirm(
        string $token,
        UserConfirmationService $userConfirmationService
    )
    {
        $userConfirmationService->confirmUser($token);
        return $this->redirectToRoute('index');
    }
}