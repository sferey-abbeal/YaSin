<?php

namespace App\Controller;

use App\Entity\ActivityType;
use App\Repository\ActivityTypeRepository;
use FOS\RestBundle\Controller\Annotations\Route;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;

/**
 * ActivityType controller.
 * @Route("/api/activity-types", name="activityTypes")
 */
class ActivityTypeController extends AbstractController
{
    /**
     * @var ActivityTypeRepository
     */
    private $activityTypeRepository;
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(ActivityTypeRepository $activityTypeRepository, SerializerInterface $serializer)
    {
        $this->activityTypeRepository = $activityTypeRepository;
        $this->serializer = $serializer;
    }

    /**
     * Get a list of all activity types sorted alphabetically
     * @Rest\Get()
     * @SWG\Get(
     *     tags={"ActivityType"},
     *     summary="Get a list of all activity types sorted alphabetically",
     *     description="Get a list of all activity types sorted alphabetically",
     *     operationId="getActivityTypes",
     *     produces={"application/json"},
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Successfull operation!",
     *     @SWG\Schema(
     *     type="array",
     *     @Model(type=ActivityType::class, groups={"ActivityTypeList"})
     * )
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Unauthorized.",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=401),
     *     @SWG\Property(property="message", type="string", example="JWT Token not found"),
     *     )
     * )
     */
    public function getActivityTypeList(): JsonResponse
    {
        $queryBuilder = $this->activityTypeRepository->getActivityTypes();

        /** @var SerializationContext $context */
        $context = SerializationContext::create()->setGroups(array('ActivityTypeList'));
        $json = $this->serializer->serialize(
            $queryBuilder->getQuery()->getResult(),
            'json',
            $context
        );

        return new JsonResponse($json, 200, [], true);
    }
}
