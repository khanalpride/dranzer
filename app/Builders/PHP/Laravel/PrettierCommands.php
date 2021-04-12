<?php

namespace App\Builders\PHP\Laravel;

/**
 * Class PrettierCommands
 * @package App\Builders\PHP\Laravel
 */
class PrettierCommands
{
    /**
     * @var array
     */
    private static $commands = [];

    /**
     * @param $path
     * @param null $parser
     * @return PrettierCommands
     */
    public static function add($path, $parser = null): PrettierCommands
    {
        self::$commands[] = ['path' => $path, 'parser' => $parser];
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
