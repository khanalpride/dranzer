<?php

namespace App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations;

/**
 * Class RelationSource
 * @package App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations
 */
class RelationSource
{
    /**
     * @var string
     */
    private string $id;
    /**
     * @var string
     */
    private string $name;

    /**
     * RelationSource constructor.
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
    public function fill(array $definition = []): RelationSource
    {
        $this->id = $definition['id'];
        $this->name = $definition['name'];

        return $this;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return RelationSource
     */
    public function setId(string $id): RelationSource
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return RelationSource
     */
    public function setName(string $name): RelationSource
    {
        $this->name = $name;
        return $this;
    }
}
