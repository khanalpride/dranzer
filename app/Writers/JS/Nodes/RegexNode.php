<?php

namespace App\Writers\JS\Nodes;

use App\Writers\JS\Contracts\JSNode;
use Exception;

/**
 * Class RegexNode
 * @package App\Writers\JS\Nodes
 */
class RegexNode implements JSNode
{
    /**
     * @var string|mixed|null
     */
    private ?string $expression;

    /**
     * RegexNode constructor.
     * @param null $expression
     */
    public function __construct($expression = null)
    {
        $this->expression = $expression;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'regex';
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getNodeValue(): string
    {
        $expression = $this->expression;

        $matches = [];

        preg_match('/^\/.*?\/.*?([a-zA-Z]+)?/', $expression, $matches);

        if (!count($matches)) {
            throw new Exception('Invalid Regex Expression: ' . $expression);
        }

        return $expression;
    }
}
