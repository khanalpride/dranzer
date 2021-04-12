<?php

namespace App\Builders\PHP\Laravel\Parsers\Mutations\Modules;

use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\Parsers\Contracts\MutationParser;

/**
 * Class RequestMutationsParser
 * @package App\Builders\PHP\Laravel\Parsers\Mutations\Modules
 */
class RequestMutationsParser implements MutationParser
{
    /**
     * @param array $mutations
     * @param string $projectId
     * @return Collection
     */
    public function parse(array $mutations, string $projectId): Collection
    {
        $trustProxiesConfig = MutationHelpers::first('^request\/proxies', $mutations) ?? [
                'headers' => ['HEADER_X_FORWARDED_ALL'],
                'proxies' => []
            ];

        $headers = $trustProxiesConfig['headers'] ?? [];

        $proxies = collect($trustProxiesConfig['proxies'])->map(fn ($p) => $p['value'])->toArray();

        $parsed = [
            'headers' => $headers,
            'proxies' => $proxies
        ];

        return collect($parsed);
    }
}
