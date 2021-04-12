<?php

namespace App\Builders\PHP\Laravel\Parsers\Mutations\Modules;

use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\Parsers\Contracts\MutationParser;

/**
 * Class NotificationMutationsParser
 * @package App\Builders\PHP\Laravel\Parsers\Mutations\Modules
 */
class NotificationMutationsParser implements MutationParser
{
    /**
     * @param array $mutations
     * @param string $projectId
     * @return Collection
     */
    public function parse(array $mutations, string $projectId): Collection
    {
        $notifications = collect(MutationHelpers::filter('^notifications\/', $mutations) ?? [])->map(
            fn ($n) => $n['value']
        );
        $slackWebhook = MutationHelpers::first('^config\/notifications\/slack\/webhook', $mutations);
        $nexmoRecipient = MutationHelpers::first('^config\/notifications\/nexmo\/recipient', $mutations);

        $parsed = [
            'slackWebhook'   => $slackWebhook,
            'nexmoRecipient' => $nexmoRecipient,
            'notifications'  => $notifications,
        ];

        return collect($parsed);
    }
}
