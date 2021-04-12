<?php

namespace App\Builders\PHP\Laravel\Framework;

use App\Builders\PHP\FileBuilder;
use App\Builders\Processors\SupervisorProcessor;

/**
 * Class SupervisorConfBuilder
 * @package App\Builders\PHP\Laravel\Framework
 */
class SupervisorConfBuilder extends FileBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        SupervisorProcessor::class,
    ];
    /**
     * @var string
     */
    protected string $filename = 'supervisor.conf';
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
     * @return SupervisorConfBuilder
     */
    public function setConfig(string $config): SupervisorConfBuilder
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
