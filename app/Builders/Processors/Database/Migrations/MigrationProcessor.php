<?php

namespace App\Builders\Processors\Database\Migrations;

use Closure;
use Illuminate\Support\Str;
use App\Builders\Helpers\BlueprintHelpers;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\PHP\Laravel\Framework\Database\Blueprint;
use App\Builders\PHP\Laravel\Framework\Database\Migrations\MigrationBuilder;

/**
 * Class MigrationProcessor
 * @package App\Builders\Processors\Database\Migrations
 */
class MigrationProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $databaseConfig = app('mutations')->for('database');

        $dbConnection = $databaseConfig['connection'];
        $blueprints = $databaseConfig['blueprints'];
        $relations = $databaseConfig['relations'];

        foreach ($blueprints as $blueprint) {
            $this->createMigration($blueprint, $blueprints, $relations, $dbConnection);
        }

        $next($builder);

        return true;
    }

    /**
     * @param Blueprint $blueprint
     * @param array $blueprints
     * @param array $relations
     * @param string $dbConnection
     * @return void
     */
    private function createMigration(Blueprint $blueprint, array $blueprints, array $relations, string $dbConnection): void
    {
        $tableName = $blueprint->getTable();

        $migrationsPath = app('project-dir') . '/database/migrations';

        $migrationOrder = $this->getMigrationOrder($blueprints, $relations);

        // Reset the users migration file timestamp to the freshly installed Laravel app.
        if (array_key_exists('users', $migrationOrder)) {
            $migrationOrder['users'] = '2014_10_12_000000';
        }

        $pluralize = (!$blueprint->isPivot() && !$blueprint->isSingularTableName()) || $blueprint->shouldPluralizeTableName();

        $timestamp = $tableName === 'users' ? '2014_10_12_000000' : $this->getTimestamp();
        $migrationFilename = $timestamp . '_' . $this->getMigrationFilename($tableName, $pluralize);

        if (array_key_exists($tableName, $migrationOrder)) {
            $migrationFilename = $migrationOrder[$tableName] . '_' . $this->getMigrationFilename(
                    $tableName, $pluralize
                );
        }

        (new MigrationBuilder)
            ->setOutputDir($migrationsPath)
            ->setFilename($migrationFilename)
            ->setBlueprintId($blueprint->getId())
            ->setTableName($tableName)
            ->setPluralizeTableName($pluralize)
            ->setBlueprints($blueprints)
            ->setConnection($dbConnection)
            ->setRelations($relations)
            ->setColumns($blueprint->getColumns())
            ->setSoftDelete($blueprint->shouldSoftDelete())
            ->build();

    }

    /**
     * @return false|string
     */
    private function getTimestamp()
    {
        return date('Y_m_d_His');
    }

    /**
     * @param $tableName
     * @param bool $pluralize
     * @return string
     */
    private function getMigrationFilename($tableName, $pluralize = true): string
    {
        return 'create_' . Str::snake($pluralize ? Str::plural($tableName) : $tableName) . '_table.php';
    }

    /**
     * @param array $blueprints
     * @param array $relations
     * @return array
     */
    private function getMigrationOrder(array $blueprints, array $relations): array
    {
        $depMap = [];

        foreach ($blueprints as $blueprint) {
            $tableName = $blueprint->getTable();
            $dependencyMapForBlueprint = BlueprintHelpers::getDependencyMapForBlueprint(
                $blueprint, collect($blueprints), $relations
            );
            $depMap[$tableName] = $dependencyMapForBlueprint;
        }

        $order = [];

        $index = 1;

        foreach ($depMap as $table => $dependencies) {
            if (!count($dependencies)) {
                $order[$table] = date('Y_m_d_Hi') . ($index < 10 ? '0' . $index : $index);
                $index++;
            }
        }

        foreach ($depMap as $table => $dependencies) {
            if (count($dependencies)) {
                foreach ($dependencies as $dependency) {
                    if (!array_key_exists($dependency, $order)) {
                        $order[$dependency] = date('Y_m_d_Hi') . ($index < 10 ? '0' . $index : $index);
                        $index++;
                    }
                }
                if (!array_key_exists($table, $order)) {
                    $order[$table] = date('Y_m_d_Hi') . ($index < 10 ? '0' . $index : $index);
                    $index++;
                }
            }
        }

        return $order;
    }
}
