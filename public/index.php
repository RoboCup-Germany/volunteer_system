<?php

declare(strict_types=1);

use Volunteersystem\Application;
use Volunteersystem\Middleware\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;

// Include app bootstrapping
require_once realpath(__DIR__ . '/../includes/volunteersystem.php');

/** @var Application $app */
$app = app();

/** @var ServerRequestInterface $request */
$request = $app->get('psr7.request');
$middleware = $app->getMiddleware();

$dispatcher = new Dispatcher($middleware);
$dispatcher->setContainer($app);

// Handle the request
$dispatcher->handle($request);
