<?php

namespace App\Builders\PHP\Laravel\Framework;

use App\Builders\PHP\JSONBuilder;
use App\Builders\PHP\FileBuilder;
use App\Builders\Processors\PackageProcessor;

/**
 * Class PackageBuilder
 * @package App\Builders\PHP\Laravel\Framework
 */
class PackageBuilder extends FileBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        PackageProcessor::class,
    ];
    /**
     * @var string
     */
    protected string $filename = 'package.json';
    /**
     * @var array
     */
    private $keyValueMap = [];
    /**
     * @var array
     */
    private $devDependencies = [];
    /**
     * @var array
     */
    private $dependencies = [];
    /**
     * @var string
     */
    private $hotCommand = 'mix watch --hot';

    /**
     * @return PackageBuilder
     */
    public function prepare(): PackageBuilder
    {
        return $this->setDefaults();
    }

    /**
     * @return PackageBuilder
     */
    private function setDefaults(): PackageBuilder
    {
        $this->keyValueMap = [
            'private'         => true,
            'scripts'         => [
                'dev'         => 'npm run development',
                'development' => 'mix',
                'watch'       => 'mix watch',
                'watch-poll'  => 'mix watch -- --watch-options-poll=1000',
                'prod'        => 'npm run production',
                'production'  => 'mix --production',
            ],
            'devDependencies' => array_merge([
                'axios'              => '^0.21',
                'cross-env'          => '^7.0',
                'laravel-mix'        => '^6.0.6',
                'lodash'             => '^4.17.19',
                'resolve-url-loader' => '^3.1.0',
            ], $this->getDevDependencies()),
        ];

        return $this;
    }

    /**
     * @return $this
     */
    public function useHttpsForHMR(): PackageBuilder
    {
        $this->setHotCommand('mix watch --hot --https');

        return $this;
    }

    /**
     * @return bool
     */
    public function build(): bool
    {
        $this
            ->addHotScript()
            ->addDependenciesKey()
            ->updateDevDependencies()
            ->toDisk();

        return $this->toDisk();
    }

    /**
     * @return PackageBuilder
     */
    private function addDependenciesKey(): PackageBuilder
    {
        if (count($this->getDependencies())) {
            $dependencies = $this->getDependencies();
            ksort($dependencies);
            $this->keyValueMap['dependencies'] = $dependencies;
        }

        return $this;
    }

    /**
     * @param $package
     * @param $version
     * @return $this
     */
    public function addDependency($package, $version): PackageBuilder
    {
        $this->dependencies[$package] = $version;
        return $this;
    }

    /**
     * @param $package
     * @param $version
     * @return $this
     */
    public function addDevDependency($package, $version): PackageBuilder
    {
        $this->devDependencies[$package] = $version;
        return $this;
    }

    /**
     * @return PackageBuilder
     */
    private function addHotScript(): PackageBuilder
    {
        $this->keyValueMap['scripts']['hot'] = $this->hotCommand;

        return $this;
    }

    /**
     * @return array
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * @return array
     */
    public function getDevDependencies(): array
    {
        return $this->devDependencies;
    }

    /**
     * @return string
     */
    public function getHotCommand(): string
    {
        return $this->hotCommand;
    }

    /**
     * @param string $hotCommand
     */
    public function setHotCommand(string $hotCommand): void
    {
        $this->hotCommand = $hotCommand;
    }

    /**
     * @return string
     */
    public function getContents(): string
    {
        return (new JSONBuilder)->raw($this->keyValueMap)->build();
    }

    /**
     * @return PackageBuilder
     */
    private function updateDevDependencies(): PackageBuilder
    {
        $this->keyValueMap['devDependencies'] = array_merge($this->keyValueMap['devDependencies'], $this->getDevDependencies());

        return $this;
    }
}
