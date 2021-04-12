<?php declare(strict_types=1);

namespace App\Builders\PHP\Parser\Extensions\Stmt;

use PhpParser\Node;

/**
 * Class MethodCall
 * @package App\Builders\PHP\Parser\Extensions\Stmt
 */
class FuncCall extends Node\Stmt
{
    /**
     * @var Node\Expr\FuncCall
     */
    public Node\Expr\FuncCall $call;

    /**
     * Constructs a break node.
     *
     * @param Node\Expr\FuncCall $assignment
     * @param array $attributes
     */
    public function __construct(Node\Expr\FuncCall $assignment, $attributes = [])
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
        return 'Stmt_FuncCall';
    }
}
