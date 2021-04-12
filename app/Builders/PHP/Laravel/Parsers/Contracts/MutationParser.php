<?php

namespace App\Builders\PHP\Laravel\Parsers\Contracts;

use Illuminate\Support\Collection;

/**
 * Interface MutationParser
 * @package App\Builders\PHP\Laravel\Parsers\Contracts
 */
interface MutationParser
{
    /**
     * @param array $mutations
     * @param string $projectId
     * @return Collection
     */
    public function parse(array $mutations, string $projectId): Collection;
}
