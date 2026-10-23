<?php
/* Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained. */

declare (strict_types=1);
namespace AlkamyPdfVendor\Sabberworm\CSS\Parsing;

/**
 * Thrown if the CSS parser encounters a token it did not expect.
 */
class UnexpectedTokenException extends SourceException
{
    /**
     * @param 'literal'|'identifier'|'count'|'expression'|'search'|'custom' $matchType
     * @param int<1, max>|null $lineNumber
     */
    public function __construct(string $expected, string $found, string $matchType = 'literal', ?int $lineNumber = null)
    {
        $message = "Token “{$expected}” ({$matchType}) not found. Got “{$found}”.";
        if ($matchType === 'search') {
            $message = "Search for “{$expected}” returned no results. Context: “{$found}”.";
        } elseif ($matchType === 'count') {
            $message = "Next token was expected to have {$expected} chars. Context: “{$found}”.";
        } elseif ($matchType === 'identifier') {
            $message = "Identifier expected. Got “{$found}”";
        } elseif ($matchType === 'custom') {
            $message = \trim("{$expected} {$found}");
        }
        parent::__construct($message, $lineNumber);
    }
}
