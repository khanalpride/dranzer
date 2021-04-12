<?php

namespace App\Builders\PHP\Laravel\Framework\Config;

use App\Builders\PHP\FileBuilder;
use PhpParser\Node\Expr\ArrayItem;

/**
 * Class ViewBuilder
 * @package App\Builders\PHP\Laravel\Framework\Config
 */
class ViewBuilder extends FileBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'view.php';

    /**
     * @return ViewBuilder
     */
    public function prepare(): ViewBuilder
    {
        return $this;
    }

    /**
     * @return bool
     */
    public function build(): bool
    {
        return $this
            ->buildConfigArray()
            ->toDisk();
    }

    /**
     * @return ViewBuilder
     */
    private function buildConfigArray(): ViewBuilder
    {
        $this->retArr(([
            $this->getPathsKey(),
            $this->getCompiledKey(),
        ]));
        return $this;
    }

    /**
     * @return ArrayItem
     */
    private function getPathsKey(): ArrayItem
    {
        return $this->assoc('paths', $this->arr([
            $this->funcCall('resource_path', ([
                $this->string('views')
            ]))
        ]), 'View Storage Paths', 'Most templating systems load templates from disk. Here you may specify an array of paths that should be checked for your views. Of course, the usual Laravel view path has already been registered for you.');
    }

    /**
     * @return ArrayItem
     */
    private function getCompiledKey(): ArrayItem
    {
        return $this->assoc('compiled', $this->envFuncCall('VIEW_COMPILED_PATH', [
            $this->funcCall('realpath', [
                $this->funcCall('storage_path', [
                    $this->string('framework/views')
                ])
            ])
        ]), 'Compiled View Path', 'This option determines where all the compiled Blade templates will be stored for your application. Typically, this is within the storage directory. However, as usual, you are free to change this value.');
    }
}
