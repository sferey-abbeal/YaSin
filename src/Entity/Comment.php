<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\CommentRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Comment
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\Expose()
     * @Groups({"Comment"})
     * @SWG\Property()
     */
    private $id;

    /**
     * Activity id.
     * @ORM\ManyToOne(targetEntity="Activity")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Serializer\Expose()
     * @SWG\Property()
     */
    private $activity;

    /**
     * User id.
     * @ORM\ManyToOne(targetEntity="User", fetch="EAGER")
     * @Serializer\Expose()
     * @Groups({"Comment"})
     * @SWG\Property()
     */
    private $user;

    /**
     * Activity comment.
     * @ORM\Column(type="text")
     * @Serializer\Expose()
     * @Groups({"Comment"})
     * @SWG\Property()
     */
    private $comment;

    /**
     * Comment id linked to.
     * @ORM\ManyToOne(targetEntity="Comment")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Serializer\Expose()
     * @Serializer\MaxDepth(1)
     * @Groups({"Comment"})
     * @SWG\Property()
     */
    private $parent;

    /**
     * Comment status (true=deleted, false=available)
     * @ORM\Column(type="boolean")
     * @Serializer\Expose()
     * @Groups({"Comment"})
     * @SWG\Property()
     */
    private $deleted = false;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Expose()
     * @Serializer\Type("DateTime<'U'>")
     * @Groups({"Comment"})
     * @SWG\Property(example="15555555599")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Expose()
     * @Serializer\Type("DateTime<'U'>")
     * @Groups({"Comment"})
     * @SWG\Property(example="15555555599")
     */
    private $updatedAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function setActivity(?Activity $activity): self
    {
        $this->activity = $activity;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getParent(): ?Comment
    {
        return $this->parent;
    }

    public function setParent(?Comment $parent): self
    {
        $this->parent = $parent;

        return $this;
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

    public function getDeleted()
    {
        return $this->deleted;
    }

    public function setDeleted($deleted): void
    {
        $this->deleted = $deleted;
    }
}
