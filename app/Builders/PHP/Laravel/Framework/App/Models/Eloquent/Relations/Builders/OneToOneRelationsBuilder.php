<?php

namespace App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations\Builders;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use App\Builders\PHP\MethodBuilder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Builders\PHP\Laravel\Framework\Database\Blueprint;

class OneToOneRelationsBuilder extends EloquentRelationsBuilder
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
     * @noinspection DuplicatedCode
     */
    private function parseRelation($type, $relation, Collection $blueprints): array
    {
        $belongsTo = $type === 'belongsTo';

        $childBlueprintId = $relation['related']['id'] ?? null;

        if (!$childBlueprintId) {
            return [];
        }

        $childModel = $blueprints->first(fn (Blueprint $blueprint) => $blueprint->getId() === $childBlueprintId);

        if (!$childModel || !$childModel instanceof Blueprint) {
            return [];
        }

        $childModelName = $childModel->getName();

        $imports = [];

        $imports[] = $belongsTo ? BelongsTo::class : HasOne::class;

        $methodName = lcfirst(Str::studly(Str::singular($childModelName)));

        $methodBuilder = new MethodBuilder($methodName);

        $methodBuilder
            ->addStatement(
                $this->return(
                    $this->methodCall(
                        'this', $belongsTo ? 'belongsTo' : 'hasOne', [
                            $this->const("$childModelName::class"),
                        ]
                    )
                )
            )
            ->setReturnType($belongsTo ? 'BelongsTo' : 'HasOne')
            ->getDocBuilder()
            ->setReturnType($belongsTo ? 'BelongsTo' : 'HasOne')
            ->addCommentLine("Get the $methodName associated with this model.");

        return [
            'imports' => $imports,
            'builder' => $methodBuilder,
        ];
    }
}
