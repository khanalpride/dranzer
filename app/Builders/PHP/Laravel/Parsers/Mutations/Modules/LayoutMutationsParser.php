<?php

namespace App\Builders\PHP\Laravel\Parsers\Mutations\Modules;

use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\Parsers\Contracts\MutationParser;

/**
 * Class LayoutMutationsParser
 * @package App\Builders\PHP\Laravel\Parsers\Mutations\Modules
 */
class LayoutMutationsParser implements MutationParser
{
    /**
     * @param array $mutations
     * @param string $projectId
     * @return Collection
     */
    public function parse(array $mutations, string $projectId): Collection
    {
        $layouts = collect(MutationHelpers::filter('^ui\/layouts', $mutations) ?? [])
            ->map(fn ($l) => $l['value'])
            ->toArray();

        $parsed = [
            'layouts' => $layouts,
        ];

        return collect($parsed);
    }
}
