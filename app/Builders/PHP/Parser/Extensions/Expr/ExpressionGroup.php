<?php declare(strict_types=1);

namespace App\Builders\PHP\Parser\Extensions\Expr;

use PhpParser\Node;
use PhpParser\Node\Expr;

/**
 * Class ExpressionGroup
 * @package App\Builders\PHP\Parser\Extensions\Expr
 */
class ExpressionGroup extends Expr
{
    /**
     * @var Node
     */
    public $node;

    /**
     * ExpressionGroup constructor.
     * @param Node $node
     * @param array $attributes
     */
    public function __construct(Node $node, array $attributes = [])
    {
        $this->node = $node;
        parent::__construct($attributes);
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
        return 'ExpressionGroup';
    }
}
