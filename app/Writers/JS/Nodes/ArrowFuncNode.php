<?php

namespace App\Writers\JS\Nodes;

use App\Writers\JS\Contracts\JSNode;
use App\Writers\JS\Contracts\JSStatement;

/**
 * Class ArrowFuncNode
 * @package App\Writers\JS\Nodes
 */
class ArrowFuncNode implements JSNode
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
     * ArrowFuncNode constructor.
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
        return 'arrowFunc';
    }

    /**
     * @return string
     */
    public function getNodeValue(): string
    {
        $multiStmt = count($this->stmts) > 1 || (count($this->stmts) && $this->stmts[0] instanceof JSStatement);

        $multiStmtsOutput = implode(PHP_EOL, array_map(static fn($stmt) => $stmt instanceof JSStatement ? $stmt->toString() : $stmt->getNodeValue(), $this->stmts));

        $stmtsOutput = $multiStmt ? $multiStmtsOutput : $this->stmts[0]->getNodeValue();

        $paramsOutput = implode(', ', array_map(static fn($param) => $param, $this->params));

        return ($this->async ? 'async ' : '') . ($this->name ?? '') . '(' . $paramsOutput . ')' . ' => ' . ($multiStmt ? ' {' . PHP_EOL . $stmtsOutput . '}' : $stmtsOutput);
    }
}
