<?php

namespace App\Builders\Processors\Modules;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use App\Builders\PHP\CustomClassBuilder;
use Illuminate\Database\Eloquent\Builder;
use App\Builders\PHP\Laravel\ArtisanCommands;
use App\Builders\Processors\PHPBuilderProcessor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Builders\PHP\Laravel\Framework\Database\TableColumn;

/**
 * Class APIModuleProcessor
 * @package App\Builders\Processors\Modules
 */
class APIModuleProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $projectRoot = app('project-dir');

        $apiConfig = app('mutations')->for('api');

        $generateAPI = $apiConfig['generate'];

        if ($generateAPI) {
            $modules = $apiConfig['modules'];

            $this->processAuth($apiConfig, $projectRoot)
                ->buildResourceControllers($modules, $projectRoot);
        }

        $next($builder);

        return true;
    }

    /**
     * @param $apiConfig
     * @param $projectRoot
     * @return APIModuleProcessor
     */
    private function processAuth($apiConfig, $projectRoot): APIModuleProcessor
    {
        $jwtAuth = $apiConfig['jwtAuth'];

        $sanctumAuth = $apiConfig['sanctumAuth'];

        if (!$jwtAuth && !$sanctumAuth) {
            return $this;
        }

        $apiControllersPath = $projectRoot . '/app/Http/Controllers/API';

        File::ensureDirectoryExists($apiControllersPath);

        if ($jwtAuth) {
            File::copy(
                app('static-assets') . '/app/Http/Controllers/API/JWTAuthController.php',
                "$apiControllersPath/AuthController.php"
            );
        }

        if ($sanctumAuth) {
            File::copy(
                app('static-assets') . '/app/Http/Controllers/API/SanctumAuthController.php',
                "$apiControllersPath/AuthController.php"
            );

            ArtisanCommands::add('vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"');
        }

        return $this;
    }

    /**
     * @param $modules
     * @param $projectRoot
     */
    private function buildResourceControllers($modules, $projectRoot): void
    {
        $apiControllersPath = $projectRoot . '/app/Http/Controllers/API';

        File::ensureDirectoryExists($apiControllersPath);

        foreach ($modules as $module) {
            $blueprint = $module['blueprint'];

            $config = $module['config'];

            $modelName = $blueprint->getName();

            $builder = new CustomClassBuilder;

            $className = Str::plural($modelName) . 'Controller';

            $builder
                ->setClassDefinition($className, 'App\Http\Controllers\API', 'Controller')
                ->updateClassDefinition();

            $builder->use("App\Models\\$modelName");

            $builder->use('Orion\Http\Controllers\Controller');

            $disableAuthorization = $config['disableAuthorization'] ?? false;

            if ($disableAuthorization) {
                $builder->use('Orion\Concerns\DisableAuthorization');
                $builder->setTraits(['DisableAuthorization']);
            }

            $builder->addPropertyBuilder(
                $builder
                    ->getNewPropertyBuilder('model')
                    ->setValue($this->const("$modelName::class"))
                    ->makeProtected()
            );

            // Index Display Criteria
            $displayCriteria = $config['displayCriteria'];

            $displayColumns = $displayCriteria['columns'] ?? [];

            $displayColumns = collect($blueprint->getColumns())
                ->filter(static fn (TableColumn $col) => in_array($col->getId(), $displayColumns, true))
                ->map(static fn (TableColumn $col) => $col->getName())
                ->values()
                ->toArray();

            $paginationLimit = $displayCriteria['paginationLimit'] ?? 15;

            if ($paginationLimit !== 15 || count($displayColumns)) {
                $builder->use(Builder::class);
                $builder->use('Orion\Http\Requests\Request');

                $runIndexFQMethodBuilder = $builder->getNewMethodBuilder('runIndexFetchQuery');

                $stmts = [];

                $customPaginationLimit = $paginationLimit !== 15 && is_numeric($paginationLimit);

                if ($customPaginationLimit) {
                    $builder->use(LengthAwarePaginator::class);
                    $stmts[] = $this->inlineAssign('perPage', $this->int($paginationLimit));
                }

                $displayColumnsCount = count($displayColumns);

                $hasDisplayColumns = $displayColumnsCount > 0;

                if ($hasDisplayColumns && count($blueprint->getColumns()) !== $displayColumnsCount) {
                    $stmts[] = $this->return(
                        $this->methodCall('query', 'paginate', [
                            $this->var($customPaginationLimit ? 'perPage' : 'paginationLimit'),
                            $this->arr(collect($displayColumns)->map(fn ($c) => $this->string($c))->toArray())
                        ])
                    );
                } else if ($customPaginationLimit) {
                    $stmts[] = $this->staticCall('parent', 'runIndexFetchQuery', [
                        $this->var('request'),
                        $this->var('query'),
                        $this->var('perPage')
                    ]);
                }

                $runIndexFQMethodBuilder->makeProtected()
                    ->addParameters([
                        $this->param('request', 'Request'),
                        $this->param('query', 'Builder'),
                        $this->param('paginationLimit', 'int'),
                    ])
                    ->addStatements($stmts)
                    ->setReturnType('LengthAwarePaginator')
                    ->getDocBuilder()
                    ->setReturnType('LengthAwarePaginator')
                    ->addCommentLine('Runs the given query for fetching entities in index method.');

                if (count($stmts)) {
                    $builder->addMethodBuilder($runIndexFQMethodBuilder);
                }
            }

            $resourceDisplayCriteria = $config['resourceDisplayCriteria'] ?? [];

            $resourceDisplayColumns = $resourceDisplayCriteria['columns'] ?? [];

            $resourceDisplayColumns = collect($blueprint->getColumns())
                ->filter(static fn (TableColumn $col) => in_array($col->getId(), $resourceDisplayColumns, true))
                ->map(static fn (TableColumn $col) => $col->getName())
                ->values()
                ->toArray();

            if (count($resourceDisplayColumns) && (count($blueprint->getColumns()) !== count($resourceDisplayColumns))) {
                $builder->use('Orion\Http\Requests\Request');
                $builder->use(Builder::class);
                $builder->use(Model::class);

                $runShowFQMethodBuilder = $builder->getNewMethodBuilder('runShowFetchQuery');

                $runShowFQMethodBuilder->makeProtected()
                    ->addParameters([
                        $this->param('request', 'Request'),
                        $this->param('query', 'Builder'),
                        $this->param('key'),
                    ])
                    ->addStatement(
                        $this->return(
                            $this->methodCall(
                                $this->methodCall(
                                    $this->methodCall(
                                        'query',
                                        'select',
                                        [$this->arr(collect($resourceDisplayColumns)->map(fn ($c) => $this->string($c))->toArray())]
                                    ),
                                    'where', [
                                        $this->methodCall('this', 'resolveQualifiedKeyName'),
                                        $this->var('key')
                                    ]
                                ),
                                'firstOrFail'
                            )
                        )
                    )
                    ->setReturnType('Model')
                    ->getDocBuilder()
                    ->setReturnType('Model')
                    ->addCommentLine('Runs the given query for fetching entity in show method.');

                $builder->addMethodBuilder($runShowFQMethodBuilder);
            }

            $builder
                ->setFilename("$className.php")
                ->setOutputDir($apiControllersPath)
                ->toDisk();

            $builder->reset();
        }
    }
}
