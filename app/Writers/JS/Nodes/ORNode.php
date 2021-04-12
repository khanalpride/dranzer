<?php

namespace App\Writers\JS\Nodes;

use App\Writers\JS\Contracts\JSNode;

/**
 * Class ORNode
 * @package App\Writers\JS\Nodes
 */
class ORNode implements JSNode
{
    /**
     * @var JSNode
     */
    private JSNode $lhs;
    /**
     * @var JSNode
     */
    private JSNode $rhs;

    /**
     * ORNode constructor.
     * @param JSNode $lhs
     * @param JSNode $rhs
     */
    public function __construct(JSNode $lhs, JSNode $rhs)
    {
        $this->lhs = $lhs;
        $this->rhs = $rhs;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'OR';
    }

    /**
     * @return string
     */
    public function getNodeValue(): string
    {
        return "{$this->lhs->getNodeValue()} || {$this->rhs->getNodeValue()}";
    }
}
