<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\Serializer\Serializer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use App\Model\Chat;
use App\Model\Message;
use App\Repository\ChatRepository;
use App\Repository\UserRepository;
use App\Repository\MessageRepository;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class ChatController extends BaseController
{

  private UserRepository $userRepository;
  private MessageRepository $messageRepository;

  public function __construct(ChatRepository $chatRepository, UserRepository $userRepository, MessageRepository $messageRepository, Serializer $serializer)
  {
    parent::__construct($chatRepository, $serializer);
    $this->userRepository = $userRepository;
    $this->messageRepository = $messageRepository;
  }

  private function getUserRepository(): UserRepository {
    return $this->userRepository;
  }

  private function getMessageRepository(): MessageRepository {
    return $this->messageRepository;
  }


  public function getChat(Request $request, Response $response, string $id): Response
  {
    $chat = $this->getRepository()->getChat($id);
    if(!$chat) {
      $response->getBody()->write('NOT FOUND');
      return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
    $json = $this->getSerializer()->serialize($chat, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['users']]);
    $response->getBody()->write($json);
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function getAllChats(Request $request, Response $response): Response
  {
    $chats = $this->getRepository()->getAllChats();
    if(!$chats) {
      $response->getBody()->write('NOT FOUND');
      return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
    $json = $this->getSerializer()->serialize($chats, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['users']]);
    $response->getBody()->write($json);
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function createChat(Request $request, Response $response): Response
  {
    $user = $request->getAttribute('user');
    $data = $request->getParsedBody();
    $name = $data['name'] ?? null;
    if (!$name) {
        return $response->withStatus(400); // Bad Request
    }
    $id = uniqid();
    $chat = new Chat($id);
    $chat->setName($name);
    $user->addChat($chat);
    $chat = $this->getRepository()->createChat($chat);
    $this->getUserRepository()->updateUser($user);

    
    $response->getBody()->write(json_encode(
      [
        'id' => $chat->getId(), 
        'name' => $chat->getName()
      ]
    ));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
  }

  public function getChatsOfUser(Request $request, Response $response, string $id): Response
  {
      $user = $this->getUserRepository()->getUser($id);
  
      if (!$user) {
          return $response->withStatus(400);
      }
  
      $chats = $this->getRepository()->findChatsByUser($user);
      $chatData = [];
      foreach ($chats as $chat) {
          $chatData[] = [
              'id' => $chat->getId(),
              'name' => $chat->getName(),
          ];
      }
  
      $response->getBody()->write(json_encode($chatData));
      return $response->withHeader('Content-Type', 'application/json');
  }

  public function getMessages(Request $request, Response $response, string $id): Response
  {
      $user = $request->getAttribute('user');
      $chat = $this->getRepository()->getChat($id);

      if (!$user || !$chat) {
          return $response->withStatus(404);
      }

      // Check if the user is member of chat
      if (!$this->getRepository()->isUserInChat($user, $chat)) {
        $response->getBody()->write('Forbidden');
        return $response->withStatus(403); // Forbidden
      }

      $queryParams = $request->getQueryParams();
      $lastMessageId = $queryParams['lastMessageId'] ?? 0;
      $lastMessageId = (int) $lastMessageId;
      $messages = $this->getRepository()->getMessagesByChatId($id, $lastMessageId);
      $data = [
          'id' => $id,
          'messages' => $messages
      ];

      $response->getBody()->write(json_encode($data));
      return $response->withHeader('Content-Type', 'application/json');
  }

  public function createMessage(Request $request, Response $response, string $id, string $userId): Response
  {
      $data = $request->getParsedBody();
      $content = $data['content'] ?? null;
      
      if (!$content) {
          return $response->withStatus(400); // Bad Request
      }

      $chat = $this->getRepository()->getChat($id);
      $user = $this->getUserRepository()->getUser($userId);

      if (!$user || !$chat) {
          $response->getBody()->write('NOT FOUND');
          return $response->withStatus(404); // Not Found
      }

      // Check if the user is member of chat
      if (!$this->getRepository()->isUserInChat($user, $chat)) {
        $response->getBody()->write('Forbidden');
        return $response->withStatus(403); // Forbidden
      }

      $message = new Message($content, $user, $chat);

      $this->getMessageRepository()->createMessage($message);
      $json = $this->getSerializer()->serialize($message, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['user', 'chat']]);
      $response->getBody()->write($json);
      return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
  }

  public function addUserToChat(Request $request, Response $response, string $id): Response {
    $chat = $this->getRepository()->getChat($id);
    $user = $request->getAttribute('user');

    if (!$user || !$chat) {
      $response->getBody()->write('NOT FOUND');
      return $response->withStatus(404); // Not Found
    }
    
    $user->addChat($chat);
    $this->getRepository()->updateChat($chat);
    $this->getUserRepository()->updateUser($user);
    
    $response->getBody()->write(json_encode(
      [
        'chatId' => $chat->getId(), 
        'userId' => $user->getId() 
      ]
    ));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
  }

  public function getUsersInChat(Request $request, Response $response, string $id): Response {
    $chat = $this->getRepository()->getChat($id);
    $user = $request->getAttribute('user');

    if (!$user || !$chat) {
      $response->getBody()->write('NOT FOUND');
      return $response->withStatus(404); // Not Found
    }


    // Check if the user is member of chat
    if (!$this->getRepository()->isUserInChat($user, $chat)) {
      $response->getBody()->write('Forbidden');
      return $response->withStatus(403); // Forbidden
    }

    $users = $this->getRepository()->getUsers($chat);
    $cleanUsers = array_map(function($user) {
      unset($user['chats']);
      unset($user['token']);
      return $user;
    }, $users);
    $json = $this->getSerializer()->serialize($cleanUsers, 'json');
    $response->getBody()->write($json);
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function deleteChat(Request $request, Response $response, string $id): Response
  {
      $chat = $this->getRepository()->getChat($id);
      $user = $request->getAttribute('user');

      if (!$user || !$chat) {
        $response->getBody()->write('NOT FOUND');
        return $response->withStatus(404); // Not Found
      }


      // Check if the user is member of chat
      if (!$this->getRepository()->isUserInChat($user, $chat)) {
        $response->getBody()->write('Forbidden');
        return $response->withStatus(403); // Forbidden
      }

      $this->getRepository()->deleteChat($chat);
      return $response->withStatus(204); // No Content
  }


}
