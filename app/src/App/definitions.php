<?php

declare(strict_types=1);

use App\Repository\UserRepository;
use App\Repository\ChatRepository;
use App\Repository\MessageRepository;
use App\Controller\UserController;
use App\Controller\ChatController;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

return [
  PDO::class => function () {
      $dsn = 'sqlite:' . $_ENV['DB_PATH'];
      $pdo = new PDO($dsn);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
      return $pdo;
  },
  EntityManager::class => function () {
    $isDevMode = true;
    // $config = Setup::createAnnotationMetadataConfiguration([__DIR__ . "/../Model"], $isDevMode);
    $config = ORMSetup::createAttributeMetadataConfiguration(
      paths: array(__DIR__. "/../Model"),
      isDevMode: true,
    );
    $conn = DriverManager::getConnection([
      'driver' => 'pdo_sqlite',
      'path' => $_ENV['DB_PATH']
    ], $config);
    return EntityManager::create($conn, $config);
  },
  Serializer::class => function () {
    $normalizers = [new ObjectNormalizer()];
    $encoders = [new JsonEncoder()];

    return new Serializer($normalizers, $encoders);
  },
  UserRepository::class => function (EntityManager $entityManager) {
      return new UserRepository($entityManager);
  },
  ChatRepository::class => function (EntityManager $entityManager) {
    return new ChatRepository($entityManager);
  },
  MessageRepository::class => function (EntityManager $entityManager) {
    return new MessageRepository($entityManager);
  },
  UserController::class => function (UserRepository $userRepository, Serializer $serializer) {
    return new UserController($userRepository, $serializer);
  },
  ChatController::class => function (ChatRepository $chatRepository, UserRepository $userRepository, MessageRepository $messageRepository, Serializer $serializer) {
    return new ChatController($chatRepository, $userRepository, $messageRepository, $serializer);
  }
];