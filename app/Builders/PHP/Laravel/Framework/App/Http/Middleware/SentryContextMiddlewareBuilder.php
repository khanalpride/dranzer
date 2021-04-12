<?php

namespace App\Builders\PHP\Laravel\Framework\App\Http\Middleware;

use Illuminate\Http\Request;
use App\Builders\PHP\ClassBuilder;
use App\Builders\PHP\MethodBuilder;

/**
 * Class SentryContextMiddlewareBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Http\Middleware
 */
class SentryContextMiddlewareBuilder extends ClassBuilder
{
    /**
     * @var bool
     */
    public static bool $customBuilder = true;
    /**
     * @var string
     */
    protected string $filename = 'SentryContext.php';
    /**
     * @var string
     */
    protected string $namespace = 'App\Http\Middleware';
    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name' => 'SentryContext',
    ];
    /**
     * @var MethodBuilder
     */
    private MethodBuilder $handleMethodBuilder;
    /**
     * @var bool
     */
    private bool $attachUserId = true;
    /**
     * @var bool
     */
    private bool $attachUserEmail = true;

    /**
     * @return SentryContextMiddlewareBuilder
     */
    public function prepare(): SentryContextMiddlewareBuilder
    {
        return $this
            ->instantiateMethodBuilders()
            ->buildUseStatements()
            ->setDefaults();
    }

    /**
     * @return SentryContextMiddlewareBuilder
     */
    private function instantiateMethodBuilders(): SentryContextMiddlewareBuilder
    {
        $this->setHandleMethodBuilder($this->getNewMethodBuilder('handle'));

        return $this;
    }

    /**
     * @return SentryContextMiddlewareBuilder
     */
    private function buildUseStatements(): SentryContextMiddlewareBuilder
    {
        $this->useClosure()
            ->useSentryScope();

        return $this;
    }

    /**
     * @return SentryContextMiddlewareBuilder
     */
    private function setDefaults(): SentryContextMiddlewareBuilder
    {
        $context = [];

        $this->use(Request::class);

        if ($this->attachUserId) {
            $context[] = $this->assoc(
                'id',
                $this->funcPropFetch(
                    $this->chainedFuncCalls(
                        [
                            $this->funcCall('auth'),
                            $this->funcCall('user'),
                        ]
                    ),
                    'id',
                )
            );
        }

        if ($this->attachUserEmail) {
            $context[] = $this->assoc(
                'email',
                $this->funcPropFetch(
                    $this->chainedFuncCalls(
                        [
                            $this->funcCall('auth'),
                            $this->funcCall('user'),
                        ]
                    ),
                    'email',
                )
            );
        }

        $this->getHandleMethodBuilder()
            ->addParameter($this->param('request', 'Request', null, false, false, ['docBlockParamType' => 'Request']))
            ->addParameter($this->param('next', 'Closure'))
            ->addStatement(
                $this->if(
                    $this->boolAnd(
                        $this->chainedFuncCalls(
                            [
                                $this->funcCall('auth'),
                                $this->funcCall('check'),
                            ]
                        ),
                        $this->chainedFuncCalls(
                            [
                                $this->funcCall('app'),
                                $this->funcCall(
                                    'bound', [
                                        $this->string('sentry'),
                                    ]
                                ),
                            ]
                        ),
                    ),
                    [
                        $this->funcCallStmt(
                            $this->funcCall(
                                '\Sentry\configureScope', [
                                    $this->staticClosure(
                                        [
                                            $this->param('scope', 'Scope'),
                                        ], [
                                            $this->methodCallStmt(
                                                $this->methodCall(
                                                    'scope', 'setUser', [
                                                        $this->arr($context),
                                                    ]
                                                )
                                            ),
                                        ]
                                    ),
                                ]
                            )
                        ),
                    ]
                )
            )
            ->addStatement($this->nop())
            ->addStatement($this->return($this->funcCall($this->var('next'), [$this->var('request')])));

        return $this;
    }

    /**
     * @return void
     */
    private function useSentryScope(): void
    {
        $this->use('Sentry\State\Scope');
    }

    /**
     *
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
    protected function buildClass(): SentryContextMiddlewareBuilder
    {
        $this->addHandleMethod();

        return $this;
    }

    /**
     * @return void
     */
    private function addHandleMethod(): void
    {
        $this->getHandleMethodBuilder()
            ->disableInspectionSuppression()
            ->getDocBuilder()
            ->addCommentLine('Handle an incoming request.')
            ->setReturnType('mixed');

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
     * @return bool
     */
    public function hasAttachedUserId(): bool
    {
        return $this->attachUserId;
    }

    /**
     * @param bool $attachUserId
     * @return SentryContextMiddlewareBuilder
     */
    public function setAttachUserId(bool $attachUserId): SentryContextMiddlewareBuilder
    {
        $this->attachUserId = $attachUserId;

        return $this;
    }

    /**
     * @param bool $attachUserEmail
     * @return SentryContextMiddlewareBuilder
     */
    public function setAttachUserEmail(bool $attachUserEmail): SentryContextMiddlewareBuilder
    {
        $this->attachUserEmail = $attachUserEmail;

        return $this;
    }

    /**
     * @param MethodBuilder $handleMethodBuilder
     * @return void
     */
    private function setHandleMethodBuilder(MethodBuilder $handleMethodBuilder): void
    {
        $this->handleMethodBuilder = $handleMethodBuilder;

    }
}
