<?php

namespace App\Builders\Contracts;

/**
 * Interface IFileBuilder
 * @package App\Builders\Contracts
 */
interface IFileBuilder
{
    /**
     * @return $this
     */
    public function prepare(): self;

    /**
     * @return bool
     */
    public function build(): bool;

    /**
     * @return bool
     */
    public function canBuild(): bool;

    /**
     * @return string
     */
    public function getContents(): string;

    /**
     * @param string $filename
     * @return $this
     */
    public function setFilename(string $filename): self;

    /**
     * @return string
     */
    public function getFilename(): string;

    /**
     * @param string $outputDir
     * @return $this
     */
    public function setOutputDir(string $outputDir): self;

    /**
     * @return string
     */
    public function getOutputDir(): string;

    /**
     * @return array
     */
    public function getProcessors(): array;
}
