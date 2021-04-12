<?php

namespace App\Builders\PHP\Laravel\Framework\App\Http\Middleware;

use App\Builders\PHP\ClassBuilder;
use App\Builders\PHP\PropertyBuilder;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;

/**
 * Class PreventRequestsDuringMaintenance
 * @package App\Builders\PHP\Laravel\Framework\App\Http\Middleware
 */
class PreventRequestsDuringMaintenanceBuilder extends ClassBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'PreventRequestsDuringMaintenance.php';
    /**
     * @var string
     */
    protected string $namespace = 'App\Http\Middleware';
    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name'   => 'PreventRequestsDuringMaintenance',
        'extend' => 'Middleware',
    ];
    /**
     * @var array
     */
    private array $except = [];
    /**
     * @var PropertyBuilder
     */
    private PropertyBuilder $exceptPropertyBuilder;

    /**
     * @return PreventRequestsDuringMaintenanceBuilder
     */
    public function prepare(): PreventRequestsDuringMaintenanceBuilder
    {
        return $this
            ->instantiatePropertyBuilders()
            ->buildUseStatements()
            ->setDefaults();
    }

    /**
     * @return PreventRequestsDuringMaintenanceBuilder
     */
    private function instantiatePropertyBuilders(): PreventRequestsDuringMaintenanceBuilder
    {
        return $this->setExceptPropertyBuilder($this->getNewPropertyBuilder('except'));
    }

    /**
     * @return PreventRequestsDuringMaintenanceBuilder
     */
    protected function buildUseStatements(): PreventRequestsDuringMaintenanceBuilder
    {
        $this->useMiddleware();

        return $this;
    }

    /**
     * @return PreventRequestsDuringMaintenanceBuilder
     */
    private function setDefaults(): PreventRequestsDuringMaintenanceBuilder
    {
        $this->getExceptPropertyBuilder()
            ->makeProtected()
            ->getDocBuilder()
            ->addCommentLine('The URIs that should be reachable while maintenance mode is enabled.')
            ->addVar('array');

        return $this;
    }

    /**
     * @return void
     */
    private function useMiddleware(): void
    {
        $this->use(PreventRequestsDuringMaintenance::class, 'Middleware');
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
     * @return PreventRequestsDuringMaintenanceBuilder
     */
    protected function buildClass(): PreventRequestsDuringMaintenanceBuilder
    {
        $this->addExceptProperty();

        return $this;
    }

    /**
     * @param $except
     * @return PreventRequestsDuringMaintenanceBuilder
     */
    public function addExcept($except): PreventRequestsDuringMaintenanceBuilder
    {
        $this->except[] = $except;

        return $this;
    }

    /**
     * @return void
     */
    private function addExceptProperty(): void
    {
        $this
            ->getExceptPropertyBuilder()
            ->setValue($this->getExcept());

        $this->addPropertyBuilder($this->getExceptPropertyBuilder());

    }

    /**
     * @return PropertyBuilder
     */
    public function getExceptPropertyBuilder(): PropertyBuilder
    {
        return $this->exceptPropertyBuilder;
    }

    /**
     * @param PropertyBuilder $exceptPropertyBuilder
     * @return PreventRequestsDuringMaintenanceBuilder
     */
    public function setExceptPropertyBuilder(PropertyBuilder $exceptPropertyBuilder): PreventRequestsDuringMaintenanceBuilder
    {
        $this->exceptPropertyBuilder = $exceptPropertyBuilder;

        return $this;
    }

    /**
     * @return array
     */
    public function getExcept(): array
    {
        return $this->except;
    }
}
