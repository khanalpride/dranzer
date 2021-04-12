<?php

namespace App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Scope;

/**
 * Class ScopeColumn
 * @package App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Scope
 */
class ScopeColumn
{
    /**
     * @var string|mixed
     */
    private string $id;
    /**
     * @var string|mixed|null
     */
    private ?string $name;

    /**
     * ScopeColumn constructor.
     * @param null $column
     */
    public function __construct($column = null)
    {
        if (!$column) {
            return;
        }

        $this->id = $column['id'];
        $this->name = $column['name'] ?? null;
    }

    /**
     * @return mixed|string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param mixed|string $id
     * @return ScopeColumn
     */
    public function setId(string $id): ScopeColumn
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed|string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param mixed|string|null $name
     * @return ScopeColumn
     */
    public function setName(?string $name): ScopeColumn
    {
        $this->name = $name;

        return $this;
    }
}
