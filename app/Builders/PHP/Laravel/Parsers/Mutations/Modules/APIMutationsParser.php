<?php

namespace App\Builders\PHP\Laravel\Parsers\Mutations\Modules;

use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\Parsers\Contracts\MutationParser;

/**
 * Class APIMutationsParser
 * @package App\Builders\PHP\Laravel\Parsers\Mutations\Modules
 */
class APIMutationsParser implements MutationParser
{
    /**
     * @param array $mutations
     * @param string $projectId
     * @return Collection
     */
    public function parse(array $mutations, string $projectId): Collection
    {
        $apiMutations = MutationHelpers::first('^api$', $mutations)
            ?? [
                'generate'                => false,
                'jwtAuth'                 => false,
                'sanctumAuth'             => true,
                'blueprints'              => [],
                'configCollectionDisplay' => false,
                'configCollectionColumns' => false,
                'configResourceColumns'   => false,
            ];

        $generate = $apiMutations['generate'];

        // Database mutations are already parsed so we can use parsed blueprints.
        $blueprints = app('mutations')->for('database')['blueprints'];

        $modules = [];

        foreach ($blueprints as $blueprint) {
            $blueprintId = $blueprint->getId();

            $moduleConfig = MutationHelpers::first("^api\/resources\/$blueprintId", $mutations);

            if (!$moduleConfig) {
                continue;
            }

            $modules[] = [
                'blueprint' => $blueprint,
                'config'    => $moduleConfig,
            ];
        }

        return collect(
            [
                'generate'    => $generate,
                'jwtAuth'     => $apiMutations['jwtAuth'],
                'sanctumAuth' => $apiMutations['sanctumAuth'],
                'modules'     => $modules,
            ]
        );
    }
}
