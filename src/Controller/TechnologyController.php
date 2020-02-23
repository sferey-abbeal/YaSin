<?php

namespace App\Controller;

use App\Entity\Technology;
use App\Repository\TechnologyRepository;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Technology controller.
 * @Route("/api/technologies", name="technologies")
 */
class TechnologyController extends AbstractController
{
    /**
     * @var TechnologyRepository
     */
    private $technologyRepository;
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer, TechnologyRepository $technologyRepository)
    {
        $this->technologyRepository = $technologyRepository;
        $this->serializer = $serializer;
    }

    /**
     * Get a list of all technologies sorted alphabetically
     * @Rest\Get()
     * @SWG\Get(
     *     tags={"Technology"},
     *     summary="Get a list of all technologies sorted alphabetically",
     *     description="Get a list of all technologies sorted alphabetically",
     *     operationId="getTechnologies",
     *     produces={"application/json"},
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Successfull operation!",
     *     @SWG\Schema(
     *     type="array",
     *     @Model(type=Technology::class, groups={"TechnologyList"})
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
    public function getTechnologyList(): JsonResponse
    {
        $queryBuilder = $this->technologyRepository->getTechnologies();

        /** @var SerializationContext $context */
        $context = SerializationContext::create()->setGroups(array('TechnologyList'));

        $json = $this->serializer->serialize(
            $queryBuilder->getQuery()->getResult(),
            'json',
            $context
        );

        return new JsonResponse($json, 200, [], true);
    }
}
