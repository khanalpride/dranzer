<?php

namespace App\Writers\JS\Nodes;

use App\Writers\JS\Contracts\JSNode;
use App\Writers\JS\Contracts\JSStatement;

/**
 * Class FuncNode
 * @package App\Writers\JS\Nodes
 */
class FuncNode implements JSNode
{
    /**
     * @var array
     */
    private array $params;
    /**
     * @var array
     */
    private array $stmts;
    /**
     * @var null
     */
    private $name;
    /**
     * @var bool|mixed
     */
    private bool $async;

    /**
     * FuncNode constructor.
     * @param $params
     * @param $stmts
     * @param $name
     * @param false $async
     */
    public function __construct($params, $stmts, $name, $async = false)
    {
        $this->params = $params;
        $this->stmts = $stmts;
        $this->name = $name;
        $this->async = $async;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'func';
    }

    /**
     * @return string
     */
    public function getNodeValue(): string
    {
        $stmtsOutput = implode(PHP_EOL, array_map(static fn (JSStatement $stmt) => $stmt->toString(), $this->stmts));

        $paramsOutput = implode(', ', array_map(static fn ($param) => $param, $this->params));

        return ($this->async ? 'async ' : '') . ($this->name ?? '') . '(' . $paramsOutput . ')' . ' {' . PHP_EOL . $stmtsOutput . '}';
    }
}
