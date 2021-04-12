<?php

namespace App\Builders\PHP\Laravel\Framework;

use App\Builders\PHP\FileBuilder;
use App\Builders\Processors\TailwindConfProcessor;

/**
 * Class TailwindConfBuilder
 * @package App\Builders\PHP\Laravel\Framework
 */
class TailwindConfBuilder extends FileBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        TailwindConfProcessor::class,
    ];
    /**
     * @var string
     */
    protected string $filename = 'tailwind.config.js';
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
     * @return TailwindConfBuilder
     */
    public function setConfig(string $config): TailwindConfBuilder
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
