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

  /**
   * Constructs a new instance of the class, injecting its dependencies.
   * @param ChatRepository $repository Instance of ChatRepository.
   * This object is used to interact with the database Chat table.
   * @param UserRepository $repository Instance of UserRepository.
   * This object is used to interact with the database User table.
   * @param MessageRepository $repository Instance of MessageRepository.
   * This object is used to interact with the database Message table.
   * @param Serializer $serializer.
   * 
   * @return void
   */
  public function __construct(ChatRepository $chatRepository, UserRepository $userRepository, MessageRepository $messageRepository, Serializer $serializer)
  {
    parent::__construct($chatRepository, $serializer);
    $this->userRepository = $userRepository;
    $this->messageRepository = $messageRepository;
  }

  /**
   * getter for respository
   * @return UserRepository
   */
  private function getUserRepository(): UserRepository {
    return $this->userRepository;
  }

  /**
   * getter for respository
   * @return MessageRepository
   */
  private function getMessageRepository(): MessageRepository {
    return $this->messageRepository;
  }

  /**
   * This method handles the GET request for a chat identified by its id.
   * If the chat is not found, it returns a 404 status.
   * If the chat is found, it returns a JSON response.
   *
   * @param Request $request The request object.
   * @param Response $response The response object.
   * @param string $id the chat id.
   *
   * @return Response The response object containing the chat information in JSON format or a NOT FOUND message.
   */
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

  /**
   * This method handles the GET request for all chats in database.
   * If no chats are found, it returns a 404 status.
   * If chats are found, it returns a JSON response.
   *
   * @param Request $request The request object.
   * @param Response $response The response object.
   *
   * @return Response The response object containing an array of chats in JSON format or a NOT FOUND message.
   */
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

  /**
   * This method handles the POST request to create a new chat.
   * It generates a unique id and creates a new Chat instance with that id.
   * It uses the chat repository to persist the chat.
   * The user is then updated in the repository with the added chat.
   * The response is returned as a JSON with a status code of 201 (Created).
   *
   * @param Request $request The request object.
   * @param Response $response The response object.
   *
   * @return Response The response object containing the chatId and name in JSON.
   */
  public function createChat(Request $request, Response $response): Response
  {
    $user = $request->getAttribute('user');
    $data = $request->getParsedBody();
    $name = $data['name'];
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

  /**
   * This method handles the GET request to get chats of a user.
   * If no userId is not in path, it returns 400 (Bad Request).
   * It uses the chat repository to find chats by userId.
   * The response is returned as a JSON.
   *
   * @param Request $request The request object.
   * @param Response $response The response object.
   * @param string $id The user id.
   * 
   * @return Response The response object containing the chats array in JSON or a status code of 400 for a Bad Request.
   */
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

  /**
   * This method handles the GET request to get messages of a chat.
   * It fetches the user using the route attribute.
   * It fetches the chat from the chatId provided in the route path.
   * If no user or chat is found, it returns 404 (Not Found).
   * If both are found, it checks if user belongs to chat, if user does not it returns 403 (Forbidden) 
   * If user belogns to chat, it fetches the messages.
   * Additionally it uses a query param 'lastMessageId' to get messages after this id.
   * The response is returned as a JSON.
   *
   * @param Request $request The request object.
   * @param Response $response The response object.
   * @param string $id The chat id.
   * 
   * @return Response The response object containing the messages array in JSON or a status code of 404 or 403.
   */
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

  /**
   * This method handles the POST request to create a messages in a chat.
   * It fetches the user using the userId provided in the route path.
   * It fetches the chat using the id provided in the route path.
   * If no user or chat is found, it returns 404 (Not Found).
   * If both are found, it checks if user belongs to chat, if user does not it returns 403 (Forbidden) 
   * If user belogns to chat, it creates a message using the message repository, where the message is persisted.
   * The response is returned as a JSON containing the message.
   *
   * @param Request $request The request object.
   * @param Response $response The response object.
   * @param string $id The chat id.
   * @param string $userId The user id.
   * 
   * @return Response The response object containing the new message created in JSON or a status code of 404 or 403.
   */
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

  /**
   * This method handles the POST request to add a user to a chat.
   * It fetches the user using the route attribute.
   * It fetches the chat using the id provided in the route path.
   * If no user or chat is found, it returns 404 (Not Found).
   * If both are found, it uses the user repository to add the chat to the user.
   * It uses both chat and user repository to update the user and chat data.
   * The response is returned as a JSON with status 201.
   *
   * @param Request $request The request object.
   * @param Response $response The response object.
   * @param string $id The chat id.
   * @param string $userId The user id.
   * 
   * @return Response The response object containing the chatId and userId or a status code of 404.
   */
  public function addUserToChat(Request $request, Response $response, string $id): Response
  {
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

  /**
   * This method handles the GET request to get users belonging to a chat
   * It fetches the chat using the id provided in the route path.
   * It fetches the user using the route attribute.
   * If no user or chat is found, it returns 404 (Not Found).
   * If both are found, it checks if user belongs to chat, if user does not it returns 403 (Forbidden) 
   * If user belogns to chat, it fetches the list of users.
   * The response is returned as a JSON.
   *
   * @param Request $request The request object.
   * @param Response $response The response object.
   * @param string $id The chat id.
   * 
   * @return Response The response object containing the list of users in JSON or a status code of 404 or 403.
   */
  public function getUsersInChat(Request $request, Response $response, string $id): Response
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

  /**
   * This method handles the DELETE request to delete chat by id.
   * It fetches the chat using the id provided in the route path.
   * It fetches the user using the route attribute.
   * If no user or chat is found, it returns 404 (Not Found).
   * If both are found, it checks if user belongs to chat, if user does not it returns 403 (Forbidden) 
   * If user belogns to chat, it uses the chat repository to delete the chat.
   * The response is returned as a JSON with status code 204 (No Content)
   *
   * @param Request $request The request object.
   * @param Response $response The response object.
   * @param string $id The chat id.
   * 
   * @return Response The response object with a status code of 204 (sucess, no content) or 404 or 403.
   */
  public function deleteChat(Request $request, Response $response, string $id): Response
  {
      $chat = $this->getRepository()->getChat($id);
      $user = $request->getAttribute('user');

      if (!$user || !$chat) {
        $response->getBody()->write('NOT FOUND');
        return $response->withStatus(404);
      }

      // Check if the user is member of chat
      if (!$this->getRepository()->isUserInChat($user, $chat)) {
        $response->getBody()->write('Forbidden');
        return $response->withStatus(403);
      }

      $this->getRepository()->deleteChat($chat);
      return $response->withStatus(204);
  }
}
