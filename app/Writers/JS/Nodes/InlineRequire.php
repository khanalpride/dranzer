<?php

namespace App\Writers\JS\Nodes;

use App\Writers\JS\Contracts\JSNode;

/**
 * Class InlineRequire
 * @package App\Writers\JS\Nodes
 */
class InlineRequire implements JSNode
{
    /**
     * @var string
     */
    private string $module;

    /**
     * InlineRequire constructor.
     * @param string $module
     */
    public function __construct(string $module)
    {
        $this->module = $module;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'inline-require';
    }

    /**
     * @return string
     */
    public function getNodeValue(): string
    {
        return "require('" . $this->module . "')";
    }
}
