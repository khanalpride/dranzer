<?php

namespace App\Writers\JS\Nodes;

use App\Writers\JS\Contracts\JSNode;

/**
 * Class KeyValueNode
 * @package App\Writers\JS\Nodes
 */
class KeyValueNode implements JSNode
{
    /*
     * @var null
     */
    /**
     * @var JSNode|null
     */
    private ?JSNode $key;

    /*
     * @var null
     */
    /**
     * @var JSNode|null
     */
    private ?JSNode $value;

    /**
     * @var bool
     */
    private bool $trailingComma;

    /**
     * KeyValueNode constructor.
     * @param JSNode|null $key
     * @param JSNode|null $value
     * @param bool $trailingComma
     */
    public function __construct(JSNode $key = null, JSNode $value = null, $trailingComma = true)
    {
        $this->key = $key;
        $this->value = $value;
        $this->trailingComma = $trailingComma;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return 'KeyValueNode';
    }

    /**
     * @return string
     */
    public function getNodeValue(): string
    {
        $value = $this->value->getNodeValue();

        if ($this->value instanceof NOPNode) {
            $value = '{}';
        }

        return "{$this->key->getNodeValue()}: $value";
    }

    /**
     * @return null
     */
    public function getKey(): ?JSNode
    {
        return $this->key;
    }

    /**
     * @param JSNode $key
     * @return KeyValueNode
     */
    public function setKey(JSNode $key): KeyValueNode
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return null
     */
    public function getValue(): ?JSNode
    {
        return $this->value;
    }

    /**
     * @param JSNode $value
     * @return KeyValueNode
     */
    public function setValue(JSNode $value): KeyValueNode
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function trailingComma(): bool
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

}
