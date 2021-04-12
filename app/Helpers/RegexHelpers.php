<?php

namespace App\Helpers;

/**
 * Class RegexHelpers
 * @package App\Helpers
 */
class RegexHelpers
{
    /**
     * @param $pattern
     * @param $source
     * @return bool
     */
    public static function test($pattern, $source): bool
    {
        $matches = [];
        preg_match($pattern, $source, $matches);
        return count($matches) > 0;
    }

    /**
     * @param $pattern
     * @param $source
     * @return mixed|null
     */
    public static function match($pattern, $source)
    {
        $matches = [];
        preg_match($pattern, $source, $matches);
        return $matches[0] ?? null;
    }

    /**
     * @param $pattern
     * @param $source
     * @return array
     */
    public static function matches($pattern, $source): array
    {
        $matches = [];
        preg_match_all($pattern, $source, $matches);
        return $matches[0] ?? [];
    }
}
