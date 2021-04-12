<?php

namespace App\Writers\JS\Statements;

use App\Writers\JS\Contracts\JSNode;
use App\Writers\JS\Contracts\JSStatement;

/**
 * Class RequireStatement
 * @package App\Writers\JS\Statements
 */
class RequireStatement implements JSStatement
{
    /**
     * @var string
     */
    private $requireAs;
    /**
     * @var string
     */
    private $moduleName;
    /**
     * @var string
     */
    private $property;

    /**
     * RequireStatement constructor.
     * @param string|null $requireAs
     * @param null $moduleName
     * @param string|null $property
     */
    public function __construct(string $requireAs = null, $moduleName = null, string $property = null)
    {
        $this->requireAs = $requireAs;
        $this->moduleName = $moduleName;
        $this->property = $property;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'require';
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $moduleName = $this->moduleName instanceof JSNode ? $this->moduleName->getNodeValue() : "'" . $this->moduleName . "'";
        $requireStmt = "require($moduleName)";
        $propertyStmt = $this->property ? '.' . $this->property : '';

        if (!$this->requireAs) {
            return $requireStmt . ($propertyStmt ?: '') . ';';
        }

        return "const $this->requireAs = $requireStmt" . ($propertyStmt ?: '') . ';';
    }

    /**
     * @return string
     */
    public function getRequireAs(): string
    {
        return $this->requireAs;
    }

    /**
     * @param null $requireAs
     */
    public function setRequireAs($requireAs): void
    {
        $this->requireAs = $requireAs;
    }

    /**
     * @return string
     */
    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    /**
     * @param null $moduleName
     */
    public function setModuleName($moduleName): void
    {
        $this->moduleName = $moduleName;
    }
}
