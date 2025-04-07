<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, SerializerInterface $serializer, UserRepository $userRepository): Response
    {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        $userExists = $userRepository->findOneBy(["username"=>$user->getUsername()]);

        if ($userExists) {
            return $this->json("username already exists", 300);
        }


        /** @var string $plainPassword */
        $plainPassword = $user->getPassword();

        // encode the plain password
        $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

        $entityManager->persist($user);
        $entityManager->flush();

        // do anything else you need here, like send an email

        return $this->json(
            ['message' => 'User created successfully',], Response::HTTP_CREATED,);

    }
}
