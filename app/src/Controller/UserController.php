<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\Serializer\Serializer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use DateTimeImmutable;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

use App\Model\User;

class UserController extends BaseController
{
  public function getUser(Request $request, Response $response, string $id): Response
  {
    $user = $this->getRepository()->getUser($id);
    if(!$user) {
      $response->getBody()->write('NOT FOUND');
      return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
    $json = $this->getSerializer()->serialize($user, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['chats', 'token']]);
    $response->getBody()->write($json);
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function getAllUsers(Request $request, Response $response): Response
  {
    $users = $this->getRepository()->getAllUsers();
    if(!$users) {
      $response->getBody()->write('NOT FOUND');
      return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
    $json = $this->getSerializer()->serialize($users, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['chats', 'token']]);
    $response->getBody()->write($json);
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function createUser(Request $request, Response $response): Response
  {
      $data = $request->getParsedBody();
      $id = uniqid();
      $user = new User($id);
      $user->setName($data['name']);

      $tokenId    = base64_encode(random_bytes(32));
      $issuedAt   = new DateTimeImmutable();
      $expire     = $issuedAt->modify('+365 day')->getTimestamp();
      $data = [
          'iat'  => $issuedAt->getTimestamp(),
          'jti'  => $tokenId,
          'iss'  => 'chatapp.com',
          'nbf'  => $issuedAt->getTimestamp(),
          'exp'  => $expire,
          'userId' => $id
      ];

      $jwt = JWT::encode(
        $data,
        $_ENV['JWT_KEY'],
        'HS256'
      );
      $user->setToken($jwt);

      $this->getRepository()->createUser($user);

      $jsonObject = json_decode($this->getSerializer()->serialize($user, 'json'));
      unset($jsonObject->chats);
      $json = json_encode($jsonObject);

      $response->getBody()->write($json);
      return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
  }

  public function deleteUser(Request $request, Response $response, string $userId): Response
  {
      $user = $this->getRepository()->getUser($userId);

      if (!$user) {
          // User not found, return 404
          $response->getBody()->write('NOT FOUND');
          return $response->withStatus(404);
      }

      $this->getRepository()->deleteUser($user);
      return $response->withStatus(204); // No Content
  }
}
