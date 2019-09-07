<?php

namespace App\EventListener;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;

class JWTDecodedListener
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function onJWTDecoded(JWTDecodedEvent $event)
    {
        $payload = $event->getPayload();
        $user = $this->userRepository->findOneBy(array('username' => $payload['username']));

        if (!$user) {
            $event->markAsInvalid();
            return;
        }

        $tokenCreatedAt = $payload['iat'];
        $passwordChangedAt = $user->getPasswordChangedAt();

        if ($passwordChangedAt && $tokenCreatedAt < $passwordChangedAt->getTimestamp()) {
            $event->markAsInvalid();
        }
    }
}
