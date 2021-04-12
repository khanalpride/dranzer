<?php

namespace App\Builders\Processors\Modules;

use Closure;
use Illuminate\Support\Str;
use App\Helpers\RegexHelpers;
use Illuminate\Support\Facades\File;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\Helpers\Mutations\UIMutationHelpers;
use App\Builders\PHP\Parser\Printers\Extensions\Standard;
use App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\OrchidModule;
use App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\OrchidColumn;
use App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\Builders\OrchidLayoutBuilder;
use App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\Builders\OrchidScreenBuilder;
use App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\Builders\OrchidPresenterBuilder;

/**
 * Class OrchidAdminModuleProcessor
 * @package App\Builders\Processors\Modules
 */
class OrchidAdminModuleProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        if (UIMutationHelpers::installOrchid()) {
            $dbMutations = app('mutations')->for('database');

            $blueprints = $dbMutations['blueprints'];

            $oneToManyRelations = $dbMutations['eloquent']['relations']['one-to-many'];

            $this->processOrchidAdminPanel($blueprints, $oneToManyRelations);
        }

        $next($builder);

        return true;
    }

    /**
     * @param array $blueprints
     * @param array $oneToManyRelations
     * @return void
     */
    private function processOrchidAdminPanel(array $blueprints, array $oneToManyRelations): void
    {
        $projectRoot = app('project-dir');

        collect([
            $projectRoot . '/app/Orchid',
            $projectRoot . '/app/Orchid/Screens',
            $projectRoot . '/app/Orchid/Filters',
            $projectRoot . '/app/Orchid/Layouts',
            $projectRoot . '/app/Orchid/Presenters',
        ])
            ->each(static fn ($dir) => File::ensureDirectoryExists($dir));

        $this->copyAssets(app('static-assets'), $projectRoot);

        $uiMutations = app('mutations')->for('ui');

        $orchidAdminModules = $uiMutations['orchid']['modules'];

        foreach ($orchidAdminModules as $orchidAdminModule) {
            $this->processOrchidModule($orchidAdminModule, $blueprints, $oneToManyRelations);
        }

        // Service Provider
        $platformProviderCode = $this->getPlatformProvider($orchidAdminModules);
        File::put($projectRoot . '/app/Orchid/PlatformProvider.php', $platformProviderCode);

        // Routes
        $platformRoutesCode = $this->getPlatformRoutes($orchidAdminModules);
        File::put($projectRoot . '/routes/platform.php', $platformRoutesCode);

    }

    /**
     * @param OrchidModule $module
     * @param array $blueprints
     * @param array $oneToManyRelations
     * @return void
     */
    private function processOrchidModule(OrchidModule $module, array $blueprints, array $oneToManyRelations): void
    {
        $projectRoot = app('project-dir');

        $moduleName = $module->getName();

        $screensPath = $projectRoot . '/app/Orchid/Screens';

        $layoutsPath = $projectRoot . '/app/Orchid/Layouts';

        // Build layouts
        collect([
            'listing'  => 'ListLayout',
            'creating' => 'EditLayout'
        ])
            ->each(
                static fn ($filename, $type) => (new OrchidLayoutBuilder)
                    ->setOutputDir($layoutsPath)
                    ->setFilename("$moduleName$filename.php")
                    ->setLayoutType($type)
                    ->setBlueprints($blueprints)
                    ->setModule($module)
                    ->setOneToManyRelations($oneToManyRelations)
                    ->build()
            );

        // Build screens
        collect([
            'listing'  => 'ListScreen',
            'creating' => 'EditScreen'
        ])
            ->each(
                static fn ($filename, $type) => (new OrchidScreenBuilder)
                    ->setOutputDir($screensPath)
                    ->setFilename("$moduleName$filename.php")
                    ->setScreenType($type)
                    ->setScreenName("$moduleName$filename")
                    ->setBlueprints($blueprints)
                    ->setModule($module)
                    ->build()
            );

        $searchableColumns = collect($module->getColumns())
            ->filter(fn (OrchidColumn $column) => $column->isSearchable())
            ->toArray();

        $usingScout = count($searchableColumns) && $module->isFullTextSearch();

        if ($usingScout) {
            File::copy(
                app('static-assets') . '/app/Console/Commands/Orchid/SyncWithAlgolia.php',
                $projectRoot . '/app/Console/Commands/SyncWithAlgolia.php'
            );

            $presentersPath = $projectRoot . '/app/Orchid/Presenters';

            (new OrchidPresenterBuilder)
                ->setOutputDir($presentersPath)
                ->setFilename("${moduleName}Presenter.php")
                ->setModule($module)
                ->build();
        }

    }

    /**
     * @param $modules
     * @return string|string[]
     */
    private function getPlatformProvider($modules)
    {
        $stmts = [];

        $titles = [];

        $sections = array_unique(collect($modules)
            ->map(fn (OrchidModule $module) => $module->getSection())
            ->toArray());

        $sections = array_flip($sections);

        foreach ($modules as $module) {
            if (!$module->shouldShowInNavigation()) {
                continue;
            }

            $moduleName = $module->getName();

            $pluralSnakedModuleName = Str::snake(Str::plural($moduleName));
            $route = 'platform.' . $pluralSnakedModuleName;
            $label = Str::plural($moduleName);
            $title = $module->getSection();
            $icon = $module->getNavigationIcon();

            $props = [
                $this->chainableMethodCall('icon', [$this->string($icon)]),
                $this->chainableMethodCall('route', [$this->string($route)])
            ];

            if (!in_array($title, $titles, true)) {
                $props[] = $this->chainableMethodCall('title', [$this->string($title)]);
                $titles[] = $title;
            }

            $stmts[$sections[$title]][] = $this->chainableStaticCall('ItemMenu', 'label', [
                $this->string($label)
            ], $props);
        }

        $stmts = $this->arr(collect($stmts)->flatten()->toArray());

        $generator = new Standard(['shortArraySyntax' => true]);

        $menuItems = $generator->prettyPrint([$stmts]);

        $providerPath = app('static-assets') . '/app/Orchid/PlatformProvider.php';
        $providerContent = file_get_contents($providerPath);
        return str_replace("___MENU_ITEMS__", $menuItems, $providerContent);
    }

    /**
     * @param array $modules
     * @return string|string[]
     */
    private function getPlatformRoutes(array $modules)
    {
        $stmts = collect();

        foreach ($modules as $module) {
            $stmts [] = $this->getPlatformRouteStatements($module);
        }

        $generator = new Standard(['shortArraySyntax' => true]);

        $menuItems = $generator->prettyPrint($stmts->collapse()->toArray());

        $platformRoutesPath = app('static-assets') . '/routes/orchid/platform.php';

        $platformRoutesContent = file_get_contents($platformRoutesPath);

        return str_replace("__ROUTES__", $menuItems, $platformRoutesContent);
    }

    /**
     * @param OrchidModule $module
     * @return array
     */
    private function getPlatformRouteStatements(OrchidModule $module): array
    {
        if (!$module->shouldShowInNavigation()) {
            return [];
        }

        $stmts = [];

        $moduleName = $module->getName();

        $pluralSnakedModuleName = Str::snake(Str::plural($moduleName));

        $stmts[] = $this->methodCallStmt($this->chainableStaticCall('Route', 'screen', [
            $this->string($pluralSnakedModuleName),
            $this->const("\App\Orchid\Screens\\${moduleName}ListScreen::class")
        ], [
            $this->chainableMethodCall('name', [
                $this->string("platform.$pluralSnakedModuleName")
            ])
        ]));

        $singularSnakedModuleName = Str::snake(Str::singular($moduleName));
        $stmts[] = $this->methodCallStmt($this->chainableStaticCall('Route', 'screen', [
            $this->string($singularSnakedModuleName . "/\{$singularSnakedModuleName?}"),
            $this->const("\App\Orchid\Screens\\${moduleName}EditScreen::class")
        ], [
            $this->chainableMethodCall('name', [
                $this->string("platform.$singularSnakedModuleName.edit")
            ])
        ]));

        // Add a newline stmt for readability.
        if (count($stmts)) {
            $stmts[] = $this->nop();
        }

        return $stmts;
    }

    /**
     * @param $assetsPath
     * @param $projectRoot
     * @return void
     */
    private function copyAssets($assetsPath, $projectRoot): void
    {
        // Config File (platform.php)
        File::copy(
            $assetsPath . '/config/orchid/platform.php', $projectRoot . '/config/platform.php'
        );

        // Views
        File::ensureDirectoryExists($projectRoot . '/resources/views/admin/partials');

        File::copy(
            $assetsPath . '/resources/views/orchid/header.blade.php', $projectRoot . '/resources/views/admin/partials/header.blade.php'
        );
        File::copy(
            $assetsPath . '/resources/views/orchid/footer.blade.php', $projectRoot . '/resources/views/admin/partials/footer.blade.php'
        );
        File::copy(
            $assetsPath . '/resources/views/orchid/dashboard.blade.php', $projectRoot . '/resources/views/admin/partials/dashboard.blade.php'
        );

        // Migrations
        $migrations = File::allFiles($assetsPath . '/database/migrations/orchid');

        // Renew the timestamps on the migrations.
        foreach ($migrations as $migration) {
            $oldTs = RegexHelpers::match('/^\d+_\d+_\d+_\d+/', $migration->getFilename());

            $newTs = date('Y_m_d_His');

            // Need the role_users migration to run after the user and role
            // migrations have already completed (appending 999 places it after
            // the user and role migration files).
            $newTs = Str::contains($migration->getFilename(), 'role_users')
                ? substr($newTs, 0, 14) . '999' : $newTs;

            $newName = str_replace($oldTs, $newTs, $migration->getFilename());

            File::copy($migration->getPathname(), $projectRoot . '/database/migrations/' . $newName);
        }

        // Copy default Screens, Filters, Layouts and Presenters
        File::copy(
            $assetsPath . '/app/Orchid/Screens/PlatformScreen.php', $projectRoot . '/app/Orchid/Screens/PlatformScreen.php'
        );

        File::copyDirectory($assetsPath . '/app/Orchid/Screens/User', $projectRoot . '/app/Orchid/Screens/User');
        File::copyDirectory($assetsPath . '/app/Orchid/Screens/Role', $projectRoot . '/app/Orchid/Screens/Role');
        File::copyDirectory($assetsPath . '/app/Orchid/Filters', $projectRoot . '/app/Orchid/Filters');
        File::copyDirectory($assetsPath . '/app/Orchid/Layouts', $projectRoot . '/app/Orchid/Layouts');
        File::copyDirectory($assetsPath . '/app/Orchid/Presenters', $projectRoot . '/app/Orchid/Presenters');

        // Commands
        File::copy($assetsPath . '/app/Console/Commands/Orchid/SetDefaultAdminPermissions.php', $projectRoot . '/app/Console/Commands/SetDefaultAdminPermissions.php');

    }
}
