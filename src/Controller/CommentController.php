<?php

namespace App\Controller;

use App\DTO\CommentDTO;
use App\Entity\Activity;
use App\Entity\Comment;
use App\Exceptions\EntityNotFound;
use App\Repository\CommentRepository;
use App\Security\AccessRightsPolicy;
use App\Serializer\ValidationErrorSerializer;
use App\Transformer\CommentTransformer;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Comment controller.
 * @Route("/api", name="comment")
 */
class CommentController extends AbstractController
{
    /** @var SerializerInterface */
    private $serializer;

    /** @var CommentTransformer */
    private $transformer;

    /** @var ValidatorInterface */
    private $validator;
    /**
     * @var AccessRightsPolicy
     */
    private $accessRightsPolicy;

    public function __construct(
        SerializerInterface $serializer,
        CommentTransformer $transformer,
        ValidatorInterface $validator,
        AccessRightsPolicy $accessRightsPolicy
    ) {
        $this->serializer = $serializer;
        $this->transformer = $transformer;
        $this->validator = $validator;
        $this->accessRightsPolicy = $accessRightsPolicy;
    }

    /**
     * Add comment for activity.
     * @Rest\Post("/activities/{id}/add_comment", requirements={"id"="\d+"})
     * @SWG\Post(
     *     tags={"Comment"},
     *     summary="Add comment.",
     *     description="Add comment.",
     *     operationId="addComment",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of Activity",
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
     *     @Model(type=CommentDTO::class, groups={"AddComment"}),
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
     *     @SWG\Property(property="message", type="string", example="Your comment has successfully added!"),
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
     *     @SWG\Property(property="message", type="string", example="Bad request."),
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
     * @param CommentRepository $commentRepository
     * @param ValidationErrorSerializer $validationErrorSerializer
     * @return JsonResponse
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function addComment(
        Activity $activity,
        Request $request,
        CommentRepository $commentRepository,
        ValidationErrorSerializer $validationErrorSerializer
    ): JsonResponse {
        $authenticatedUser = $this->getUser();
        $rights = $this->accessRightsPolicy->canAccessActivity($activity, $authenticatedUser);
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if ($rights === false && !$isAdmin) {
            return new JsonResponse([
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Access denied!'
            ], Response::HTTP_FORBIDDEN);
        }

        $data = $request->getContent();

        /** @var DeserializationContext $context */
        $context = DeserializationContext::create()->setGroups(array('AddComment'));

        $commentDTO = $this->serializer->deserialize(
            $data,
            CommentDTO::class,
            'json',
            $context
        );
        $errors = $this->validator->validate($commentDTO, null, ['AddComment']);

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
            $addComment = $this->transformer->addComment($commentDTO, $activity, $authenticatedUser);
        } catch (EntityNotFound $exception) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => $exception->getMessage(),
                    'entity' => $exception->getEntity(),
                    'id' => $exception->getId()
                ],
                Response::HTTP_BAD_REQUEST
            );
        }
        $commentRepository->save($addComment);
        return new JsonResponse(['message' => 'Your comment has successfully added!'], Response::HTTP_OK);
    }

    /**
     * Edit comment.
     * @Rest\Post("/activities/{activityId}/edit_comment/{commentId}",
     *     requirements={"activityId"="\d+", "commentId"="\d+"})
     * @ParamConverter("activity", options={"mapping": {"activityId" : "id"}})
     * @ParamConverter("comment", options={"mapping": {"commentId" : "id"}})
     * @SWG\Post(
     *     tags={"Comment"},
     *     summary="Edit comment.",
     *     description="Edit comment.",
     *     operationId="editComment",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of Activity",
     *     in="path",
     *     name="activityId",
     *     required=true,
     *     type="integer",
     * ),
     *     @SWG\Parameter(
     *     description="ID of Comment to be edited",
     *     in="path",
     *     name="commentId",
     *     required=true,
     *     type="integer",
     * ),
     *     @SWG\Parameter(
     *     description="Json body for the request",
     *     name="requestBody",
     *     required=true,
     *     in="body",
     *     @Model(type=CommentDTO::class, groups={"EditComment"}),
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
     *     @SWG\Property(property="message", type="string", example="Your comment has successfully edited!"),
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
     *     @SWG\Property(property="message", type="string", example="Bad request."),
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
     * @param Request $request
     * @param CommentRepository $commentRepository
     * @param Comment $comment
     * @param ValidationErrorSerializer $validationErrorSerializer
     * @return JsonResponse
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function editComment(
        Request $request,
        CommentRepository $commentRepository,
        Comment $comment,
        ValidationErrorSerializer $validationErrorSerializer
    ): JsonResponse {
        $authenticatedUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isAdmin && $authenticatedUser !== $comment->getUser()) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => 'Access denied'
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $data = $request->getContent();

        /** @var DeserializationContext $context */
        $context = DeserializationContext::create()->setGroups(array('EditComment'));

        $commentDTO = $this->serializer->deserialize(
            $data,
            CommentDTO::class,
            'json',
            $context
        );
        $errors = $this->validator->validate($commentDTO, null, ['EditComment']);

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
        $editComment = $this->transformer->editComment($commentDTO, $comment);
        $commentRepository->save($editComment);
        return new JsonResponse(['message' => 'Your comment has successfully edited!'], Response::HTTP_OK);
    }

    /**
     * Get comments for activity.
     * @Rest\Get("/activities/{id}/comments", requirements={"id"="\d+"})
     * @SWG\Get(
     *     tags={"Comment"},
     *     summary="Get comments for activity.",
     *     description="Get comments for activity.",
     *     operationId="getComments",
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
     *     @Model(type=Comment::class, groups={"Comment"}),
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
     * @param CommentRepository $commentRepository
     * @param Activity $activity
     * @return JsonResponse
     */
    public function getCommentsForActivity(CommentRepository $commentRepository, Activity $activity): JsonResponse
    {
        $authenticatedUser = $this->getUser();
        $rights = $this->accessRightsPolicy->canAccessActivity($activity, $authenticatedUser);
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        if ($rights === false && !$isAdmin) {
            return new JsonResponse([
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Access denied!'
            ], Response::HTTP_FORBIDDEN);
        }

        $comments = $commentRepository->getCommentsForActivity($activity)->getQuery()->getResult();
        /** @var SerializationContext $context */
        $context = SerializationContext::create()
            ->enableMaxDepthChecks()
            ->setGroups(array('Comment'));

        $json = $this->serializer->serialize(
            $comments,
            'json',
            $context
        );

        return new JsonResponse($json, 200, [], true);
    }

    /**
     * Delete a comment.
     * @Rest\Delete("/comment/{id}/delete",requirements={"id"="\d+"})
     * @SWG\Delete(
     *     tags={"Comment"},
     *     summary="Delete an comment.",
     *     description="Delete an comment.",
     *     operationId="deleteCommentById",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of Comment to delete",
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
     *     @SWG\Property(property="message", type="string", example="Your comment was successfully deleted!"),
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
     *     description="Not Found",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=404),
     *     @SWG\Property(property="message", type="string", example="Not Found"),
     * )
     * )
     * @param Comment $comment
     * @param CommentRepository $commentRepository
     * @return JsonResponse
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteComment(
        Comment $comment,
        CommentRepository $commentRepository
    ): JsonResponse {
        $authenticatedUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isAdmin && $authenticatedUser->getId() !== $comment->getUser()->getId()) {
            return new JsonResponse([
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Access denied!'
            ], Response::HTTP_FORBIDDEN);
        }

        $comment->setDeleted(true);
        $commentRepository->save($comment);

        return new JsonResponse(['message' => 'Your comment was successfully deleted!'], Response::HTTP_OK);
    }
}
