<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use App\Model\Message;

class MessageRepository extends BaseRepository {

  public function createMessage(Message $message): Message
  {
      $entityManager = $this->getEntityManager();
      $entityManager->persist($message);
      $entityManager->flush();
      return $message;
  }
}