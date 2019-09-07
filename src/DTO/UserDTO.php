<?php

namespace App\DTO;

use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Context\ExecutionContext;

/**
 * @Serializer\ExclusionPolicy("all")
 */
class UserDTO
{
    public const SENIORITY_JUNIOR = 0;
    public const SENIORITY_MIDDLE = 1;
    public const SENIORITY_SENIOR = 2;

    /**
     * User ID
     * @var integer
     * @Serializer\Type("integer")
     * @Serializer\Expose()
     * @SWG\Property()
     */
    public $id;

    /**
     * The username of User
     * @var string
     * @Serializer\Type("string")
     * @Assert\NotBlank(groups={"UserCreate"})
     * @Assert\Length(
     *      min = 4,
     *      max = 50,
     *      minMessage = "Your first name must be at least 4 characters long",
     *      maxMessage = "Your first name cannot be longer than 50 characters",
     *     groups={"UserCreate", "UserEdit"}
     * )
     * @Serializer\Expose()
     * @Groups({"UserCreate"})
     * @SWG\Property()
     */
    public $username;

    /**
     * Old Password
     * @var string
     * @Serializer\Type("string")
     * @Assert\NotBlank(groups={"PasswordEdit"})
     * @Assert\Regex("/^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])(?=\S*[-_!@#$%^&*])\S*$/",
     * message = "Password requirements(at least):length >8, 1 uppercase, 1 lowercase, 1 digit, 1 special",
     * groups={"PasswordEdit"}
     * )
     * @Serializer\Expose()
     * @Groups({"PasswordEdit"})
     * @SWG\Property()
     */
    public $oldPassword;

    /**
     * Password
     * @var string
     * @Serializer\Type("string")
     * @Serializer\Expose()
     * @Groups({"UserCreate", "PasswordEdit"})
     * @Assert\NotBlank(groups={"UserCreate", "PasswordEdit"})
     * @Assert\Regex("/^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])(?=\S*[-_!@#$%^&*])\S*$/",
     * message = "Password requirements(at least):length >8, 1 uppercase, 1 lowercase, 1 digit, 1 special",
     * groups={"UserCreate", "PasswordEdit"}
     * )
     * @SWG\Property()
     */
    public $password;


    /**
     * Confirm password (===password on register or password edit)
     * @var string
     * @Serializer\Type("string")
     * @Assert\NotBlank(groups={"UserCreate", "PasswordEdit"})
     * @Assert\EqualTo(propertyPath="password",
     *     message="Passwords do not match.",
     *     groups={"UserCreate", "PasswordEdit"}
     *     )
     * @Serializer\Expose()
     * @Groups({"UserCreate", "PasswordEdit"})
     * @SWG\Property()
     */
    public $confirmPassword;

    /**
     * User email
     * @var string
     * @Serializer\Type("string")
     * @Assert\NotBlank(groups={"UserCreate"})
     * @Assert\Email(
     *     message = "The email '{{ value }}' is not a valid email.",
     *     groups={"UserCreate"}
     * )
     * @Serializer\Expose()
     * @Groups({"UserCreate"})
     * @SWG\Property()
     */
    public $email;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\Expose()
     * @Groups({"UserEdit"})
     */
    public $position;

    /**
     * User seniority (JUNIOR, MIDDLE, SENIOR int(0-2) )
     * @var integer
     * @Serializer\Type("integer")
     * @Serializer\Expose()
     * @Groups({"UserEdit"})
     * @SWG\Property()
     */
    public $seniority = self::SENIORITY_JUNIOR;

    /**
     * User location (CHI, NYC, BOS, FRA, PAR, ORL, BUC, BRA, CLU, IAS, HAN, GUA, LYO)
     * @var string
     * @Serializer\Type("string")
     * @Serializer\Expose()
     * @Groups({"UserEdit"})
     * @SWG\Property()
     */
    public $location;

    /**
     * The name of User
     * @var string
     * @Serializer\Type("string")
     * @Assert\NotBlank(groups={"UserCreate", "UserEdit"})
     * @Serializer\Expose()
     * @Groups({"UserCreate", "UserEdit"})
     * @SWG\Property()
     */
    public $name;

    /**
     * The surname of User
     * @var string
     * @Serializer\Type("string")
     * @Assert\NotBlank(groups={"UserCreate", "UserEdit"})
     * @Serializer\Expose()
     * @Groups({"UserCreate", "UserEdit"})
     * @SWG\Property()
     */
    public $surname;

    /**
     * User biography
     * @var string
     * @Serializer\Type("string")
     * @Serializer\Expose()
     * @Groups({"UserEdit"})
     * @SWG\Property()
     */
    public $biography;

    /**
     * User Technologies (Technology Collection)
     * @var Collection|TechnologyDTO[]
     * @Serializer\Type("ArrayCollection<App\DTO\TechnologyDTO>")
     * @Serializer\Expose()
     * @Groups({"UserEdit"})
     * @SWG\Property()
     */
    public $technologies;

    /**
     * The avatar of User encoded in Base64
     * @var string
     * @Serializer\Type("string")
     * @Serializer\Expose()
     * @Groups({"UserEdit"})
     * @SWG\Property()
     */
    public $avatar;

    /**
     * User role
     * @var array
     * @Serializer\Type("array")
     * @Serializer\Expose()
     * @Groups({"UserRole"})
     * @SWG\Property()
     */
    public $roles;

    /**
     * @param ExecutionContext $context
     * @Assert\Callback(groups={"UserEdit"})
     */
    public function isLocationValid(ExecutionContext $context): void
    {
        if (!in_array($this->location, User::LOCATION, true)) {
            $context
                ->buildViolation('Please enter a valid location!')
                ->atPath('location')
                ->addViolation();
        }
    }

    /**
     * @param ExecutionContext $context
     * @Assert\Callback(groups={"UserRole"})
     */
    public function isRoleValid(ExecutionContext $context): void
    {
        foreach ($this->roles as $role) {
            if (!in_array($role, User::ROLES, true)) {
                $context
                    ->buildViolation('Please enter a valid ROLE!')
                    ->atPath('roles')
                    ->addViolation();
            }
        }
    }
}
