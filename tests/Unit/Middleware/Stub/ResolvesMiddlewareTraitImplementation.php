<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Middleware\Stub;

use Volunteersystem\Application;
use Volunteersystem\Middleware\ResolvesMiddlewareTrait;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ResolvesMiddlewareTraitImplementation
{
    use ResolvesMiddlewareTrait;

    public function __construct(protected ?Application $container = null)
    {
    }

    public function callResolveMiddleware(
        string|callable|array|MiddlewareInterface|RequestHandlerInterface $middleware
    ): MiddlewareInterface|RequestHandlerInterface {
        return $this->resolveMiddleware($middleware);
    }
}
