<?php

namespace App\Builders\PHP\Laravel\Framework\Database;

use Illuminate\Support\Str;
use App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Scope\ModelScope;
use App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations\OneToOneRelation;
use App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations\OneToManyRelation;

/**
 * Class Blueprint
 * @package App\Builders\PHP\Laravel\Framework\Database
 */
class Blueprint
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
     * @var string
     */
    private string $table;
    /**
     * @var array
     */
    private array $columns = [];
    /**
     * @var array
     */
    private array $scopes = [];
    /**
     * @var bool
     */
    private bool $softDelete = false;
    /**
     * @var bool
     */
    private bool $singularTableName = false;
    /**
     * @var bool
     */
    private bool $pluralizeTableName = true;
    /**
     * @var bool
     */
    private bool $isPivot = false;
    /**
     * @var bool
     */
    private bool $unguarded = false;
    /**
     * @var bool
     */
    private bool $createFactory = true;
    /**
     * @var bool
     */
    private bool $createSeeder = true;
    /**
     * @var int
     */
    private int $seedCount = 50;
    /**
     * @var array
     */
    private array $oneToOneRelations = [];
    /**
     * @var array
     */
    private array $oneToManyRelations = [];

    /**
     * Blueprint constructor.
     * @param array $definition
     */
    public function __construct(array $definition = [])
    {
        $this->fill($definition);
    }

    /**
     * @param array $definition
     * @return Blueprint
     */
    public function fill(array $definition): Blueprint
    {
        $id = $definition['id'] ?? null;

        $name = $definition['modelName'] ?? null;

        $table = $definition['tableName'] ?? null;

        $columns = $definition['columns'] ?? [];

        $scopes = $definition['scopes'] ?? [];

        $pluralizeTable = $definition['pluralize'] ?? true;

        $singularTable = $definition['singular'] ?? false;

        $pivot = $definition['pivot'] ?? false;

        $config = $definition['config'] ?? [];

        $unguarded = $config['unguarded'] ?? false;

        $shouldSoftDelete = $config['softDelete'] ?? false;

        $factory = $definition['factory'] ?? [];

        $createFactory = $factory['createFactory'] ?? true;

        $createSeeder = $factory['createSeeder'] ?? true;

        $seedCount = $factory['seedCount'] ?? 50;

        $eloquentRelations = $definition['eloquent']['relations'] ?? [];

        $oneToOneRelations = collect($eloquentRelations['one-to-one'] ?? [])
            ->map(static fn ($relation) => new OneToOneRelation($relation))
            ->toArray();

        $oneToManyRelations = collect($eloquentRelations['one-to-many'] ?? [])
            ->map(static fn ($relation) => new OneToManyRelation($relation))
            ->toArray();

//        $hasOneThroughRelations = $eloquentRelations['has-one-through'] ?? [];
//        $hasManyThroughRelations = $eloquentRelations['has-many-through'] ?? [];
//        $manyToManyRelations = $eloquentRelations['many-to-many'] ?? [];

        $this
            ->setId($id)
            ->setName($name)
            ->setTable($table)
            ->setScopes($scopes)
            ->setIsPivot($pivot)
            ->setColumns($columns)
            ->setUnguarded($unguarded)
            ->setSeedCount($seedCount)
            ->setCreateSeeder($createSeeder)
            ->setSoftDelete($shouldSoftDelete)
            ->setCreateFactory($createFactory)
            ->setSingularTableName($singularTable)
            ->setPluralizeTableName($pluralizeTable)
            ->setOneToOneRelations($oneToOneRelations)
            ->setOneToManyRelations($oneToManyRelations);

        return $this;
    }

    /**
     * @return array
     */
    public function getOneToOneRelations(): array
    {
        return $this->oneToOneRelations;
    }

    /**
     * @param array $oneToOneRelations
     * @return Blueprint
     */
    public function setOneToOneRelations(array $oneToOneRelations): Blueprint
    {
        $this->oneToOneRelations = $oneToOneRelations;
        return $this;
    }

    /**
     * @return array
     */
    public function getOneToManyRelations(): array
    {
        return $this->oneToManyRelations;
    }

    /**
     * @param array $oneToManyRelations
     * @return Blueprint
     */
    public function setOneToManyRelations(array $oneToManyRelations): Blueprint
    {
        $this->oneToManyRelations = $oneToManyRelations;
        return $this;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return !empty($this->getId()) && !empty($this->getTable());
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
     * @return Blueprint
     */
    public function setId(string $id): Blueprint
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return Str::studly(Str::singular($this->name));
    }

    /**
     * @param string $name
     * @return Blueprint
     */
    public function setName(string $name): Blueprint
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return Str::snake(Str::plural($this->table));
    }

    /**
     * @param string $table
     * @return Blueprint
     */
    public function setTable(string $table): Blueprint
    {
        $this->table = $table;

        return $this;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        $columns = [];

        foreach ($this->columns as $column) {
            $id = $column['id'] ?? null;
            $name = $column['name'] ?? null;
            $type = $column['type'] ?? null;

            if (empty($id) || empty($name) || empty($type)) {
                continue;
            }

            $attributes = $column['attributes'];

            $columns[] = (new TableColumn)
                ->setId($id)
                ->setName($name)
                ->setType($type)
                ->setAutoIncrement($attributes['ai'] ?? false)
                ->setUnsigned($attributes['us'] ?? false)
                ->setHidden($attributes['h'] ?? false)
                ->setFillable($attributes['f'] ?? false)
                ->setUnique($attributes['u'] ?? false)
                ->setNullable($attributes['n'] ?? false)
                ->setLength($attributes['length'] ?? null)
                ->setRawAttributes($attributes);
        }

        return $columns;
    }

    /**
     * @param array $columns
     * @return Blueprint
     */
    public function setColumns(array $columns): Blueprint
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @return bool
     */
    public function shouldSoftDelete(): bool
    {
        return $this->softDelete;
    }

    /**
     * @param bool $softDelete
     * @return Blueprint
     */
    public function setSoftDelete(bool $softDelete): Blueprint
    {
        $this->softDelete = $softDelete;

        return $this;
    }

    /**
     * @return array
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * @param array $scopes
     * @return Blueprint
     */
    public function setScopes(array $scopes): Blueprint
    {
        $scopes = collect($scopes)
            ->map(static fn ($scope) => new ModelScope($scope))
            ->toArray();

        $this->scopes = $scopes;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUnguarded(): bool
    {
        return $this->unguarded;
    }

    /**
     * @param bool $unguarded
     * @return Blueprint
     */
    public function setUnguarded(bool $unguarded): Blueprint
    {
        $this->unguarded = $unguarded;

        return $this;
    }

    /**
     * @return bool
     */
    public function shouldCreateFactory(): bool
    {
        return $this->createFactory;
    }

    /**
     * @param bool $createFactory
     * @return Blueprint
     */
    public function setCreateFactory(bool $createFactory): Blueprint
    {
        $this->createFactory = $createFactory;

        return $this;
    }

    /**
     * @return bool
     */
    public function shouldCreateSeeder(): bool
    {
        return $this->createSeeder;
    }

    /**
     * @param bool $createSeeder
     * @return Blueprint
     */
    public function setCreateSeeder(bool $createSeeder): Blueprint
    {
        $this->createSeeder = $createSeeder;

        return $this;
    }

    /**
     * @return int
     */
    public function getSeedCount(): int
    {
        return $this->seedCount;
    }

    /**
     * @param int $seedCount
     * @return Blueprint
     */
    public function setSeedCount(int $seedCount): Blueprint
    {
        $this->seedCount = $seedCount;

        return $this;
    }

    /**
     * @return bool
     */
    public function shouldPluralizeTableName(): bool
    {
        return $this->pluralizeTableName;
    }

    /**
     * @param bool $pluralizeTableName
     * @return Blueprint
     */
    public function setPluralizeTableName(bool $pluralizeTableName): Blueprint
    {
        $this->pluralizeTableName = $pluralizeTableName;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPivot(): bool
    {
        return $this->isPivot;
    }

    /**
     * @param bool $isPivot
     * @return Blueprint
     */
    public function setIsPivot(bool $isPivot): Blueprint
    {
        $this->isPivot = $isPivot;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSingularTableName(): bool
    {
        return $this->singularTableName;
    }

    /**
     * @param bool $singularTableName
     * @return Blueprint
     */
    public function setSingularTableName(bool $singularTableName): Blueprint
    {
        $this->singularTableName = $singularTableName;
        return $this;
    }
}
