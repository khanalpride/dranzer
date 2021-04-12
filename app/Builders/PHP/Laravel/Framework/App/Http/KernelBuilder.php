<?php

namespace App\Builders\PHP\Laravel\Framework\App\Http;

use Fruitcake\Cors\HandleCors;
use App\Builders\PHP\ClassBuilder;
use PhpParser\Node\Expr\ConstFetch;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\Authenticate;
use App\Builders\PHP\PropertyBuilder;
use Illuminate\Foundation\Http\Kernel;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Auth\Middleware\RequirePassword;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use App\Builders\Processors\App\Http\HttpKernelProcessor;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

/**
 * Class KernelBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Http\KernelBuilder
 */
class KernelBuilder extends ClassBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        HttpKernelProcessor::class,
    ];
    /**
     * @var string|null
     */
    protected string $filename = 'Kernel.php';
    /**
     * @var string|null
     */
    protected string $namespace = 'App\Http';
    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name'   => 'Kernel',
        'extend' => 'HttpKernel',
    ];
    /**
     * @var array
     */
    private array $middlewareList = [];
    /**
     * @var array
     */
    private array $middlewareGroupsList = [];
    /**
     * @var array
     */
    private array $routeMiddlewareList = [];
    /**
     * @var array
     */
    private array $apiMiddlewareGroups = [];
    /**
     * @var PropertyBuilder
     */
    private PropertyBuilder $middlewarePropertyBuilder;
    /**
     * @var PropertyBuilder
     */
    private PropertyBuilder $middlewareGroupsPropertyBuilder;
    /**
     * @var PropertyBuilder
     */
    private PropertyBuilder $routeMiddlewarePropertyBuilder;

    /**
     * @return KernelBuilder
     */
    public function prepare(): KernelBuilder
    {
        return $this
            ->instantiatePropertyBuilders()
            ->buildUseStatements()
            ->setDefaults();
    }

    /**
     * @return KernelBuilder
     */
    private function instantiatePropertyBuilders(): KernelBuilder
    {
        return $this
            ->setMiddlewarePropertyBuilder(
                $this->getNewPropertyBuilder('middleware')
            )
            ->setMiddlewareGroupsPropertyBuilder(
                $this->getNewPropertyBuilder('middlewareGroups')
            )
            ->setRouteMiddlewarePropertyBuilder(
                $this->getNewPropertyBuilder('routeMiddleware')
            );
    }

    /**
     * @return KernelBuilder
     */
    protected function buildUseStatements(): KernelBuilder
    {
        $this
            ->use(Kernel::class, 'HttpKernel')
            ->use(TrustProxies::class)
            ->use(HandleCors::class)
            ->use(PreventRequestsDuringMaintenance::class)
            ->use(EncryptCookies::class)
            ->use(AddQueuedCookiesToResponse::class)
            ->use(StartSession::class)
            ->use(ShareErrorsFromSession::class)
            ->use(VerifyCsrfToken::class)
            ->use(SubstituteBindings::class)
            ->use(Authenticate::class)
            ->use(AuthenticateWithBasicAuth::class)
            ->use(SetCacheHeaders::class)
            ->use(Authorize::class)
            ->use(RedirectIfAuthenticated::class)
            ->use(RequirePassword::class)
            ->use(ValidateSignature::class)
            ->use(ThrottleRequests::class)
            ->use(EnsureEmailIsVerified::class);

        return $this;
    }

    /**
     * @return KernelBuilder
     */
    private function setDefaults(): KernelBuilder
    {
        $this->addMiddleware($this->const("TrustProxies::class"))
            ->addMiddleware($this->const("HandleCors::class"))
            ->addMiddleware($this->const("PreventRequestsDuringMaintenance::class"));

        $this->addMiddlewareGroup(
            'web', [
                $this->const("EncryptCookies::class"),
                $this->const("AddQueuedCookiesToResponse::class"),
                $this->const("StartSession::class"),
                $this->const("ShareErrorsFromSession::class"),
                $this->const("VerifyCsrfToken::class"),
                $this->const("SubstituteBindings::class"),
            ]
        )
            ->addMiddlewareGroup(
                'api', array_merge(
                    [
                        $this->string('throttle:60,1'),
                        $this->const("SubstituteBindings::class"),
                    ], $this->apiMiddlewareGroups
                )
            );

        $this->addRouteMiddleware('auth', $this->const("Authenticate::class"))
            ->addRouteMiddleware('auth.basic', $this->const("AuthenticateWithBasicAuth::class"))
            ->addRouteMiddleware('cache.headers', $this->const("SetCacheHeaders::class"))
            ->addRouteMiddleware('can', $this->const("Authorize::class"))
            ->addRouteMiddleware('guest', $this->const("RedirectIfAuthenticated::class"))
            ->addRouteMiddleware('password.confirm', $this->const("RequirePassword::class"))
            ->addRouteMiddleware('signed', $this->const("ValidateSignature::class"))
            ->addRouteMiddleware('throttle', $this->const("ThrottleRequests::class"))
            ->addRouteMiddleware('verified', $this->const("EnsureEmailIsVerified::class"));

        // Default Properties
        $this->getMiddlewarePropertyBuilder()
            ->makeProtected()
            ->getDocBuilder()
            ->addCommentLine("The application's global HTTP middleware stack.")
            ->addCommentLine()
            ->addCommentLine('These middleware are run during every request to your application.')
            ->addVar('array');

        $this->getMiddlewareGroupsPropertyBuilder()
            ->makeProtected()
            ->getDocBuilder()
            ->addCommentLine("The application's route middleware groups.")
            ->addCommentLine()
            ->addCommentLine('These middleware may be assigned to groups or used individually.')
            ->addVar('array');

        $this->getRouteMiddlewarePropertyBuilder()
            ->makeProtected()
            ->getDocBuilder()
            ->addCommentLine("The application's route middleware.")
            ->addCommentLine()
            ->addCommentLine('These middleware may be assigned to groups or used individually.')
            ->addVar('array');

        return $this;
    }

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
     * @return KernelBuilder
     */
    protected function buildClass(): KernelBuilder
    {
        $this->addMiddlewareProperty()
            ->addMiddlewareGroupsProperty()
            ->addRouteMiddlewareProperty();

        return $this;
    }

    /**
     * @param ConstFetch $middlewareClass
     * @return KernelBuilder
     */
    public function addMiddleware(ConstFetch $middlewareClass): KernelBuilder
    {
        $this->middlewareList[] = $middlewareClass;
        return $this;
    }

    /**
     * @param string $key
     * @param array $middlewareList
     * @return KernelBuilder
     */
    public function addMiddlewareGroup(string $key, array $middlewareList): KernelBuilder
    {
        $this->middlewareGroupsList[$key] = $middlewareList;
        return $this;
    }

    /**
     * @return KernelBuilder
     */
    private function addMiddlewareGroupsProperty(): KernelBuilder
    {
        $this->getMiddlewareGroupsPropertyBuilder()
            ->setValue($this->getMiddlewareGroupsList());

        return $this->addPropertyBuilder($this->getMiddlewareGroupsPropertyBuilder());
    }

    /**
     * @return KernelBuilder
     */
    private function addMiddlewareProperty(): KernelBuilder
    {
        $this->getMiddlewarePropertyBuilder()
            ->setValue($this->getMiddlewareList());

        $this->addPropertyBuilder($this->getMiddlewarePropertyBuilder());

        return $this;
    }

    /**
     * @param string $route
     * @param ConstFetch $middlewareClass
     * @return KernelBuilder
     */
    public function addRouteMiddleware(string $route, ConstFetch $middlewareClass): KernelBuilder
    {
        $this->routeMiddlewareList[$route] = $middlewareClass;
        return $this;
    }

    /**
     * @return void
     */
    private function addRouteMiddlewareProperty(): void
    {
        $this->getRouteMiddlewarePropertyBuilder()
            ->setValue($this->getRouteMiddlewareList());

        $this->addPropertyBuilder($this->getRouteMiddlewarePropertyBuilder());
    }

    /**
     * @return PropertyBuilder
     */
    public function getRouteMiddlewarePropertyBuilder(): PropertyBuilder
    {
        return $this->routeMiddlewarePropertyBuilder;
    }

    /**
     * @param PropertyBuilder $routeMiddlewarePropertyBuilder
     * @return KernelBuilder
     */
    public function setRouteMiddlewarePropertyBuilder(PropertyBuilder $routeMiddlewarePropertyBuilder): KernelBuilder
    {
        $this->routeMiddlewarePropertyBuilder = $routeMiddlewarePropertyBuilder;
        return $this;
    }

    /**
     * @return PropertyBuilder
     */
    public function getMiddlewareGroupsPropertyBuilder(): PropertyBuilder
    {
        return $this->middlewareGroupsPropertyBuilder;
    }

    /**
     * @param PropertyBuilder $middlewareGroupsPropertyBuilder
     * @return KernelBuilder
     */
    public function setMiddlewareGroupsPropertyBuilder(PropertyBuilder $middlewareGroupsPropertyBuilder): KernelBuilder
    {
        $this->middlewareGroupsPropertyBuilder = $middlewareGroupsPropertyBuilder;
        return $this;
    }

    /**
     * @return PropertyBuilder
     */
    public function getMiddlewarePropertyBuilder(): PropertyBuilder
    {
        return $this->middlewarePropertyBuilder;
    }

    /**
     * @param PropertyBuilder $middlewarePropertyBuilder
     * @return KernelBuilder
     */
    public function setMiddlewarePropertyBuilder(PropertyBuilder $middlewarePropertyBuilder): KernelBuilder
    {
        $this->middlewarePropertyBuilder = $middlewarePropertyBuilder;
        return $this;
    }

    /**
     * @return array
     */
    public function getMiddlewareList(): array
    {
        return $this->middlewareList;
    }

    /**
     * @return array
     */
    public function getMiddlewareGroupsList(): array
    {
        return $this->middlewareGroupsList;
    }

    /**
     * @return array
     */
    public function getRouteMiddlewareList(): array
    {
        return $this->routeMiddlewareList;
    }

    /**
     * @param array $apiMiddlewareGroups
     * @return KernelBuilder
     */
    public function setApiMiddlewareGroups(array $apiMiddlewareGroups): KernelBuilder
    {
        $this->apiMiddlewareGroups = $apiMiddlewareGroups;
        return $this;
    }
}
