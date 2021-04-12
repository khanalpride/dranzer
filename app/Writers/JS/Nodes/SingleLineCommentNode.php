<?php

namespace App\Writers\JS\Nodes;

use App\Writers\JS\Contracts\JSNode;

/**
 * Class SingleLineCommentNode
 * @package App\Writers\JS\Nodes
 */
class SingleLineCommentNode implements JSNode
{
    /**
     * @var JSNode
     */
    private JSNode $comment;

    /**
     * SingleLineCommentNode constructor.
     * @param JSNode $comment
     */
    public function __construct(JSNode $comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'singleLineComment';
    }

    /**
     * @return string
     */
    public function getNodeValue(): string
    {
        $comment = $this->comment;

        if ($comment instanceof StringNode) {
            $comment->setQuoted(false);
        }

        return '// ' . $comment->getNodeValue();
    }
}
