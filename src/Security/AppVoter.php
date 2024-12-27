<?php

namespace App\Security;

use App\Entity\Usuario;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AppVoter extends Voter
{
    #[Override]
    protected function supports(?string $attribute, mixed $subject): bool
    {
        return null === $attribute;
    }

    #[Override]
    protected function voteOnAttribute(?string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $usuario = $token->getUser();
        if (!$usuario instanceof Usuario) {
            return false;
        }

        $roles = $token->getRoleNames();

        return null === $attribute || [] === $roles || in_array('ROLE_' . strtoupper($attribute), $roles);
    }
}
