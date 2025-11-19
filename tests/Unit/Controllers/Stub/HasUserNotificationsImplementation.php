<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers\Stub;

use Volunteersystem\Controllers\HasUserNotifications;
use Volunteersystem\Controllers\NotificationType;

class HasUserNotificationsImplementation
{
    use HasUserNotifications;

    public function add(string|array $value, NotificationType $type = NotificationType::MESSAGE): void
    {
        $this->addNotification($value, $type);
    }

    public function get(): array
    {
        return $this->getNotifications();
    }
}
