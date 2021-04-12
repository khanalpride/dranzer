<?php

namespace App\Builders\PHP\Laravel\Framework\App\Providers;

use App\Builders\PHP\ClassBuilder;
use App\Builders\PHP\MethodBuilder;
use App\Builders\PHP\PropertyBuilder;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

/**
 * Class EventServiceProviderBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Providers
 */
class EventServiceProviderBuilder extends ClassBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'EventServiceProvider.php';
    /**
     * @var string
     */
    protected string $namespace = 'App\Providers';

    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name' => 'EventServiceProvider',
        'extend' => 'ServiceProvider'
    ];
    /**
     * @var array
     */
    private array $listenerMap = [];
    /**
     * @var PropertyBuilder
     */
    private PropertyBuilder $listenPropertyBuilder;
    /**
     * @var MethodBuilder
     */
    private MethodBuilder $bootMethodBuilder;

    /**
     * @return EventServiceProviderBuilder
     */
    public function prepare(): EventServiceProviderBuilder
    {
        return $this
            ->instantiatePropertyBuilders()
            ->instantiateMethodBuilders()
            ->buildUseStatements()
            ->setDefaults();
    }

    /**
     * @return EventServiceProviderBuilder
     */
    private function setDefaults(): EventServiceProviderBuilder
    {
        $this->setListenerMap([
            $this->assoc(
                $this->const('Registered::class'),
                $this->arr([
                    $this->const('SendEmailVerificationNotification::class')
                ])
            )
        ]);

        $this
            ->getListenPropertyBuilder()
            ->makeProtected()
            ->getDocBuilder()
            ->addCommentLine('The event listener mappings for the application.')
            ->addVar('array');

        $this->getBootMethodBuilder()
            ->addStatements([
                $this->comment()
            ])
            ->getDocBuilder()
            ->addCommentLine('Register any events for your application.')
            ->setReturnType('void');

        return $this;
    }

    /**
     * @return PropertyBuilder
     */
    public function getListenPropertyBuilder(): PropertyBuilder
    {
        return $this->listenPropertyBuilder;
    }

    /**
     * @param PropertyBuilder $listenPropertyBuilder
     * @return EventServiceProviderBuilder
     */
    public function setListenPropertyBuilder(PropertyBuilder $listenPropertyBuilder): EventServiceProviderBuilder
    {
        $this->listenPropertyBuilder = $listenPropertyBuilder;

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
    protected function buildUseStatements(): EventServiceProviderBuilder
    {
        $this->useRegistered()
            ->useSendEmailVerificationNotification()
            ->useEventServiceProvider();

        return $this;
    }

    /**
     * @return void
     */
    private function useEventServiceProvider(): void
    {
        $this->use(EventServiceProvider::class, 'ServiceProvider');
    }

    /**
     * @return EventServiceProviderBuilder
     */
    private function useSendEmailVerificationNotification(): EventServiceProviderBuilder
    {
        $this->use(SendEmailVerificationNotification::class);

        return $this;
    }

    /**
     * @return EventServiceProviderBuilder
     */
    private function useRegistered(): EventServiceProviderBuilder
    {
        $this->use(Registered::class);

        return $this;
    }

    /**
     * @return EventServiceProviderBuilder
     */
    private function instantiateMethodBuilders(): EventServiceProviderBuilder
    {
        return $this->setBootMethodBuilder($this->getNewMethodBuilder('boot'));
    }

    /**
     * @param MethodBuilder $bootMethodBuilder
     * @return EventServiceProviderBuilder
     */
    private function setBootMethodBuilder(MethodBuilder $bootMethodBuilder): EventServiceProviderBuilder
    {
        $this->bootMethodBuilder = $bootMethodBuilder;

        return $this;
    }

    /**
     * @return EventServiceProviderBuilder
     */
    private function instantiatePropertyBuilders(): EventServiceProviderBuilder
    {
        return $this->setListenPropertyBuilder($this->getNewPropertyBuilder('listen'));
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
     * @return EventServiceProviderBuilder
     */
    protected function buildClass(): EventServiceProviderBuilder
    {
        $this->getListenPropertyBuilder()
            ->setValue($this->getListenerMap());

        return $this
            ->addPropertyBuilder($this->getListenPropertyBuilder())
            ->addMethodBuilder($this->getBootMethodBuilder());
    }

    /**
     * @return array
     */
    public function getListenerMap(): array
    {
        return $this->listenerMap;
    }

    /**
     * @param array $listenerMap
     * @return EventServiceProviderBuilder
     */
    public function setListenerMap(array $listenerMap): EventServiceProviderBuilder
    {
        $this->listenerMap = $listenerMap;
        return $this;
    }

    /**
     * @param $listenTo
     * @param $listener
     * @return $this
     */
    public function addListener($listenTo, $listener): EventServiceProviderBuilder
    {
        $this->listenerMap[$listenTo] = $listener;
        return $this;
    }
}
