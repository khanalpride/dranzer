<?php

namespace App\Builders\PHP\Laravel\Framework\App\Providers;

use App\Builders\PHP\ClassBuilder;
use App\Builders\PHP\MethodBuilder;
use App\Builders\PHP\PropertyBuilder;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;

/**
 * Class AuthServiceProviderBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Providers
 */
class AuthServiceProviderBuilder extends ClassBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'AuthServiceProvider.php';
    /**
     * @var string
     */
    protected string $namespace = 'App\Providers';
    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name' => 'AuthServiceProvider',
        'extend' => 'ServiceProvider'
    ];
    /**
     * @var array
     */
    private array $policies = [];
    /**
     * @var MethodBuilder
     */
    private MethodBuilder $bootMethodBuilder;
    /**
     * @var PropertyBuilder
     */
    private PropertyBuilder $policiesPropertyBuilder;

    /**
     * @return AuthServiceProviderBuilder
     */
    public function prepare(): AuthServiceProviderBuilder
    {
        return $this
            ->instantiatePropertyBuilders()
            ->instantiateMethodBuilders()
            ->buildUseStatements()
            ->setDefaults();
    }

    /**
     * @return AuthServiceProviderBuilder
     */
    private function setDefaults(): AuthServiceProviderBuilder
    {
        $this
            ->getPoliciesPropertyBuilder()
            ->makeProtected()
            ->getDocBuilder()
            ->addCommentLine('The policy mappings for the application.')
            ->addVar('array');

        $this->getBootMethodBuilder()
            ->addStatements([
                $this->methodCall($this->var('this'), 'registerPolicies'),
                $this->nop(),
                $this->comment()
            ]);

        return $this;
    }

    /**
     * @return PropertyBuilder
     */
    public function getPoliciesPropertyBuilder(): PropertyBuilder
    {
        return $this->policiesPropertyBuilder;
    }

    /**
     * @param PropertyBuilder $policiesPropertyBuilder
     * @return AuthServiceProviderBuilder
     */
    public function setPoliciesPropertyBuilder(PropertyBuilder $policiesPropertyBuilder): AuthServiceProviderBuilder
    {
        $this->policiesPropertyBuilder = $policiesPropertyBuilder;

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
    protected function buildUseStatements(): AuthServiceProviderBuilder
    {
        $this->useAuthServiceProvider();
        return $this;
    }

    /**
     * @return void
     */
    private function useAuthServiceProvider(): void
    {
        $this->use(AuthServiceProvider::class, 'ServiceProvider');
    }

    /**
     * @return AuthServiceProviderBuilder
     */
    private function instantiateMethodBuilders(): AuthServiceProviderBuilder
    {
        $this->setBootMethodBuilder($this->getNewMethodBuilder('boot'));

        return $this;
    }

    /**
     * @return AuthServiceProviderBuilder
     */
    private function instantiatePropertyBuilders(): AuthServiceProviderBuilder
    {
        $this->setPoliciesPropertyBuilder($this->getNewPropertyBuilder('policies'));

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
     * @return AuthServiceProviderBuilder
     */
    protected function buildClass(): AuthServiceProviderBuilder
    {
        $this
            ->getPoliciesPropertyBuilder()
            ->setValue($this->getPolicies());

        return $this
            ->addPropertyBuilder($this->getPoliciesPropertyBuilder())
            ->addMethodBuilder($this->getBootMethodBuilder());
    }

    /**
     * @return array
     */
    public function getPolicies(): array
    {
        return $this->policies;
    }
}
