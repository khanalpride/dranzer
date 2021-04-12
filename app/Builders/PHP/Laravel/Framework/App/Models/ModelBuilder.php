<?php

/**
 * @noinspection PhpUnused
 * @noinspection UnknownInspectionInspection
 */

namespace App\Builders\PHP\Laravel\Framework\App\Models;

use Illuminate\Support\Str;
use App\Builders\PHP\ClassBuilder;
use App\Builders\PHP\DocBlockBuilder;
use App\Builders\PHP\PropertyBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Builders\Processors\App\Models\ModelProcessor;
use App\Builders\PHP\Laravel\Framework\Database\TableColumn;
use App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Scope\ModelScope;

/**
 * Class ModelBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Models
 */
class ModelBuilder extends ClassBuilder
{
    /**
     * @var bool
     */
    public static bool $customBuilder = true;
    /**
     * @var array|string[]
     */
    protected array $processors = [
        ModelProcessor::class,
    ];
    /**
     * @var string
     */
    protected string $namespace = 'App\Models';
    /**
     * @var string
     */
    protected string $modelName;
    /**
     * @var string
     */
    protected string $tableName;
    /**
     * @var string
     */
    private string $modelId;
    /**
     * @var array
     */
    private array $fillable = [];
    /**
     * @var array
     */
    private array $hidden = [];
    /**
     * @var array
     */
    private array $dates = [];
    /**
     * @var array
     */
    private array $casts = [];
    /**
     * @var array
     */
    private array $useStatements = [];
    /**
     * @var array
     */
    private array $useAsStatements = [];
    /**
     * @var bool
     */
    private bool $softDelete = false;
    /**
     * @var bool
     */
    private bool $unguarded = false;
    /**
     * @var array
     */
    private array $columns = [];
    /**
     * @var array
     */
    private array $modelCustomDocBlockProperties = [];
    /**
     * @var bool
     */
    private bool $useDefaultTimestamps = true;
    /**
     * @var bool
     */
    private bool $processFilterables = true;
    /**
     * @var bool
     */
    private bool $buildPresenter = true;
    /**
     * @var array
     */
    private array $scopes = [];
    /**
     * @var
     */
    private $model;
    /**
     * @var PropertyBuilder $tablePropertyBuilder
     */
    private PropertyBuilder $tablePropertyBuilder;
    /**
     * @var PropertyBuilder $castsPropertyBuilder
     */
    private PropertyBuilder $castsPropertyBuilder;
    /**
     * @var PropertyBuilder $datesPropertyBuilder
     */
    private PropertyBuilder $datesPropertyBuilder;
    /**
     * @var PropertyBuilder $fillablePropertyBuilder
     */
    private PropertyBuilder $guardedPropertyBuilder;
    /**
     * @var PropertyBuilder $fillablePropertyBuilder
     */
    private PropertyBuilder $fillablePropertyBuilder;
    /**
     * @var PropertyBuilder $hiddenPropertyBuilder
     */
    private PropertyBuilder $hiddenPropertyBuilder;
    /**
     * @var PropertyBuilder $timestampsPropertyBuilder
     */
    private PropertyBuilder $timestampsPropertyBuilder;

    /**
     * @return ModelBuilder
     */
    public function prepare(): ModelBuilder
    {
        return $this
            ->instantiatePropertyBuilders()
            ->buildUseStatements()
            ->setupTraits()
            ->setDefaults();
    }

    /**
     * @return ModelBuilder
     */
    private function instantiatePropertyBuilders(): ModelBuilder
    {
        return $this
            ->setTablePropertyBuilder($this->getNewPropertyBuilder('table'))
            ->setCastsPropertyBuilder($this->getNewPropertyBuilder('casts'))
            ->setDatesPropertyBuilder($this->getNewPropertyBuilder('dates'))
            ->setGuardedPropertyBuilder($this->getNewPropertyBuilder('guarded'))
            ->setFillablePropertyBuilder($this->getNewPropertyBuilder('fillable'))
            ->setHiddenPropertyBuilder($this->getNewPropertyBuilder('hidden'))
            ->setTimestampsPropertyBuilder($this->getNewPropertyBuilder('timestamps'));
    }

    /**
     * @return ModelBuilder
     */
    protected function buildUseStatements(): ModelBuilder
    {
        if ($this->shouldSoftDelete()) {
            $this->use(SoftDeletes::class);
        }

        return $this;
    }

    /**
     * @return ModelBuilder
     */
    private function setDefaults(): ModelBuilder
    {
        $this
            ->getTablePropertyBuilder()
            ->makeProtected()
            ->getDocBuilder()
            ->addCommentLine('The name of the table this model represents.')
            ->addVar('string');

        $this
            ->getGuardedPropertyBuilder()
            ->makeProtected()
            ->getDocBuilder()
            ->addCommentLine("The attributes that aren't mass assignable.")
            ->addVar('array');

        $this
            ->getCastsPropertyBuilder()
            ->makeProtected()
            ->getDocBuilder()
            ->addCommentLine('The attributes that should be cast to native types.')
            ->addVar('array');

        $this
            ->getDatesPropertyBuilder()
            ->makeProtected()
            ->getDocBuilder()
            ->addCommentLine('The attributes that should be mutated to dates.')
            ->addVar('array');

        $this
            ->getFillablePropertyBuilder()
            ->makeProtected()
            ->getDocBuilder()
            ->addCommentLine('The attributes that are mass assignable.')
            ->addVar('array');

        $this
            ->getHiddenPropertyBuilder()
            ->makeProtected()
            ->getDocBuilder()
            ->addCommentLine('The attributes that should be hidden for arrays.')
            ->addVar('array');

        $this
            ->getTimestampsPropertyBuilder()
            ->makePublic()
            ->getDocBuilder()
            ->addCommentLine('Whether to use the default timestamps.')
            ->addVar('bool');

        return $this;
    }

    /**
     * @return bool
     */
    public function useTimestamps(): bool
    {
        return $this->useDefaultTimestamps;
    }

    /**
     * @param ModelScope $scope
     * @return void
     */
    private function processScope(ModelScope $scope): void
    {
        $methodBuilder = $this->getNewMethodBuilder($scope->getFormattedName());

        $methodBuilder->addParameters(
            [
                $this->param('query', 'Builder'),
                $this->param('value'),
            ]
        )
            ->addStatement(
                $this->return(
                    $this->methodCall(
                        'query', 'where', [
                            $this->string($scope->getColumnName() ?? 'some.column'),
                            $this->string('='),
                            $this->var('value'),
                        ]
                    )
                )
            )
            ->setReturnType('Builder');

        $this->addMethodBuilder($methodBuilder);

    }

    /**
     * @return ModelBuilder
     */
    private function processScopes(): ModelBuilder
    {
        $scopes = $this->scopes;

        if (count($scopes)) {
            $this->use(Builder::class);
        }

        foreach ($scopes as $scope) {
            $this->processScope($scope);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function build(): bool
    {
        return $this
            ->processScopes()
            ->buildClass()
            ->toDisk();
    }

    /**
     * @return ModelBuilder
     */
    protected function buildClass(): ModelBuilder
    {
        if ($tableName = $this->tableName) {
            $defaultTableName = Str::snake(Str::pluralStudly($this->modelName));

            // Only add the table property if the provided table name
            // doesn't match the default table name (pluralized, snake cased model name).
            if ($tableName !== $defaultTableName) {
                $this->addTableProperty();
            }
        }

        if ($this->isUnguarded()) {
            $this->addGuardedProperty();
        }

        if (count($this->getFillable())) {
            $this->addFillableProperty();
        }

        if (count($this->getHidden())) {
            $this->addHiddenProperty();
        }

        if (count($this->casts)) {
            $this->addCastsProperty();
        }

        if (count($this->getDates())) {
            $this->addDatesProperty();
        }

        if (!$this->useDefaultTimestamps) {
            $this->addTimestampsProperty();
        }

        $this->buildRelations()
            ->createDocBlock();

        return $this;
    }

    /**
     *
     * @return ModelBuilder
     */
    private function buildRelations(): ModelBuilder
    {
        $modelId = $this->getModelId();

        $parsedRelations = (new RelationsBuilder)->getParsedRelations($modelId)->collapse();

        $imported = [];

        foreach ($parsedRelations as $parsedRelation) {
            $imports = $parsedRelation['imports'] ?? [];
            foreach ($imports as $import) {
                if (!in_array($import, $imported, true)) {
                    $this->use($import);
                    $imported[] = $import;
                }
            }

            if ($builder = $parsedRelation['builder']) {
                $this->addMethodBuilder($builder);
            }
        }

        return $this;
    }

    /**
     * @param $property
     * @param $targetType
     * @return ModelBuilder
     */
    public function addCast($property, $targetType): ModelBuilder
    {
        if (!array_key_exists($property, $this->casts)) {
            $this->casts[] = $this->assoc($property, $targetType);
        }
        return $this;
    }

    /**
     * @return void
     */
    private function addCastsProperty(): void
    {
        $this
            ->getCastsPropertyBuilder()
            ->setValue($this->casts);

        $this->addPropertyBuilder($this->getCastsPropertyBuilder());
    }

    /**
     * @param $date
     * @return ModelBuilder
     */
    public function addDate($date): ModelBuilder
    {
        if (!in_array($date, $this->dates, true)) {
            $this->dates[] = $date;
        }

        return $this;
    }

    /**
     * @return void
     */
    private function addDatesProperty(): void
    {
        $this
            ->getDatesPropertyBuilder()
            ->setValue($this->getDates());

        $this->addPropertyBuilder($this->getDatesPropertyBuilder());
    }

    /**
     * @param $fillable
     * @return ModelBuilder
     */
    public function addFillable($fillable): ModelBuilder
    {
        if (!in_array($fillable, $this->fillable, true)) {
            $this->fillable[] = $fillable;
        }

        return $this;
    }

    /**
     * @return void
     */
    private function addFillableProperty(): void
    {
        $this
            ->getFillablePropertyBuilder()
            ->setValue($this->getFillable());

        $this->addPropertyBuilder($this->getFillablePropertyBuilder());
    }

    /**
     * @return void
     */
    private function addGuardedProperty(): void
    {
        $this
            ->getGuardedPropertyBuilder()
            ->setValue(
                $this->arr([])
            );

        $this->addPropertyBuilder($this->getGuardedPropertyBuilder());
    }

    /**
     * @param $hidden
     * @return ModelBuilder
     */
    public function addHidden($hidden): ModelBuilder
    {
        if (!in_array($hidden, $this->hidden, true)) {
            $this->hidden[] = $hidden;
        }
        return $this;
    }

    /**
     * @return void
     */
    private function addHiddenProperty(): void
    {
        $this
            ->getHiddenPropertyBuilder()
            ->setValue($this->getHidden());

        $this->addPropertyBuilder($this->getHiddenPropertyBuilder());
    }

    /**
     * @return void
     */
    private function addTableProperty(): void
    {
        $this
            ->getTablePropertyBuilder()
            ->setValue($this->tableName);

        $this->addPropertyBuilder($this->getTablePropertyBuilder());
    }

    /**
     * @return void
     */
    private function addTimestampsProperty(): void
    {
        $this
            ->getTimestampsPropertyBuilder()
            ->setValue($this->useDefaultTimestamps);

        $this->addPropertyBuilder($this->getTimestampsPropertyBuilder());
    }

    /**
     * @param string $useStatement
     * @return ModelBuilder
     */
    public function addUseStatement(string $useStatement): ModelBuilder
    {
        $this->useStatements[] = $useStatement;

        return $this;
    }

    /**
     * @return void
     */
    private function createDocBlock(): void
    {
        $imported = [];

        $docBlockProperties = [];

        foreach ($this->columns as $column) {
            if (!$column instanceof TableColumn || $column->isInvalid()) {
                continue;
            }

            $columnName = $column->getName();

            $type = $column->getType() ?? 'mixed';

            $conversionMap = [
                'int'       => 'int',
                'increment' => 'int',
                'unsigned'  => 'int',
                'string'    => 'string',
                'text'      => 'string',
                'char'      => 'string',
                'remember'  => 'string',
                'mac'       => 'string',
                'uuid'      => 'string',
                'ip'        => 'string',
                'double'    => 'double',
                'float'     => 'float',
                'enum'      => 'array',
                'json'      => 'object',
                'time'      => 'DateTime',
                'date'      => 'DateTime',
                'soft'      => 'DateTime',
            ];

            $importMap = [
                'DateTime' => 'DateTime',
            ];

            $matchedType = false;

            foreach ($conversionMap as $sourceType => $targetType) {
                if (stripos($type, $sourceType) !== false) {
                    $type = $targetType;
                    $matchedType = true;
                    break;
                }
            }

            if (!$matchedType) {
                $type = 'mixed';
            }

            if (array_key_exists($type, $importMap)) {
                $import = $importMap[$type];
                if (!in_array($import, $imported, true)) {
                    $this->use($import);
                    $imported[] = $import;
                }
            }

            $docBlockProperties[] = [
                'name' => $columnName,
                'type' => $type
            ];
        }

        $docBlockProperties = array_merge($docBlockProperties, $this->modelCustomDocBlockProperties);

        $docBlockProperties = array_map(
            static fn ($property) => '@property ' . $property['type'] . ' ' . $property['name'],
            $docBlockProperties
        );

        $docBlockBuilder = new DocBlockBuilder;

        $docBlockBuilder
            ->prependNewLine()
            ->addCommentLine("App\Models\\$this->modelName")
            ->addCommentLine();

        collect($docBlockProperties)
            ->each(static fn ($property) => $docBlockBuilder->addCommentLine($property));

        $this
            ->getClass()
            ->setDocComment($docBlockBuilder->getDocBlock());

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
     * @return ModelBuilder
     */
    public function setScopes(array $scopes): ModelBuilder
    {
        $this->scopes = $scopes;

        return $this;
    }

    /**
     * @return array
     */
    public function getFillable(): array
    {
        return $this->fillable;
    }

    /**
     * @param bool $timestamps
     */
    public function setUseDefaultTimestamps(bool $timestamps): void
    {
        $this->useDefaultTimestamps = $timestamps;
    }

    /**
     * @return string
     */
    public function getModelName(): string
    {
        return $this->modelName;
    }

    /**
     * @param string $modelName
     * @return ModelBuilder
     */
    public function setModelName(string $modelName): ModelBuilder
    {
        $this->modelName = $modelName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     * @return ModelBuilder
     */
    public function setTableName(string $tableName): ModelBuilder
    {
        $this->tableName = $tableName;

        return $this;
    }

    /**
     * @param bool $processFilterables
     * @return ModelBuilder
     */
    public function setProcessFilterables(bool $processFilterables): ModelBuilder
    {
        $this->processFilterables = $processFilterables;

        return $this;
    }

    /**
     * @param bool $buildPresenter
     * @return ModelBuilder
     */
    public function setBuildPresenter(bool $buildPresenter): ModelBuilder
    {
        $this->buildPresenter = $buildPresenter;

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
     * @return ModelBuilder
     */
    public function setColumns(array $columns): ModelBuilder
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @param array $modelCustomDocBlockProperties
     */
    public function setModelCustomDocBlockProperties(array $modelCustomDocBlockProperties): void
    {
        $this->modelCustomDocBlockProperties = $modelCustomDocBlockProperties;
    }

    /**
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param mixed $model
     */
    public function setModel($model): void
    {
        $this->model = $model;
    }

    /**
     * @return array
     */
    public function getHidden(): array
    {
        return $this->hidden;
    }

    /**
     * @return array
     */
    public function getDates(): array
    {
        return $this->dates;
    }

    /**
     * @return bool
     */
    public function shouldSoftDelete(): bool
    {
        return $this->softDelete;
    }

    /**
     * @return array
     */
    public function getUseStatements(): array
    {
        return $this->useStatements;
    }

    /**
     * @param array $useStatements
     * @return ModelBuilder
     */
    public function setUseStatements(array $useStatements): ModelBuilder
    {
        $this->useStatements = $useStatements;
        return $this;
    }

    /**
     * @return array
     */
    public function getUseAsStatements(): array
    {
        return $this->useAsStatements;
    }

    /**
     * @param array $classDefinition
     * @return ModelBuilder
     */
    public function overrideClassDefinition(array $classDefinition): ModelBuilder
    {
        $this->classDefinition = $classDefinition;

        $this->updateClassDefinition();

        return $this;
    }

    /**
     * @param bool $softDelete
     * @return ModelBuilder
     */
    public function setSoftDelete(bool $softDelete): ModelBuilder
    {
        $this->softDelete = $softDelete;

        return $this;
    }

    /**
     * @return PropertyBuilder
     */
    public function getTablePropertyBuilder(): PropertyBuilder
    {
        return $this->tablePropertyBuilder;
    }

    /**
     * @param PropertyBuilder $tablePropertyBuilder
     * @return ModelBuilder
     */
    public function setTablePropertyBuilder(PropertyBuilder $tablePropertyBuilder): ModelBuilder
    {
        $this->tablePropertyBuilder = $tablePropertyBuilder;

        return $this;
    }

    /**
     * @return PropertyBuilder
     */
    public function getCastsPropertyBuilder(): PropertyBuilder
    {
        return $this->castsPropertyBuilder;
    }

    /**
     * @param PropertyBuilder $castsPropertyBuilder
     * @return ModelBuilder
     */
    public function setCastsPropertyBuilder(PropertyBuilder $castsPropertyBuilder): ModelBuilder
    {
        $this->castsPropertyBuilder = $castsPropertyBuilder;

        return $this;
    }

    /**
     * @return PropertyBuilder
     */
    public function getDatesPropertyBuilder(): PropertyBuilder
    {
        return $this->datesPropertyBuilder;
    }

    /**
     * @param PropertyBuilder $datesPropertyBuilder
     * @return ModelBuilder
     */
    public function setDatesPropertyBuilder(PropertyBuilder $datesPropertyBuilder): ModelBuilder
    {
        $this->datesPropertyBuilder = $datesPropertyBuilder;

        return $this;
    }

    /**
     * @return PropertyBuilder
     */
    public function getFillablePropertyBuilder(): PropertyBuilder
    {
        return $this->fillablePropertyBuilder;
    }

    /**
     * @param PropertyBuilder $fillablePropertyBuilder
     * @return ModelBuilder
     */
    public function setFillablePropertyBuilder(PropertyBuilder $fillablePropertyBuilder): ModelBuilder
    {
        $this->fillablePropertyBuilder = $fillablePropertyBuilder;

        return $this;
    }

    /**
     * @return PropertyBuilder
     */
    public function getHiddenPropertyBuilder(): PropertyBuilder
    {
        return $this->hiddenPropertyBuilder;
    }

    /**
     * @param PropertyBuilder $hiddenPropertyBuilder
     * @return ModelBuilder
     */
    public function setHiddenPropertyBuilder(PropertyBuilder $hiddenPropertyBuilder): ModelBuilder
    {
        $this->hiddenPropertyBuilder = $hiddenPropertyBuilder;

        return $this;
    }

    /**
     * @return PropertyBuilder
     */
    public function getTimestampsPropertyBuilder(): PropertyBuilder
    {
        return $this->timestampsPropertyBuilder;
    }

    /**
     * @param PropertyBuilder $timestampsPropertyBuilder
     * @return ModelBuilder
     */
    public function setTimestampsPropertyBuilder(PropertyBuilder $timestampsPropertyBuilder): ModelBuilder
    {
        $this->timestampsPropertyBuilder = $timestampsPropertyBuilder;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUsingDefaultTimestamps(): bool
    {
        return $this->useDefaultTimestamps;
    }

    /**
     * @return bool
     */
    public function shouldBuildPresenter(): bool
    {
        return $this->buildPresenter;
    }

    /**
     * @return bool
     */
    public function shouldProcessFilterables(): bool
    {
        return $this->processFilterables;
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
     * @return ModelBuilder
     */
    public function setUnguarded(bool $unguarded): ModelBuilder
    {
        $this->unguarded = $unguarded;

        return $this;
    }

    /**
     * @return PropertyBuilder
     */
    public function getGuardedPropertyBuilder(): PropertyBuilder
    {
        return $this->guardedPropertyBuilder;
    }

    /**
     * @param PropertyBuilder $guardedPropertyBuilder
     * @return ModelBuilder
     */
    public function setGuardedPropertyBuilder(PropertyBuilder $guardedPropertyBuilder): ModelBuilder
    {
        $this->guardedPropertyBuilder = $guardedPropertyBuilder;

        return $this;
    }

    /**
     * @return string
     */
    public function getModelId(): string
    {
        return $this->modelId;
    }

    /**
     * @param string $modelId
     * @return ModelBuilder
     */
    public function setModelId(string $modelId): ModelBuilder
    {
        $this->modelId = $modelId;

        return $this;
    }

    /**
     * @return ModelBuilder
     */
    protected function setupTraits(): ModelBuilder
    {
        if ($this->shouldSoftDelete()) {
            $this->addTrait('SoftDeletes');
        }

        return $this->addTrait('HasFactory');
    }

}
