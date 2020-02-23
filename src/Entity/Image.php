<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use http\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ImageRepository")
 */
class Image
{
    public const IMAGE_TYPE_USER = 'user';
    public const IMAGE_TYPE_ACTIVITY = 'activity';
    public const VALID_IMAGE_TYPES = [
        self::IMAGE_TYPE_USER,
        self::IMAGE_TYPE_ACTIVITY
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank(message="Please upload your image!")
     */
    private $file;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $alt;

    /**
     * Specifying the link of the image with another entity (e.g. user, activity)
     * @ORM\Column(type="string")
     */
    private $linkedTo;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(string $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getAlt(): ?string
    {
        return $this->alt;
    }

    public function setAlt(?string $alt): self
    {
        $this->alt = $alt;

        return $this;
    }

    /**
     * @return string
     */
    public function getLinkedTo(): string
    {
        return $this->linkedTo;
    }

    /**
     * @param string $linkedTo
     */
    public function setLinkedTo(string $linkedTo): void
    {
        if (!in_array($linkedTo, self::VALID_IMAGE_TYPES, true)) {
            throw new InvalidArgumentException('Not valid image type');
        }

        $this->linkedTo = $linkedTo;
    }
}
