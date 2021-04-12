<?php

namespace App\Builders\Processors\App\Models;

use Closure;
use Illuminate\Support\Str;
use App\Builders\PHP\MethodBuilder;
use Illuminate\Foundation\Auth\User;
use Illuminate\Database\Eloquent\Model;
use App\Builders\Helpers\PipelineHelpers;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\PHP\Constants\PHPStormInspections;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Builders\PHP\Laravel\Framework\App\Models\ModelBuilder;
use App\Builders\PHP\Laravel\Framework\Database\Blueprint;
use App\Builders\PHP\Laravel\Framework\App\Models\UserModelBuilder;
use App\Builders\PHP\Laravel\Framework\Database\TableColumn;

/**
 * Class ModelsProcessor
 * @package App\Builders\Processors\App\Models
 */
class ModelsProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $blueprints = app('mutations')->for('database')['blueprints'];

        foreach ($blueprints as $blueprint) {
            $this->createModelFromBlueprint($blueprint);
        }

        $next($builder);

        return true;
    }

    /**
     * @param TableColumn $column
     * @param ModelBuilder $modelBuilder
     * @return void
     */
    private function processColumn(TableColumn $column, ModelBuilder $modelBuilder): void
    {
        $columnName = $column->getName();

        $columnType = $column->getType();

        $fillable = $column->isFillable();

        $hidden = $column->isHidden();

        if ($fillable) {
            $modelBuilder->addFillable($columnName);
        }

        if ($hidden) {
            $modelBuilder->addHidden($columnName);
        }

        if (Str::contains($columnType, 'date') || Str::contains($columnType, 'time')) {
            $modelBuilder->addDate($columnName);
        }

        if ($columnType === 'boolean') {
            $this
                ->addBooleanAttributeGetter($columnName, $modelBuilder)
                ->addBooleanAttributeSetter($columnName, $modelBuilder);
        }

    }

    /**
     * @param string $primaryKeyColumn
     * @param ModelBuilder $modelBuilder
     * @return void
     */
    private function addRouteKeyNameMethod(string $primaryKeyColumn, ModelBuilder $modelBuilder): void
    {
        $methodBuilder = new MethodBuilder('getRouteKeyName');

        $methodBuilder
            ->setReturnType('string')
            ->addStatement(
                $this->return(
                    $this->string($primaryKeyColumn)
                )
            )
            ->getDocBuilder()
            ->addCommentLine('Get the route key for the model.')
            ->setReturnType('string');

        $modelBuilder->addMethodBuilder($methodBuilder);

    }

    /**
     * @param Blueprint $blueprint
     * @return void
     */
    private function createModelFromBlueprint(Blueprint $blueprint): void
    {
        $modelsPath = app('project-dir') . '/app/Models';

        $modelName = $blueprint->getName();

        $isUserModel = $modelName === 'User';

        $authenticatableClass = User::class;

        $baseModelClass = Model::class;

        // Use a dedicated class for building the User model.
        $modelBuilder = $isUserModel
            ? new UserModelBuilder
            : new ModelBuilder;

        $modelBuilder
            ->setOutputDir($modelsPath)
            ->setFilename("$modelName.php");

        if (!$isUserModel) {
            $modelBuilder->use($baseModelClass);
        } else {
            $modelBuilder->setAuthenticatable($authenticatableClass);
        }

        $modelBuilder
            ->setModelId($blueprint->getId())
            ->setModelName($modelName)
            ->setTableName($blueprint->getTable())
            ->setColumns($blueprint->getColumns())
            ->setScopes($blueprint->getScopes())
            ->setUnguarded($blueprint->isUnguarded())
            ->setSoftDelete($blueprint->shouldSoftDelete())
            ->overrideClassDefinition(
                [
                    'name' => $modelName,
                    'extend' => $isUserModel ? 'Authenticatable' : 'Model',
                ]
            );

        $modelBuilder
            ->use(HasFactory::class)
            ->addTraits(['HasFactory']);

        $columns = $blueprint->getColumns();

        $primaryKeyColumn = 'id';

        $useDefaultTimestamps = collect($columns)
                ->first(
                    static fn (TableColumn $column) => $column->getName() === 'updated_at' ||
                        $column->getName() === 'created_at'
                ) !== null;

        foreach ($columns as $column) {
            if (!$column instanceof TableColumn || $column->isInvalid()) {
                continue;
            }

            if ($column->isAutoIncrementing() && $column->isUnsigned()) {
                $primaryKeyColumn = $column->getName();
            }

            $this->processColumn($column, $modelBuilder);
        }

        if ($primaryKeyColumn !== 'id') {
            $this->addRouteKeyNameMethod($primaryKeyColumn, $modelBuilder);
        }

        $modelBuilder->setUseDefaultTimestamps($useDefaultTimestamps);

        $modelBuilder->prepare();

        PipelineHelpers::processBuilderProcessors($modelBuilder)
            ->then(static fn ($processedBuilder) => $processedBuilder->build());

    }

    /**
     * @param $attributeName
     * @param ModelBuilder $modelBuilder
     * @return ModelsProcessor
     */
    private function addBooleanAttributeGetter($attributeName, ModelBuilder $modelBuilder): ModelsProcessor
    {
        $getAttrMethodBuilder = $modelBuilder->getNewMethodBuilder(
            'get' . Str::studly($attributeName) . 'Attribute'
        );

        $getAttrMethodBuilder->addParameter($this->param('value'))
            ->addStatement(
                $this->return(
                    $this->ternary(
                        $this->strictEquals($this->var('value'), $this->int(1)),
                        $this->string('Yes'),
                        $this->string('No')
                    )
                )
            )
            ->setReturnType('string')
            ->getDocBuilder()
            ->setReturnType('string')
            ->addCommentLine('Get a human-friendly representation of the ' . $attributeName . ' attribute.');

        $modelBuilder->addMethodBuilder($getAttrMethodBuilder);

        return $this;
    }

    /**
     * @param $attributeName
     * @param ModelBuilder $modelBuilder
     * @return void
     */
    private function addBooleanAttributeSetter($attributeName, ModelBuilder $modelBuilder): void
    {
        $setAttrMethodBuilder = $modelBuilder->getNewMethodBuilder(
            'set' . Str::studly($attributeName) . 'Attribute'
        );

        $setAttrMethodBuilder->addParameter($this->param('value'))
            ->addStatements(
                [
                    $this->if(
                        $this->boolOr(
                            $this->funcCall('is_numeric', [$this->var('value')]),
                            $this->funcCall('is_bool', [$this->var('value')]),
                        ), [
                            $this->nestedAssign(
                                $this->arrayFetch(
                                    $this->propFetch('this', 'attributes'),
                                    $this->string($attributeName)
                                ),
                                $this->var('value')
                            ),
                            $this->return($this->nopExpr()),
                        ]
                    ),
                    $this->nop(),
                    $this->inlineAssign(
                        'converted',
                        $this->ternary(
                            $this->strictEquals($this->var('value'), $this->string('Yes')),
                            $this->int(1),
                            $this->int(0)
                        )
                    ),
                    $this->nop(),
                    $this->nestedAssign(
                        $this->arrayFetch(
                            $this->propFetch('this', 'attributes'),
                            $this->string($attributeName)
                        ),
                        $this->var('converted')
                    ),
                ]
            )
            ->addSuppressedInspection(PHPStormInspections::PHP_MISSING_RETURN_TYPE_INSPECTION)
            ->getDocBuilder()
            ->addCommentLine('Convert the value of ' . $attributeName . ' attribute if necessary.');

        $modelBuilder->addMethodBuilder($setAttrMethodBuilder);

    }
}
