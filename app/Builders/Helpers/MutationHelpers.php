<?php

namespace App\Builders\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;

/**
 * Class MutationHelpers
 * @package App\Builders\Helpers
 */
class MutationHelpers
{
    /**
     * @param $pattern
     * @param $mutations
     * @param false $includePath
     * @return mixed|null
     */
    public static function first($pattern, $mutations, $includePath = false)
    {
        $pattern = static::toPattern($pattern);

        $first = static::filter($pattern, $mutations, true)->first();

        return $includePath ? $first : $first['value'] ?? null;
    }

    /**
     * @param $pattern
     * @param $mutations
     * @param false $asCollection
     * @return array|Collection
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public static function filter($pattern, $mutations, $asCollection = false)
    {
        $pattern = static::toPattern($pattern);

        $filtered = collect($mutations)->filter(
            static function ($_, $k) use ($pattern) {
                return preg_match($pattern, $k) === 1;
            }
        );

        return !$asCollection ? $filtered->toArray() : $filtered;
    }

    /**
     * @param $pattern
     * @return mixed|string
     */
    private static function toPattern($pattern): string
    {
        return preg_match('/^\/.*?\/(\w+)?$/', $pattern) !== 1 ? "/$pattern/i" : $pattern;
    }

    public static function getDefaultUserModelMutations($blueprintId): array
    {
        return [
            "modelName" => "User",
            "tableName" => "users",
            "columns" => [
                0 => [
                    "id" => Str::random(),
                    "name" => "id",
                    "type" => "bigIncrements",
                    "disabled" => true,
                    "attributes" => [
                        "ai" => true,
                        "us" => true,
                        "n" => false,
                        "u" => false,
                        "f" => false,
                        "ug" => false,
                        "h" => true,
                        "length" => null,
                    ],
                ],
                1 => [
                    "id" => Str::random(),
                    "name" => "name",
                    "type" => "string",
                    "disabled" => true,
                    "attributes" => [
                        "ai" => false,
                        "us" => false,
                        "n" => false,
                        "u" => false,
                        "f" => true,
                        "ug" => false,
                        "h" => false,
                        "length" => null,
                    ],
                ],
                2 => [
                    "id" => Str::random(),
                    "name" => "email",
                    "type" => "string",
                    "disabled" => true,
                    "attributes" => [
                        "ai" => false,
                        "us" => false,
                        "n" => false,
                        "u" => true,
                        "f" => true,
                        "ug" => false,
                        "h" => false,
                        "length" => null,
                    ],
                ],
                3 => [
                    "id" => Str::random(),
                    "name" => "email_verified_at",
                    "type" => "timestamp",
                    "attributes" => [
                        "ai" => false,
                        "us" => false,
                        "n" => true,
                        "u" => false,
                        "f" => true,
                        "ug" => false,
                        "h" => true,
                        "length" => null,
                    ],
                ],
                4 => [
                    "id" => Str::random(),
                    "name" => "password",
                    "type" => "string",
                    "disabled" => true,
                    "attributes" => [
                        "ai" => false,
                        "us" => false,
                        "n" => false,
                        "u" => false,
                        "f" => true,
                        "ug" => false,
                        "h" => true,
                        "length" => null,
                    ],
                ],
                5 => [
                    "id" => Str::random(),
                    "name" => "remember_token",
                    "type" => "rememberToken",
                    "disabled" => true,
                    "attributes" => [
                        "ai" => false,
                        "us" => false,
                        "n" => false,
                        "u" => false,
                        "f" => false,
                        "ug" => false,
                        "h" => true,
                        "length" => null,
                    ],
                ],
                6 => [
                    "id" => Str::random(),
                    "name" => "created_at",
                    "type" => "timestamp",
                    "disabled" => true,
                    "attributes" => [
                        "ai" => false,
                        "us" => false,
                        "n" => false,
                        "u" => false,
                        "f" => false,
                        "ug" => false,
                        "h" => false,
                        "length" => null,
                    ],
                ],
                7 => [
                    "id" => Str::random(),
                    "name" => "updated_at",
                    "type" => "timestamp",
                    "disabled" => true,
                    "attributes" => [
                        "ai" => false,
                        "us" => false,
                        "n" => false,
                        "u" => false,
                        "f" => false,
                        "ug" => false,
                        "h" => false,
                        "length" => null,
                    ],
                ],
            ],
            "id" => $blueprintId,
        ];
    }
}
