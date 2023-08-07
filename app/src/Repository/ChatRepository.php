<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use App\Model\Chat;
use App\Model\User;
use App\Model\Message;
use Doctrine\ORM\EntityManager;

class ChatRepository extends BaseRepository {

  /**
   * Finds a chat by its id
   * @param string $id chat id
   * 
   * @return Chat an instance of Chat model. 
   */
  public function getChat(string $id): ?Chat
  {
      return $this->getEntityManager()->find(Chat::class, $id);
  }

  /**
   * Finds all chats in database
   * 
   * @return array of Chat.
   */
  public function getAllChats()
  {
      return $this->getEntityManager()->getRepository(Chat::class)->findAll();
  }

  /**
   * Persists a new Chat in the database
   * @param Chat $chat the new Chat instance
   * 
   * @return Chat $user the new Chat instance
   */
  public function createChat(Chat $chat): Chat
  {
      $entityManager = $this->getEntityManager();
      $entityManager->persist($chat);
      $entityManager->flush();
      return $chat;
  }

  /**
   * merges the updated version of Chat instance in the database
   * @param Chat $chat the updated Chat instance
   */
  public function updateChat(Chat $chat) {
    $entityManager = $this->getEntityManager();
    $entityManager->merge($chat);
    $entityManager->flush();
  }

  /**
   * Finds chats belonging to a user.
   * Uses queryBuilder to add params.
   * @param User $user the User instance
   * 
   * @return array the list of Chats belonging to user
   */
  public function findChatsByUser(User $user): array
  {
      $qb = $this->getEntityManager()->createQueryBuilder();
      $qb->select('c')
        ->from(Chat::class, 'c')
        ->join('c.users', 'u')
        ->where('u.id = :userId')
        ->setParameter('userId', $user->getId());

      return $qb->getQuery()->getResult();
  }

  /**
   * Finds messages of a chat
   * @param string $id of chat
   * @param int $id lastMessageId
   * 
   * @return array array of messages
   */
  public function getMessagesByChatId(string $id, int $lastMessageId = 0): array
  {
      $qb = $this->getEntityManager()->createQueryBuilder();
      $qb->select('m.id, m.content')
        ->from(Message::class, 'm')
        ->join('m.chat', 'c')
        ->where('c.id = :chatId')
        ->setParameter('chatId', $id)
        ->andWhere('m.id > :lastMessageId')
        ->setParameter('lastMessageId', $lastMessageId);
      return $qb->getQuery()->getResult();
  }

  /**
   * Checks if user belongs to chat using the user_chat table
   * @param User $user the User instance
   * @param Chat $chat the Chat instance
   * 
   * @return bool true if user belongs else false.
   */
  public function isUserInChat(User $user, Chat $chat): bool
  {
      $sql = "SELECT 1 FROM user_chat uc WHERE uc.user_id = :userId AND uc.chat_id = :chatId LIMIT 1";
  
      $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
      $stmt->bindValue("userId", $user->getId());
      $stmt->bindValue("chatId", $chat->getId());
  
      $result = $stmt->executeQuery();
      $fetchResult = $result->fetchOne();
      return $fetchResult ? true : false;
  }

  /**
   * Finds users belonging to a chat
   * @param Chat the Chat instance
   * 
   * @return array list of users beloning to chat.
   */
  public function getUsers(Chat $chat): array
  {
    $sql = "SELECT u.* FROM user u INNER JOIN user_chat uc ON u.id = uc.user_id WHERE uc.chat_id = :chatId";

    $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
    $stmt->execute(['chatId' => $chat->getId()]);

    $result = $stmt->executeQuery();
    return $result->fetchAll();
  }

  /**
   * Removes a Chat from the database
   * @param Chat $chat the new Chat instance
   * 
   * @return void
   */
  public function deleteChat(Chat $chat): void
  {
      $this->getEntityManager()->remove($chat);
      $this->getEntityManager()->flush();
  }
}