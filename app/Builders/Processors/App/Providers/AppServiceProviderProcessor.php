<?php

namespace App\Builders\Processors\App\Providers;

use Closure;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\Helpers\Mutations\UIMutationHelpers;
use App\Builders\Helpers\Mutations\DatabaseMutationHelpers;
use App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\OrchidModule;
use App\Builders\PHP\Laravel\Framework\App\Providers\AppServiceProviderBuilder;

/**
 * Class AppServiceProviderProcessor
 * @package App\Builders\Processors\App\Providers
 */
class AppServiceProviderProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $this
            ->processLayouts($builder)
            ->processTelescope($builder)
            ->processHorizon($builder);

        $next($builder);

        return true;
    }

    /**
     * @param AppServiceProviderBuilder $builder
     * @return void
     */
    private function processHorizon(AppServiceProviderBuilder $builder): void
    {
        $installHorizon = app('mutations')->for('queues')['packages']['installHorizon'];

        if ($installHorizon) {
            $builder->use('Laravel\Horizon\Horizon');

            $builder
                ->getRegisterMethodBuilder()
                ->addStatement(
                    $this->inlineAssign(
                        $this->const('Horizon::$useDarkTheme'),
                        $this->const('true')
                    )
                );
        }

    }

    /**
     * @param AppServiceProviderBuilder $builder
     * @return AppServiceProviderProcessor
     */
    private function processLayouts(AppServiceProviderBuilder $builder): AppServiceProviderProcessor
    {
        $bootMethodBuilder = $builder->getBootMethodBuilder();

        $uiMutations = app('mutations')->for('ui');

        $customLayout = $uiMutations['customLayout'];

        if ($customLayout) {
            $layoutStyling = $customLayout['styling'];

            $bootstrapPagination = $layoutStyling['bootstrapPagination'] ?? false;

            if ($bootstrapPagination) {
                $builder->use(Paginator::class);
                $bootMethodBuilder->addStatement(
                    $this->staticCall(
                        'Paginator', 'defaultView', [
                            $this->string('pagination::bootstrap-4'),
                        ]
                    )
                );
            }
        }

        $installOrchid = UIMutationHelpers::installOrchid();

        if (!$installOrchid) {
            $bootMethodBuilder->getDocBuilder()
                ->addCommentLine('Bootstrap any application services.')
                ->setReturnType('void');

            return $this;
        }

        $adminModules = collect($uiMutations['orchid']['modules'])
            ->filter(fn (OrchidModule $module) => $module->isFullTextSearch())
            ->toArray();

        $builder->use(User::class);

        $models = [$this->const('User::class')];

        foreach ($adminModules as $adminModule) {
            $moduleId = $adminModule->getId();

            if (!$moduleId) {
                continue;
            }

            $moduleName = DatabaseMutationHelpers::getBlueprintNameFromId($moduleId);

            if (!$moduleName) {
                continue;
            }

            $builder->use("App\Models\\$moduleName");

            $models[] = $this->const("$moduleName::class");
        }

        $builder->use('Orchid\Platform\Dashboard');

        $bootMethodBuilder
            ->addParameter($this->param('dashboard', 'Dashboard'))
            ->addStatement(
                $this->methodCall(
                    'dashboard', 'registerSearch', [
                        $this->arr($models),
                    ]
                )
            );

        return $this;
    }

    /**
     * @param AppServiceProviderBuilder $builder
     * @return AppServiceProviderProcessor
     */
    private function processTelescope(AppServiceProviderBuilder $builder): AppServiceProviderProcessor
    {
        $statements = [];

        $installTelescope = app('mutations')->for('logging')['packages']['installTelescope'];

        if ($installTelescope) {
            $builder
                ->use('Laravel\Telescope\Telescope')
                ->use('Laravel\Telescope\TelescopeServiceProvider');

            $statements[] = $this->if(
                $this->chainedFuncCalls(
                    [
                        $this->propFetch('this', 'app'),
                        $this->funcCall('isLocal'),
                    ]
                ), [
                    $this->chainedFuncCallStmt(
                        $this->chainedFuncCalls(
                            [
                                $this->propFetch('this', 'app'),
                                $this->funcCall(
                                    'register', [
                                        $this->const('TelescopeServiceProvider::class'),
                                    ]
                                ),
                            ]
                        )
                    ),
                ]
            );

            $statements[] = $this->inlineAssign(
                $this->const('Telescope::$useDarkTheme'),
                $this->const('true')
            );

            $builder
                ->getRegisterMethodBuilder()
                ->addStatements($statements);
        }

        return $this;
    }
}
