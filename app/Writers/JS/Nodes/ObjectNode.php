<?php

namespace App\Writers\JS\Nodes;

use App\Writers\JS\Contracts\JSNode;

/**
 * Class ObjectNode
 * @package App\Writers\JS\Nodes
 */
class ObjectNode implements JSNode
{
    /**
     * @var string
     */
    private string $name = 'object';
    /**
     * @var bool|mixed
     */
    private bool $multiLine;
    /**
     * @var bool
     */
    private bool $appendSemiColon = false;

    /**
     * @var array
     */
    private array $mappings;
    /**
     * @var bool|mixed
     */
    private bool $spacedSingleMapping;

    /**
     * ObjectNode constructor.
     * @param array $mappings
     * @param bool $multiLine
     * @param false $spacedSingleMapping
     */
    public function __construct(array $mappings = [], $multiLine = true, $spacedSingleMapping = false)
    {
        $this->mappings = $mappings;
        $this->multiLine = $multiLine;
        $this->spacedSingleMapping = $spacedSingleMapping;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getNodeValue(): string
    {
        $mappings = $this->mappings;

        $value = '{';

        if ($this->multiLine && count($mappings)) {
            $value .= PHP_EOL;
        }

        foreach ($mappings as $mapping) {
            if ($mapping instanceof KeyValueNode && $mapping->getKey() instanceof NOPNode) {
                $value .= PHP_EOL;
                continue;
            }

            $value .= ($this->multiLine ? '  ' : '') . $mapping->getNodeValue() . ($mapping instanceof KeyValueNode && $mapping->trailingComma() ? "," : '') . ($this->multiLine ? PHP_EOL : '');
        }

        /** @noinspection NestedTernaryOperatorInspection */
        $value .= '}' . ($this->appendSemiColon ? ';' . ($this->multiLine ? PHP_EOL : '') : ($this->multiLine ? PHP_EOL : ''));

        if (!count($mappings)) {
            $value = trim($value);
        } else if (count($mappings) === 1 && $this->spacedSingleMapping) {
            $value = $value[0] . ' ' . substr($value, 1, -1) . ' ' . substr($value, strlen($value) - 1);
        }

        return $value;
    }

    /**
     * @return array
     */
    public function getMappings(): array
    {
        return $this->mappings;
    }

    /**
     * @param array $mappings
     * @return ObjectNode
     */
    public function setMappings(array $mappings): ObjectNode
    {
        $this->mappings = $mappings;
        return $this;
    }


    /**
     * @return bool
     */
    public function appendSemiColon(): bool
    {
        return $this->appendSemiColon;
    }

    /**
     * @param bool $appendSemiColon
     * @return ObjectNode
     */
    public function setAppendSemiColon(bool $appendSemiColon): ObjectNode
    {
        $this->appendSemiColon = $appendSemiColon;
        return $this;
    }
}
