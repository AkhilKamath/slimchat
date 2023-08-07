<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use Doctrine\ORM\EntityManager;

abstract class BaseRepository {
  // protected $pdo;
  // public function __construct(PDO $pdo) {
  //   $this->pdo = $pdo;
  // }
  private EntityManager $entityManager;

  public function __construct(EntityManager $entityManager) {
    if ($entityManager === null) {
      error_log('EntityManager is null');
    } else {
        error_log('EntityManager is set');
    }

    $this->entityManager = $entityManager;
  }

  protected function getEntityManager(): EntityManager {
    return $this->entityManager;
  }

  // pagination code goes here
}