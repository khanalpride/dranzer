<?php

/** @noinspection ClassConstantCanBeUsedInspection */

namespace App\Builders\PHP\Laravel\Framework\App\Http\Middleware;

use App\Builders\PHP\ClassBuilder;
use Illuminate\Routing\Redirector;
use App\Builders\PHP\MethodBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Providers\RouteServiceProvider;
use Illuminate\Contracts\Foundation\Application;

/**
 * Class RedirectIfAuthenticatedBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Http\Middleware
 */
class RedirectIfAuthenticatedBuilder extends ClassBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'RedirectIfAuthenticated.php';
    /**
     * @var string
     */
    protected string $namespace = 'App\Http\Middleware';
    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name' => 'RedirectIfAuthenticated',
    ];
    /**
     * @var MethodBuilder
     */
    private MethodBuilder $handleMethodBuilder;

    /**
     * @return RedirectIfAuthenticatedBuilder
     */
    public function prepare(): RedirectIfAuthenticatedBuilder
    {
        return $this
            ->instantiateMethodBuilders()
            ->buildUseStatements()
            ->setDefaults();
    }

    /**
     * @return RedirectIfAuthenticatedBuilder
     */
    private function instantiateMethodBuilders(): RedirectIfAuthenticatedBuilder
    {
        $this->setHandleMethodBuilder($this->getNewMethodBuilder('handle'));

        return $this;
    }

    /**
     * @return RedirectIfAuthenticatedBuilder
     */
    protected function buildUseStatements(): RedirectIfAuthenticatedBuilder
    {
        $this->useClosure()
            ->useAuthFacade()
            ->useIlluminateHttpRequest()
            ->useRouteServiceProvider()
            /**
             *
             * Return types of the handle method.
             * If a processor modifies the return type,
             * make sure these are removed (by the processor)
             * if no longer required.
             *
             */
            ->use(Application::class)
            ->use(RedirectResponse::class)
            ->use(Redirector::class);

        return $this;
    }

    /**
     * @return RedirectIfAuthenticatedBuilder
     */
    private function setDefaults(): RedirectIfAuthenticatedBuilder
    {
        $this->getHandleMethodBuilder()
            ->addParameter($this->param('request', 'Request'))
            ->addParameter($this->param('next', 'Closure'))
            ->addParameter($this->param('guards', null, null, false, true))
            ->addStatement(
                $this->inlineAssign(
                    'guards', $this->ternary(
                    $this->funcCall(
                        'empty', [
                            $this->var('guards'),
                        ]
                    ),
                    $this->arr(
                        [
                            $this->const('null'),
                        ]
                    ),
                    $this->var('guards')
                )
                )
            )
            ->addStatement($this->nop())
            ->addStatement(
                $this->forEach(
                    $this->var('guards'),
                    $this->var('guard'),
                    [
                        $this->if(
                            $this->methodCall(
                                $this->staticCall(
                                    'Auth', 'guard', [
                                        $this->var('guard'),
                                    ]
                                ), 'check'
                            ), [
                                $this->return(
                                    $this->funcCall(
                                        'redirect', [
                                            $this->const('RouteServiceProvider::HOME'),
                                        ]
                                    )
                                ),
                            ]
                        ),
                    ]
                )
            )
            ->addStatement($this->nop())
            ->addStatement($this->return($this->funcCall($this->var('next'), [$this->var('request')])))
            ->disableInspectionSuppression()
            ->getDocBuilder()
            ->addCommentLine('Handle an incoming request.')
            ->setReturnType('Application|RedirectResponse|Redirector|mixed');

        return $this;
    }

    /**
     * @return RedirectIfAuthenticatedBuilder
     */
    private function useAuthFacade(): RedirectIfAuthenticatedBuilder
    {
        $this->use(Auth::class);

        return $this;
    }

    /**
     * @return RedirectIfAuthenticatedBuilder
     */
    private function useRouteServiceProvider(): RedirectIfAuthenticatedBuilder
    {
        $this->use(RouteServiceProvider::class);

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
     * @return RedirectIfAuthenticatedBuilder
     */
    protected function buildClass(): RedirectIfAuthenticatedBuilder
    {
        $this->addHandleMethod();

        return $this;
    }

    /**
     * @return void
     */
    private function addHandleMethod(): void
    {
        $this->addMethodBuilder($this->getHandleMethodBuilder());
    }

    /**
     * @return MethodBuilder
     */
    public function getHandleMethodBuilder(): MethodBuilder
    {
        return $this->handleMethodBuilder;
    }

    /**
     * @param MethodBuilder $methodBuilder
     * @return void
     */
    private function setHandleMethodBuilder(MethodBuilder $methodBuilder): void
    {
        $this->handleMethodBuilder = $methodBuilder;

    }
}
