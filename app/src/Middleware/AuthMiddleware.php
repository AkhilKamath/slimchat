<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as Response;
use Slim\Routing\RouteContext;
use App\Model\User;
use App\Repository\UserRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $routeArguments = $route->getArguments();
        $routeName = $route->getName();
        if($routeName == 'createUser') {
          return $handler->handle($request); 
        }
        $token = explode(' ', (string)$request->getHeaderLine('Authorization'))[1] ?? null;
        $user = null;
        if($token) {
          try {
            $key = $_ENV['JWT_KEY'];
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            $userId = $decoded->userId;
            $user = $this->userRepository->getUser($userId);
          } catch (ExpiredException $e) {
              $response = new Response();
              $response->getBody()->write('Token Expired');
              return $response->withStatus(401);
          } catch (\Exception $e) {
              $response = new Response();
              $response->getBody()->write('Unauthorized');
              return $response->withStatus(401);
          }
          if ($user) {
            $routeUserId = $routeArguments['userId'] ?? null;
            if ($routeUserId && $routeUserId != $user->getId()) {
              // Unauthorized user
              $response = new Response();
              $response->getBody()->write('Unauthorized');
              return $response->withStatus(401);
            }
            // Valid token
            if($routeName == 'createChat' || $routeName == 'getMessages' || $routeName == 'addUserToChat' || $routeName == 'getUsersInChat' || $routeName == 'deleteChat') {
              $request = $request->withAttribute('user', $user);                      
            }
            return $handler->handle($request);
          }
        }

        // No token provided
        $response = new Response();
        $response->getBody()->write('Unauthorized');
        return $response->withStatus(401);
    }
}