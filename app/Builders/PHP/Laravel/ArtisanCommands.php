<?php

namespace App\Builders\PHP\Laravel;

/**
 * Class ArtisanCommands
 * @package App\Builders\PHP\Laravel
 */
class ArtisanCommands
{
    /**
     * @var array
     */
    private static $commands = [];

    /**
     * @param $command
     * @return ArtisanCommands
     */
    public static function add($command): ArtisanCommands
    {
        self::$commands[] = $command;
        return new self;
    }

    /**
     * @return array
     */
    public static function getCommands(): array
    {
        return self::$commands;
    }
}
