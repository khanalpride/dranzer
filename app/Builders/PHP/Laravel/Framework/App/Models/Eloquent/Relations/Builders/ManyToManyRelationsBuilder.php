<?php

namespace App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations\Builders;

use Illuminate\Support\Str;
use App\Builders\PHP\MethodBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ManyToManyRelationsBuilder extends EloquentRelationsBuilder
{
    /**
     * @param $modelId
     * @param $relations
     * @return array
     */
    public function parseRelations($modelId, $relations): array
    {
        return collect($relations)
            ->map(static fn ($relation) => $relation['value'])
            ->map(fn ($relation) => $this->parseRelation($modelId, $relation))
            ->toArray();
    }

    /**
     * @param $modelId
     * @param $relation
     * @return array
     */
    private function parseRelation($modelId, $relation): array
    {
        $childBlueprint = $relation['related'] ?? null;

        $parentBlueprint = $relation['source'] ?? null;

        if (!$parentBlueprint || !$childBlueprint) {
            return [];
        }

        if ($modelId !== $parentBlueprint['id']) {
            $childBlueprint = $parentBlueprint;
        }

        $childBlueprintName = $childBlueprint['name'];

        $methodName = lcfirst(Str::pluralStudly($childBlueprintName));

        $imports = [BelongsToMany::class];

        $methodBuilder = new MethodBuilder($methodName);

        $methodBuilder
            ->addStatement(
                $this->return(
                    $this->methodCall(
                        'this', 'belongsToMany', [
                            $this->const("$childBlueprintName::class"),
                        ]
                    )
                )
            )
            ->setReturnType('BelongsToMany')
            ->getDocBuilder()
            ->setReturnType('BelongsToMany')
            ->addCommentLine("Get the $methodName associated with this model.");

        return [
            'imports' => $imports,
            'builder' => $methodBuilder,
        ];
    }
}
