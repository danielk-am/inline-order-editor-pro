<?php
/* Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained. */

namespace AlkamyPdfVendor\Dompdf\Css\Content;

final class CloseQuote extends ContentPart
{
    public function __toString(): string
    {
        return "close-quote";
    }
}
