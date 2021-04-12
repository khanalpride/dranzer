<?php

namespace App\Builders\PHP\Laravel\Framework\App\Http\Controllers;

use App\Builders\PHP\MethodBuilder;
use App\Builders\Processors\App\Http\Controllers\DynamicControllersProcessor;

class DynamicControllersBuilder extends ControllerBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        DynamicControllersProcessor::class,
    ];

    /**
     * @return bool
     */
    public function build(): bool
    {
        // Processors are responsible for actually building
        // the controllers.
        return true;
    }

    /**
     * @param $methodName
     * @param bool $addRequestParam
     * @param null $boundModelName
     * @param null $boundModelType
     * @return MethodBuilder
     */
    public function getResourceMethodBuilder($methodName, $addRequestParam = true, $boundModelName = null, $boundModelType = null): MethodBuilder
    {
        $methodBuilder = $this->getNewMethodBuilder($methodName);

        if ($addRequestParam) {
            $methodBuilder->addParameter(
                $this->param('request', 'Request')
            );
        }

        if ($boundModelName) {
            $methodBuilder->addParameter(
                $this->param($boundModelName, $boundModelType)
            );
        }

        $methodBuilder->addStatement($this->comment());

        return $methodBuilder;
    }
}
