<?php

namespace App\Writers\JS\Statements;

use Exception;
use App\Writers\JS\Nodes\RawNode;
use App\Writers\JS\Contracts\JSStatement;

class RawStatement implements JSStatement
{
    private RawNode $node;

    public function __construct(RawNode $node)
    {
        $this->node = $node;
    }

    /**
     * @throws Exception
     */
    public function toString(): string
    {
        return $this->node->getNodeValue();
    }
}
