<?php

namespace App\Writers\JS\Statements;

use Exception;
use App\Writers\JS\Contracts\JSStatement;

/**
 * Class UseStrictStatement
 * @package App\Writers\JS\Statements
 */
class UseStrictStatement implements JSStatement
{
    /**
     * @throws Exception
     */
    public function toString(): string
    {
        return 'use strict';
    }
}
