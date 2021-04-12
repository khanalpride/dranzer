<?php

namespace App\Builders\PHP\Laravel\Parsers\Mutations\Modules;

use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\Parsers\Contracts\MutationParser;

/**
 * Class ControllerMutationsParser
 * @package App\Builders\PHP\Laravel\Parsers\Mutations\Modules
 */
class ControllerMutationsParser implements MutationParser
{
    /**
     * @param array $mutations
     * @param string $projectId
     * @return Collection
     */
    public function parse(array $mutations, string $projectId): Collection
    {
        $parsed = [];

        $controllers = collect(MutationHelpers::filter('^controllers', $mutations) ?? [])->map(fn ($c) => $c['value']);
        $webControllers = collect(MutationHelpers::filter('^controllers\/web', $mutations) ?? [])->map(fn ($c) => $c['value']);
        $apiControllers = collect(MutationHelpers::filter('^controllers\/api', $mutations) ?? [])->map(fn ($c) => $c['value']);

        $parsed['controllers'] = $controllers->toArray();
        $parsed['webControllers'] = $webControllers->toArray();
        $parsed['apiControllers'] = $apiControllers->toArray();

        return collect($parsed);
    }
}
