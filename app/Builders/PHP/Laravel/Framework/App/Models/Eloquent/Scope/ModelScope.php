<?php

namespace App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Scope;

use Illuminate\Support\Str;

/**
 * Class ModelScope
 * @package App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Scope
 */
class ModelScope
{
    /**
     * @var string|mixed
     */
    private string $name;
    /**
     * @var ScopeColumn|null
     */
    private ?ScopeColumn $column;

    /**
     * ModelScope constructor.
     * @param null $scope
     */
    public function __construct($scope =  null)
    {
        if (!$scope) {
            return;
        }

        $name = $scope['name'];
        $column = $scope['column'] ?? null;

        $this->name = $name;
        $this->column = new ScopeColumn($column);
    }

    /**
     * @return mixed|string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed|string
     */
    public function getFormattedName(): string
    {
        $name = $this->name;

        if (!Str::startsWith($name, 'scope')) {
            $name = 'scope' . $name;
        }

        return $name;
    }

    /**
     * @param mixed|string $name
     * @return ModelScope
     */
    public function setName(string $name): ModelScope
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return ScopeColumn|null
     */
    public function getColumn(): ?ScopeColumn
    {
        return $this->column;
    }

    /**
     * @return string|null
     */
    public function getColumnName(): ?string
    {
        return $this->column ? $this->column->getName() : null;
    }

    /**
     * @param ScopeColumn|null $column
     * @return ModelScope
     */
    public function setColumn(?ScopeColumn $column): ModelScope
    {
        $this->column = $column;

        return $this;
    }
}
