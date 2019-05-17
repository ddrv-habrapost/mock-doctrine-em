<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(
 *     name="users",
 *     indexes={
 *         @ORM\Index(name="users_is_approved_index", columns={"is_approved"}),
 *     },
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="users_login_unique", columns={"login"})
 *     }
 * )
 */
class User
{


    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(name="id", type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(name="login", type="string", length=32)
     * @var string
     */
    private $login;

    /**
     * @ORM\Column(name="email", type="string", length=180)
     * @var string
     */
    private $email;

    /**
     * @ORM\Column(name="is_approved", type="boolean", options={"default": false})
     * @var bool
     */
    private $isApproved;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="referrals")
     * @ORM\JoinColumn(name="referrer_id", referencedColumnName="id", nullable=true)
     * @var User
     */
    private $referrer;

    /**
     * @ORM\OneToMany(targetEntity="User", mappedBy="referrer")
     * @var User[]|ArrayCollection
     */
    private $referrals;

    public function __construct()
    {
        $this->referrals = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function isApproved(): bool
    {
        return $this->isApproved;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function setLogin(string $login): self
    {
        $this->login = $login;

        return $this;
    }

    public function setApproved(bool $isApproved): self
    {
        $this->isApproved = $isApproved;

        return $this;
    }

    public function setReferrer(User $user): self
    {
        $this->referrer = $user;

        return $this;
    }

    public function getReferrer(): ?User
    {
        return $this->referrer;
    }

}