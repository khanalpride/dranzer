<?php

namespace App\Builders\PHP\Laravel\Framework\App\Http\Controllers;

use App\Builders\PHP\ClassBuilder;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Class ControllerBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Http\Controllers\ControllerBuilder
 */
class ControllerBuilder extends ClassBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'Controller.php';
    /**
     * @var string
     */
    protected string $namespace = 'App\Http\Controllers';
    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name'   => 'Controller',
        'extend' => 'BaseController',
    ];
    /**
     * @var string
     */
    protected string $name = 'Controller';
    /**
     * @var string
     */
    protected string $extends = 'BaseController';
    /**
     * @var array
     */
    private array $useStatements = [];
    /**
     * @var bool
     */
    private bool $useDefaultTraits = true;

    /**
     * @return $this
     */
    public function buildUseStatements(): ControllerBuilder
    {
        if ($this->useDefaultTraits) {
            $this->use(DispatchesJobs::class);
            $this->use(ValidatesRequests::class);
            $this->use(AuthorizesRequests::class);
            $this->use(Controller::class, 'BaseController');
        }

        foreach ($this->useStatements as $useStatement) {
            $this->use($useStatement);
        }

        return $this;
    }

    /**
     *
     * @return mixed|void
     */
    public function build(): bool
    {
        $status = $this
            ->buildUseStatements()
            ->buildTraits()
            ->toDisk();

        $this->reset();

        return $status;
    }

    /**
     * @return ControllerBuilder
     */
    public function buildTraits(): ControllerBuilder
    {
        $traits = $this->useDefaultTraits ? $this->getDefaultTraits() : [];

        $this->addTraits($traits);

        return $this;
    }

    /**
     * @return string[]
     */
    private function getDefaultTraits(): array
    {
        return [
            'AuthorizesRequests',
            'DispatchesJobs',
            'ValidatesRequests',
        ];
    }

    /**
     * @param $useStatement
     * @return ControllerBuilder
     */
    public function addUseStatement($useStatement): ControllerBuilder
    {
        $this->useStatements[] = $useStatement;
        return $this;
    }

    /**
     * @param bool $useDefaultTraits
     * @return ControllerBuilder
     */
    public function setUseDefaultTraits(bool $useDefaultTraits): ControllerBuilder
    {
        $this->useDefaultTraits = $useDefaultTraits;
        return $this;
    }

    /**
     * @return string
     */
    public function getExtends(): string
    {
        return $this->extends;
    }

    /**
     * @param string $extends
     * @return ControllerBuilder
     */
    public function setExtends(string $extends): ControllerBuilder
    {
        $this->extends = $extends;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ControllerBuilder
     */
    public function setName(string $name): ControllerBuilder
    {
        $this->name = $name;
        return $this;
    }
}
