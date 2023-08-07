<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use DI\Container;
use DI\ContainerBuilder;
use Slim\Handlers\Strategies\RequestResponseArgs;
use App\Middleware\JsonSchemaValidator;
use App\Middleware\AuthMiddleware;
use App\Repository\UserRepository;

require_once __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../env/');
$dotenv->load();

// Get config
$config = require __DIR__ . '/config.php';

$containerBuilder = new ContainerBuilder();
$definitions = require __DIR__ . '/definitions.php';
$containerBuilder->addDefinitions($definitions);
$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();


// Set routing strategy
$app->getRouteCollector()->setDefaultInvocationStrategy(new RequestResponseArgs());

// My middlewares
$app->add(new JsonSchemaValidator());
$app->add(new AuthMiddleware($container->get(UserRepository::class)));

// Add body parsing middleware
$app->addBodyParsingMiddleware();

// Add routing middleware
$app->addRoutingMiddleware();


// Set error handling
$errorMiddleware = $app->addErrorMiddleware(
  $config['errorParams']['displayErrorDetails'],
  $config['errorParams']['logErrors'],
  $config['errorParams']['logErrorDetails']
);

// Add routes
(require __DIR__ . '/routes.php')($app);

