<?php

namespace App\Builders\PHP\Laravel\Parsers\Mutations;

use App\Models\Project;
use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\Parsers\Mutations\Modules\UIMutationsParser;
use App\Builders\PHP\Laravel\Parsers\Mutations\Modules\APIMutationsParser;
use App\Builders\PHP\Laravel\Parsers\Mutations\Modules\MailMutationsParser;
use App\Builders\PHP\Laravel\Parsers\Mutations\Modules\AssetsMutationsParser;
use App\Builders\PHP\Laravel\Parsers\Mutations\Modules\LinterMutationsParser;
use App\Builders\PHP\Laravel\Parsers\Mutations\Modules\LayoutMutationsParser;
use App\Builders\PHP\Laravel\Parsers\Mutations\Modules\QueuesMutationsParser;
use App\Builders\PHP\Laravel\Parsers\Mutations\Modules\LoggingMutationsParser;
use App\Builders\PHP\Laravel\Parsers\Mutations\Modules\RequestMutationsParser;
use App\Builders\PHP\Laravel\Parsers\Mutations\Modules\DatabaseMutationsParser;
use App\Builders\PHP\Laravel\Parsers\Mutations\Modules\FrontendMutationsParser;
use App\Builders\PHP\Laravel\Parsers\Mutations\Modules\SchedulerMutationsParser;
use App\Builders\PHP\Laravel\Parsers\Mutations\Modules\ControllerMutationsParser;
use App\Builders\PHP\Laravel\Parsers\Mutations\Modules\DeploymentMutationsParser;
use App\Builders\PHP\Laravel\Parsers\Mutations\Modules\MiddlewareMutationsParser;
use App\Builders\PHP\Laravel\Parsers\Mutations\Modules\NotificationMutationsParser;
use App\Builders\PHP\Laravel\Parsers\Mutations\Modules\AuthorizationMutationsParser;
use App\Builders\PHP\Laravel\Parsers\Mutations\Modules\AuthenticationMutationsParser;
use App\Builders\PHP\Laravel\Parsers\Mutations\Modules\FormValidationMutationsParser;
use App\Builders\PHP\Laravel\Parsers\Mutations\Modules\ExceptionHandlerMutationsParser;

/**
 * Class MutationsParser
 * @package App\Builders\PHP\Laravel\Parsers\Mutations
 */
class MutationsParser
{
    /**
     * @var array
     */
    private array $parsed = [];
    /**
     * @var array
     */
    private array $mutations;
    /**
     * @var string
     */
    private string $projectId;

    /**
     * @param $mutations
     * @param $projectId
     * @return MutationsParser
     */
    public function parse($mutations, $projectId): MutationsParser
    {
        $this->mutations = $mutations;
        $this->projectId = $projectId;

        $this
            ->parseDatabaseMutations()
            ->parseBrandingMutations()
            ->parseAPIMutations()
            ->parseDevToolsMutations()
            ->parseComplianceMutations()
            ->parseAssetMutations()
            ->parseAuthenticationMutations()
            ->parseAuthorizationMutations()
            ->parseControllerMutations()
            ->parseExceptionHandlerMutations()
            ->parseLoggingMutations()
            ->parseMailableMutations()
            ->parseMiddlewareMutations()
            ->parseNotificationMutations()
            ->parseQueueMutations()
            ->parseRequestMutations()
            ->parseSchedulingMutations()
            ->parseUserInterfaceMutations()
            ->parseValidationMutations()
            ->parseDeploymentMutations()
            ->parseFrontendMutations()
            ->parseLayoutMutations()
            ->parseLinterMutations();

        return $this;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->parsed;
    }

    /**
     * @param $moduleKey
     * @param bool $asArray
     * @return array|Collection|null
     */
    public function for($moduleKey, $asArray = true)
    {
        $parsed = $this->parsed[$moduleKey] ?? null;

        if (!$parsed) {
            return $asArray ? [] : collect();
        }

        return $asArray ? $parsed->toArray() : $parsed;
    }

    /**
     *
     */
    private function parseAPIMutations(): MutationsParser
    {
        $this->parsed['api'] = (new APIMutationsParser)->parse($this->mutations, $this->projectId);
        return $this;
    }

    /**
     *
     */
    private function parseBrandingMutations(): MutationsParser
    {
        $project = Project::auth()->uuid($this->projectId)->first();
        $branding = MutationHelpers::first('^config\/app\/branding', $this->mutations) ?? [];
        $branding['name'] = $branding['name'] ?? $project->name ?? 'Laravel';
        $branding['desc'] = $branding['desc'] ?? 'Built with Dranzer';
        $this->parsed['branding'] = collect($branding);
        return $this;
    }

    /**
     *
     */
    private function parseAssetMutations(): MutationsParser
    {
        $this->parsed['assets'] = (new AssetsMutationsParser)->parse($this->mutations, $this->projectId);
        return $this;
    }

    /**
     *
     */
    private function parseDevToolsMutations(): MutationsParser
    {
        $installDecomposer = MutationHelpers::first('^devTools\/decomposer\/install', $this->mutations) ?? [];
        $installDebugBar = MutationHelpers::first('^devTools\/debugBar\/install', $this->mutations) ?? [];
        $installIdeHelper = MutationHelpers::first('^devTools\/ideHelper\/install', $this->mutations) ?? [];

        $this->parsed['devTools'] = collect([
            'installDecomposer' => $installDecomposer['install'] ?? false,
            'installDebugBar'   => $installDebugBar['install'] ?? false,
            'installIdeHelper'  => $installIdeHelper['install'] ?? false,
        ]);
        return $this;
    }

    /**
     *
     */
    private function parseComplianceMutations(): MutationsParser
    {
        $cookieConsent = MutationHelpers::first('^compliance\/cc\/install', $this->mutations) ?? ['install' => false];

        $this->parsed['compliance'] = collect([
            'installCookieConsent' => $cookieConsent['install'],
            'cookieConsent'        => $cookieConsent,
        ]);
        return $this;
    }

    /**
     *
     */
    private function parseAuthorizationMutations(): MutationsParser
    {
        $this->parsed['authorization'] = (new AuthorizationMutationsParser)->parse($this->mutations, $this->projectId);
        return $this;
    }

    /**
     *
     */
    private function parseControllerMutations(): MutationsParser
    {
        $this->parsed['controllers'] = (new ControllerMutationsParser)->parse($this->mutations, $this->projectId);
        return $this;
    }

    /**
     *
     */
    private function parseDatabaseMutations(): MutationsParser
    {
        $this->parsed['database'] = (new DatabaseMutationsParser)->parse($this->mutations, $this->projectId);
        return $this;
    }

    /**
     *
     */
    private function parseExceptionHandlerMutations(): MutationsParser
    {
        $this->parsed['exceptions'] = (new ExceptionHandlerMutationsParser)->parse($this->mutations, $this->projectId);
        return $this;
    }

    /**
     *
     */
    private function parseLoggingMutations(): MutationsParser
    {
        $this->parsed['logging'] = (new LoggingMutationsParser)->parse($this->mutations, $this->projectId);
        return $this;
    }

    /**
     *
     */
    private function parseMailableMutations(): MutationsParser
    {
        $this->parsed['mail'] = (new MailMutationsParser)->parse($this->mutations, $this->projectId);
        return $this;
    }

    /**
     *
     */
    private function parseMiddlewareMutations(): MutationsParser
    {
        $this->parsed['middlewares'] = (new MiddlewareMutationsParser)->parse($this->mutations, $this->projectId);
        return $this;
    }

    /**
     *
     */
    private function parseNotificationMutations(): MutationsParser
    {
        $this->parsed['notifications'] = (new NotificationMutationsParser)->parse($this->mutations, $this->projectId);
        return $this;
    }

    /**
     *
     */
    private function parseQueueMutations(): MutationsParser
    {
        $this->parsed['queues'] = (new QueuesMutationsParser)->parse($this->mutations, $this->projectId);
        return $this;
    }

    /**
     *
     */
    private function parseRequestMutations(): MutationsParser
    {
        $this->parsed['request'] = (new RequestMutationsParser)->parse($this->mutations, $this->projectId);
        return $this;
    }

    /**
     *
     */
    private function parseSchedulingMutations(): MutationsParser
    {
        $this->parsed['scheduler'] = (new SchedulerMutationsParser)->parse($this->mutations, $this->projectId);
        return $this;
    }

    /**
     *
     */
    private function parseAuthenticationMutations(): MutationsParser
    {
        $this->parsed['auth'] = (new AuthenticationMutationsParser)->parse($this->mutations, $this->projectId);
        return $this;
    }

    /**
     *
     */
    private function parseUserInterfaceMutations(): MutationsParser
    {
        $this->parsed['ui'] = (new UIMutationsParser)->parse($this->mutations, $this->projectId);
        return $this;
    }

    /**
     *
     */
    private function parseValidationMutations(): MutationsParser
    {
        $this->parsed['validation'] = (new FormValidationMutationsParser)->parse($this->mutations, $this->projectId);
        return $this;
    }

    /**
     *
     */
    private function parseDeploymentMutations(): MutationsParser
    {
        $this->parsed['deployment'] = (new DeploymentMutationsParser)->parse($this->mutations, $this->projectId);
        return $this;
    }

    /**
     *
     */
    private function parseFrontendMutations(): MutationsParser
    {
        $this->parsed['frontend'] = (new FrontendMutationsParser)->parse($this->mutations, $this->projectId);
        return $this;
    }

    /**
     * @return MutationsParser
     */
    private function parseLayoutMutations(): MutationsParser
    {
        $this->parsed['layout'] = (new LayoutMutationsParser)->parse($this->mutations, $this->projectId);
        return $this;
    }

    /**
     *
     */
    private function parseLinterMutations(): void
    {
        $this->parsed['linters'] = (new LinterMutationsParser)->parse($this->mutations, $this->projectId);
    }
}
