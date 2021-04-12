<?php

namespace App\Builders\PHP\Laravel\Framework\App\Http\Middleware;

use App\Builders\PHP\ClassBuilder;
use App\Builders\PHP\PropertyBuilder;
use Illuminate\Cookie\Middleware\EncryptCookies;

/**
 * Class EncryptCookiesBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Http\Middleware
 */
class EncryptCookiesBuilder extends ClassBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'EncryptCookies.php';
    /**
     * @var string
     */
    protected string $namespace = 'App\Http\Middleware';
    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name'   => 'EncryptCookies',
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
     * @return EncryptCookiesBuilder
     */
    public function prepare(): EncryptCookiesBuilder
    {
        return $this
            ->instantiatePropertyBuilders()
            ->buildUseStatements()
            ->setDefaults();
    }

    /**
     * @return EncryptCookiesBuilder
     */
    private function instantiatePropertyBuilders(): EncryptCookiesBuilder
    {
        return $this->setExceptPropertyBuilder($this->getNewPropertyBuilder('except'));
    }

    /**
     * @return EncryptCookiesBuilder
     */
    protected function buildUseStatements(): EncryptCookiesBuilder
    {
        return $this->useMiddleware();
    }

    /**
     * @return EncryptCookiesBuilder
     */
    private function setDefaults(): EncryptCookiesBuilder
    {
        $this->getExceptPropertyBuilder()
            ->makeProtected()
            ->getDocBuilder()
            ->addCommentLine('The names of the cookies that should not be encrypted.')
            ->addVar('array');

        return $this;
    }

    /**
     * @return EncryptCookiesBuilder
     */
    private function useMiddleware(): EncryptCookiesBuilder
    {
        return $this->use(EncryptCookies::class, 'Middleware');
    }

    /**
     *
     * @return bool
     */
    public function build(): bool
    {
        return $this
            ->buildClass()
            ->toDisk();
    }

    /**
     * @return EncryptCookiesBuilder
     */
    protected function buildClass(): EncryptCookiesBuilder
    {
        $this->addExceptProperty();

        return $this;
    }

    /**
     * @param $except
     * @return EncryptCookiesBuilder
     */
    public function addExcept($except): EncryptCookiesBuilder
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
     * @return PropertyBuilder
     */
    public function getExceptPropertyBuilder(): PropertyBuilder
    {
        return $this->exceptPropertyBuilder;
    }

    /**
     * @param PropertyBuilder $exceptPropertyBuilder
     * @return EncryptCookiesBuilder
     */
    public function setExceptPropertyBuilder(PropertyBuilder $exceptPropertyBuilder): EncryptCookiesBuilder
    {
        $this->exceptPropertyBuilder = $exceptPropertyBuilder;

        return $this;
    }
}
