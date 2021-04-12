<?php

namespace App\Writers\JS\Nodes;

use App\Writers\JS\Contracts\JSNode;

/**
 * Class VarNode
 * @package App\Writers\JS\Nodes
 */
class VarNode implements JSNode
{
    /**
     * @var string
     */
    private string $varName;

    /**
     * VarNode constructor.
     * @param string $varName
     */
    public function __construct(string $varName)
    {
        $this->varName = $varName;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'var';
    }

    /**
     * @return string
     */
    public function getNodeValue(): string
    {
        return $this->varName;
    }
}
