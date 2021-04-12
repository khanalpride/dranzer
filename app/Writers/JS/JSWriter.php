<?php

namespace App\Writers\JS;

use App\Writers\JS\Contracts\JSStatement;
use RuntimeException;

/**
 * Class JSWriter
 * @package App\Writers\JS
 */
class JSWriter
{
    /**
     * @var array
     */
    private array $statements;

    /**
     * JSWriter constructor.
     * @param array $statements
     */
    public function __construct(array $statements = [])
    {
        $this->statements = $statements;
    }

    /**
     * @throws RuntimeException
     */
    public function toString(): string
    {
        $output = '';

        $statements = $this->statements;

        foreach ($statements as $statement) {
            if (!$statement instanceof JSStatement) {
                throw new RuntimeException('Statement must implement JSStatement interface.');
            }

            $output .= $statement->toString() . PHP_EOL;
        }

        return trim($output);
    }

    /**
     * @param array $statements
     * @return JSWriter
     */
    public function setStatements(array $statements = []): JSWriter
    {
        $this->statements = $statements;
        return $this;
    }

    /**
     * @return array
     */
    public function getStatements(): array
    {
        return $this->statements;
    }
}
