<?php declare(strict_types=1);

namespace App\Builders\PHP\Parser\Extensions\Stmt;

use PhpParser\Node;

/**
 * Class MethodCall
 * @package App\Builders\PHP\Parser\Extensions\Stmt
 */
class MethodCall extends Node\Stmt
{
    /**
     * @var Node\Expr\MethodCall
     */
    public Node\Expr\MethodCall $call;

    /**
     * Constructs a break node.
     *
     * @param Node\Expr\MethodCall $assignment
     * @param array $attributes
     */
    public function __construct(Node\Expr\MethodCall  $assignment, $attributes = [])
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
        return 'Stmt_MethodCall';
    }
}
