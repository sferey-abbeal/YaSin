<?php

namespace App\Handlers;

use App\Filters\PaginatorValidator;
use App\Filters\UserListFilter;
use App\Filters\UserListPagination;
use App\Filters\UserListSort;
use App\Repository\UserRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserHandler
{
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var PaginatorValidator
     */
    private $parameterValidator;

    public function __construct(
        SerializerInterface $serializer,
        UserRepository $userRepository,
        PaginatorValidator $parameterValidator
    ) {

        $this->serializer = $serializer;
        $this->userRepository = $userRepository;
        $this->parameterValidator = $parameterValidator;
    }

    public function getUserListPaginated(
        UserListPagination $userListPagination,
        UserListSort $userListSort,
        UserListFilter $userListFilter
    ): array {

        $paginatedResults = $this->userRepository
            ->getPaginatedUserList($userListPagination, $userListSort, $userListFilter);

        $paginator = new Paginator($paginatedResults);
        $numResults = $paginator->count();

        if (!$this->parameterValidator->isPageSizeValid($userListPagination->pageSize)) {
            throw new NotFoundHttpException();
        }
        if ($userListPagination->pageSize === -1) {
            $userListPagination->pageSize = $numResults;
        }

        $numPages = (int)ceil($numResults / $userListPagination->pageSize);
        if (!$this->parameterValidator->isPageNumberValid($numPages, $userListPagination->currentPage)) {
            throw new NotFoundHttpException();
        }

        /** @var SerializationContext $context */
        $context = SerializationContext::create()->setGroups(array('UserList'));

        $json = $this->serializer->serialize(
            $paginatedResults->getResult(),
            'json',
            $context
        );

        return array(
            'results' => json_decode($json, true),
            'currentPage' => $userListPagination->currentPage,
            'numResults' => $numResults,
            'perPage' => $userListPagination->pageSize,
            'numPages' => $numPages
        );
    }
}
