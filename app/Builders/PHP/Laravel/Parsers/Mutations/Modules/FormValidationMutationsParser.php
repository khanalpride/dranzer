<?php

namespace App\Builders\PHP\Laravel\Parsers\Mutations\Modules;

use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\Parsers\Contracts\MutationParser;

/**
 * Class FormValidationMutationsParser
 * @package App\Builders\PHP\Laravel\Parsers\Mutations\Modules
 */
class FormValidationMutationsParser implements MutationParser
{
    /**
     * @param array $mutations
     * @param string $projectId
     * @return Collection
     */
    public function parse(array $mutations, string $projectId): Collection
    {
        $blueprints = app('mutations')->for('database')['blueprints'];

        $mapped = [];

        foreach ($blueprints as $blueprint) {
            $schemaId = $blueprint->getId();
            $modelName = $blueprint->getName();

            $rules = collect(MutationHelpers::filter("^validation\/mapping\/$schemaId", $mutations))
                ->map(fn ($m) => $m['value'])->map(function ($m) {
                    return [
                        'name'     => $m['name'],
                        'compiled' => $m['compiled']
                    ];
                });

            $config = MutationHelpers::first("^validation\/config\/$schemaId", $mutations)
                ?? ['auth' => 'user'];

            $auth = $config['auth'] ?? 'user';

            $mapped[$modelName] = [
                'auth'  => $auth,
                'model' => $modelName,
                'rules' => $rules,
            ];
        }

        return collect([
            'rules' => $mapped
        ]);
    }
}
