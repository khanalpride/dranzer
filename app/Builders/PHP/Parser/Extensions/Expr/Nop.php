<?php declare(strict_types=1);

namespace App\Builders\PHP\Parser\Extensions\Expr;

use PhpParser\Node\Expr;

/**
 * Class Nop
 * @package App\Builders\PHP\Parser\Extensions\Expr
 */
class Nop extends Expr
{
    /**
     * @return string[]
     */
    public function getSubNodeNames(): array
    {
        return ['nop'];
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'Nop';
    }
}
