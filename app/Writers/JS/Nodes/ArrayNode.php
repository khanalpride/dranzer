<?php


namespace App\Writers\JS\Nodes;

use App\Writers\JS\Contracts\JSNode;
use Exception;

/**
 * Class ArrayNode
 * @package App\Writers\JS\Nodes
 */
class ArrayNode implements JSNode
{
    /**
     * @var array
     */
    private array $items;
    /**
     * @var bool
     */
    private bool $trailingComma;
    /**
     * @var bool
     */
    private bool $arrayTrailingComma = false;

    /**
     * ArrayNode constructor.
     * @param array $items
     * @param bool $trailingComma
     */
    public function __construct(array $items = [], bool $trailingComma = true)
    {
        $this->items = $items;
        $this->trailingComma = $trailingComma;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'array';
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getNodeValue(): string
    {
        $value = '[' . PHP_EOL;
        foreach ($this->items as $index => $item) {
            if (!$item instanceof JSNode) {
                throw new Exception("Expected JSNode, got " . gettype($item));
            }

            $trailingComma = ',';

            if (!$this->trailingComma && $index === count($this->items) - 1) {
                $trailingComma = '';
            }

            $value .= "\t" . $item->getNodeValue() . $trailingComma . PHP_EOL;
        }

        return $value . ']' . ($this->arrayTrailingComma ? ',' : '');
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    /**
     * @return bool
     */
    public function isTrailingComma(): bool
    {
        return $this->trailingComma;
    }

    /**
     * @param bool $trailingComma
     */
    public function setTrailingComma(bool $trailingComma): void
    {
        $this->trailingComma = $trailingComma;
    }

    /**
     * @return bool
     */
    public function hasArrayTrailingComma(): bool
    {
        return $this->arrayTrailingComma;
    }

    /**
     * @param bool $arrayTrailingComma
     */
    public function setArrayTrailingComma(bool $arrayTrailingComma): void
    {
        $this->arrayTrailingComma = $arrayTrailingComma;
    }
}
