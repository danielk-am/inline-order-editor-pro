<?php
/* Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained. */

declare (strict_types=1);
namespace AlkamyPdfVendor\Sabberworm\CSS\Position;

/**
 * Provides a standard reusable implementation of `Positionable`.
 *
 * @internal
 *
 * @phpstan-require-implements Positionable
 */
trait Position
{
    /**
     * @var int<1, max>|null
     */
    protected $lineNumber;
    /**
     * @var int<0, max>|null
     */
    protected $columnNumber;
    /**
     * @return int<1, max>|null
     */
    public function getLineNumber(): ?int
    {
        return $this->lineNumber;
    }
    /**
     * @return int<0, max>|null
     */
    public function getColumnNumber(): ?int
    {
        return $this->columnNumber;
    }
    /**
     * @param int<1, max>|null $lineNumber
     * @param int<0, max>|null $columnNumber
     *
     * @return $this fluent interface
     */
    public function setPosition(?int $lineNumber, ?int $columnNumber = null): Positionable
    {
        $this->lineNumber = $lineNumber;
        $this->columnNumber = $columnNumber;
        return $this;
    }
}
