<?php

namespace App\Builders\PHP;

use PhpParser\Builder\Property;

/**
 * Class PropertyBuilder
 * @package App\Builders\PHP
 */
class PropertyBuilder
{
    /**
     * @var Property
     */
    private Property $property;
    /**
     * @var
     */
    private $value;
    /**
     * @var
     */
    private $comment;
    /**
     * @var DocBlockBuilder
     */
    private DocBlockBuilder $docBuilder;

    /**
     * PropertyBuilder constructor.
     * @param $name
     */
    public function __construct($name)
    {
        $this->property = new Property($name);
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
     * @return PropertyBuilder
     */
    public function makePrivate(): PropertyBuilder
    {
        $this->getProperty()->makePrivate();
        return $this;
    }

    /**
     * @return Property
     */
    public function getProperty(): Property
    {
        return $this->property;
    }

    /**
     * @return PropertyBuilder
     */
    public function makePublic(): PropertyBuilder
    {
        $this->getProperty()->makePublic();
        return $this;
    }

    /**
     * @return PropertyBuilder
     */
    public function makeProtected(): PropertyBuilder
    {
        $this->getProperty()->makeProtected();
        return $this;
    }

    /**
     * @param $type
     * @return PropertyBuilder
     */
    public function setType($type): PropertyBuilder
    {
        $this->getProperty()->setType($type);
        return $this;
    }

    /**
     * @return PropertyBuilder
     */
    public function makeStatic(): PropertyBuilder
    {
        $this->getProperty()->makeStatic();
        return $this;
    }

    /**
     * @return Property
     */
    public function getConcrete(): Property
    {
        if (!$this->getDocBuilder()->isDry()) {
            $this->getProperty()->setDocComment($this->getDocBuilder()->getDocBlock());
        }
        if ($this->getValue() !== null) {
            $this->getProperty()->setDefault($this->getValue());
        }

        return $this->getProperty();
    }

    /**
     * @return DocBlockBuilder
     */
    public function getDocBuilder(): DocBlockBuilder
    {
        return $this->docBuilder;
    }

    /**
     * @return null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param null $value
     * @return PropertyBuilder
     */
    public function setValue($value): PropertyBuilder
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return null
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param null $comment
     * @return PropertyBuilder
     */
    public function setComment($comment): PropertyBuilder
    {
        $this->comment = $comment;
        return $this;
    }
}
