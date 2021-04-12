<?php

namespace App\Writers\JS\Nodes;

use App\Writers\JS\Contracts\JSNode;

/**
 * Class ArrayAccessNode
 * @package App\Writers\JS\Nodes
 */
class ArrayAccessNode implements JSNode
{
    /**
     * @var JSNode
     */
    private JSNode $array;
    /**
     * @var JSNode
     */
    private JSNode $index;

    /**
     * ArrayAccessNode constructor.
     * @param JSNode $array
     * @param JSNode $index
     */
    public function __construct(JSNode $array, JSNode $index)
    {
        $this->array = $array;
        $this->index = $index;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'arrayAccess';
    }

    /**
     * @return string
     */
    public function getNodeValue(): string
    {
        return "{$this->array->getNodeValue()}[{$this->index->getNodeValue()}]";
    }
}
