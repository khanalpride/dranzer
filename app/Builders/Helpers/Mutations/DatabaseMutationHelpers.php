<?php

namespace App\Builders\Helpers\Mutations;

use App\Builders\PHP\Laravel\Framework\Database\Blueprint;

class DatabaseMutationHelpers
{
    public static function getConnectionName($databaseType): string
    {
        /** @noinspection SpellCheckingInspection */
        $mapping = [
            'sqlite' => 'sqlite',
            'mysql' => 'mysql',
            'mongodb' => 'mongodb',
            'postgresql' => 'pgsql',
            'sqlserver' => 'sqlsrv',
        ];

        return $mapping[$databaseType] ?? 'mysql';
    }

    public static function getDefaultDatabasePort($databaseType): string
    {
        $mapping = [
            'mysql' => '3306',
            'mongodb' => '27017',
            'postgresql' => '5432',
            'sqlserver' => '1433',
        ];

        return $mapping[$databaseType] ?? '3306';
    }

    public static function getBlueprintNameFromId($blueprintId)
    {
        $blueprints = app('mutations')->for('database')['blueprints'];

        $blueprint = collect($blueprints)
            ->first(static fn (Blueprint $blueprint) => $blueprint->getId() === $blueprintId);

        return $blueprint ? $blueprint->getName() : null;
    }
}
