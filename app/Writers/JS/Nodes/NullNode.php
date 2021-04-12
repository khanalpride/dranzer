<?php

namespace App\Writers\JS\Nodes;

use App\Writers\JS\Contracts\JSNode;

/**
 * Class NullNode
 * @package App\Writers\JS\Nodes
 */
class NullNode implements JSNode
{
    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'null';
    }

    /**
     * @return string
     */
    public function getNodeValue(): string
    {
        return 'null';
    }
}
