<?php

namespace App\DataFixtures;

use App\Entity\Activity;
use App\Entity\ActivityUser;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class ActivityUserFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 10; $i++) {
            $activityUser = new ActivityUser();
            /** @var User $user */
            $user = $this->getReference('user_' . $i);
            $activityUser->setUser($user);
            /** @var Activity $activity */
            $activity = $this->getReference('activity_' . $i);
            $activityUser->setActivity($activity);
            $activityUser->setType(rand(0, 3));

            $manager->persist($activityUser);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return array(
            ActivityFixtures::class,
            UserFixtures::class,
        );
    }
}
