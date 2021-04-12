<?php declare(strict_types=1);

namespace App\Builders\PHP\Parser\Extensions\Expr;

use PhpParser\Node\Expr;

/**
 * Class ChainedFuncCalls
 * @package App\Builders\PHP\Parser\Extensions\Expr
 */
class ChainedFuncCalls extends Expr
{
    /**
     * @var array
     */
    public $calls = [];

    /**
     * ChainedFuncCalls constructor.
     * @param array $calls
     * @param array $attributes
     */
    public function __construct(array $calls, array $attributes = [])
    {
        $this->calls = $calls;
        parent::__construct($attributes);
    }

    /**
     * @return string[]
     */
    public function getSubNodeNames(): array
    {
        return ['chainedFuncCalls'];
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'ChainedFuncCalls';
    }
}
