<?php

namespace App\Controller;

use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $utils): Response {
        $error = $utils->getLastAuthenticationError();

        return $this->render('login/login.html.twig', [
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): Response {
        return $this->redirectToRoute('app_login');
    }

    #[Route('/password', name: 'app_password')]
    public function password(
        Request $request,
        UserPasswordHasherInterface $passwordEncoder,
        EntityManagerInterface $entityManager,
    ): Response
    {
        /** @var Usuario $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $form = $this->createForm(PasswordType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($passwordEncoder->hashPassword($user, $form->get('password')->getData()));
            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $this->render('login/password.html.twig');
    }

    #[Route('/menu', name: 'usuario_menu')]
    public function menuUsuario(): Response
    {
        return $this->render('layout/_menu_superior_usuario.html.twig');
    }
}
