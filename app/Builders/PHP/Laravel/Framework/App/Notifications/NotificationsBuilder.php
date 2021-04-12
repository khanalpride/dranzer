<?php

namespace App\Builders\PHP\Laravel\Framework\App\Notifications;

use App\Builders\PHP\ClassBuilder;
use App\Builders\Processors\App\Notifications\NotificationsProcessor;

/**
 * Class NotificationBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Notifications
 */
class NotificationsBuilder extends ClassBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        NotificationsProcessor::class
    ];
    /**
     * @var string|null
     */
    protected string $namespace = 'App\Notifications';
}
