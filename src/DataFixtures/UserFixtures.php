<?php

namespace App\DataFixtures;

use App\Entity\Technology;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    public const POSITIONS = [
        'dev',
        'po',
        'qa'
    ];

    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    /**
     * @param UserPasswordEncoderInterface $encoder
     */
    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }


    public function load(ObjectManager $manager): void
    {

        $faker = Factory::create();

        $user = new User();
        $user->setUsername('ADMIN');
        $user->setPassword($this->encoder->encodePassword($user, 'iamadmin'));
        $user->setPosition(self::POSITIONS[rand(0, 2)]);
        $user->setSeniority(1);
        $user->setLocation(User::LOCATION[rand(0, 12)]);
        $user->setName('Staci');
        $user->setSurname('Nicolae');
        $user->setEmail('nstaci@pentalog.com');
        $user->setBiography($faker->sentence);
        $user->setStars(0);
        $user->setRoles((array)'ROLE_ADMIN');
        $manager->persist($user);

        $user = new User();
        $user->setUsername('project_manager');
        $user->setPassword($this->encoder->encodePassword($user, 'iampm'));
        $user->setPosition(self::POSITIONS[rand(0, 2)]);
        $user->setSeniority(1);
        $user->setLocation(User::LOCATION[rand(0, 12)]);
        $user->setName('Project');
        $user->setSurname('Manager');
        $user->setEmail('pm@pentalog.com');
        $user->setBiography($faker->sentence);
        $user->setStars(0);
        $user->setRoles((array)'ROLE_PM');
        $this->setReference('projectManager', $user);
        $manager->persist($user);

        $user = new User();
        $user->setUsername('USER');
        $user->setPassword($this->encoder->encodePassword($user, 'passtester'));
        $user->setPosition(self::POSITIONS[rand(0, 2)]);
        $user->setSeniority(1);
        $user->setLocation(User::LOCATION[rand(0, 12)]);
        $user->setName('Druta');
        $user->setSurname('Mihai');
        $user->setEmail('mdruta@pentalog.com');
        $user->setBiography($faker->sentence);
        $user->setStars(0);
        $user->setRoles((array)'ROLE_USER');

        /** @var User $projectManager */
        $projectManager = $this->getReference('projectManager');
        $user->setProjectManager($projectManager);
        $manager->persist($user);

        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setUsername($faker->userName);
            $user->setPassword($this->encoder->encodePassword($user, 'test_Password1'));
            $user->setPosition(self::POSITIONS[rand(0, 2)]);
            $user->setSeniority(mt_rand(0, 2));
            $user->setLocation(User::LOCATION[rand(0, 12)]);
            $user->setName($faker->firstName);
            $user->setSurname($faker->lastName);
            $user->setEmail($faker->email);
            $user->setBiography($faker->sentence);
            $user->setStars(0);
            $user->setRoles((array)'ROLE_USER');
            $this->setReference('user_' . $i, $user);

            /** @var Technology $technology */
            $technology = $this->getReference('tech_' . array_rand(TechnologyFixtures::TECHNOLOGIES));
            $user->addTechnology($technology);
            $manager->persist($user);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return array(
            TechnologyFixtures::class,
        );
    }
}
