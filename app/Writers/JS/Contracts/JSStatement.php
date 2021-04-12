<?php

namespace App\Writers\JS\Contracts;

/**
 * Interface JSStatement
 * @package App\Writers\JS\Contracts
 */
interface JSStatement
{
    /**
     * @return mixed
     */
    public function toString(): string;
}
