<?php


namespace App\Writers\JS\Nodes;

use App\Writers\JS\Contracts\JSNode;
use Exception;

/**
 * Class InstantiationNode
 * @package App\Writers\JS\Nodes
 */
class InstantiationNode implements JSNode
{
    /**
     * @var string|null
     */
    private ?string $className;
    /**
     * @var array
     */
    private array $params;

    /**
     * InstantiationNode constructor.
     * @param string $className
     * @param array $params
     */
    public function __construct(string $className = '', array $params = [])
    {
        $this->className = $className;
        $this->params = $params;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'new';
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getNodeValue(): string
    {
        $params = [];

        foreach ($this->params as $param) {
            if (!$param instanceof JSNode) {
                throw new Exception("Expected JSNode, got " . gettype($param));
            }

            $params []= $param->getNodeValue();
        }

        $params = implode(',', $params);

        return "new $this->className($params)";
    }

    /**
     * @return null
     */
    public function getClassName(): ?string
    {
        return $this->className;
    }

    /**
     * @param null $className
     */
    public function setClassName($className): void
    {
        $this->className = $className;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param array $params
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }
}
