<?php

namespace App\Builders\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use App\Builders\PHP\Laravel\Framework\Database\Blueprint;

class BlueprintHelpers
{
    /**
     * @param $typeHintedModels
     * @param $blueprints
     * @return array
     */
    public static function resolveTypeHintedModels($typeHintedModels, $blueprints): array
    {
        $hintedModels = [];

        foreach ($typeHintedModels as $hintedModel) {
            $hintedModelId = $hintedModel['id'];
            $blueprint = collect($blueprints)->first(fn (Blueprint $blueprint) => $blueprint->getId() === $hintedModelId);

            if (!$blueprint) {
                continue;
            }

            $hintedModelName = $blueprint->getName();
            $hintedModelTableName = $blueprint->getTable() ?? Str::snake(Str::plural($hintedModelName));

            if (!$hintedModelName) {
                continue;
            }

            $hintedModels[] = [
                'model'    => $hintedModelName,
                'table'    => $hintedModelTableName,
                'singular' => Str::snake(Str::singular($hintedModelName))
            ];
        }

        return collect($hintedModels)
            ->sort(function ($a, $b) {
                $aLen = strlen($a['model']) + strlen($a['table']);
                $bLen = strlen($b['model']) + strlen($b['table']);
                return $aLen - $bLen;
            })
            ->toArray();
    }

    public static function getModelNameFromId($modelId)
    {
        $blueprint = static::getBlueprintFromId($modelId);

        return $blueprint['modelName'] ?? null;
    }

    public static function getBlueprintFromId($id)
    {
        $blueprints = app('mutations')->for('database')['blueprints'];

        $blueprint = collect($blueprints)->first(fn (Blueprint $blueprint) => $blueprint->getId() === $id);

        return $blueprint ?? null;
    }

    /**
     * @param Blueprint $blueprint
     * @param Collection $blueprints
     * @param array $relations
     * @return array
     */
    public static function getDependencyMapForBlueprint(Blueprint $blueprint, Collection $blueprints, array $relations): array
    {
        $blueprintId = $blueprint->getId();

        $depMap = [];

        if (!count($relations)) {
            return [];
        }

        foreach ($relations as $relation) {
            $foreignBlueprint = $blueprints->first(
                static function (Blueprint $blueprint) use ($relation) {
                    return $blueprint->getId() === $relation['foreignTable'];
                }
            );

            if (!$foreignBlueprint || $blueprintId !== $relation['localTable']) {
                continue;
            }

            $depMap[] = $foreignBlueprint->getTable();
        }

        return $depMap;
    }
}
