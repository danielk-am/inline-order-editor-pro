<?php
/* Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained. */

namespace AlkamyPdfVendor\Dompdf\Css\Content;

final class Counter extends ContentPart
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $style;
    public function __construct(string $name, string $style)
    {
        $this->name = $name;
        $this->style = $style;
    }
    public function equals(ContentPart $other): bool
    {
        return $other instanceof self && $other->name === $this->name && $other->style === $this->style;
    }
    public function __toString(): string
    {
        return "counter({$this->name}, {$this->style})";
    }
}
