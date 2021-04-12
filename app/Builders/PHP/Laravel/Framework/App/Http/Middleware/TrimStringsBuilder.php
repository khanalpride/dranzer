<?php

namespace App\Builders\PHP\Laravel\Framework\App\Http\Middleware;

use App\Builders\PHP\ClassBuilder;
use App\Builders\PHP\PropertyBuilder;
use Illuminate\Foundation\Http\Middleware\TrimStrings;

/**
 * Class TrimStringsBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Http\Middleware
 */
class TrimStringsBuilder extends ClassBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'TrimStrings.php';
    /**
     * @var string
     */
    protected string $namespace = 'App\Http\Middleware';

    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name'   => 'TrimStrings',
        'extend' => 'Middleware',
    ];

    /**
     * @var array
     */
    private array $except = [];

    private PropertyBuilder $exceptPropertyBuilder;

    /**
     * @return TrimStringsBuilder
     */
    public function prepare(): TrimStringsBuilder
    {
        return $this
            ->instantiatePropertyBuilders()
            ->buildUseStatements()
            ->setDefaults();
    }

    /**
     * @return TrimStringsBuilder
     */
    private function instantiatePropertyBuilders(): TrimStringsBuilder
    {
        return $this->setExceptPropertyBuilder($this->getNewPropertyBuilder('except'));
    }

    /**
     * @return TrimStringsBuilder
     */
    protected function buildUseStatements(): TrimStringsBuilder
    {
        return $this->useMiddleware();
    }

    /**
     * @return TrimStringsBuilder
     */
    private function setDefaults(): TrimStringsBuilder
    {
        $this
            ->addExcept('current_password')
            ->addExcept('password')
            ->addExcept('password_confirmation')
            ->getExceptPropertyBuilder()
            ->makeProtected()
            ->getDocBuilder()
            ->addCommentLine('The names of the attributes that should not be trimmed.')
            ->addVar('array');

        return $this;
    }

    private function useMiddleware(): TrimStringsBuilder
    {
        return $this->use(TrimStrings::class, 'Middleware');
    }

    /**
     * @return bool
     */
    public function build(): bool
    {
        return $this
            ->buildClass()
            ->toDisk();
    }

    /**
     * @return TrimStringsBuilder
     */
    protected function buildClass(): TrimStringsBuilder
    {
        $this->addExceptProperty();

        return $this;
    }

    /**
     * @param $except
     * @return TrimStringsBuilder
     */
    public function addExcept($except): TrimStringsBuilder
    {
        $this->except[] = $except;

        return $this;
    }

    /**
     * @return TrimStringsBuilder
     */
    private function addExceptProperty(): TrimStringsBuilder
    {
        $this->getExceptPropertyBuilder()
            ->setValue($this->getExcept());

        $this->addPropertyBuilder($this->getExceptPropertyBuilder());

        return $this;
    }

    /**
     * @return PropertyBuilder
     */
    public function getExceptPropertyBuilder(): PropertyBuilder
    {
        return $this->exceptPropertyBuilder;
    }

    /**
     * @param PropertyBuilder $exceptPropertyBuilder
     * @return TrimStringsBuilder
     */
    public function setExceptPropertyBuilder(PropertyBuilder $exceptPropertyBuilder): TrimStringsBuilder
    {
        $this->exceptPropertyBuilder = $exceptPropertyBuilder;

        return $this;
    }

    /**
     * @return array
     */
    public function getExcept(): array
    {
        return $this->except;
    }
}
