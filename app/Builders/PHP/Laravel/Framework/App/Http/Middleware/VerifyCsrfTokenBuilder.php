<?php

namespace App\Builders\PHP\Laravel\Framework\App\Http\Middleware;

use App\Builders\PHP\ClassBuilder;
use App\Builders\PHP\PropertyBuilder;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

/**
 * Class VerifyCsrfTokenBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Http\Middleware
 */
class VerifyCsrfTokenBuilder extends ClassBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'VerifyCsrfToken.php';
    /**
     * @var string
     */
    protected string $namespace = 'App\Http\Middleware';
    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name'   => 'VerifyCsrfToken',
        'extend' => 'Middleware',
    ];
    /**
     * @var array
     */
    private array $except = [];
    /**
     * @var PropertyBuilder
     */
    private PropertyBuilder $exceptPropertyBuilder;

    /**
     * @return VerifyCsrfTokenBuilder
     */
    public function prepare(): VerifyCsrfTokenBuilder
    {
        return $this
            ->instantiatePropertyBuilders()
            ->buildUseStatements()
            ->setDefaults();
    }

    /**
     * @return VerifyCsrfTokenBuilder
     */
    private function instantiatePropertyBuilders(): VerifyCsrfTokenBuilder
    {
        return $this->setExceptPropertyBuilder($this->getNewPropertyBuilder('except'));
    }

    /**
     * @return VerifyCsrfTokenBuilder
     */
    protected function buildUseStatements(): VerifyCsrfTokenBuilder
    {
        $this->useMiddleware();

        return $this;
    }

    /**
     * @return VerifyCsrfTokenBuilder
     */
    private function setDefaults(): VerifyCsrfTokenBuilder
    {
        $this->getExceptPropertyBuilder()
            ->makeProtected()
            ->getDocBuilder()
            ->addCommentLine('The URIs that should be excluded from CSRF verification.')
            ->addVar('array');

        return $this;
    }

    private function useMiddleware(): void
    {
        $this->use(VerifyCsrfToken::class, 'Middleware');
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
     * @return VerifyCsrfTokenBuilder
     */
    protected function buildClass(): VerifyCsrfTokenBuilder
    {
        $this->addExceptProperty();

        return $this;
    }

    /**
     * @param $except
     * @return VerifyCsrfTokenBuilder
     */
    public function addExcept($except): VerifyCsrfTokenBuilder
    {
        $this->except[] = $except;

        return $this;
    }

    /**
     * @return void
     */
    private function addExceptProperty(): void
    {
        $this->getExceptPropertyBuilder()
            ->setValue($this->getExcept());

        $this->addPropertyBuilder($this->getExceptPropertyBuilder());
    }

    /**
     * @return array
     */
    public function getExcept(): array
    {
        return $this->except;
    }

    /**
     * @param array $except
     * @return VerifyCsrfTokenBuilder
     */
    public function setExcept(array $except): VerifyCsrfTokenBuilder
    {
        $this->except = $except;

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
     * @return VerifyCsrfTokenBuilder
     */
    public function setExceptPropertyBuilder(PropertyBuilder $exceptPropertyBuilder): VerifyCsrfTokenBuilder
    {
        $this->exceptPropertyBuilder = $exceptPropertyBuilder;

        return $this;
    }

}
