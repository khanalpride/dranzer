<?php

namespace App\Writers\JS\Nodes;

use App\Writers\JS\Contracts\JSNode;

/**
 * Class RawNode
 * @package App\Writers\JS\Nodes
 */
class RawNode implements JSNode
{
    /**
     * @var mixed|null
     */
    private $value;

    /**
     * RawNode constructor.
     * @param null $value
     */
    public function __construct($value = null)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'raw';
    }

    /**
     * @return string
     */
    public function getNodeValue(): string
    {
        return $this->value;
    }

    /**
     * @return null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param null $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }
}
