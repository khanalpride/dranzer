<?php

namespace App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations\Builders;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use App\Builders\PHP\MethodBuilder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Builders\PHP\Laravel\Framework\Database\Blueprint;

class OneToManyRelationsBuilder extends EloquentRelationsBuilder
{
    /**
     * @param $relations
     * @return array
     *
     */
    public function parseRelations($relations): array
    {
        $blueprints = collect(app('mutations')->for('database')['blueprints']);

        return collect($relations)
            ->map(static fn ($relation) => $relation['value'])
            ->map(fn ($relation) => $this->parseRelation($relation['type'], $relation, $blueprints))
            ->toArray();
    }

    /**
     * @param $type
     * @param $relation
     * @param Collection $blueprints
     * @return array
     *
     */
    private function parseRelation($type, $relation, Collection $blueprints): array
    {
        $isBelongsToRelation = $type === 'belongsTo';

        $childBlueprintId = $relation['related']['id'] ?? null;

        if (!$childBlueprintId) {
            return [];
        }

        $childModel = $blueprints->first(static fn (Blueprint $blueprint) => $blueprint->getId() === $childBlueprintId);

        if (!$childModel) {
            return [];
        }

        $childModelName = $childModel->getName();

        $imports = $isBelongsToRelation ? [BelongsTo::class] : [HasMany::class];

        $methodName = lcfirst($isBelongsToRelation ? Str::studly($childModelName) : Str::pluralStudly($childModelName));

        $methodBuilder = new MethodBuilder($methodName);

        $methodBuilder
            ->addStatement(
                $this->return(
                    $this->methodCall(
                        'this', $isBelongsToRelation ? 'belongsTo' : 'hasMany', [
                            $this->const("$childModelName::class"),
                        ]
                    )
                )
            )
            ->setReturnType($isBelongsToRelation ? 'BelongsTo' : 'HasMany')
            ->getDocBuilder()
            ->setReturnType($isBelongsToRelation ? 'BelongsTo' : 'HasMany')
            ->addCommentLine("Get the $methodName associated with this model.");

        return [
            'imports' => $imports,
            'builder' => $methodBuilder,
        ];
    }
}
