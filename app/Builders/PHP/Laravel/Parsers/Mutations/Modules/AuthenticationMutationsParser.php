<?php

namespace App\Builders\PHP\Laravel\Parsers\Mutations\Modules;

use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\Parsers\Contracts\MutationParser;

/**
 * Class AuthenticationMutationsParser
 * @package App\Builders\PHP\Laravel\Parsers\Mutations\Modules
 */
class AuthenticationMutationsParser implements MutationParser
{
    /**
     * @param array $mutations
     * @param string $projectId
     * @return Collection
     */
    public function parse(array $mutations, string $projectId): Collection
    {
        $authConfig = MutationHelpers::first('^auth\/config', $mutations)
            ?? [
                'enabled' => false,
                'modules' => [
                    'breeze'  => false,
                    'fortify' => false,
                    'ui'      => false,
                ],
            ];

        $activeModule = $authConfig['modules']['breeze'] === true ? 'breeze' : null;
        $activeModule = $authConfig['modules']['fortify'] === true ? 'fortify' : $activeModule;
        $activeModule = $authConfig['modules']['ui'] === true ? 'ui' : $activeModule;

        $uiModuleConfig = MutationHelpers::first('^auth\/modules\/ui', $mutations)
            ?? [
                'registration' => true,
                'resets'       => true,
                'verify'       => true,
                'library'      => 'vue',
            ];

        $breezeModuleConfig = MutationHelpers::first('^auth\/modules\/breeze', $mutations)
            ?? [
                'registration' => true,
                'resets'       => true,
                'verify'       => true,
            ];

        $fortifyModuleConfig = MutationHelpers::first('^auth\/modules\/fortify', $mutations)
            ?? [
                'registration'      => true,
                'resets'            => true,
                'verify'            => true,
                'update'            => true,
                'twoFactor'         => false,
                'disableViewRoutes' => false,
            ];

        $parsed = [
            'install' => $authConfig['enabled'] ?? false,
            'config'  => $authConfig,
            'module'  => $activeModule,
            'ui'      => $uiModuleConfig,
            'breeze'  => $breezeModuleConfig,
            'fortify' => $fortifyModuleConfig,
        ];

        return collect($parsed);
    }
}
