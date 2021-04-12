<?php

/** @noinspection PhpUndefinedFieldInspection */

/** @noinspection SpellCheckingInspection */

namespace App\Builders\PHP\Helpers;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Comment\Doc;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\Expression;
use App\Builders\PHP\DocBlockBuilder;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use App\Builders\PHP\Parser\Extensions\Stmt\Comment;
use App\Builders\PHP\Parser\Extensions\Stmt\BlockComment;
use App\Builders\PHP\Parser\Extensions\Stmt\NestedAssign;
use App\Builders\PHP\Parser\Extensions\Expr\ExpressionGroup;
use App\Builders\PHP\Parser\Extensions\Stmt\ChainedFuncCall;
use App\Builders\PHP\Parser\Extensions\Expr\ChainedFuncCalls;
use App\Builders\PHP\Parser\Extensions\Stmt\ChainedStaticCall;
use App\Builders\PHP\Parser\Extensions\Expr\ChainedPropFetches;

/**
 * Trait BuilderHelpers
 * @package App\Builders\PHP\Helpers
 */
trait BuilderHelpers
{
    /**
     * @param mixed ...$traits
     * @return TraitUse
     */
    protected function useTraits(...$traits): TraitUse
    {
        return new TraitUse(...$traits);
    }

    /**
     * @param $array
     * @param $keyToFetch
     * @param array $attributes
     * @return ArrayDimFetch
     */
    protected function arrayFetch($array, $keyToFetch, array $attributes = []): ArrayDimFetch
    {
        return new ArrayDimFetch(
            is_string($array) ? $this->var($array) : $array,
            is_string($keyToFetch) ? $this->string($keyToFetch) : $keyToFetch,
            $attributes
        );
    }

    /**
     * @param $name
     * @param array $attributes
     * @return Variable
     */
    protected function var($name, array $attributes = []): Variable
    {
        return new Variable($name, $attributes);
    }

    /**
     * @param $value
     * @param array $attributes
     * @return String_
     */
    protected function string($value, array $attributes = []): String_
    {
        return new String_($value, $attributes);
    }

    /**
     * @param $left
     * @param $right
     * @param array $attributes
     * @return NotIdentical
     */
    protected function strictNotEquals($left, $right, array $attributes = []): NotIdentical
    {
        return new NotIdentical($left, $right, $attributes);
    }

    /**
     * @param $left
     * @param $right
     * @param array $attributes
     * @return Expr\BinaryOp\Coalesce
     */
    protected function coalesce($left, $right, array $attributes = []): Expr\BinaryOp\Coalesce
    {
        return new Expr\BinaryOp\Coalesce($left, $right, $attributes);
    }

    /**
     * @param $left
     * @param $right
     * @param array $attributes
     * @return Expr\BinaryOp\Greater
     */
    protected function greaterThan($left, $right, array $attributes = []): Expr\BinaryOp\Greater
    {
        return new Expr\BinaryOp\Greater($left, $right, $attributes);
    }

    /**
     * @param $expr
     * @param array $attributes
     * @return BooleanNot
     */
    protected function boolNot($expr, array $attributes = []): BooleanNot
    {
        return new BooleanNot($expr, $attributes);
    }

    /**
     * @param $left
     * @param $right
     * @param array $attributes
     * @return Identical
     */
    protected function strictEquals($left, $right, array $attributes = []): Identical
    {
        return new Identical($left, $right, $attributes);
    }

    /**
     * @param $cond
     * @param $if
     * @param $else
     * @param array $attributes
     * @return Ternary
     */
    protected function ternary($cond, $if, $else, array $attributes = []): Ternary
    {
        return new Ternary($cond, $if, $else, $attributes);
    }

    /**
     * @param $key
     * @param $value
     * @param null $commentHeading
     * @param null $commentBody
     * @param array $attributes
     * @return ArrayItem
     */
    protected function assoc($key, $value, $commentHeading = null, $commentBody = null, array $attributes = []): ArrayItem
    {
        $item = new ArrayItem(
            is_string($value) ? $this->string($value) : $value,
            is_string($key) ? $this->string($key) : $key,
            false,
            $attributes
        );

        if ($commentHeading) {
            $docBlockBuilder = new DocBlockBuilder;
            $docBlockBuilder->laravelStyleDoc($commentHeading, $commentBody);
            $item->setDocComment($docBlockBuilder->getDocBlock());
        }
        return $item;
    }

    protected function bitwiseOr($left, $right, $attributes = []): Expr\BinaryOp\BitwiseOr
    {
        return new Expr\BinaryOp\BitwiseOr($left, $right, $attributes);
    }

    /**
     * @param $number
     * @param array $attributes
     * @return LNumber
     */
    protected function int($number, array $attributes = []): LNumber
    {
        return new LNumber($number, $attributes);
    }

    /**
     * @return Nop
     */
    protected function nop(): Nop
    {
        return new Nop();
    }

    /**
     * @return \App\Builders\PHP\Parser\Extensions\Expr\Nop
     */
    protected function nopExpr(): \App\Builders\PHP\Parser\Extensions\Expr\Nop
    {
        return new \App\Builders\PHP\Parser\Extensions\Expr\Nop();
    }

    /**
     * @param string $comment
     * @param array $attributes
     * @return Comment
     */
    protected function comment($comment = '', array $attributes = []): Comment
    {
        return new Comment($comment, $attributes);
    }

    /**
     * @param Expr $left
     * @param Expr $right
     * @param array $attributes
     * @return Concat
     */
    protected function concat(Expr $left, Expr $right, array $attributes = []): Concat
    {
        return new Concat($left, $right, $attributes);
    }

    /**
     * @param $name
     * @param null $comment
     * @param array $commentAttributes
     * @return ConstFetch
     */
    protected function const($name, $comment = null, array $commentAttributes = []): ConstFetch
    {
        if (is_bool($name)) {
            $name = $name === true ? 'true' : 'false';
        }

        $const = new ConstFetch(new Name($name));

        if ($comment) {
            $docBlockBuilder = new DocBlockBuilder;
            $docBlockBuilder->rawComment($comment);
            $const->setDocComment($docBlockBuilder->getDocBlock());
            $const->getDocComment()->commentAttributes = $commentAttributes;
        }

        return $const;
    }

    /**
     * @return ConstFetch
     */
    protected function null(): ConstFetch
    {
        return $this->const('null');
    }

    /**
     * @return ConstFetch
     */
    protected function true(): ConstFetch
    {
        return $this->const('true');
    }

    /**
     * @return ConstFetch
     */
    protected function false(): ConstFetch
    {
        return $this->const('false');
    }

    /**
     * @param Expr $expr
     * @param array $attributes
     * @return Include_
     */
    protected function include(Expr $expr, array $attributes = []): Include_
    {
        return new Include_($expr, 1, $attributes);
    }

    /**
     * @param Expr $expr
     * @param null $comment
     * @param array $attributes
     * @return Include_
     */
    protected function require(Expr $expr, $comment = null, array $attributes = []): Include_
    {
        $require = new Include_($expr, 3, $attributes);

        $comment && $require->setDocComment($this->doc($comment));

        return $require;
    }

    /**
     * @param $comment
     * @return Doc
     */
    protected function doc($comment): Doc
    {
        return new Doc(trim($comment));
    }

    /**
     * @param Doc $comment
     * @param array $attributes
     * @return BlockComment
     */
    protected function blockComment(Doc $comment, $attributes = []): BlockComment
    {
        return new BlockComment($comment, $attributes);
    }

    /**
     * @param $variable
     * @param $expr
     * @param array $attributes
     * @return NestedAssign
     */
    protected function nestedAssign($variable, $expr, $attributes = []): NestedAssign
    {
        $assignment = new Assign(is_string($variable) ? $this->var($variable) : $variable, $expr);

        return new NestedAssign($assignment, $attributes);
    }

    /**
     * @param $variable
     * @param $expr
     * @param array $attributes
     * @return Assign
     */
    protected function inlineAssign($variable, $expr, $attributes = []): Assign
    {
        return new Assign(is_string($variable) ? $this->var($variable) : $variable, $expr, $attributes);
    }

    /**
     * @param Expr $expr
     * @param array $attributes
     * @return Include_
     */
    protected function requireOnce(Expr $expr, array $attributes = []): Include_
    {
        return new Include_($expr, 4, $attributes);
    }

    /**
     * @param $key
     * @param array $additionalArgs
     * @param array $attributes
     * @return FuncCall
     */
    protected function envFuncCall($key, array $additionalArgs = [], array $attributes = []): FuncCall
    {
        array_unshift($additionalArgs, $this->string($key));

        return $this->funcCall('env', $additionalArgs, $attributes);
    }

    /**
     * @param $functionName
     * @param array $args
     * @param array $attributes
     * @return FuncCall
     */
    protected function funcCall($functionName, array $args = [], $attributes = []): FuncCall
    {
        return new FuncCall(is_string($functionName) ? $this->name($functionName) : $functionName, $args, $attributes);
    }

    /**
     * @param $functionCall
     * @param array $attributes
     * @return \App\Builders\PHP\Parser\Extensions\Stmt\FuncCall
     */
    protected function funcCallStmt($functionCall, $attributes = []): \App\Builders\PHP\Parser\Extensions\Stmt\FuncCall
    {
        return new \App\Builders\PHP\Parser\Extensions\Stmt\FuncCall($functionCall, $attributes);
    }

    protected function new_($class, $args = [], $attributes = []): New_
    {
        if (is_string($class)) {
            $class = $this->name($class);
        }

        return new New_($class, $args, $attributes);
    }

    /**
     * @param $name
     * @return Name
     */
    protected function name($name): Name
    {
        return new Name($name);
    }

    /**
     * @param $value
     * @param bool $byRef
     * @param array $attributes
     * @return Arg
     */
    protected function arg($value, $byRef = false, array $attributes = []): Arg
    {
        if (is_string($value)) {
            $value = new String_($value);
        }

        return new Arg($value, $byRef, false, $attributes);
    }

    /**
     * @param Expr $expr
     * @param array $attributes
     * @return Return_
     */
    protected function return(Expr $expr, array $attributes = []): Return_
    {
        return new Return_($expr, $attributes);
    }

    /**
     * @param string $name
     * @param null $type
     * @param null $default
     * @param bool $byRef
     * @param bool $variadic
     * @param array $attributes
     * @return Param
     */
    protected function param(string $name, $type = null, $default = null, $byRef = false, $variadic = false, array $attributes = []): Param
    {
        return new Param($this->var($name), $default, $type, $byRef, $variadic, $attributes);
    }

    /**
     * @param $var
     * @param $name
     * @param array $attributes
     * @return PropertyFetch
     */
    protected function propFetch($var, $name, array $attributes = []): PropertyFetch
    {
        return new PropertyFetch($this->var($var), $name, $attributes);
    }

    /**
     * @param $funcCall
     * @param $name
     * @param array $attributes
     * @return PropertyFetch
     */
    protected function funcPropFetch($funcCall, $name, array $attributes = []): PropertyFetch
    {
        return new PropertyFetch($funcCall, $name, $attributes);
    }

    /**
     * @param $condition
     * @param array $statements
     * @param array $elseIfs
     * @param null $else
     * @param array $attributes
     * @return If_
     */
    protected function if($condition, array $statements = [], array $elseIfs = [], $else = null, array $attributes = []): If_
    {
        return new If_($condition, [
            'stmts' => $statements,
            'elseifs' => $elseIfs,
            'else' => $else
        ], $attributes);
    }

    /**
     * @param Expr $expr
     * @param Expr $valueVar
     * @param array $stmts
     * @return Foreach_
     */
    protected function forEach(Expr $expr, Expr $valueVar, array $stmts = []): Foreach_
    {
        return new Foreach_($expr, $valueVar, [
            'stmts' => $stmts,
        ]);
    }

    /**
     * @param $left
     * @param $right
     * @param array $attributes
     * @return BooleanAnd
     */
    protected function boolAnd($left, $right, array $attributes = []): BooleanAnd
    {
        return new BooleanAnd($left, $right, $attributes);
    }

    /**
     * @param $left
     * @param $right
     * @param array $attributes
     * @return Expr\BinaryOp\BooleanOr
     */
    protected function boolOr($left, $right, array $attributes = []): Expr\BinaryOp\BooleanOr
    {
        return new Expr\BinaryOp\BooleanOr($left, $right, $attributes);
    }

    /**
     * @param Expr $expr
     * @param array $attributes
     * @return Expression
     */
    protected function expression(Expr $expr, array $attributes = []): Expression
    {
        return new Expression($expr, $attributes);
    }

    /**
     * @param Node $node
     * @param array $attributes
     * @return ExpressionGroup
     */
    protected function expressionGroup(Node $node, array $attributes = []): ExpressionGroup
    {
        return new ExpressionGroup($node, $attributes);
    }

    /**
     * @param $className
     * @param $methodName
     * @param array $args
     * @param array $chainableMethodCalls
     * @param array $attributes
     * @return MethodCall|null
     */
    protected function chainableStaticCall($className, $methodName, $args = [], $chainableMethodCalls = [], array $attributes = []): ?MethodCall
    {
        $staticCall = $this->staticCall($className, $methodName, $args, $attributes);
        $finalMethodCall = null;

        foreach ($chainableMethodCalls as $methodCall) {
            $finalMethodCall = $this->methodCall($finalMethodCall ?: $staticCall, $methodCall[0], $methodCall[1]);
        }

        return $finalMethodCall;
    }

    /**
     * @param $call
     * @param array $attributes
     * @return ChainedStaticCall
     */
    protected function chainableStaticCallStmt($call, array $attributes = []): ChainedStaticCall
    {
        return new ChainedStaticCall($call, $attributes);
    }

    /**
     * @param $var
     * @param array $fetches
     * @param array $attributes
     * @return ChainedPropFetches
     */
    protected function chainedPropFetches($var, array $fetches, array $attributes = []): ChainedPropFetches
    {
        return new ChainedPropFetches(
            is_string($var) ? $this->var($var) : $var,
            $fetches,
            $attributes
        );
    }

    protected function chainableMethodCall($methodName, $args = []): array
    {
        return [
            $methodName,
            $args
        ];
    }

    /**
     * @param $class
     * @param $methodName
     * @param array $args
     * @param array $attributes
     * @return StaticCall
     */
    protected function staticCall($class, $methodName, array $args = [], array $attributes = []): StaticCall
    {
        return new StaticCall(
            is_string($class) ? $this->name($class) : $class,
            $methodName,
            $args,
            $attributes
        );
    }

    /**
     * @param StaticCall $staticCall
     * @param array $attributes
     * @return \App\Builders\PHP\Parser\Extensions\Stmt\StaticCall
     */
    protected function staticCallStmt(StaticCall $staticCall, array $attributes = []): \App\Builders\PHP\Parser\Extensions\Stmt\StaticCall
    {
        return new \App\Builders\PHP\Parser\Extensions\Stmt\StaticCall($staticCall, $attributes);
    }

    /**
     * @param array $calls
     * @param array $attributes
     * @return ChainedFuncCalls
     */
    protected function chainedFuncCalls(array $calls, array $attributes = []): ChainedFuncCalls
    {
        return new ChainedFuncCalls($calls, $attributes);
    }

    /**
     * @param ChainedFuncCalls $calls
     * @param array $attributes
     * @return ChainedFuncCall
     */
    protected function chainedFuncCallStmt(ChainedFuncCalls $calls, array $attributes = []): ChainedFuncCall
    {
        return new ChainedFuncCall($calls, $attributes);
    }

    /**
     * @param $var
     * @param $methodName
     * @param array $args
     * @return MethodCall
     */
    protected function methodCall($var, $methodName, array $args = []): MethodCall
    {
        if (is_string($var)) {
            $var = $this->var($var);
        }

        return new MethodCall($var, $methodName, $args);
    }

    /**
     * @param MethodCall $methodCall
     * @param array $args
     * @return \App\Builders\PHP\Parser\Extensions\Stmt\MethodCall
     */
    protected function methodCallStmt(MethodCall $methodCall, array $args = []): \App\Builders\PHP\Parser\Extensions\Stmt\MethodCall
    {
        return new \App\Builders\PHP\Parser\Extensions\Stmt\MethodCall($methodCall, $args);
    }

    /**
     * @param $items
     * @param array $attributes
     * @return Array_
     */
    protected function arr($items, array $attributes = []): Array_
    {
        return new Array_($items, $attributes);
    }

    /**
     * @param $methodName
     * @param array $args
     * @return array
     */
    protected function chainedMethodCall($methodName, $args = []): array
    {
        return [
            $methodName,
            $args
        ];
    }

    /**
     * @param array $params
     * @param array $stmts
     * @param bool $static
     * @return Closure
     *
     * @noinspection PhpDocSignatureInspection
     */
    protected function closure($params = [], $stmts = [], $uses = []): Closure
    {
        return new Closure([
            'params' => $params,
            'uses'   => $uses,
            'stmts'  => $stmts,
        ]);
    }

    /**
     * @param array $params
     * @param array $stmts
     * @param array $uses
     * @return Closure
     *
     * @noinspection PhpDocSignatureInspection
     */
    protected function staticClosure($params = [], $stmts = [], $uses = []): Closure
    {
        return new Closure([
            'params' => $params,
            'stmts'  => $stmts,
            'uses'   => $uses,
            'static' => true,
        ]);
    }
}
