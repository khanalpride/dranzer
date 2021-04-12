<?php

namespace App\Writers\JS\Nodes;

use App\Writers\JS\Contracts\JSNode;

/**
 * Class TemplateStringNode
 * @package App\Writers\JS\Nodes
 */
class TemplateStringNode implements JSNode
{
    /**
     * @var string
     */
    private string $string;

    /**
     * TemplateStringNode constructor.
     * @param string $string
     */
    public function __construct(string $string)
    {
        $this->string = $string;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'templateString';
    }

    /**
     * @return string
     */
    public function getNodeValue(): string
    {
        return '`' . $this->string . '`';
    }
}
