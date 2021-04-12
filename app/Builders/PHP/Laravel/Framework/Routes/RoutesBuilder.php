<?php

namespace App\Builders\PHP\Laravel\Framework\Routes;

use App\Builders\PHP\FileBuilder;
use Illuminate\Support\Facades\Route;
use App\Builders\PHP\Parser\Extensions\Stmt\StaticCall;
use App\Builders\PHP\Parser\Extensions\Stmt\ChainedStaticCall;

/**
 * Class RoutesBuilder
 * @package App\Builders\PHP\Laravel\Framework\Routes
 */
abstract class RoutesBuilder extends FileBuilder
{
    /**
     * @var array $resources
     */
    private $resources = [];
    /**
     * @var array $stmts
     */
    private $stmts = [];
    /**
     * @var array $routes
     */
    private $routes = [];
    /**
     * @var array $groups
     */
    private $groups = [];
    /**
     * @var array $includes
     */
    private $includes = [];

    /**
     * @return RoutesBuilder
     */
    public function prepare(): RoutesBuilder
    {
        return $this->useRouteFacade();
    }

    /**
     * @return $this
     */
    protected function useRouteFacade(): RoutesBuilder
    {
        $this->use(Route::class);

        return $this;
    }

    /**
     * @return $this
     */
    protected function processGroups(): RoutesBuilder
    {
        // Separate the group statements from the route statements.
        if (count($this->groups)) {
            $this->stmt($this->nop());
        }

        foreach ($this->groups as $group) {
            $prefix = $group['prefix'];
            $middleware = $group['middleware'];
            $routes = $group['routes'];

            $options = [];

            if ($prefix) {
                $options[] = $this->assoc('prefix', $this->string($prefix));
            }

            if ($middleware) {
                $options[] = $this->assoc('middleware', $this->string($middleware));
            }

            $routeStmts = [];

            if (count($routes)) {
                foreach ($routes as $route) {
                    $routeStmts[] = $this->routeToStatement($route);
                }
            }

            $this
                ->stmt(
                    $this->staticCall('Route', 'group', [
                        $this->arr($options),
                        $this->staticClosure([], $routeStmts)
                    ])
                )
                ->stmt($this->nop()); // Append a newline after every group.
        }

        return $this;
    }

    /**
     * @return RoutesBuilder
     */
    private function processIncludes(): RoutesBuilder
    {
        foreach ($this->includes as $include) {
            $this->stmt($this->nop());
            $this->stmt($include);
        }

        return $this;
    }

    /**
     * @return RoutesBuilder
     */
    protected function processResourceRoutes(): RoutesBuilder
    {
        if (count($this->resources)) {
            $this->stmt($this->nop());
        }

        foreach ($this->resources as $resource => $controller) {
            $this->stmt($this->staticCall('Route', 'resource', [
                $this->string($resource),
                $this->string($controller),
            ]));
        }

        return $this;
    }

    /**
     * @return RoutesBuilder
     */
    private function processRoutes(): RoutesBuilder
    {
        foreach ($this->routes as $route) {
            $this->stmt($this->routeToStatement($route));
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function processStatements(): RoutesBuilder
    {
        // Separate the route statements from the use statements.
        if (count($this->stmts)) {
            $this->stmt($this->nop());
        }

        foreach ($this->stmts as $stmt) {
            $this
                ->stmt($stmt)
                ->stmt($this->nop()); // Append a newline after every route.
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function build(): bool
    {
        return $this
            ->processStatements()
            ->processResourceRoutes()
            ->processRoutes()
            ->processGroups()
            ->processIncludes()
            ->toDisk();
    }

    /**
     * @param $path
     * @param $action
     * @param array $middlewares
     * @return $this
     */
    public function addGetRoute($path, $action, $middlewares = []): RoutesBuilder
    {
        $this->addRoute('get', $path, $action, $middlewares);

        return $this;
    }

    /**
     * @param array $routes
     * @param null $prefix
     * @param null $middleware
     * @param null $namespace
     * @return RoutesBuilder
     */
    public function addGroup(array $routes, $prefix = null, $middleware = null, $namespace = null): RoutesBuilder
    {
        $this->groups[] = [
            'routes'     => $routes,
            'prefix'     => $prefix,
            'namespace'  => $namespace,
            'middleware' => $middleware,
        ];

        return $this;
    }

    /**
     * @param $include
     * @return $this
     */
    public function addInclude($include): RoutesBuilder
    {
        $this->includes[] = $include;

        return $this;
    }

    /**
     * @param $path
     * @param $action
     * @param array $middlewares
     * @return $this
     */
    public function addPostRoute($path, $action, $middlewares = []): RoutesBuilder
    {
        $this->addRoute('post', $path, $action, $middlewares);

        return $this;
    }

    /**
     * @param $resource
     * @param $controller
     * @param array $options
     * @return RoutesBuilder
     */
    public function addResource($resource, $controller, $options = []): RoutesBuilder
    {
        $this->resources[$resource] = [
            'controller' => $controller,
            'options'    => $options,
        ];

        return $this;
    }

    /**
     * @param $verb
     * @param $path
     * @param $action
     * @param array $middlewares
     * @return RoutesBuilder
     */
    public function addRoute($verb, $path, $action, $middlewares = []): RoutesBuilder
    {
        $this->routes[] = $this->newRoute($verb, $path, $action, $middlewares);

        return $this;
    }

    /**
     * @param $stmt
     * @return RoutesBuilder
     */
    public function addStatement($stmt): RoutesBuilder
    {
        $this->stmts[] = $stmt;

        return $this;
    }

    /**
     * @return array
     */
    public function getIncludes(): array
    {
        return $this->includes;
    }

    /**
     * @param array $includes
     * @return RoutesBuilder
     */
    public function setIncludes(array $includes): RoutesBuilder
    {
        $this->includes = $includes;
        return $this;
    }

    /**
     * @return array
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    /**
     * @param $verb
     * @param $path
     * @param $action
     * @param array $middlewares
     * @return array
     */
    public function newRoute($verb, $path, $action, $middlewares = []): array
    {
        if (is_array($action)) {
            $action = [
                'controller' => $action[0],
                'method'     => $action[1],
            ];
        }

        return [
            'verb'        => $verb,
            'path'        => $path,
            'action'      => $action,
            'middlewares' => $middlewares,
        ];
    }

    /**
     * @param $path
     * @param $action
     * @param array $middlewares
     * @return array
     */
    public function newGetRoute($path, $action, $middlewares = []): array
    {
        return $this->newRoute('get', $path, $action, $middlewares);
    }

    /**
     * @param $path
     * @param $action
     * @param array $middlewares
     * @return array
     */
    public function newPostRoute($path, $action, $middlewares = []): array
    {
        return $this->newRoute('post', $path, $action, $middlewares);
    }

    /**
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @param array $routes
     * @return RoutesBuilder
     */
    public function setRoutes(array $routes): RoutesBuilder
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * @return array
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @param array $groups
     * @return RoutesBuilder
     */
    public function setGroups(array $groups): RoutesBuilder
    {
        $this->groups = $groups;

        return $this;
    }

    /**
     * @param $route
     * @return ChainedStaticCall|StaticCall
     */
    private function routeToStatement($route)
    {
        $verb = $route['verb'];
        $path = $route['path'];
        $action = $route['action'];
        $middlewares = $route['middlewares'];

        $controller = null;
        $method = null;

        if (is_array($action) && array_key_exists('controller', $action)) {
            $controller = $action['controller'];
            $method = $action['method'];
        }

        $args = [$this->string($path)];

        if ($controller && $method) {
            $args[] = $this->arr([
                $this->const($controller),
                $this->string($method),
            ]);
        } else {
            $args[] = $this->string($action);
        }

        $middlewareCall = null;

        if (count($middlewares)) {
            $middlewareCall = $this->chainableMethodCall('middleware', [
                $this->arr(
                    collect($middlewares)
                        ->map(fn ($middleware) => $this->string($middleware))
                        ->toArray()
                )
            ]);
        }

        return $middlewareCall
            ? $this->chainableStaticCallStmt(
                $this->chainableStaticCall('Route', $verb, $args, [$middlewareCall])
            )
            : $this->staticCallStmt(
                $this->staticCall('Route', $verb, $args)
            );
    }
}
