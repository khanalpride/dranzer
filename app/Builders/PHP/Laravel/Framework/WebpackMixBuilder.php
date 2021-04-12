<?php

namespace App\Builders\PHP\Laravel\Framework;

use App\Writers\JS\JSWriter;
use App\Builders\JS\JSFileBuilder;
use Illuminate\Support\Facades\File;
use App\Builders\Processors\WebpackMixProcessor;

/**
 * Class WebpackMixBuilder
 * @package App\Builders\PHP\Laravel\Framework
 */
class WebpackMixBuilder extends JSFileBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        WebpackMixProcessor::class,
    ];
    /**
     * @var string
     */
    protected string $filename = 'webpack.mix.js';
    /**
     * @var bool
     */
    private $useHMR = false;
    /**
     * @var array
     */
    private $jsFiles = [];
    /**
     * @var array
     */
    private $postCssFiles = [];
    /**
     * @var array
     */
    private $sassFiles = [];

    /**
     * @var array
     */
    private $additionalFilesToWrite = [];
    /**
     * @var array
     */
    private array $statements = [];

    /**
     * @return bool
     */
    public function build(): bool
    {
        $customLayout = app('mutations')->for('ui')['customLayout'];

        $this->statements = $this->getWebpackMixFileStatements($customLayout);

        return $this
            ->writeAdditionalFiles()
            ->toDisk();
    }

    /**
     * @param string $path
     * @param string $contents
     * @return WebpackMixBuilder
     */
    public function addAdditionalFileToWrite(string $path, string $contents): WebpackMixBuilder
    {
        $this->additionalFilesToWrite[] = [
            'path'     => $path,
            'contents' => $contents
        ];

        return $this;
    }

    /**
     * @param $jsFile
     * @param $outputDirectory
     * @return $this
     */
    public function addJsFile($jsFile, $outputDirectory): WebpackMixBuilder
    {
        $this->jsFiles[$jsFile] = $outputDirectory;
        return $this;
    }

    /**
     * @param $cssFile
     * @param $outputDirectory
     * @param array $params
     * @return $this
     */
    public function addPostCssFile($cssFile, $outputDirectory, $params = []): WebpackMixBuilder
    {
        $this->postCssFiles[$cssFile] = [
            'outputDir' => $outputDirectory,
            'params'    => $params,
        ];
        return $this;
    }

    /**
     * @param $sassFile
     * @param $outputDirectory
     * @return $this
     */
    public function addSassFile($sassFile, $outputDirectory): WebpackMixBuilder
    {
        $this->sassFiles[$sassFile] = $outputDirectory;
        return $this;
    }

    /**
     * @return string
     */
    public function getContents(): string
    {
        $webpackMixWriter = new JSWriter;
        $webpackMixWriter->setStatements($this->statements);

        return $webpackMixWriter->toString();
    }

    /**
     * @return bool
     */
    public function shouldUseHMR(): bool
    {
        return $this->useHMR;
    }

    /**
     * @param bool $useHMR
     * @return WebpackMixBuilder
     */
    public function setUseHMR(bool $useHMR): WebpackMixBuilder
    {
        $this->useHMR = $useHMR;
        return $this;
    }

    /**
     * @return array
     */
    public function getAdditionalFilesToWrite(): array
    {
        return $this->additionalFilesToWrite;
    }

    /**
     * @param array $additionalFilesToWrite
     * @return WebpackMixBuilder
     */
    public function setAdditionalFilesToWrite(array $additionalFilesToWrite): WebpackMixBuilder
    {
        foreach ($additionalFilesToWrite as $file) {
            $path = $file['path'];
            $contents = $file['contents'];

            $this->addAdditionalFileToWrite($path, $contents);
        }

        return $this;
    }

    /**
     * @return WebpackMixBuilder
     */
    private function writeAdditionalFiles(): WebpackMixBuilder
    {
        foreach ($this->additionalFilesToWrite as $file) {
            $path = $file['path'];
            $contents = $file['contents'];

            File::put($path, $contents);
        }

        return $this;
    }

    /**
     * @param $customLayout
     * @return array
     */
    private function getWebpackMixFileStatements($customLayout): array
    {
        $statements = [];

        $statements[] = $this->require('laravel-mix', 'mix');

        //Frontend
        $vueSetup = app('mutations')->for('frontend')['vue'];

        $vueConfig = $vueSetup['config'] ?? [];

        $installVue = $vueConfig['install'] ?? false;

        $createMain = $vueConfig['createMain'] ?? true;

        if ($installVue && $createMain) {
            $authMutations = app('mutations')->for('auth');

            $authEnabled = $authMutations['config']['enabled'];

            if (!$authEnabled) {
                $this->addJsFile('resources/js/app.js', 'public/js');
            }

            $statements[] = $this->nopStmt();

            $statements[] = $this->rawStmt(
                $this->raw("mix.js('resources/js/modules/main.js', 'public/js/modules').vue();")
            );

            $statements[] = $this->nopStmt();
        }

        if ($customLayout) {
            $moduleNames = [
                $this->string($customLayout['name'])
            ];
            $statements[] = $this->nopStmt();

            $statements[] = $this->funcCallStmt(
                $this->funcCall('forEach', [
                    $this->arrowFunc(['module'], [
                        $this->require($this->templateString("./mix/modules/\${module}")),
                    ])
                ], $this->array($moduleNames))
            );
        }

        if (count($this->jsFiles)) {
            $statements[] = $this->nopStmt();
        }

        foreach ($this->jsFiles as $jsFile => $outputDir) {
            $statements[] = $this->funcCallStmt(
                $this->funcCall('js', [
                    $this->string($jsFile),
                    $this->string($outputDir)
                ], $this->var('mix'))
            );
        }

        if (count($this->postCssFiles)) {
            $statements[] = $this->nopStmt();
        }

        foreach ($this->postCssFiles as $postCssFile => $options) {
            $outputDir = $options['outputDir'];
            $params = $options['params'] ?? [];

            $statements[] = $this->funcCallStmt(
                $this->funcCall(
                    'postCss',
                    [
                        $this->string($postCssFile),
                        $this->string($outputDir),
                        $this->array($params)
                    ],
                    $this->var('mix')
                )
            );
        }

        if (count($this->sassFiles)) {
            $statements[] = $this->nopStmt();
        }

        foreach ($this->sassFiles as $sassFile => $outputDir) {
            $statements[] = $this->funcCallStmt(
                $this->funcCall('sass', [
                    $this->string($sassFile),
                    $this->string($outputDir)
                ], $this->var('mix'))
            );
        }

        if ($this->useHMR) {
            $statements[] = $this->nopStmt();
            $statements[] = $this->comment('HMR related settings');
            $statements[] = $this->require('./mix/hmr');
        }

        return array_merge($statements, $this->getMiscConfigStatements());
    }

    /**
     * @return array
     */
    private function getMiscConfigStatements(): array
    {
        $statements = [];

        $miscConfig = app('mutations')->for('assets')['misc'];

        if ($miscConfig['version']) {
            $statements[] = $this->rawStmt(
                $this->raw("
                    if (process.env.npm_lifecycle_event !== 'hot' && process.env.NODE_ENV !== 'development') {
                        // noinspection JSUnresolvedFunction
                        mix.version();
                    }
                ")
            );
        }

        if ($miscConfig['disableSuccessNotifications']) {
            $statements[] = $this->nopStmt();

            $statements[] = $this->funcCallStmt(
                $this->funcCall('disableSuccessNotifications', [], $this->var('mix'))
            );
        }

        return $statements;
    }
}
