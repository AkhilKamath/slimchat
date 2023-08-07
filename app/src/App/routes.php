<?php

declare(strict_types=1);

use Slim\Routing\RouteCollectorProxy;
use App\Controller\UserController;
use App\Controller\ChatController;

return function (RouteCollectorProxy $group) {
  // Versioning API
  $group->group('/api/v1', function (RouteCollectorProxy $group) {
      // User routes
      $group->group('/users', function (RouteCollectorProxy $group) {
          $group->get('/{userId}', UserController::class . ':getUser');
          $group->get('', UserController::class . ':getAllUsers');
          $group->post('', UserController::class . ':createUser')->setName('createUser');
          $group->delete('/{userId}', UserController::class . ':deleteUser');
      });
      // Chat routes
      $group->group('/chats', function (RouteCollectorProxy $group) {
        $group->get('/user/{userId}', ChatController::class . ':getChatsOfUser');
        $group->get('/{id}/users', ChatController::class . ':getUsersInChat')->setName('getUsersInChat');
        $group->post('/{id}/users', ChatController::class . ':addUserToChat')->setName('addUserToChat');
        $group->get('/{id}/messages', ChatController::class . ':getMessages')->setName('getMessages');
        $group->post('/{id}/{userId}/messages', ChatController::class . ':createMessage')->setName('createMessage');
        $group->get('/{id}', ChatController::class . ':getChat');
        $group->delete('/{id}', ChatController::class . ':deleteChat')->setName('deleteChat');
        $group->get('', ChatController::class . ':getAllChats');
        $group->post('', ChatController::class . ':createChat')->setName('createChat');
    });
  });
};
