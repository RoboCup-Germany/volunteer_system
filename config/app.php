<?php

declare(strict_types=1);

// Application config

return [
    // Service providers
    'providers'  => [
        // Application bootstrap
        \Volunteersystem\Logger\LoggerServiceProvider::class,
        \Volunteersystem\Exceptions\ExceptionsServiceProvider::class,
        \Volunteersystem\Config\ConfigServiceProvider::class,
        \Volunteersystem\Helpers\ConfigureEnvironmentServiceProvider::class,
        \Volunteersystem\Events\EventsServiceProvider::class,

        // Request handling
        \Volunteersystem\Http\UrlGeneratorServiceProvider::class,
        \Volunteersystem\Renderer\RendererServiceProvider::class,
        \Volunteersystem\Database\DatabaseServiceProvider::class,
        \Volunteersystem\Http\RequestServiceProvider::class,
        \Volunteersystem\Http\SessionServiceProvider::class,
        \Volunteersystem\Helpers\Translation\TranslationServiceProvider::class,
        \Volunteersystem\Http\ResponseServiceProvider::class,
        \Volunteersystem\Http\Psr7ServiceProvider::class,
        \Volunteersystem\Helpers\CacheServiceProvider::class,
        \Volunteersystem\Helpers\AuthenticatorServiceProvider::class,
        \Volunteersystem\Helpers\AssetsServiceProvider::class,
        \Volunteersystem\Renderer\TwigServiceProvider::class,
        \Volunteersystem\Middleware\RouteDispatcherServiceProvider::class,
        \Volunteersystem\Middleware\RequestHandlerServiceProvider::class,
        \Volunteersystem\Http\Validation\ValidationServiceProvider::class,
        \Volunteersystem\Http\RedirectServiceProvider::class,

        // Additional services
        \Volunteersystem\Helpers\VersionServiceProvider::class,
        \Volunteersystem\Mail\MailerServiceProvider::class,
        \Volunteersystem\Http\HttpClientServiceProvider::class,
        \Volunteersystem\Helpers\DumpServerServiceProvider::class,
        \Volunteersystem\Helpers\UuidServiceProvider::class,
        \Volunteersystem\Controllers\Api\UsesAuthServiceProvider::class,
    ],

    // Application middleware
    'middleware' => [
        // Basic initialization
        \Volunteersystem\Middleware\SendResponseHandler::class,
        \Volunteersystem\Middleware\ExceptionHandler::class,

        // Changes of request/response parameters
        \Volunteersystem\Middleware\SetLocale::class,
        \Volunteersystem\Middleware\ETagHandler::class,
        \Volunteersystem\Middleware\AddHeaders::class,
        \Volunteersystem\Middleware\TrimInput::class,

        // The application code
        \Volunteersystem\Middleware\ErrorHandler::class,
        \Volunteersystem\Middleware\ApiRouteHandler::class,
        \Volunteersystem\Middleware\VerifyCsrfToken::class,
        \Volunteersystem\Middleware\RouteDispatcher::class,
        \Volunteersystem\Middleware\SessionHandler::class,

        // Handle request
        \Volunteersystem\Middleware\RequestHandler::class,
    ],

    // Event handlers
    'event-handlers' => [
        // 'event' => [
        //      a list of
        //      'Class@method' or 'Class' (which uses @handle),
        //      ['Class', 'method'],
        //      callable like [$instance, 'method'] or 'function'
        //      or $function
        // ]

        'message.created' => \Volunteersystem\Events\Listener\Messages::class . '@created',

        'news.created' => \Volunteersystem\Events\Listener\News::class . '@created',
        'news.updated' => \Volunteersystem\Events\Listener\News::class . '@updated',

        'oauth2.login' => \Volunteersystem\Events\Listener\OAuth2::class . '@login',

        'shift.deleting' => [
            \Volunteersystem\Events\Listener\Shifts::class . '@deletingCreateWorklogs',
            \Volunteersystem\Events\Listener\Shifts::class . '@deletingSendEmails',
        ],

        'shift.updating' => \Volunteersystem\Events\Listener\Shifts::class . '@updatedSendEmail',
    ],
];
