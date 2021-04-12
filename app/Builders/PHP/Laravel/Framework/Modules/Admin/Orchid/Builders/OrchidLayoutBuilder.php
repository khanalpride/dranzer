<?php

/**
 * @noinspection PhpUnused
 * @noinspection UnknownInspectionInspection
 */

namespace App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\Builders;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Builders\PHP\ClassBuilder;
use PhpParser\Node\Expr\MethodCall;
use App\Builders\PHP\Laravel\Framework\Database\Blueprint;
use App\Builders\PHP\Laravel\Framework\Database\TableColumn;
use App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\OrchidModule;
use App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\OrchidColumn;
use App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations\OneToManyRelation;
use App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\Helpers\OrchidRelationHelpers;

/**
 * Class OrchidLayoutBuilder
 * @package App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid
 */
class OrchidLayoutBuilder extends ClassBuilder
{
    /**
     * @var bool
     */
    public static bool $customBuilder = true;
    /**
     * @var string
     */
    protected string $namespace = 'App\Orchid\Layouts';
    /**
     * @var string
     */
    private string $layoutType;
    /**
     * @var array
     */
    private array $oneToManyRelations = [];
    /**
     * @var bool
     */
    private bool $usesRelation = false;
    /**
     * @var array
     */
    private array $columnsMethodVars = [];
    /**
     * @var OrchidModule
     */
    private OrchidModule $module;
    /**
     * @var array
     */
    private array $blueprints = [];

    /**
     * @return bool
     */
    public function build(): bool
    {
        return $this
            ->buildClass()
            ->toDisk();
    }

    /**
     * @return OrchidLayoutBuilder
     */
    protected function buildClass(): OrchidLayoutBuilder
    {
        $module = $this->module;

        $moduleName = $module->getName();

        $layoutType = $this->layoutType;

        $layoutName = $moduleName . ($layoutType === 'listing' ? 'ListLayout' : 'EditLayout');

        $this->classDefinition = [
            'name'      => $layoutName,
            'extend'    => $layoutType === 'listing' ? 'Table' : 'Rows',
            'namespace' => 'App\Orchid\Layouts'
        ];

        $this->updateClassDefinition();

        $this->use('App\Models\\' . $moduleName);

        $this->use('Orchid\Screen\TD');
        $this->use('Orchid\Screen\Actions\Link');

        if ($layoutType === 'listing') {
            $this->use('Orchid\Screen\Layouts\Table');
        } else {
            $this->use('Orchid\Screen\Layouts\Rows');
        }

        $columns = collect($this->module->getColumns())
            ->sort(fn (OrchidColumn $a, OrchidColumn $b) => $a->getIndex() - $b->getIndex())
            ->toArray();

        $stmts = collect();

        $formImportMap = [
            'input'           => 'Orchid\Screen\Fields\Input',
            'textarea'        => 'Orchid\Screen\Fields\TextArea',
            'htmlEditor'      => 'Orchid\Screen\Fields\Quill',
            'datetime'        => 'Orchid\Screen\Fields\DateTimer',
            'boolean'         => 'Orchid\Screen\Fields\RadioButtons',
            'checkbox'        => 'Orchid\Screen\Fields\CheckBox',
            'select'          => 'Orchid\Screen\Fields\Select',
            'relation'        => 'Orchid\Screen\Fields\Relation',
            'hasManyRelation' => 'Orchid\Screen\Fields\Relation',
        ];

        $formImports = [];

        foreach ($columns as $column) {
            if ($layoutType === 'listing') {
                $colStmts = Arr::wrap($this->buildListScreenColumnStatement($column));
            } else {
                $controlType = $column->getLayout()->getControl() ?? 'input';

                if (($formImportMap[$controlType] ?? null) && !in_array($formImportMap[$controlType], $formImports, true)) {
                    collect($formImportMap[$controlType])->each(function ($import) use (&$formImports) {
                        $this->use($import);
                        $formImports[] = $import;
                    });
                }

                $colStmts = Arr::wrap($this->buildEditScreenColumnStatement($column));
            }

            $stmts [] = $colStmts;
        }

        $stmts = $stmts->collapse()->toArray();

        if ($layoutType === 'listing') {
            $target = Str::snake(Str::plural($this->module->getName()));

            $targetProperty = $this->getNewPropertyBuilder('target');
            $targetProperty->setValue($target)
                ->getDocBuilder()
                ->setReturnType('string')
                ->addCommentLine('Table used to fetch data for this layout.');
            $this->addPropertyBuilder($targetProperty);

            $columnMethodBuilder = $this->getNewMethodBuilder('columns');

            foreach ($this->columnsMethodVars as $columnsMethodVar) {
                $columnMethodBuilder->addStatement($columnsMethodVar);
            }

            $columnMethodBuilder->addStatement($this->return($this->arr($stmts)))
                ->setReturnType('array');

            $this->addMethodBuilder($columnMethodBuilder);
        } else {
            $resourceProp = $this->getNewPropertyBuilder(Str::snake($this->module->getName()));
            $resourceProp->makePrivate();
            $this->addPropertyBuilder($resourceProp);

            $constructorMethod = $this->getNewMethodBuilder('__construct');
            $constructorMethod->addParameter($this->param(Str::snake($this->module->getName()), $this->module->getName()))
                ->addStatement(
                    $this->inlineAssign(
                        $this->chainedPropFetches('this', [$this->const(Str::snake($this->module->getName()))], ['terminate' => false]),
                        $this->var(Str::snake($this->module->getName()))
                    )
                );
            $this->addMethodBuilder($constructorMethod);

            // Edit Screen Layout...
            $fieldsMethodBuilder = $this->getNewMethodBuilder('fields');
            $fieldsMethodBuilder->addStatement($this->return($this->arr($stmts)))
                ->setReturnType('array');

            $fieldsMethodBuilder->getDocBuilder()
                ->addCommentLine('Get the fields required to build this layout.');

            if ($this->usesRelation) {
                $fieldsMethodBuilder->getDocBuilder()
                    ->addCommentLine()
                    ->addCommentLine('@noinspection PhpUnhandledExceptionInspection');
            }

            $this->addMethodBuilder($fieldsMethodBuilder);
        }

        return $this;
    }

    /**
     * @param OrchidColumn $column
     *
     * @return MethodCall|null
     */
    private function buildEditScreenColumnStatement(OrchidColumn $column): ?MethodCall
    {
        $name = $column->getName();

        $layout = $column->getLayout();

        $moduleVisibility = $layout->getModuleVisibility();

        $visible = in_array('creating', $moduleVisibility, true);

        if (!$visible) {
            return null;
        }

        $controlType = $layout->getControl();

        $layoutControlProps = $layout->getControlProps();

        $title = $layoutControlProps['title'] ?? 'Data';

        $mask = $layoutControlProps['mask'] ?? null;

        $titleLessControls = [
            'checkbox',
        ];

        $chainedCalls = [];

        if (!in_array($controlType, $titleLessControls)) {
            $chainedCalls = [$this->chainableMethodCall('title', [$this->string($title)])];
        }

        $required = $column->isRequired();
        $vertical = $column->isVertical();

        if (!$vertical) {
            $chainedCalls[] = $this->chainableMethodCall('horizontal');
        }

        if ($required) {
            $chainedCalls[] = $this->chainableMethodCall('required');
        }

        $snakedModuleName = Str::snake($this->module->getName());

        if ($controlType === 'input') {
            $subType = $layoutControlProps['subType'] ?? 'text';

            if ($subType !== 'text') {
                $chainedCalls[] = $this->chainableMethodCall('type', [$this->string($subType)]);
            }

            if ($mask) {
                $chainedCalls[] = $this->chainableMethodCall('mask', [$this->string($mask)]);
            }

            return $this->chainableStaticCall('Input', 'make', [
                $this->string("$snakedModuleName.$name"),
            ], $chainedCalls);
        }

        if ($controlType === 'datetime') {
            return $this->chainableStaticCall('DateTimer', 'make', [
                $this->string("$snakedModuleName.$name"),
            ], $chainedCalls);
        }

        if ($controlType === 'textarea') {
            $rows = $layoutControlProps['rows'] ?? 5;

            $chainedCalls[] = $this->chainableMethodCall('rows', [$this->int($rows)]);

            return $this->chainableStaticCall('TextArea', 'make', [
                $this->string("$snakedModuleName.$name"),
            ], $chainedCalls);
        }

        if ($controlType === 'htmlEditor') {
            return $this->chainableStaticCall('Quill', 'make', [
                $this->string("$snakedModuleName.$name"),
            ], $chainedCalls);
        }

        if ($controlType === 'checkbox') {
            $sendUncheckedStateToServer = $layoutControlProps['sendUncheckedStateToServer'] ?? true;

            if ($sendUncheckedStateToServer) {
                $chainedCalls[] = $this->chainableMethodCall('sendTrueOrFalse');
            }

            $chainedCalls[] = $this->chainableMethodCall('placeholder', [$this->string($title)]);

            return $this->chainableStaticCall('CheckBox', 'make', [
                $this->string("$snakedModuleName.$name"),
            ], $chainedCalls);
        }

        if ($controlType === 'select') {
            $columnId = $layoutControlProps['columnId'] ?? null;

            $fromModelValueColumn = null;

            if ($columnId && $columnId !== $column->getId()) {
                $valueColumn = collect($this->module->getBlueprint()->getColumns())->first(static fn (TableColumn $column) => $column->getId() === $columnId);

                if ($valueColumn) {
                    $fromModelValueColumn = $valueColumn->getName();
                }
            }

            $fromModelMethodParams = [
                $this->const($this->module->getName() . '::class'),
                $this->string($name)
            ];

            if ($fromModelValueColumn) {
                $fromModelMethodParams[] = $this->string($fromModelValueColumn);
            }

            $chainedCalls[] = $this->chainableMethodCall('fromModel', $fromModelMethodParams);

            return $this->chainableStaticCall('Select', 'make', [
                $this->string("$snakedModuleName.$name"),
            ], $chainedCalls);
        }

        if ($controlType === 'boolean') {
            $chainedCalls[] = $this->chainableMethodCall('options', [
                $this->arr([
                    $this->assoc($this->string('Yes'), $this->string('Yes')),
                    $this->assoc($this->string('No'), $this->string('No')),
                ])
            ]);

            return $this->chainableStaticCall('RadioButtons', 'make', [
                $this->string("$snakedModuleName.$name")
            ], $chainedCalls);
        }

        if ($controlType === 'relation') {
            if (($layoutControlProps['screen'] ?? 'create') !== 'create') {
                return null;
            }

            $meta = $this->getRelationControlMeta($layoutControlProps);

            if (!$meta) {
                return null;
            }

            $foreignModelName = $meta['foreignModelName'];
            $displayColumnName = $meta['displayColumnName'];
            $oneToManyRelation = $meta['oneToManyRelation'];

            $this->use("App\Models\\$foreignModelName");

            $chainedCalls[] = $this->chainableMethodCall('fromModel', [
                $this->const("$foreignModelName::class"),
                $this->string($displayColumnName)
            ]);

            if ($oneToManyRelation) {
                $chainedCalls[] = $this->chainableMethodCall('multiple');
            }

            $this->usesRelation = true;

            return $this->chainableStaticCall('Relation', 'make', [
                $this->string("$snakedModuleName.$name")
            ], $chainedCalls);
        }

        if ($controlType === 'hasManyRelation') {
            if (($layoutControlProps['screen'] ?? 'create') !== 'create') {
                return null;
            }

            $meta = OrchidRelationHelpers::getHasManyRelationMeta($layoutControlProps, $this->blueprints);

            if (!$meta) {
                return null;
            }

            $modelName = $meta['modelName'];
            $displayColumnName = $meta['displayColumnName'];

            $this->use("App\Models\\$modelName");

            $chainedCalls[] = $this->chainableMethodCall('fromModel', [
                $this->const("$modelName::class"),
                $this->string($displayColumnName)
            ]);

            $chainedCalls[] = $this->chainableMethodCall('multiple');

            $this->usesRelation = true;

            $relationMethodName = lcfirst(Str::studly(Str::plural($modelName)));

            return $this->chainableStaticCall('Relation', 'make', [
                $this->string("$snakedModuleName.$relationMethodName.")
            ], $chainedCalls);
        }

        return null;
    }

    /**
     * @param OrchidColumn $column
     *
     * @return MethodCall|null
     */
    private function buildListScreenColumnStatement(OrchidColumn $column): ?MethodCall
    {
        $name = $column->getName();

        $layout = $column->getLayout();

        $moduleVisibility = $layout->getModuleVisibility();

        $visible = in_array('listing', $moduleVisibility, true);

        if (!$visible) {
            return null;
        }

        $controlType = $layout->getControl();
        $layoutControlProps = $layout->getControlProps();
        $layoutListControlProps = $layout->getListControlProps();

        $title = $layoutControlProps['title'] ?? 'Data';

        $filterable = $column->isFilterable();
        $sortable = $column->isSortable();

        $chainedCalls = [];

        $closureStmts = [];

        if ($sortable) {
            $chainedCalls[] = $this->chainableMethodCall('sort');
        }

        if ($filterable) {
            $filterType = 'FILTER_TEXT';

            if (Str::contains($column->getType(), 'time') || Str::contains($column->getType(), 'date')) {
                $filterType = 'FILTER_DATE';
            }

            if (Str::contains($column->getType(), 'integer')) {
                $filterType = 'FILTER_NUMERIC';
            }

            $chainedCalls[] = $this->chainableMethodCall('filter', [$this->const("TD::$filterType")]);
        }

        $snaked = Str::snake(Str::singular($this->module->getName()));

        $linkValue = $this->propFetch($snaked, $column->getName());

        if ($controlType === 'relation') {
            $copy = $layoutListControlProps['copy'] ?? true;
            $relationProps = $copy ? $layoutControlProps : $layoutListControlProps;

            $meta = $this->getRelationControlMeta($relationProps);

            if (!$meta) {
                return null;
            }

            $displayColumnName = $meta['displayColumnName'];
            $belongsToRelation = $meta['belongsToRelation'];
            $related = $belongsToRelation->getRelated();

            if ($related) {
                $relatedModel = collect($this->blueprints)->first(fn (Blueprint $blueprint) => $blueprint->getId() === $related->getId());
                if ($relatedModel) {
                    $relationMethodName = lcfirst(Str::studly($related->getName()));
                    $linkValue = $this->const("$$snaked->$relationMethodName->$displayColumnName");
                }
            }
        }

        if ($controlType === 'hasManyRelation') {
            $meta = OrchidRelationHelpers::getHasManyRelationMeta($layoutListControlProps, $this->blueprints);

            if (!$meta) {
                return null;
            }

            $modelName = $meta['modelName'];

            $displayColumnName = $meta['displayColumnName'];

            $relationMethodName = lcfirst(Str::studly(Str::plural($modelName)));

            $closureStmts[] = $this->const('/** @noinspection PhpUndefinedFieldInspection */');

            $closureStmts[] = $this->nestedAssign(
                $relationMethodName,
                $this->funcCall('implode', [
                    $this->string(','),
                    $this->methodCall(
                        $this->methodCall(
                            $this->propFetch($snaked, $relationMethodName),
                            'pluck', [
                                $this->string($displayColumnName)
                            ]
                        ),
                        'toArray'
                    )
                ])
            );

            /** @noinspection SpellCheckingInspection */
            $closureStmts[] = $this->nestedAssign(
                $relationMethodName,
                $this->ternary(
                    $this->greaterThan(
                        $this->funcCall('strlen', [
                            $this->var($relationMethodName)
                        ]),
                        $this->int(50)
                    ),
                    $this->concat(
                        $this->funcCall('substr', [
                            $this->var($relationMethodName),
                            $this->int(0),
                            $this->int(50)
                        ]),
                        $this->string('...')
                    ),
                    $this->var($relationMethodName)
                )
            );

            $closureStmts[] = $this->if(
                $this->strictEquals(
                    $this->funcCall('trim', [
                        $this->var($relationMethodName)
                    ]),
                    $this->string('')
                ), [
                    $this->nestedAssign(
                        $this->var($relationMethodName),
                        $this->string('-')
                    )
                ]
            );

            $linkValue = $this->const("$$relationMethodName");
        }

        $closureStmts = array_merge($closureStmts, [
            $this->return(
                $this->chainableStaticCall('Link', 'make', [
                    $linkValue
                ], [
                    $this->chainableMethodCall('route', [
                        $this->string("platform.$snaked.edit"),
                        $this->var($snaked)
                    ])
                ])
            )
        ]);

        $chainedCalls[] = $this->chainableMethodCall('render', [
            $this->closure([$this->param($snaked, Str::singular($this->module->getName()))], $closureStmts)
        ]);

        return $this->chainableStaticCall('TD', 'make', [
            $this->string($name),
            $this->string($title),
        ], $chainedCalls);
    }

    /**
     * @return mixed
     */
    public function getOneToManyRelations(): array
    {
        return $this->oneToManyRelations;
    }

    /**
     * @param mixed $oneToManyRelations
     * @return OrchidLayoutBuilder
     */
    public function setOneToManyRelations(array $oneToManyRelations): OrchidLayoutBuilder
    {
        $this->oneToManyRelations = $oneToManyRelations;
        return $this;
    }

    /**
     * @return string
     */
    public function getLayoutType(): string
    {
        return $this->layoutType;
    }

    /**
     * @param string $layoutType
     * @return OrchidLayoutBuilder
     */
    public function setLayoutType(string $layoutType): OrchidLayoutBuilder
    {
        $this->layoutType = $layoutType;
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
     * @return OrchidLayoutBuilder
     */
    public function setModule(OrchidModule $module): OrchidLayoutBuilder
    {
        $this->module = $module;
        return $this;
    }

    /**
     * @return array
     */
    public function getBlueprints(): array
    {
        return $this->blueprints;
    }

    /**
     * @param array $blueprints
     * @return OrchidLayoutBuilder
     */
    public function setBlueprints(array $blueprints): OrchidLayoutBuilder
    {
        $this->blueprints = $blueprints;
        return $this;
    }

    /**
     * @param $props
     *
     * @return array|null
     */
    private function getRelationControlMeta($props): ?array
    {
        $localModelId = $props['localModelId'] ?? null;
        $foreignModelId = $props['foreignModelId'] ?? null;

        $valueColumnId = $props['valueColumnId'] ?? null;
        $displayColumnId = $props['displayColumnId'] ?? null;

        if (!$localModelId || !$foreignModelId || !$valueColumnId) {
            return null;
        }

        if (!$displayColumnId) {
            $displayColumnId = $valueColumnId;
        }

        $localModel = collect($this->blueprints)->first(static fn (Blueprint $blueprint) => $blueprint->getId() === $localModelId);
        $foreignModel = collect($this->blueprints)->first(static fn (Blueprint $blueprint) => $blueprint->getId() === $foreignModelId);

        if (!$localModel || !$foreignModel) {
            return null;
        }

        $displayColumn = collect($foreignModel->getColumns())->first(fn (TableColumn $c) => $c->getId() === $displayColumnId);
        $valueColumn = collect($foreignModel->getColumns())->first(fn (TableColumn $c) => $c->getId() === $valueColumnId);

        if (!$valueColumn) {
            return null;
        }

        if (!$displayColumn) {
            $displayColumn = $valueColumn;
        }

        $localModelName = Str::studly($localModel->getName());
        $foreignModelName = Str::studly($foreignModel->getName());

        $oneToManyRelation = collect($this->oneToManyRelations)
            ->first(fn (OneToManyRelation $r) => $r->getRelated()->getId() === $foreignModel->getId() && $r->getSource()->getId() === $localModel->getId() && $r->getType() === 'hasMany');

        $belongsToRelation = collect($this->oneToManyRelations)
            ->first(fn (OneToManyRelation $r) => $r->getRelated()->getId() === $foreignModel->getId() && $r->getSource()->getId() === $localModel->getId() && $r->getType() === 'belongsTo');

        $valueColumnName = $valueColumn->getName();
        $displayColumnName = $displayColumn->getName();

        return [
            'localModelName'    => $localModelName,
            'foreignModelName'  => $foreignModelName,
            'valueColumnName'   => $valueColumnName,
            'displayColumnName' => $displayColumnName,
            'oneToManyRelation' => $oneToManyRelation,
            'belongsToRelation' => $belongsToRelation,
        ];
    }
}
