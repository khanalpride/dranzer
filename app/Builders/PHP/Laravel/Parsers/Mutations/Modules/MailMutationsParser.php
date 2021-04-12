<?php

namespace App\Builders\PHP\Laravel\Parsers\Mutations\Modules;

use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\Parsers\Contracts\MutationParser;

/**
 * Class MailMutationsParser
 * @package App\Builders\PHP\Laravel\Parsers\Mutations\Modules
 */
class MailMutationsParser implements MutationParser
{
    /**
     * @param array $mutations
     * @param string $projectId
     * @return Collection
     */
    public function parse(array $mutations, string $projectId): Collection
    {
        $mailables = collect(MutationHelpers::filter('^mail\/mailables', $mutations) ?? [])->map(fn ($m) => $m['value']);

        $driver = MutationHelpers::first('^mail\/driver', $mutations) ?? 'smtp';

        $senderConfig = MutationHelpers::first('^mail\/sender\/config', $mutations);

        $smtpConfig = MutationHelpers::first('^mail\/mailers\/smtp', $mutations);

        return collect([
            'mailables' => $mailables,
            'driver'    => $driver,
            'config'    => [
                'sender' => $senderConfig,
                'smtp'   => $smtpConfig,
            ]
        ]);
    }
}
