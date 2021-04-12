<?php /** @noinspection SpellCheckingInspection */

namespace App\Builders\PHP\Laravel\Framework;

use App\Builders\PHP\JSONBuilder;
use Illuminate\Support\Facades\File;
use App\Builders\Contracts\IFileBuilder;
use App\Builders\Processors\ComposerProcessor;

/**
 * Class ComposerBuilder
 * @package App\Builders\PHP\Laravel\Framework
 */
class ComposerBuilder implements IFileBuilder
{
    /**
     * @var string[] $processors
     */
    protected $processors = [
        ComposerProcessor::class,
    ];
    /**
     * @var string
     */
    private string $filename;
    /**
     * @var string $outputDir
     */
    private string $outputDir;
    /**
     * @var array
     */
    private $keyValueMap = [];
    /**
     * @var array
     */
    private $required = [];
    /**
     * @var array
     */
    private $requiredDev = [];
    /**
     * @var array
     */
    private $postAutoLoadDumpCommands = [];
    /**
     * @var array
     */
    private $postRootPackageInstallCommands = [];
    /**
     * @var array
     */
    private $postInstallCommands = [];
    /**
     * @var array
     */
    private $postUpdateCommands = [];
    /**
     * @var array
     */
    private $dontDiscover = [];

    /**
     * @return $this
     */
    public function prepare(): ComposerBuilder
    {
        return $this
            ->setDefaults();
    }

    /**
     * @return $this
     */
    private function setDefaults(): ComposerBuilder
    {
        $this->setFilename('composer.json')
            ->setDefaultRequired()
            ->setDefaultDevRequired()
            ->setPostAutoLoadDumpCommands(
                [
                    'Illuminate\\Foundation\\ComposerScripts::postAutoloadDump',
                    '@php artisan package:discover --ansi',
                ]
            )
            ->addPostRootPackageInstallCommand(
                '@php -r "file_exists(\'.env\') || copy(\'.env.example\', \'.env\');"'
            );

        return $this;
    }

    /**
     * @return bool
     */
    public function build(): bool
    {
        return $this
            ->setRootKeyValuePair()
            ->toDisk();
    }

    /**
     * @param $postRootPackageInstallCommand
     * @return ComposerBuilder
     */
    public function addPostRootPackageInstallCommand($postRootPackageInstallCommand): ComposerBuilder
    {
        $this->postRootPackageInstallCommands[] = $postRootPackageInstallCommand;
        return $this;
    }

    /**
     * @param $package
     * @param $version
     * @return $this
     */
    public function addRequired($package, $version): ComposerBuilder
    {
        if (!array_key_exists($package, $this->required)) {
            $this->required[$package] = $version;
        }

        return $this;
    }

    /**
     * @param $package
     * @param $version
     * @return $this
     */
    public function addRequiredDev($package, $version): ComposerBuilder
    {
        if (!array_key_exists($package, $this->requiredDev)) {
            $this->requiredDev[$package] = $version;
        }

        return $this;
    }

    /**
     * @param null $path
     * @return bool
     */
    public function toDisk($path = null): bool
    {
        return File::put(
                $path ?? "$this->outputDir/$this->filename",
                $this->getContents()
            ) !== false;
    }

    /**
     * @return string
     */
    public function getContents(): string
    {
        $jsonBuilder = new JSONBuilder;
        return $jsonBuilder->raw($this->keyValueMap)->build();
    }

    /**
     * @return array
     */
    public function getRequired(): array
    {
        return $this->required;
    }

    /**
     * @return array
     */
    public function getRequiredDev(): array
    {
        return $this->requiredDev;
    }

    /**
     * @return array
     */
    public function getPostAutoLoadDumpCommands(): array
    {
        return $this->postAutoLoadDumpCommands;
    }

    /**
     * @param array $postAutoLoadDumpCommands
     * @return ComposerBuilder
     */
    public function setPostAutoLoadDumpCommands(array $postAutoLoadDumpCommands): ComposerBuilder
    {
        $this->postAutoLoadDumpCommands = $postAutoLoadDumpCommands;
        return $this;
    }

    /**
     * @return array
     */
    public function getPostRootPackageInstallCommands(): array
    {
        return $this->postRootPackageInstallCommands;
    }

    /**
     * @param array $postRootPackageInstallCommands
     * @return ComposerBuilder
     */
    public function setPostRootPackageInstallCommands(array $postRootPackageInstallCommands): ComposerBuilder
    {
        $this->postRootPackageInstallCommands = $postRootPackageInstallCommands;
        return $this;
    }

    /**
     * @return array
     */
    public function getPostUpdateCommands(): array
    {
        return $this->postUpdateCommands;
    }

    /**
     * @param array $postUpdateCommands
     * @return ComposerBuilder
     */
    public function setPostUpdateCommands(array $postUpdateCommands): ComposerBuilder
    {
        $this->postUpdateCommands = $postUpdateCommands;
        return $this;
    }

    /**
     * @return array
     */
    public function getPostInstallCommands(): array
    {
        return $this->postInstallCommands;
    }

    /**
     * @param array $postInstallCommands
     * @return ComposerBuilder
     */
    public function setPostInstallCommands(array $postInstallCommands): ComposerBuilder
    {
        $this->postInstallCommands = $postInstallCommands;
        return $this;
    }

    /**
     * @return array
     */
    public function getDontDiscoverPackages(): array
    {
        return $this->dontDiscover;
    }

    /**
     * @param string $package
     */
    public function dontDiscoverPackage(string $package): void
    {
        $this->dontDiscover[] = $package;
    }

    /**
     * @return string[]
     */
    public function getProcessors(): array
    {
        return $this->processors;
    }

    /**
     * @return string
     */
    public function getOutputDir(): string
    {
        return $this->outputDir;
    }

    /**
     * @param $outputDir
     * @return ComposerBuilder
     */
    public function setOutputDir($outputDir): ComposerBuilder
    {
        $this->outputDir = $outputDir;

        return $this;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     * @return ComposerBuilder
     */
    public function setFilename(string $filename): ComposerBuilder
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @return bool
     */
    public function canBuild(): bool
    {
        return true;
    }

    /**
     * @return ComposerBuilder
     */
    private function setDefaultRequired(): ComposerBuilder
    {
        $this->addRequired('php', '^7.3|^8.0');
        $this->addRequired('fideloper/proxy', '^4.4');
        $this->addRequired('fruitcake/laravel-cors', '^2.0');
        $this->addRequired('guzzlehttp/guzzle', '^7.0.1');
        $this->addRequired('laravel/framework', '^8.36');
        $this->addRequired('laravel/tinker', '^2.5');
        return $this;
    }

    /**
     * @return ComposerBuilder
     */
    private function setDefaultDevRequired(): ComposerBuilder
    {
        $this->addRequiredDev('facade/ignition', '^2.5');
        $this->addRequiredDev('fakerphp/faker', '^1.13.0');
        $this->addRequiredDev('laravel/sail', '^1.0.1');
        $this->addRequiredDev('mockery/mockery', '^1.4.2');
        $this->addRequiredDev('nunomaduro/collision', '^5.0');
        $this->addRequiredDev('phpunit/phpunit', '^9.3.3');
        $this->addRequiredDev('roave/security-advisories', 'dev-latest');
        return $this;
    }

    /**
     * @return ComposerBuilder
     */
    private function setRootKeyValuePair(): ComposerBuilder
    {
        $this->keyValueMap = [
            'name'              => 'laravel/laravel',
            'type'              => 'project',
            'description'       => 'The Laravel Framework.',
            'keywords'          =>
                [
                    'framework',
                    'laravel',
                ],
            'license'           => 'MIT',
            'require'           => $this->getRequired(),
            'require-dev'       => $this->getRequiredDev(),
            'config'            =>
                [
                    'optimize-autoloader' => true,
                    'preferred-install'   => 'dist',
                    'sort-packages'       => true,
                ],
            'extra'             =>
                [
                    'laravel' =>
                        [
                            'dont-discover' => $this->getDontDiscoverPackages(),
                        ],
                ],
            'autoload'          =>
                [
                    'psr-4' =>
                        [
                            'App\\'                 => 'app/',
                            'Database\\Factories\\' => 'database/factories/',
                            'Database\\Seeders\\'   => 'database/seeders/',
                        ],
                ],
            'autoload-dev'      =>
                [
                    'psr-4' =>
                        [
                            'Tests\\' => 'tests/',
                        ],
                ],
            'minimum-stability' => 'dev',
            'prefer-stable'     => true,
            'scripts'           =>
                [
                    'post-autoload-dump'        => $this->getPostAutoLoadDumpCommands(),
                    'post-root-package-install' => $this->getPostRootPackageInstallCommands(),
                    'post-create-project-cmd'   =>
                        [
                            '@php artisan key:generate --ansi',
                        ],
                ],
        ];
        return $this;
    }
}
