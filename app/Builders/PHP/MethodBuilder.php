<?php

/** @noinspection PhpPrivateFieldCanBeLocalVariableInspection */

namespace App\Builders\PHP;

use App\Builders\PHP\Parser\Extensions\Stmt\Comment;
use PhpParser\Builder\Method;
use PhpParser\Node\Param;

/**
 * Class MethodBuilder
 * @package App\Builders\PHP
 */
class MethodBuilder
{
    /**
     * @var Method
     */
    private Method $method;
    /**
     * @var string
     */
    private string $name;
    /**
     * @var null
     */
    private $returnType;
    /**
     * @var array
     */
    private array $parameters = [];
    /**
     * @var array
     */
    private array $statements = [];
    /**
     * @var DocBlockBuilder
     */
    private DocBlockBuilder $docBuilder;
    /**
     * @var null
     */
    private $comment;
    /**
     * @var bool
     */
    private bool $addEmptyCommentForEmptyMethod = true;
    /**
     * @var bool
     */
    private bool $hasVisibilityModifier = false;
    /**
     * @var bool
     */
    private bool $enableInspectionSuppression = true;
    /**
     * @var array
     */
    private array $suppressedInspections = [];

    /**
     * MethodBuilder constructor.
     * @param $methodName
     */
    public function __construct($methodName)
    {
        $this->method = new Method($methodName);

        $this->setDocBuilder(new DocBlockBuilder);
        $this->name = $methodName;
    }

    /**
     * @param DocBlockBuilder $docBuilder
     * @return void
     */
    private function setDocBuilder(DocBlockBuilder $docBuilder): void
    {
        $this->docBuilder = $docBuilder;
    }

    /**
     * @return Method
     */
    public function getConcrete(): Method
    {
        if ($this->shouldAddEmptyCommentForEmptyMethod() && count($this->getStatements()) < 1) {
            $this->addStatement(new Comment());
        }

        $this->getMethod()->addParams($this->getParameters());

        $this->getMethod()->addStmts($this->getStatements());

        $this->getDocBuilder()->addParameters($this->getParameters());

        if (!$this->getDocBuilder()->isDry()) {
            $this->getMethod()->setDocComment($this->getDocBuilder()->getDocBlock());
        }

        if (!$this->hasVisibilityModifier()) {
            $this->getMethod()->makePublic();
        }

        if ($this->getReturnType()) {
            $this->getMethod()->setReturnType($this->getReturnType());
        }

        return $this->getMethod();
    }

    /**
     * @return bool
     */
    public function shouldAddEmptyCommentForEmptyMethod(): bool
    {
        return $this->addEmptyCommentForEmptyMethod;
    }

    /**
     * @return array
     */
    public function getStatements(): array
    {
        return $this->statements;
    }

    /**
     * @param $statement
     * @return MethodBuilder
     */
    public function addStatement($statement): MethodBuilder
    {
        $this->statements[] = $statement;
        return $this;
    }

    /**
     * @param array $statements
     * @return MethodBuilder
     */
    public function setStatements(array $statements): MethodBuilder
    {
        $this->statements = $statements;
        return $this;
    }

    /**
     * @return Method
     */
    public function getMethod(): Method
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return DocBlockBuilder
     */
    public function getDocBuilder(): DocBlockBuilder
    {
        return $this->docBuilder;
    }

    /**
     * @return bool
     */
    private function hasVisibilityModifier(): bool
    {
        return $this->hasVisibilityModifier;
    }

    /**
     * @return MethodBuilder
     */
    public function makePrivate(): MethodBuilder
    {
        $this->getMethod()->makePrivate();
        $this->setHasVisibilityModifier();
        return $this;
    }

    /**
     * @return void
     */
    private function setHasVisibilityModifier(): void
    {
        $this->hasVisibilityModifier = true;
    }

    /**
     * @return MethodBuilder
     */
    public function makePublic(): MethodBuilder
    {
        $this->getMethod()->makePublic();
        $this->setHasVisibilityModifier();
        return $this;
    }

    /**
     * @return MethodBuilder
     */
    public function makeProtected(): MethodBuilder
    {
        $this->getMethod()->makeProtected();
        $this->setHasVisibilityModifier();
        return $this;
    }

    /**
     * @return MethodBuilder
     */
    public function makeStatic(): MethodBuilder
    {
        $this->getMethod()->makeStatic();
        $this->setHasVisibilityModifier();
        return $this;
    }

    /**
     * @param array $parameters
     * @return MethodBuilder
     */
    public function addParameters(array $parameters): MethodBuilder
    {
        foreach ($parameters as $parameter) {
            $this->addParameter($parameter);
        }
        return $this;
    }

    /**
     * @param Param $parameter
     * @return MethodBuilder
     */
    public function addParameter(Param $parameter): MethodBuilder
    {
        $this->parameters[] = $parameter;
        return $this;
    }

    /**
     * @param array $statements
     * @return MethodBuilder
     */
    public function addStatements(array $statements): MethodBuilder
    {
        foreach ($statements as $statement) {
            $this->addStatement($statement);
        }

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
     * @param $comment
     * @return MethodBuilder
     */
    public function setComment($comment): MethodBuilder
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return null
     */
    public function getReturnType()
    {
        return $this->returnType;
    }

    /**
     * @param $returnType
     * @return MethodBuilder
     */
    public function setReturnType($returnType): MethodBuilder
    {
        $this->returnType = $returnType;

        return $this;
    }

    /**
     * @param bool $addEmptyCommentForEmptyMethod
     * @return MethodBuilder
     */
    public function setAddEmptyCommentForEmptyMethod(bool $addEmptyCommentForEmptyMethod): MethodBuilder
    {
        $this->addEmptyCommentForEmptyMethod = $addEmptyCommentForEmptyMethod;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isInspectionSuppressionEnabled(): bool
    {
        return $this->enableInspectionSuppression;
    }

    /**
     * @param $inspection
     * @return bool
     */
    public function isInspectionSuppressed($inspection): bool
    {
        return in_array($inspection, $this->suppressedInspections, true);
    }

    /**
     * @return MethodBuilder
     */
    public function enableInspectionSuppression(): MethodBuilder
    {
        $this->enableInspectionSuppression = true;

        return $this;
    }

    /**
     * @return MethodBuilder
     */
    public function disableInspectionSuppression(): MethodBuilder
    {
        $this->enableInspectionSuppression = false;

        return $this;
    }

    /**
     * @return array
     */
    public function getSuppressedInspections(): array
    {
        return $this->suppressedInspections;
    }

    /**
     * @param array $suppressedInspections
     * @return MethodBuilder
     */
    public function setSuppressedInspections(array $suppressedInspections): MethodBuilder
    {
        $this->suppressedInspections = $suppressedInspections;

        return $this;
    }

    /**
     * @param $inspection
     * @return MethodBuilder
     */
    public function addSuppressedInspection($inspection): MethodBuilder
    {
        if ($this->isInspectionSuppressed($inspection)) {
            return $this;
        }

        $this->suppressedInspections[] = $inspection;

        return $this;
    }

    /**
     * @param array $suppressedInspections
     * @return MethodBuilder
     */
    public function addSuppressedInspections(array $suppressedInspections): MethodBuilder
    {
        foreach ($suppressedInspections as $suppressedInspection) {
            $this->addSuppressedInspection($suppressedInspection);
        }

        return $this;
    }
}
