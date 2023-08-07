<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use App\Model\User;
use Doctrine\ORM\EntityManager;

class UserRepository extends BaseRepository {

  public function getUser(string $id): ?User
  {
      return $this->getEntityManager()->find(User::class, $id);
  }

  public function getAllUsers()
  {
      return $this->getEntityManager()->getRepository(User::class)->findAll();
  }

  public function createUser(User $user)
  {
      $entityManager = $this->getEntityManager();
      $entityManager->persist($user);
      $entityManager->flush();
  }

  public function findByToken(string $token): ?User {
    return $this->getEntityManager()->getRepository(User::class)->findOneBy(['token' => $token]);
  }

  public function updateUser(User $user) {
    $entityManager = $this->getEntityManager();
    $entityManager->merge($user);
    $entityManager->flush();
  }

  public function deleteUser(User $user): void
  {
      $this->getEntityManager()->remove($user);
      $this->getEntityManager()->flush();
  }

}