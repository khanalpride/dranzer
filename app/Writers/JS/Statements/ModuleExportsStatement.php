<?php

namespace App\Writers\JS\Statements;

use Exception;
use App\Writers\JS\Contracts\JSNode;
use App\Writers\JS\Contracts\JSStatement;

/**
 * Class ModuleExportsStatement
 * @package App\Writers\JS\Statements
 */
class ModuleExportsStatement implements JSStatement
{
    /**
     * @var JSNode
     */
    private JSNode $exportedNode;

    /**
     * ModuleExportsStatement constructor.
     * @param JSNode $exportedNode
     */
    public function __construct(JSNode $exportedNode)
    {
        $this->exportedNode = $exportedNode;
    }

    /**
     * @throws Exception
     */
    public function toString(): string
    {
        return 'module.exports = ' . $this->exportedNode->getNodeValue() . ';';
    }
}
