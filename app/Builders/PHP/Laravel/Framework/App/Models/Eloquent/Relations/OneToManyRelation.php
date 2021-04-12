<?php

namespace App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations;

/**
 * Class OneToManyRelation
 * @package App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations
 */
class OneToManyRelation
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
     * OneToManyRelation constructor.
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
    public function fill(array $definition = []): OneToManyRelation
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
     * @return OneToManyRelation
     */
    public function setId(string $id): OneToManyRelation
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
     * @return OneToManyRelation
     */
    public function setSource(RelationSource $source): OneToManyRelation
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
     * @return OneToManyRelation
     */
    public function setRelated(RelationRelated $related): OneToManyRelation
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
     * @return OneToManyRelation
     */
    public function setType(string $type): OneToManyRelation
    {
        $this->type = $type;
        return $this;
    }
}
