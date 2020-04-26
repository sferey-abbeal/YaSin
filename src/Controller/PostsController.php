<?php

namespace App\Controller;

use App\DTO\PostsDTO;
use App\Entity\Activity;
use App\Entity\PostsLikes;
use App\Entity\Posts;
use App\Repository\LikeRepository;
use App\Repository\PostsRepository;
use App\Security\AccessRightsPolicy;
use App\Serializer\ValidationErrorSerializer;
use App\Transformer\PostTransformer;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * Comment controller.
 * @Route("/api", name="posts")
 */
class PostsController extends AbstractController
{
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var PostTransformer
     */
    private $transformer;
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var AccessRightsPolicy
     */
    private $accessRightsPolicy;

    public function __construct(
        SerializerInterface $serializer,
        PostTransformer $transformer,
        ValidatorInterface $validator,
        AccessRightsPolicy $accessRightsPolicy
    )
    {
        $this->serializer = $serializer;
        $this->transformer = $transformer;
        $this->validator = $validator;
        $this->accessRightsPolicy = $accessRightsPolicy;
    }

    /**
     * Add post to activity
     * @Rest\Post("/activities/{id}/add_post", requirements={"id"="\d+"})
     * @param Activity $activity
     * @param Request $request
     * @param PostsRepository $postsRepository
     * @param ValidationErrorSerializer $validationErrorSerializer
     * @return JsonResponse
     * @throws ORMException
     * @throws OptimisticLockException
     */

    public function addPost(
        Activity $activity,
        Request $request,
        PostsRepository $postsRepository,
        ValidationErrorSerializer $validationErrorSerializer
    ): JsonResponse
    {
        $authenticatedUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if ($authenticatedUser == $activity->getOwner() && !$isAdmin) {
            return new JsonResponse([
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Access denied!'
            ], Response::HTTP_FORBIDDEN);
        }
        $data = $request->getContent();

        /** @var DeserializationContext $context */
        $context = DeserializationContext::create()->setGroups(array('Posts'));
        $postDTO = $this->serializer->deserialize(
            $data,
            PostsDTO::class,
            'json',
            $context
        );
        $errors = $this->validator->validate($postDTO, null, ['Posts']);
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
        $addPost = $this->transformer->addPost($postDTO, $activity, $authenticatedUser);
        $postsRepository->save($addPost);
        return new JsonResponse(['message' => 'Your comment has successfully added!'], Response::HTTP_OK);
    }

    /**
     * Add post to activity
     * @Rest\Post("/like/post/{id}", requirements={"id"="\d+"})
     * @param Posts $posts
     * @param LikeRepository $likeRepository
     * @return JsonResponse
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function addLike(Posts $posts, LikeRepository $likeRepository): JsonResponse
    {

        $authenticatedUser = $this->getUser();
        $like_exist = $likeRepository->findLike($posts, $authenticatedUser);
        if ($like_exist) {
            $likeRepository->delete($like_exist);
            return new JsonResponse(['message' => 'Your like has successfully deleted!'], Response::HTTP_OK);
        }
        $like = New PostsLikes();
        $like->setPost($posts);
        $like->setUser($authenticatedUser);
        $likeRepository->save($like);
        return new JsonResponse(['message' => 'Your like has successfully added!'], Response::HTTP_OK);
    }

    /**
     * @Rest\Get("/activities/{id}/posts", requirements={"id"="\d+"})
     * @param Activity $activity
     * @param PostsRepository $postsRepository
     * @return JsonResponse
     */
    public function getActivityPosts(Activity $activity, PostsRepository $postsRepository): JsonResponse
    {
        $posts = $postsRepository->getPostsForActivity($activity)->getQuery()->getResult();

        /** @var SerializationContext $context */
        $context = SerializationContext::create()->setGroups(array('getPosts'));

        $json = $this->serializer->serialize(
            $posts,
            'json',
            $context
        );

        return new JsonResponse($json, 200, [], true);
    }

    /**
     * @Rest\Get("/posts")
     * @param PostsRepository $postsRepository
     * @return JsonResponse
     */
    public function getUserPosts(PostsRepository $postsRepository): JsonResponse
    {
        $authenticatedUser = $this->getUser();
        $posts = $postsRepository->getPostsForUser($authenticatedUser)->getQuery()->getResult();
        /** @var SerializationContext $context */
        $context = SerializationContext::create()->setGroups(array('getPosts'));

        $json = $this->serializer->serialize(
            $posts,
            'json',
            $context
        );

        return new JsonResponse($json, 200, [], true);
    }

}
