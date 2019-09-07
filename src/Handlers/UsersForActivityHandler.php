<?php

namespace App\Handlers;

use App\Entity\Activity;
use App\Filters\UserListFilter;
use App\Filters\UserListPagination;
use App\Filters\UserListSort;
use App\Repository\UserRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;

class UsersForActivityHandler
{
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(SerializerInterface $serializer, UserRepository $userRepository)
    {

        $this->serializer = $serializer;
        $this->userRepository = $userRepository;
    }

    public function getUsersForActivityListPaginated(
        UserListPagination $userListPagination,
        UserListSort $userListSort,
        UserListFilter $userListFilter,
        Activity $activity
    ): array {

        $paginatedResults = $this->userRepository
            ->getUsersForActivityListPaginated($userListPagination, $userListSort, $userListFilter, $activity);

        $paginator = new Paginator($paginatedResults);
        $numResults = $paginator->count();

        /** @var SerializationContext $context */
        $context = SerializationContext::create()->setGroups(array('ActivityUser'));

        $json = $this->serializer->serialize(
            $paginatedResults->getResult(),
            'json',
            $context
        );

        if ($userListPagination->pageSize === -1) {
            $userListPagination->pageSize = $numResults;
        }

        return array(
            'results' => json_decode($json, true),
            'currentPage' => $userListPagination->currentPage,
            'numResults' => $numResults,
            'perPage' => $userListPagination->pageSize,
            'numPages' => (int)ceil($numResults / $userListPagination->pageSize)
        );
    }
}
