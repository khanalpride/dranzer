<?php

namespace App\Writers\JS\Nodes;

use App\Writers\JS\Contracts\JSNode;

/**
 * Class StringNode
 * @package App\Writers\JS\Nodes
 */
class StringNode implements JSNode
{
    /**
     * @var string
     */
    private string $string;
    /**
     * @var bool
     */
    private bool $quoted;
    /**
     * @var bool|mixed
     */
    private bool $useDoubleQuotes;

    /**
     * StringNode constructor.
     * @param string $string
     * @param bool $quoted
     * @param false $useDoubleQuotes
     */
    public function __construct(string $string, bool $quoted, $useDoubleQuotes = false)
    {
        $this->string = $string;
        $this->quoted = $quoted;
        $this->useDoubleQuotes = $useDoubleQuotes;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'string';
    }

    /**
     * @return string
     */
    public function getNodeValue(): string
    {
        if (!$this->quoted) {
            return $this->string;
        }

        return $this->useDoubleQuotes ? '"' . $this->string . '"' : "'$this->string'";
    }

    /**
     * @return bool
     */
    public function isQuoted(): bool
    {
        return $this->quoted;
    }

    /**
     * @param bool $quoted
     * @return StringNode
     */
    public function setQuoted(bool $quoted): StringNode
    {
        $this->quoted = $quoted;
        return $this;
    }
}
