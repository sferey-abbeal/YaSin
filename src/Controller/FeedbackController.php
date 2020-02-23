<?php

namespace App\Controller;

use App\DTO\FeedbackDTO;
use App\Entity\Activity;
use App\Entity\Feedback;
use App\Entity\User;
use App\Filters\FeedbackPagination;
use App\Filters\FeedbackSort;
use App\Handlers\FeedbackHandler;
use App\Repository\FeedbackRepository;
use App\Repository\UserRepository;
use App\Security\AccessRightsPolicy;
use App\Serializer\ValidationErrorSerializer;
use App\Transformer\FeedbackTransformer;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Feedback controller.
 * @Route("/api/feedback", name="feedback")
 */
class FeedbackController extends AbstractController
{
    /**
     * @var Serializer
     */
    private $serializer;
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var FeedbackTransformer
     */
    private $feedbackTransformer;
    /**
     * @var FeedbackRepository
     */
    private $feedbackRepository;
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var AccessRightsPolicy
     */
    private $accessRightsPolicy;
    /**
     * @var FeedbackHandler
     */
    private $feedbackHandler;

    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        FeedbackTransformer $feedbackTransformer,
        FeedbackRepository $feedbackRepository,
        UserRepository $userRepository,
        AccessRightsPolicy $accessRightsPolicy,
        FeedbackHandler $feedbackHandler
    ) {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->feedbackTransformer = $feedbackTransformer;
        $this->feedbackRepository = $feedbackRepository;
        $this->userRepository = $userRepository;
        $this->accessRightsPolicy = $accessRightsPolicy;
        $this->feedbackHandler = $feedbackHandler;
    }

    /**
     * Add feedback.
     * @Rest\Post("/{activityId}/{userId}", requirements={"activityId"="\d+", "userId"="\d+"})
     * @ParamConverter("activity", options={"mapping": {"activityId" : "id"}})
     * @ParamConverter("userTo", options={"mapping": {"userId" : "id"}})
     * @SWG\Post(
     *     tags={"Feedback"},
     *     summary="Add feedback.",
     *     description="Add feedback.",
     *     operationId="addfeedback",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of Activity on which feedback is given",
     *     in="path",
     *     name="activityId",
     *     required=true,
     *     type="integer",
     * ),
     *     @SWG\Parameter(
     *     description="ID of User to who feedback is given",
     *     in="path",
     *     name="userId",
     *     required=true,
     *     type="integer",
     * ),
     *     @SWG\Parameter(
     *     description="Comment an stars for feedback",
     *     name="requestBody",
     *     required=true,
     *     in="body",
     *     @Model(type=FeedbackDTO::class, groups={"AddFeedback"}),
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
     *     response=412,
     *     description="Precondition failed.",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=412),
     *     @SWG\Property(property="message",
     *     type="string", example="You were not involved or the job is not yet finished!"),
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
     * @SWG\Response(
     *     response="400",
     *     description="Bad Request",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="string", example="400"),
     *     @SWG\Property(property="message", type="string", example="You already gave feedback to this user involved!"),
     *     )
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Successfull operation!",
     *     @SWG\Schema(
     *     @SWG\Property(property="message", type="string", example="Feedback submitted successfully!"),
     *     )
     * )
     * @param Activity $activity
     * @param User $userTo
     * @param Request $request
     * @param ValidationErrorSerializer $validationErrorSerializer
     * @return JsonResponse
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function addFeedback(
        Activity $activity,
        User $userTo,
        Request $request,
        ValidationErrorSerializer $validationErrorSerializer
    ): JsonResponse {
        $authenticatedUser = $this->getUser();

        if ($this->feedbackRepository->hasUserGivenFeedback($activity, $authenticatedUser, $userTo)) {
            return new JsonResponse([
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'You already gave feedback to this user involved!'
            ], Response::HTTP_BAD_REQUEST);
        }

        $rights = $this->accessRightsPolicy->canGiveFeedback($activity, $authenticatedUser, $userTo);

        if ($rights === false) {
            return new JsonResponse([
                'code' => Response::HTTP_PRECONDITION_FAILED,
                'message' => 'You were not involved or the job is not yet finished!'
            ], Response::HTTP_PRECONDITION_FAILED);
        }

        $data = $request->getContent();

        /** @var DeserializationContext $context */
        $context = DeserializationContext::create()->setGroups(array('AddFeedback'));

        $feedbackDTO = $this->serializer->deserialize(
            $data,
            FeedbackDTO::class,
            'json',
            $context
        );

        $errors = $this->validator->validate($feedbackDTO, null, ['AddFeedback']);

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

        $newFeedback = $this->feedbackTransformer->addFeedback($feedbackDTO, $authenticatedUser, $activity, $userTo);
        $this->feedbackRepository->save($newFeedback);

        $userTo->setStars($this->feedbackRepository->getAvgStars($userTo));
        $this->userRepository->save($userTo);

        return new JsonResponse(['message' => 'Feedback submitted successfully!'], Response::HTTP_OK);
    }

    /**
     * Edit Feedback.
     * @Rest\Post("/{id}/edit", requirements={"id"="\d+"})
     * @SWG\Post(
     *     tags={"Feedback"},
     *     summary="Edit Feedback.",
     *     description="Edit Feedback.",
     *     operationId="editFeedback",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of Feedback to edit",
     *     in="path",
     *     name="id",
     *     required=true,
     *     type="integer",
     * ),
     *     @SWG\Parameter(
     *     description="Comment an stars for feedback",
     *     name="requestBody",
     *     required=true,
     *     in="body",
     *     @Model(type=FeedbackDTO::class, groups={"EditFeedback"}),
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
     *     @SWG\Property(property="message", type="string", example="Feedback successfully edited!"),
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
     * @param Feedback $feedback
     * @param Request $request
     * @param ValidationErrorSerializer $validationErrorSerializer
     * @return JsonResponse
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function editFeedback(
        Feedback $feedback,
        Request $request,
        ValidationErrorSerializer $validationErrorSerializer
    ): JsonResponse {
        $authenticatedUser = $this->getUser();

        if ($feedback->getUserFrom() !== $authenticatedUser) {
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
        $context = DeserializationContext::create()->setGroups(array('EditFeedback'));

        $feedbackDTO = $this->serializer->deserialize(
            $data,
            FeedbackDTO::class,
            'json',
            $context
        );

        $errors = $this->validator->validate($feedbackDTO, null, ['EditFeedback']);

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

        $updatedFeedback = $this->feedbackTransformer->editFeedback($feedbackDTO, $feedback);
        $this->feedbackRepository->save($updatedFeedback);

        $userTo = $feedback->getUserTo();
        $userTo->setStars($this->feedbackRepository->getAvgStars($userTo));

        return new JsonResponse(['message' => 'Feedback successfully edited!'], Response::HTTP_OK);
    }

    /**
     * Get user Feedback
     * @Rest\Get("/{id}", requirements={"id"="\d+"})
     * @param User $user
     * @param Request $request
     * @param FeedbackSort $feedbackSort
     * @param FeedbackPagination $feedbackPagination
     * @return JsonResponse
     * @SWG\Get(
     *     tags={"Feedback"},
     *     summary="Get user Feedback",
     *     description="Get user Feedback",
     *     operationId="getFeedback",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of User to see Feedback",
     *     in="path",
     *     name="id",
     *     required=true,
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
     *     description="Sorting by stars (asc or desc)",
     *     in="query",
     *     name="sortBy[stars]",
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
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Successfull operation!",
     *     @SWG\Schema(
     *     @SWG\Property(property="currentPage", type="integer"),
     *     @SWG\Property(property="numResults", type="integer"),
     *     @SWG\Property(property="perPage", type="integer"),
     *     @SWG\Property(property="numPages", type="integer"),
     *     @SWG\Property(property="results", type="array", @Model(type=Feedback::class, groups={"FeedbackList"}),
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
     */
    public function getUserFeedback(
        User $user,
        Request $request,
        FeedbackSort $feedbackSort,
        FeedbackPagination $feedbackPagination
    ): JsonResponse {

        $sorting = $request->query->get('sortBy');
        $feedbackSort->setSortingFields((array)$sorting);

        $pagination = $request->query->get('pagination');
        $feedbackPagination->setPaginationFields((array)$pagination);

        return new JsonResponse(
            json_encode($this->feedbackHandler->getUserFeedbackPaginated($user, $feedbackSort, $feedbackPagination)),
            200,
            [],
            true
        );
    }
}
