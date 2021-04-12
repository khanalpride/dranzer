<?php

namespace App\Builders\PHP\Laravel\Parsers\Mutations\Modules;

use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\Parsers\Contracts\MutationParser;

/**
 * Class DeploymentMutationsParser
 * @package App\Builders\PHP\Laravel\Parsers\Mutations\Modules
 */
class DeploymentMutationsParser implements MutationParser
{
    /**
     * @param array $mutations
     * @param string $projectId
     * @return Collection
     */
    public function parse(array $mutations, string $projectId): Collection
    {
        $nginxConfig = MutationHelpers::first('^deployment\/nginx', $mutations)
            ?? [
                'listeningPort' => 80,
                'root'          => '/var/www/html/app/public',
                'serverNames'   => ['name' => 'example.com'],
                'phpFPMVersion' => 'php8.0-fpm',
                'copyConfig'    => false,
                'maxBodySize'   => 128,
            ];

        $parsed = [
            'copyConfig'  => $nginxConfig['copyConfig'],
            'nginxConfig' => $nginxConfig,
        ];

        return collect($parsed);
    }
}
