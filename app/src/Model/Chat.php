<?php

declare(strict_types=1);

namespace App\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping as ORM;

use App\Model\User;

#[Entity]
#[Table(name: "Chat")]
class Chat
{
    #[Id]
    #[Column(type: "string", length: 36)]
    private string $id;

    #[Column(type: "string")]
    private string $name;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: "chats")]
    private Collection $users;

    public function __construct(string $id) {
      $this->id = $id;
      $this->users = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
      if (!$this->users->contains($user)) {
        $this->users[] = $user;
      }

      return $this;
    }
}
