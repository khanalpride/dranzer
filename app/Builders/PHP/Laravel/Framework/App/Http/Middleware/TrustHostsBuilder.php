<?php

namespace App\Builders\PHP\Laravel\Framework\App\Http\Middleware;

use App\Builders\PHP\ClassBuilder;
use App\Builders\PHP\MethodBuilder;
use Illuminate\Http\Middleware\TrustHosts;

/**
 * Class TrustHostsBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Http\Middleware
 */
class TrustHostsBuilder extends ClassBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'TrustHosts.php';
    /**
     * @var string
     */
    protected string $namespace = 'App\Http\Middleware';
    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name'   => 'TrustHosts',
        'extend' => 'Middleware',
    ];
    /**
     * @var MethodBuilder
     */
    private MethodBuilder $hostsMethodBuilder;

    /**
     * @return TrustHostsBuilder
     */
    public function prepare(): TrustHostsBuilder
    {
        return $this
            ->instantiateMethodBuilders()
            ->buildUseStatements()
            ->setDefaults();
    }

    /**
     * @return TrustHostsBuilder
     */
    private function instantiateMethodBuilders(): TrustHostsBuilder
    {
        $this->setHostsMethodBuilder($this->getNewMethodBuilder('hosts'));

        return $this;
    }

    /**
     * @return TrustHostsBuilder
     */
    protected function buildUseStatements(): TrustHostsBuilder
    {
        return $this->useTrustHosts();
    }

    /**
     * @return TrustHostsBuilder
     */
    private function setDefaults(): TrustHostsBuilder
    {
        $this->getHostsMethodBuilder()
            ->addStatement(
                $this->return(
                    $this->arr(
                        [
                            $this->methodCall('this', 'allSubdomainsOfApplicationUrl'),
                        ]
                    )
                )
            )
            ->getDocBuilder()
            ->setReturnType('array')
            ->addCommentLine('Get the host patterns that should be trusted.');

        return $this;
    }

    /**
     * @return TrustHostsBuilder
     */
    private function useTrustHosts(): TrustHostsBuilder
    {
        return $this->use(TrustHosts::class, 'Middleware');
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
     * @return TrustHostsBuilder
     */
    protected function buildClass(): TrustHostsBuilder
    {
        $this->addMethodBuilder($this->getHostsMethodBuilder());

        return $this;
    }

    /**
     * @return MethodBuilder
     */
    public function getHostsMethodBuilder(): MethodBuilder
    {
        return $this->hostsMethodBuilder;
    }

    /**
     * @param MethodBuilder $hostsMethodBuilder
     */
    public function setHostsMethodBuilder(MethodBuilder $hostsMethodBuilder): void
    {
        $this->hostsMethodBuilder = $hostsMethodBuilder;
    }
}
