<?php

namespace App\Tests;

use App\DataFixtures\ActivityFixtures;
use App\DataFixtures\TechnologyFixtures;
use App\DataFixtures\TypeFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class RegisterTest extends WebTestCase
{
    use FixturesTrait;

    private $client;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->loadFixtures([
            TechnologyFixtures::class,
            UserFixtures::class,
            TypeFixtures::class,
            ActivityFixtures::class
        ]);
        $this->client = self::createClient();
    }

    private function getRepository(KernelInterface $kernel, $class)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        return $entityManager->getRepository($class);
    }

    private function registerPostRequest($data): void
    {
        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );
    }

    private function getErrorCode()
    {
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        return $responseContent['errors'][0]['code'];
    }

    private function getPropertyPath()
    {
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        return $responseContent['errors'][0]['property_path'];
    }

    private function getValidationErrorMessage()
    {
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        return $responseContent['errors'][0]['message'];
    }

    public function testSuccessfulRegister(): void
    {
        $data = [
            'username' => 'TestUsername',
            'password' => 'TestPassword_1',
            'confirm_password' => 'TestPassword_1',
            'email' => 'testemail@test.mail',
            'name' => 'TestName',
            'surname' => 'TestSurname'
        ];
        $this->registerPostRequest($data);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $kernel = self::bootKernel();
        $userRepository = $this->getRepository($kernel, User::class);

        /** @var User $user */
        $user = $userRepository->findOneBy(array(
            'username' => 'TestUsername',
        ));

        $this->assertEquals('TestUsername', $user->getUsername());
        $this->assertEquals('testemail@test.mail', $user->getEmail());
        $this->assertEquals('TestName', $user->getName());
        $this->assertEquals('TestSurname', $user->getSurname());
    }

    public function dataProviderForUnsuccessfulRegister(): array
    {
        return [
            [
                [
                    'username' => 'TestUsername',
                ],
                'Username or Email already exist',
                null,
                null,
                null,
            ],
            [
                [
                    'email' => 'testemail@test.mail',
                ],
                'Username or Email already exist',
                null,
                null,
                null,
            ],
            [
                [
                    'username' => '',
                ],
                null,
                'username',
                'c1051bb4-d103-4f74-8988-acbcafc7fdc3',
                'This value should not be blank.',
            ],
            [
                [
                    'username' => 'Abc',
                ],
                null,
                'username',
                '9ff3fdc4-b214-49db-8718-39c315e33d45',
                'Your first name must be at least 4 characters long',
            ],
            [
                [
                    'username' => 'UsernameUsernameUsernameUsernameUsernameUsernameUsername',
                ],
                null,
                'username',
                'd94b19cc-114f-4f44-9cc4-4138e80a87b9',
                'Your first name cannot be longer than 50 characters',
            ],
            [
                [
                    'confirm_password' => 'TestPassword_A',
                ],
                null,
                'confirm_password',
                '478618a7-95ba-473d-9101-cabd45e49115',
                'Passwords do not match.',
            ],
            [
                [
                    'password' => 'TestPassword1',
                    'confirm_password' => 'TestPassword1',
                ],
                null,
                'password',
                'de1e3db3-5ed4-4941-aae4-59f3667cc3a3',
                'Password requirements(at least):length >8, 1 uppercase, 1 lowercase, 1 digit, 1 special',
            ],
            [
                [
                    'password' => 'Test_1',
                    'confirm_password' => 'Test_1',
                ],
                null,
                'password',
                'de1e3db3-5ed4-4941-aae4-59f3667cc3a3',
                'Password requirements(at least):length >8, 1 uppercase, 1 lowercase, 1 digit, 1 special',
            ],
            [
                [
                    'password' => 'TestPassword_',
                    'confirm_password' => 'TestPassword_',
                ],
                null,
                'password',
                'de1e3db3-5ed4-4941-aae4-59f3667cc3a3',
                'Password requirements(at least):length >8, 1 uppercase, 1 lowercase, 1 digit, 1 special',
            ],
            [
                [
                    'password' => 'testpassword_1',
                    'confirm_password' => 'testpassword_1',
                ],
                null,
                'password',
                'de1e3db3-5ed4-4941-aae4-59f3667cc3a3',
                'Password requirements(at least):length >8, 1 uppercase, 1 lowercase, 1 digit, 1 special',
            ],
            [
                [
                    'password' => 'TESTPASSWORD_1',
                    'confirm_password' => 'TESTPASSWORD_1',
                ],
                null,
                'password',
                'de1e3db3-5ed4-4941-aae4-59f3667cc3a3',
                'Password requirements(at least):length >8, 1 uppercase, 1 lowercase, 1 digit, 1 special',
            ],
            [
                [
                    'email' => '',
                ],
                null,
                'email',
                'c1051bb4-d103-4f74-8988-acbcafc7fdc3',
                'This value should not be blank.',
            ],
            [
                [
                    'email' => 'testemail',
                ],
                null,
                'email',
                'bd79c0ab-ddba-46cc-a703-a7a4b08de310',
                'The email \'"testemail"\' is not a valid email.',
            ],
            [
                [
                    'name' => '',
                ],
                null,
                'name',
                'c1051bb4-d103-4f74-8988-acbcafc7fdc3',
                'This value should not be blank.',
            ],
            [
                [
                    'surname' => '',
                ],
                null,
                'surname',
                'c1051bb4-d103-4f74-8988-acbcafc7fdc3',
                'This value should not be blank.',
            ],
        ];
    }

    /**
     * @dataProvider dataProviderForUnsuccessfulRegister
     * @param array $newData
     * @param string|null $expectedErrorMessage
     * @param string|null $expectedPropertyPath
     * @param string|null $expectedErrorCode
     * @param string|null $expectedValidationErrorMessage
     */
    public function testUnsuccessfulRegister(
        array $newData,
        ?string $expectedErrorMessage,
        ?string $expectedPropertyPath,
        ?string $expectedErrorCode,
        ?string $expectedValidationErrorMessage
    ): void {
        $initialData = [
            'username' => 'TestUsername1',
            'password' => 'TestPassword_1',
            'confirm_password' => 'TestPassword_1',
            'email' => 'testemail1@test.mail',
            'name' => 'TestName',
            'surname' => 'TestSurname'
        ];

        $data = array_merge($initialData, $newData);
        $this->registerPostRequest($data);

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        if ($expectedErrorMessage) {
            $responseContent = json_decode($this->client->getResponse()->getContent(), true);
            $message = $responseContent['message'];
            $this->assertEquals($expectedErrorMessage, $message);
        }

        if ($expectedPropertyPath && $expectedErrorCode) {
            $this->assertEquals($expectedPropertyPath, $this->getPropertyPath());
            $this->assertEquals($expectedErrorCode, $this->getErrorCode());
        }

        if ($expectedValidationErrorMessage) {
            $this->assertEquals($expectedValidationErrorMessage, $this->getValidationErrorMessage());
        }
    }
}
