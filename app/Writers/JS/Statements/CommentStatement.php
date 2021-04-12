<?php

namespace App\Writers\JS\Statements;

use Exception;
use App\Writers\JS\Contracts\JSStatement;
use App\Writers\JS\Nodes\MultiLineCommentNode;
use App\Writers\JS\Nodes\SingleLineCommentNode;

/**
 * Class CommentStatement
 * @package App\Writers\JS\Statements
 */
class CommentStatement implements JSStatement
{
    /**
     * @var array
     */
    private array $comments;
    /**
     * @var bool|mixed
     */
    private bool $singleLine;

    /**
     * CommentStatement constructor.
     * @param array $comments
     * @param bool $singleLine
     */
    public function __construct(array $comments, $singleLine = true)
    {
        $this->comments = $comments;
        $this->singleLine = $singleLine;
    }

    /**
     * @throws Exception
     */
    public function toString(): string
    {
        if (!count($this->comments)) {
            return '';
        }

        return $this->singleLine ?
            (new SingleLineCommentNode($this->comments[0]))->getNodeValue() :
            (new MultiLineCommentNode($this->comments))->getNodeValue();
    }
}
