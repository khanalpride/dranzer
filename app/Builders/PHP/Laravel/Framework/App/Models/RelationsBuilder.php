<?php

namespace App\Builders\PHP\Laravel\Framework\App\Models;

use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\ProjectBuilder;
use App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations\Builders\OneToOneRelationsBuilder;
use App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations\Builders\OneToManyRelationsBuilder;
use App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations\Builders\ManyToManyRelationsBuilder;
use App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations\Builders\HasOneThroughRelationsBuilder;
use App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations\Builders\HasManyThroughRelationsBuilder;

class RelationsBuilder
{
    /**
     * Indicates that this builder should not be
     * included in the default build pipeline.
     *
     * @more-info @method ProjectBuilder getBuilderMap
     *
     * @var bool
     */
    public static bool $customBuilder = true;

    /**
     * TODO: Refactor
     *
     * @param $modelId
     * @return Collection
     */
    public function getParsedRelations($modelId): Collection
    {
        $parsed = collect();

        $mutations = app('raw-mutations');

        $oneToOneRelations = MutationHelpers::filter("^eloquent\/relations\/regular\/one-to-one\/$modelId", $mutations);
        $oneToManyRelations = MutationHelpers::filter("^eloquent\/relations\/regular\/one-to-many\/$modelId", $mutations);
        $hasOneThroughRelations = MutationHelpers::filter("^eloquent\/relations\/regular\/has-one-through\/$modelId", $mutations);
        $hasManyThroughRelations = MutationHelpers::filter("^eloquent\/relations\/regular\/has-many-through\/$modelId", $mutations);
        $manyToManyRelations = MutationHelpers::filter("^eloquent\/relations\/regular\/many-to-many\/$modelId", $mutations);

        if (!count($manyToManyRelations)) {
            $manyToManyRelations = MutationHelpers::filter("^eloquent\/relations\/regular\/many-to-many\/.*?$modelId", $mutations);
        }

        $parsed->push((new OneToOneRelationsBuilder)->parseRelations($oneToOneRelations));
        $parsed->push((new OneToManyRelationsBuilder)->parseRelations($oneToManyRelations));
        $parsed->push((new HasOneThroughRelationsBuilder)->parseRelations($hasOneThroughRelations));
        $parsed->push((new HasManyThroughRelationsBuilder)->parseRelations($hasManyThroughRelations));
        $parsed->push((new ManyToManyRelationsBuilder)->parseRelations($modelId, $manyToManyRelations));

        return $parsed;
    }
}
