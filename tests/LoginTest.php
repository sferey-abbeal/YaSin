<?php

namespace App\Tests;

use App\DataFixtures\ActivityFixtures;
use App\DataFixtures\TechnologyFixtures;
use App\DataFixtures\TypeFixtures;
use App\DataFixtures\UserFixtures;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginTest extends WebTestCase
{
    use FixturesTrait;

    private $client;

    private $tokenEncoder;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->client = self::createClient();
        $this->tokenEncoder = static::$container->get(JWTEncoderInterface::class);
    }

    private function loadFixturesForTest(): void
    {
        $this->loadFixtures([
            TechnologyFixtures::class,
            UserFixtures::class,
            TypeFixtures::class,
            ActivityFixtures::class
        ]);
    }

    private function loginPostRequest($data): void
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );
    }

    /**
     * @throws JWTDecodeFailureException
     */
    private function getUsernameRoles($token): array
    {
        $decodedToken = $this->tokenEncoder->decode($token);
        return [
            'username' => $decodedToken['username'],
            'roles' => $decodedToken['roles'][0],
        ];
    }

    /**
     * @throws JWTDecodeFailureException
     */
    public function testSuccessfulAdminLogin(): void
    {
        $this->loadFixturesForTest();
        $data = [
            'username' => 'ADMIN',
            'password' => 'iamadmin'
        ];
        $this->loginPostRequest($data);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertArrayHasKey('token', json_decode($this->client->getResponse()->getContent(), true));
        $token = json_decode($this->client->getResponse()->getContent(), true)['token'];
        $this->assertEquals($data['username'], $this->getUsernameRoles($token)['username']);
        $this->assertEquals('ROLE_ADMIN', $this->getUsernameRoles($token)['roles']);
    }

    /**
     * @throws JWTDecodeFailureException
     */
    public function testSuccessfulUserLogin(): void
    {
        $this->loadFixturesForTest();
        $data = [
            'username' => 'USER',
            'password' => 'passtester'
        ];
        $this->loginPostRequest($data);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertArrayHasKey('token', json_decode($this->client->getResponse()->getContent(), true));
        $token = json_decode($this->client->getResponse()->getContent(), true)['token'];
        $this->assertEquals($data['username'], $this->getUsernameRoles($token)['username']);
        $this->assertEquals('ROLE_USER', $this->getUsernameRoles($token)['roles']);
    }

    public function testUnsuccessfulLogin(): void
    {
        $data = [
            'username' => 'incorrectUsername',
            'password' => 'incorrectPassword',
        ];
        $this->loginPostRequest($data);

        $this->assertEquals(401, $this->client->getResponse()->getStatusCode());
        $responseContent = $this->client->getResponse()->getContent();
        $this->assertEquals('Bad credentials.', json_decode($responseContent, true)['message']);
    }
}
