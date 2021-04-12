<?php

namespace App\Builders\PHP\Laravel\Framework\App\Providers;

use App\Builders\PHP\ClassBuilder;
use App\Builders\PHP\MethodBuilder;
use App\Builders\PHP\Constants\PHPStormInspections;
use App\Builders\Processors\App\Providers\AppServiceProviderProcessor;

/**
 * Class AppServiceProviderBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Providers
 */
class AppServiceProviderBuilder extends ClassBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        AppServiceProviderProcessor::class,
    ];
    /**
     * @var string|null
     */
    protected string $filename = 'AppServiceProvider.php';
    /**
     * @var string|null
     */
    protected string $namespace = 'App\Providers';
    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name'   => 'AppServiceProvider',
        'extend' => 'ServiceProvider'
    ];
    /**
     * @var MethodBuilder
     */
    private MethodBuilder $bootMethodBuilder;
    /**
     * @var MethodBuilder
     */
    private MethodBuilder $registerMethodBuilder;
    /**
     * @var array
     */
    private array $methods = [];

    /**
     * @return AppServiceProviderBuilder
     */
    public function prepare(): AppServiceProviderBuilder
    {
        return $this
            ->instantiateMethodBuilders()
            ->buildUseStatements()
            ->setDefaults();
    }

    /**
     * @return AppServiceProviderBuilder
     */
    private function instantiateMethodBuilders(): AppServiceProviderBuilder
    {
        return $this
            ->setBootMethodBuilder($this->getNewMethodBuilder('boot'))
            ->setRegisterMethodBuilder($this->getNewMethodBuilder('register'));
    }

    /**
     * @return $this
     */
    protected function buildUseStatements(): AppServiceProviderBuilder
    {
        $this->useIlluminateServiceProvider();
        return $this;
    }

    /**
     * @return AppServiceProviderBuilder
     */
    private function setDefaults(): AppServiceProviderBuilder
    {
        $this
            ->getBootMethodBuilder()
            ->addSuppressedInspection(PHPStormInspections::PHP_MISSING_RETURN_TYPE_INSPECTION)
            ->getDocBuilder()
            ->addCommentLine('Bootstrap any application services.');

        $this
            ->getRegisterMethodBuilder()
            ->addSuppressedInspection(PHPStormInspections::PHP_MISSING_RETURN_TYPE_INSPECTION)
            ->getDocBuilder()
            ->addCommentLine('Register any application services.')
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
     * @return AppServiceProviderBuilder
     */
    protected function buildClass(): AppServiceProviderBuilder
    {
        return $this->addMethodBuilders([
            $this->getRegisterMethodBuilder(),
            $this->getBootMethodBuilder()
        ]);
    }

    /**
     * @param MethodBuilder $method
     * @return AppServiceProviderBuilder
     */
    public function addMethod(MethodBuilder $method): AppServiceProviderBuilder
    {
        $this->methods[] = $method;
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
     * @return MethodBuilder
     */
    public function getBootMethodBuilder(): MethodBuilder
    {
        return $this->bootMethodBuilder;
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @param array $methods
     * @return AppServiceProviderBuilder
     */
    public function setMethods(array $methods): AppServiceProviderBuilder
    {
        $this->methods = $methods;
        return $this;
    }

    /**
     * @param MethodBuilder $methodBuilder
     * @return AppServiceProviderBuilder
     */
    private function setBootMethodBuilder(MethodBuilder $methodBuilder): AppServiceProviderBuilder
    {
        $this->bootMethodBuilder = $methodBuilder;
        return $this;
    }

    /**
     * @param MethodBuilder $methodBuilder
     * @return AppServiceProviderBuilder
     */
    private function setRegisterMethodBuilder(MethodBuilder $methodBuilder): AppServiceProviderBuilder
    {
        $this->registerMethodBuilder = $methodBuilder;
        return $this;
    }
}
