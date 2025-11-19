<?php

declare(strict_types=1);

namespace Volunteersystem;

enum Environment: string
{
    case PRODUCTION = 'prod';
    case DEVELOPMENT = 'dev';
}
