<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use App\Model\Message;

class MessageRepository extends BaseRepository {

  /**
   * Persists a new Message in the database
   * @param Message $Message the new Message instance
   * 
   * @return Message $user the new Message instance
   */
  public function createMessage(Message $message): Message
  {
      $entityManager = $this->getEntityManager();
      $entityManager->persist($message);
      $entityManager->flush();
      return $message;
  }
}