<?php

namespace App\Builders\PHP\Laravel\Parsers\Mutations\Modules;

use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\Parsers\Contracts\MutationParser;

/**
 * Class QueuesMutationsParser
 * @package App\Builders\PHP\Laravel\Parsers\Mutations\Modules
 */
class QueuesMutationsParser implements MutationParser
{
    /**
     * @param array $mutations
     * @param string $projectId
     * @return Collection
     */
    public function parse(array $mutations, string $projectId): Collection
    {
        $installHorizon = MutationHelpers::first('^queues\/horizon\/enabled', $mutations) ?? false;

        $jobs = MutationHelpers::first('^queues\/jobs$', $mutations) ?? [];

        $parsedJobs = [];

        foreach ($jobs as $job) {
            $jobId = $job['id'] ?? null;
            $jobName = $job['name'] ?? null;

            if (!$jobId || !$jobName) {
                continue;
            }

            $job = MutationHelpers::first("^queues\/jobs\/$jobId", $mutations)
                ??
                [
                    'name'                   => $jobName,
                    'unique'                 => false,
                    'uniqueUntilProcessing'  => false,
                    'overrideUniqueDuration' => false,
                    'uniqueFor'              => 120,
                    'uniqueVia'              => null,
                    'middleware'             => null,
                    'typeHint'               => [
                    ],
                    'noRelation'             => false,
                    'createUniqueViaMethod'  => false,
                ];

            $parsedJobs[] = $job;
        }

        $supervisorConfig = MutationHelpers::first('^queues\/supervisor$', $mutations)
            ??
            [
                'artisanPath' => '/var/www/html/app/artisan',
                'logFilePath' => '/var/www/html/app/storage/logs/horizon.log',
                'username'    => 'forge',
                'copyConfig'  => false,
            ];

        $parsed = [
            'jobs'                 => $parsedJobs,
            'copySupervisorConfig' => $supervisorConfig['copyConfig'],
            'supervisorConfig'     => $supervisorConfig,
            'packages'             => [
                'installHorizon' => $installHorizon,
            ]
        ];

        return collect($parsed);
    }
}
