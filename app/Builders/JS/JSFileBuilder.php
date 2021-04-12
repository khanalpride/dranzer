<?php

namespace App\Builders\JS;

use RuntimeException;
use Illuminate\Support\Facades\File;
use App\Builders\Contracts\IFileBuilder;
use App\Writers\JS\Traits\JSWriterNodeHelpers;
use App\Writers\JS\Traits\JSWriterStmtHelpers;

/**
 * Class JSFileBuilder
 * @package App\Builders\PHP
 */
abstract class JSFileBuilder implements IFileBuilder
{
    use JSWriterNodeHelpers, JSWriterStmtHelpers;

    /**
     * @var array
     */
    protected array $processors = [];
    /**
     * @var string
     */
    protected string $filename;
    /**
     * @var string
     */
    protected string $outputDir;

    private array $stmts = [];

    private bool $canBuild = true;

    /**
     * @return $this
     */
    public function prepare(): JSFileBuilder
    {
        return $this;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     * @return JSFileBuilder
     */
    public function setFilename(string $filename): JSFileBuilder
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @return string
     */
    abstract public function getContents(): string;

    public function canBuild(): bool
    {
        return $this->canBuild;
    }

    public function getOutputDir(): string
    {
        return $this->outputDir;
    }

    /**
     * @param string $outputDir
     * @return JSFileBuilder
     */
    public function setOutputDir(string $outputDir): JSFileBuilder
    {
        $this->outputDir = $outputDir;

        return $this;
    }

    public function getProcessors(): array
    {
        return $this->processors;
    }

    /**
     * @param bool $canBuild
     * @return JSFileBuilder
     */
    public function setCanBuild(bool $canBuild): JSFileBuilder
    {
        $this->canBuild = $canBuild;
        return $this;
    }

    /**
     * @param null $path
     * @return bool
     */
    protected function toDisk($path = null): bool
    {
        $contents = $this->getContents();

        if (trim($contents) === '') {
            throw new RuntimeException('File cannot be empty!');
        }

        return File::put(
                $path ?? $this->outputDir . '/' . $this->filename,
                $contents
            ) !== null;
    }
}
