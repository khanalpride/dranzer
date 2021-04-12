<?php

namespace App\Builders\PHP\Laravel\Parsers\Mutations\Modules;

use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\Parsers\Contracts\MutationParser;

/**
 * Class MiddlewareMutationsParser
 * @package App\Builders\PHP\Laravel\Parsers\Mutations\Modules
 */
class MiddlewareMutationsParser implements MutationParser
{
    /**
     * @param array $mutations
     * @param string $projectId
     * @return Collection
     */
    public function parse(array $mutations, string $projectId): Collection
    {
        $middlewares = MutationHelpers::first('^middlewares', $mutations) ?? [
                'minifyHtml'                => true,
                'validatePostSize'          => true,
                'trimStrings'               => true,
                'convertEmptyStringsToNull' => true,
            ];

        return collect($middlewares);
    }
}
