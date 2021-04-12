<?php

namespace App\Builders\PHP\Laravel\Parsers\Mutations\Modules;

use App\Models\Project;
use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\Framework\Database\Blueprint;
use App\Builders\Helpers\Mutations\DatabaseMutationHelpers;
use App\Builders\PHP\Laravel\Parsers\Contracts\MutationParser;
use App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations\OneToOneRelation;
use App\Builders\PHP\Laravel\Framework\App\Models\Eloquent\Relations\OneToManyRelation;

/**
 * Class DatabaseMutationsParser
 * @package App\Builders\PHP\Laravel\Parsers\Mutations\Modules
 */
class DatabaseMutationsParser implements MutationParser
{
    /**
     * @param array $mutations
     * @param string $projectId
     * @return Collection
     */
    public function parse(array $mutations, string $projectId): Collection
    {
        $project = Project::auth()->uuid($projectId)->first();

        $connection = MutationHelpers::first('^database\/type', $mutations) ?? 'mysql';

        $connection = strtolower($connection);

        $defaultDatabase = $project->name ?? 'forge';

        $defaultUsername = 'root';

        $configMutations = MutationHelpers::first("^database\/configurations\/$connection", $mutations) ?? [
                "url"       => null,
                "path"      => null,
                "host"      => "127.0.0.1",
                "port"      => 3306,
                "database"  => $defaultDatabase,
                "username"  => $defaultUsername,
                "password"  => null,
                "prefix"    => null,
                "engine"    => null,
                "charset"   => "utf8mb4",
                "collation" => "utf8mb4_unicode_ci",
            ];

        $defaultPort = DatabaseMutationHelpers::getDefaultDatabasePort($connection);

        $blueprints = collect(MutationHelpers::filter('/^database\/blueprints/', $mutations))
            ->map(static fn ($b) => $b['value'])
            // Attach model related mutations to blueprint.
            ->map(static function ($b) use ($mutations) {
                $blueprintId = $b['id'];

                $b['scopes'] = MutationHelpers::first("^eloquent\/scopes\/$blueprintId", $mutations) ?? [];
                $b['factory'] = MutationHelpers::first("^database\/factories\/$blueprintId", $mutations) ?? [];
                $b['config'] = MutationHelpers::first("^config\/database\/blueprints\/$blueprintId", $mutations) ?? [];

                $oneToOneRelations = collect(MutationHelpers::filter("^eloquent\/relations\/regular\/one-to-one\/$blueprintId", $mutations) ?? [])
                    ->map(static fn ($relation) => $relation['value']);

                $oneToManyRelations = collect(MutationHelpers::filter("^eloquent\/relations\/regular\/one-to-many\/$blueprintId", $mutations) ?? [])
                    ->map(static fn ($relation) => $relation['value']);

                $hasOneThroughRelations = collect(MutationHelpers::filter("^eloquent\/relations\/regular\/has-one-through\/$blueprintId", $mutations) ?? [])
                    ->map(static fn ($relation) => $relation['value']);

                $hasManyThroughRelations = collect(MutationHelpers::filter("^eloquent\/relations\/regular\/has-many-through\/$blueprintId", $mutations) ?? [])
                    ->map(static fn ($relation) => $relation['value']);

                $manyToManyRelations = collect(MutationHelpers::filter("^eloquent\/relations\/regular\/many-to-many\/$blueprintId", $mutations) ?? [])
                    ->map(static fn ($relation) => $relation['value']);

                if (!count($manyToManyRelations)) {
                    $manyToManyRelations = MutationHelpers::filter("^eloquent\/relations\/regular\/many-to-many\/.*?$blueprintId", $mutations);
                }

                $b['eloquent'] = [
                    'relations' => [
                        'one-to-one'       => $oneToOneRelations,
                        'one-to-many'      => $oneToManyRelations,
                        'has-one-through'  => $hasOneThroughRelations,
                        'has-many-through' => $hasManyThroughRelations,
                        'many-to-many'     => $manyToManyRelations,
                    ]
                ];

                return $b;
            })
            ->map(static fn ($b) => new Blueprint($b))
            ->filter(static fn (Blueprint $b) => $b->isValid())
            ->flatten();

        // Table relations
        $relations = collect(MutationHelpers::filter('^database\/relations', $mutations) ?? [])
            ->map(static fn ($relation) => $relation['value']);

        // Eloquent Relations
        $allEloquentRelations = collect(MutationHelpers::filter("^eloquent\/relations\/regular", $mutations))
            ->map(static fn ($r) => $r['value']);

        $oneToOneRelations = collect(MutationHelpers::filter("^eloquent\/relations\/regular\/one-to-one\/", $mutations))
            ->map(static fn ($r) => $r['value'])
            ->map(static fn ($r) => new OneToOneRelation($r))
            ->filter(static fn (OneToOneRelation $r) => $r->isValid());

        $oneToManyRelations = collect(MutationHelpers::filter("^eloquent\/relations\/regular\/one-to-many\/", $mutations))
            ->map(static fn ($r) => $r['value'])
            ->map(static fn ($r) => new OneToManyRelation($r))
            ->filter(static fn (OneToManyRelation $r) => $r->isValid());

        return collect(
            [
                'connection'     => $connection,
                'url'            => $configMutations['url'] ?? null,
                'path'           => $configMutations['path'] ?? null,
                'host'           => $configMutations['host'] ?? '127.0.0.1',
                'port'           => $configMutations['port'] ?? $defaultPort,
                'database'       => $configMutations['database'] ?? ($connection === 'sqlite' ? 'database.sqlite' : $defaultDatabase),
                'username'       => $configMutations['username'] ?? $defaultUsername,
                'password'       => $configMutations['password'] ?? null,
                'strict'         => $configMutations['strict'] ?? false,
                'prefix'         => $configMutations['prefix'] ?? null,
                'prefixIndexes'  => $configMutations['prefixIndexes'] ?? true,
                'fkConstraints'  => $configMutations['fkConstraints'] ?? true,
                'engine'         => $configMutations['engine'] ?? null,
                'charset'        => $configMutations['charset'] ?? ($connection === 'mysql' ? 'utf8mb4' : 'utf8'),
                'collation'      => $configMutations['collation'] ?? 'utf8mb4_unicode_ci',
                'schema'         => $configMutations['schema'] ?? 'public',
                'sslMode'        => $configMutations['sslMode'] ?? 'prefer',
                // MongoDB
                'dbAuthDatabase' => $configMutations['dbAuthDatabase'] ?? 'admin',
                'replicaSet'     => ($configMutations['multipleHosts'] ?? false) === true ? $configMutations['replicaSet'] : null,
                'multipleHosts'  => $configMutations['multipleHosts'] ?? false,
                'hosts'          => $configMutations['hosts'] ?? [],
                // Tables
                'blueprints'     => $blueprints,
                // Relations
                'relations'      => $relations,
                'eloquent'       => [
                    'relations' => [
                        'all'         => $allEloquentRelations->toArray(),
                        'one-to-one'  => $oneToOneRelations->toArray(),
                        'one-to-many' => $oneToManyRelations->toArray(),
                    ]
                ]
            ]
        );
    }
}
