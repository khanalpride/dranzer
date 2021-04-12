<?php

namespace App\Writers\JS\Traits;

use RuntimeException;
use App\Writers\JS\Nodes\ORNode;
use App\Writers\JS\Nodes\NOPNode;
use App\Writers\JS\Nodes\RawNode;
use App\Writers\JS\Nodes\VarNode;
use App\Writers\JS\Nodes\BoolNode;
use App\Writers\JS\Nodes\FuncNode;
use App\Writers\JS\Nodes\NullNode;
use App\Writers\JS\Nodes\ArrayNode;
use App\Writers\JS\Contracts\JSNode;
use App\Writers\JS\Nodes\ConcatNode;
use App\Writers\JS\Nodes\NumberNode;
use App\Writers\JS\Nodes\ObjectNode;
use App\Writers\JS\Nodes\StringNode;
use App\Writers\JS\Nodes\FuncCallNode;
use App\Writers\JS\Nodes\KeyValueNode;
use App\Writers\JS\Nodes\ArrowFuncNode;
use App\Writers\JS\Nodes\InlineRequire;
use App\Writers\JS\Nodes\ArrayAccessNode;
use App\Writers\JS\Nodes\ObjectAccessNode;
use App\Writers\JS\Nodes\TemplateStringNode;

/**
 * Trait JSWriterNodeHelpers
 * @package App\Writers\JS\Traits
 */
trait JSWriterNodeHelpers
{
    /**
     * @var bool
     */
    private $doubleQuotesForStrings = false;

    /**
     *
     */
    public function useDoubleQuotesForStrings(): void
    {
        $this->doubleQuotesForStrings = true;
    }

    /**
     *
     */
    public function useSingleQuotesForStrings(): void
    {
        $this->doubleQuotesForStrings = false;
    }

    /**
     * @param string $value
     * @param bool $quoted
     * @return StringNode
     */
    public function string(string $value, $quoted = true): StringNode
    {
        return new StringNode($value, $quoted, $this->doubleQuotesForStrings);
    }

    /**
     * @param string $value
     * @return TemplateStringNode
     */
    public function templateString(string $value): TemplateStringNode
    {
        return new TemplateStringNode($value);
    }

    /**
     * @param array $items
     * @param bool $trailingComma
     * @return ArrayNode
     */
    public function array(array $items = [], bool $trailingComma = true): ArrayNode
    {
        return new ArrayNode($items, $trailingComma);
    }

    /**
     * @param JSNode $array
     * @param JSNode $index
     * @return ArrayAccessNode
     */
    public function arrayAccess(JSNode $array, JSNode $index): ArrayAccessNode
    {
        return new ArrayAccessNode($array, $index);
    }

    /**
     * @param bool $value
     * @return BoolNode
     */
    public function bool(bool $value): BoolNode
    {
        return new BoolNode($value);
    }

    /**
     * @return NullNode
     */
    public function null(): NullNode
    {
        return new NullNode();
    }

    /**
     * @param array $mappings
     * @param bool $multiLine
     * @param false $spacedSingleMapping
     * @return ObjectNode
     */
    public function object(array $mappings, $multiLine = true, $spacedSingleMapping = false): ObjectNode
    {
        return new ObjectNode($mappings, $multiLine, $spacedSingleMapping);
    }

    /**
     * @param JSNode $lhs
     * @param JSNode $rhs
     * @return ConcatNode
     */
    public function concat(JSNode $lhs, JSNode $rhs): ConcatNode
    {
        return new ConcatNode($lhs, $rhs);
    }

    /**
     * @param array $nodes
     * @return ConcatNode|null
     */
    public function fluentConcat(array $nodes): ?ConcatNode
    {
        if (count($nodes) < 2) {
            return null;
        }

        $stack = $this->concat($nodes[0], $nodes[1]);

        $sliced = array_slice($nodes, 2);

        foreach ($sliced as $node) {
            $stack = $this->concat($stack, $node);
        }

        return $stack;
    }

    /**
     * @param $key
     * @param $value
     * @param false $trailingComma
     * @return KeyValueNode
     */
    public function keyValueMap($key, $value, $trailingComma = false): KeyValueNode
    {
        $key = is_string($key) ? $this->string($key) : $key;
        $value = is_string($value) ? $this->string($value) : $value;

        if (!$key instanceof JSNode) {
            throw new RuntimeException('Key must be an instance of JSNode');
        }

        if (!$value instanceof JSNode) {
            throw new RuntimeException('Value must be an instance of JSNode');
        }

        return new KeyValueNode($key, $value, $trailingComma);
    }

    /**
     * @param string $funcName
     * @param array $params
     * @param null $object
     * @param array $chainedCalls
     * @return FuncCallNode
     */
    public function funcCall(string $funcName, $params = [], $object = null, array $chainedCalls = []): FuncCallNode
    {
        if ($params instanceof JSNode) {
            $params = [$params];
        }

        return new FuncCallNode($funcName, $params, $object, $chainedCalls);
    }

    /**
     * @param array $params
     * @param array $stmts
     * @param null $name
     * @param bool $async
     * @return ArrowFuncNode
     */
    public function arrowFunc(array $params, array $stmts, $name = null, bool $async = false): ArrowFuncNode
    {
        return new ArrowFuncNode($params, $stmts, $name, $async);
    }

    /**
     * @param array $params
     * @param array $stmts
     * @param null $name
     * @param bool $async
     * @return FuncNode
     */
    public function func(array $params, array $stmts, $name = null, bool $async = false): FuncNode
    {
        return new FuncNode($params, $stmts, $name, $async);
    }

    /**
     * @param $value
     * @return RawNode
     */
    public function raw($value): RawNode
    {
        return new RawNode($value);
    }

    /**
     * @param string $path
     * @return ObjectAccessNode
     */
    public function objectAccess(string $path): ObjectAccessNode
    {
        return new ObjectAccessNode($path);
    }

    /**
     * @param $number
     * @return NumberNode
     */
    public function number($number): NumberNode
    {
        return new NumberNode($number);
    }

    /**
     * @param JSNode $lhs
     * @param JSNode $rhs
     * @return ORNode
     */
    public function or(JSNode $lhs, JSNode $rhs): ORNode
    {
        return new ORNode($lhs, $rhs);
    }

    /**
     * @param string $varName
     * @return VarNode
     */
    public function var(string $varName): VarNode
    {
        return new VarNode($varName);
    }

    /**
     * @param string $module
     * @return InlineRequire
     */
    public function inlineRequire(string $module): InlineRequire
    {
        return new InlineRequire($module);
    }

    /**
     * @return NOPNode
     */
    public function nop(): NOPNode
    {
        return new NOPNode();
    }

    /**
     * @return bool
     */
    public function usingDoubleQuotesForStrings(): bool
    {
        return $this->doubleQuotesForStrings;
    }

    /**
     * @return bool
     */
    public function usingSingleQuotesForStrings(): bool
    {
        return !$this->doubleQuotesForStrings;
    }
}
