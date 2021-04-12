<?php

namespace App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations\Builders;

use App\Builders\PHP\Helpers\BuilderHelpers;
use App\Builders\PHP\Laravel\ProjectBuilder;

class EloquentRelationsBuilder
{
    use BuilderHelpers;

    /**
     * Indicates that this builder should not be
     * included in the default build pipeline.
     *
     * @more-info @method ProjectBuilder getBuilderMap
     *
     * @var bool
     */
    public static bool $customBuilder = true;
}
