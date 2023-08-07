<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use App\Model\User;
use Doctrine\ORM\EntityManager;

class UserRepository extends BaseRepository {

  /**
   * Finds a User by its id
   * @param string $id User id
   * 
   * @return User an instance of User model. 
   */
  public function getUser(string $id): ?User
  {
      return $this->getEntityManager()->find(User::class, $id);
  }

  public function getAllUsers()
  {
      return $this->getEntityManager()->getRepository(User::class)->findAll();
  }

  /**
   * Persists a new User in the database
   * @param User $user the new User instance
   */
  public function createUser(User $user)
  {
      $entityManager = $this->getEntityManager();
      $entityManager->persist($user);
      $entityManager->flush();
  }

  public function findByToken(string $token): ?User {
    return $this->getEntityManager()->getRepository(User::class)->findOneBy(['token' => $token]);
  }

  /**
   * merges the updated version of User instance in the database
   * @param User $user the updated Chat instance
   */
  public function updateUser(User $user) {
    $entityManager = $this->getEntityManager();
    $entityManager->merge($user);
    $entityManager->flush();
  }

  /**
   * Removes a User from the database
   * @param User $user the new User instance
   * 
   * @return void
   */
  public function deleteUser(User $user): void
  {
      $this->getEntityManager()->remove($user);
      $this->getEntityManager()->flush();
  }
}