<?php

namespace App\Builders\PHP\Laravel\Framework\App\Providers;

use App\Builders\PHP\ClassBuilder;
use App\Builders\PHP\MethodBuilder;
use Illuminate\Support\Facades\Broadcast;

/**
 * Class BroadcastServiceProviderBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Providers
 */
class BroadcastServiceProviderBuilder extends ClassBuilder
{
    /**
     * @var string|null
     */
    protected string $filename = 'BroadcastServiceProvider.php';
    /**
     * @var string|null
     */
    protected string $namespace = 'App\Providers';

    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name' => 'BroadcastServiceProvider',
        'extend' => 'ServiceProvider'
    ];
    /**
     * @var MethodBuilder
     */
    private MethodBuilder $bootMethodBuilder;

    /**
     * @return BroadcastServiceProviderBuilder
     */
    public function prepare(): BroadcastServiceProviderBuilder
    {
        return $this
            ->instantiateMethodBuilders()
            ->buildUseStatements()
            ->setDefaults();
    }

    /**
     * @return BroadcastServiceProviderBuilder
     */
    private function setDefaults(): BroadcastServiceProviderBuilder
    {
        $this
            ->getBootMethodBuilder()
            ->addStatements([
                $this->staticCall('Broadcast', 'routes'),
                $this->nop(),
                $this->include(
                    $this->funcCall('base_path', [
                        $this->string('routes/channels.php')
                    ])
                )
            ])
            ->getDocBuilder()
            ->addCommentLine('Register broadcasting routes.')
            ->setReturnType('void');

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
     * @return $this
     */
    protected function buildUseStatements(): BroadcastServiceProviderBuilder
    {
        $this
            ->useIlluminateServiceProvider()
            ->useBroadcastFacade();

        return $this;
    }

    /**
     * @return void
     */
    private function useBroadcastFacade(): void
    {
        $this->use(Broadcast::class);
    }

    /**
     * @return BroadcastServiceProviderBuilder
     */
    private function instantiateMethodBuilders(): BroadcastServiceProviderBuilder
    {
        return $this->setBootMethodBuilder($this->getNewMethodBuilder('boot'));
    }

    /**
     * @param MethodBuilder $bootMethodBuilder
     * @return BroadcastServiceProviderBuilder
     */
    private function setBootMethodBuilder(MethodBuilder $bootMethodBuilder): BroadcastServiceProviderBuilder
    {
        $this->bootMethodBuilder = $bootMethodBuilder;

        return $this;
    }

    /**
     * @return mixed|void
     */
    public function build(): bool
    {
        return $this
            ->buildClass()
            ->toDisk();
    }

    /**
     * @return BroadcastServiceProviderBuilder
     */
    protected function buildClass(): BroadcastServiceProviderBuilder
    {
        return $this->addMethodBuilder($this->getBootMethodBuilder());
    }
}
