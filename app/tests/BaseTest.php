<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Model\User;
use App\Model\Chat;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../env/');
$dotenv->load();

class BaseTest extends TestCase
{

  public function createUser(): User
  {
      // Generate a random user name
      $name = $this->faker->name;
      // Create a new user
      $response = $this->http->post('/api/v1/users', [
          'json' => [
              'name' => $name
          ]
      ]);

      $this->assertEquals(201, $response->getStatusCode());

      $userData = json_decode($response->getBody(), true);
      $this->assertArrayHasKey('id', $userData);
      $this->assertArrayHasKey('token', $userData);

      $user = new User($userData['id']);
      $user->setToken($userData['token']);
      $user->setName($userData['name']);
      return $user;
  }

  public function deleteUser(string $token, string $id): void
  {
      $response = $this->http->delete('/api/v1/users/' . $id, [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
          ]
      ]);

      $this->assertEquals(204, $response->getStatusCode());
  }

  public function createChat(string $token): Chat
  {
      $name = $this->faker->name;
      $response = $this->http->post('/api/v1/chats', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
        ],
        'json' => [
          'name' => $name
        ]
      ]);
      $this->assertEquals(201, $response->getStatusCode());

      $chatData = json_decode($response->getBody(), true);
      $this->assertArrayHasKey('id', $chatData);
      $this->assertArrayHasKey('name', $chatData);

      $chat = new Chat($chatData['id']);
      $chat->setName($chatData['name']);
      return $chat;
  }

  public function deleteChat(string $token, string $chatId): void
  {
      $response = $this->http->delete('/api/v1/chats/' . $chatId, [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
          ]
      ]);

      $this->assertEquals(204, $response->getStatusCode());
  }

  public function test0sample(): void {
    $this->assertEquals(1, 1);
  }

}