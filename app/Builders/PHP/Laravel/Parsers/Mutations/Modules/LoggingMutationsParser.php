<?php

namespace App\Builders\PHP\Laravel\Parsers\Mutations\Modules;

use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\Parsers\Contracts\MutationParser;

/**
 * Class LoggingMutationsParser
 * @package App\Builders\PHP\Laravel\Parsers\Mutations\Modules
 */
class LoggingMutationsParser implements MutationParser
{
    /**
     * @param array $mutations
     * @param string $projectId
     * @return Collection
     */
    public function parse(array $mutations, string $projectId): Collection
    {
        $defaultLoggingChannel = MutationHelpers::first('logging\/channels\/default', $mutations) ?? 'stack';

        $defaultChannelPath = 'storage/logs/laravel.log';

        $singleChannelPath = MutationHelpers::first("logging\/channels\/single\/path", $mutations) ?? $defaultChannelPath;

        $dailyChannelPath = MutationHelpers::first("logging\/channels\/daily\/path", $mutations) ?? $defaultChannelPath;

        $slackConfig = MutationHelpers::first('^logging\/channels\/slack', $mutations) ?? [
                'webhookURL' => '',
                'username'   => 'Laravel Log',
                'emoji'      => ':boom:',
                'level'      => 'critical',
            ];

        $installTelescope = MutationHelpers::first('logging\/telescope\/enabled', $mutations) ?? false;

        return collect([
            'channel'  => $defaultLoggingChannel,
            'channels' => [
                'single' => [
                    'path' => $singleChannelPath,
                ],
                'daily'  => [
                    'path' => $dailyChannelPath,
                ],
                'slack'  => $slackConfig,
            ],
            'packages' => [
                'installTelescope' => $installTelescope,
            ]
        ]);
    }
}
