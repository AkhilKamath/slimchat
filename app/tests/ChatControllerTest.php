<?php

// tests/Controller/ChatControllerTest.php

namespace Tests;

use GuzzleHttp\Client;
use Faker\Factory;
use App\Model\User;
use Tests\BaseTest;

class ChatControllerTest extends BaseTest
{
    protected function setUp(): void
    {
        $this->http = new Client(['base_uri' => $_ENV['BASE_URI']]);
        $this->faker = Factory::create();
    }

    public function test1GetAllChats()
    {
        $user = $this->createUser();
        $token = $user->getToken();
        $userId = $user->getId();
        $chat = $this->createChat($token); 
        $chatId = $chat->getId();

        $response = $this->http->get('/api/v1/chats', [
          'headers' => [
              'Authorization' => 'Bearer ' . $token,
          ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $fetchedChatsData = json_decode($response->getBody(), true);
        $this->assertGreaterThanOrEqual(1, count($fetchedChatsData));
        $fetchedUserIds = array_column($fetchedChatsData, 'id');
        $this->assertContains($chatId, $fetchedUserIds);

        $this->deleteChat($token, $chatId);
        $this->deleteUser($token, $userId);
    }

    public function test3CreateMessage()
    {
      $user = $this->createUser();
      $token = $user->getToken();
      $userId = $user->getId();
      $chat = $this->createChat($token);
      $chatId = $chat->getId();

      $response = $this->http->post('/api/v1/chats/' . $chatId . '/' . $userId . '/messages', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
        ],
        'json' => [
          'content' => 'this is a new message'
        ]
      ]);

      $messageData = json_decode($response->getBody(), true);
      $this->assertArrayHasKey('id', $messageData);
      $this->assertArrayHasKey('content', $messageData);
      $this->assertEquals('this is a new message', $messageData['content']);

      $this->deleteChat($token, $chatId);
      $this->deleteUser($token, $userId);
    }

    public function test4AddNewUserToChat()
    {
      $user = $this->createUser();
      $token = $user->getToken();
      $userId = $user->getId();
      $chat = $this->createChat($token);
      $chatId = $chat->getId();
      $newUser = $this->createUser();
      $newToken = $newUser->getToken();
      $newUserId = $newUser->getId();

      $response = $this->http->post('/api/v1/chats/' . $chatId . '/users', [
        'headers' => [
            'Authorization' => 'Bearer ' . $newToken,
        ],
      ]);

      $responseData = json_decode($response->getBody(), true);
      $this->assertArrayHasKey('chatId', $responseData);
      $this->assertArrayHasKey('userId', $responseData);
      $this->assertEquals($chatId, $responseData['chatId']);
      $this->assertEquals($newUserId, $responseData['userId']);

      $this->deleteChat($token, $chatId);
      $this->deleteUser($newToken, $newUserId);
      $this->deleteUser($token, $userId);
    }

    public function test4ForbiddenIfUserNotInChat()
    {
        $user = $this->createUser();
        $token = $user->getToken();
        $userId = $user->getId();
        $chat = $this->createChat($token);
        $chatId = $chat->getId();
        $newUser = $this->createUser();
        $newToken = $newUser->getToken();
        $newUserId = $newUser->getId();

        $response = $this->http->get('/api/v1/chats/' . $chatId . '/users', [
          'headers' => [
              'Authorization' => 'Bearer ' . $newToken,
          ],
          'http_errors' => false
        ]);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('Forbidden', $response->getBody());

        $this->deleteChat($token, $chatId);
        $this->deleteUser($newToken, $newUserId);
        $this->deleteUser($token, $userId);
    }
}
