<?php

/**
 * @noinspection PhpUnusedPrivateMethodInspection
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Mutation\MutationService;
use App\Builders\PHP\Laravel\BindingsManager;
use App\Builders\PHP\Laravel\Framework\Database\Blueprint;

/**
 * Class SummaryController
 * @package App\Http\Controllers
 */
class SummaryController extends Controller
{
    /**
     * @param Request $request
     * @return array|JsonResponse
     */
    public function getSummary(Request $request)
    {
        $projectId = $request->input('projectId');

        $mutations = MutationService::getMutations($projectId);

        BindingsManager::registerMutationBindings($mutations, $projectId);

        $parsed = app('mutations')->all();

        $summary = collect();

        foreach ($parsed as $module => $value) {
            $methodName = "get" . Str::studly($module) . "Summary";
            if (method_exists($this, $methodName)) {
                $summary = $summary->merge($this->{$methodName}($value->toArray()));
            }
        }

        $summary = ['summary' => $summary->sortKeys()];

        return $request->wantsJson()
            ? response()->json($summary)
            : $summary;
    }

    /**
     * @param $mutations
     * @return array
     */
    private function getApiSummary($mutations): array
    {
        $summary = [];

        if (!$mutations['generate']) {
            return $summary;
        }

        $modules = collect($mutations['modules'])->map(
            fn ($m) => [
                'title' => $m['blueprint']->getName(),
                'desc'  => 'Controller, Routes and Policy',
            ]
        );

        $generated = [];

        if (count($modules)) {
            $moduleCount = count($modules);
            $generated[] = $this->countStatement($moduleCount, 'Controller');
            $generated[] = 'Routes';
        }

        if ($mutations['jwtAuth']) {
            $generated[] = 'JWT Setup';
        }

        $description = collect($generated)->join(', ', ' and ');

        $summary['API'] = [
            'id'          => Str::random(),
            'mutations'   => $modules,
            'description' => $description,
        ];

        return $summary;
    }

    /**
     * @param $mutations
     * @return array
     */
    private function getDatabaseSummary($mutations): array
    {
        $summary = [];

        $blueprints = $mutations['blueprints'];

        if (!count($blueprints)) {
            return $summary;
        }

        $blueprints = collect($blueprints)->values()->map(
            fn (Blueprint $blueprint) => [
                'title' => $blueprint->getName(),
                'desc'  => 'Model, Migration, Seeder, Factory and Relations',
            ]
        );

        $generated = [];

        if (count($blueprints)) {
            $modelCount = count($blueprints);
            $generated[] = $this->countStatement($modelCount, 'Model');
        }

        $description = collect($generated)->join(', ', ' and ');

        $summary['Database'] = [
            'id'          => Str::random(),
            'mutations'   => $blueprints,
            'description' => $description,
        ];

        return $summary;
    }

    /**
     * @param $mutations
     * @return array
     */
    private function getDevToolsSummary($mutations): array
    {
        $summary = [];

        $tools = [
            'installDecomposer',
            'installDebugBar',
            'installIdeHelper'
        ];

        $installs = [];

        foreach ($tools as $tool) {
            $install = $mutations[$tool] === true;

            if ($install) {
                $installs[] = [
                    'title' => ucwords(str_ireplace('_', ' ', Str::snake($tool))),
                    'desc'  => 'Install and Configure',
                ];
            }
        }

        $installsCount = count($installs);

        $description = $this->countStatement($installsCount, 'Package');

        $summary['Developer Tools'] = [
            'id'          => Str::random(),
            'mutations'   => $installs,
            'description' => $description,
        ];

        return $summary;
    }

    /**
     * @param $mutations
     * @return array
     */
    private function getComplianceSummary($mutations): array
    {
        $summary = [];

        $installCookieConsent = $mutations['installCookieConsent'];

        if (!$installCookieConsent) {
            return $summary;
        }

        $installs = [
            'title' => 'Cookie Consent',
            'desc'  => 'Middleware, Language Files and Views',
        ];

        $summary['Compliance'] = [
            'id'          => Str::random(),
            'mutations'   => [$installs],
            'description' => 'Install Cookie Consent Package',
        ];

        return $summary;
    }

    /**
     * @param $mutations
     * @return array
     */
    private function getAssetsSummary($mutations): array
    {
        $summary = [];

        $items = [];

        $hmr = $mutations['enableHmr'];

        if ($hmr) {
            $items[] = [
                'title' => 'HMR',
                'desc'  => 'Host and Port',
            ];
        } else {
            return [];
        }

        $summary['Laravel Mix'] = [
            'id'          => Str::random(),
            'mutations'   => $items,
            'description' => 'Configure',
        ];

        return $summary;
    }

    /**
     * @param $mutations
     * @return array
     */
    private function getAuthSummary($mutations): array
    {
        $summary = [];

        if (!$mutations['install']) {
            return $summary;
        }

        $types = [];

        if ($mutations['module']) {
            $types[] = [
                'title' => $mutations['module'] === 'ui' ? 'UI' : Str::studly($mutations['module']),
                'desc'  => 'Routes, Views, Controllers and Mix Configuration',
            ];
        }

        $summary['Authentication'] = [
            'id'          => Str::random(),
            'mutations'   => $types,
            'description' => 'Use ' . $types[0]['title'] . ' Auth Module',
        ];

        return $summary;
    }

    /**
     * @param $mutations
     * @return array
     */
    private function getAuthorizationSummary($mutations): array
    {
        $summary = [];

        $roles = $mutations['roles'];

        $permissions = $mutations['permissions'];

        if (!count($roles) && !count($permissions)) {
            return $summary;
        }

        $generated = [];

        $types = [];

        if (count($roles)) {
            $generated[] = 'Roles';

            $types[] = [
                'title' => $this->countStatement(count($roles), 'Role'),
                'desc'  => 'Service Provider Entry',
            ];
        }

        if (count($permissions)) {
            $generated[] = 'Permissions';

            $types[] = [
                'title' => $this->countStatement(count($permissions), 'Permission'),
                'desc'  => 'Service Provider Entry',
            ];
        }

        $description = collect($generated)->join(', ', ' and ');

        $summary['Authorization'] = [
            'id'          => Str::random(),
            'mutations'   => $types,
            'description' => $description,
        ];

        return $summary;
    }

    /**
     * @param $mutations
     * @return array
     */
    private function getControllersSummary($mutations): array
    {
        $summary = [];

        $controllersCount = count($mutations);

        if (!$controllersCount) {
            return $summary;
        }

        $items = [];

        foreach ($mutations['controllers'] as $controller) {
            $items[] = [
                'title' => $controller['name'],
                'desc'  => 'Routes and Controller Class',
            ];
        }

        $summary['Controllers'] = [
            'id'          => Str::random(),
            'mutations'   => $items,
            'description' => $this->countStatement($controllersCount, 'Controller'),
        ];

        return $summary;
    }

    /**
     * @param $mutations
     * @return array
     */
    private function getExceptionsSummary($mutations): array
    {
        $summary = [];

        $installSentry = $mutations['sentry']['enabled'];
        $messagesCount = count($mutations['messages']);
        $minimalMessageTemplate = $mutations['basic'];

        if (!$installSentry && !$messagesCount && !$minimalMessageTemplate) {
            return $summary;
        }

        $items = [];

        if ($installSentry) {
            $items[] = [
                'title' => 'Sentry',
                'desc'  => 'Install and Add Context Middleware',
            ];
        }

        if ($messagesCount) {
            $items[] = [
                'title' => $this->countStatement($messagesCount, 'Error Page'),
                'desc'  => 'Override and Publish',
            ];
        }

        $summary['Exception Handler'] = [
            'id'          => Str::random(),
            'mutations'   => $items,
            'description' => 'Configure',
        ];

        return $summary;
    }

    /**
     * @param $mutations
     * @return array
     */
    private function getMailSummary($mutations): array
    {
        $summary = [];

        if (!count($mutations)) {
            return $summary;
        }

        $mailables = $mutations['mailables'];

        if (!count($mailables)) {
            return $summary;
        }

        $items = [];

        foreach ($mailables as $mailable) {
            $items[] = [
                'title' => $mailable['name'],
                'desc'  => 'Mailable Class and View',
            ];
        }

        $summary['Mailables'] = [
            'id'          => Str::random(),
            'mutations'   => $items,
            'description' => $this->countStatement(count($mailables), 'Mailable'),
        ];

        return $summary;
    }

    /**
     * @param $mutations
     * @return array
     */
    private function getMiddlewaresSummary($mutations): array
    {
        $summary = [];

        $minifyHtml = $mutations['minifyHtml'] ?? true;
        $validatePostSize = $mutations['validatePostSize'] ?? true;
        $trimStrings = $mutations['trimStrings'] ?? true;
        $convertEmptyStringsToNull = $mutations['convertEmptyStringsToNull'] ?? true;

        $items = [];

        if ($minifyHtml) {
            $items[] = [
                'title' => 'HTMLMin Middleware',
                'desc'  => 'Install the HTMLMin/Laravel-HTMLMin package',
            ];
        }

        if (!$validatePostSize) {
            $items[] = [
                'title' => 'ValidatePostSize Middleware',
                'desc'  => 'Disable the ValidatePostSize middleware',
            ];
        }

        if (!$trimStrings) {
            $items[] = [
                'title' => 'TrimStrings Middleware',
                'desc'  => 'Disable the TrimStrings middleware',
            ];
        }

        if (!$convertEmptyStringsToNull) {
            $items[] = [
                'title' => 'ConvertEmptyStringsToNull Middleware',
                'desc'  => 'Disable the ConvertEmptyStringsToNull middleware',
            ];
        }

        if (!count($items)) {
            return [];
        }

        $summary['Middlewares'] = [
            'id'          => Str::random(),
            'mutations'   => $items,
            'description' => 'Configure',
        ];

        return $summary;
    }

    /**
     * @param $mutations
     * @return array
     */
    private function getNotificationsSummary($mutations): array
    {
        $summary = [];

        if (!count($mutations)) {
            return $summary;
        }

        $notifications = $mutations['notifications'];

        if (!count($notifications)) {
            return $summary;
        }

        $items = [];

        foreach ($notifications as $notification) {

            $items[] = [
                'title' => $notification['name'],
                'desc'  => 'Notification Class',
            ];
        }

        $summary['Notifications'] = [
            'id'          => Str::random(),
            'mutations'   => $items,
            'description' => $this->countStatement(count($notifications), 'Notification'),
        ];

        return $summary;
    }

    /**
     * @param $mutations
     * @return array
     */
    private function getQueuesSummary($mutations): array
    {
        $summary = [];

        $installHorizon = $mutations['packages']['installHorizon'];

        $copySupervisorConfig = $mutations['copySupervisorConfig'];

        $jobs = $mutations['jobs'];

        if (!$copySupervisorConfig && !$installHorizon && !count($jobs)) {
            return [];
        }

        $items = [];

        if ($installHorizon) {
            $items[] = [
                'title' => 'Horizon',
                'desc'  => 'Install and Configure Horizon',
            ];
        }

        if (count($jobs)) {
            $items[] = [
                'title' => 'Jobs',
                'desc'  => 'Create ' . $this->countStatement(count($jobs), 'Job', 'Jobs'),
            ];
        }

        if ($copySupervisorConfig) {
            $items[] = [
                'title' => 'Supervisor',
                'desc'  => 'Copy Supervisor Config',
            ];
        }

        $summary['Queues'] = [
            'id'          => Str::random(),
            'mutations'   => $items,
            'description' => 'Configure',
        ];

        return $summary;
    }

    /**
     * @param $mutations
     * @return array
     */
    private function getRequestSummary($mutations): array
    {
        $summary = [];

        $headers = $mutations['headers'];
        $proxies = $mutations['proxies'];

        $items = [];

        if (count($headers) > 1) {
            $items[] = [
                'title' => 'Request Headers',
                'desc'  => 'Modify Request Headers',
            ];
        }

        if (count($proxies) > 1) {
            $items[] = [
                'title' => 'Proxies',
                'desc'  => 'Add ' . $this->countStatement(count($proxies), 'Proxy', 'Proxies'),
            ];
        }

        if (!count($items)) {
            return [];
        }

        $summary['Requests'] = [
            'id'          => Str::random(),
            'mutations'   => $items,
            'description' => 'Configure',
        ];

        return $summary;
    }


    /**
     * @param $mutations
     * @return array
     */
    private function getSchedulerSummary($mutations): array
    {
        $summary = [];

        $tasks = $mutations['tasks'];

        if (!count($tasks)) {
            return $summary;
        }

        $items = [
            [
                'title' => 'Commands',
                'desc'  => 'Create ' . $this->countStatement(count($tasks), 'Command'),
            ],
        ];

        $summary['Scheduler'] = [
            'id'          => Str::random(),
            'mutations'   => $items,
            'description' => 'Configure',
        ];

        return $summary;
    }

    /**
     * @param $mutations
     * @return array
     */
    private function getValidationSummary($mutations): array
    {
        $summary = [];

        $mapped = $mutations['rules'];

        if (!count($mapped)) {
            return [];
        }

        $totalRuleCount = collect($mapped)->map(fn ($m) => $m['rules'])->reduce(fn ($acc, $r) => count($r), 0);

        if ($totalRuleCount < 1) {
            return [];
        }

        $items = [
            [
                'title' => 'Rules',
                'desc'  => 'Create ' . $this->countStatement($totalRuleCount, 'Rule'),
            ],
        ];

        $summary['Validation'] = [
            'id'          => Str::random(),
            'mutations'   => $items,
            'description' => 'Configure',
        ];

        return $summary;
    }

    /**
     * @param $mutations
     * @return array
     */
    private function getDeploymentSummary($mutations): array
    {
        $summary = [];

        if (!$mutations['copyConfig']) {
            return $summary;
        }

        $items = [
            [
                'title' => 'Nginx',
                'desc'  => 'Create and Copy Configuration',
            ],
        ];

        $summary['Deployment'] = [
            'id'          => Str::random(),
            'mutations'   => $items,
            'description' => 'Configure',
        ];

        return $summary;
    }

    /**
     * @param $mutations
     * @return array
     */
    private function getFrontendSummary($mutations): array
    {
        $summary = [];

        $installTailwind = $mutations['tailwind']['install'];
        $navPartialItems = $mutations['navPartials']['items'];
        $alertPartialItems = $mutations['alertPartials'];
        $vueSetup = $mutations['vue'];

        $installVue = $vueSetup['config']['install'] ?? false;

        if (!$installVue && !$installTailwind && !count($navPartialItems) && !count($alertPartialItems)) {
            return [];
        }

        $items = [];

        if ($installTailwind) {
            $items[] = [
                'title' => 'Tailwind',
                'desc'  => 'Install and Create Configuration',
            ];
        }

        if (count($navPartialItems)) {
            $items[] = [
                'title' => 'Navigation Partials',
                'desc'  => 'Create ' . $this->countStatement(
                        count($navPartialItems), 'Navigation Partial', 'Navigation Partials'
                    ),
            ];
        }

        if (count($alertPartialItems)) {
            $items[] = [
                'title' => 'Alert Partials',
                'desc'  => 'Create ' . $this->countStatement(
                        count($alertPartialItems), 'Alert Partial', 'Alert Partials'
                    ),
            ];
        }

        if ($installVue) {
            $items[] = [
                'title' => 'Vue',
                'desc'  => 'Install and Configure Vue',
            ];
        }

        $summary['Frontend'] = [
            'id'          => Str::random(),
            'mutations'   => $items,
            'description' => 'Configure',
        ];

        return $summary;
    }

    /**
     * @param $mutations
     * @return array
     */
    private function getLayoutSummary($mutations): array
    {
        $summary = [];

        $layouts = collect($mutations['layouts'] ?? []);

        if (!count($layouts)) {
            return $summary;
        }

        $hasCustomLayout = $layouts->first(fn ($l) => $l['type'] === 'blade');
        $hasAdminLayout = $layouts->first(fn ($l) => $l['type'] === 'admin');

        $items = [];

        if ($hasCustomLayout) {
            $items[] = [
                'title' => 'Custom Layout',
                'desc'  => 'Blade layout from a theme',
            ];
        }

        if ($hasAdminLayout) {
            $items[] = [
                'title' => 'Admin Layout',
                'desc'  => 'Generate a complete admin panel',
            ];
        }

        $summary['Layouts'] = [
            'id'          => Str::random(),
            'mutations'   => $items,
            'description' => 'Configure',
        ];

        return $summary;
    }

    /**
     * @param $mutations
     * @return array
     */
    private function getLintersSummary($mutations): array
    {
        $summary = [];

        if (!$mutations['eslint']['create']) {
            return [];
        }

        $items = [
            'title' => 'ESLint',
            'desc'  => 'Create .eslintrc.json and Install Packages',
        ];

        $summary['Linters'] = [
            'id'          => Str::random(),
            'mutations'   => [$items],
            'description' => 'Configure',
        ];

        return $summary;
    }

    /**
     * @param $count
     * @param $single
     * @param null $plural
     * @return string|null
     */
    private function countStatement($count, $single, $plural = null): ?string
    {
        return !$count ? null : $count . ($count === 1 ? " $single" : " " . ($plural ?: Str::plural($single)));
    }
}
