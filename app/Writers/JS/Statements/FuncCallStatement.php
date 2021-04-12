<?php

namespace App\Writers\JS\Statements;

use Exception;
use App\Writers\JS\Nodes\FuncCallNode;
use App\Writers\JS\Contracts\JSStatement;

/**
 * Class FuncCallStatement
 * @package App\Writers\JS\Statements
 */
class FuncCallStatement implements JSStatement
{
    /**
     * @var FuncCallNode
     */
    private FuncCallNode $funcCall;

    /**
     * FuncCallStatement constructor.
     * @param FuncCallNode $funcCall
     */
    public function __construct(FuncCallNode $funcCall)
    {
        $this->funcCall = $funcCall;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'funcCallStmt';
    }

    /**
     * @return string
     * @throws Exception
     */
    public function toString(): string
    {
        return $this->funcCall->getNodeValue() . ';';
    }
}
