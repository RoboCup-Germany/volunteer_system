<?php

declare(strict_types=1);

namespace Volunteersystem\Http;

use Volunteersystem\Container\ServiceProvider;
use GuzzleHttp\Client as GuzzleClient;

class HttpClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->when(GuzzleClient::class)
            ->needs('$config')
            ->give(
                function () {
                    return [
                        // No exception on >= 400 responses
                        'http_errors' => false,
                        // Wait max n seconds for a response
                        'timeout'     => 2.0,
                    ];
                }
            );
    }
}
