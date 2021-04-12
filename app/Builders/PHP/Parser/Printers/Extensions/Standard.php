<?php

/** @noinspection PhpUnused */

/** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUndefinedMethodInspection */

/** @noinspection SpellCheckingInspection */

namespace App\Builders\PHP\Parser\Printers\Extensions;

use PhpParser\Node;
use PhpParser\Comment;
use PhpParser\Node\Stmt;
use PhpParser\Comment\Doc;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Include_;
use App\Builders\PHP\Parser\Extensions\Stmt\FuncCall;
use App\Builders\PHP\Parser\Extensions\Stmt\StaticCall;
use App\Builders\PHP\Parser\Extensions\Stmt\BlockComment;
use App\Builders\PHP\Parser\Extensions\Stmt\NestedAssign;
use App\Builders\PHP\Parser\Extensions\Expr\ExpressionGroup;
use App\Builders\PHP\Parser\Extensions\Stmt\ChainedFuncCall;
use App\Builders\PHP\Parser\Extensions\Expr\ChainedFuncCalls;
use App\Builders\PHP\Parser\Extensions\Expr\ChainedPropFetches;
use App\Builders\PHP\Parser\Extensions\Stmt\MethodCall as MethodCallStmt;

/**
 * Class Standard
 * @package App\Builders\PHP\Parser\Printers\Extensions
 */
class Standard extends \PhpParser\PrettyPrinter\Standard
{
    /**
     * @param Stmt\Class_ $node
     * @param $afterClassToken
     * @return string
     */
    protected function pClassCommon(Stmt\Class_ $node, $afterClassToken): string
    {
        if (!$node->getComments()) {
            return PHP_EOL . parent::pClassCommon(
                    $node,
                    $afterClassToken
                );
        }

        return parent::pClassCommon(
            $node,
            $afterClassToken
        );
    }

    /**
     * @param Include_ $node
     * @return string
     */
    protected function pExpr_Include(Include_ $node): string
    {
        static $map = [
            Include_::TYPE_INCLUDE      => 'include',
            Include_::TYPE_INCLUDE_ONCE => 'include_once',
            Include_::TYPE_REQUIRE      => 'require',
            Include_::TYPE_REQUIRE_ONCE => 'require_once',
        ];

        $ret = $map[$node->type] . ' ' . $this->p($node->expr);

        if ($node->hasAttribute('comments')) {
            $ret = $node->getAttribute('comments')[0]->getReformattedText() . PHP_EOL . $ret;
        }

        return $ret;
    }

    /**
     * @param string $string
     * @return string
     */
    protected function pSingleQuotedString(string $string): string
    {
        return '\'' . $string . '\'';
    }

    /**
     * @param Stmt\TraitUse $node
     * @return string
     */
    protected function pStmt_TraitUse(Stmt\TraitUse $node): string
    {
        return 'use ' . $this->pCommaSeparated($node->traits)
            . (empty($node->adaptations)
                ? ';' . $this->nl
                : ' {' . $this->pStmts($node->adaptations) . $this->nl . '}');
    }

    /**
     * Pretty prints an array of nodes (statements) and indents them optionally.
     *
     * @param Node[] $nodes Array of nodes
     * @param bool $indent Whether to indent the printed nodes
     *
     * @return string Pretty printed statements
     */
    protected function pStmts(array $nodes, bool $indent = true): string
    {
        if ($indent) {
            $this->indent();
        }

        $manuallyTerminateExpressions = [
            Include_::class,
        ];

        $result = '';
        foreach ($nodes as $node) {
            $comments = $node->getComments();
            if ($comments) {
                $result .= $this->nl . $this->pComments($comments);
                if ($node instanceof Stmt\Nop) {
                    continue;
                }
            }

            $result .= $this->nl . $this->p($node);

            foreach ($manuallyTerminateExpressions as $terminatable) {
                if ($node instanceof $terminatable) {
                    $result .= ';';
                }
            }
        }

        if ($indent) {
            $this->outdent();
        }

        return $result;
    }

    /**
     * @param Stmt\Property $node
     * @return string
     */
    protected function pStmt_Property(Stmt\Property $node): string
    {
        return parent::pStmt_Property($node) . PHP_EOL;
    }

    /**
     * @param Stmt\ClassConst $node
     * @return string
     */
    protected function pStmt_ClassConst(Stmt\ClassConst $node): string
    {
        return parent::pStmt_ClassConst($node) . PHP_EOL;
    }

    /**
     * @param NestedAssign $node
     * @return string
     */
    protected function pStmt_NestedAssign(NestedAssign $node): string
    {
        return $this->p($node->assignment) . ';';
    }

    /**
     * @param ChainedPropFetches $node
     * @return string
     */
    protected function pExpr_ChainedPropFetches(ChainedPropFetches $node): string
    {
        $terminate = $node->getAttribute('terminate') ?? true;
        return $this->p($node->var) . '->' .
            implode(
                '->',
                array_map(
                    fn ($fetch) => $this->p($fetch),
                    $node->fetches
                )
            ) . ($terminate ? ';' : '');
    }

    /**
     * @return string
     */
    protected function pNop(): string
    {
        return '';
    }

    /**
     * @param StaticCall $node
     * @return string
     */
    protected function pStmt_StaticCall(StaticCall $node): string
    {
        return $this->pExpr_StaticCall($node->call) . ';';
    }

    /**
     * Method call as a statement; it terminates with a semi-colon.
     *
     * @param MethodCallStmt $node
     * @return string
     */
    protected function pStmt_MethodCall(MethodCallStmt $node): string
    {
        return $this->pExpr_MethodCall($node->call) . ';';
    }

    /**
     * @param FuncCall $node
     * @return string
     */
    protected function pStmt_FuncCall(FuncCall $node): string
    {
        return $this->pExpr_FuncCall($node->call) . ';';
    }

    /**
     * @param ChainedFuncCall $call
     * @return string
     */
    protected function pStmt_ChainedFuncCall(ChainedFuncCall $call): string
    {
        return $this->pChainedFuncCalls($call->chainedFunCalls) . ';';
    }

    /**
     * @param ChainedFuncCalls $chainedFuncCalls
     *
     * @return string
     */
    protected function pChainedFuncCalls(ChainedFuncCalls $chainedFuncCalls): string
    {
        $calls = $chainedFuncCalls->calls;

        $output = '';

        foreach ($calls as $call) {
            $output .= $this->p($call) . '->';
        }

        return rtrim(
            $output,
            '->'
        );
    }

    /**
     * @param $call
     * @return string
     */
    protected function pStmt_ChainedStaticCall($call): string
    {
        return $this->pExpr_MethodCall($call->call) . ';';
    }

    /**
     * @param ExpressionGroup $expressionGroup
     * @return string
     */
    protected function pExpressionGroup(ExpressionGroup $expressionGroup): string
    {
        return '(' . $this->p($expressionGroup->node) . ')';
    }

    /**
     * @param BlockComment $node
     * @return string
     * @noinspection PhpUnused
     */
    protected function pStmt_DocBlock(BlockComment $node): string
    {
        $indentationLevel = $node->getAttribute('indentationLevel');

        $appendNewLineAtEnd = true;

        if ($node->hasAttribute('appendNewLineAtEnd')) {
            $appendNewLineAtEnd = $node->getAttribute('appendNewLineAtEnd');
        }

        if ($indentationLevel) {
            $this->setIndentLevel($indentationLevel);
        }

        return PHP_EOL . $this->pComments([$node->doc]) . ($appendNewLineAtEnd === true ? PHP_EOL : '');
    }

    /**
     * @param $node
     * @return string
     */
    protected function pStmt_Comment($node): string
    {
        return trim("// " . $node->comment);
    }

    /**
     * @param Stmt\ClassMethod $node
     * @return string
     */
    protected function pStmt_ClassMethod(Stmt\ClassMethod $node): string
    {
        return parent::pStmt_ClassMethod($node) . PHP_EOL;
    }

    /**
     * @param Stmt\Return_ $node
     * @return string
     */
    protected function pStmt_Return(Stmt\Return_ $node): string
    {
        if ($node->getAttribute('prependNewline')) {
            return PHP_EOL . parent::pStmt_Return($node);
        }

        return parent::pStmt_Return($node);
    }

    /**
     * @param Array_ $node
     * @return string
     */
    protected function pExpr_Array(Array_ $node): string
    {
        $syntax = $node->getAttribute(
            'kind',
            $this->options['shortArraySyntax'] ? Array_::KIND_SHORT : Array_::KIND_LONG
        );

        if ($syntax === Array_::KIND_SHORT) {
            if (count($node->items) > 0) {
                return '[' . $this->pMaybeMultiline(
                        $node->items,
                        false,
                        true
                    ) . $this->nl . ']';
            }
            return '[' . $this->pMaybeMultiline($node->items) . ']';
        }

        return 'array(' . $this->pMaybeMultiline(
                $node->items,
                true
            ) . ')';
    }

    /**
     * @param array $nodes
     * @param bool $trailingComma
     * @param bool $forceMultiline
     * @return string
     */
    protected function pMaybeMultiline(array $nodes, $trailingComma = false, $forceMultiline = false): ?string
    {
        if (!$this->hasNodeWithComments($nodes)) {
            if ($forceMultiline) {
                return $this->pCommaSeparatedMultiline(
                    $nodes,
                    $trailingComma
                );
            }
            return $this->pCommaSeparated($nodes);
        }

        return $this->pCommaSeparatedMultiline(
                $nodes,
                $trailingComma
            ) . $this->nl;
    }

    /**
     * Pretty prints a comma-separated list of nodes in multiline style, including comments.
     *
     * The result includes a leading newline and one level of indentation (same as pStmts).
     *
     * @param Node[] $nodes Array of Nodes to be printed
     * @param bool $trailingComma Whether to use a trailing comma
     *
     * @return string Comma separated pretty printed nodes in multiline style
     */
    protected function pCommaSeparatedMultiline(array $nodes, bool $trailingComma): string
    {
        $this->indent();

        $result = '';

        $lastIdx = count($nodes) - 1;

        foreach ($nodes as $idx => $node) {
            if ($node instanceof Stmt\Nop) {
                continue;
            }
            if ($node !== null) {
                $comments = $node->getComments();
                $shouldNotAppendNewline = false;
                if ($comments) {
                    $doc = $comments[0];
                    /** @noinspection MissingIssetImplementationInspection */
                    if (($doc instanceof Doc) && isset($doc->commentAttributes) && array_key_exists(
                            'doNotAppendNewline',
                            $doc->commentAttributes
                        )) {
                        $shouldNotAppendNewline = $doc->commentAttributes['doNotAppendNewline'] === true;
                    }
                    if ($shouldNotAppendNewline) {
                        $result .= ($idx !== 0 ? $this->nl : '') . $this->nl . $this->pComments($comments);
                    } else {
                        $result .= ($idx !== 0 ? $this->nl : '') . $this->nl . $this->pComments($comments) . $this->nl;
                    }
                }

                $result .= $this->nl . $this->p($node);
            } else {
                $result .= $this->nl;
            }
            if ($trailingComma || $idx !== $lastIdx) {
                $result .= ',';
            }
        }

        $this->outdent();
        return $result;
    }

    /**
     * Prints reformatted text of the passed comments.
     *
     * @param Comment[] $comments List of comments
     *
     * @return string Reformatted text of comments
     */
    protected function pComments(array $comments): string
    {
        $formattedComments = [];

        foreach ($comments as $comment) {
            $prependNewline = false;

            /** @noinspection MissingIssetImplementationInspection */
            if (($comment instanceof Doc) && isset($comment->commentAttributes) && array_key_exists(
                    'prependNewline',
                    $comment->commentAttributes
                )) {
                $prependNewline = $comment->commentAttributes['prependNewline'] === true;
            }

            $formattedComment = str_replace("\n", $this->nl, $comment->getReformattedText());

            if ($prependNewline) {
                $formattedComments[] = $this->nl . $formattedComment;
            } else {
                $formattedComments[] = $formattedComment;
            }
        }

        return implode($this->nl, $formattedComments);
    }
}
