<?php

namespace App\Controller;

use App\DTO\ActivityDTO;
use App\Entity\Activity;
use App\Entity\ActivityUser;
use App\Entity\User;
use App\Exceptions\EntityNotFound;
use App\Exceptions\NotValidFileType;
use App\Filters\ActivityListFilter;
use App\Filters\ActivityListPagination;
use App\Filters\ActivityListSort;
use App\Filters\UserListFilter;
use App\Filters\UserListPagination;
use App\Filters\UserListSort;
use App\Handlers\ActivityHandler;
use App\Handlers\UsersForActivityHandler;
use App\Repository\ActivityUserRepository;
use App\Repository\ImageRepository;
use App\Security\AccessRightsPolicy;
use App\Serializer\ValidationErrorSerializer;
use App\Service\ActivityCoverManager;
use App\Service\EmailSender;
use App\Transformer\ActivityTransformer;
use DateTime;
use Doctrine\ORM\NonUniqueResultException;
use JMS\Serializer\DeserializationContext;
use App\Repository\ActivityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Swagger\Annotations as SWG;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Activity controller.
 * @Route("/api/activities", name="activities")
 */
class ActivityController extends AbstractController
{
    /** @var SerializerInterface */
    private $serializer;

    /** @var ActivityTransformer */
    private $transformer;

    /** @var ValidatorInterface */
    private $validator;
    /**
     * @var AccessRightsPolicy
     */
    private $accessRightsPolicy;
    /**
     * @var ActivityHandler
     */
    private $activityHandler;
    /**
     * @var UsersForActivityHandler
     */
    private $usersForActivityHandler;
    /**
     * @var EmailSender
     */
    private $emailSender;

    public function __construct(
        SerializerInterface $serializer,
        ActivityTransformer $transformer,
        ValidatorInterface $validator,
        AccessRightsPolicy $accessRightsPolicy,
        ActivityHandler $activityHandler,
        UsersForActivityHandler $usersForActivityHandler,
        EmailSender $emailSender
    ) {
        $this->serializer = $serializer;
        $this->transformer = $transformer;
        $this->validator = $validator;
        $this->accessRightsPolicy = $accessRightsPolicy;
        $this->activityHandler = $activityHandler;
        $this->usersForActivityHandler = $usersForActivityHandler;
        $this->emailSender = $emailSender;
    }

    /**
     * Get a list of all activities
     * @Rest\Get()
     * @param Request $request
     * @param ActivityListFilter $activityListFilter
     * @param ActivityListSort $activityListSort
     * @param ActivityListPagination $activityListPagination
     * @return JsonResponse
     * @SWG\Get(
     *     tags={"Activity"},
     *     summary="Get a list of all activities",
     *     description="Get a list of all activities",
     *     operationId="getActivities",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="Number of the current page (1 by default)",
     *     in="query",
     *     name="pagination[page]",
     *     required=false,
     *     type="integer",
     *     ),
     *     @SWG\Parameter(
     *     description="Number of items per page (10 by default)",
     *     in="query",
     *     name="pagination[per_page]",
     *     required=false,
     *     type="integer",
     *     ),
     *     @SWG\Parameter(
     *     description="Filtration by name",
     *     in="query",
     *     name="filter[name]",
     *     required=false,
     *     type="string",
     *     ),
     *     @SWG\Parameter(
     *     description="Filtration by status",
     *     in="query",
     *     name="filter[status]",
     *     required=false,
     *     type="integer",
     *     ),
     *     @SWG\Parameter(
     *     description="Filtration by owner",
     *     in="query",
     *     name="filter[owner]",
     *     required=false,
     *     type="integer",
     *     ),
     *     @SWG\Parameter(
     *     description="Filtration by assigned user",
     *     in="query",
     *     name="filter[assignedUser]",
     *     required=false,
     *     type="integer",
     *     ),
     *     @SWG\Parameter(
     *     description="Filtration by technologies",
     *     in="query",
     *     name="filter[technology][]",
     *     required=false,
     *     type="integer",
     *     ),
     *     @SWG\Parameter(
     *     description="Filtration by activity types",
     *     in="query",
     *     name="filter[activityType][]",
     *     required=false,
     *     type="integer",
     *     ),
     *     @SWG\Parameter(
     *     description="Sorting by name (asc or desc)",
     *     in="query",
     *     name="sortBy[name]",
     *     required=false,
     *     type="string",
     *     ),
     *     @SWG\Parameter(
     *     description="Sorting by creation date (asc or desc)",
     *     in="query",
     *     name="sortBy[createdAt]",
     *     required=false,
     *     type="string",
     *     ),
     *     @SWG\Parameter(
     *     description="Sorting by final deadline (asc or desc)",
     *     in="query",
     *     name="sortBy[finalDeadline]",
     *     required=false,
     *     type="string",
     *     )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Successfull operation!",
     *     @SWG\Schema(
     *     @SWG\Property(property="currentPage", type="integer"),
     *     @SWG\Property(property="numResults", type="integer"),
     *     @SWG\Property(property="perPage", type="integer"),
     *     @SWG\Property(property="numPages", type="integer"),
     *     @SWG\Property(property="results", type="array", @Model(type=Activity::class, groups={"ActivityList"}),
     *     )
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
    public function getActivitiesList(
        Request $request,
        ActivityListFilter $activityListFilter,
        ActivityListSort $activityListSort,
        ActivityListPagination $activityListPagination
    ): JsonResponse {
        $user = $this->getUser();

        $filter = $request->query->get('filter');
        $activityListFilter->setFilterFields((array)$filter);

        $sorting = $request->query->get('sortBy');
        $activityListSort->setSortingFields((array)$sorting);

        $pagination = $request->query->get('pagination');
        $activityListPagination->setPaginationFields((array)$pagination);

        return new JsonResponse(
            json_encode($this->activityHandler
                ->getActivitiesListPaginated(
                    $activityListPagination,
                    $activityListSort,
                    $activityListFilter,
                    $user
                )),
            200,
            [],
            true
        );
    }

    /**
     * Get details about and Activity
     * @Rest\Get("/{id}", requirements={"id"="\d+"})
     * @param Activity $activity
     * @return Response
     * @SWG\Get(
     *     tags={"Activity"},
     *     summary="Get details about and Activity",
     *     description="Get details about and Activity",
     *     operationId="getActivitById",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of Activity to return",
     *     in="path",
     *     name="id",
     *     required=true,
     *     type="integer",
     * )
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Successfull operation!",
     *     @Model(type=Activity::class, groups={"ActivityDetails"}),
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Unauthorized.",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=401),
     *     @SWG\Property(property="message", type="string", example="JWT Token not found"),
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Not found",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=404),
     *     @SWG\Property(property="message", type="string", example="Not found!"),
     *     )
     * )
     */
    public function getActivityDetails(Activity $activity): Response
    {
        $user = $this->getUser();
        $rights = $this->accessRightsPolicy->canAccessActivity($activity, $user);
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if ($activity->getStatus() === Activity::STATUS_IN_VALIDATION &&
            (!$isAdmin || $user !== $activity->getOwner() || $user !== $activity->getOwner()->getProjectManager())
        ) {
            return new JsonResponse([
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Access denied!'
            ], Response::HTTP_FORBIDDEN);
        }

        if ($rights === false && (!$isAdmin || $user !== $activity->getOwner()->getProjectManager())) {
            return new JsonResponse([
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Access denied!'
            ], Response::HTTP_FORBIDDEN);
        }

        /** @var SerializationContext $context */
        $context = SerializationContext::create()->setGroups(array('ActivityDetails'));

        $json = $this->serializer->serialize(
            $activity,
            'json',
            $context
        );

        return new JsonResponse($json, 200, [], true);
    }

    /**
     * Delete an Activity.
     * @Rest\Delete("/{id}/delete", requirements={"id"="\d+"})
     * @SWG\Delete(
     *     tags={"Activity"},
     *     summary="Delete an Activity.",
     *     description="Delete an Activity.",
     *     operationId="deleteActivityById",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of Activity to delete",
     *     in="path",
     *     name="id",
     *     required=true,
     *     type="integer",
     * )
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Successfull operation!",
     *     @SWG\Schema(
     *     @SWG\Property(property="message", type="string", example="The activity was successfully deleted!"),
     *     )
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Unauthorized.",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=401),
     *     @SWG\Property(property="message", type="string", example="JWT Token not found"),
     *     )
     * )
     * @SWG\Response(
     *     response="403",
     *     description="Forbidden",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=403),
     *     @SWG\Property(property="message", type="string", example="Access denied!"),
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Not found",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=404),
     *     @SWG\Property(property="message", type="string", example="Not found!"),
     *     )
     * )
     * @param Activity $activity
     * @param ActivityRepository $activityRepository
     * @return JsonResponse
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteActivity(Activity $activity, ActivityRepository $activityRepository): JsonResponse
    {
        $authenticatedUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isAdmin && $authenticatedUser !== $activity->getOwner()) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => 'Access denied!'
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $activityRepository->delete($activity);

        return new JsonResponse(['message' => 'The activity was successfully deleted!'], Response::HTTP_OK);
    }

    /**
     * Create an Activity.
     * @Rest\Post("/create")
     * @SWG\Post(
     *     tags={"Activity"},
     *     summary="Create an Activity.",
     *     description="Create an Activity.",
     *     operationId="createActivity",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="Json body for the request",
     *     name="requestBody",
     *     required=true,
     *     in="body",
     *     @Model(type=ActivityDTO::class, groups={"ActivityCreate"}),
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
     * @SWG\Response(
     *     response="201",
     *     description="Successfull operation!",
     *     @SWG\Schema(
     *     @SWG\Property(property="message", type="string", example="Activity successfully created!"),
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Activity not found.",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=404),
     *     @SWG\Property(property="message", type="string", example="No technology found."),
     *     @SWG\Property(property="entity", type="integer", example="App\\Entity\\Technology"),
     *     @SWG\Property(property="id", type="integer", example=999),
     * )
     * )
     * @param ActivityRepository $activityRepository
     * @param Request $request
     * @param ValidationErrorSerializer $validationErrorSerializer
     * @return Response
     * @throws LoaderError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function createAction(
        ActivityRepository $activityRepository,
        Request $request,
        ValidationErrorSerializer $validationErrorSerializer
    ): Response {
        $owner = $this->getUser();

        $data = $request->getContent();

        /** @var DeserializationContext $context */
        $context = DeserializationContext::create()->setGroups(array('ActivityCreate'));

        $activityDTO = $this->serializer->deserialize(
            $data,
            ActivityDTO::class,
            'json',
            $context
        );

        $errors = $this->validator->validate($activityDTO, null, ['ActivityCreate']);

        if (count($errors) > 0) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'Bad Request',
                    'errors' => $validationErrorSerializer->serialize($errors)
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $newActivity = $this->transformer->createTransform($activityDTO, $owner);
        } catch (EntityNotFound $exception) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => $exception->getMessage(),
                    'errors' => [
                        array(
                            'entity' => $exception->getEntity(),
                            'id' => $exception->getId()
                        )
                    ]
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        $activityRepository->save($newActivity);
        if ($owner->getProjectManager()) {
            $subject = ' has created a new activity waiting for your validation ';
            $this->emailSender->sendEmail($owner, $owner->getProjectManager(), $newActivity, $subject);
        }
        return new JsonResponse(['message' => 'Activity successfully created!'], Response::HTTP_CREATED);
    }

    /**
     * Edit an Activity.
     * @Rest\Post("/{id}/edit", requirements={"id"="\d+"})
     * @SWG\Post(
     *     tags={"Activity"},
     *     summary="Edit an Activity.",
     *     description="Edit an Activity.",
     *     operationId="editActivity",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of Activity to edit",
     *     in="path",
     *     name="id",
     *     required=true,
     *     type="integer",
     * ),
     *     @SWG\Parameter(
     *     description="Json body for the request",
     *     name="requestBody",
     *     required=true,
     *     in="body",
     *     @Model(type=ActivityDTO::class, groups={"ActivityEdit"}),
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
     * @SWG\Response(
     *     response="201",
     *     description="Successfull operation!",
     *     @SWG\Schema(
     *     @SWG\Property(property="message", type="string", example="Activity successfully edited!"),
     *     )
     * )
     * @SWG\Response(
     *     response="403",
     *     description="Forbidden",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=403),
     *     @SWG\Property(property="message", type="string", example="Access denied!"),
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Technoology not found.",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=404),
     *     @SWG\Property(property="message", type="string", example="No technology found."),
     *     @SWG\Property(property="entity", type="integer", example="App\\Entity\\Technology"),
     *     @SWG\Property(property="id", type="integer", example=999),
     * )
     * )
     * @param Activity $activity
     * @param ActivityRepository $activityRepository
     * @param Request $request
     * @param ValidationErrorSerializer $validationErrorSerializer
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws NotValidFileType
     */
    public function editAction(
        Activity $activity,
        ActivityRepository $activityRepository,
        Request $request,
        ValidationErrorSerializer $validationErrorSerializer
    ): Response {
        $authenticatedUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isAdmin && $authenticatedUser !== $activity->getOwner()) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => 'Access denied!'
                ],
                Response::HTTP_FORBIDDEN
            );
        }
        $data = $request->getContent();

        /** @var DeserializationContext $context */
        $context = DeserializationContext::create()->setGroups(array('ActivityEdit'));

        $activityDTO = $this->serializer->deserialize(
            $data,
            ActivityDTO::class,
            'json',
            $context
        );

        $errors = $this->validator->validate($activityDTO, null, ['ActivityEdit']);

        if (count($errors) > 0) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'Bad Request',
                    'errors' => $validationErrorSerializer->serialize($errors)
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $activityToEdit = $this->transformer->editTransform($activityDTO, $activity);
        } catch (EntityNotFound $exception) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => $exception->getMessage(),
                    'entity' => $exception->getEntity(),
                    'id' => $exception->getId()
                ],
                Response::HTTP_NOT_FOUND
            );
        }
        $activityRepository->save($activityToEdit);

        return new JsonResponse(['message' => 'Activity successfully edited!'], Response::HTTP_OK);
    }

    /**
     * Apply for an Activity.
     * @Rest\Post("/{id}/apply", requirements={"id"="\d+"})
     * @SWG\Post(
     *     tags={"Activity"},
     *     summary="Apply for an Activity.",
     *     description="Apply for an Activity.",
     *     operationId="applyForActivity",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of Activity to apply",
     *     in="path",
     *     name="id",
     *     required=true,
     *     type="integer",
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
     * @SWG\Response(
     *     response="200",
     *     description="Successfull operation!",
     *     @SWG\Schema(
     *     @SWG\Property(property="message", type="string", example="Applied with succes!"),
     *     )
     * )
     * @SWG\Response(
     *     response="400",
     *     description="Already applied!",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=400),
     *     @SWG\Property(property="message", type="string", example="You cannot apply!"),
     *     )
     * )
     * @SWG\Response(
     *     response="403",
     *     description="You are the owner of the Job!",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=403),
     *     @SWG\Property(property="message", type="string", example="Access denied!"),
     *     )
     * )
     * @SWG\Response(
     *     response="406",
     *     description="You are the owner of the Job!",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=406),
     *     @SWG\Property(property="message", type="string", example="You are the owner of this Job!"),
     *     )
     * )
     * @SWG\Response(
     *     response="412",
     *     description="Activity already finished or application deadline passed!",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=412),
     *     @SWG\Property(
     *     property="message",
     *     type="string", example="Activity is already finished or application deadline passed!"),
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Not found",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=404),
     *     @SWG\Property(property="message", type="string", example="Not found!"),
     *     )
     * )
     * @param Activity $activity
     * @param ActivityUserRepository $activityUserRepo
     * @return JsonResponse
     * @throws LoaderError
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function applyForActivity(
        Activity $activity,
        ActivityUserRepository $activityUserRepo
    ): JsonResponse {
        /** @var User $applierUser */
        $applierUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isAdmin && $activity->isPublic() === false) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => 'Access denied'
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        if ($activity->getOwner() === $applierUser) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_NOT_ACCEPTABLE,
                    'message' => 'You are the owner of this Job!'
                ],
                Response::HTTP_NOT_ACCEPTABLE
            );
        }

        if ($activity->getStatus() !== Activity::STATUS_NEW
            || $activity->getApplicationDeadline() < new DateTime('now')
        ) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_PRECONDITION_FAILED,
                    'message' => 'Activity is already finished or application deadline passed!'
                ],
                Response::HTTP_PRECONDITION_FAILED
            );
        }

        $activityUser = $activityUserRepo->getActivityUser($applierUser, $activity);

        if ($activityUser !== null) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'You cannot apply!'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }
        $subject = ' applied your job: ';
        $this->emailSender->sendEmail($applierUser, $activity->getOwner(), $activity, $subject);
        $activityUserRepo->apply($activity, $applierUser);
        return new JsonResponse(['message' => 'Applied with success!'], Response::HTTP_OK);
    }

    /**
     * Invite an User to an Activity.
     * @Rest\Post("/{activityId}/invite/{userId}", requirements={"activityId"="\d+", "userId"="\d+"})
     * @ParamConverter("activity", options={"mapping": {"activityId" : "id"}})
     * @ParamConverter("invitedUser", options={"mapping": {"userId" : "id"}})
     * @SWG\Post(
     *     tags={"Activity"},
     *     summary="Invite user to an Activity.",
     *     description="Invite user to an Activity.",
     *     operationId="inviteToActivity",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of Activity for invite",
     *     in="path",
     *     name="activityId",
     *     required=true,
     *     type="integer",
     * ),
     *     @SWG\Parameter(
     *     description="ID of User to be invited",
     *     in="path",
     *     name="userId",
     *     required=true,
     *     type="integer",
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
     * @SWG\Response(
     *     response="200",
     *     description="Successfull operation!",
     *     @SWG\Schema(
     *     @SWG\Property(property="message", type="string", example="User invited with succes!"),
     *     )
     * )
     * @SWG\Response(
     *     response="400",
     *     description="This user already applied/is invited/is assigned!",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=400),
     *     @SWG\Property(property="message", type="string"),
     *     )
     * )
     * @SWG\Response(
     *     response="403",
     *     description="Forbidden",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=403),
     *     @SWG\Property(property="message", type="string", example="Access denied!"),
     *     )
     * )
     * @SWG\Response(
     *     response="406",
     *     description="You are the owner of the Job!",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=406),
     *     @SWG\Property(property="message", type="string", example="You are the owner of this Job!"),
     *     )
     * )
     * @SWG\Response(
     *     response="412",
     *     description="Activity already finished or application deadline passed!",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=412),
     *     @SWG\Property(
     *     property="message",
     *     type="string", example="Activity is already finished or application deadline passed!"),
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Not found",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=404),
     *     @SWG\Property(property="message", type="string", example="Not found!"),
     *     )
     * )
     * @param Activity $activity
     * @param User $invitedUser
     * @param ActivityUserRepository $activityUserRepo
     * @return JsonResponse
     * @throws LoaderError
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function inviteUserToActivity(
        Activity $activity,
        User $invitedUser,
        ActivityUserRepository $activityUserRepo
    ): JsonResponse {
        /** @var User $authenticatedUser */
        $authenticatedUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isAdmin && $activity->getOwner() !== $authenticatedUser) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => 'Access denied'
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        if ($activity->getOwner() === $invitedUser) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_NOT_ACCEPTABLE,
                    'message' => 'You are the owner of this Job!'
                ],
                Response::HTTP_NOT_ACCEPTABLE
            );
        }

        if ($activity->getStatus() !== Activity::STATUS_NEW
            || $activity->getApplicationDeadline() < new DateTime('now')
        ) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_PRECONDITION_FAILED,
                    'message' => 'Activity is already finished or application deadline passed!'
                ],
                Response::HTTP_PRECONDITION_FAILED
            );
        }

        $activityUser = $activityUserRepo->getActivityUser($invitedUser, $activity);

        if ($activityUser !== null) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'This user already applied!'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $subject = ' invited you for the job: ';
        $this->emailSender->sendEmail($authenticatedUser, $invitedUser, $activity, $subject);
        $activityUserRepo->invite($activity, $invitedUser);
        return new JsonResponse(['message' => 'User invited with success!'], Response::HTTP_OK);
    }

    /**
     * Validate an applicant.
     * @Rest\Post("/{activityId}/applicants/{userId}/accept")
     * @ParamConverter("activity", options={"mapping": {"activityId" : "id"}})
     * @ParamConverter("user", options={"mapping": {"userId" : "id"}})
     * @SWG\Post(
     *     tags={"Activity"},
     *     summary="Validate an applicant.",
     *     description="Validate an applicant.",
     *     operationId="validateApplicant",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of Activity for validation",
     *     in="path",
     *     name="activityId",
     *     required=true,
     *     type="integer",
     * ),
     *     @SWG\Parameter(
     *     description="ID of User to be validated",
     *     in="path",
     *     name="userId",
     *     required=true,
     *     type="integer",
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
     * @SWG\Response(
     *     response="200",
     *     description="Successfull operation!",
     *     @SWG\Schema(
     *     @SWG\Property(property="message", type="string", example="User assigned with success!"),
     *     )
     * )
     * @SWG\Response(
     *     response="403",
     *     description="Forbidden",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=403),
     *     @SWG\Property(property="message", type="string", example="Access denied!"),
     *     )
     * )
     * @SWG\Response(
     *     response="400",
     *     description="Bad request.",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=400),
     *     @SWG\Property(property="message", type="string", example="User cannot be assigned!"),
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Not found",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=404),
     *     @SWG\Property(property="message", type="string", example="Not found!"),
     *     )
     * )
     * @param Activity $activity
     * @param User $user
     * @param ActivityUserRepository $activityUserRepo
     * @return JsonResponse
     * @throws LoaderError
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function acceptAnUserAppliance(
        Activity $activity,
        User $user,
        ActivityUserRepository $activityUserRepo
    ): JsonResponse {
        $authenticatedUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isAdmin && $activity->getOwner() !== $authenticatedUser) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => 'Access denied'
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $activityUser = $activityUserRepo->getActivityUser($user, $activity);
        if ($activityUser === null || $activityUser->getType() !== ActivityUser::TYPE_APPLIED) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'User cannot be assigned!'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $subject = ' accepted your application on Job: ';
        $this->emailSender->sendEmail($activity->getOwner(), $authenticatedUser, $activity, $subject);
        $activityUser->setType(ActivityUser::TYPE_ASSIGNED);
        $activityUserRepo->save($activityUser);
        return new JsonResponse(['message' => 'User assigned with success!'], Response::HTTP_OK);
    }

    /**
     * Reject an applicant.
     * @Rest\Post("/{activityId}/applicants/{userId}/decline", requirements={"activityId"="\d+", "userId"="\d+"})
     * @ParamConverter("activity", options={"mapping": {"activityId" : "id"}})
     * @ParamConverter("user", options={"mapping": {"userId" : "id"}})
     * @SWG\Post(
     *     tags={"Activity"},
     *     summary="Reject an applicant.",
     *     description="Reject an applicant.",
     *     operationId="rejectApplicant",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of Activity for rejection",
     *     in="path",
     *     name="activityId",
     *     required=true,
     *     type="integer",
     * ),
     *     @SWG\Parameter(
     *     description="ID of User to be rejected",
     *     in="path",
     *     name="userId",
     *     required=true,
     *     type="integer",
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
     * @SWG\Response(
     *     response="200",
     *     description="Successfull operation!",
     *     @SWG\Schema(
     *     @SWG\Property(property="message", type="string", example="User rejected!"),
     *     )
     * )
     * @SWG\Response(
     *     response="403",
     *     description="Forbidden",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=403),
     *     @SWG\Property(property="message", type="string", example="Access denied!"),
     *     )
     * )
     * @SWG\Response(
     *     response="400",
     *     description="Bad request.",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=400),
     *     @SWG\Property(property="message", type="string", example="User cannot be rejected!"),
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Not found",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=404),
     *     @SWG\Property(property="message", type="string", example="Not found!"),
     *     )
     * )
     * @param Activity $activity
     * @param User $user
     * @param ActivityUserRepository $activityUserRepo
     * @return JsonResponse
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function rejectAnUserAppliance(
        Activity $activity,
        User $user,
        ActivityUserRepository $activityUserRepo
    ): JsonResponse {
        $authenticatedUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isAdmin && $activity->getOwner() !== $authenticatedUser) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => 'Access denied'
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $activityUser = $activityUserRepo->getActivityUser($user, $activity);
        if ($activityUser === null || $activityUser->getType() !== ActivityUser::TYPE_APPLIED) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'User cannot be rejected!'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $subject = ' rejected your application on Job: ';
        $this->emailSender->sendEmail($activity->getOwner(), $authenticatedUser, $activity, $subject);
        $activityUser->setType(ActivityUser::TYPE_REJECTED);
        $activityUserRepo->save($activityUser);
        return new JsonResponse(['message' => 'User rejected!'], Response::HTTP_OK);
    }

    /**
     * Accept invitation for a job.
     * @Rest\Post("/{id}/accept", requirements={"id"="\d+"})
     * @SWG\Post(
     *     tags={"Activity"},
     *     summary="Accept a invitation for a job.",
     *     description="Accept a invitation for a job.",
     *     operationId="acceptInvitation",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of Activity",
     *     in="path",
     *     name="id",
     *     required=true,
     *     type="integer",
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
     * @SWG\Response(
     *     response="200",
     *     description="Successfull operation!",
     *     @SWG\Schema(
     *     @SWG\Property(property="message", type="string", example="You are assigned with success!"),
     *     )
     * )
     * @SWG\Response(
     *     response="400",
     *     description="Bad request",
     *     @SWG\Schema(
     *     @SWG\Property(property="message", type="string", example="You are not invited!"),
     *     )
     * )
     * @param Activity $activity
     * @param ActivityUserRepository $activityUserRepo
     * @return JsonResponse
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function acceptInvitation(
        Activity $activity,
        ActivityUserRepository $activityUserRepo
    ): JsonResponse {
        $authenticatedUser = $this->getUser();
        $accept = $activityUserRepo->getActivityUser($authenticatedUser, $activity);

        if ($accept === null || $accept->getType() !== ActivityUser::TYPE_INVITED) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'You are not invited!'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $subject = ' accepted your invitation on Job: ';
        $this->emailSender->sendEmail($authenticatedUser, $activity->getOwner(), $activity, $subject);
        $accept->setType(ActivityUser::TYPE_ASSIGNED);
        $activityUserRepo->save($accept);
        return new JsonResponse(['message' => 'You are assigned with success!'], Response::HTTP_OK);
    }

    /**
     * Decline invitation for a job.
     * @Rest\Post("/{id}/decline", requirements={"id"="\d+"})
     * @SWG\Post(
     *     tags={"Activity"},
     *     summary="Decline a invitation for a job.",
     *     description="Decline a invitation for a job.",
     *     operationId="declineInvitation",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of Activity",
     *     in="path",
     *     name="id",
     *     required=true,
     *     type="integer",
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
     * @SWG\Response(
     *     response="200",
     *     description="Successfull operation!",
     *     @SWG\Schema(
     *     @SWG\Property(property="message", type="string", example="You declined the invitation!"),
     *     )
     * )
     * @SWG\Response(
     *     response="400",
     *     description="Bad request",
     *     @SWG\Schema(
     *     @SWG\Property(property="message", type="string", example="You are not invited!"),
     *     )
     * )
     * @param Activity $activity
     * @param ActivityUserRepository $activityUserRepo
     * @return JsonResponse
     * @throws LoaderError
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function declineInvitation(
        Activity $activity,
        ActivityUserRepository $activityUserRepo
    ): JsonResponse {
        $authenticatedUser = $this->getUser();
        $accept = $activityUserRepo->getActivityUser($authenticatedUser, $activity);

        if ($accept === null || $accept->getType() !== ActivityUser::TYPE_INVITED) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'You are not invited!'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $subject = ' declined your invitation on Job: ';
        $this->emailSender->sendEmail($authenticatedUser, $activity->getOwner(), $activity, $subject);
        $accept->setType(ActivityUser::TYPE_DECLINED);
        $activityUserRepo->save($accept);
        return new JsonResponse(['message' => 'You declined the invitation!'], Response::HTTP_OK);
    }

    /**
     * Remove Activity cover.
     * @Rest\Delete("/{id}/remove_cover")
     * * @SWG\Delete(
     *     tags={"Activity"},
     *     summary="Remove Activity cover.",
     *     description="Remove Activity cover.",
     *     operationId="removeCover",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of Activity to edit",
     *     in="path",
     *     name="id",
     *     required=true,
     *     type="integer",
     * ),
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Unauthorized.",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=401),
     *     @SWG\Property(property="message", type="string", example="JWT Token not found"),
     *     )
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Successfull operation!",
     *     @SWG\Schema(
     *     @SWG\Property(property="message", type="string", example="Cover successfully deleted!"),
     *     )
     * )
     * @SWG\Response(
     *     response="403",
     *     description="Forbidden",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=403),
     *     @SWG\Property(property="message", type="string", example="Access denied!"),
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Not found",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=404),
     *     @SWG\Property(property="message", type="string", example="Not found!"),
     *     )
     * )
     * @param Activity $activity
     * @param ImageRepository $imageRepository
     * @param ActivityCoverManager $activityCoverManager
     * @return JsonResponse
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function removeCover(
        Activity $activity,
        ImageRepository $imageRepository,
        ActivityCoverManager $activityCoverManager
    ): JsonResponse {
        $authenticatedUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isAdmin && $authenticatedUser !== $activity->getOwner()) {
            return new JsonResponse([
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Access denied!'
            ], Response::HTTP_FORBIDDEN);
        }

        $image = $activity->getCover();
        if ($image) {
            $activityCoverManager->removeImageFromDirectory($image->getFile());
            $activity->setCover(null);
            $imageRepository->delete($image);
        }
        return new JsonResponse(['message' => 'Cover successfully deleted!'], Response::HTTP_OK);
    }

    /**
     * Get Users for Activity
     * @Rest\Get("/{id}/users", requirements={"id"="\d+"})
     * @SWG\Get(
     *     tags={"Activity"},
     *     summary="Get a list of all users for activity",
     *     description="Get a list of users for activity",
     *     operationId="getUsers",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of Activity",
     *     in="path",
     *     name="activityId",
     *     required=true,
     *     type="integer",
     *     ),
     *     @SWG\Parameter(
     *     description="Filtration by users on activity",
     *     in="query",
     *     name="filter[activityRole][]",
     *     required=false,
     *     type="integer",
     *     ),
     *     @SWG\Parameter(
     *     description="Number of the current page (1 by default)",
     *     in="query",
     *     name="pagination[page]",
     *     required=false,
     *     type="integer",
     *     ),
     *     @SWG\Parameter(
     *     description="Number of items per page (10 by default)",
     *     in="query",
     *     name="pagination[per_page]",
     *     required=false,
     *     type="integer",
     *     ),
     *     @SWG\Parameter(
     *     description="Sorting by seniority (asc or desc)",
     *     in="query",
     *     name="sortBy[seniority]",
     *     required=false,
     *     type="integer",
     *     )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Successfull operation!",
     *     @SWG\Schema(
     *     @SWG\Property(property="currentPage", type="integer"),
     *     @SWG\Property(property="numResults", type="integer"),
     *     @SWG\Property(property="perPage", type="integer"),
     *     @SWG\Property(property="numPages", type="integer"),
     *     @SWG\Property(property="results", type="array", @Model(type=User::class, groups={"ActivityUser"}),
     *     )
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
     * @SWG\Response(
     *     response=404,
     *     description="Not found",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=404),
     *     @SWG\Property(property="message", type="string", example="Not found!"),
     *     )
     * )
     * @param Activity $activity
     * @param Request $request
     * @param UserListFilter $userListFilter
     * @param UserListSort $userListSort
     * @param UserListPagination $userListPagination
     * @return JsonResponse
     */
    public function getUsersForActivity(
        Activity $activity,
        Request $request,
        UserListFilter $userListFilter,
        UserListSort $userListSort,
        UserListPagination $userListPagination
    ): JsonResponse {
        $filter = $request->query->get('filter');
        $userListFilter->setFilterFields((array)$filter);

        $sorting = $request->query->get('sortBy');
        $userListSort->setSortingFields((array)$sorting);

        $pagination = $request->query->get('pagination');
        $userListPagination->setPaginationFields((array)$pagination);
        return new JsonResponse(
            json_encode($this->usersForActivityHandler
                ->getUsersForActivityListPaginated(
                    $userListPagination,
                    $userListSort,
                    $userListFilter,
                    $activity
                )),
            200,
            [],
            true
        );
    }

    /**
     * Get activities for validation
     * @Rest\Get("/validation")
     * @SWG\Get(
     *     tags={"Activity"},
     *     summary="Get activities for validation",
     *     description="Get activities for validation",
     *     operationId="getActivities",
     *     produces={"application/json"}
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Successfull operation!",
     *     @Model(type=Activity::class, groups={"ActivityList"})
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Unauthorized.",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=401),
     *     @SWG\Property(property="message", type="string", example="JWT Token not found"),
     *     )
     * )
     * @SWG\Response(
     *     response="403",
     *     description="Forbidden",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=403),
     *     @SWG\Property(property="message", type="string", example="Access denied!"),
     *     )
     * )
     * @param ActivityRepository $activityRepository
     * @return JsonResponse
     */
    public function getActivitiesForValidation(ActivityRepository $activityRepository): JsonResponse
    {
        $authenticatedUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_PM');
        if (!$isAdmin) {
            return new JsonResponse([
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Access denied!'
            ], Response::HTTP_FORBIDDEN);
        }
        $listOfActivities = $activityRepository
            ->getActivitiesForValidation($authenticatedUser)
            ->getQuery()
            ->getResult();

        /** @var SerializationContext $context */
        $context = SerializationContext::create()->setGroups(array('ActivityList'));

        $json = $this->serializer->serialize(
            $listOfActivities,
            'json',
            $context
        );
        return new JsonResponse($json, 200, [], true);
    }

    /**
     * Validate a Job.
     * @Rest\Post("/{id}/validate")
     * @SWG\Post(
     *     tags={"Activity"},
     *     summary="Validate a Job.",
     *     description="Validate a Job.",
     *     operationId="validateJob",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of Activity for validation",
     *     in="path",
     *     name="id",
     *     required=true,
     *     type="integer",
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
     * @SWG\Response(
     *     response="200",
     *     description="Successfull operation!",
     *     @SWG\Schema(
     *     @SWG\Property(property="message", type="string", example="Job was validated with success!"),
     *     )
     * )
     * @SWG\Response(
     *     response="403",
     *     description="Forbidden",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=403),
     *     @SWG\Property(property="message", type="string", example="Access denied!"),
     *     )
     * )
     * @SWG\Response(
     *     response="400",
     *     description="Bad request.",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=400),
     *     @SWG\Property(property="message", type="string", example="This Job dont need a validation!"),
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Not found",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=404),
     *     @SWG\Property(property="message", type="string", example="Not found!"),
     *     )
     * )
     * @param Activity $activity
     * @param ActivityRepository $activityRepo
     * @return JsonResponse
     * @throws LoaderError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function validateJob(
        Activity $activity,
        ActivityRepository $activityRepo
    ): JsonResponse {
        $authenticatedUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isAdmin && $activity->getOwner()->getProjectManager() !== $authenticatedUser) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => 'Access denied'
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        if ($activity->getStatus() !== Activity::STATUS_IN_VALIDATION) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'This Job dont need a validation!'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $subject = ' validated your Job: ';
        $this->emailSender->sendEmail($authenticatedUser, $activity->getOwner(), $activity, $subject);
        $activity->setStatus(Activity::STATUS_NEW);
        $activityRepo->save($activity);
        return new JsonResponse(['message' => 'Job was validated with success!'], Response::HTTP_OK);
    }

    /**
     * Reject a Job.
     * @Rest\Post("/{id}/reject")
     * @SWG\Post(
     *     tags={"Activity"},
     *     summary="Reject a Job.",
     *     description="Reject a Job.",
     *     operationId="rejectJob",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of Activity to reject",
     *     in="path",
     *     name="id",
     *     required=true,
     *     type="integer",
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
     * @SWG\Response(
     *     response="200",
     *     description="Successfull operation!",
     *     @SWG\Schema(
     *     @SWG\Property(property="message", type="string", example="Job was rejected with success!"),
     *     )
     * )
     * @SWG\Response(
     *     response="403",
     *     description="Forbidden",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=403),
     *     @SWG\Property(property="message", type="string", example="Access denied!"),
     *     )
     * )
     * @SWG\Response(
     *     response="400",
     *     description="Bad request.",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=400),
     *     @SWG\Property(property="message", type="string", example="This Job dont need a validation!"),
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Not found",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=404),
     *     @SWG\Property(property="message", type="string", example="Not found!"),
     *     )
     * )
     * @param Activity $activity
     * @param ActivityRepository $activityRepo
     * @return JsonResponse
     * @throws LoaderError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function rejectJob(
        Activity $activity,
        ActivityRepository $activityRepo
    ): JsonResponse {
        $authenticatedUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isAdmin && $activity->getOwner()->getProjectManager() !== $authenticatedUser) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => 'Access denied'
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        if ($activity->getStatus() !== Activity::STATUS_IN_VALIDATION) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'This Job dont need a validation!'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $subject = ' rejected your Job: ';
        $this->emailSender->sendEmail($authenticatedUser, $activity->getOwner(), $activity, $subject);
        $activity->setStatus(Activity::STATUS_REJECTED);
        $activityRepo->save($activity);
        return new JsonResponse(['message' => 'Job was rejected with success!'], Response::HTTP_OK);
    }
}
