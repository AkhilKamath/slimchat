<?php

// tests/Controller/UserControllerTest.php

namespace Tests;

use GuzzleHttp\Client;
use Faker\Factory;
use Tests\BaseTest;


class UserControllerTest extends BaseTest
{
    protected function setUp(): void
    {
      $this->http = new Client(['base_uri' => $_ENV['BASE_URI']]);
        $this->faker = Factory::create();
    }

    public function test1GetUser()
    {
        $user = $this->createUser();
        $token = $user->getToken();
        $id = $user->getId();
        $name = $user->getName();
        $response = $this->http->get('/api/v1/users/' . $id, [
          'headers' => [
              'Authorization' => 'Bearer ' . $token,
            ]
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $userData = json_decode($response->getBody(), true);
        $this->assertEquals($id, $userData['id']);
        $this->assertEquals($name, $userData['name']);
        $this->assertArrayNotHasKey('token', $userData);
        $this->deleteUser($token, $id);
    }

    public function test2GetAllUsers()
    {
        $user = $this->createUser();
        $token = $user->getToken();
        $id = $user->getId();
        $response = $this->http->get('/api/v1/users', [
          'headers' => [
              'Authorization' => 'Bearer ' . $token,
          ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $fetchedUsersData = json_decode($response->getBody(), true);
        $this->assertGreaterThanOrEqual(1, count($fetchedUsersData));
        $fetchedUserIds = array_column($fetchedUsersData, 'id');
        $this->assertContains($user->getId(), $fetchedUserIds);
        $this->deleteUser($token, $id);
    }

    public function test3UnauthorizedIfInvalidToken()
    {
        $user = $this->createUser();
        $token = $user->getToken();
        $id = $user->getId();
        $response = $this->http->get('/api/v1/users', [
          'headers' => [
              'Authorization' => 'Bearer ' . 'invalid',
          ],
          'http_errors' => false
        ]);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Unauthorized', $response->getBody());        

        $this->deleteUser($token, $id);
    }
}
