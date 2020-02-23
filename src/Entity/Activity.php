<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Swagger\Annotations as SWG;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\ActivityRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Activity
{
    /**
     * Activity ID
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\Expose()
     * @Groups({"ActivityList", "ActivityDetails", "FeedbackList"})
     * @SWG\Property()
     */
    protected $id;

    /**
     * Activity name
     * @ORM\Column(type="string")
     * @Serializer\Expose()
     * @Groups({"ActivityList", "ActivityDetails", "ActivityCreate", "ActivityEdit", "FeedbackList"})
     * @SWG\Property()
     */
    private $name;

    /**
     * Activity description
     * @ORM\Column(type="text")
     * @Serializer\Expose()
     * @Groups({"ActivityList", "ActivityDetails", "ActivityCreate", "ActivityEdit"})
     * @SWG\Property()
     */
    private $description;
    /**
     * Activity owner (User)
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Serializer\Expose()
     * @Groups({"ActivityDetails", "ActivityCreate", "ActivityEdit", "ActivityList"})
     * @SWG\Property()
     */
    private $owner;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Expose()
     * @Serializer\Type("DateTime<'U'>")
     * @Groups({"ActivityDetails"})
     * @SWG\Property(example="15555555599")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Expose()
     * @Serializer\Type("DateTime<'U'>")
     * @Groups({"ActivityDetails"})
     * @SWG\Property(example="15555555599")
     */
    private $updatedAt;

    /**
     * Activity technologies (Technology Collection)
     * @var Collection|Technology[]
     * @ORM\ManyToMany(targetEntity="Technology")
     * @Serializer\Expose()
     * @Groups({"ActivityDetails", "ActivityCreate", "ActivityEdit"})
     * @SWG\Property()
     */
    protected $technologies;

    /**
     * Activity privacy (true=public, false=private)
     * @ORM\Column(type="boolean")
     * @Serializer\Expose()
     * @Groups({"ActivityDetails", "ActivityCreate", "ActivityEdit"})
     * @SWG\Property()
     */
    private $public;

    /**
     * @ORM\OneToMany(targetEntity="ActivityUser", mappedBy="activity")
     */
    private $activityUsers;

    /**
     * @var Image
     * @ORM\OneToOne(targetEntity="Image")
     * @ORM\JoinColumn()
     * @Serializer\Expose()
     * @Groups({"ActivityList", "ActivityDetails"})
     * @SWG\Property(ref="#/definitions/ActivityCover")
     */
    private $cover;

    public function __construct()
    {
        $this->technologies = new ArrayCollection();
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->activityUsers = new ArrayCollection();
    }

    /**
     * @return Technology[]
     */
    public function getTechnologies(): ?iterable
    {
        return $this->technologies;
    }

    /**
     * @param Technology $technology
     */
    public function addTechnology(Technology $technology): void
    {
        if ($this->technologies->contains($technology)) {
            return;
        }
        $this->technologies->add($technology);
    }

    /**
     * @param Technology $technology
     */
    public function removeTechnology(Technology $technology): void
    {
        if (!$this->technologies->contains($technology)) {
            return;
        }
        $this->technologies->removeElement($technology);
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

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): void
    {
        $this->owner = $owner;
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

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): void
    {
        $this->public = $public;
    }

    /**
     * @return Image
     */
    public function getCover(): ?Image
    {
        return $this->cover;
    }

    /**
     * @param Image $cover
     */
    public function setCover(?Image $cover): void
    {
        $this->cover = $cover;
    }
}
