<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\FeedbackRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Feedback
{
    /**
     * Feedback ID
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\Expose()
     * @Groups({"FeedbackList"})
     */
    private $id;

    /**
     * Rating in stars (int(1-5))
     * @ORM\Column(type="integer")
     * @Serializer\Expose()
     * @Groups({"FeedbackList"})
     */
    private $stars;

    /**
     * Feedback comment
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Expose()
     * @Groups({"FeedbackList"})
     */
    private $comment;

    /**
     * User who gave Feedback
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn()
     * @Serializer\Expose()
     * @Groups({"FeedbackList"})
     */
    private $userFrom;

    /**
     * User who received Feedback
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn()
     * @Serializer\Expose()
     * @Groups({"FeedbackList"})
     */
    private $userTo;

    /**
     * Activity on which Feedback is given
     * @ORM\ManyToOne(targetEntity="Activity")
     * @ORM\JoinColumn()
     * @Serializer\Expose()
     * @Groups({"FeedbackList"})
     */
    private $activity;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Expose()
     * @Serializer\Type("DateTime<'U'>")
     * @SWG\Property(example="15555555599")
     * @Groups({"FeedbackList"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Expose()
     * @Serializer\Type("DateTime<'U'>")
     * @SWG\Property(example="15555555599")
     * @Groups({"FeedbackList"})
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

    public function getStars(): ?int
    {
        return $this->stars;
    }

    public function setStars(int $stars): void
    {
        $this->stars = $stars;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    public function getUserFrom(): ?User
    {
        return $this->userFrom;
    }

    public function setUserFrom(?User $userFrom): void
    {
        $this->userFrom = $userFrom;
    }

    public function getUserTo(): ?User
    {
        return $this->userTo;
    }

    public function setUserTo(?User $userTo): void
    {
        $this->userTo = $userTo;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function setActivity(?Activity $activity): void
    {
        $this->activity = $activity;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps(): void
    {
        $this->setUpdatedAt(new DateTime());
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param mixed $updatedAt
     */
    public function setUpdatedAt($updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
