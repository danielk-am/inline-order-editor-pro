<?php
/* Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained. */

declare (strict_types=1);
namespace AlkamyPdfVendor\Sabberworm\CSS\Parsing;

/**
 * Thrown if the CSS parser encounters end of file it did not expect.
 *
 * Extends `UnexpectedTokenException` in order to preserve backwards compatibility.
 */
final class UnexpectedEOFException extends UnexpectedTokenException
{
}
