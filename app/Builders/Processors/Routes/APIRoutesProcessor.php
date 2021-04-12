<?php

namespace App\Builders\Processors\Routes;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\PHP\Laravel\Framework\Routes\APIRoutesBuilder;

/**
 * Class APIRoutesProcessor
 * @package App\Builders\Processors\Routes
 */
class APIRoutesProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $apiGuard = 'auth:api';

        $apiConfig = app('mutations')->for('api');

        $generateAPI = $apiConfig['generate'];

        $sanctumAuth = $apiConfig['sanctumAuth'] ?? true;

        if ($generateAPI && $sanctumAuth) {
            $apiGuard = 'auth:sanctum';
        }

        $jwtAuth = $apiConfig['jwtAuth'];

        if ($jwtAuth) {
            $this->processJWTAuth($builder);
        }

        $this
            ->addDefaultRoutes($builder, $apiGuard)
            ->processModules($builder, $apiConfig);

        $next($builder);

        return true;
    }

    /**
     * @param APIRoutesBuilder $builder
     * @return void
     */
    private function processJWTAuth(APIRoutesBuilder $builder): void
    {
        $builder->use('App\Http\Controllers\API\AuthController');

        $builder->addGroup([
            $builder->newPostRoute('login', [
                'AuthController::class',
                'login'
            ]),
            $builder->newPostRoute('logout', [
                'AuthController::class',
                'logout'
            ]),
            $builder->newPostRoute('refresh', [
                'AuthController::class',
                'refresh'
            ]),
            $builder->newPostRoute('me', [
                'AuthController::class',
                'me'
            ]),
        ], 'auth', 'api');

    }

    /**
     * @param APIRoutesBuilder $builder
     * @param $apiConfig
     * @return void
     */
    private function processModules(APIRoutesBuilder $builder, $apiConfig): void
    {
        $modules = $apiConfig['modules'];

        if (!count($modules)) {
            return;
        }

        $builder->use('Orion\Facades\Orion');

        $orionResourceStmts = [];

        foreach ($modules as $module) {
            $blueprint = $module['blueprint'];

            $blueprintName = Str::studly($blueprint->getName());
            $tableName = Str::snake($blueprint->getTable() ?? $blueprintName);

            $controllerName = Str::plural($blueprintName) . 'Controller';

            $orionResourceStmts[] = $this->staticCallStmt($this->staticCall('Orion', 'resource', [
                $this->string($tableName),
                $this->const($controllerName . '::class'),
            ]));

            $builder->use("App\Http\Controllers\API\\$controllerName");
        }

        $builder->addStatement(
            $this->staticCall('Route', 'group', [
                $this->arr([
                    $this->assoc('as', 'api.')
                ]),
                $this->staticClosure([], $orionResourceStmts)
            ])
        );

    }

    /**
     * @param APIRoutesBuilder $builder
     * @param $apiGuard
     * @return APIRoutesProcessor
     */
    private function addDefaultRoutes(APIRoutesBuilder $builder, $apiGuard): APIRoutesProcessor
    {
        $builder->use(Request::class);

        $builder->addStatement(
            $this->methodCall(
                $this->staticCall('Route', 'middleware', [
                    $this->string($apiGuard)
                ]), 'get', [
                    $this->string('/user'),
                    $this->closure([
                        $this->param('request', 'Request')
                    ],
                        [
                            $this->return(
                                $this->methodCall('request', 'user')
                            )
                        ]
                    )
                ]
            )
        );

        return $this;
    }
}
