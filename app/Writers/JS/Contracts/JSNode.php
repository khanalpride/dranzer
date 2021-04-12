<?php

namespace App\Writers\JS\Contracts;

/**
 * Interface JSNode
 * @package App\Writers\JS\Contracts
 */
interface JSNode
{
    /**
     * @return string
     */
    public function getNodeName(): string;

    /**
     * @return string
     */
    public function getNodeValue(): string;
}
