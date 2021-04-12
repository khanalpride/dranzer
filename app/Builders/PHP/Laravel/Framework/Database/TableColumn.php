<?php

namespace App\Builders\PHP\Laravel\Framework\Database;

/**
 * Class TableColumn
 * @package App\Builders\PHP\Laravel\Framework\Database
 */
class TableColumn
{
    /**
     * @var
     */
    private $id;
    /**
     * @var string
     */
    private string $name;
    /**
     * @var string
     */
    private string $type;
    // Attributes
    /**
     * @var bool
     */
    private bool $hidden;
    /**
     * @var bool
     */
    private bool $nullable;
    /**
     * @var bool
     */
    private bool $unique;
    /**
     * @var bool
     */
    private bool $unsigned;
    /**
     * @var bool
     */
    private bool $fillable;
    /**
     * @var bool
     */
    private bool $autoIncrement;
    /**
     * @var int|null
     */
    private ?int $length;
    /**
     * @var array
     */
    private array $rawAttributes;

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return !empty($this->getName()) && !empty($this->getType());
    }

    /**
     * @return bool
     */
    public function isInvalid(): bool
    {
        return !$this->isValid();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return TableColumn
     */
    public function setName(string $name): TableColumn
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return TableColumn
     */
    public function setType(string $type): TableColumn
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAutoIncrementing(): bool
    {
        return $this->autoIncrement;
    }

    /**
     * @param bool $autoIncrement
     * @return TableColumn
     */
    public function setAutoIncrement(bool $autoIncrement): TableColumn
    {
        $this->autoIncrement = $autoIncrement;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    /**
     * @param bool $unsigned
     * @return TableColumn
     */
    public function setUnsigned(bool $unsigned): TableColumn
    {
        $this->unsigned = $unsigned;

        return $this;
    }

    /**
     * @return bool
     */
    public function isFillable(): bool
    {
        return $this->fillable;
    }

    /**
     * @param bool $fillable
     * @return TableColumn
     */
    public function setFillable(bool $fillable): TableColumn
    {
        $this->fillable = $fillable;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * @param bool $hidden
     * @return TableColumn
     */
    public function setHidden(bool $hidden): TableColumn
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * @return array
     */
    public function getRawAttributes(): array
    {
        return $this->rawAttributes;
    }

    /**
     * @param array $rawAttributes
     * @return TableColumn
     */
    public function setRawAttributes(array $rawAttributes): TableColumn
    {
        $this->rawAttributes = $rawAttributes;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUnique(): bool
    {
        return $this->unique;
    }

    /**
     * @param bool $unique
     * @return TableColumn
     */
    public function setUnique(bool $unique): TableColumn
    {
        $this->unique = $unique;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * @param bool $nullable
     * @return TableColumn
     */
    public function setNullable(bool $nullable): TableColumn
    {
        $this->nullable = $nullable;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getLength(): ?int
    {
        $length = $this->length;

        if (!$length || !is_numeric($length)) {
            return null;
        }

        return (int) trim($length);
    }

    /**
     * @param int|null $length
     * @return TableColumn
     */
    public function setLength(?int $length): TableColumn
    {
        $this->length = $length;

        return $this;
    }

    /**
     *
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     * @return TableColumn
     */
    public function setId($id): TableColumn
    {
        $this->id = $id;

        return $this;
    }
}
