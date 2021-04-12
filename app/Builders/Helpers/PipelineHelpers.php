<?php

namespace App\Builders\Helpers;

use Illuminate\Pipeline\Pipeline;

class PipelineHelpers
{
    public static function processBuilderProcessors($builder)
    {
        return app(Pipeline::class)
            ->send($builder)
            ->through($builder->getProcessors())
            ->via('process');
    }
}
