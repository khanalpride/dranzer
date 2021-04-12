<?php

namespace App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid;

/**
 * Class OrchidColumn
 * @package App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid
 */
class OrchidColumn
{
    /**
     * @var string
     */
    private string $id;
    /**
     * @var int
     */
    private int $index;
    /**
     * @var string
     */
    private string $name;
    /**
     * @var string
     */
    private string $label;
    /**
     * @var string
     */
    private string $type;
    /**
     * @var bool
     */
    private bool $visible;
    /**
     * @var OrchidLayout
     */
    private OrchidLayout $layout;
    /**
     * @var bool
     */
    private bool $searchable;
    /**
     * @var bool
     */
    private bool $sortable;
    /**
     * @var bool
     */
    private bool $filterable;
    /**
     * @var bool
     */
    private bool $vertical;
    /**
     * @var bool
     */
    private bool $required;
    /**
     * @var array
     */
    private array $attributes;
    /**
     * @var array
     */
    private array $relatedModel = [];

    /**
     * OrchidColumn constructor.
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
    public function fill(array $definition = []): OrchidColumn
    {
        if (empty($definition)) {
            return $this;
        }

        $this->id = $definition['id'];
        $this->index = $definition['index'];
        $this->name = $definition['name'];
        $this->label = $definition['label'];
        $this->type = $definition['type'];
        $this->visible = $definition['visible'] ?? false;
        $this->layout = (new OrchidLayout($definition['layout'] ?? []));
        $this->searchable = $definition['searchable'] ?? true;
        $this->sortable = $definition['sortable'] ?? true;
        $this->filterable = $definition['filterable'] ?? true;
        $this->vertical = $definition['vertical'] ?? true;
        $this->required = $definition['required'] ?? true;
        $this->attributes = $definition['attributes'] ?? [];
        $this->relatedModel = $definition['relatedModel'] ?? [];

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
     * @return OrchidColumn
     */
    public function setId(string $id): OrchidColumn
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getIndex(): int
    {
        return $this->index ?? 0;
    }

    /**
     * @param int $index
     * @return OrchidColumn
     */
    public function setIndex(int $index): OrchidColumn
    {
        $this->index = $index;
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
     * @return OrchidColumn
     */
    public function setName(string $name): OrchidColumn
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return OrchidColumn
     */
    public function setLabel(string $label): OrchidColumn
    {
        $this->label = $label;
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
     * @return OrchidColumn
     */
    public function setType(string $type): OrchidColumn
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return bool
     */
    public function isVisible(): bool
    {
        return $this->visible;
    }

    /**
     * @param bool $visible
     * @return OrchidColumn
     */
    public function setVisible(bool $visible): OrchidColumn
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * @return OrchidLayout
     */
    public function getLayout(): OrchidLayout
    {
        return $this->layout;
    }

    /**
     * @param OrchidLayout $layout
     * @return OrchidColumn
     */
    public function setLayout(OrchidLayout $layout): OrchidColumn
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    /**
     * @param bool $searchable
     * @return OrchidColumn
     */
    public function setSearchable(bool $searchable): OrchidColumn
    {
        $this->searchable = $searchable;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSortable(): bool
    {
        return $this->sortable;
    }

    /**
     * @param bool $sortable
     * @return OrchidColumn
     */
    public function setSortable(bool $sortable): OrchidColumn
    {
        $this->sortable = $sortable;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    /**
     * @param bool $filterable
     * @return OrchidColumn
     */
    public function setFilterable(bool $filterable): OrchidColumn
    {
        $this->filterable = $filterable;
        return $this;
    }

    /**
     * @return bool
     */
    public function isVertical(): bool
    {
        return $this->vertical;
    }

    /**
     * @param bool $vertical
     * @return OrchidColumn
     */
    public function setVertical(bool $vertical): OrchidColumn
    {
        $this->vertical = $vertical;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @param bool $required
     * @return OrchidColumn
     */
    public function setRequired(bool $required): OrchidColumn
    {
        $this->required = $required;
        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     * @return OrchidColumn
     */
    public function setAttributes(array $attributes): OrchidColumn
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * @return array
     */
    public function getRelatedModel(): array
    {
        return $this->relatedModel;
    }

    /**
     * @param array $relatedModel
     * @return OrchidColumn
     */
    public function setRelatedModel(array $relatedModel): OrchidColumn
    {
        $this->relatedModel = $relatedModel;
        return $this;
    }
}
