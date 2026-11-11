<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/', name: 'ow_route')]
    public function owRoute(): Response
    { 
        return $this->json(['message'=>"oh... "]);
    }



     #[Route(path: '/create-admin-account', name: 'create_admin_route')]
    public function tmp(UserPasswordHasherInterface $userPasswordHasherInterface, EntityManagerInterface $em): Response
    { 
        $user =new User();
        $user->setEmail("contact@kingofroads.store");
        $user->setPassword( $userPasswordHasherInterface->hashPassword($user,"adminadmin") );
        $user->setRoles(['ROLE_ADMIN']);

        $em->persist($user);
        $em->flush();


        return $this->json(['message'=>"created account"]);
    }


 


    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
