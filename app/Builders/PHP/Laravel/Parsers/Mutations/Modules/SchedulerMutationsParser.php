<?php

namespace App\Builders\PHP\Laravel\Parsers\Mutations\Modules;

use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\Parsers\Contracts\MutationParser;

/**
 * Class SchedulerMutationsParser
 * @package App\Builders\PHP\Laravel\Parsers\Mutations\Modules
 */
class SchedulerMutationsParser implements MutationParser
{
    /**
     * @param array $mutations
     * @param string $projectId
     * @return Collection
     */
    public function parse(array $mutations, string $projectId): Collection
    {
        $tasks = collect(MutationHelpers::filter('^tasks\/', $mutations) ?? [])->map(fn ($task) => $task['value']);

        $parsed = [
            'tasks' => $tasks,
        ];

        return collect($parsed);
    }
}
