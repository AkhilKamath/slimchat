<?php

namespace App\Middleware;

use JsonSchema\Validator;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as Response;
use Slim\Routing\RouteContext;

/**
 * This middleware class is responsible for the validation of incoming JSON requests.
 * It validates the request's body against a predefined JSON schema for the User, Chat and Message models.
 * If the validation succeeds, the process continues to the next handler.
 * If the validation fails, it immediately returns a 400 Bad Request response with details about the validation errors.
 * This middleware will validate for all the POST routes.
 */
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
