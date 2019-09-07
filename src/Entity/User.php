<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Security\Core\User\UserInterface;
use Swagger\Annotations as SWG;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class User implements UserInterface
{
    public const SENIORITY_JUNIOR = 0;
    public const SENIORITY_MIDDLE = 1;
    public const SENIORITY_SENIOR = 2;
    public const LOCATION_CHISINAU = 'CHI';
    public const LOCATION_NEW_YORK = 'NYC';
    public const LOCATION_BOSTON = 'BOS';
    public const LOCATION_FRANKFURT = 'FRA';
    public const LOCATION_PARIS = 'PAR';
    public const LOCATION_ORLEANS = 'ORL';
    public const LOCATION_BUCHAREST = 'BUC';
    public const LOCATION_BRASOV = 'BRA';
    public const LOCATION_CLUJ = 'CLU';
    public const LOCATION_IASI = 'IAS';
    public const LOCATION_HANOI = 'HAN';
    public const LOCATION_GUADALAJARA = 'GUA';
    public const LOCATION_LYON = 'LYO';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_PM = 'ROLE_PM';
    public const ROLE_USER = 'ROLE_USER';

    public const ROLES = [
        self::ROLE_USER,
        self::ROLE_PM,
        self::ROLE_ADMIN
    ];

    public const LOCATION = [
        self::LOCATION_CHISINAU,
        self::LOCATION_NEW_YORK,
        self::LOCATION_BOSTON,
        self::LOCATION_FRANKFURT,
        self::LOCATION_PARIS,
        self::LOCATION_ORLEANS,
        self::LOCATION_BUCHAREST,
        self::LOCATION_BRASOV,
        self::LOCATION_CLUJ,
        self::LOCATION_IASI,
        self::LOCATION_HANOI,
        self::LOCATION_GUADALAJARA,
        self::LOCATION_LYON
    ];
    /**
     * User ID
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\Expose()
     * @Groups(
     *     {
     *         "UserDetail",
     *         "ActivityCreate",
     *         "ActivityEdit",
     *         "ActivityDetails",
     *         "UserList",
     *         "ActivityList",
     *         "Comment",
     *         "FeedbackList",
     *         "ActivityUser",
     *         "SetPM"
     *     }
     *)
     * @SWG\Property()
     */
    protected $id;

    /**
     * The username of User
     * @ORM\Column(type="string", unique=true)
     * @Serializer\Expose()
     * @Groups({"UserDetail", "UserList", "ActivityList", "FeedbackList", "ActivityUser"})
     * @SWG\Property()
     */
    private $username;

    /**
     * Password of User
     * @ORM\Column(type="string")
     * @SWG\Property()
     */
    private $password;

    /**
     * User email
     * @ORM\Column(type="string", unique=true)
     * @Serializer\Expose()
     * @Groups({"UserDetail", "UserList", "ActivityUser"})
     * @SWG\Property()
     */
    protected $email;

    /**
     * User position
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Expose()
     * @Groups({"UserDetail", "UserList", "ActivityUser"})
     * @SWG\Property()
     */
    private $position;

    /**
     * User seniority (JUNIOR, MIDDLE, SENIOR int(0-2) )
     * @ORM\Column(type="integer", nullable=true)
     * @Serializer\Expose()
     * @Groups({"UserDetail", "UserList", "ActivityUser"})
     * @SWG\Property()
     */
    private $seniority = self::SENIORITY_JUNIOR;

    /**
     * User location (CHI, NYC, BOS, FRA, PAR, ORL, BUC, BRA, CLU, IAS, HAN, GUA, LYO)
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Expose()
     * @Groups({"UserDetail", "UserList", "ActivityUser"})
     * @SWG\Property()
     */
    private $location;

    /**
     * The name of User
     * @ORM\Column(type="string")
     * @Serializer\Expose()
     * @Groups({"UserDetail", "ActivityUser", "UserList", "ActivityDetails", "FeedbackList", "ActivityList", "Comment"})
     * @SWG\Property()
     */
    private $name;

    /**
     * The surname of User
     * @ORM\Column(type="string")
     * @Serializer\Expose()
     * @Groups({"UserDetail", "ActivityUser", "UserList", "ActivityDetails", "FeedbackList", "ActivityList", "Comment"})
     * @SWG\Property()
     */
    private $surname;

    /**
     * User biography
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Expose()
     * @Groups({"UserDetail"})
     * @SWG\Property()
     */
    private $biography;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Expose()
     * @Serializer\Type("DateTime<'U'>")
     * @Groups({"UserDetail"})
     * @SWG\Property(example="15555555599")
     */

    private $createdAt;

    /**
     * Project Manager id.
     * @ORM\ManyToOne(targetEntity="User")
     * @Serializer\Expose()
     */
    private $projectManager;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Expose()
     * @Serializer\Type("DateTime<'U'>")
     * @Groups({"UserDetail"})
     * @SWG\Property(example="15555555599")
     */
    private $updatedAt;

    /**
     * User Technologies (Technology Collection)
     * @var Collection|Technology[]
     * @ORM\ManyToMany(targetEntity="Technology")
     * @Serializer\Expose()
     * @Groups({"UserDetail", "UserList", "ActivityUser"})
     * @SWG\Property()
     */
    protected $technologies;

    /**
     * User type in Activity(invited = 0, applied = 1, assigned = 2, declined = 3, rejected = 4)
     * @var Collection|ActivityUser[]
     * @ORM\OneToMany(targetEntity="ActivityUser", mappedBy="user")
     * @Serializer\Expose()
     * @Groups({"ActivityUser"})
     * @SWG\Property()
     */
    private $activityUsers;

    /**
     * @ORM\Column(type="datetime")
     */
    private $passwordChangedAt;

    /**
     * @var Image
     * @ORM\OneToOne(targetEntity="Image")
     * @ORM\JoinColumn()
     * @Serializer\Expose()
     * @Groups
     * (
     *     {
     *      "UserDetail",
     *      "ActivityUser",
     *      "UserList",
     *      "ActivityList",
     *      "ActivityDetails",
     *      "Comment",
     *      "FeedbackList"
     *     }
     * )
     * @SWG\Property(ref="#/definitions/UserAvatar")
     */
    private $avatar;

    /**
     * User rating in stars (1-5)
     * @var float
     * @ORM\Column(type="float")
     * @Serializer\Expose()
     * @Groups({"UserDetail", "UserList", "ActivityUser"})
     * @SWG\Property()
     */
    private $stars;

    /**
     * The role of User
     * @ORM\Column(type="json")
     */
    private $roles;

    public function __construct()
    {
        $this->technologies = new ArrayCollection();
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->passwordChangedAt = new DateTime();
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

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
        $this->passwordChangedAt = new DateTime();
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(string $position): void
    {
        $this->position = $position;
    }

    public function getSeniority(): ?string
    {
        return $this->seniority;
    }

    public function setSeniority(string $seniority): void
    {
        $this->seniority = $seniority;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): void
    {
        $this->surname = $surname;
    }

    public function getBiography(): ?string
    {
        return $this->biography;
    }

    public function setBiography(string $biography): void
    {
        $this->biography = $biography;
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

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function getSalt()
    {
    }

    public function eraseCredentials(): void
    {
    }

    public function getPasswordChangedAt(): ?DateTimeInterface
    {
        return $this->passwordChangedAt;
    }

    public function setPasswordChangedAt(DateTimeInterface $passwordChangedAt): void
    {
        $this->passwordChangedAt = $passwordChangedAt;
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
     * @return Image
     */
    public function getAvatar(): ?Image
    {
        return $this->avatar;
    }

    public function setAvatar(?Image $avatar): void
    {
        $this->avatar = $avatar;
    }

    /**
     * @return float
     */
    public function getStars(): float
    {
        return $this->stars;
    }

    /**
     * @param float $stars
     */
    public function setStars(float $stars): void
    {
        $this->stars = $stars;
    }

    public function getProjectManager(): ?self
    {
        return $this->projectManager;
    }

    public function setProjectManager(?User $projectManager): void
    {
        $this->projectManager = $projectManager;
    }
}
