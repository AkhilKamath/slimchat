<?php

namespace App\Middleware;

use JsonSchema\Validator;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as Response;
use Slim\Routing\RouteContext;

class JsonSchemaValidator
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $routeName = $route->getName();

        // Map route names to schema files
        $schemas = [
            'createUser' => __DIR__ . '/../Schema/user.json',
            'createChat' => __DIR__ . '/../Schema/chat.json',
            'createMessage' => __DIR__ . '/../Schema/message.json',
        ];

        if (!isset($schemas[$routeName])) {
            // No schema to validate against
            return $handler->handle($request);
        }

        $data = $request->getParsedBody();

        $schemaPath = $schemas[$routeName];
        $data = json_decode(json_encode($data));
        // Validate
        $validator = new Validator();
        $validator->validate(
            $data,
            (object)['$ref' => 'file://' . realpath($schemaPath)]
        );
        if (!$validator->isValid()) {
          return $this->sendErrorResponse($validator->getErrors());
        }

        return $handler->handle($request);
    }

    public function sendErrorResponse($error) {
      $response = new Response();
      $responseJson = [
        "errors" => $error
      ];
      $response->getBody()->write(json_encode($responseJson));
      $newResponse = $response->withStatus(422);
      return $newResponse->withHeader('Content-Type', 'application/json');
    }
}
