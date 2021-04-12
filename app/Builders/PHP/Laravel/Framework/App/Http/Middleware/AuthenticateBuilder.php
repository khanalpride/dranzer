<?php

/** @noinspection ClassConstantCanBeUsedInspection */

namespace App\Builders\PHP\Laravel\Framework\App\Http\Middleware;

use App\Builders\PHP\ClassBuilder;
use App\Builders\PHP\MethodBuilder;
use Illuminate\Auth\Middleware\Authenticate;

/**
 * Class AuthenticateBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Http\Middleware
 */
class AuthenticateBuilder extends ClassBuilder
{
    /**
     * @var string|null
     */
    protected string $filename = 'Authenticate.php';
    /**
     * @var string|null
     */
    protected string $namespace = 'App\Http\Middleware';
    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name'   => 'Authenticate',
        'extend' => 'Middleware',
    ];
    /**
     * @var MethodBuilder
     */
    private MethodBuilder $redirectToMethodBuilder;

    /**
     * @return AuthenticateBuilder
     */
    public function prepare(): AuthenticateBuilder
    {
        return $this
            ->instantiateMethodBuilders()
            ->buildUseStatements()
            ->setDefaults();
    }

    /**
     * @return AuthenticateBuilder
     */
    private function instantiateMethodBuilders(): AuthenticateBuilder
    {
        $this->setRedirectToMethodBuilder($this->getNewMethodBuilder('redirectTo'));

        return $this;
    }

    /**
     * @return AuthenticateBuilder
     */
    protected function buildUseStatements(): AuthenticateBuilder
    {
        $this->useMiddleware();

        return $this;
    }

    /**
     * @return AuthenticateBuilder
     */
    private function setDefaults(): AuthenticateBuilder
    {
        $this
            ->getRedirectToMethodBuilder()
            ->addParameter(
                $this->param('request')
            )
            ->addStatement(
                $this->if(
                    $this->boolNot(
                        $this->methodCall('request', 'expectsJson')
                    ),
                    [
                        $this->return(
                            $this->funcCall(
                                'route', [$this->string('login')]
                            )
                        ),
                    ]
                )
            )
            ->addStatement($this->nop())
            ->addStatement(
                $this->return(
                    $this->null()
                )
            )
            ->getDocBuilder()
            ->addCommentLine('Get the path the user should be redirected to when they are not authenticated.')
            ->setReturnType('string|null');

        return $this;
    }

    private function useMiddleware(): void
    {
        $this->use(Authenticate::class, 'Middleware');
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
     * @return AuthenticateBuilder
     */
    protected function buildClass(): AuthenticateBuilder
    {
        $this->addRedirectToMethod();

        return $this;
    }

    /**
     * @return void
     */
    private function addRedirectToMethod(): void
    {
        $this->addMethodBuilder($this->getRedirectToMethodBuilder());

    }

    /**
     * @return MethodBuilder
     */
    public function getRedirectToMethodBuilder(): MethodBuilder
    {
        return $this->redirectToMethodBuilder;
    }

    /**
     * @param $methodBuilder
     * @return void
     */
    private function setRedirectToMethodBuilder(MethodBuilder $methodBuilder): void
    {
        $this->redirectToMethodBuilder = $methodBuilder;

    }
}
