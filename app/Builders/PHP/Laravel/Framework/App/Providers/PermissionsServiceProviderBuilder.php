<?php

namespace App\Builders\PHP\Laravel\Framework\App\Providers;

use App\Builders\PHP\ClassBuilder;
use App\Builders\PHP\MethodBuilder;
use App\Builders\Processors\App\Providers\PermissionsServiceProviderProcessor;

/**
 * Class PermissionsServiceProviderBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Providers
 */
class PermissionsServiceProviderBuilder extends ClassBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        PermissionsServiceProviderProcessor::class,
    ];
    /**
     * @var string
     */
    protected string $filename = 'PermissionServiceProvider.php';
    /**
     * @var string
     */
    protected string $namespace = 'App\Providers';
    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name' => 'PermissionServiceProvider',
        'extend' => 'ServiceProvider'
    ];
    /**
     * @var MethodBuilder
     */
    private MethodBuilder $bootMethodBuilder;

    /**
     * @return PermissionsServiceProviderBuilder
     */
    public function prepare(): PermissionsServiceProviderBuilder
    {
        return $this
            ->instantiateMethodBuilders()
            ->buildUseStatements();
    }

    /**
     * @return PermissionsServiceProviderBuilder
     */
    protected function buildUseStatements(): PermissionsServiceProviderBuilder
    {
        $this
            ->useIlluminateServiceProvider()
            ->useOrchidDashboard()
            ->useOrchidItemPermission();

        return $this;
    }

    /**
     * @return void
     */
    private function useOrchidItemPermission(): void
    {
        $this->use('Orchid\Platform\ItemPermission');
    }

    /**
     * @return PermissionsServiceProviderBuilder
     */
    private function useOrchidDashboard(): PermissionsServiceProviderBuilder
    {
        $this->use('Orchid\Platform\Dashboard');

        return $this;
    }

    /**
     * @return PermissionsServiceProviderBuilder
     */
    private function instantiateMethodBuilders(): PermissionsServiceProviderBuilder
    {
        $this->setBootMethodBuilder($this->getNewMethodBuilder('boot'));

        return $this;
    }

    /**
     * @param MethodBuilder $bootMethodBuilder
     * @return void
     */
    private function setBootMethodBuilder(MethodBuilder $bootMethodBuilder): void
    {
        $this->bootMethodBuilder = $bootMethodBuilder;

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
     * @return $this
     */
    protected function buildClass(): PermissionsServiceProviderBuilder
    {
        $this->addMethodBuilder($this->getBootMethodBuilder());

        return $this;
    }

    /**
     * @return MethodBuilder
     */
    public function getBootMethodBuilder(): MethodBuilder
    {
        return $this->bootMethodBuilder;
    }
}
