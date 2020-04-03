<?php

namespace App\DTO;

use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Swagger\Annotations as SWG;

/**
 * @Serializer\ExclusionPolicy("all")
 */
class PostsDTO
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
     *     groups={"Posts"}
     * )
     * @Groups({"Posts"})
     */
    public $text;

    /**
     * @Serializer\Type("string")
     * @Serializer\Expose()
     * @Groups({"Posts"})
     * @SWG\Property()
     */
    public $image;
}