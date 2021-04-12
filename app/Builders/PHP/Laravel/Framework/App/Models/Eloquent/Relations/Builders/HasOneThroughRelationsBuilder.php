<?php

namespace App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations\Builders;

use Illuminate\Support\Str;
use App\Builders\PHP\MethodBuilder;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class HasOneThroughRelationsBuilder extends EloquentRelationsBuilder
{
    /**
     * @param $relations
     * @return array
     */
    public function parseRelations($relations): array
    {
        return collect($relations)
            ->map(static fn ($relation) => $relation['value'])
            ->map(fn ($relation) => $this->parseRelation($relation))
            ->toArray();
    }

    /**
     * @param $relation
     * @return array
     */
    private function parseRelation($relation): array
    {
        $childBlueprint = $relation['related'] ?? null;

        $intermediateTable = $relation['intermediateTable'] ?? null;

        if (!$childBlueprint || !$intermediateTable) {
            return [];
        }

        $intermediateModel = Str::studly(Str::singular($intermediateTable));

        $childBlueprintName = $childBlueprint['name'];

        $methodName = Str::camel($childBlueprintName);

        $imports = [HasOneThrough::class];

        $methodBuilder = new MethodBuilder($methodName);

        $methodBuilder->addStatement(
            $this->return(
                $this->methodCall(
                    'this', 'hasOneThrough', [
                        $this->const("$childBlueprintName::class"),
                        $this->const("$intermediateModel::class"),
                    ]
                )
            )
        )
            ->setReturnType('HasOneThrough')
            ->getDocBuilder()
            ->setReturnType('HasOneThrough')
            ->addCommentLine("Get the $methodName associated with this model.");

        return [
            'imports' => $imports,
            'builder' => $methodBuilder,
        ];
    }
}
