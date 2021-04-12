<?php

/** @noinspection ClassConstantCanBeUsedInspection */

namespace App\Builders\PHP\Laravel\Framework\App\Exceptions;

use App\Builders\PHP\ClassBuilder;
use App\Builders\PHP\MethodBuilder;
use App\Builders\PHP\PropertyBuilder;
use Illuminate\Foundation\Exceptions\Handler;
use App\Builders\Processors\App\Exceptions\ExceptionHandlerProcessor;

/**
 * Class HandlerBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Exceptions\HandlerBuilder
 */
class HandlerBuilder extends ClassBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        ExceptionHandlerProcessor::class,
    ];
    /**
     * @var string
     */
    protected string $filename = 'Handler.php';
    /**
     * @var string
     */
    protected string $namespace = 'App\Exceptions';
    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name' => 'Handler',
        'extend' => 'ExceptionHandler',
    ];
    /**
     * @var array
     */
    private array $dontReportExceptions = [];
    /**
     * @var array
     */
    private array $dontFlashFields = [];
    /**
     * @var PropertyBuilder
     */
    private PropertyBuilder $dontFlashPropertyBuilder;
    /**
     * @var PropertyBuilder
     */
    private PropertyBuilder $dontReportPropertyBuilder;
    /**
     * @var MethodBuilder
     */
    private MethodBuilder $registerMethodBuilder;

    /**
     * @return HandlerBuilder
     */
    public function prepare(): HandlerBuilder
    {
        return $this
            ->instantiatePropertyBuilders()
            ->instantiateMethodBuilders()
            ->buildUseStatements()
            ->setDefaults();
    }

    /**
     * @return HandlerBuilder
     */
    private function instantiatePropertyBuilders(): HandlerBuilder
    {
        return $this
            ->setDontFlashPropertyBuilder($this->getNewPropertyBuilder('dontFlash'))
            ->setDontReportPropertyBuilder($this->getNewPropertyBuilder('dontReport'));
    }

    /**
     * @return HandlerBuilder
     */
    private function instantiateMethodBuilders(): HandlerBuilder
    {
        $this->setRegisterMethodBuilder($this->getNewMethodBuilder('register'));

        return $this;
    }

    /**
     * @return HandlerBuilder
     */
    protected function buildUseStatements(): HandlerBuilder
    {
        $this->useExceptionHandler();

        return $this;
    }

    /**
     * @return void
     */
    private function useExceptionHandler(): void
    {
        $this->use(Handler::class, 'ExceptionHandler');

    }

    /**
     * @return HandlerBuilder
     */
    private function setDefaults(): HandlerBuilder
    {
        $this->dontFlash('password');
        $this->dontFlash('password_confirmation');

        $this
            ->getDontFlashPropertyBuilder()
            ->setValue($this->getDontFlashFields())
            ->getDocBuilder()
            ->addCommentLine('A list of the inputs that are never flashed for validation exceptions.')
            ->addVar('array');

        $this
            ->getDontReportPropertyBuilder()
            ->setValue($this->getDontReportExceptions())
            ->getDocBuilder()
            ->addCommentLine('A list of the exception types that are not reported.')
            ->addVar('array');

        $this
            ->getRegisterMethodBuilder()
            ->setReturnType('void')
            ->addStatement($this->comment())
            ->getDocBuilder()
            ->setReturnType('void')
            ->addCommentLine('Register the exception handling callbacks for the application.');

        return $this;
    }

    /**
     * @return bool
     *
     */
    public function build(): bool
    {
        return $this
            ->buildClass()
            ->toDisk();
    }

    /**
     * @return HandlerBuilder|mixed
     *
     */
    protected function buildClass(): HandlerBuilder
    {
        $this->addDontFlashProperty()
            ->addDontReportProperty()
            ->addRegisterMethod();

        return $this;
    }

    /**
     * @return HandlerBuilder
     */
    private function addDontFlashProperty(): HandlerBuilder
    {
        $this->addPropertyBuilder($this->getDontFlashPropertyBuilder());

        return $this;
    }

    /**
     * @return HandlerBuilder
     */
    private function addDontReportProperty(): HandlerBuilder
    {
        $this->addPropertyBuilder($this->getDontReportPropertyBuilder());

        return $this;
    }

    /**
     * @return void
     */
    private function addRegisterMethod(): void
    {
        $this->addMethodBuilder($this->getRegisterMethodBuilder());

    }

    /**
     * @return array
     */
    public function getDontReportExceptions(): array
    {
        return $this->dontReportExceptions;
    }

    /**
     * @return array
     */
    public function getDontFlashFields(): array
    {
        return $this->dontFlashFields;
    }

    /**
     * @param $fieldName
     * @return HandlerBuilder
     */
    public function dontFlash($fieldName): HandlerBuilder
    {
        $this->dontFlashFields[] = $fieldName;

        return $this;
    }

    /**
     * @param $exception
     * @return HandlerBuilder
     */
    public function dontReportException($exception): HandlerBuilder
    {
        $this->dontReportExceptions[] = $exception;

        return $this;
    }

    /**
     * @return MethodBuilder
     */
    public function getRegisterMethodBuilder(): MethodBuilder
    {
        return $this->registerMethodBuilder;
    }

    /**
     * @param MethodBuilder $registerMethodBuilder
     * @return HandlerBuilder
     */
    public function setRegisterMethodBuilder(MethodBuilder $registerMethodBuilder): HandlerBuilder
    {
        $this->registerMethodBuilder = $registerMethodBuilder;

        return $this;
    }

    /**
     * @return PropertyBuilder
     */
    public function getDontFlashPropertyBuilder(): PropertyBuilder
    {
        return $this->dontFlashPropertyBuilder;
    }

    /**
     * @param PropertyBuilder $dontFlashPropertyBuilder
     * @return HandlerBuilder
     */
    public function setDontFlashPropertyBuilder(PropertyBuilder $dontFlashPropertyBuilder): HandlerBuilder
    {
        $this->dontFlashPropertyBuilder = $dontFlashPropertyBuilder;

        return $this;
    }

    /**
     * @return PropertyBuilder
     */
    public function getDontReportPropertyBuilder(): PropertyBuilder
    {
        return $this->dontReportPropertyBuilder;
    }

    /**
     * @param PropertyBuilder $dontReportPropertyBuilder
     * @return HandlerBuilder
     */
    public function setDontReportPropertyBuilder(PropertyBuilder $dontReportPropertyBuilder): HandlerBuilder
    {
        $this->dontReportPropertyBuilder = $dontReportPropertyBuilder;

        return $this;
    }
}
