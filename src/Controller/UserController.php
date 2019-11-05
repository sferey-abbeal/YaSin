<?php

namespace App\Controller;

use App\DTO\UserDTO;
use App\Entity\Image;
use App\Entity\User;
use App\Exceptions\EntityNotFound;
use App\Exceptions\NotValidFileType;
use App\Exceptions\NotValidOldPassword;
use App\Filters\UserListFilter;
use App\Filters\UserListPagination;
use App\Handlers\UserHandler;
use App\Repository\ImageRepository;
use App\Repository\UserRepository;
use App\Serializer\ValidationErrorSerializer;
use App\Service\UserAvatarManager;
use App\Transformer\UserTransformer;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Swagger\Annotations as SWG;

/**
 * User controller.
 * @Route("/api/user", name="user")
 */
class UserController extends AbstractController
{
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var UserTransformer
     */
    private $transformer;
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var UserHandler
     */
    private $userHandler;

    public function __construct(
        SerializerInterface $serializer,
        UserTransformer $transformer,
        ValidatorInterface $validator,
        UserHandler $userHandler
    ) {
        $this->serializer = $serializer;
        $this->transformer = $transformer;
        $this->validator = $validator;
        $this->userHandler = $userHandler;
    }

    /**
     * Get details about an User.
     * @Rest\Get("/{id}", requirements={"id"="\d+"})
     * @SWG\Get(
     *     tags={"User"},
     *     summary="Get details about an User.",
     *     description="Get details about an User.",
     *     operationId="getUserById",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of User to return",
     *     in="path",
     *     name="id",
     *     required=true,
     *     type="integer",
     * )
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Successfull operation!",
     *     @Model(type=User::class, groups={"UserDetail"}),
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
     * @param User $user
     * @return Response
     */
    public function getUserDetails(User $user): Response
    {
        /** @var SerializationContext $context */
        $context = SerializationContext::create()->setGroups(array('UserDetail'));

        $json = $this->serializer->serialize(
            $user,
            'json',
            $context
        );

        return new JsonResponse($json, 200, [], true);
    }

    /**
     * Modify an User.
     * @Rest\Post("/{id}/edit", requirements={"id"="\d+"})
     * @SWG\Post(
     *     tags={"User"},
     *     summary="Edit an User.",
     *     description="Edit an User.",
     *     operationId="editUser",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of User to edit",
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
     *     @Model(type=UserDTO::class, groups={"UserEdit"}),
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
     *     @SWG\Property(property="message", type="string", example="User successfully edited!"),
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
     * @param User $user
     * @param Request $request
     * @param UserRepository $userRepository
     * @param ValidationErrorSerializer $validationErrorSerializer
     * @return JsonResponse|Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function editUser(
        User $user,
        Request $request,
        UserRepository $userRepository,
        ValidationErrorSerializer $validationErrorSerializer
    ) {
        $authenticatedUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        if (!$isAdmin && $authenticatedUser->getId() !== $user->getId()) {
            return new JsonResponse([
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Access denied!'
            ], Response::HTTP_FORBIDDEN);
        }

        $data = $request->getContent();

        /** @var DeserializationContext $context */
        $context = DeserializationContext::create()->setGroups(array('UserEdit'));

        $userDTO = $this->serializer->deserialize(
            $data,
            UserDTO::class,
            'json',
            $context
        );

        $errors = $this->validator->validate($userDTO, null, ['UserEdit']);

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
            $userEdit = $this->transformer->editTransform($userDTO, $user);
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
        } catch (NotValidFileType $exception) {
            return new JsonResponse(
                [
                    'code' => Response::HTTP_NOT_ACCEPTABLE,
                    'message' => $exception->getMessage(),
                    'filetype' => $exception->getFileType()
                ],
                Response::HTTP_NOT_ACCEPTABLE
            );
        }

        $userRepository->save($userEdit);
        return new JsonResponse(['message' => 'User successfully edited!'], Response::HTTP_OK);
    }

    /**
     * Delete an User.
     * @Rest\Delete("/{id}/delete", requirements={"id"="\d+"})
     * @SWG\Delete(
     *     tags={"User"},
     *     summary="Delete an User.",
     *     description="Delete an User.",
     *     operationId="deleteUserById",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of User to delete",
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
     *     @SWG\Property(property="message", type="string", example="The user was successfully deleted!"),
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
     * @param User $user
     * @param UserRepository $userRepository
     * @return JsonResponse
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteUser(User $user, UserRepository $userRepository): JsonResponse
    {
        $authenticatedUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isAdmin && $authenticatedUser->getId() !== $user->getId()) {
            return new JsonResponse([
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Access denied!'
            ], Response::HTTP_FORBIDDEN);
        }

        $userRepository->delete($user);

        return new JsonResponse(['message' => 'The user was successfully deleted!'], Response::HTTP_OK);
    }

    /**
     * Change password of User.
     * @Rest\Post("/{id}/change_password", requirements={"id"="\d+"})
     * @SWG\Post(
     *     tags={"User"},
     *     summary="Change password of User.",
     *     description="Change password of User.",
     *     operationId="userChangePassword",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of User to change password",
     *     in="path",
     *     name="id",
     *     required=true,
     *     type="integer",
     *     ),
     *     @SWG\Parameter(
     *     description="Json body for the request",
     *     name="requestBody",
     *     required=true,
     *     in="body",
     *     @Model(type=UserDTO::class, groups={"PasswordEdit"}),
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
     *     @SWG\Property(property="message", type="string", example="Password successfully changed!"),
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
     * @param User $user
     * @param UserRepository $userRepository
     * @param Request $request
     * @param ValidationErrorSerializer $validationErrorSerializer
     * @return JsonResponse
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function userChangePassword(
        User $user,
        UserRepository $userRepository,
        Request $request,
        ValidationErrorSerializer $validationErrorSerializer
    ): JsonResponse {
        $authenticatedUser = $this->getUser();

        if ($authenticatedUser->getId() !== $user->getId()) {
            return new JsonResponse([
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Access denied!'
            ], Response::HTTP_FORBIDDEN);
        }
        $data = $request->getContent();

        /** @var DeserializationContext $context */
        $context = DeserializationContext::create()->setGroups(array('PasswordEdit'));

        $userDTO = $this->serializer->deserialize(
            $data,
            UserDTO::class,
            'json',
            $context
        );
        $errors = $this->validator->validate($userDTO, null, ['PasswordEdit']);
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
            $userChangePassword = $this->transformer->changePasswordTransform($userDTO, $user);
        } catch (NotValidOldPassword $exception) {
            return new JsonResponse(
                [
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage()
                ],
                Response::HTTP_BAD_REQUEST
            );
        }
        $userRepository->save($userChangePassword);
        return new JsonResponse(['message' => 'Password successfully changed!'], Response::HTTP_OK);
    }

    /**
     * Get User List.
     * @Rest\Get("/list")
     * @SWG\Get(
     *     tags={"User"},
     *     summary="Get a list of all users",
     *     description="Get a list of all users",
     *     operationId="getUsers",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="Filtration by technologies",
     *     in="query",
     *     name="filter[technology][]",
     *     required=false,
     *     type="integer",
     *     ),
     *     @SWG\Parameter(
     *     description="Sorting by seniority (asc or desc). Desc by default.",
     *     in="query",
     *     name="sortBy[seniority]",
     *     required=false,
     *     type="string",
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
     *     )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Successfull operation!",
     *     @SWG\Schema(
     *     type="array",
     *     @Model(type=User::class, groups={"UserList"})
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
     * @param Request $request
     * @param UserListFilter $userListFilter
     * @param UserListSort $userListSort
     * @param UserListPagination $userListPagination
     * @return JsonResponse
     */

    public function getUserList(
        Request $request,
        UserListFilter $userListFilter,
        UserListPagination $userListPagination
    ): JsonResponse {

        $filter = $request->query->get('filter');
        $userListFilter->setFilterFields((array)$filter);

        $pagination = $request->query->get('pagination');
        $userListPagination->setPaginationFields((array)$pagination);

        return new JsonResponse(
            json_encode($this->userHandler
                ->getUserListPaginated(
                    $userListPagination,
                    $userListFilter
                )),
            200,
            [],
            true
        );
    }

    /**
     * Remove User avatar.
     * @Rest\Delete("/{id}/remove_avatar")
     * @SWG\Delete(
     *     tags={"User"},
     *     summary="Remove User avatar.",
     *     description="Remove User avatar.",
     *     operationId="removeAvatar",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="ID of User to edit",
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
     *     @SWG\Property(property="message", type="string", example="Avatar successfully deleted!"),
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
     * @param User $user
     * @param ImageRepository $imageRepository
     * @param UserAvatarManager $userAvatarManager
     * @return JsonResponse
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function removeAvatar(
        User $user,
        ImageRepository $imageRepository,
        UserAvatarManager $userAvatarManager
    ): JsonResponse {
        $authenticatedUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        if ($authenticatedUser !== $user && !$isAdmin) {
            return new JsonResponse([
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Access denied!'
            ], Response::HTTP_FORBIDDEN);
        }

        /** @var Image $image */
        $image = $authenticatedUser->getAvatar();
        if ($image) {
            $userAvatarManager->removeImageFromDirectory($image->getFile());
            $authenticatedUser->setAvatar(null);
            $imageRepository->delete($image);
        }
        return new JsonResponse(['message' => 'Avatar successfully deleted!'], Response::HTTP_OK);
    }
}
