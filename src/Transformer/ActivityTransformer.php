<?php

namespace App\Transformer;

use App\Base64EncodedFileTransformers\Base64EncodedFile;
use App\Base64EncodedFileTransformers\UploadedBase64EncodedFile;
use App\DTO\ActivityDTO;
use App\Entity\Activity;
use App\Entity\Image;
use App\Entity\Technology;
use App\Entity\ActivityType;
use App\Entity\User;
use App\Exceptions\EntityNotFound;
use App\Exceptions\NotValidFileType;
use App\Repository\ActivityRepository;
use App\Repository\ImageRepository;
use App\Repository\TechnologyRepository;
use App\Repository\ActivityTypeRepository;
use App\Service\ActivityCoverManager;
use App\Service\ImageManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ActivityTransformer
{

    /**
     * @var TechnologyRepository
     */
    private $techRepo;
    /**
     * @var ActivityTypeRepository
     */
    private $typeRepo;
    /**
     * @var ActivityRepository
     */
    private $activityRepository;
    /**
     * @var ImageRepository
     */
    private $imageRepository;
    /**
     * @var ImageManager
     */
    private $activityCoverManager;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $checker;

    public function __construct(
        TechnologyRepository $techRepo,
        ActivityTypeRepository $typeRepo,
        ActivityRepository $activityRepository,
        ImageRepository $imageRepository,
        ActivityCoverManager $activityCoverManager,
        AuthorizationCheckerInterface $checker
    ) {
        $this->techRepo = $techRepo;
        $this->typeRepo = $typeRepo;
        $this->activityRepository = $activityRepository;
        $this->imageRepository = $imageRepository;
        $this->activityCoverManager = $activityCoverManager;
        $this->checker = $checker;
    }


    /**
     * @param ActivityDTO $dto
     * @param Activity $entity
     * @throws EntityNotFound
     */
    private function addTechnologies(ActivityDTO $dto, Activity $entity): void
    {
        /** @var Technology $tech */
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
            $entity->addTechnology($techToAdd);
        }
    }

    /**
     * @param ActivityDTO $dto
     * @param Activity $entity
     * @throws EntityNotFound
     */
    private function addTypes(ActivityDTO $dto, Activity $entity): void
    {
        /** @var ActivityType $activityType */
        foreach ($dto->types as $activityType) {
            $activityTypeID = $activityType->id;
            $activityTypeToAdd = $this->typeRepo->find($activityTypeID);
            if (!$activityTypeToAdd) {
                $entityNotFound = new EntityNotFound(
                    ActivityType::class,
                    $activityTypeID,
                    'No activity type found.'
                );
                throw $entityNotFound;
            }
            $entity->addType($activityTypeToAdd);
        }
    }

    /**
     * @param Activity $entity
     */
    private function resetTechTypeCollections(Activity $entity): void
    {
        foreach ($entity->getTechnologies() as $techToRemove) {
            $entity->removeTechnology($techToRemove);
        }
        foreach ($entity->getTypes() as $typeToRemove) {
            $entity->removeType($typeToRemove);
        }
    }

    /**
     * @param ActivityDTO $dto
     * @param User $owner
     * @return Activity
     * @throws EntityNotFound
     */
    public function createTransform(
        ActivityDTO $dto,
        User $owner
    ): Activity {

        $entity = new Activity();
        $entity->setName($dto->name);
        $entity->setDescription($dto->description);
        $entity->setApplicationDeadline($dto->applicationDeadline);
        $entity->setFinalDeadline($dto->finalDeadline);
        $entity->setPublic($dto->public);
        $entity->setOwner($owner);

        $this->addTechnologies($dto, $entity);
        $this->addTypes($dto, $entity);

        return $entity;
    }

    /**
     * @param ActivityDTO $dto
     * @param Activity $activity
     * @return Activity
     * @throws EntityNotFound
     * @throws NotValidFileType
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function editTransform(
        ActivityDTO $dto,
        Activity $activity
    ): Activity {

        $this->resetTechTypeCollections($activity);

        $activity->setName($dto->name);
        $activity->setDescription($dto->description);
        $activity->setApplicationDeadline($dto->applicationDeadline);
        $activity->setFinalDeadline($dto->finalDeadline);
        $isAdmin = $this->checker->isGranted('ROLE_ADMIN');
        if ($isAdmin || $activity->getStatus() !== (Activity::STATUS_IN_VALIDATION || Activity::STATUS_REJECTED)) {
            $activity->setStatus($dto->status);
        }

        $activity->setPublic($dto->public);

        $this->addTechnologies($dto, $activity);
        $this->addTypes($dto, $activity);

        if (!empty($dto->cover)) {
            $activityCover = new UploadedBase64EncodedFile(new Base64EncodedFile($dto->cover));

            $this->activityCoverManager->checkFileType($activityCover);

            $image = $this->activityCoverManager->createImage(
                $activity->getId() . '.' . $activityCover->guessExtension(),
                $activity->getName(),
                Image::IMAGE_TYPE_ACTIVITY
            );

            $currentImage = $activity->getCover();
            if ($currentImage) {
                $filename = $currentImage->getFile();
                $this->activityCoverManager->removeImageFromDirectory($filename);

                $activity->setCover(null);
                $this->imageRepository->delete($currentImage);
            }

            $this->imageRepository->save($image);
            $activity->setCover($image);
            $this->activityRepository->save($activity);

            $this->activityCoverManager->saveImageInDirectory(
                $activityCover,
                $activity->getId() . '.' . $activityCover->guessExtension()
            );
        }

        return $activity;
    }
}
