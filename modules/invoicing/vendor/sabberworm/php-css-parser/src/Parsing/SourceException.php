<?php
/* Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained. */

declare (strict_types=1);
namespace AlkamyPdfVendor\Sabberworm\CSS\Parsing;

use AlkamyPdfVendor\Sabberworm\CSS\Position\Position;
use AlkamyPdfVendor\Sabberworm\CSS\Position\Positionable;
class SourceException extends \Exception implements Positionable
{
    use Position;
    /**
     * @param int<1, max>|null $lineNumber
     */
    public function __construct(string $message, ?int $lineNumber = null)
    {
        $this->setPosition($lineNumber);
        if ($lineNumber !== null) {
            $message .= " [line no: {$lineNumber}]";
        }
        parent::__construct($message);
    }
}
