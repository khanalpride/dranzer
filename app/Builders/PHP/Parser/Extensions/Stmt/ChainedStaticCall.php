<?php declare(strict_types=1);

namespace App\Builders\PHP\Parser\Extensions\Stmt;

use PhpParser\Node;

/**
 * Class ChainedStaticCall
 * @package App\Builders\PHP\Parser\Extensions\Stmt
 */
class ChainedStaticCall extends Node\Stmt
{
    /**
     * @var
     */
    public $call;

    /**
     * Constructs a break node.
     *
     * @param $call
     * @param array $attributes
     */
    public function __construct($call, $attributes = [])
    {
        parent::__construct($attributes);
        $this->call = $call;
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
        return 'Stmt_ChainedStaticCall';
    }
}
