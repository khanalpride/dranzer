<?php declare(strict_types=1);

namespace App\Builders\PHP\Parser\Extensions\Stmt;

use PhpParser\Node;
use App\Builders\PHP\Parser\Extensions\Expr\ChainedFuncCalls;

/**
 * Class ChainedFuncCall
 * @package App\Builders\PHP\Parser\Extensions\Stmt
 */
class ChainedFuncCall extends Node\Stmt
{
    /**
     * @var ChainedFuncCalls
     */
    public ChainedFuncCalls $chainedFunCalls;

    /**
     * Constructs a break node.
     *
     * @param ChainedFuncCalls $calls
     * @param array $attributes
     */
    public function __construct(ChainedFuncCalls $calls, $attributes = [])
    {
        parent::__construct($attributes);
        $this->chainedFunCalls = $calls;
    }

    /**
     * @return string[]
     */
    public function getSubNodeNames(): array
    {
        return [];
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'Stmt_ChainedFuncCall';
    }
}
