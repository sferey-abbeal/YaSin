<?php

namespace App\Tests;

use App\DataFixtures\ActivityFixtures;
use App\DataFixtures\TechnologyFixtures;
use App\DataFixtures\TypeFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\Activity;
use App\Repository\ActivityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RejectJobTest extends WebTestCase
{
    use FixturesTrait;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->loadFixtures([
            TechnologyFixtures::class,
            UserFixtures::class,
            TypeFixtures::class,
            ActivityFixtures::class
        ]);
    }

    private function rejectJobPostRequest($activityToReject, Client $client): void
    {
        $format = '/api/activities/%d/reject';
        $client->request(
            'POST',
            sprintf($format, $activityToReject),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );
    }

    protected function createAuthenticatedClient($username, $password): Client
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/login',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode(array(
                'username' => $username,
                'password' => $password,
            ))
        );

        $data = json_decode($client->getResponse()->getContent(), true);

        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $data['token']));

        return $client;
    }

    public function testSuccessfulRejectJob(): void
    {
        $client = $this->createAuthenticatedClient('ADMIN', 'iamadmin');
        $activityRepository = self::$container->get(ActivityRepository::class);
        /** @var Activity $activityToReject */
        $activityToReject = $activityRepository->findOneBy(array(
            'status' => 1,
        ));
        $this->rejectJobPostRequest($activityToReject->getId(), $client);
        $em = self::$container->get(EntityManagerInterface::class);
        $em->refresh($activityToReject);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(Activity::STATUS_REJECTED, $activityToReject->getStatus());
    }
}
