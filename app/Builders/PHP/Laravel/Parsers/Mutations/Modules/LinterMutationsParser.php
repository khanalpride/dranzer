<?php

namespace App\Builders\PHP\Laravel\Parsers\Mutations\Modules;

use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\Parsers\Contracts\MutationParser;

/**
 * Class LinterMutationsParser
 * @package App\Builders\PHP\Laravel\Parsers\Mutations\Modules
 */
class LinterMutationsParser implements MutationParser
{
    /**
     * @param array $mutations
     * @param string $projectId
     * @return Collection
     */
    public function parse(array $mutations, string $projectId): Collection
    {
        $esLintConfig = MutationHelpers::first('^linters\/eslint', $mutations)
            ?? [
                'create'     => false,
                'env'        => [
                    'browser' => true,
                    'es6'     => true,
                    'node'    => false,
                ],
                'parser'     => [
                    'babelEsLint' => true,
                ],
                'extends'    => [
                    'airbnbBase'             => true,
                    'vueEssential'           => false,
                    'vueRecommended'         => true,
                    'vueStronglyRecommended' => false,
                ],
                'resolution' => [
                    'mapJsDir' => true,
                ],
                'overrides'  => [
                    'maxLineLength'        => 135,
                    'noReturnAssignment'   => true,
                    'noParamReassignments' => false,
                ],
            ];

        $parsed = [
            'eslint' => [
                'create' => $esLintConfig['create'],
                'config' => $esLintConfig
            ]
        ];

        return collect($parsed);
    }
}
