<?php

namespace App\Writers;

/**
 * Class HTMLWriter
 * @package App\Writers
 */
class HTMLWriter
{
    /**
     * @var string
     */
    private $html = '';
    /**
     * @var array
     */
    private $tags = [];

    /**
     * @return $this
     */
    public function hr(): HTMLWriter
    {
        $this->selfClosingTag('hr');
        return $this;
    }

    /**
     * @return $this
     */
    public function br(): HTMLWriter
    {
        $this->selfClosingTag('br');
        return $this;
    }

    /**
     * @param string $tagName
     * @param array $attributes
     * @param null $innerHtml
     * @param bool $appendEmptyLine
     * @return $this
     */
    public function startTag(string $tagName, array $attributes = [], $innerHtml = null, $appendEmptyLine = true): HTMLWriter
    {
        $attributesHtml = $this->getAttributesHtml($attributes);
        $this->appendHtml("<$tagName$attributesHtml>" . ($innerHtml ?: '') . ($appendEmptyLine ? PHP_EOL : ''));
        $this->tags[] = $tagName;
        return $this;
    }

    /**
     * @param bool $appendEmptyLine
     * @return $this
     */
    public function closeTag($appendEmptyLine = true): HTMLWriter
    {
        $lastTag = array_pop($this->tags);

        if ($lastTag) {
            $this->appendHtml("</$lastTag>"  . ($appendEmptyLine ? PHP_EOL : ''));
        }

        return $this;
    }

    /**
     * @param string $string
     * @param bool $appendEmptyLine
     * @return $this
     */
    public function rawAppend(string $string, $appendEmptyLine = true): HTMLWriter
    {
        $this->appendHtml($string . ($appendEmptyLine ? PHP_EOL : ''));
        return $this;
    }

    /**
     * @return $this
     */
    public function emptyLine(): HTMLWriter
    {
        $this->appendHtml(PHP_EOL);
        return $this;
    }

    /**
     * @param string $tagName
     * @param array $attributes
     * @return $this
     */
    public function selfClosingTag(string $tagName, array $attributes = []): HTMLWriter
    {
        $attributesHtml = $this->getAttributesHtml($attributes);
        $this->appendHtml("<$tagName$attributesHtml />" . PHP_EOL);
        return $this;
    }

    /**
     * @return string
     */
    public function getHtml(): string
    {
        return $this->html;
    }

    /**
     * @return $this
     */
    public function reset(): HTMLWriter
    {
        $this->html = '';
        return $this;
    }

    /**
     * @return string
     */
    public function flush(): string
    {
        $html = $this->getHtml();
        $this->reset();
        return $html;
    }

    /**
     * @param array $attributes
     * @return string
     */
    private function getAttributesHtml(array $attributes): string
    {
        $html = '';
        foreach ($attributes as $key => $value) {
            // If the attribute has no key, use the value as key.
            if (is_numeric($key)) {
                $key = $value;
                $value = null;
            }

            if (is_string($value) && trim($value) === '') {
                continue;
            }

            if ($value!==null) {
                $html .= "$key=\"$value\" ";
            }
            else {
                $html .= $key . ' ';
            }
        }

        if ($html !== '') {
            $html = ' ' . substr($html, 0, -1);
        }
        else {
            $html = trim($html);
        }

        return $html;
    }

    /**
     * @param string $string
     * @return $this
     */
    private function appendHtml(string $string): HTMLWriter
    {
        $this->html.= $string;
        return $this;
    }
}
