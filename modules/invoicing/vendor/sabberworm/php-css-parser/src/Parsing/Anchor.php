<?php
/* Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained. */

declare (strict_types=1);
namespace AlkamyPdfVendor\Sabberworm\CSS\Parsing;

/**
 * @internal since 8.7.0
 */
class Anchor
{
    /**
     * @var int<0, max>
     */
    private $position;
    /**
     * @var ParserState
     */
    private $parserState;
    /**
     * @param int<0, max> $position
     */
    public function __construct(int $position, ParserState $parserState)
    {
        $this->position = $position;
        $this->parserState = $parserState;
    }
    public function backtrack(): void
    {
        $this->parserState->setPosition($this->position);
    }
}
