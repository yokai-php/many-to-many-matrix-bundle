<?php

declare(strict_types=1);

namespace Yokai\ManyToManyMatrixBundle\Tests\App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="user")
 */
class User
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", unique=true)
     */
    private string $email;

    /**
     * @var Collection<Role>
     * @ORM\ManyToMany(targetEntity=Role::class, mappedBy="users")
     */
    private Collection $roles;

    public function __construct(string $email)
    {
        $this->email = $email;
        $this->roles = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->email;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return Collection<Role>
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }
//
//    public function addRole(Role $role): void
//    {
//        if ($this->roles->contains($role)) {
//            return;
//        }
//
//        $this->roles->add($role);
//    }
//
//    public function removeRole(Role $role): void
//    {
//        if (!$this->roles->contains($role)) {
//            return;
//        }
//
//        $this->roles->removeElement($role);
//    }
}
