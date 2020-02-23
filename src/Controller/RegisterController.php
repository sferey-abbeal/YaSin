<?php

namespace App\Controller;

use App\DTO\UserDTO;
use App\Repository\UserRepository;
use App\Serializer\ValidationErrorSerializer;
use App\Transformer\UserTransformer;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use JMS\Serializer\DeserializationContext;
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
use App\Exceptions\DuplicateUsernameEmail;

/**
 * Register controller.
 * @Route("/api", name="register")
 */
class RegisterController extends AbstractController
{
    /**
     * @var UserTransformer
     */
    private $transformer;
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(
        UserTransformer $transformer,
        ValidatorInterface $validator,
        SerializerInterface $serializer
    ) {
        $this->transformer = $transformer;
        $this->validator = $validator;
        $this->serializer = $serializer;
    }

    /**
     * Register User. (No Auth required!)
     * @Rest\Post("/register")
     * @SWG\Post(
     *     tags={"Login\Register"},
     *     summary="Register User. (No Auth required!)",
     *     description="Register User. (No Auth required!)",
     *     operationId="registerUser",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *     description="Json body for the request",
     *     name="requestBody",
     *     required=true,
     *     in="body",
     *     @Model(type=UserDTO::class, groups={"UserCreate"}),
     * )
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Successfull operation!",
     *     @SWG\Schema(
     *     @SWG\Property(property="message", type="string", example="User successfully created!"),
     *     )
     * )
     * @SWG\Response(
     *     response="400",
     *     description="Bad request",
     *     @SWG\Schema(
     *     @SWG\Property(property="code", type="integer", example=400),
     *     @SWG\Property(property="message", type="string", example="Bad Request!"),
     *     )
     * )
     * @param Request $request
     * @param UserRepository $userRepository
     * @param ValidationErrorSerializer $validationErrorSerializer
     * @return JsonResponse|Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createUser(
        Request $request,
        UserRepository $userRepository,
        ValidationErrorSerializer $validationErrorSerializer
    ) {
        $data = $request->getContent();

        /** @var DeserializationContext $context */
        $context = DeserializationContext::create()->setGroups(array('UserCreate'));

        $userDTO = $this->serializer->deserialize(
            $data,
            UserDTO::class,
            'json',
            $context
        );

        $errors = $this->validator->validate($userDTO, null, ['UserCreate']);

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
            $newUser = $this->transformer->registerTransform($userDTO);
        } catch (DuplicateUsernameEmail $exception) {
            return new JsonResponse(
                [
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage()
                ],
                Response::HTTP_BAD_REQUEST
            );
        }
        $userRepository->save($newUser);
        return new JsonResponse(
            [
                'code' => Response::HTTP_OK,
                'message' => 'User successfully created!'
            ],
            Response::HTTP_OK
        );
    }
}
