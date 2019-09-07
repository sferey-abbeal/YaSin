<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\ActivityTypeRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class ActivityType
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\Expose()
     * @Groups({"TypeDetails", "ActivityCreate", "ActivityEdit", "ActivityDetails", "ActivityTypeList"})
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Expose()
     * @Groups({"TypeDetails", "ActivityDetails", "ActivityTypeList"})
     */
    private $name;

    /**
     * @ORM\Column(type="text")
     * @Serializer\Expose()
     * @Groups({"TypeDetails", "ActivityTypeList"})
     */
    private $description;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Expose()
     * @Serializer\Type("DateTime<'U'>")
     * @Groups({"TypeDetails"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Expose()
     * @Serializer\Type("DateTime<'U'>")
     * @Groups({"TypeDetails"})
     */
    private $updatedAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps(): void
    {
        $this->setUpdatedAt(new DateTime('now'));
        if ($this->getCreatedAt() === null) {
            $this->setCreatedAt(new DateTime('now'));
        }
    }
}
