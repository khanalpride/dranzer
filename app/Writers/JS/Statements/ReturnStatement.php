<?php

namespace App\Writers\JS\Statements;

use Exception;
use App\Writers\JS\Contracts\JSNode;
use App\Writers\JS\Contracts\JSStatement;

/**
 * Class ReturnStatement
 * @package App\Writers\JS\Statements
 */
class ReturnStatement implements JSStatement
{
    /**
     * @var JSNode
     */
    private JSNode $expression;

    /**
     * ReturnStatement constructor.
     * @param JSNode $expression
     */
    public function __construct(JSNode $expression)
    {
        $this->expression = $expression;
    }

    /**
     * @throws Exception
     */
    public function toString(): string
    {
        return "return {$this->expression->getNodeValue()};";
    }
}
