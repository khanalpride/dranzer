<?php

namespace App\Builders\PHP\Laravel\Framework\App\Http\Middleware;

use App\Builders\PHP\ClassBuilder;
use Illuminate\Support\Facades\Redis;
use Illuminate\Contracts\Redis\LimiterTimeoutException;

class JobMiddlewareBuilder extends ClassBuilder
{
    /**
     * @var bool
     */
    public static bool $customBuilder = true;
    /**
     * @var string
     */
    protected string $namespace = 'App\Http\Middleware\Jobs';
    /**
     * @var string
     */
    private string $name;

    /**
     * @return bool
     */
    public function build(): bool
    {
        $status = $this
            ->buildClass()
            ->toDisk();

        $this->reset();

        return $status;
    }

    /**
     * @return JobMiddlewareBuilder
     */
    protected function buildClass(): JobMiddlewareBuilder
    {
        $this->classDefinition = ['name' => $this->name];

        $this->updateClassDefinition();

        $this->filename = $this->name . '.php';

        $this->use(Redis::class);
        $this->use(LimiterTimeoutException::class);

        $handleMethodBuilder = $this->getNewMethodBuilder('handle');

        $handleMethodBuilder->addParameters([
            $this->param('job'),
            $this->param('next'),
        ]);

        $handleMethodBuilder->addStatements([
            $this->comment('TODO: Implement middleware...'),
            $this->nop(),
            $this->inlineAssign('key', $this->string('key')),
            $this->nop(),
            $this->chainableStaticCall('Redis', 'throttle', [$this->var('key')], [
                $this->chainableMethodCall('block', [$this->int(5)]),
                $this->chainableMethodCall('then', [
                    $this->staticClosure([], [
                        $this->comment('Lock obtained...'),
                        $this->nopExpr(),
                        $this->funcCallStmt($this->funcCall($this->var('next'), [$this->var('job')]))
                    ], [
                        $this->var('job'),
                        $this->var('next')
                    ]),
                    $this->staticClosure([], [
                        $this->comment('Could not obtain the lock...'),
                        $this->nopExpr(),
                        $this->methodCallStmt($this->methodCall('job', 'release', [$this->int(5)]))
                    ], [$this->var('job')]),
                ])
            ]),
            $this->nop(),
            $this->return($this->int(0))
        ])
            ->setReturnType('int')
            ->getDocBuilder()
            ->addCommentLine('Process the queued job.')
            ->addCommentLine()
            ->addCommentLine('@throws LimiterTimeoutException')
            ->setReturnType('int');

        $this->addMethodBuilder($handleMethodBuilder);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return JobMiddlewareBuilder
     */
    public function setName($name): JobMiddlewareBuilder
    {
        $this->name = $name;
        return $this;
    }
}
