<?php

namespace App\Builders\PHP\Laravel\Framework\Database\Migrations;

use Illuminate\Support\Str;
use App\Builders\PHP\ClassBuilder;
use App\Builders\PHP\MethodBuilder;
use PhpParser\Node\Stmt\Expression;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use App\Builders\PHP\Laravel\Framework\Database\Blueprint;
use App\Builders\PHP\Laravel\Framework\Database\TableColumn;

/**
 * Class MigrationsBuilder
 * @package App\Builders\PHP\Laravel\Framework\Database\Migrations
 */
class MigrationBuilder extends ClassBuilder
{
    /**
     * @var bool
     */
    public static bool $customBuilder = true;
    /**
     * @var string|null
     */
    protected string $namespace = 'App\Schema';
    /**
     * @var bool
     */
    protected bool $removeNamespace = true;
    /**
     * @var
     */
    private $upMethodBuilder;
    /**
     * @var
     */
    private $downMethodBuilder;
    /**
     * @var string $blueprintId
     */
    private string $blueprintId;
    /**
     * @var
     */
    private $tableName;
    /**
     * @var string
     */
    private $connection = 'mysql';
    /**
     * @var bool
     */
    private $useIlluminateUseStatements = true;
    /**
     * @var array
     */
    private $blueprints = [];
    /**
     * @var array
     */
    private $columns = [];
    /**
     * @var bool
     */
    private $pluralizeTableName = true;
    /**
     * @var array
     */
    private $relations = [];
    /**
     * @var bool
     */
    private $softDelete = false;

    /**
     * @return bool
     */
    public function build(): bool
    {
        return $this
            ->buildClass()
            ->toDisk();
    }

    /**
     * @return MigrationBuilder
     */
    protected function buildClass(): MigrationBuilder
    {
        $tableName = $this->pluralizeTableName ? Str::studly(Str::plural($this->getTableName())) : Str::studly(
            $this->getTableName()
        );

        $migrationClass = 'Create' . $tableName . 'Table';

        $this->classDefinition = [
            'name'   => $migrationClass,
            'extend' => 'Migration',
        ];

        $this->updateClassDefinition();

        if ($this->shouldUseIlluminateUseStatements()) {
            $this->use(Schema::class);
            $this->use(\Illuminate\Database\Schema\Blueprint::class);
            $this->use(Migration::class);
        }

        $this->setUpMethodBuilder(new MethodBuilder('up'));
        $this->setDownMethodBuilder(new MethodBuilder('down'));

        $this->getUpMethodBuilder()
            ->addStatements(
                [
                    $this->staticCall(
                        'Schema', 'create', [
                            $this->string(
                                $this->pluralizeTableName ? Str::plural($this->getTableName()) : $this->getTableName()
                            ),
                            $this->staticClosure(
                                [$this->param('table', 'Blueprint')],
                                array_merge($this->getBlueprintStatements(), $this->getForeignKeyStatements())
                            ),
                        ]
                    ),
                ]
            )
            ->setReturnType('void')
            ->getDocBuilder()
            ->addCommentLine('Run the migrations.')
            ->setReturnType('void');

        $this->getDownMethodBuilder()
            ->addStatements(
                [
                    $this->staticCall(
                        'Schema', 'dropIfExists', [
                            $this->string(
                                $this->pluralizeTableName ? Str::plural($this->getTableName()) : $this->getTableName()
                            ),
                        ]
                    ),
                ]
            )
            ->setReturnType('void')
            ->getDocBuilder()
            ->addCommentLine('Reverse the migrations.')
            ->setReturnType('void');

        $this->addMethodBuilder($this->getUpMethodBuilder());
        $this->addMethodBuilder($this->getDownMethodBuilder());

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUpMethodBuilder(): MethodBuilder
    {
        return $this->upMethodBuilder;
    }

    /**
     * @param mixed $upMethodBuilder
     * @return MigrationBuilder
     */
    public function setUpMethodBuilder($upMethodBuilder): MigrationBuilder
    {
        $this->upMethodBuilder = $upMethodBuilder;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDownMethodBuilder(): MethodBuilder
    {
        return $this->downMethodBuilder;
    }

    /**
     * @param mixed $downMethodBuilder
     * @return MigrationBuilder
     */
    public function setDownMethodBuilder($downMethodBuilder): MigrationBuilder
    {
        $this->downMethodBuilder = $downMethodBuilder;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @param mixed $tableName
     * @return MigrationBuilder
     */
    public function setTableName(string $tableName): MigrationBuilder
    {
        $this->tableName = strtolower($tableName);
        return $this;
    }

    /**
     * @return string
     */
    public function getConnection(): string
    {
        return $this->connection;
    }

    /**
     * @param mixed $connection
     * @return MigrationBuilder
     */
    public function setConnection($connection): MigrationBuilder
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * @param bool $useIlluminateUseStatements
     * @return MigrationBuilder
     */
    public function setUseIlluminateUseStatements(bool $useIlluminateUseStatements): MigrationBuilder
    {
        $this->useIlluminateUseStatements = $useIlluminateUseStatements;
        return $this;
    }

    /**
     * @return bool
     */
    public function shouldUseIlluminateUseStatements(): bool
    {
        return $this->useIlluminateUseStatements;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     * @return MigrationBuilder
     */
    public function setColumns(array $columns): MigrationBuilder
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * @return array
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * @param array $relations
     * @return MigrationBuilder
     */
    public function setRelations(array $relations): MigrationBuilder
    {
        $this->relations = $relations;
        return $this;
    }

    /**
     * @return array
     */
    public function getBlueprints(): array
    {
        return $this->blueprints;
    }

    /**
     * @param array $blueprints
     * @return MigrationBuilder
     */
    public function setBlueprints(array $blueprints): MigrationBuilder
    {
        $this->blueprints = $blueprints;
        return $this;
    }

    /**
     * @param bool $softDelete
     * @return MigrationBuilder
     */
    public function setSoftDelete(bool $softDelete): MigrationBuilder
    {
        $this->softDelete = $softDelete;
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
     * @return bool
     *
     * @noinspection PhpUnused
     * @noinspection UnknownInspectionInspection
     *
     */
    public function shouldPluralizeTableName(): bool
    {
        return $this->pluralizeTableName;
    }

    /**
     * @param bool $pluralizeTableName
     * @return MigrationBuilder
     *
     * @noinspection PhpUnused
     * @noinspection UnknownInspectionInspection
     */
    public function setPluralizeTableName(bool $pluralizeTableName): MigrationBuilder
    {
        $this->pluralizeTableName = $pluralizeTableName;

        return $this;
    }

    /**
     * @return string
     */
    public function getBlueprintId(): string
    {
        return $this->blueprintId;
    }

    /**
     * @param string $blueprintId
     * @return MigrationBuilder
     */
    public function setBlueprintId(string $blueprintId): MigrationBuilder
    {
        $this->blueprintId = $blueprintId;

        return $this;
    }

    /**
     * @return array
     */
    private function getBlueprintStatements(): array
    {
        $statements = [];

        $columns = collect($this->getColumns());

        $timestampColumns = $columns->filter(
            static fn (TableColumn $column) => $column->getType() === 'timestamp'
        );

        // The blueprint has default timestamps only when both created_at and updated_at
        // columns are present and are nullable.
        $hasCreatedAtColumn = $timestampColumns->first(
                static fn (TableColumn $column) => $column->getName() === 'created_at' && $column->isNullable()
            ) !== null;

        $hasUpdatedAtColumn = $timestampColumns->first(
                static fn (TableColumn $column) => $column->getName() === 'updated_at' && $column->isNullable()
            ) !== null;

        $hasDefaultTimestamps = $hasCreatedAtColumn && $hasUpdatedAtColumn;

        // If the created_at or updated_at timestamp columns are present, remove them
        // from the columns collection as we'll use the timestamps helper method.
        if ($hasDefaultTimestamps) {
            $columns = $columns->reject(
                static fn (TableColumn $column) => $column->getType() === 'timestamp'
                    && ($column->getName() === 'created_at' || $column->getName() === 'updated_at')
            );
        }

        // If the soft-delete option is not set, check to see if
        // the deleted_at column attributes qualify for the softDeletes
        // helper method. If it does, we'll set softDelete property to true.
        // The reason we can do this is because the mere presence of deleted_at
        // column doesn't mean the Model will soft-delete the records. The model
        // must be using the SoftDeletes trait which is explicitly set.
        if (!$this->shouldSoftDelete()) {
            $shouldSoftDelete = $timestampColumns->first(
                    static fn (TableColumn $column) => $column->getName() === 'deleted_at' && $column->isNullable()
                ) !== null;

            $this->setSoftDelete($shouldSoftDelete);
        }

        // Remove the deleted_at column from the columns collection
        // as we'll use the softDeletes helper method.
        if ($this->shouldSoftDelete()) {
            $columns = $columns->reject(
                static fn (TableColumn $column) => $column->getName() === 'deleted_at'
            );
        }

        foreach ($columns as $column) {
            $statements[] = $this->getColumnStatement($column);
        }

        // Adding the statement here and not during the early check as the
        // timestamps are usually added at the end.
        if ($hasDefaultTimestamps) {
            $statements[] = $this->expression($this->methodCall('table', 'timestamps'));
        }

        // Adding the statement here and not during the early check as the
        // deleted_at timestamp is usually added at the end.
        if ($this->shouldSoftDelete()) {
            $statements[] = $this->expression($this->methodCall('table', 'softDeletes'));
        }

        return $statements;
    }

    /**
     * @param TableColumn $column
     * @return Expression
     */
    private function getColumnStatement(TableColumn $column): Expression
    {
        $name = $column->getName();

        $type = $column->getType();

        $autoIncrement = $column->isAutoIncrementing();
        $unsigned = $column->isUnsigned();
        $unique = $column->isUnique();
        $nullable = $column->isNullable();
        $length = $column->getLength();

        $methodParams = [
            $this->string($name),
        ];

        if ($unsigned && !$autoIncrement) {
            if ($type === 'integer') {
                $type = 'unsignedInteger';
            }

            if ($type === 'bigInteger') {
                $type = 'unsignedBigInteger';
            }

            if ($type === 'mediumInteger') {
                $type = 'unsignedMediumInteger';
            }

            if ($type === 'tinyInteger') {
                $type = 'unsignedTinyInteger';
            }
        }

        if ($unsigned && $autoIncrement) {
            if ($type === 'integer') {
                $type = 'increments';
            }

            if ($type === 'bigInteger') {
                $type = 'bigIncrements';
            }

            if ($type === 'mediumInteger') {
                $type = 'mediumIncrements';
            }

            if ($type === 'tinyInteger') {
                $type = 'tinyIncrements';
            }

            $unique = false;

            $nullable = false;
        }

        if ($type !== 'increments' && $autoIncrement && Str::endsWith($type, 'ger') && !Str::startsWith(
                $type, 'unsigned'
            )) {
            $methodParams[] = $this->const(true);
        }

        if ($length !== null && !str_contains($type, 'tiny') && !str_contains(
                $type, 'small'
            )) {
            $methodParams[] = $this->int($length);
        }

        if ($name === 'remember_token') {
            $methodParams = [];
        }

        $methodCall = $this->methodCall('table', $this->getColumnTypeForModel($type), $methodParams);

        if ($unique) {
            $methodCall = $this->methodCall($methodCall, 'unique');
        }

        if ($nullable) {
            $methodCall = $this->methodCall($methodCall, 'nullable');
        }

        return $this->expression($methodCall);
    }

    /**
     * @return array
     */
    private function getForeignKeyStatements(): array
    {
        $relations = $this->getRelations();

        $map = [];

        $blueprints = collect($this->getBlueprints());

        foreach ($relations as $relation) {
            $localTable = $blueprints->first(
                fn (Blueprint $blueprint) => $blueprint->getId() === $this->getBlueprintId()
            );

            $foreignTable = $blueprints->first(
                fn (Blueprint $blueprint) => $blueprint->getId() === $relation['foreignTable']
            );

            if (!$foreignTable || !$localTable) {
                continue;
            }

            $foreignTableName = $foreignTable->getTable();

            if ($foreignTableName === $this->getTableName()) {
                continue;
            }

            $localTableColumns = collect($localTable->getColumns());

            $localColumn = $localTableColumns->first(
                static fn (TableColumn $column) => $column->getId() === $relation['localColumn']
            );

            if (!$localColumn) {
                continue;
            }

            $resolved = $relation;

            $resolved['foreignTable'] = $foreignTableName;

            $foreignTableColumns = $foreignTable->getColumns();

            foreach ($foreignTableColumns as $column) {
                if ($column->getId() === $relation['foreignColumn']) {
                    $resolved['foreignColumn'] = $column->getName();
                    break;
                }
            }

            $localColumnName = $localColumn->getName();

            $resolved['key'] = $localColumnName;

            $map[$foreignTableName] = $resolved;
        }

        $statements = [];

        foreach ($map as $relation) {
            $key = $relation['key'];
            $foreignTableName = $relation['foreignTable'];
            $column = $relation['foreignColumn'];

            $methodCall = $this->methodCall(
                'table', 'foreign', [
                    $this->string($key),
                ]
            );

            $foreignTable = collect($this->getBlueprints())->first(static fn (Blueprint $b) => $b->getTable() === $foreignTableName);

            $pluralizeReferencedModel = !$foreignTable || !$foreignTable->isSingularTableName();

            $foreignTableName = $pluralizeReferencedModel ? Str::plural($foreignTableName) : $foreignTableName;

            $methodCall = $this->methodCall($methodCall, 'references', [$this->string($column)]);

            $methodCall = $this->methodCall($methodCall, 'on', [$this->string($foreignTableName)]);

            if ($relation['onDeleteReference'] ?? null) {
                $methodCall = $this->methodCall(
                    $methodCall, 'onDelete', [
                        $this->string($relation['onDeleteReference']),
                    ]
                );
            }

            $statements[] = $this->expression($methodCall);
        }

        if (count($statements)) {
            $statements = array_merge([$this->nop()], $statements);
        }

        return $statements;
    }

    /**
     * @param $type
     *
     * @return string
     */
    private function getColumnTypeForModel($type): string
    {
        if ($type === 'int') {
            return 'integer';
        }

        return $type;
    }
}
