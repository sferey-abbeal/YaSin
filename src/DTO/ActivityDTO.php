<?php


namespace App\DTO;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Swagger\Annotations as SWG;

/**
 * @Serializer\ExclusionPolicy("all")
 */
class ActivityDTO
{
    public const STATUS_IN_VALIDATION = 1;
    public const STATUS_NEW = 2;
    public const STATUS_FINISHED = 3;
    public const STATUS_CLOSED = 4;
    public const STATUS_REJECTED = 5;

    /**
     * @var int
     * @Serializer\Type("int")
     * @Serializer\Expose()
     */
    public $id;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Assert\NotBlank(
     *     message = "Activity name cannot be blank!",
     *     groups={"ActivityEdit", "ActivityCreate"}
     * )
     * @Serializer\Expose()
     * @Groups({"ActivityEdit", "ActivityCreate"})
     */
    public $name;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Assert\NotBlank(
     *     message = "Activity description cannot be blank!",
     *     groups={"ActivityEdit", "ActivityCreate"}
     * )
     * @Serializer\Expose()
     * @Groups({"ActivityEdit", "ActivityCreate"})
     */
    public $description;

    /**
     * @var DateTime
     * @Serializer\Type("DateTime<'U'>")
     * @Assert\GreaterThan("now", groups={"ActivityEdit", "ActivityCreate"})
     * @Serializer\Expose()
     * @Groups({"ActivityEdit", "ActivityCreate"})
     * @SWG\Property(example="15555555599")
     */
    public $applicationDeadline;

    /**
     * @var DateTime
     * @Serializer\Type("DateTime<'U'>")
     * @Assert\GreaterThan(propertyPath="applicationDeadline",
     *     groups={"ActivityEdit", "ActivityCreate"}
     * )
     * @Serializer\Expose()
     * @Groups({"ActivityEdit", "ActivityCreate"})
     * @SWG\Property(example="15555555599")
     */
    public $finalDeadline;

    /**
     * @var int
     * @Serializer\Type("integer")
     * @Assert\Range(
     *     min = 1,
     *     max = 4,
     *     minMessage="Status must be in range 1-4!",
     *     maxMessage="Status must be in range 1-4!",
     *     groups={"ActivityEdit"}
     * )
     * @Serializer\Expose()
     * @Groups({"ActivityEdit"})
     *
     */
    public $status = self::STATUS_IN_VALIDATION;


    /**
     * @var boolean
     * @Serializer\Type("boolean")
     * @Serializer\Expose()
     * @Groups({"ActivityEdit", "ActivityCreate"})
     */
    public $public;

    /**
     * @var Collection|TechnologyDTO[]
     * @Serializer\Type("ArrayCollection<App\DTO\TechnologyDTO>")
     * @Serializer\Expose()
     * @Groups({"ActivityEdit", "ActivityCreate"})
     */
    public $technologies;

    /**
     * @var Collection|ActivityTypeDTO[]
     * @Serializer\Type("ArrayCollection<App\DTO\ActivityTypeDTO>")
     * @Serializer\Expose()
     * @Groups({"ActivityEdit", "ActivityCreate"})
     */
    public $types;

    /**
     * The cover of Activity encoded in Base64
     * @var string
     * @Serializer\Type("string")
     * @Serializer\Expose()
     * @Groups({"ActivityEdit"})
     * @SWG\Property()
     */
    public $cover;

    public function __construct()
    {
        $this->technologies = new ArrayCollection();
        $this->types = new ArrayCollection();
    }
}
