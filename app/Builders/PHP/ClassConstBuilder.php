<?php
/**
 * Created by PhpStorm.
 * User: Hassan
 * Date: 26/12/18
 * Time: 5:12 PM
 */

namespace App\Builders\PHP;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;

/**
 * Class ClassConstBuilder
 * @package App\Builders\PHP
 */
class ClassConstBuilder
{
    /**
     * @var ClassConst
     */
    private ClassConst $const;
    /**
     * @var DocBlockBuilder
     */
    private DocBlockBuilder $docBuilder;

    /**
     * ClassConstBuilder constructor.
     * @param $name
     */
    public function __construct($name)
    {
        $this->const = new ClassConst([$name]);
        $this->setDocBuilder(new DocBlockBuilder());
    }

    /**
     * @param $docBuilder
     * @return void
     */
    private function setDocBuilder($docBuilder): void
    {
        $this->docBuilder = $docBuilder;
    }

    /**
     * @param Expr $value
     * @return $this
     */
    public function setValue(Expr $value): ClassConstBuilder
    {
        $this->getConst()->consts[0]->expr = $value;

        return $this;
    }

    /**
     * @return ClassConst
     */
    public function getConst(): ClassConst
    {
        return $this->const;
    }

    /**
     * @return ClassConst
     */
    public function getConcrete(): ClassConst
    {
        if (!$this->getDocBuilder()->isDry()) {
            $this->getConst()->setDocComment(
                $this->getDocBuilder()->getDocBlock()
            );
        }
        return $this->getConst();
    }

    /**
     * @return $this
     */
    public function makePublic(): ClassConstBuilder
    {
        $this->getConcrete()->flags = Class_::MODIFIER_PUBLIC;

        return $this;
    }

    /**
     * @return $this
     */
    public function makeProtected(): ClassConstBuilder
    {
        $this->getConcrete()->flags = Class_::MODIFIER_PROTECTED;

        return $this;
    }

    /**
     * @return $this
     */
    public function makeStatic(): ClassConstBuilder
    {
        $this->getConcrete()->flags = Class_::MODIFIER_STATIC;

        return $this;
    }

    /**
     * @return DocBlockBuilder
     */
    public function getDocBuilder(): DocBlockBuilder
    {
        return $this->docBuilder;
    }
}
