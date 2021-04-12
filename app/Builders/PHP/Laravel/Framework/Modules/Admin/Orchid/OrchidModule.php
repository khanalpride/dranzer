<?php

namespace App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid;

use Illuminate\Support\Str;
use App\Builders\Helpers\BlueprintHelpers;
use App\Builders\PHP\Laravel\Framework\Database\Blueprint;

/**
 * Class OrchidModule
 * @package App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid
 */
class OrchidModule
{
    /**
     * @var string|null
     */
    private ?string $id;
    /**
     * @var string|null
     */
    private ?string $name;
    /**
     * @var bool
     */
    private bool $showInNavigation;
    /**
     * @var string
     */
    private string $navigationIcon;
    /**
     * @var string
     */
    private string $section;
    /**
     * @var bool
     */
    private bool $fullTextSearch;
    /**
     * @var string
     */
    private string $searchableAs;
    /**
     * @var string|null
     */
    private ?string $titleColumn;
    /**
     * @var string|null
     */
    private ?string $subTitleColumn;
    /**
     * @var array
     */
    private array $indexes;
    /**
     * @var array
     */
    private array $columns;
    /**
     * @var Blueprint
     */
    private Blueprint $blueprint;

    /**
     * OrchidModule constructor.
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
    public function fill(array $definition): OrchidModule
    {
        $this->id = $definition['moduleId'];
        $this->showInNavigation = $definition['showInNav'];
        $this->navigationIcon = $definition['navIcon'];
        $this->section = $definition['section'];
        $this->fullTextSearch = $definition['fullTextSearch'];
        $this->searchableAs = $definition['searchableAs'];
        $this->titleColumn = $definition['titleColumn'];
        $this->subTitleColumn = $definition['subTitleColumn'];
        $this->indexes = $definition['indexes'];

        $this->columns = collect($definition['columns'])
            ->map(static fn ($column) => new OrchidColumn($column))
            ->toArray();

        $blueprint = BlueprintHelpers::getBlueprintFromId($this->id);

        if ($blueprint) {
            $this->blueprint = $blueprint;
        }

        $this->name = $this->blueprint->getName();

        return $this;
    }

    /**
     * @return Blueprint
     */
    public function getBlueprint(): Blueprint
    {
        return $this->blueprint;
    }

    /**
     * @param Blueprint $blueprint
     * @return OrchidModule
     */
    public function setBlueprint(Blueprint $blueprint): OrchidModule
    {
        $this->blueprint = $blueprint;
        return $this;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns ?? [];
    }

    /**
     * @param array $columns
     * @return OrchidModule
     */
    public function setColumns(array $columns): OrchidModule
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id ?? null;
    }

    /**
     * @param string $id
     * @return OrchidModule
     */
    public function setId(string $id): OrchidModule
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return array
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * @param array $indexes
     * @return OrchidModule
     */
    public function setIndexes(array $indexes): OrchidModule
    {
        $this->indexes = $indexes;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return OrchidModule
     */
    public function setName(?string $name): OrchidModule
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getNavigationIcon(): string
    {
        return $this->navigationIcon ?? 'doc';
    }

    /**
     * @param string $navigationIcon
     * @return OrchidModule
     */
    public function setNavigationIcon(string $navigationIcon): OrchidModule
    {
        $this->navigationIcon = $navigationIcon;
        return $this;
    }

    /**
     * @return string
     */
    public function getSearchableAs(): string
    {
        return $this->searchableAs ?? Str::snake(Str::plural($this->getName())) . '_index';
    }

    /**
     * @param string $searchableAs
     * @return OrchidModule
     */
    public function setSearchableAs(string $searchableAs): OrchidModule
    {
        $this->searchableAs = $searchableAs;
        return $this;
    }

    /**
     * @return string
     */
    public function getSection(): string
    {
        return $this->section ?? 'Resources';
    }

    /**
     * @param string $section
     * @return OrchidModule
     */
    public function setSection(string $section): OrchidModule
    {
        $this->section = $section;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSubTitleColumn(): ?string
    {
        return $this->subTitleColumn;
    }

    /**
     * @param string|null $subTitleColumn
     * @return OrchidModule
     */
    public function setSubTitleColumn(?string $subTitleColumn): OrchidModule
    {
        $this->subTitleColumn = $subTitleColumn;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitleColumn(): ?string
    {
        return $this->titleColumn;
    }

    /**
     * @param string|null $titleColumn
     * @return OrchidModule
     */
    public function setTitleColumn(?string $titleColumn): OrchidModule
    {
        $this->titleColumn = $titleColumn;
        return $this;
    }

    /**
     * @param bool $showInNavigation
     * @return OrchidModule
     */
    public function setShowInNavigation(bool $showInNavigation): OrchidModule
    {
        $this->showInNavigation = $showInNavigation;
        return $this;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->getId() !== null && $this->blueprint !== null;
    }

    /**
     * @return bool
     */
    public function isInvalid(): bool
    {
        return !$this->isValid();
    }

    /**
     * @return bool
     */
    public function shouldShowInNavigation(): bool
    {
        return $this->showInNavigation;
    }

    /**
     * @return bool
     */
    public function isFullTextSearch(): bool
    {
        return $this->fullTextSearch;
    }

    /**
     * @param bool $fullTextSearch
     * @return OrchidModule
     */
    public function setFullTextSearch(bool $fullTextSearch): OrchidModule
    {
        $this->fullTextSearch = $fullTextSearch;
        return $this;
    }
}
