<?php

namespace App\DTO;

use DateTime;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;

class TechnologyDTO
{
    /**
     * @var integer
     * @Serializer\Type("integer")
     * @Groups({"UserEdit", "ActivityEdit", "ActivityCreate"})
     */
    public $id;

    /**
     * @var string
     * @Serializer\Type("string")
     */
    public $name;

    /**
     * @var string
     * @Serializer\Type("string")
     */
    public $description;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }
}
