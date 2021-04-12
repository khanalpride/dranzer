<?php

namespace App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\Builders;

use Illuminate\Support\Str;
use App\Builders\PHP\ClassBuilder;
use App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\OrchidModule;
use App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\OrchidColumn;

/**
 * Class OrchidPresenterBuilder
 * @package App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\Builders
 */
class OrchidPresenterBuilder extends ClassBuilder
{
    /**
     * @var bool
     */
    public static bool $customBuilder = true;
    /**
     * @var string
     */
    protected string $namespace = 'App\Orchid\Presenters';
    /**
     * @var OrchidModule
     */
    private OrchidModule $module;

    /**
     * @return bool
     */
    public function build(): bool
    {
        $this->classDefinition = [
            'name'   => $this->module->getName() . 'Presenter',
            'extend' => 'Presenter',
        ];

        $this->updateClassDefinition();

        $this->setImplements(['Searchable']);

        $this->use('Laravel\Scout\Builder');
        $this->use('Orchid\Screen\Contracts\Searchable');
        $this->use('Orchid\Support\Presenter');

        return $this
            ->buildClass()
            ->toDisk();
    }

    /**
     * @return OrchidPresenterBuilder
     */
    protected function buildClass(): OrchidPresenterBuilder
    {
        $moduleName = $this->module->getName();

        $pluralModuleName = Str::plural($moduleName);

        $columns = $this->module->getColumns();

        $titleColumn = collect($columns)->first(fn (OrchidColumn $col) => $col->getId() === $this->module->getTitleColumn());
        $subTitleColumn = collect($columns)->first(fn (OrchidColumn $col) => $col->getId() === $this->module->getSubTitleColumn());

        $titleColumnStmt = $titleColumn ? $this->chainedPropFetches('this', [
            $this->const('entity'),
            $this->const($titleColumn->getName()),
        ]) : $this->string($pluralModuleName);

        $subTitleColumnStmt = $subTitleColumn ? $this->chainedPropFetches('this', [
            $this->const('entity'),
            $this->const($subTitleColumn->getName()),
        ]) : $this->string('');

        $entityEditUrlStmt = $this->funcCall('route', [
            $this->string('platform.' . Str::snake(Str::singular($moduleName)) . '.edit'),
            $this->propFetch('this', 'entity')
        ]);

        $builder = $this->getNewMethodBuilder('perSearchShow');
        $builder->setReturnType('int')
            ->addStatement($this->return($this->funcCall('env', [
                $this->string('SCOUT_MAX_RESULTS'),
                $this->int(5)
            ])));
        $this->addMethodBuilder($builder);

        $builder = $this->getNewMethodBuilder('searchQuery');
        $builder->addParameter($this->param('query', 'string', $this->const('null')))
            ->setReturnType('Builder')
            ->addStatement($this->return(
                $this->methodCall($this->propFetch('this', 'entity'), 'search', [$this->var('query')])
            ))
            ->getDocBuilder()
            ->setReturnType('Builder');
        $this->addMethodBuilder($builder);

        $builder = $this->getNewMethodBuilder('label');
        $builder->setReturnType('string')
            ->addStatement($this->return($this->string($pluralModuleName)));
        $this->addMethodBuilder($builder);

        $builder = $this->getNewMethodBuilder('title');
        $builder->setReturnType('string')
            ->addStatement($this->return($titleColumnStmt));
        $this->addMethodBuilder($builder);

        $builder = $this->getNewMethodBuilder('subTitle');
        $builder->setReturnType('string')
            ->addStatement($this->return($subTitleColumnStmt));
        $this->addMethodBuilder($builder);

        $builder = $this->getNewMethodBuilder('url');
        $builder->setReturnType('string')
            ->addStatement($this->return($entityEditUrlStmt));
        $this->addMethodBuilder($builder);

        $builder = $this->getNewMethodBuilder('image');
        $builder->setReturnType('?string')
            ->addStatement($this->return($this->const('null')));
        $this->addMethodBuilder($builder);

        return $this;
    }

    /**
     * @return OrchidModule
     */
    public function getModule(): OrchidModule
    {
        return $this->module;
    }

    /**
     * @param OrchidModule $module
     * @return OrchidPresenterBuilder
     */
    public function setModule(OrchidModule $module): OrchidPresenterBuilder
    {
        $this->module = $module;
        return $this;
    }
}
