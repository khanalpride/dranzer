<?php

namespace App\Builders\PHP\Laravel\Framework\Database\Factories;

use Faker\Factory;
use Illuminate\Support\Str;
use PhpParser\Node\Expr\FuncCall;
use App\Builders\PHP\ClassBuilder;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\PropertyFetch;
use App\Builders\PHP\Laravel\Framework\Database\Blueprint;
use App\Builders\PHP\Laravel\Framework\Database\TableColumn;

/**
 * Class FactoryBuilder
 * @package App\Builders\PHP\Laravel\Framework\Database\Factories
 */
class FactoryBuilder extends ClassBuilder
{
    /**
     * @var bool
     */
    public static bool $customBuilder = true;
    /**
     * @var string|null
     */
    protected string $namespace = 'Database\Factories';
    /**
     * @var
     */
    private $table;
    /**
     * @var
     */
    private $rowCount;
    /**
     * @var array
     */
    private $blueprints = [];
    /**
     * @var array
     */
    private $relations = [];
    /**
     * @var array
     */
    private $columns = [];
    /**
     * @var array
     */
    private $preReturnStatements = [];
    /**
     * @var array
     */
    private $foreignKeyColumns = [];
    /**
     * @var
     */
    private $projectId;
    /**
     * @var string
     */
    private string $modelImport;
    /**
     * @var string
     */
    private string $modelName;

    /**
     * @return bool
     */
    public function build(): bool
    {
        $modelName = Str::studly(Str::singular($this->getTable()));

        $this->modelName = $modelName;

        $this->classDefinition = [
            'name'   => $modelName . 'Factory',
            'extend' => 'Factory',
        ];

        $this->updateClassDefinition();

        $this->filename = $modelName . 'Factory.php';

        $modelImport = 'App\Models\\' . Str::studly(Str::singular($this->getTable()));

        $this->modelImport = $modelImport;

        $this->use($modelImport);

        $this->resolveForeignKeyReferences();

        $this->use(Factory::class, 'Faker');

        $this->use(\Illuminate\Database\Eloquent\Factories\Factory::class);

        $modelProperty = $this->getNewPropertyBuilder('model');

        $modelProperty
            ->makeProtected()
            ->setValue($this->const("$modelName::class"))
            ->getDocBuilder()
            ->addCommentLine("The name of the factory's corresponding model.")
            ->addVar('string');

        $this->addPropertyBuilder($modelProperty);

        $definitionMethodBuilder = $this->getNewMethodBuilder('definition');

        $definitionMethodBuilder->addStatements([
            $this->inlineAssign('faker', $this->staticCall('Faker', 'create')),
            $this->nop(),
            ...$this->getPreReturnStatements(),
            $this->return(
                $this->arr($this->getColumnsMapping())
            ),
        ])
            ->setReturnType('array')
            ->getDocBuilder()
            ->addCommentLine("Define the model's default state.")
            ->setReturnType('array');

        $this->addMethodBuilder($definitionMethodBuilder);

        $this->toDisk();

        return true;
    }

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param mixed $table
     * @return FactoryBuilder
     */
    public function setTable($table): FactoryBuilder
    {
        $this->table = $table;
        return $this;
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
     * @return FactoryBuilder
     */
    public function setColumns(array $columns): FactoryBuilder
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * @param mixed $projectId
     * @return FactoryBuilder
     */
    public function setProjectId($projectId): FactoryBuilder
    {
        $this->projectId = $projectId;
        return $this;
    }

    /**
     * @return array
     */
    public function getPreReturnStatements(): array
    {
        return $this->preReturnStatements;
    }

    /**
     * @return mixed
     */
    public function getRowCount()
    {
        return $this->rowCount;
    }

    /**
     * @param mixed $rowCount
     * @return FactoryBuilder
     */
    public function setRowCount($rowCount): FactoryBuilder
    {
        $this->rowCount = $rowCount;
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
     * @return FactoryBuilder
     */
    public function setBlueprints(array $blueprints): FactoryBuilder
    {
        $this->blueprints = $blueprints;
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
     * @return FactoryBuilder
     */
    public function setRelations(array $relations): FactoryBuilder
    {
        $this->relations = $relations;

        return $this;
    }

    /**
     * @return void
     */
    private function resolveForeignKeyReferences(): void
    {
        $blueprints = collect($this->getBlueprints());

        $blueprint = $blueprints->first(fn (Blueprint $blueprint) => $blueprint->getTable() === $this->getTable());

        if (!$blueprint) {
            return;
        }

        $relations = $this->getRelations();

        if (!count($relations)) {
            return;
        }

        $preReturnStatements = [];

        $uses = [];

        foreach ($relations as $relation) {
            $referencedTable = $blueprints->first(fn (Blueprint $blueprint) => $blueprint->getId() === $relation['foreignTable']);

            if (!$referencedTable) {
                continue;
            }

            $columns = collect($referencedTable->getColumns());

            $localColumn = collect($this->getColumns())->first(static fn (TableColumn $column) => $column->getId() === $relation['localColumn']);

            if (!$localColumn || !$localColumn->isUnsigned() || !Str::contains(strtolower($localColumn->getType()), 'integer')) {
                continue;
            }

            $referencedColumn = $columns->first(static fn (TableColumn $column) => $column->getId() === $relation['foreignColumn']);

            if (!$referencedColumn) {
                continue;
            }

            $tableName = $referencedTable->getTable();

            $modelName = Str::studly(Str::singular($tableName));

            $varName = Str::plural(Str::snake($modelName));

            $import = 'App\Models\\' . $modelName;

            if ($import !== $this->modelImport) {
                $uses[] = $import;
            }

            $preReturnStatements[] = $this->expression(
                $this->inlineAssign($varName, $this->chainableStaticCall($modelName, 'all', [], [
                    $this->chainableMethodCall('pluck', [$this->string($referencedColumn->getName())]),
                    $this->chainableMethodCall('toArray')
                ]))
            );

            $this->foreignKeyColumns[$localColumn->getName()] = $varName;
        }

        if (count($preReturnStatements)) {
            $preReturnStatements[] = $this->nop();
        }

        collect($uses)->each(fn ($stmt) => $this->use($stmt));

        $this->setPreReturnStatements($preReturnStatements);
    }

    /**
     * @return array
     */
    private function getColumnsMapping(): array
    {
        return collect($this->getColumns())
            ->map(fn (TableColumn $column) => $this->getColumnMapping($column))
            ->collapse()
            ->toArray();
    }

    /**
     * @param TableColumn $column
     * @return array
     */
    private function getColumnMapping(TableColumn $column): array
    {
        $mapping = [];

        $name = $column->getName();

        $type = $column->getType();

        $isPrimary = $column->isAutoIncrementing() && $column->isUnsigned();

        $length = $column->getLength();

        if ($isPrimary) {
            $mapping[] = $this->assoc($name, $this->null());

            return $mapping;
        }

        if (!$length && $this->modelName === 'User' && strtolower($name) === 'name') {
            $mapping[] = $this->assoc($name, $this->propFetch('faker', 'name'));
        }

        if (strtolower($name) === 'firstname') {
            $mapping[] = $this->assoc($name, $this->propFetch('faker', 'firstName'));
        }

        if (strtolower($name) === 'lastname') {
            $mapping[] = $this->assoc($name, $this->propFetch('faker', 'lastName'));
        }

        if (strtolower($name) === 'username') {
            $mapping[] = $this->assoc($name, $this->propFetch('faker', 'userName'));
        }

        if (strtolower($name) === 'email') {
            $mapping[] = $this->assoc($name, $this->propFetch('faker', 'safeEmail'));
        }

        if (strtolower($name) === 'password') {
            $mapping[] = $this->assoc($name, $this->string(bcrypt('password')));
        }

        if (count($mapping)) {
            return $mapping;
        }

        if (array_key_exists($name, $this->foreignKeyColumns)) {
            $mapping[] = $this->assoc($name, $this->methodCall('faker', 'randomElement', [
                $this->var($this->foreignKeyColumns[$name])
            ]));
        } else {
            if (Str::contains(strtolower($name), 'image') || Str::contains(strtolower($name), 'avatar') || Str::contains(strtolower($name), 'photo') || Str::contains(strtolower($name), 'picture')) {
                $this->use(Str::class);
                $mapping[] = $this->assoc($name, $this->concat(
                    $this->concat(
                        $this->string('https://picsum.photos/seed/'),
                        $this->staticCall('Str', 'random')
                    ),
                    $this->string('/200/200')
                ));
            }

            $mapping[] = $this->assoc($name, $this->getFakerCall($name, $type, $length));
        }

        return $mapping;
    }

    /**
     * @param $columnName
     * @param $columnType
     * @param null $length
     * @return ConstFetch|FuncCall|MethodCall|PropertyFetch|StaticCall
     */
    private function getFakerCall($columnName, $columnType, $length = null)
    {
        if ($columnName === 'deleted_at') {
            return $this->const('null');
        }

        if ($columnType === 'string') {
            if ($length) {
                return $this->methodCall('faker', 'text', [$this->int($length)]);
            }

            return $this->propFetch('faker', 'word');
        }

        if ($columnType === 'text') {
            return $this->methodCall('faker', 'realText', [$this->int(1000)]);
        }

        if (str_contains($columnType, 'ger')) {
            $tiny = str_contains($columnType, 'tiny');
            $len = $tiny ? ($length ?? 5) : ($length ?? 99999999);
            return $this->methodCall('faker', 'numberBetween', [
                $this->int(1),
                $this->int($len)
            ]);
        }

        if ($columnType === 'boolean') {
            return $this->propFetch('faker', 'boolean');
        }

        if ($columnType === 'decimal' || $columnType === 'float') {
            return $this->methodCall('faker', 'randomFloat', [
                $this->const('null'),
                $this->int(10),
                $this->int(1000)
            ]);
        }

        if ($columnType === 'timestamp') {
            return $this->funcCall('time');
        }

        if ($columnType === 'dateTime') {
            return $this->funcCall('now');
        }

        $this->use(Str::class);

        if ($columnType === 'uuid') {
            return $this->staticCall('Str', 'uuid');
        }

        return $this->staticCall('Str', 'random');
    }

    /**
     * @param array $preReturnStatements
     * @return void
     */
    private function setPreReturnStatements(array $preReturnStatements): void
    {
        $this->preReturnStatements = $preReturnStatements;
    }
}
