<?php

namespace App\Transformer;

use App\DTO\CommentDTO;
use App\Entity\Activity;
use App\Entity\Comment;
use App\Entity\User;
use App\Exceptions\EntityNotFound;
use App\Repository\CommentRepository;

class CommentTransformer
{
    /**
     * @var CommentRepository
     */
    private $commentRepository;

    public function __construct(CommentRepository $commentRepository)
    {
        $this->commentRepository = $commentRepository;
    }

    /**
     * @param CommentDTO $commentDTO
     * @param Activity $activity
     * @param User $user
     * @return Comment
     * @throws EntityNotFound
     */
    public function addComment(CommentDTO $commentDTO, Activity $activity, User $user): Comment
    {
        $entity = new Comment();
        $entity->setActivity($activity);
        $entity->setUser($user);
        $entity->setComment($commentDTO->comment);
        if ($commentDTO->parent) {
            $parentComment = $this->commentRepository->find($commentDTO->parent);
            if (!$parentComment) {
                throw new EntityNotFound(Comment::class, $commentDTO->parent, 'Comment not found');
            }
            $entity->setParent($parentComment);
        }
        return $entity;
    }

    /**
     * @param CommentDTO $commentDTO
     * @param Comment $comment
     * @return Comment
     */
    public function editComment(CommentDTO $commentDTO, Comment $comment): Comment
    {
        $comment->setComment($commentDTO->comment);
        return $comment;
    }
}
