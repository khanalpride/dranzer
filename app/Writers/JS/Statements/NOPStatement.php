<?php

namespace App\Writers\JS\Statements;

use Exception;
use App\Writers\JS\Contracts\JSStatement;

/**
 * Class NOPStatement
 * @package App\Writers\JS\Statements
 */
class NOPStatement implements JSStatement
{
    /**
     * @throws Exception
     */
    public function toString(): string
    {
        return '';
    }
}
