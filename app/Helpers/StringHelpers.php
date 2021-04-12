<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class StringHelpers
{
    /**
     * @param $string
     * @return string
     */
    public static function humanize($string): string
    {
        $words = explode(' ', str_ireplace([
            '_',
            '-',
            ':',
            '.',
            '*'
        ], ' ', Str::snake($string)));
        return collect($words)->map(fn ($w) => Str::studly($w))->join(' ');
    }
}
