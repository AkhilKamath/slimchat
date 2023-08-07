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
  
  /**
   * This method handles the GET request to find a user by id.
   * If the user is not found, it returns a 404 status.
   * If the user is found, it returns a JSON response.
   *
   * @param Request $request The request object.
   * @param Response $response The response object.
   * @param string $id the user id.
   *
   * @return Response The response object containing the user information in JSON format or a NOT FOUND message.
   */
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

  /**
   * This method handles the GET request to find all users.
   * If no users are found, it returns a 404 status.
   * If users are found, it returns a JSON response.
   *
   * @param Request $request The request object.
   * @param Response $response The response object.
   *
   * @return Response The response object containing an array of users in JSON format or a NOT FOUND message.
   */
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

  /**
   * This method handles the POST request to create a new user.
   * It generates a unique id and creates a new user instance with that id.
   * It uses the user repository to persist the user.
   * The response is returned as a JSON with a status code of 201 (Created).
   *
   * @param Request $request The request object.
   * @param Response $response The response object.
   *
   * @return Response The response object containing the userId, name and token in JSON.
   */
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

  /**
   * This method handles the DELETE request to delete user by id.
   * It fetches the user using the id provided in the route path.
   * If no user or user is found, it returns 404 (Not Found).
   * It uses the user repository to delete the user.
   * The response is returned as a JSON with status code 204 (No Content)
   *
   * @param Request $request The request object.
   * @param Response $response The response object.
   * @param string $id The chat id.
   * 
   * @return Response The response object with a status code of 204 (sucess, no content) or 404.
   */
  public function deleteUser(Request $request, Response $response, string $userId): Response
  {
      $user = $this->getRepository()->getUser($userId);

      if (!$user) {
          $response->getBody()->write('NOT FOUND');
          return $response->withStatus(404);
      }

      $this->getRepository()->deleteUser($user);
      return $response->withStatus(204);
  }
}
