<?php
/* Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained. */

declare (strict_types=1);
namespace AlkamyPdfVendor\Sabberworm\CSS;

/**
 * Provides a method to obtain the short name of the instantiated class (i.e. without namespace prefix).
 *
 * @internal
 */
trait ShortClassNameProvider
{
    /**
     * @return non-empty-string
     */
    private function getShortClassName(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}
