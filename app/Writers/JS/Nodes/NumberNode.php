<?php

namespace App\Writers\JS\Nodes;

use App\Writers\JS\Contracts\JSNode;

/**
 * Class NumberNode
 * @package App\Writers\JS\Nodes
 */
class NumberNode implements JSNode
{
    /**
     * @var string
     */
    private $number;

    /**
     * NumberNode constructor.
     * @param $number
     */
    public function __construct($number)
    {
        $this->number = $number;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'number';
    }

    /**
     * @return string
     */
    public function getNodeValue(): string
    {
        return $this->number;
    }
}
