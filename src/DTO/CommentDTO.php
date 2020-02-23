<?php

namespace App\DTO;

use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Swagger\Annotations as SWG;

/**
 * @Serializer\ExclusionPolicy("all")
 */
class CommentDTO
{
    /**
     * @var int
     * @Serializer\Type("int")
     * @Serializer\Expose()
     * @SWG\Property()
     */
    public $id;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\Expose()
     * @SWG\Property()
     * @Assert\NotBlank(
     *     message = "Comment cannot be blank!",
     *     groups={"AddComment", "EditComment"}
     * )
     * @Groups({"AddComment", "EditComment"})
     */
    public $comment;

    /**
     * @Serializer\Type("int")
     * @Serializer\Expose()
     * @Groups({"AddComment"})
     * @SWG\Property()
     */
    public $parent;
}
