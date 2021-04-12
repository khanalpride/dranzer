<?php

namespace App\Builders\PHP\Laravel\Framework\App\Providers;

use App\Builders\PHP\ClassBuilder;
use App\Builders\PHP\MethodBuilder;
use App\Builders\PHP\ClassConstBuilder;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use App\Builders\Processors\App\Providers\RouteServiceProviderProcessor;

/**
 * Class RouteServiceProviderBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Providers
 */
class RouteServiceProviderBuilder extends ClassBuilder
{
    /**
     * @var array|string[] $processors
     */
    protected array $processors = [
        RouteServiceProviderProcessor::class,
    ];
    /**
     * @var string|null
     */
    protected string $filename = 'RouteServiceProvider.php';
    /**
     * @var string|null
     */
    protected string $namespace = 'App\Providers';
    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name'   => 'RouteServiceProvider',
        'extend' => 'ServiceProvider'
    ];
    /**
     * @var null
     */
    private $home = '/';
    /**
     * @var ClassConstBuilder
     */
    private ClassConstBuilder $homeConstBuilder;
    /**
     * @var MethodBuilder
     */
    private MethodBuilder $bootMethodBuilder;

    /**
     * @return RouteServiceProviderBuilder
     */
    public function prepare(): RouteServiceProviderBuilder
    {
        return $this
            ->instantiateConstantBuilders()
            ->instantiateMethodBuilders()
            ->buildUseStatements()
            ->setDefaults();
    }

    /**
     * @return RouteServiceProviderBuilder
     */
    private function instantiateConstantBuilders(): RouteServiceProviderBuilder
    {
        return $this->setHomeConstBuilder($this->getNewClassConstBuilder('HOME'));
    }

    /**
     * @return RouteServiceProviderBuilder
     */
    private function instantiateMethodBuilders(): RouteServiceProviderBuilder
    {
        return $this->setBootMethodBuilder($this->getNewMethodBuilder('boot'));
    }

    /**
     * @return $this
     */
    protected function buildUseStatements(): RouteServiceProviderBuilder
    {
        $this
            ->useIlluminateRouteFacade()
            ->useRouteServiceProvider();

        return $this;
    }

    /**
     * @return void
     */
    private function useRouteServiceProvider(): void
    {
        $this->use(RouteServiceProvider::class, 'ServiceProvider');
    }

    /**
     * @return RouteServiceProviderBuilder
     */
    private function setDefaults(): RouteServiceProviderBuilder
    {
        $this
            ->getHomeConstBuilder()
            ->makePublic()
            ->getDocBuilder()
            ->addCommentLine('The path to the "home" route for your application.')
            ->addCommentLine()
            ->addCommentLine('This is used by Laravel authentication to redirect users after login.')
            ->addVar('string');

        $this->getBootMethodBuilder()
            ->addStatements([
                $this->methodCall('this', 'routes', [
                    $this->closure([], [
                        $this->methodCallStmt(
                            $this->methodCall(
                                $this->staticCall('Route', 'middleware', [
                                    $this->string('web')
                                ]),
                                'group', [
                                    $this->funcCall('base_path', [
                                        $this->string('routes/web.php')
                                    ])
                                ]
                            ),
                        ),
                        $this->methodCallStmt(
                            $this->methodCall(
                                $this->staticCall('Route', 'prefix', [
                                    $this->string('api')
                                ]),
                                'group', [
                                    $this->funcCall('base_path', [
                                        $this->string('routes/api.php')
                                    ])
                                ]
                            )
                        )
                    ])
                ]),
            ])
            ->getDocBuilder()
            ->addCommentLine('Define your route model bindings, pattern filters, etc.')
            ->setReturnType('void');

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
     * @return RouteServiceProviderBuilder
     */
    protected function buildClass(): RouteServiceProviderBuilder
    {
        return $this
            ->addHomeConst()
            ->addBootMethod();
    }

    /**
     * @return RouteServiceProviderBuilder
     */
    private function addBootMethod(): RouteServiceProviderBuilder
    {
        $this->addMethodBuilder($this->getBootMethodBuilder());

        return $this;
    }

    /**
     * @return RouteServiceProviderBuilder
     */
    private function addHomeConst(): RouteServiceProviderBuilder
    {
        $builder = $this
            ->getHomeConstBuilder()
            ->setValue(
                $this->string($this->home)
            );

        $this->addClassConst($builder);

        return $this;
    }

    /**
     * @return MethodBuilder
     */
    public function getBootMethodBuilder(): MethodBuilder
    {
        return $this->bootMethodBuilder;
    }

    /**
     * @return string|null
     */
    public function getHome(): ?string
    {
        return $this->home;
    }

    /**
     * @param $home
     * @return RouteServiceProviderBuilder
     */
    public function setHome($home): RouteServiceProviderBuilder
    {
        $this->home = $home;
        return $this;
    }

    /**
     * @return ClassConstBuilder
     */
    public function getHomeConstBuilder(): ClassConstBuilder
    {
        return $this->homeConstBuilder;
    }

    /**
     * @param ClassConstBuilder $homeConstBuilder
     * @return RouteServiceProviderBuilder
     */
    public function setHomeConstBuilder(ClassConstBuilder $homeConstBuilder): RouteServiceProviderBuilder
    {
        $this->homeConstBuilder = $homeConstBuilder;

        return $this;
    }

    /**
     * @param MethodBuilder $methodBuilder
     * @return RouteServiceProviderBuilder
     */
    private function setBootMethodBuilder(MethodBuilder $methodBuilder): RouteServiceProviderBuilder
    {
        $this->bootMethodBuilder = $methodBuilder;
        return $this;
    }
}
