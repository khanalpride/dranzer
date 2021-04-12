<?php

namespace App\Writers\JS\Nodes;

use Exception;
use App\Writers\JS\Contracts\JSNode;

/**
 * Class FuncCallNode
 * @package App\Writers\JS\Nodes
 */
class FuncCallNode implements JSNode
{
    /**
     * @var JSNode|null
     */
    private $object;
    /**
     * @var string
     */
    private string $funcName;
    /**
     * @var array
     */
    private array $params;
    /**
     * @var array
     */
    private array $chainedCalls;

    /**
     * FuncCallNode constructor.
     * @param string $funcName
     * @param array $params
     * @param JSNode|null $object
     * @param array $chainedCalls
     */
    public function __construct(string $funcName, array $params = [], JSNode $object = null, array $chainedCalls = [])
    {
        $this->funcName = $funcName;
        $this->object = $object;
        $this->params = $params;
        $this->chainedCalls = $chainedCalls;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'funcCall';
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getNodeValue(): string
    {
        $value = '';

        if ($this->object) {
            $value = $this->object->getNodeValue() . '.';
        }

        $value .= $this->funcName . '(';

        foreach ($this->params as $param) {
            // Remove trailing commas from array nodes since we'll be adding one to separate params.
            if ($param instanceof ArrayNode) {
                $param->setArrayTrailingComma(false);
            }

            $value .= $param->getNodeValue() . ', ';
        }

        $value = rtrim($value, ', ');

        $value .= ')';

        if (count($this->chainedCalls)) {
            foreach ($this->chainedCalls as $chainedCall) {
                $value .= '.' . $chainedCall->getNodeValue();
            }
        }

        return $value;

//        return ($this->object ? $this->object->getNodeValue() . '.' : '') . $this->funcName . '(' . implode(', ', array_map(fn(JSNode $node) => $node->getNodeValue(), $this->params)) . ')' . (count($this->chainedCalls) ? implode('', array_map(fn(JSNode $call) => '(' . $call->getNodeValue() . ')', $this->chainedCalls)) : '');
    }
}
