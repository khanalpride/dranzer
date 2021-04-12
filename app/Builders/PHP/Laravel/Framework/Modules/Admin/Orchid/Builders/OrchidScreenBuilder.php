<?php

/**
 * @noinspection PhpUnused
 * @noinspection UnknownInspectionInspection
 */

namespace App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\Builders;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Builders\PHP\ClassBuilder;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\OrchidModule;
use App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\Helpers\OrchidRelationHelpers;

/**
 * Class OrchidScreenBuilder
 * @package App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\Builders
 */
class OrchidScreenBuilder extends ClassBuilder
{
    /**
     * @var bool
     */
    public static bool $customBuilder = true;
    /**
     * @var string
     */
    protected string $namespace = 'App\Orchid\Screens';
    /**
     * @var OrchidModule
     */
    private OrchidModule $module;
    /**
     * @var array
     */
    private array $blueprints;
    /**
     * @var mixed|string
     */
    private $screenType;
    /**
     * @var mixed|string
     */
    private string $screenName;

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
     * @return $this
     */
    protected function buildClass(): OrchidScreenBuilder
    {
        $moduleName = $this->module->getName();

        $this->classDefinition = [
            'name'      => $this->screenName,
            'extend'    => 'Screen',
            'namespace' => 'App\Orchid\Screens'
        ];

        $this->updateClassDefinition();

        $title = Str::plural($moduleName) ?? 'Incomplete Screen';

        $desc = $this->screenType === 'listing'
            ? 'View and manage ' . strtolower(Str::plural($moduleName))
            : 'Update or delete a ' . strtolower($moduleName);

        $this->use('Orchid\Screen\Screen');

        $this
            ->addNameProperty($title)
            ->addDescriptionProperty($desc)
            ->addQueryMethod()
            ->addCommandBarMethod()
            ->addLayoutMethod($this->module);

        if ($this->screenType !== 'listing') {
            $existsProp = $this->getNewPropertyBuilder('exists');
            $existsProp->makePrivate();
            $this->addPropertyBuilder($existsProp);

            $resourceProp = $this->getNewPropertyBuilder(Str::snake($moduleName));
            $resourceProp->makePrivate();
            $this->addPropertyBuilder($resourceProp);
        }

        return $this;
    }

    /**
     * @return OrchidScreenBuilder
     */
    private function addCommandBarMethod(): OrchidScreenBuilder
    {
        $buttons = [];

        $module = $this->module;

        $screenType = $this->screenType;

        $moduleName = $this->module->getName();

        $snaked = Str::snake($moduleName);

        $pluralSnaked = Str::snake(Str::plural($moduleName));

        if ($screenType === 'listing') {
            $this->use('Orchid\Screen\Actions\Link');

            $buttons[] = $this->chainableStaticCall('Link', 'make', [$this->string('New ' . Str::singular($moduleName))], [
                $this->chainableMethodCall('icon', [$this->string('pencil')]),
                $this->chainableMethodCall('route', [$this->string('platform.' . $snaked . '.edit')]),
            ]);
        } else {
            $this->use('Orchid\Screen\Actions\Button');

            $buttons[] = $this->chainableStaticCall('Button', 'make', [$this->string('Create ' . Str::singular($moduleName))], [
                $this->chainableMethodCall('icon', [$this->string('pencil')]),
                $this->chainableMethodCall('method', [$this->string('createOrUpdate')]),
                $this->chainableMethodCall('canSee', [$this->boolNot($this->propFetch('this', 'exists'))]),
            ]);

            $buttons[] = $this->chainableStaticCall('Button', 'make', [$this->string('Update ' . Str::singular($moduleName))], [
                $this->chainableMethodCall('icon', [$this->string('note')]),
                $this->chainableMethodCall('method', [$this->string('createOrUpdate')]),
                $this->chainableMethodCall('canSee', [$this->propFetch('this', 'exists')]),
            ]);

            $buttons[] = $this->chainableStaticCall('Button', 'make', [$this->string('Delete ' . Str::singular($moduleName))], [
                $this->chainableMethodCall('icon', [$this->string('trash')]),
                $this->chainableMethodCall('method', [$this->string('delete')]),
                $this->chainableMethodCall('canSee', [$this->propFetch('this', 'exists')]),
            ]);

            $this->use(Request::class);
            $this->use('Orchid\Support\Facades\Alert');

            $createOrUpdateMethod = $this->getNewMethodBuilder('createOrUpdate');
            $createOrUpdateMethod->addParameters([
                $this->param($snaked, $moduleName),
                $this->param('request', 'Request'),
            ])
                ->addStatements([
                    $this->methodCall(
                        $this->methodCall($snaked, 'fill', [
                            $this->methodCall('request', 'get', [$this->string($snaked)])
                        ]),
                        'save'
                    ),
                ]);

            $columns = $module->getColumns();

            $imports = [];

            $referencesHasManyRelation = false;

            $hasManyRelationMethodNames = [];

            foreach ($columns as $column) {
                $layout = $column->getLayout();
                $layoutControlProps = $layout->getControlProps();

                $controlType = $layout->getControl();

                if ($controlType !== 'hasManyRelation') {
                    continue;
                }

                $meta = OrchidRelationHelpers::getHasManyRelationMeta($layoutControlProps, $this->blueprints);

                if (!$meta) {
                    continue;
                }

                $modelName = $meta['modelName'];

                $singularRelationMethodName = lcfirst(Str::studly(Str::singular($modelName)));
                $relationMethodName = lcfirst(Str::studly(Str::plural($modelName)));

                if (!in_array("App\Models\\$modelName", $imports, true)) {
                    $imports[] = "App\Models\\$modelName";
                }

                $createOrUpdateMethod->addStatements([
                    $this->nop(),
                    $this->inlineAssign(
                        $singularRelationMethodName . 'Ids',
                        $this->coalesce(
                            $this->arrayFetch(
                                $this->methodCall('request', 'input', [$this->string($snaked)]),
                                $this->string($relationMethodName)
                            ),
                            $this->arr([])
                        )
                    ),
                    $this->inlineAssign(
                        $relationMethodName,
                        $this->methodCall('this', 'getReplicatedHasManyRelatedModels', [
                            $this->var($singularRelationMethodName . 'Ids'),
                            $this->const("$modelName::class")
                        ])
                    ),
                    $this->methodCall('this', 'syncRelation', [
                        $this->string($relationMethodName),
                        $this->var($relationMethodName),
                        $this->var($snaked),
                    ]),
                    $this->nop(),
                ]);

                $referencesHasManyRelation = true;

                $hasManyRelationMethodNames[] = $relationMethodName;
            }

            $createOrUpdateMethod
                ->addStatements([
                    $this->staticCall('Alert', 'info', [$this->string($moduleName . ' updated successfully.')]),
                    $this->return(
                        $this->chainedFuncCalls([
                            $this->funcCall('redirect'),
                            $this->funcCall('route', [$this->string('platform.' . $pluralSnaked)])
                        ])
                    )
                ]);

            collect($imports)->each(fn ($i) => $this->use($i));
            $this->use(RedirectResponse::class);

            $createOrUpdateMethod->setReturnType('RedirectResponse')
                ->getDocBuilder()
                ->addCommentLine("Handle creating or updating $moduleName.")
                ->addCommentLine()
                ->addCommentLine('@noinspection PhpUnused')
                ->setReturnType('RedirectResponse');

            $this->addMethodBuilder($createOrUpdateMethod);

            if ($referencesHasManyRelation) {
                $this->use(Collection::class);

                $methodBuilder = $this->getNewMethodBuilder('syncRelation');
                $methodBuilder->addParameters([
                    $this->param('relationName', 'string'),
                    $this->param('newModels', 'Collection'),
                    $this->param($snaked, $moduleName),
                ])
                    ->setReturnType($this->screenName)
                    ->addStatements([
                        $this->const("$$snaked->{\$relationName}()->delete()"),
                        $this->const("$$snaked->{\$relationName}()->saveMany(\$newModels)"),
                        $this->nop(),
                        $this->return(
                            $this->var('this')
                        )
                    ])
                    ->getDocBuilder()
                    ->addCommentLine("Sync a hasMany relation with $moduleName.")
                    ->setReturnType($this->screenName);

                $this->addMethodBuilder($methodBuilder);

                $methodBuilder = $this->getNewMethodBuilder('getReplicatedHasManyRelatedModels');
                $methodBuilder->addParameters([
                    $this->param('ids', 'array'),
                    $this->param('modelClass', 'string'),
                ])
                    ->setReturnType('Collection')
                    ->addStatement(
                        $this->return(
                            $this->methodCall(
                                $this->methodCall(
                                    $this->funcCall('collect', [
                                        $this->var('ids')
                                    ]),
                                    'map', [
                                        $this->closure(
                                            [
                                                $this->var('id')
                                            ],
                                            [
                                                $this->nestedAssign(
                                                    'model',
                                                    $this->staticCall($this->var('modelClass'), 'find', [$this->var('id')])
                                                ),
                                                $this->return(
                                                    $this->ternary(
                                                        $this->var('model'),
                                                        $this->methodCall('model', 'replicate'),
                                                        $this->const('null')
                                                    )
                                                )
                                            ],
                                            [
                                                $this->var('modelClass')
                                            ])
                                    ]
                                ),
                                'filter', [
                                    $this->closure([$this->var('model')], [
                                        $this->return(
                                            $this->strictNotEquals(
                                                $this->var('model'),
                                                $this->const('null')
                                            )
                                        )
                                    ])
                                ]
                            )
                        )
                    )
                    ->getDocBuilder()
                    ->addCommentLine("Returns a collection of models to be synced with $moduleName.")
                    ->addCommentLine()
                    ->addCommentLine('@noinspection PhpUndefinedMethodInspection')
                    ->setReturnType('Collection');

                $this->addMethodBuilder($methodBuilder);
            }

            $deleteMethod = $this->getNewMethodBuilder('delete');
            $deleteMethod->addParameters([
                $this->param($snaked, $moduleName),
            ])
                ->addStatement(
                    $this->methodCall($snaked, 'delete'),
                );

            if ($referencesHasManyRelation && count($hasManyRelationMethodNames)) {
                collect($hasManyRelationMethodNames)
                    ->each(function ($methodName) use ($snaked, $deleteMethod) {
                        $deleteMethod
                            ->addStatement(
                                $this->const("$$snaked->$methodName()->delete()"),
                            );
                    });
            }

            $deleteMethod->addStatements([
                $this->nop(),
                $this->staticCall('Alert', 'info', [$this->string($moduleName . ' deleted successfully.')]),
                $this->return(
                    $this->chainedFuncCalls([
                        $this->funcCall('redirect'),
                        $this->funcCall('route', [$this->string('platform.' . $pluralSnaked)])
                    ])
                )
            ]);

            $comment = count($hasManyRelationMethodNames) ? "Delete $moduleName and its associated relations." : "Delete $moduleName.";

            $deleteMethod->setReturnType('RedirectResponse')
                ->getDocBuilder()
                ->addCommentLine($comment)
                ->addCommentLine()
                ->addCommentLine('@throws Exception')
                ->setReturnType('RedirectResponse');

            $this->use('Exception');

            $this->addMethodBuilder($deleteMethod);
        }

        $builder = $this->getNewMethodBuilder('commandBar')
            ->setReturnType('array')
            ->addStatement($this->return($this->arr($buttons)));

        $builder
            ->getDocBuilder()
            ->setReturnType('array')
            ->addCommentLine('Button commands');

        $this->addMethodBuilder($builder);

        return $this;
    }

    /**
     * @param $desc
     * @return OrchidScreenBuilder
     */
    private function addDescriptionProperty($desc): OrchidScreenBuilder
    {
        $builder = $this->getNewPropertyBuilder('description');
        $builder->makePublic()
            ->setValue($desc);

        $this->addPropertyBuilder($builder);

        return $this;
    }

    /**
     * @param OrchidModule $module
     * @return void
     */
    private function addLayoutMethod(OrchidModule $module): void
    {
        $moduleName = $module->getName();

        if ($this->screenType === 'listing') {
            $this->use("App\Orchid\Layouts\\{$moduleName}ListLayout");
        } else {
            $this->use("App\Orchid\Layouts\\{$moduleName}EditLayout");
        }

        $layoutClasses = [];

        if ($this->screenType === 'listing') {
            $layoutClasses[] = $this->const("{$moduleName}ListLayout::class");
        } else {
            $layoutClasses[] = $this->new_(
                "{$moduleName}EditLayout",
                [$this->chainedPropFetches('this', [$this->const(Str::snake($moduleName))], ['terminate' => false])]
            );
        }

        $builder = $this
            ->getNewMethodBuilder('layout')
            ->setReturnType('array')
            ->addStatement($this->return($this->arr($layoutClasses)));

        $builder
            ->getDocBuilder()
            ->setReturnType('array')
            ->addCommentLine('Render the screen');

        $this->addMethodBuilder($builder);

    }

    /**
     * @param $title
     * @return OrchidScreenBuilder
     */
    private function addNameProperty($title): OrchidScreenBuilder
    {
        $builder = $this->getNewPropertyBuilder('name');

        $builder
            ->makePublic()
            ->setValue($title);

        $this->addPropertyBuilder($builder);

        return $this;
    }

    /**
     * @return OrchidScreenBuilder
     */
    private function addQueryMethod(): OrchidScreenBuilder
    {
        $moduleName = $this->module->getName();

        $snaked = Str::snake($moduleName);

        $tableName = Str::snake(Str::plural($moduleName));

        $this->use("App\Models\\$moduleName");

        $screenType = $this->screenType;

        $stmts = [];

        if ($screenType === 'listing') {
            $stmts[] = $this->return(
                $this->arr([
                    $this->assoc($tableName, $this->chainableStaticCall($moduleName, 'filters', [], [
                        $this->chainableMethodCall('paginate')
                    ]))
                ])
            );
        } else {
            $stmts[] = $this->inlineAssign($this->propFetch('this', $snaked), $this->var($snaked));

            $stmts[] = $this->nop();

            $stmts[] = $this->inlineAssign($this->propFetch('this', 'exists'), $this->propFetch($snaked, 'exists'));

            $stmts[] = $this->nop();
            $stmts[] = $this->nop();

            $stmts[] = $this->if($this->propFetch($snaked, 'exists'), [
                $this->nestedAssign($this->propFetch('this', 'name'), $this->string('Edit ' . $moduleName))
            ]);

            $stmts[] = $this->nop();

            $stmts[] = $this->return(
                $this->arr([
                    $this->assoc($snaked, $this->var($snaked))
                ])
            );
        }

        $builder = $this->getNewMethodBuilder('query')
            ->setReturnType('array')
            ->addStatements($stmts);

        if ($screenType !== 'listing') {
            $builder->addParameter(
                $this->param($snaked, $moduleName)
            );
        }

        $builder
            ->getDocBuilder()
            ->setReturnType('array')
            ->addCommentLine('Query the model associated with this model.');

        $this->addMethodBuilder($builder);

        return $this;
    }

    /**
     * @return mixed|string
     */
    public function getScreenName(): string
    {
        return $this->screenName;
    }

    /**
     * @param mixed|string $screenName
     * @return OrchidScreenBuilder
     */
    public function setScreenName(string $screenName): OrchidScreenBuilder
    {
        $this->screenName = $screenName;
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
     * @return OrchidScreenBuilder
     */
    public function setBlueprints(array $blueprints): OrchidScreenBuilder
    {
        $this->blueprints = $blueprints;
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
     * @return OrchidScreenBuilder
     */
    public function setModule(OrchidModule $module): OrchidScreenBuilder
    {
        $this->module = $module;
        return $this;
    }

    /**
     * @return mixed|string
     */
    public function getScreenType(): string
    {
        return $this->screenType;
    }

    /**
     * @param mixed|string $screenType
     * @return OrchidScreenBuilder
     */
    public function setScreenType(string $screenType): OrchidScreenBuilder
    {
        $this->screenType = $screenType;
        return $this;
    }
}
