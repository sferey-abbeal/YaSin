<?php

namespace App\Transformer;

use App\Base64EncodedFileTransformers\Base64EncodedFile;
use App\Base64EncodedFileTransformers\UploadedBase64EncodedFile;
use App\DTO\UserDTO;
use App\Entity\Image;
use App\Entity\Technology;
use App\Entity\User;
use App\Exceptions\DuplicateUsernameEmail;
use App\Exceptions\EntityNotFound;
use App\Exceptions\NotValidFileType;
use App\Exceptions\NotValidOldPassword;
use App\Repository\ImageRepository;
use App\Repository\TechnologyRepository;
use App\Repository\UserRepository;
use App\Service\ImageManager;
use App\Service\UserAvatarManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserTransformer
{
    /**
     * @var TechnologyRepository
     */
    private $techRepo;
    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var ImageRepository
     */
    private $imageRepository;
    /**
     * @var ImageManager
     */
    private $userAvatarManager;

    /**
     * UserTransformer constructor.
     * @param TechnologyRepository $techRepo
     * @param UserPasswordEncoderInterface $encoder
     * @param UserRepository $userRepository
     * @param ImageRepository $imageRepository
     * @param UserAvatarManager $userAvatarManager
     */
    public function __construct(
        TechnologyRepository $techRepo,
        UserPasswordEncoderInterface $encoder,
        UserRepository $userRepository,
        ImageRepository $imageRepository,
        UserAvatarManager $userAvatarManager
    ) {
        $this->techRepo = $techRepo;
        $this->encoder = $encoder;
        $this->userRepository = $userRepository;
        $this->imageRepository = $imageRepository;
        $this->userAvatarManager = $userAvatarManager;
    }

    /**
     * @param UserDTO $dto
     * @return User
     * @throws DuplicateUsernameEmail
     */
    public function registerTransform(
        UserDTO $dto
    ): User {
        $entity = new User();
        if ($this->userRepository->findOneBy(array('username' => $dto->username)) ||
            $this->userRepository->findOneBy(array('email' => $dto->email))) {
            $duplicateUsernameEmail = new DuplicateUsernameEmail(
                'Username or Email already exist',
                400
            );
            throw $duplicateUsernameEmail;
        }
        {
            $entity->setName($dto->name);
        }
        $entity->setPassword($this->encoder->encodePassword($entity, $dto->password));
        $entity->setSurname($dto->surname);
        $entity->setUsername($dto->username);
        $entity->setEmail($dto->email);
        $entity->setStars(0);
        $entity->setRoles((array)'ROLE_USER');
        return $entity;
    }

    /**
     * @param UserDTO $dto
     * @param User $user
     * @return User
     * @throws EntityNotFound
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws NotValidFileType
     */
    public function editTransform(
        UserDTO $dto,
        User $user
    ): User {
        foreach ($user->getTechnologies() as $techToRemove) {
            $user->removeTechnology($techToRemove);
        }

        $user->setName($dto->name);
        $user->setSurname($dto->surname);
        $user->setPosition($dto->position);
        $user->setSeniority($dto->seniority);
        $user->setLocation($dto->location);
        $user->setBiography($dto->biography);
        if ($dto->technologies !== null) {
            foreach ($dto->technologies as $tech) {
                $techID = $tech->id;
                $techToAdd = $this->techRepo->find($techID);
                if (!$techToAdd) {
                    $entityNotFound = new EntityNotFound(
                        Technology::class,
                        $techID,
                        'No technology found.'
                    );
                    throw $entityNotFound;
                }
                $user->addTechnology($techToAdd);
            }
        }

        if (!empty($dto->avatar)) {
            $userAvatar = new UploadedBase64EncodedFile(new Base64EncodedFile($dto->avatar));

            $this->userAvatarManager->checkFileType($userAvatar);

            $image = $this->userAvatarManager->createImage(
                $user->getId() . '.' . $userAvatar->guessExtension(),
                $user->getUsername(),
                Image::IMAGE_TYPE_USER
            );

            $currentImage = $user->getAvatar();
            if ($currentImage) {
                $filename = $currentImage->getFile();
                $this->userAvatarManager->removeImageFromDirectory($filename);


                $user->setAvatar(null);
                $this->imageRepository->delete($currentImage);
            }

            $this->imageRepository->save($image);
            $user->setAvatar($image);
            $this->userRepository->save($user);

            $this->userAvatarManager->saveImageInDirectory(
                $userAvatar,
                $user->getId() . '.' . $userAvatar->guessExtension()
            );
        }

        return $user;
    }

    /**
     * @param UserDTO $dto
     * @param User $user
     * @return User
     * @throws NotValidOldPassword
     */
    public function changePasswordTransform(
        UserDTO $dto,
        User $user
    ): User {
        $match = $this->encoder->isPasswordValid($user, $dto->oldPassword);
        if (!$match) {
            $passwordDoNotMatch = new NotValidOldPassword(
                'Old password not valid.',
                400
            );
            throw $passwordDoNotMatch;
        }
        $user->setPassword($this->encoder->encodePassword($user, $dto->password));
        return $user;
    }
}
