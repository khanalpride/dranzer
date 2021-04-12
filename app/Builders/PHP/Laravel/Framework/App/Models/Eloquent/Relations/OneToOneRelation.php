<?php

namespace App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations;

/**
 * Class OneToOneRelation
 * @package App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations
 */
class OneToOneRelation
{
    /**
     * @var string
     */
    private string $id;
    /**
     * @var string
     */
    private string $type;
    /**
     * @var RelationSource
     */
    private RelationSource $source;
    /**
     * @var RelationRelated
     */
    private RelationRelated $related;

    /**
     * OneToOneRelation constructor.
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
    public function fill(array $definition = []): OneToOneRelation
    {
        if (empty($definition)) {
            return $this;
        }

        $this->id = $definition['id'];
        $this->source = new RelationSource($definition['source']);
        $this->related = new RelationRelated($definition['related']);
        $this->type = $definition['type'];

        return $this;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return !empty($this->id) && !empty($this->source) && !empty($this->related);
    }

    /**
     * @return bool
     */
    public function isInvalid(): bool
    {
        return !$this->isValid();
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
     * @return OneToOneRelation
     */
    public function setId(string $id): OneToOneRelation
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return RelationSource
     */
    public function getSource(): RelationSource
    {
        return $this->source;
    }

    /**
     * @param RelationSource $source
     * @return OneToOneRelation
     */
    public function setSource(RelationSource $source): OneToOneRelation
    {
        $this->source = $source;
        return $this;
    }

    /**
     * @return RelationRelated
     */
    public function getRelated(): RelationRelated
    {
        return $this->related;
    }

    /**
     * @param RelationRelated $related
     * @return OneToOneRelation
     */
    public function setRelated(RelationRelated $related): OneToOneRelation
    {
        $this->related = $related;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return OneToOneRelation
     */
    public function setType(string $type): OneToOneRelation
    {
        $this->type = $type;
        return $this;
    }
}
