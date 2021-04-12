<?php

namespace App\Builders\PHP\Laravel\Parsers\Mutations\Modules;

use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\Parsers\Contracts\MutationParser;

class AuthorizationMutationsParser implements MutationParser
{
    /**
     * @param array $mutations
     * @param string $projectId
     * @return Collection
     */
    public function parse(array $mutations, string $projectId): Collection
    {
        $roles = MutationHelpers::first('^authorization\/roles', $mutations) ?? [];
        $permissions = MutationHelpers::first('^authorization\/permissions', $mutations) ?? [];

        return collect(['roles' => $roles, 'permissions' => $permissions]);
    }
}
