<?php

namespace App\Builders\Processors;

use Closure;
use Illuminate\Support\Str;
use App\Writers\JS\JSWriter;
use Illuminate\Support\Facades\File;
use App\Builders\PHP\Laravel\Framework\WebpackMixBuilder;

/**
 * Class WebpackMixProcessor
 * @package App\Builders\Processors
 */
class WebpackMixProcessor extends JSBuilderProcessor
{
    /**
     * @var string
     */
    private string $projectRoot;
    /**
     * @var bool
     */
    private bool $useHMR = false;
    /**
     * @var array
     */
    private $additionalFilesToWrite = [];

    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        // Configure options
        $this->projectRoot = app('project-dir');

        $hmrConfig = app('mutations')->for('assets')['hmr'];

        $useHMR = $hmrConfig['enabled'] ?? false;

        $this->useHMR = $useHMR;

        $this->createDirectories();

        $customLayout = app('mutations')->for('ui')['customLayout'];

        if ($customLayout) {
            $this->processCustomLayout($customLayout);
        }

        $this->processAuthMutations($builder);

        $this->processHMR();

        $builder->setAdditionalFilesToWrite($this->additionalFilesToWrite);

        $next($builder);

        return true;
    }

    /**
     * @param WebpackMixBuilder $builder
     * @return void
     */
    private function processAuthMutations(WebpackMixBuilder $builder): void
    {
        $authMutations = app('mutations')->for('auth');

        $authEnabled = $authMutations['config']['enabled'];

        if (!$authEnabled) {
            return;
        }

        $authModule = $authMutations['module'];

        if ($authModule === 'ui') {
            $builder->addJsFile('resources/js/app.js', 'public/js');
            $builder->addSassFile('resources/sass/app.scss', 'public/css');
        }

        if ($authModule === 'breeze') {
            $builder->addJsFile('resources/js/app.js', 'public/js');
            $builder->addPostCssFile('resources/css/app.css', 'public/css', [
                $this->inlineRequire('postcss-import'),
                $this->inlineRequire('tailwindcss'),
                $this->inlineRequire('autoprefixer'),
            ]);
        }

    }

    /**
     * @param $group
     * @param $moduleName
     * @param $mixCall
     * @param $defaultOutputPath
     * @return string
     */
    private function processConcatenatedGroup($group, $moduleName, $mixCall, $defaultOutputPath): string
    {
        $statements = [];

        $outputPath = $group['outputPath'];

        $files = $group['assets'] ?? [];

        if (!count($files)) {
            return '';
        }

        $scriptStmts = [];

        collect($files)->each(function ($file) use (&$scriptStmts) {
            $basePath = "\${baseTemplatePath}/";
            $path = $basePath . $file['path'];
            $scriptStmts[] = $this->templateString($path);
        });

        $scripts = $this->array($scriptStmts, false);

        if (!Str::contains($outputPath, '/')) {
            $outputPath = $defaultOutputPath . $moduleName . '/' . $outputPath;
        }

        $statements[] = $this->funcCallStmt(
            $this->funcCall($mixCall, [
                $scripts,
                $this->templateString("\${baseOutputPath}/$outputPath")
            ], $this->var('mix'))
        );

        $statements[] = $this->nopStmt();

        $jsWriter = new JSWriter($statements);

        return $jsWriter->toString();
    }

    /**
     * @param array $assets
     * @param $layoutName
     * @param $baseResourcePath
     * @param $baseTemplatePath
     * @param $baseOutputPath
     * @return void
     */
    private function processCopyableAssets(array $assets, $layoutName, $baseResourcePath, $baseTemplatePath, $baseOutputPath): void
    {
        $statements = $this->getCommonStatements($baseResourcePath, $baseTemplatePath, $baseOutputPath);

        $assets = collect($assets)->filter(fn ($a) => $a['enabled'] && $a['sourcePath'])->toArray();

        foreach ($assets as $asset) {
            $source = $asset['sourcePath'];
            $target = $asset['targetPath'] ?? $source;

            $statements[] = $this->funcCallStmt(
                $this->funcCall('copy', [
                    $this->templateString("\${baseTemplatePath}/$source"),
                    $this->templateString("\${baseOutputPath}/$target"),
                ], $this->var('mix'))
            );
        }

        $statements[] = $this->nopStmt();

        $jsWriter = new JSWriter($statements);

        $code = $jsWriter->toString();

        $modulePath = $this->projectRoot . '/mix/modules/' . $layoutName;

        $this->additionalFilesToWrite[] = [
            'path'     => $modulePath . '/assets.js',
            'contents' => $code
        ];

        File::put($modulePath . '/assets.js', $code);

    }

    /**
     * @param $layout
     * @return void
     */
    private function processCustomLayout($layout): void
    {
        $layoutName = $layout['name'] ?? 'unnamed_layout';

        $paths = $layout['paths'];

        $baseResourcePath = $paths['resourcePath'] ?? 'resources';
        $baseTemplatePath = $paths['templatePath'] ?? '';
        $baseOutputPath = $paths['outputPath'] ?? 'public';

        $scripts = $layout['templateScripts'];
        $stylesheets = $layout['templateStylesheets'];

        $scriptGroups = $layout['scripts'];
        $stylesheetGroups = $layout['stylesheets'];

        $scriptPaths = collect($scriptGroups)
            ->map(fn ($sg) => $sg['assets'])
            ->collapse()
            ->map(fn ($sg) => $sg['path']);

        $stylesheetPaths = collect($stylesheetGroups)
            ->map(fn ($sg) => $sg['assets'])
            ->collapse()
            ->map(fn ($sg) => $sg['path']);

        $ungroupedScripts = collect($scripts)
            ->filter(fn ($s) => $s['enabled'] && !$scriptPaths->first(fn ($sgp) => $sgp === $s['asset']))
            ->map(fn ($s) => $s['asset']);

        $ungroupedStylesheets = collect($stylesheets)
            ->filter(fn ($s) => $s['enabled'] && !$stylesheetPaths->first(fn ($sgp) => $sgp === $s['asset']))
            ->map(fn ($s) => $s['asset']);

        $copyableAssets = $layout['copyableAssets'];

        if (!count($copyableAssets)) {
            $images = $layout['templateImages'];
            $videos = $layout['templateVideos'];
            $fonts = $layout['templateFonts'];

            $copyableAssets = collect($images)
                ->concat($videos)
                ->concat($fonts)
                ->map(fn ($a) => [
                    'sourcePath' => $a['asset'] ?? null,
                    'targetPath' => null,
                    'enabled'    => true,
                ])
                ->filter(fn ($a) => $a['sourcePath'])
                ->toArray();
        }

        $modulePath = $this->projectRoot . '/mix/modules/' . $layoutName;

        File::ensureDirectoryExists($modulePath);

        $this->processScripts($scriptGroups, $ungroupedScripts->toArray(), $layoutName, $modulePath, $baseResourcePath, $baseTemplatePath, $baseOutputPath);
        $this->processStylesheets($stylesheetGroups, $ungroupedStylesheets->toArray(), $layoutName, $modulePath, $baseResourcePath, $baseTemplatePath, $baseOutputPath);

        $this->processCopyableAssets($copyableAssets, $layoutName, $baseResourcePath, $baseTemplatePath, $baseOutputPath);

        $this->makeModuleIndex($layoutName);

    }

    /**
     * @return void
     */
    private function processHMR(): void
    {
        if (!$this->useHMR) {
            return;
        }

        $host = $hmrConfig['host'] ?? '127.0.0.1';
        $port = $hmrConfig['port'] ?? 8080;

        $statements = [];

        $statements[] = $this->require('laravel-mix', 'mix');

        $statements[] = $this->nopStmt();
        $statements[] = $this->assign(
            'url',
            $host && $host !== '127.0.0.1' ? $this->string($host) : $this->raw("process.env.APP_URL.replace(/(^\w+:|^)\/\//, '')")
        );

        $statements[] = $this->nopStmt();

        $statements[] = $this->funcCallStmt(
            $this->funcCall('options', [
                $this->object([
                    $this->keyValueMap(
                        $this->var('hmrOptions'),
                        $this->object([
                            $this->keyValueMap(
                                $this->var('host'),
                                $this->var('url'),
                                true
                            ),
                            $this->keyValueMap(
                                $this->var('port'),
                                $this->number($port ?: '8080')
                            ),
                        ])
                    )
                ])
            ], $this->var('mix'))
        );

        $webpackMixWriter = new JSWriter;
        $webpackMixWriter->setStatements($statements);

        $code = $webpackMixWriter->toString();

        $this->additionalFilesToWrite[] = [
            'path'     => $this->projectRoot . '/mix/hmr.js',
            'contents' => $code
        ];

    }

    /**
     * @param array $groups
     * @param array $ungroupedScripts
     * @param $layoutName
     * @param $modulePath
     * @param $baseResourcePath
     * @param $baseTemplatePath
     * @param $baseOutputPath
     * @return void
     */
    private function processScripts(array $groups, array $ungroupedScripts, $layoutName, $modulePath, $baseResourcePath, $baseTemplatePath, $baseOutputPath): void
    {
        $code = PHP_EOL;

        foreach ($groups as $group) {
            $code .= $this->processConcatenatedGroup($group, $layoutName, 'scripts', 'js/') . PHP_EOL . PHP_EOL;
        }

        $ungroupedScriptStmts = [];

        collect($ungroupedScripts)->each(function ($file) use (&$ungroupedScriptStmts) {
            $basePath = "\${baseTemplatePath}/";

            $path = $basePath . $file;

            $filename = pathinfo($file, PATHINFO_BASENAME);

            $ungroupedScriptStmts[] = $this->funcCallStmt(
                $this->funcCall('scripts', [
                    $this->array([$this->templateString($path)], false),
                    $this->templateString("\${baseOutputPath}/js/$filename")
                ], $this->var('mix'))
            );
        });

        $code .= PHP_EOL . (new JSWriter($ungroupedScriptStmts))->toString();

        $code = $this->compileCommonStmts($baseResourcePath, $baseTemplatePath, $baseOutputPath) . PHP_EOL . $code;

        $this->additionalFilesToWrite[] = [
            'path'     => $modulePath . '/scripts.js',
            'contents' => $code
        ];

    }

    /**
     * @param array $groups
     * @param $ungroupedStylesheets
     * @param $layoutName
     * @param $modulePath
     * @param $baseResourcePath
     * @param $baseTemplatePath
     * @param $baseOutputPath
     * @return void
     */
    private function processStylesheets(array $groups, $ungroupedStylesheets, $layoutName, $modulePath, $baseResourcePath, $baseTemplatePath, $baseOutputPath): void
    {
        $code = PHP_EOL;

        foreach ($groups as $group) {
            $code .= $this->processConcatenatedGroup($group, $layoutName, 'styles', 'css/') . PHP_EOL . PHP_EOL;
        }

        $ungroupedStylesheetStmts = [];

        collect($ungroupedStylesheets)->each(function ($file) use (&$ungroupedStylesheetStmts) {
            $basePath = "\${baseTemplatePath}/";

            $path = $basePath . $file;

            $filename = pathinfo($file, PATHINFO_BASENAME);

            $ungroupedStylesheetStmts[] = $this->funcCallStmt(
                $this->funcCall('styles', [
                    $this->array([$this->templateString($path)], false),
                    $this->templateString("\${baseOutputPath}/css/$filename")
                ], $this->var('mix'))
            );
        });

        $code .= PHP_EOL . (new JSWriter($ungroupedStylesheetStmts))->toString();

        $code = $this->compileCommonStmts($baseResourcePath, $baseTemplatePath, $baseOutputPath) . PHP_EOL . $code;

        $this->additionalFilesToWrite[] = [
            'path'     => $modulePath . '/stylesheets.js',
            'contents' => $code
        ];

    }

    /**
     * @return void
     */
    private function createDirectories(): void
    {
        File::ensureDirectoryExists($this->projectRoot . '/mix');

    }

    /**
     * @param $moduleName
     * @return void
     */
    private function makeModuleIndex($moduleName): void
    {
        $statements = [
            $this->require($this->templateString("./scripts")),
            $this->require($this->templateString("./stylesheets")),
            $this->require($this->templateString("./assets")),
        ];

        $modulePath = $this->projectRoot . '/mix/modules/' . $moduleName;

        File::ensureDirectoryExists($modulePath);

        $code = (new JSWriter($statements))->toString();

        $this->additionalFilesToWrite[] = [
            'path'     => $modulePath . '/index.js',
            'contents' => $code
        ];

    }

    /**
     * @param $baseResourcePath
     * @param $baseTemplatePath
     * @param $baseOutputPath
     * @return string
     */
    private function compileCommonStmts($baseResourcePath, $baseTemplatePath, $baseOutputPath): string
    {
        $statements = $this->getCommonStatements($baseResourcePath, $baseTemplatePath, $baseOutputPath);
        $statements[] = $this->nopStmt();

        $jsWriter = new JSWriter($statements);
        return $jsWriter->toString();
    }

    /**
     * @param $baseResourcePath
     * @param $baseTemplatePath
     * @param $baseOutputPath
     * @return array
     */
    private function getCommonStatements($baseResourcePath, $baseTemplatePath, $baseOutputPath): array
    {
        $statements = [];

        $statements[] = $this->require('laravel-mix', 'mix');

        $statements[] = $this->nopStmt();

        $statements[] = $this->assign(
            'baseResourcesPath',
            $this->string($baseResourcePath)
        );

        $statements[] = $this->assign(
            'baseTemplatePath',
            $this->concat(
                $this->var('baseResourcesPath'),
                $this->string('/' . $baseTemplatePath)
            )
        );

        $statements[] = $this->assign(
            'baseOutputPath',
            $this->string($baseOutputPath)
        );

        $statements[] = $this->nopStmt();

        return $statements;
    }
}
