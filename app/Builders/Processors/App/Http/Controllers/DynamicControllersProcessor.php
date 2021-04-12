<?php

namespace App\Builders\Processors\App\Http\Controllers;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Contracts\View\Factory;
use App\Builders\Processors\PHPBuilderProcessor;
use Illuminate\Contracts\Foundation\Application;
use App\Builders\PHP\Laravel\Framework\Database\Blueprint;
use App\Builders\PHP\Laravel\Framework\Database\TableColumn;
use App\Builders\PHP\Laravel\Framework\App\Http\Controllers\DynamicControllersBuilder;

/**
 * Class DynamicControllersProcessor
 * @package App\Builders\Processors\App\Http\Controllers
 */
class DynamicControllersProcessor extends PHPBuilderProcessor
{
    /**
     * @var string
     */
    private string $projectRoot;

    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $this->projectRoot = app('project-dir');

        $dbMutations = app('mutations')->for('database');

        $blueprints = $dbMutations['blueprints'];

        $eloquentRelations = $dbMutations['eloquent']['relations']['all'];

        $webControllers = app('mutations')->for('controllers')['webControllers'];

        foreach ($webControllers as $webController) {
            $this->buildController($builder, $webController, collect($blueprints), collect($eloquentRelations));
        }

        $next($builder);

        return true;
    }

    /**
     * @param DynamicControllersBuilder $builder
     * @param $controller
     * @param Collection $blueprints
     * @param Collection $eloquentRelations
     * @return void
     */
    private function buildController(DynamicControllersBuilder $builder, $controller, Collection $blueprints, Collection $eloquentRelations): void
    {
        $builder->setUseDefaultTraits(false);

        $controllerId = $controller['id'] ?? null;
        $controllerName = $controller['name'] ?? null;
        $controllerStmts = $controller['stmts'] ?? [];

        if (!$controllerId || !($controllerName && trim($controllerName) !== '')) {
            return;
        }

        $defaultControllerPath = 'app/Http/Controllers';

        $controllerNS = 'App\Http\Controllers';

        $path = $controller['path'] ?? $defaultControllerPath;

        $isResourceController = $controller['isRC'] ?? false;

        $isSingleActionController = $controller['isSAC'] ?? false;

        if (!$isResourceController && !Str::endsWith($controllerName, 'Controller')) {
            $controllerName .= 'Controller';
        }

        if ($isResourceController) {
            if (!Str::endsWith($controllerName, 'Controller')) {
                $controllerName = Str::studly(Str::plural($controllerName)) . 'Controller';
            } else {
                $controllerName = Str::studly($controllerName);
            }
        }

        $builder
            ->setClassDefinition($controllerName, $controllerNS, 'Controller')
            ->updateClassDefinition();

        if ($isResourceController) {
            $methods = $controller['selectedMethods'] ?? [];
            $resourceModel = $controller['resourceModel'] ?? null;

            $resourceModel = $blueprints->first(fn (Blueprint $blueprint) => $blueprint->getId() === $resourceModel);

            $resourceModelVar = null;
            $resourceModelName = null;

            if ($resourceModel) {
                $resourceModelName = Str::studly($resourceModel->getName());
                $resourceModelVar = lcfirst($resourceModelName);
                $builder->use("App\Models\\$resourceModelName");
            }

            $resourceMethods = [
                'store',
                'update',
                'destroy',
                'index',
                'create',
                'edit',
                'show'
            ];

            $createStore = in_array('store', $methods, true);
            $createUpdate = in_array('update', $methods, true);
            $createDestroy = in_array('destroy', $methods, true);
            $createIndex = in_array('index', $methods, true);
            $createCreate = in_array('create', $methods, true);
            $createEdit = in_array('edit', $methods, true);
            $createShow = in_array('show', $methods, true);

            if ($createStore || $createUpdate) {
                $builder->use(Request::class);
            }

            $customMethods = array_diff($methods, $resourceMethods);

            if ($createIndex) {
                $methodBuilder = $builder->getResourceMethodBuilder('index', false);
                $methodBuilder
                    ->getDocBuilder()
                    ->addCommentLine('Display a listing of the resource.');

                $builder->addMethodBuilder($methodBuilder);
            }

            if ($createCreate) {
                $methodBuilder = $builder->getResourceMethodBuilder('create', false);
                $methodBuilder
                    ->getDocBuilder()
                    ->addCommentLine('Show the form for creating a new resource.');

                $builder->addMethodBuilder($methodBuilder);
            }

            if ($createEdit) {
                $methodBuilder = $builder->getResourceMethodBuilder('edit', false);
                $methodBuilder
                    ->getDocBuilder()
                    ->addCommentLine('Show the form for editing the specified resource.');

                $builder->addMethodBuilder($methodBuilder);
            }

            if ($createShow) {
                $methodBuilder = $builder->getResourceMethodBuilder('show', false, $resourceModelVar, $resourceModelName);
                $methodBuilder
                    ->getDocBuilder()
                    ->addCommentLine('Display the specified resource.');

                $builder->addMethodBuilder($methodBuilder);
            }

            if ($createStore) {
                $methodBuilder = $builder->getResourceMethodBuilder('store');
                $methodBuilder
                    ->getDocBuilder()
                    ->addCommentLine('Store a newly created resource in storage.');

                $builder->addMethodBuilder($methodBuilder);
            }

            if ($createUpdate) {
                $methodBuilder = $builder->getResourceMethodBuilder('update', true, $resourceModelVar, $resourceModelName);
                $methodBuilder
                    ->getDocBuilder()
                    ->addCommentLine('Update the specified resource in storage.');

                $builder->addMethodBuilder($methodBuilder);
            }

            if ($createDestroy) {
                $methodBuilder = $builder->getResourceMethodBuilder('destroy', false, $resourceModelVar, $resourceModelName);
                $methodBuilder
                    ->getDocBuilder()
                    ->addCommentLine('Remove the specified resource from storage.');

                $builder->addMethodBuilder($methodBuilder);
            }

            foreach ($customMethods as $customMethod) {
                if (!$customMethod || trim($customMethod) === '') {
                    continue;
                }

                $methodBuilder = $builder->getResourceMethodBuilder($customMethod);
                $builder->addMethodBuilder($methodBuilder);
            }
        }

        if ($isSingleActionController) {
            $indexMethodBuilder = $builder->getNewMethodBuilder('__invoke');
            $indexMethodBuilder
                ->addParameter(
                    $this->param('request', 'Request')
                )
                ->addStatement($this->comment());

            $builder->addMethodBuilder($indexMethodBuilder);
        }

        $views = collect(app('mutations')->for('frontend')['views'])
            ->filter(fn ($v) => $v['controller'] && $v['controller'] === ($controller['id'] ?? null));

        $controllerStmts = collect($controllerStmts)->map(function ($props, $methodName) use ($views) {
            $view = $views->first(fn ($v) => str_replace('show', '', $methodName) === Str::studly($v['name']));
            $props['view'] = $view;
            return $props;
        });

        $docComments = [];

        foreach ($controllerStmts as $methodName => $props) {
            $passable = [];

            $methodBuilder = $builder->getNewMethodBuilder($methodName);

            $view = $props['view'] ?? null;

            $modelStmts = $props['modelStmts'] ?? [];

            $typeHint = $modelStmts['typeHint'] ?? [];

            $statements = $modelStmts['stmts'] ?? [];

            foreach ($typeHint as $hint) {
                $model = $blueprints->first(fn (Blueprint $blueprint) => $blueprint->getId() === $hint);
                if ($model) {
                    $modelName = $model->getName();

                    $builder->use("App\Models\\$modelName");

                    $var = lcfirst($modelName);

                    if (!in_array($var, $passable, true)) {
                        $passable[] = $var;
                    }

                    $methodBuilder->addParameter(
                        $this->param(lcfirst($modelName), $modelName)
                    );
                }
            }

            $hasWithCall = false;

            foreach ($statements as $statement) {
                $type = $statement['type'];
                $model = $statement['model'] ?? null;

                $model = $blueprints->first(fn (Blueprint $blueprint) => $blueprint->getId() === $model);

                // Currently, only schema statements are supported.
                if (!$model) {
                    continue;
                }

                $modelName = $model->getName();

                if ($type === 'pluck') {
                    $columns = $model->getColumns();
                    $column = $statement['column'] ?? null;

                    $column = collect($columns)->first(fn (TableColumn $tableColumn) => $tableColumn->getId() === $column);

                    if ($column) {
                        $statement['pluckColumn'] = $column->getName() ?? 'column';
                        $statement['var'] = lcfirst($modelName) . (Str::plural(Str::studly($statement['pluckColumn'])));
                    }
                }

                $stmtContainer = $this->prepareControllerStmt($type, $statement, $modelName, $blueprints, $eloquentRelations);

                $imports = $stmtContainer['imports'];

                /** @noinspection SlowArrayOperationsInLoopInspection */
                $passable = array_merge($passable, $stmtContainer['viewCallParams'] ?? []);

                $stmt = $stmtContainer['stmt'] ?? null;

                $hasWithCall = $stmtContainer['hasWithCall'];

                if ($stmt) {
                    collect($imports)->each(fn ($i) => $builder->use($i));
                    $methodBuilder->addStatement($stmt);
                    $methodBuilder->addStatement($this->nop());
                }
            }

            if (count($passable) && !in_array('@noinspection PhpUndefinedMethodInspection', $docComments, true)) {
                $docComments[] = '@noinspection PhpUndefinedMethodInspection';
            }

            if ($view) {
                $viewName = $view['name'] ?? null;

                $builder->use(View::class);

                $viewCallParams = [
                    $this->string("pages.$viewName")
                ];

                if (count($passable)) {
                    $viewCallParams[] = $this->funcCall(
                        'compact',
                        collect($passable)->map(fn ($p) => $this->string($p))->toArray(),
                    );
                }

                $methodBuilder->addStatement(
                    $this->return(
                        $this->funcCall('view', $viewCallParams)
                    )
                )
                    ->setReturnType('View')
                    ->getDocBuilder()
                    ->addCommentLine("Render $viewName.blade.php")
                    ->setReturnType('View');
            }

            if (!$hasWithCall && count($docComments)) {
                $methodBuilder->getDocBuilder()
                    ->addCommentLine();

                foreach ($docComments as $docComment) {
                    $methodBuilder->getDocBuilder()
                        ->addCommentLine($docComment);
                }
            }

            $builder->addMethodBuilder($methodBuilder);
        }

        $views = $views->filter(fn ($v) => !$controllerStmts->first(fn ($s) => ($s['view'] ?? false) && $s['view']['name'] === $v['name']));

        foreach ($views as $view) {
            $viewName = $view['name'] ?? null;

            if (!$viewName) {
                continue;
            }

            $builder->use(View::class);

            $methodName = Str::studly($viewName);

            $methodName = "show$methodName";

            $methodBuilder = $builder->getNewMethodBuilder($methodName);

            $methodBuilder
                ->addStatement(
                    $this->return(
                        $this->funcCall('view', [
                            $this->string("pages.$viewName")
                        ])
                    )
                )
                ->setReturnType('View')
                ->getDocBuilder()
                ->addCommentLine("Render $viewName.blade.php");

            $builder->addMethodBuilder($methodBuilder);
        }

        $path = Str::startsWith($path, '/') ? $path : '/' . $path;

        if (trim($path, '/') !== 'app/Http/Controllers') {
            $builder->use(Controller::class);
        }

        $outputDir = $this->projectRoot . $path;

        File::ensureDirectoryExists($outputDir);

        $builder
            ->setOutputDir($outputDir)
            ->setFilename("$controllerName.php")
            ->buildUseStatements()
            ->buildTraits()
            ->toDisk();

        $builder->reset();
    }

    /**
     * @param $type
     * @param $props
     * @param $modelName
     * @param Collection $blueprints
     * @param Collection $eloquentRelations
     * @return array
     */
    private function prepareControllerStmt($type, $props, $modelName, Collection $blueprints, Collection $eloquentRelations): array
    {
        $stmtContainer = [
            'imports'        => [],
            'viewCallParams' => [],
            'hasWithCall'    => false,
            'stmt'           => null,
        ];

        $with = $props['with'] ?? [];

        // Pluck method prefixes model name and use it if it's passed.
        $var = $props['var'] ?? lcfirst(Str::plural($modelName));

        $stmtContainer['imports'][] = "App\Models\\$modelName";

        if (!in_array($var, $stmtContainer['viewCallParams'], true)) {
            $stmtContainer['viewCallParams'][] = $var;
        }

        $withRelations = [];

        if (count($with)) {
            foreach ($with as $relation) {
                $sourceId = $relation['source'];
                $relatedId = $relation['related'];
                $relationType = $relation['type'] ?? null;

                $eloquentRelation = $eloquentRelations
                    ->first(fn ($rel) => $rel['source']['id'] === $sourceId && $rel['related']['id'] === $relatedId);

                if (!$relationType || !$eloquentRelation) {
                    continue;
                }

                $related = $blueprints->first(fn (Blueprint $blueprint) => $blueprint->getId() === $relatedId);

                if (!$related) {
                    continue;
                }

                $relatedModelName = $related->getName();

                if ($relationType === 'hasOne' || $relationType === 'belongsTo') {
                    $relatedModelName = lcfirst($relatedModelName);
                }

                if ($relationType === 'hasMany' || $relationType === 'hasManyThrough' || $relationType === 'manyToMany') {
                    $relatedModelName = lcfirst(Str::pluralStudly($relatedModelName));
                }

                if (!in_array($relatedModelName, $withRelations, true)) {
                    $withRelations[] = $relatedModelName;
                }
            }
        }

        if (count($withRelations)) {
            $withRelations = collect($withRelations)->map(fn ($w) => $this->string($w))->toArray();

            if ($type !== 'pluck') {
                $assignment = $this->chainableStaticCall($modelName, 'with', [
                    $this->arr($withRelations)
                ], [
                    $this->chainableMethodCall($type)
                ]);
            } else {
                $pluckColumn = $props['pluckColumn'] ?? 'column';
                $assignment = $this->chainableStaticCall($modelName, 'with', [
                    $this->arr($withRelations)
                ], [
                    $this->chainableMethodCall('take', [$this->int(10)]),
                    $this->chainableMethodCall('get'),
                    $this->chainableMethodCall('pluck', [$this->string($pluckColumn)]),
                ]);
            }

            $stmtContainer['hasWithCall'] = true;
        } else if ($type !== 'pluck') {
            $assignment = $this->staticCall($modelName, $type);
        } else {
            $pluckColumn = $props['pluckColumn'] ?? 'column';
            $assignment = $this->chainableStaticCall($modelName, 'take', [$this->int(10)], [
                $this->chainableMethodCall('get'),
                $this->chainableMethodCall('pluck', [$this->string($pluckColumn)]),
            ]);
        }

        $stmtContainer['stmt'] = $this->inlineAssign(
            $this->var($var),
            $assignment
        );

        return $stmtContainer;
    }

}
