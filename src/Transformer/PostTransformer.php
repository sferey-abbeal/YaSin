<?php

namespace App\Transformer;

use App\DTO\PostsDTO;
use App\Entity\Activity;
use App\Entity\Posts;
use App\Entity\User;

class PostTransformer
{
    public function addPost(PostsDTO $postsDTO, Activity $activity, User $user): Posts
    {
        $entity = new Posts();
        $entity->setActivity($activity);
        $entity->setOwner($user);
        $entity->setText($postsDTO->text);
        $entity->setImage($postsDTO->image);
        return $entity;
    }
}