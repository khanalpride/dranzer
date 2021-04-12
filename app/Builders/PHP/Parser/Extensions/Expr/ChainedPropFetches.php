<?php declare(strict_types=1);

namespace App\Builders\PHP\Parser\Extensions\Expr;

use PhpParser\Node;

/**
 * Class ChainedPropFetches
 * @package App\Builders\PHP\Parser\Extensions\Stmt
 */
class ChainedPropFetches extends Node\Expr
{
    /**
     * @var array
     */
    public array $fetches;
    /**
     * @var Node
     */
    public Node $var;

    /**
     * Constructs a break node.
     *
     * @param Node $var
     * @param array $fetches
     * @param array $attributes
     */
    public function __construct(Node $var, array $fetches, $attributes = [])
    {
        parent::__construct($attributes);
        $this->var = $var;
        $this->fetches = $fetches;
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
        return 'Expr_ChainedPropFetches';
    }
}
