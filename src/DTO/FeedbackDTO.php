<?php

namespace App\DTO;

use App\Entity\Activity;
use App\Entity\User;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;

class FeedbackDTO
{
    /**
     * @var int
     * @Serializer\Type("int")
     * @Serializer\Expose()
     */
    public $id;

    /**
     * @var int
     * @Serializer\Type("integer")
     * @Assert\Range(
     *     min = 1,
     *     max = 5,
     *     minMessage="Please select an amount of stars from 1 to 5!",
     *     maxMessage="Please select an amount of stars from 1 to 5!",
     *     groups={"AddFeedback"}
     * )
     * @Serializer\Expose()
     * @Groups({"AddFeedback", "EditFeedback"})
     */
    public $stars;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\Expose()
     * @Groups({"AddFeedback", "EditFeedback"})
     */
    public $comment;

    /**
     * @var User
     * @Serializer\Type("App\Entity\User")
     * @Serializer\Expose()
     */
    public $userFrom;

    /**
     * @var User
     * @Serializer\Type("App\Entity\User")
     * @Serializer\Expose()
     */
    public $userTo;

    /**
     * @var Activity
     * @Serializer\Type("App\Entity\Activity")
     * @Serializer\Expose()
     */
    public $activity;

}