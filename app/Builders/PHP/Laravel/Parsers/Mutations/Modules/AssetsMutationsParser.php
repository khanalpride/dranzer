<?php

namespace App\Builders\PHP\Laravel\Parsers\Mutations\Modules;

use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\Parsers\Contracts\MutationParser;

/**
 * Class AssetsMutationsParser
 * @package App\Builders\PHP\Laravel\Parsers\Mutations\Modules
 */
class AssetsMutationsParser implements MutationParser
{
    /**
     * @param array $mutations
     * @param string $projectId
     * @return Collection
     */
    public function parse(array $mutations, string $projectId): Collection
    {
        $hmrConfig = MutationHelpers::first('^assets\/mix\/hmr$', $mutations)
            ??
            [
                'enabled' => false,
                'https'   => true,
                'host'    => null,
                'port'    => '8080',
            ];

        $mixConfig = MutationHelpers::first('^assets\/mix\/config$', $mutations)
            ??
            [
                'version'                     => true,
                'disableSuccessNotifications' => true,
            ];

        return collect(
            [
                'enableHmr' => $hmrConfig['enabled'],
                'hmr'       => $hmrConfig,
                'misc'      => $mixConfig,
            ]
        );
    }
}
