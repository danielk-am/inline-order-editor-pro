<?php
/* Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained. */

namespace AlkamyPdfVendor\Dompdf\Css\Content;

final class Attr extends ContentPart
{
    /**
     * @var string
     */
    public $attribute;
    public function __construct(string $attribute)
    {
        $this->attribute = $attribute;
    }
    public function equals(ContentPart $other): bool
    {
        return $other instanceof self && $other->attribute === $this->attribute;
    }
    public function __toString(): string
    {
        return "attr({$this->attribute})";
    }
}
