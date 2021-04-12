<?php

namespace App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations;

/**
 * Class RelationRelated
 * @package App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations
 */
class RelationRelated
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
     * RelationRelated constructor.
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
    public function fill(array $definition = []): RelationRelated
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
     * @return RelationRelated
     */
    public function setId(string $id): RelationRelated
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
     * @return RelationRelated
     */
    public function setName(string $name): RelationRelated
    {
        $this->name = $name;
        return $this;
    }
}
