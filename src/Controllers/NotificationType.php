<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers;

enum NotificationType: string
{
    case ERROR = 'error';
    case WARNING = 'warning';
    case INFORMATION = 'information';
    case MESSAGE = 'message';
}
