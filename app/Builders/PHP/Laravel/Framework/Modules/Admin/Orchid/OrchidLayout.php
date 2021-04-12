<?php

namespace App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid;

/**
 * Class OrchidLayout
 * @package App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid
 */
class OrchidLayout
{
    /**
     * @var string
     */
    private string $control;
    /**
     * @var array
     */
    private array $controlProps = [];
    /**
     * @var array
     */
    private array $listControlProps = [];
    /**
     * @var array
     */
    private array $moduleVisibility = [];

    /**
     * OrchidLayout constructor.
     * @param array $definition
     */
    public function __construct(array $definition = [])
    {
        $this->fill($definition);
    }

    /**
     * @param array $definition
     * @return $this
     */
    public function fill(array $definition = []): OrchidLayout
    {
        if (empty($definition)) {
            return $this;
        }

        $this->control = $definition['control'];
        $this->controlProps = $definition['controlProps'];
        $this->listControlProps = $definition['listControlProps'];
        $this->moduleVisibility = $definition['moduleVisibility'];

        return $this;
    }

    /**
     * @return string
     */
    public function getControl(): string
    {
        return $this->control ?? 'input';
    }

    /**
     * @param string $control
     * @return OrchidLayout
     */
    public function setControl(string $control): OrchidLayout
    {
        $this->control = $control;
        return $this;
    }

    /**
     * @return array
     */
    public function getControlProps(): array
    {
        return $this->controlProps ?? [];
    }

    /**
     * @param array $controlProps
     * @return OrchidLayout
     */
    public function setControlProps(array $controlProps): OrchidLayout
    {
        $this->controlProps = $controlProps;
        return $this;
    }

    /**
     * @return array
     */
    public function getListControlProps(): array
    {
        return $this->listControlProps ?? [];
    }

    /**
     * @param array $listControlProps
     * @return OrchidLayout
     */
    public function setListControlProps(array $listControlProps): OrchidLayout
    {
        $this->listControlProps = $listControlProps;
        return $this;
    }

    /**
     * @return array
     */
    public function getModuleVisibility(): array
    {
        return $this->moduleVisibility ?? [];
    }

    /**
     * @param array $moduleVisibility
     * @return OrchidLayout
     */
    public function setModuleVisibility(array $moduleVisibility): OrchidLayout
    {
        $this->moduleVisibility = $moduleVisibility;
        return $this;
    }
}
