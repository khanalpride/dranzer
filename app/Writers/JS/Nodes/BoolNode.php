<?php

namespace App\Writers\JS\Nodes;

use App\Writers\JS\Contracts\JSNode;

/**
 * Class BoolNode
 * @package App\Writers\JS\Nodes
 */
class BoolNode implements JSNode
{
    /**
     * @var bool
     */
    private bool $value;

    /**
     * BoolNode constructor.
     * @param $value
     */
    public function __construct($value)
    {
        $this->value = (bool) $value;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'boolean';
    }

    /**
     * @return string
     */
    public function getNodeValue(): string
    {
        return $this->value === true ? 'true' : 'false';
    }
}
