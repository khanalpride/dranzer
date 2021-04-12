<?php

namespace App\Writers\JS\Nodes;

use App\Writers\JS\Contracts\JSNode;

/**
 * Class NOPNode
 * @package App\Writers\JS\Nodes
 */
class NOPNode implements JSNode
{
    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'nop';
    }

    /**
     * @return string
     */
    public function getNodeValue(): string
    {
        return '';
    }
}
