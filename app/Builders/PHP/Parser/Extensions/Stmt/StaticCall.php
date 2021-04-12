<?php declare(strict_types=1);

namespace App\Builders\PHP\Parser\Extensions\Stmt;

use PhpParser\Node;

/**
 * Class StaticCall
 * @package App\Builders\PHP\Parser\Extensions\Stmt
 */
class StaticCall extends Node\Stmt
{
    /**
     * @var Node\Expr\StaticCall
     */
    public Node\Expr\StaticCall $call;

    /**
     * Constructs a break node.
     *
     * @param Node\Expr\StaticCall $assignment
     * @param array $attributes
     */
    public function __construct(Node\Expr\StaticCall $assignment, $attributes = [])
    {
        parent::__construct($attributes);
        $this->call = $assignment;
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
        return 'Stmt_StaticCall';
    }
}
