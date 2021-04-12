<?php

namespace App\Writers\JS\Statements;

use App\Writers\JS\Contracts\JSStatement;

/**
 * Class ImportStatement
 * @package App\Writers\JS\Statements
 */
class ImportStatement implements JSStatement
{
    /**
     * @var null
     */
    private $importAs;
    /**
     * @var null
     */
    private $moduleName;
    /**
     * @var false
     */
    private $exported;

    /**
     * ImportStatement constructor.
     *
     * @param null $moduleName
     * @param null $importAs
     * @param false $exported
     */
    public function __construct($moduleName = null, $importAs = null, $exported = false)
    {
        $this->importAs = $importAs;
        $this->moduleName = $moduleName;
        $this->exported = $exported;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'import';
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        if (!$this->importAs) {
            return "import '$this->moduleName';";
        }

        $importAs = $this->importAs;

        if (is_array($importAs)) {
            $importAs = collect($importAs)->join(', ');
        }

        if ($this->exported) {
            return 'import { ' . $importAs . ' }' . " from '$this->moduleName';";
        }

        return "import $this->importAs from '$this->moduleName';";
    }

    /**
     * @return null
     */
    public function getImportAs()
    {
        return $this->importAs;
    }

    /**
     * @param null $importAs
     */
    public function setImportAs($importAs): void
    {
        $this->importAs = $importAs;
    }

    /**
     * @return null
     */
    public function getModuleName()
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
