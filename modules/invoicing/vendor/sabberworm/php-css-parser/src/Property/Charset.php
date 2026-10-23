<?php
/* Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained. */

declare (strict_types=1);
namespace AlkamyPdfVendor\Sabberworm\CSS\Property;

use AlkamyPdfVendor\Sabberworm\CSS\Comment\CommentContainer;
use AlkamyPdfVendor\Sabberworm\CSS\OutputFormat;
use AlkamyPdfVendor\Sabberworm\CSS\Position\Position;
use AlkamyPdfVendor\Sabberworm\CSS\Position\Positionable;
use AlkamyPdfVendor\Sabberworm\CSS\ShortClassNameProvider;
use AlkamyPdfVendor\Sabberworm\CSS\Value\CSSString;
/**
 * Class representing an `@charset` rule.
 *
 * The following restrictions apply:
 * - May not be found in any CSSList other than the Document.
 * - May only appear at the very top of a Document’s contents.
 * - Must not appear more than once.
 */
class Charset implements AtRule, Positionable
{
    use CommentContainer;
    use Position;
    use ShortClassNameProvider;
    /**
     * @var CSSString
     */
    private $charset;
    /**
     * @param int<1, max>|null $lineNumber
     */
    public function __construct(CSSString $charset, ?int $lineNumber = null)
    {
        $this->charset = $charset;
        $this->setPosition($lineNumber);
    }
    /**
     * @param string|CSSString $charset
     */
    public function setCharset($charset): void
    {
        $charset = $charset instanceof CSSString ? $charset : new CSSString($charset);
        $this->charset = $charset;
    }
    public function getCharset(): string
    {
        return $this->charset->getString();
    }
    /**
     * @return non-empty-string
     */
    public function render(OutputFormat $outputFormat): string
    {
        return "{$outputFormat->getFormatter()->comments($this)}@charset {$this->charset->render($outputFormat)};";
    }
    /**
     * @return non-empty-string
     */
    public function atRuleName(): string
    {
        return 'charset';
    }
    public function atRuleArgs(): CSSString
    {
        return $this->charset;
    }
    /**
     * @return array<string, bool|int|float|string|array<mixed>|null>
     *
     * @internal
     */
    public function getArrayRepresentation(): array
    {
        return ['class' => $this->getShortClassName(), 'charset' => $this->charset->getArrayRepresentation()];
    }
}
