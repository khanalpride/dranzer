<?php

namespace App\Builders\Processors\Database\Factories;

use Closure;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\PHP\Laravel\Framework\Database\Blueprint;
use App\Builders\PHP\Laravel\Framework\Database\Factories\FactoryBuilder;

/**
 * Class FactoryProcessor
 * @package App\Builders\Processors\Database\Factories
 */
class FactoryProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $dbMutations = app('mutations')->for('database');

        $blueprints = $dbMutations['blueprints'];

        $relations = $dbMutations['relations'];

        $creatableBlueprints = collect($blueprints)
            ->filter(static fn (Blueprint $blueprint) => $blueprint->shouldCreateFactory());

        foreach ($creatableBlueprints as $blueprint) {
            $this->createFactory($blueprint, $blueprints, $relations);
        }

        $next($builder);

        return true;
    }

    /**
     * @param Blueprint $blueprint
     * @param array $blueprints
     * @param array $relations
     * @return void
     */
    private function createFactory(Blueprint $blueprint, array $blueprints, array $relations): void
    {
        if ($blueprint->shouldCreateFactory()) {
            $factoryClassPath = app('project-dir') . '/database/factories';

            $factoryBuilder = new FactoryBuilder;

            $factoryBuilder
                ->setBlueprints($blueprints)
                ->setRelations($relations)
                ->setColumns($blueprint->getColumns())
                ->setOutputDir($factoryClassPath)
                ->setTable($blueprint->getTable())
                ->build();

            $factoryBuilder->reset();
        }

    }
}
