<?php

namespace App\Builders\PHP\Laravel\Parsers\Mutations\Modules;

use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\Parsers\Contracts\MutationParser;

/**
 * Class ExceptionHandlerMutationsParser
 * @package App\Builders\PHP\Laravel\Parsers\Mutations\Modules
 */
class ExceptionHandlerMutationsParser implements MutationParser
{
    /**
     * @param array $mutations
     * @param string $projectId
     * @return Collection
     */
    public function parse(array $mutations, string $projectId): Collection
    {
        $sentryOptions = MutationHelpers::first('^exceptions\/options\/sentry', $mutations) ?? [
                'enabled'         => false,
                'sentryDSN'       => null,
                'attachUserId'    => true,
                'attachUserEmail' => true,
            ];

        $doNotReportOptions = MutationHelpers::first('^exceptions\/options\/do-not', $mutations) ?? [
                'authenticationException' => false,
                'authorizationException'  => false,
                'httpException'           => false,
                'modelNotFoundException'  => false,
                'validationException'     => false,
            ];

        $errorPagesConfig = MutationHelpers::first('^exceptions\/pages', $mutations) ?? ['basic'    => false,
                                                                                         'messages' => []
            ];

        $basic = $errorPagesConfig['basic'];

        $messages = collect($errorPagesConfig['messages'])->filter(fn ($p) => $p['override'])->toArray();

        return collect([
            'sentry'      => $sentryOptions,
            'doNotReport' => $doNotReportOptions,
            'messages'    => $messages,
            'basic'       => $basic,
        ]);
    }
}
