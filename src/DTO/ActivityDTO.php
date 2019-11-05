<?php


namespace App\DTO;

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
    }
}
