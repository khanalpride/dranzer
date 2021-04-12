<?php

namespace App\Builders\Processors;

use App\Writers\JS\Traits\JSWriterNodeHelpers;
use App\Writers\JS\Traits\JSWriterStmtHelpers;
use App\Builders\Processors\Contracts\IBuilderProcessor;

/**
 * Class JSBuilderProcessor
 * @package App\Builders\Processors
 */
abstract class JSBuilderProcessor implements IBuilderProcessor
{
    use JSWriterNodeHelpers, JSWriterStmtHelpers;
}
