<?php

namespace App\Builders\PHP\Laravel\Framework;

use App\Builders\PHP\FileBuilder;
use App\Builders\Processors\NginxProcessor;

/**
 * Class NginxConfBuilder
 * @package App\Builders\PHP\Laravel\Framework
 */
class NginxConfBuilder extends FileBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        NginxProcessor::class,
    ];
    /**
     * @var string
     */
    protected string $filename = 'nginx.conf';
    /**
     * @var string
     */
    private string $config = '';

    /**
     * @return bool
     */
    public function build(): bool
    {
        return $this->toDisk();
    }

    /**
     * @return string
     */
    public function getConfig(): string
    {
        return $this->config;
    }

    /**
     * @param string $config
     * @return NginxConfBuilder
     */
    public function setConfig(string $config): NginxConfBuilder
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return string
     */
    public function getContents(): string
    {
        return $this->config;
    }
}
