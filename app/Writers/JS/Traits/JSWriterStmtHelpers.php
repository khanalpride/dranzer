<?php

namespace App\Writers\JS\Traits;

use App\Writers\JS\Contracts\JSNode;
use App\Writers\JS\Nodes\FuncCallNode;
use App\Writers\JS\Nodes\ObjectNode;
use App\Writers\JS\Nodes\RawNode;
use App\Writers\JS\Nodes\VarNode;
use App\Writers\JS\Statements\AssignmentStatement;
use App\Writers\JS\Statements\CommentStatement;
use App\Writers\JS\Statements\FuncCallStatement;
use App\Writers\JS\Statements\ImportStatement;
use App\Writers\JS\Statements\ModuleExportsStatement;
use App\Writers\JS\Statements\NOPStatement;
use App\Writers\JS\Statements\ObjectStatement;
use App\Writers\JS\Statements\RawStatement;
use App\Writers\JS\Statements\RequireStatement;
use App\Writers\JS\Statements\ReturnStatement;

/**
 * Trait JSWriterStmtHelpers
 * @package App\Writers\JS\Traits
 */
trait JSWriterStmtHelpers
{
    /**
     * @param $module
     * @param null $as
     * @param null $property
     * @return RequireStatement
     */
    public function require($module, $as = null, $property = null): RequireStatement
    {
        return new RequireStatement($as, $module, $property);
    }

    /**
     * @param $module
     * @param null $as
     * @param bool $exported
     *
     * @return ImportStatement
     */
    public function import($module, $as = null, $exported = false): ImportStatement
    {
        return new ImportStatement($module, $as, $exported);
    }

    /**
     * @param string $varName
     * @param JSNode $expression
     * @param bool $asConst
     * @return AssignmentStatement
     */
    public function assign(string $varName, JSNode $expression, bool $asConst = true): AssignmentStatement
    {
        return new AssignmentStatement(new VarNode($varName), $expression, $asConst);
    }

    /**
     * @param ObjectNode $node
     * @return ObjectStatement
     */
    public function objectStmt(ObjectNode $node): ObjectStatement
    {
        return new ObjectStatement($node);
    }

    /**
     * @param FuncCallNode $funcCall
     * @return FuncCallStatement
     */
    public function funcCallStmt(FuncCallNode $funcCall): FuncCallStatement
    {
        return new FuncCallStatement($funcCall);
    }

    /**
     * @param JSNode $exportedNode
     * @return ModuleExportsStatement
     */
    public function modExports(JSNode $exportedNode): ModuleExportsStatement
    {
        return new ModuleExportsStatement($exportedNode);
    }

    /**
     * @param $comments
     * @return CommentStatement
     */
    public function comment($comments): CommentStatement
    {
        $multiLine = false;

        if (is_string($comments)) {
            $comments = [$this->string($comments)];
        }

        if (is_array($comments) && count($comments) > 1) {
            $multiLine = true;
        }

        return new CommentStatement($comments, !$multiLine);
    }

    /**
     * @param JSNode $expression
     * @return ReturnStatement
     */
    public function ret(JSNode $expression): ReturnStatement
    {
        return new ReturnStatement($expression);
    }

    /**
     * @param RawNode $node
     * @return RawStatement
     */
    public function rawStmt(RawNode $node): RawStatement
    {
        return new RawStatement($node);
    }

    /**
     * @return NOPStatement
     */
    public function nopStmt(): NOPStatement
    {
        return new NOPStatement;
    }
}
