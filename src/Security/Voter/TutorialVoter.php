<?php

namespace App\Security\Voter;

use App\Entity\Tutorial;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class TutorialVoter extends Voter
{
    public const EDIT = 'TUTORIAL_EDIT';
    public const DELETE = 'TUTORIAL_DELETE';

    // 1. Ce Voter est-il concerné par la question posée ?
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE])
            && $subject instanceof Tutorial;
    }

    // 2. La réponse : oui ou non ?
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        // Visiteur non connecté : refusé
        if (!$user instanceof User) {
            return false;
        }

        /** @var Tutorial $subject */
        // Autorisé seulement si l'utilisateur est l'auteur du tutoriel
        return $subject->getAuthor() === $user;
    }
}