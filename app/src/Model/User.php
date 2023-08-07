<?php

declare(strict_types=1);

namespace App\Model;

use App\Model\Chat;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping as ORM;


#[Entity]
#[Table(name: "User")]
class User
{
    #[Id]
    #[Column(type: 'string', length: 36)]
    private string $id;

    #[Column(type: "string")]
    private string $name;

    #[Column(type: "string", unique: true)]
    private string $token;

    #[ORM\ManyToMany(targetEntity: Chat::class, inversedBy: "users")]
    #[ORM\JoinTable(name: "user_chat")]
    private Collection $chats;

    public function __construct(string $id) {
      $this->id = $id;
      $this->chats = new ArrayCollection();
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
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

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
        // $this->token = password_hash($token, PASSWORD_DEFAULT);
        return $this;
    }

    public function getChats(): Collection
    {
        return $this->chats;
    }

    public function addChat(Chat $chat): self {
      if (!$this->chats->contains($chat)) {
        $this->chats[] = $chat;
        $chat->addUser($this);
      }

      return $this;
    }
}
