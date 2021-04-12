<?php

namespace App\Builders\Processors\Database\Seeders;

use Closure;
use Illuminate\Support\Str;
use App\Builders\Helpers\BlueprintHelpers;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\PHP\Laravel\Framework\Database\Blueprint;
use App\Builders\PHP\Laravel\Framework\Database\DatabaseSeederBuilder;
use App\Builders\PHP\Laravel\Framework\Database\Seeders\SeederBuilder;

class SeederProcessor extends PHPBuilderProcessor
{
    public function process($builder, Closure $next): bool
    {
        $dbMutations = app('mutations')->for('database');

        $blueprints = $dbMutations['blueprints'];
        $relations = $dbMutations['relations'];

        $this->createDatabaseSeeder($blueprints, $relations);

        $seedableBlueprints = collect($blueprints)
            ->filter(static fn (Blueprint $blueprint) => $blueprint->shouldCreateSeeder() && $blueprint->getSeedCount() > 0)
            ->toArray();

        foreach ($seedableBlueprints as $blueprint) {
            $this->createSeeder($blueprint, $seedableBlueprints);
        }

        $next($builder);

        return true;
    }

    /**
     * @param array $blueprints
     * @param array $relations
     * @return void
     */
    private function createDatabaseSeeder(array $blueprints, array $relations): void
    {
        $seedClassPath = app('project-dir') . '/database/seeders';

        $orderedSeeders = $this->getOrderedSeeders($blueprints, $relations);

        (new DatabaseSeederBuilder)
            ->setOutputDir($seedClassPath)
            ->setSeeders($orderedSeeders)
            ->build();

    }

    /**
     * @param Blueprint $blueprint
     * @param array $blueprints
     * @return void
     */
    private function createSeeder(Blueprint $blueprint, array $blueprints): void
    {
        $seedCount = $blueprint->getSeedCount();

        $seedClassPath = app('project-dir') . '/database/seeders';

        $className = $this->getSeederClassName($blueprint->getTable());

        $seedBuilder = new SeederBuilder;
        $seedBuilder->setOutputDir($seedClassPath)
            ->setFilename("$className.php")
            ->setBlueprints($blueprints)
            ->setTableName($blueprint->getTable())
            ->setColumns($blueprint->getColumns())
            ->setSeedCount($seedCount)
            ->overrideClassDefinition([
                'name'      => $className,
                'extend'    => 'Seeder',
                'namespace' => 'Database\Seeders'
            ]);

        $seedBuilder->build();

    }

    /**
     * @param array $blueprints
     * @param array $relations
     * @return array
     */
    private function getOrderedSeeders(array $blueprints, array $relations): array
    {
        $depMap = [];

        foreach ($blueprints as $blueprint) {
            $tableName = $blueprint->getTable();
            $blueprintDependencyMap = BlueprintHelpers::getDependencyMapForBlueprint(
                $blueprint, collect($blueprints), $relations
            );
            $depMap[$tableName] = $blueprintDependencyMap;
        }

        $seeders = [];

        foreach ($depMap as $table => $dependencies) {
            if (!count($dependencies)) {
                $seeders[] = $this->getSeederClassName($table);
            }
        }

        foreach ($depMap as $table => $dependencies) {
            if (count($dependencies)) {
                foreach ($dependencies as $dependency) {
                    if (!in_array(
                        $this->getSeederClassName($dependency),
                        $seeders,
                        true
                    )) {
                        $seeders[] = $this->getSeederClassName($dependency);
                    }
                }
                if (!in_array($this->getSeederClassName($table), $seeders, true)) {
                    $seeders[] = $this->getSeederClassName($table);
                }
            }
        }

        return $seeders;
    }

    /**
     * @param $table
     * @param bool $pluralize
     * @return string
     */
    private function getSeederClassName($table, $pluralize = true): string
    {
        return ($pluralize ? Str::plural(Str::studly($table)) : Str::studly($table)) . 'TableSeeder';
    }
}
