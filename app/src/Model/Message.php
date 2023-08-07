<?php

declare(strict_types=1);

namespace App\Model;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "Message")]
class Message
{
    #[Id]
    #[GeneratedValue(strategy: "AUTO")]
    #[Column(type: "integer")]
    private int $id;

    #[Column(type: "text")]
    private string $content;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id")]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Chat::class)]
    #[ORM\JoinColumn(name: "chat_id", referencedColumnName: "id")]
    private Chat $chat;

    public function __construct(string $content, User $user, Chat $chat)
    {
        $this->content = $content;
        $this->user = $user;
        $this->chat = $chat;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getChat(): Chat
    {
        return $this->chat;
    }
}
