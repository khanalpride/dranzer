<?php declare(strict_types=1);

namespace App\Builders\PHP\Parser\Extensions\Stmt;

use PhpParser\Node;

/**
 * Class Comment
 * @package App\Builders\PHP\Parser\Extensions\Stmt
 */
class Comment extends Node\Stmt
{
    /**
     * @var string
     */
    public string $comment;

    /**
     * Constructs a break node.
     *
     * @param string $comment
     * @param array $attributes Additional attributes
     */
    public function __construct(string $comment = '', array $attributes = [])
    {
        parent::__construct($attributes);
        $this->comment = $comment;
    }

    /**
     * @return string[]
     */
    public function getSubNodeNames(): array
    {
        return ['comment'];
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'Stmt_Comment';
    }
}
