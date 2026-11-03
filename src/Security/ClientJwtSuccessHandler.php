<?php

namespace App\Security;

use App\Entity\Client;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;

class ClientJwtSuccessHandler extends AuthenticationSuccessHandler
{
    public function onAuthenticationSuccess(
        $request,
        $token
    ): JsonResponse {
        /**
         * @var Client
         */
        $user = $token->getUser();

        $jwt = $this->jwtManager->create($user);

        return new JsonResponse([
            'token' => $jwt,
            'user' => [
                'id' => $user->getUniqueID(),
                'email' => $user->getUserIdentifier(),
                'name' => $user->getFullname(),
                'phone' => $user->getPhone(),
            ],
        ]);
    }
}