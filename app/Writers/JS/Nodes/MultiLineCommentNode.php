<?php

namespace App\Writers\JS\Nodes;

use Exception;
use App\Writers\JS\Contracts\JSNode;

/**
 * Class MultiLineCommentNode
 * @package App\Writers\JS\Nodes
 */
class MultiLineCommentNode implements JSNode
{
    /**
     * @var array
     */
    private array $comments;

    /**
     * MultiLineCommentNode constructor.
     * @param array $comments
     */
    public function __construct(array $comments)
    {
        $this->comments = $comments;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'multiLineComment';
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getNodeValue(): string
    {
        $mComment = '/**' . PHP_EOL;

        foreach ($this->comments as $comment) {
            if (is_string($comment)) {
                $comment = new StringNode($comment, false);
            }

            if (!$comment instanceof JSNode) {
                throw new Exception('Expected object of type JSNode for comment, got ' . gettype($comment));
            }

            if ($comment instanceof StringNode) {
                $comment->setQuoted(false);
            }

            $mComment .= ' * ' . $comment->getNodeValue() . PHP_EOL;
        }

        return $mComment . ' */';
    }
}
