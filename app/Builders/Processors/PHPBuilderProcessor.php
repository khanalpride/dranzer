<?php

namespace App\Builders\Processors;

use App\Builders\PHP\Helpers\BuilderHelpers;
use App\Builders\Processors\Contracts\IBuilderProcessor;

/**
 * Class PHPBuilderProcessor
 * @package App\Builders\Processors
 */
abstract class PHPBuilderProcessor implements IBuilderProcessor
{
    use BuilderHelpers;
}
