<?php

namespace App\Writers\JS\Statements;

use Exception;
use App\Writers\JS\Nodes\ObjectNode;
use App\Writers\JS\Contracts\JSStatement;

class ObjectStatement implements JSStatement
{
    private ObjectNode $node;

    public function __construct(ObjectNode $node)
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
