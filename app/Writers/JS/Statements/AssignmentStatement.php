<?php

namespace App\Writers\JS\Statements;

use App\Writers\JS\Nodes\VarNode;
use App\Writers\JS\Contracts\JSNode;
use App\Writers\JS\Contracts\JSStatement;

/**
 * Class AssignmentStatement
 * @package App\Writers\JS\Statements
 */
class AssignmentStatement implements JSStatement
{
    /**
     * @var bool
     */
    private $asConst;
    /**
     * @var JSNode
     */
    private JSNode $expression;
    /**
     * @var VarNode
     */
    private VarNode $varName;

    /**
     * AssignmentStatement constructor.
     * @param VarNode $varName
     * @param JSNode $expression
     * @param bool $asConst
     */
    public function __construct(VarNode $varName, JSNode $expression, bool $asConst = true)
    {
        $this->varName = $varName;
        $this->expression = $expression;
        $this->asConst = $asConst;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'assignment';
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return ($this->asConst ? 'const' : 'let') . " {$this->varName->getNodeValue()} = {$this->expression->getNodeValue()};";
    }
}
