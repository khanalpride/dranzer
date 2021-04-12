<?php declare(strict_types=1);

namespace App\Builders\PHP\Parser\Extensions\Stmt;

use PhpParser\Node;
use PhpParser\Comment\Doc;

/**
 * Class BlockComment
 * @package App\Builders\PHP\Parser\Extensions\Stmt
 */
class BlockComment extends Node\Stmt
{
    /**
     * @var Doc
     */
    public Doc $doc;

    /**
     * Constructs a break node.
     *
     * @param Doc $doc
     * @param array $attributes Additional attributes
     */
    public function __construct(Doc $doc, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->doc = $doc;
    }

    /**
     * @return string[]
     */
    public function getSubNodeNames(): array
    {
        return ['doc'];
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'Stmt_DocBlock';
    }
}
