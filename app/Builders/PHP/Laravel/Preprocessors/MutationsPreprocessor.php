<?php

namespace App\Builders\PHP\Laravel\Preprocessors;

use Illuminate\Support\Str;
use App\Builders\Helpers\MutationHelpers;
use App\Services\Mutation\MutationService;

/**
 * Class MutationsPreprocessor
 * @package App\Builders\PHP\Laravel\Preprocessors
 */
class MutationsPreprocessor
{
    /**
     * @var
     */
    private static $projectId;
    /**
     * @var
     */
    private static $projectDir;

    /**
     * @param $mutations
     * @param $projectDir
     * @param $projectId
     */
    public static function preprocess($mutations, $projectDir, $projectId): void
    {
        static::$projectId = $projectId;
        static::$projectDir = $projectDir;

        static::preprocessEloquentRelations($mutations);
    }

    /**
     * @param $mutations
     */
    private static function preprocessEloquentRelations($mutations): void
    {
        static::processHasThroughRelations($mutations);
        static::processManyToManyRelations($mutations);
    }

    /**
     * @param $mutations
     */
    private static function processManyToManyRelations($mutations): void
    {
        $blueprints = collect(MutationHelpers::filter('/^database\/blueprints/', $mutations))
            ->map(static fn ($blueprint) => $blueprint['value'])
            ->filter(fn ($blueprint) => ($blueprint['modelName'] ?? null) && ($blueprint['visible'] ?? true)
            );

        $manyToManyRelations = MutationHelpers::filter('^eloquent\/relations\/regular\/many-to-many', $mutations);

        foreach ($manyToManyRelations as $manyToManyRelation) {
            $manyToManyRelation = $manyToManyRelation['value'];
            $intermediateTable = $manyToManyRelation['intermediateTable'] ?? null;

            if (!$intermediateTable) {
                continue;
            }

            $blueprintId = 'B-Pivot-' . $manyToManyRelation['source']['name'] . '-' . $manyToManyRelation['related']['name'];

            static::createIntermediateTableMutations($blueprintId, $mutations);

            // Schema Relations
            $mutation = MutationHelpers::first("^database\/relations\/$blueprintId", $mutations);

            if (!$mutation) {
                $localColumnName = collect($manyToManyRelation['columns'])
                    ->first(fn ($column) => $column['name'] === strtolower(Str::snake($manyToManyRelation['source']['name'])) . '_id'
                        && $column['type'] === 'unsignedBigInteger'
                        && $column['attributes']['us']
                    );

                if ($localColumnName) {
                    static::createSchemaRelation($blueprintId, $localColumnName, 'source', $manyToManyRelation, $blueprints);
                }

                $localColumnName = collect($manyToManyRelation['columns'])
                    ->first(fn ($column) => $column['name'] === strtolower(Str::snake($manyToManyRelation['related']['name'])) . '_id'
                        && $column['type'] === 'unsignedBigInteger'
                        && $column['attributes']['us']
                    );

                if ($localColumnName) {
                    static::createSchemaRelation($blueprintId, $localColumnName, 'related', $manyToManyRelation, $blueprints);
                }
            }

            // Schema
            $mutation = MutationHelpers::first("^database\/blueprint\/$blueprintId", $mutations);

            if (!$mutation) {
                $schema = [
                    'id'        => $blueprintId,
                    'modelName' => Str::studly(Str::singular($intermediateTable)),
                    'tableName' => $intermediateTable,
                    'columns'   => $manyToManyRelation['columns'],
                    'visible'   => false,
                    'pivot'     => true,
                    'pluralize' => false,
                ];

                MutationService::mutate("database/blueprints/$blueprintId", 'Intermediate Table', $schema, static::$projectId);
            }
        }
    }

    /**
     * @param $schemaId
     * @param $localColumn
     * @param $modelType
     * @param $meta
     * @param $blueprints
     *
     * @noinspection DuplicatedCode
     */
    private static function createSchemaRelation($schemaId, $localColumn, $modelType, $meta, $blueprints): void
    {
        if ($localColumn) {
            $foreignTable = collect($blueprints)->first(fn ($blueprint) => $blueprint['id'] === $meta[$modelType]['id']);
            if ($foreignTable) {
                $foreignColumn = collect($foreignTable['columns'] ?? [])
                    ->first(fn ($column) => $column['attributes']['ai']);

                if ($foreignColumn) {
                    $relationId = Str::random();

                    $relation = [
                        "id"                => $relationId,
                        "localColumn"       => $localColumn['id'],
                        "localTable"        => $schemaId,
                        "foreignColumn"     => $foreignColumn['id'],
                        "foreignTable"      => $foreignTable['id'],
                        "onDeleteReference" => "CASCADE",
                    ];

                    MutationService::mutate("database/relations/$schemaId/$relationId", 'Intermediate Table Relation', $relation, static::$projectId);
                }
            }
        }
    }

    /**
     * @param $mutations
     * @noinspection DuplicatedCode
     */
    private static function processHasThroughRelations($mutations): void
    {
        $blueprints = collect(MutationHelpers::filter('/^database\/blueprints/', $mutations))
            ->map(static fn ($b) => $b['value'])
            ->filter(fn ($blueprint) => ($blueprint['modelName'] ?? null) && ($blueprint['visible'] ?? true)
            );

        $hasOneThroughRelations = MutationHelpers::filter('^eloquent\/relations\/regular\/has-one-through', $mutations);
        $hasManyThroughRelations = MutationHelpers::filter('^eloquent\/relations\/regular\/has-many-through', $mutations);

        $hasThroughRelations = array_merge($hasOneThroughRelations, $hasManyThroughRelations);

        foreach ($hasThroughRelations as $hasThroughRelation) {
            $hasThroughRelation = $hasThroughRelation['value'];
            $intermediateTable = $hasThroughRelation['intermediateTable'] ?? null;

            if (!$intermediateTable) {
                continue;
            }

            if (!($hasThroughRelation['id'] ?? null)) {
                continue;
            }

            $blueprintId = 'B' . $hasThroughRelation['id'];

            static::createIntermediateTableMutations($blueprintId, $mutations);

            // Relations
            $mutation = MutationHelpers::first("^database\/relations\/$blueprintId", $mutations);

            if (!$mutation) {
                $localColumn = collect($hasThroughRelation['columns'] ?? [])
                    ->first(fn ($col) => $col['type'] === 'unsignedBigInteger' && $col['attributes']['us'] && $col['disabled']);

                $foreignColumn = collect($hasThroughRelation['columns'] ?? [])
                    ->first(fn ($col) => $col['name'] === 'id' && $col['attributes']['ai'] && $col['disabled']);

                if ($localColumn && $foreignColumn) {
                    $foreignTable = collect($blueprints)
                        ->first(fn ($blueprint) => $blueprint['id'] === $hasThroughRelation['source']['id']);

                    if ($foreignTable) {
                        $foreignColumn = collect($foreignTable['columns'] ?? [])
                            ->first(fn ($column) => $column['attributes']['ai']);

                        if ($foreignColumn) {
                            $relationId = Str::random();

                            $relation = [
                                "id"                => $relationId,
                                "localColumn"       => $localColumn['id'],
                                "localTable"        => $blueprintId,
                                "foreignColumn"     => $foreignColumn['id'],
                                "foreignTable"      => $foreignTable['id'],
                                "onDeleteReference" => "CASCADE",
                            ];

                            MutationService::mutate("database/relations/$blueprintId/$relationId", 'Intermediate Table Relation', $relation, static::$projectId);
                        }
                    }
                }
            }

            // Blueprint
            $mutation = MutationHelpers::first("^database\/blueprints\/$blueprintId", $mutations);

            if (!$mutation) {
                $blueprint = [
                    'id'        => $blueprintId,
                    'modelName' => Str::studly(Str::singular($intermediateTable)),
                    'tableName' => $intermediateTable,
                    'columns'   => $hasThroughRelation['columns'],
                    'visible'   => false,
                    'singular'  => true,
                ];

                MutationService::mutate("database/blueprints/$blueprintId", 'Intermediate Table', $blueprint, static::$projectId);
            }

            // Related Model Schema
            $related = $hasThroughRelation['related'];
            $relatedModel = $blueprints->first(fn ($blueprint) => $blueprint['id'] === $related['id']);

            if ($relatedModel) {
                $relatedModelId = $relatedModel['id'];
                $relatedModelMutation = MutationHelpers::first("^database\/blueprint\/$relatedModelId", $mutations);

                if ($relatedModelMutation) {
                    $requiredIntermediateTableColumnName = Str::singular($intermediateTable) . '_id';
                    $intermediateTableAIColumn = collect($relatedModelMutation['columns'] ?? [])
                        ->first(fn ($column) => $column['attributes']['ai']);

                    $intermediateTableRefColumn = collect($relatedModelMutation['columns'] ?? [])
                        ->first(fn ($column) => $column['attributes']['us'] && $column['name'] === $requiredIntermediateTableColumnName);

                    if (!$intermediateTableRefColumn) {
                        $intermediateTableRefColumn = [
                            "id"         => Str::random(),
                            "name"       => $requiredIntermediateTableColumnName,
                            "type"       => "unsignedBigInteger",
                            "attributes" => [
                                "ai"     => false,
                                "us"     => true,
                                "n"      => false,
                                "u"      => false,
                                "f"      => false,
                                "ug"     => false,
                                "h"      => false,
                                "length" => null
                            ],
                        ];

                        array_splice($relatedModelMutation['columns'], 1, 0, [$intermediateTableRefColumn]);
                        MutationService::mutate("database/blueprints/$relatedModelId", 'Schema', $relatedModelMutation, static::$projectId);

                        $foreignColumn = collect($hasThroughRelation['columns'])
                            ->first(fn ($column) => $column['attributes']['ai']);

                        if ($foreignColumn && $intermediateTableAIColumn) {
                            $intermediateTableRefColumnId = $intermediateTableRefColumn['id'];
                            $relationId = $intermediateTableRefColumnId;
                            $relation = [
                                "id"                => $relationId,
                                "localColumn"       => $intermediateTableRefColumnId,
                                "localTable"        => $relatedModelId,
                                "foreignColumn"     => $foreignColumn['id'],
                                "foreignTable"      => $blueprintId,
                                "onDeleteReference" => "CASCADE",
                            ];
                            MutationService::mutate("database/relations/$relatedModelId/$relationId", 'Intermediate Table Relation', $relation, static::$projectId);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $blueprintId
     * @param $mutations
     */
    private static function createIntermediateTableMutations($blueprintId, $mutations): void
    {
        $mutation = MutationHelpers::first("^config\/database\/blueprints\/$blueprintId", $mutations);

        if (!$mutation) {
            $schemaConfig = [
                'createModel' => true,
            ];
            MutationService::mutate("config/database/blueprints/$blueprintId", 'Intermediate Table Config', $schemaConfig, static::$projectId);
        }

        $mutation = MutationHelpers::first("^database\/factories\/$blueprintId", $mutations);

        if (!$mutation) {
            $factoryConfig = [
                'createFactory' => true,
                'createSeeder'  => true,
            ];

            MutationService::mutate("database/factories/$blueprintId", 'Intermediate Table Factory Config', $factoryConfig, static::$projectId);
        }
    }
}
