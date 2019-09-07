<?php

namespace App\Security;

use App\Entity\Activity;
use App\Entity\User;
use App\Repository\ActivityUserRepository;
use App\Repository\UserRepository;

class AccessRightsPolicy
{

    /**
     * @var ActivityUserRepository
     */
    private $activityUserRepository;
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(ActivityUserRepository $activityUserRepository, UserRepository $userRepository)
    {
        $this->activityUserRepository = $activityUserRepository;
        $this->userRepository = $userRepository;
    }

    public function canAccessActivity(Activity $activity, User $user): bool
    {
        if (($activity->isPublic() === true) || $user === $activity->getOwner()) {
            return true;
        }

        $userToActivity = $this->activityUserRepository->findBy(array('activity' => $activity, 'user' => $user));
        return !empty($userToActivity);
    }

    public function canGiveFeedback(Activity $activity, User $userFrom, User $userTo): bool
    {
        $usersAssigned = $this->userRepository->getAssignedUsersForActivity($activity)->getQuery()->getResult();

        if ($activity->getStatus() !== Activity::STATUS_FINISHED) {
            return false;
        }

        if ($userFrom === $activity->getOwner() && in_array($userTo, $usersAssigned, true)) {
            return true;
        }

        if ($userTo === $activity->getOwner() && in_array($userFrom, $usersAssigned, true)) {
            return true;
        }

        if (in_array($userTo, $usersAssigned, true) && in_array($userFrom, $usersAssigned, true)) {
            return true;
        }
        return false;
    }
}
