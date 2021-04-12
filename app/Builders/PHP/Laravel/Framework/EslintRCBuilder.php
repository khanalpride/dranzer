<?php

namespace App\Builders\PHP\Laravel\Framework;

use App\Builders\PHP\FileBuilder;
use App\Builders\Processors\EslintRCProcessor;

/**
 * Class EslintRCBuilder
 * @package App\Builders\PHP\Laravel\Framework
 */
class EslintRCBuilder extends FileBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        EslintRCProcessor::class,
    ];
    /**
     * @var string
     */
    protected string $filename = '.eslintrc.json';
    /**
     * @var string
     */
    private string $config = '{ }';

    /**
     * @return $this
     */
    public function prepare(): EslintRCBuilder
    {
        return $this;
    }

    /**
     * @return bool
     *
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
     * @return EslintRCBuilder
     */
    public function setConfig(string $config): EslintRCBuilder
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
