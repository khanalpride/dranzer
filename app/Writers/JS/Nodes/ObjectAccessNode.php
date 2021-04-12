<?php

namespace App\Writers\JS\Nodes;

use App\Writers\JS\Contracts\JSNode;

/**
 * Class ObjectAccessNode
 * @package App\Writers\JS\Nodes
 */
class ObjectAccessNode implements JSNode
{
    /**
     * @var string
     */
    private string $path;

    /**
     * ObjectAccessNode constructor.
     * @param $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'objectAccess';
    }

    /**
     * @return string
     */
    public function getNodeValue(): string
    {
        return $this->path;
    }
}
