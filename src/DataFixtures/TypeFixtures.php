<?php

namespace App\DataFixtures;

use App\Entity\ActivityType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class TypeFixtures extends Fixture
{
    public const TYPES = [
        'DT Estimation',
        'DT Audit',
        'Knowledge Sharing Session',
        'Coaching',
        'Mentoring',
        'Roadmap',
        'SkillValue Test',
        'Public Event',
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        foreach (self::TYPES as $typeName) {
            $type = new ActivityType();
            $type->setName($typeName);
            $type->setDescription($faker->sentence);
            $manager->persist($type);
            $this->setReference('type_' . $typeName, $type);
        }
        $manager->flush();
    }
}
