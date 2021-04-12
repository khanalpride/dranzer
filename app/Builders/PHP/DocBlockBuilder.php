<?php

namespace App\Builders\PHP;

use RuntimeException;
use PhpParser\Node\Param;
use PhpParser\Comment\Doc;

/**
 * Class DocBlockBuilder
 * @package App\Builders\PHP
 */
class DocBlockBuilder
{
    /**
     * @var array
     */
    private array $comments = [];
    /**
     * @var array
     */
    private array $parameters = [];
    /**
     * @var array
     */
    private array $vars = [];
    /**
     * @var
     */
    private $returnType;
    /**
     * @var array
     */
    private array $exceptions = [];
    /**
     * @var array
     */
    private array $annotations = [];
    /**
     * @var bool
     */
    private bool $appendNewLineAtEnd = true;
    /**
     * @var bool
     */
    private bool $shouldPrependNewLine = false;
    /**
     * @var
     */
    private $raw;
    /**
     * @var
     */
    private $laravelStyleDecoration;

    /**
     * @var array
     */
    private array $suppressedInspections = [];

    /**
     * @param $name
     * @param string $param
     * @param bool $wrapBrackets
     * @return DocBlockBuilder
     */
    public function addAnnotation(
        $name,
        $param = '',
        $wrapBrackets = true
    ): DocBlockBuilder
    {
        $this->annotations[$name] = [
            $param,
            $wrapBrackets
        ];
        return $this;
    }

    /**
     * @param string $comment
     * @return DocBlockBuilder
     */
    public function addCommentLine($comment = ''): DocBlockBuilder
    {
        $this->comments[] = $comment;
        return $this;
    }

    /**
     * @param $exception
     * @return DocBlockBuilder
     */
    public function addException($exception): DocBlockBuilder
    {
        $this->exceptions[] = $exception;
        return $this;
    }

    /**
     * @param array $exceptions
     * @return DocBlockBuilder
     */
    public function addExceptions(array $exceptions): DocBlockBuilder
    {
        foreach ($exceptions as $exception) {
            $this->addException($exception);
        }
        return $this;
    }

    /**
     * @param Param $parameter
     * @return DocBlockBuilder
     */
    public function addParameter(Param $parameter): DocBlockBuilder
    {
        $this->parameters[] = $parameter;
        return $this;
    }

    /**
     * @param array $parameters
     * @return DocBlockBuilder
     */
    public function addParameters(array $parameters): DocBlockBuilder
    {
        foreach ($parameters as $parameter) {
            $this->addParameter($parameter);
        }
        return $this;
    }

    /**
     * @param string $var
     * @return DocBlockBuilder
     *
     * @noinspection PhpUnused
     * @noinspection UnknownInspectionInspection
     */
    public function addVar(string $var): DocBlockBuilder
    {
        $this->vars[] = $var;
        return $this;
    }

    /**
     * @param $rawComment
     * @return DocBlockBuilder
     */
    public function rawComment($rawComment): DocBlockBuilder
    {
        $this->raw = $rawComment;

        return $this;
    }

    /**
     * @param $heading
     * @param $comment
     * @return DocBlockBuilder
     */
    public function laravelStyleDoc($heading, $comment): DocBlockBuilder
    {
        if (!$comment) {
            throw new RuntimeException('Comment must not be empty or null.');
        }

        $decorated = '';

        $sentences = $this->sentences($comment . '. ');

        $lines = [];
        $skip = false;

        foreach ($sentences as $index => $sentence) {
            if (trim($sentence) === '') {
                continue;
            }

            $sentenceEndedProperly = $this->sentenceEndedProperly($sentence);

            if (!$sentenceEndedProperly) {
                $sentence .= '. ';
            }

            if ($skip) {
                $skip = false;
                continue;
            }
            if ($index > 0 && $index % 2 === 0) {
                $lines[] = "\n";
            }

            if (strlen($sentence) < 70 && $index < count($sentences) - 1) {
                $nextSentence = $sentences[$index + 1];
                if (trim($nextSentence) !== '') {
                    $nextSentenceEndedProperly = $this->sentenceEndedProperly(
                        $nextSentence
                    );

                    if (!$nextSentenceEndedProperly) {
                        $nextSentence .= '. ';
                    }

                    $merged = $sentence . $nextSentence;
                    $lines[] = $this->wordWrap($merged);
                    $skip = true;
                    continue;
                }
            }

            if (strlen($sentence) >= 72) {
                $lines[] = $this->wordWrap($sentence);
                $lines[] = "\n";
                continue;
            }

            $lines[] = $this->wordWrap($sentence);
        }

        $nl = false;
        $maxLineLength = strlen($heading);
        foreach ($lines as $index => $line) {
            if ($line === PHP_EOL) {
                $index < count($lines) - 1 && !$nl && ($decorated .= "|\n");
                $nl = true;
                continue;
            }
            foreach ($line as $part) {
                if (strlen($part) > $maxLineLength) {
                    $maxLineLength = strlen($part);
                }
                $decorated .= '| ' . trim($part) . "\n";
                $nl = false;
            }
        }

        $maxLineLength++;
        $maxLineLength = $maxLineLength < 72 ? 72 : $maxLineLength;

        $decorated = trim($decorated);

        $decorated =
            "/*\n|" .
            $this->dashes($maxLineLength) .
            "\n| $heading\n|" .
            $this->dashes($maxLineLength) .
            "\n|\n" .
            $decorated;

        $decorated .= "\n|\n*/";

        $this->laravelStyleDecoration = $decorated;
        return $this;
    }

    /**
     * @return null|Doc
     * @noinspection PhpUndefinedFieldInspection
     * @noinspection MultiAssignmentUsageInspection
     * @noinspection NestedTernaryOperatorInspection
     */
    public function getDocBlock(): ?Doc
    {
        if ($this->getRaw()) {
            $rawLines = explode("\n", $this->getRaw());
            foreach ($rawLines as $rawLine) {
                $this->addCommentLine($rawLine);
            }
        }

        if ($this->getLaravelStyleDecoration()) {
            $doc = new Doc($this->getLaravelStyleDecoration());
            $doc->commentAttributes['doNotAppendNewline'] = !$this->shouldAppendNewLineAtEnd();
            return $doc;
        }

        $block = '';

        $parameters = $this->getParameters();
        $vars = $this->getVars();
        $exceptions = $this->getExceptions();
        $returnType = $this->getReturnType();
        $comments = $this->getComments();
        $suppressedInspections = $this->getSuppressedInspections();

        $hasContent = !$this->isDry();

        if (!$hasContent) {
            return null;
        }

        if (count($comments) > 0) {
            foreach ($comments as $comment) {
                $block .= $this->newLine($comment);
            }
        }

        $addEmptyLine =
            (
                count($parameters) > 0 ||
                count($exceptions) > 0 ||
                count($vars) > 0 ||
                $returnType
            ) &&
            count($comments) > 0;

        if ($addEmptyLine) {
            $block .= $this->newLine();
        }

        foreach ($parameters as $parameter) {
            $type = $parameter->type->name ?? null;

            if ($parameter->hasAttribute('docBlockParamType')) {
                $type = $parameter->getAttribute('docBlockParamType');
            }

            $param = '@param ' . ($type ? $type . ' ' : '') . '$' . $parameter->var->name;
            $block .= $this->newLine($param);
        }

        if (count($exceptions) > 0) {
            foreach ($exceptions as $exception) {
                $block .= $this->newLine('@throws ' . $exception);
            }
        }

        if (count($vars) > 0) {
            foreach ($vars as $var) {
                $block .= $this->newLine('@var ' . $var);
            }
        }

        if (count($this->annotations) > 0) {
            $block .= $this->newLine();
        }

        foreach ($this->annotations as $name => $options) {
            $parameters = $options[0];
            $wrapInBrackets = $options[1];
            $value = $wrapInBrackets
                ? (trim($parameters) !== ''
                    ? "($parameters)"
                    : '')
                : ' ' . $parameters;

            $block .= $this->newLine('@' . $name . $value);
        }

        if ($returnType) {
            $block .= $this->newLine('@return ' . $returnType);
        }

        if (count($suppressedInspections)) {
            $block .= $this->newLine();
            foreach ($suppressedInspections as $suppressedInspection) {
                $block .= $this->newLine('@noinspection ' . $suppressedInspection);
            }
        }

        $doc = new Doc($this->docBlockWrapper($block));
        $doc->commentAttributes['doNotAppendNewline'] = !$this->shouldAppendNewLineAtEnd();

        $doc->commentAttributes['prependNewline'] = $this->shouldPrependNewLine();

        return $doc;
    }

    /**
     * @return null
     */
    public function getRaw()
    {
        return $this->raw;
    }

    /**
     * @return mixed
     */
    public function getLaravelStyleDecoration()
    {
        return $this->laravelStyleDecoration;
    }

    /**
     * @return bool
     */
    public function shouldAppendNewLineAtEnd(): bool
    {
        return $this->appendNewLineAtEnd;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array
     */
    public function getVars(): array
    {
        return $this->vars;
    }

    /**
     * @return array
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    /**
     * @return null
     */
    public function getReturnType()
    {
        return $this->returnType;
    }

    /**
     * @param null $returnType
     * @return DocBlockBuilder
     */
    public function setReturnType($returnType): DocBlockBuilder
    {
        $this->returnType = $returnType;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasReturnType(): bool
    {
        return $this->returnType !== null;
    }

    /**
     * @return null
     */
    public function getComments(): ?array
    {
        return $this->comments;
    }

    /**
     * @return bool
     */
    public function isDry(): bool
    {
        return !(
            count($this->getParameters()) > 0 ||
            count($this->getExceptions()) > 0 ||
            count($this->getVars()) > 0 ||
            count($this->getComments()) > 0 ||
            count($this->getAnnotations()) > 0 ||
            count($this->getSuppressedInspections()) > 0 ||
            $this->getReturnType()
        );
    }

    /**
     * @return array
     */
    public function getAnnotations(): array
    {
        return $this->annotations;
    }

    /**
     * @param array $annotations
     * @return DocBlockBuilder
     */
    public function setAnnotations(array $annotations): DocBlockBuilder
    {
        $this->annotations = $annotations;
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
     * @return DocBlockBuilder
     */
    public function setSuppressedInspections(array $suppressedInspections): DocBlockBuilder
    {
        $this->suppressedInspections = $suppressedInspections;
        return $this;
    }

    /**
     * @param $inspection
     * @return DocBlockBuilder
     */
    public function suppressInspection($inspection): DocBlockBuilder
    {
        if (!in_array($inspection, $this->suppressedInspections, true)) {
            $this->suppressedInspections[] = $inspection;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function shouldPrependNewLine(): bool
    {
        return $this->shouldPrependNewLine;
    }

    /**
     * @return DocBlockBuilder
     */
    public function prependNewLine(): DocBlockBuilder
    {
        $this->shouldPrependNewLine = true;

        return $this;
    }

    /**
     * @param $string
     * @return false|string[]
     */
    private function sentences($string)
    {
        return explode('. ', $string);
    }

    /**
     * @param $sentence
     * @return bool
     */
    private function sentenceEndedProperly($sentence): bool
    {
        $lastChar = substr(trim($sentence), strlen(trim($sentence)) - 1);
        return $lastChar === '.' || $lastChar === '!' || $lastChar === '"';
    }

    /**
     * @param $text
     * @return array
     */
    private function wordWrap($text): array
    {
        $lines = [];
        $line = '';
        $words = explode(' ', $text);

        $currentLength = 0;
        $leftOver = null;

        foreach ($words as $word) {
            $len = strlen($word);
            $currentLength += $len + 1;
            if ($currentLength <= 72) {
                $line .= ($leftOver ? $leftOver . ' ' . $word : $word) . ' ';
                $leftOver = null;
                continue;
            }

            $leftOver = $word;
            $lines[] = $line;
            $line = '';
            $currentLength = 0;
        }

        if ($leftOver) {
            $line .= ' ' . $leftOver;
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * @param int $count
     * @return string
     */
    private function dashes($count = 80): string
    {
        return str_repeat('-', $count);
    }

    /**
     * @param string $line
     * @return string
     */
    private function newLine($line = ''): string
    {
        return " * $line" . (true ? PHP_EOL : '');
    }

    /**
     * @param $block
     * @return string
     */
    private function docBlockWrapper($block): string
    {
        return (!$this->shouldAppendNewLineAtEnd() ? PHP_EOL : '') .
            "/**\n$block */";
    }
}
