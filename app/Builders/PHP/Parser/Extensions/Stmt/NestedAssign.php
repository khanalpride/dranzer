<?php declare(strict_types=1);

namespace App\Builders\PHP\Parser\Extensions\Stmt;

use PhpParser\Node;

/**
 * Class MethodCall
 * @package App\Builders\PHP\Parser\Extensions\Stmt
 */
class NestedAssign extends Node\Stmt
{
    /**
     * @var Node\Expr\Assign
     */
    public Node\Expr\Assign $assignment;

    /**
     * Constructs a break node.
     *
     * @param Node\Expr\Assign $assignment
     * @param array $attributes
     */
    public function __construct(Node\Expr\Assign $assignment, $attributes = [])
    {
        parent::__construct($attributes);
        $this->assignment = $assignment;
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
        return 'Stmt_NestedAssign';
    }
}
