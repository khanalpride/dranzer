<?php

namespace App\Builders\Processors\App\Models;

use Closure;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\Helpers\Mutations\UIMutationHelpers;
use App\Builders\PHP\Laravel\Framework\Database\Blueprint;
use App\Builders\PHP\Laravel\Framework\App\Models\ModelBuilder;
use App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\OrchidModule;
use App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\OrchidColumn;

/**
 * Class ModelProcessor
 * @package App\Builders\Processors\App\Models
 */
class ModelProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $this->processOrchid($builder);

        $next($builder);

        return true;
    }

    /**
     * @param ModelBuilder $builder
     * @return void
     */
    private function processOrchid(ModelBuilder $builder): void
    {
        $moduleName = $builder->getModelName();

        $uiConfig = app('mutations')->for('ui');

        if (UIMutationHelpers::installOrchid()) {
            $modules = $uiConfig['orchid']['modules'];

            $builder
                ->use('Orchid\Screen\AsSource')
                ->addTrait('AsSource');

            $blueprints = app('mutations')->for('database')['blueprints'];

            $blueprint = collect($blueprints)
                ->first(fn (Blueprint $b) => $b->getName() === $builder->getModelName());

            $module = collect($modules)
                ->first(fn (OrchidModule $mod) => $mod->getId() === $blueprint->getId());

            if ($module && $module instanceof OrchidModule) {
                $moduleColumns = collect($module->getColumns());

                $searchableColumns = $moduleColumns
                    ->filter(fn (OrchidColumn $c) => $c->isSearchable())
                    ->map(fn (OrchidColumn $c) => $this->string($c->getName()))
                    ->toArray();

                if ($module->isFullTextSearch() && count($searchableColumns)) {
                    if ($builder->shouldBuildPresenter()) {
                        $presenterClass = "${moduleName}Presenter";

                        $builder->use("App\Orchid\Presenters\\$presenterClass");

                        $presenterMethod = $builder->getNewMethodBuilder('presenter');

                        $presenterMethod
                            ->setReturnType($presenterClass)
                            ->addStatement(
                                $this->return(
                                    $this->new_($presenterClass, [$this->var('this')])
                                )
                            )
                            ->getDocBuilder()
                            ->addCommentLine('Get the presenter associated with this model.')
                            ->setReturnType($presenterClass);

                        $builder->addMethodBuilder($presenterMethod);
                    }

                    $allColumnsAreSearchable = count($moduleColumns) === count($searchableColumns);

                    $stmt = $allColumnsAreSearchable ? $this->methodCall('this', 'toArray') : $this->arr(
                        array_values($searchableColumns)
                    );

                    $toSearchableArrayMethod = $builder->getNewMethodBuilder('toSearchableArray');

                    $toSearchableArrayMethod
                        ->addStatement($this->return($stmt))
                        ->setReturnType('array')
                        ->getDocBuilder()
                        ->addCommentLine('Get the indexable data array for the model.')
                        ->setReturnType('array');

                    $builder->addMethodBuilder($toSearchableArrayMethod);

                    $searchableAsMethod = $builder->getNewMethodBuilder('searchableAs');

                    $searchableAs = $module->getSearchableAs();

                    $searchableAsMethod
                        ->addStatement($this->return($this->string($searchableAs)))
                        ->setReturnType('string')
                        ->getDocBuilder()
                        ->addCommentLine('Get the name of the index associated with the model.')
                        ->setReturnType('string');

                    $builder->addMethodBuilder($searchableAsMethod);

                    $builder->addTrait('Searchable');
                    $builder->use('Laravel\Scout\Searchable');
                }

                if ($builder->shouldProcessFilterables()) {
                    $filterableColumns = collect($modules)
                        ->filter(fn (OrchidModule $mod) => $mod->getId() === $blueprint->getId())
                        ->map(fn (OrchidModule $mod) => $mod->getColumns())
                        ->collapse()
                        ->filter(fn (OrchidColumn $c) => $c->isFilterable())
                        ->map(fn (OrchidColumn $c) => $this->string($c->getName()))
                        ->values()
                        ->toArray();

                    if (count($filterableColumns)) {
                        $builder->use('Orchid\Filters\Filterable');
                        $builder->addTrait('Filterable');

                        $allowedFiltersProperty = $builder->getNewPropertyBuilder('allowedFilters');
                        $allowedFiltersProperty
                            ->setValue($this->arr(array_values($filterableColumns)))
                            ->makeProtected()
                            ->getDocBuilder()
                            ->addCommentLine('The attributes that are filterable.')
                            ->addVar('array');

                        $builder->addPropertyBuilder($allowedFiltersProperty);
                    }

                    $sortableColumns = collect($modules)
                        ->filter(fn (OrchidModule $mod) => $mod->getId() === $blueprint->getId())
                        ->map(fn (OrchidModule $mod) => $mod->getColumns())
                        ->collapse()
                        ->filter(fn (OrchidColumn $c) => $c->isSortable())
                        ->map(fn (OrchidColumn $c) => $this->string($c->getName()))
                        ->values()
                        ->toArray();

                    if (count($sortableColumns)) {
                        $allowedSortsProperty = $builder->getNewPropertyBuilder('allowedSorts');
                        $allowedSortsProperty->setValue($this->arr($sortableColumns))
                            ->makeProtected()
                            ->getDocBuilder()
                            ->addCommentLine('The attributes that are sortable.')
                            ->addVar('array');

                        $builder->addPropertyBuilder($allowedSortsProperty);
                    }
                }
            }
        }

    }
}
