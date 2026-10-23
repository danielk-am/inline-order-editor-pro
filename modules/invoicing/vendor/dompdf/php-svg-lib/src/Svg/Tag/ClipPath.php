<?php
/* Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained. */

/**
 * @package php-svg-lib
 * @link    http://github.com/dompdf/php-svg-lib
 * @license GNU LGPLv3+ http://www.gnu.org/copyleft/lesser.html
 */
namespace AlkamyPdfVendor\Svg\Tag;

use AlkamyPdfVendor\Svg\Style;
class ClipPath extends AbstractTag
{
    protected function before($attributes)
    {
        $surface = $this->document->getSurface();
        $surface->save();
        $style = $this->makeStyle($attributes);
        $this->setStyle($style);
        $surface->setStyle($style);
        $this->applyTransform($attributes);
    }
    protected function after()
    {
        $this->document->getSurface()->restore();
    }
}
