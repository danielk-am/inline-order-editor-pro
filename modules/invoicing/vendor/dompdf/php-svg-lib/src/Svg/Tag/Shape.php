<?php
/* Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained. */

/**
 * @package php-svg-lib
 * @link    http://github.com/dompdf/php-svg-lib
 * @license GNU LGPLv3+ http://www.gnu.org/copyleft/lesser.html
 */
namespace AlkamyPdfVendor\Svg\Tag;

use AlkamyPdfVendor\Svg\Style;
class Shape extends AbstractTag
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
        $surface = $this->document->getSurface();
        if ($this->hasShape) {
            $style = $surface->getStyle();
            $fill = $style->fill && is_array($style->fill);
            $stroke = $style->stroke && is_array($style->stroke);
            if ($fill) {
                if ($stroke) {
                    $surface->fillStroke(\false);
                } else {
                    //                    if (is_string($style->fill)) {
                    //                        /** @var LinearGradient|RadialGradient $gradient */
                    //                        $gradient = $this->getDocument()->getDef($style->fill);
                    //
                    //                        var_dump($gradient->getStops());
                    //                    }
                    $surface->fill();
                }
            } elseif ($stroke) {
                $surface->stroke(\false);
            } else {
                $surface->endPath();
            }
        }
        $surface->restore();
    }
}
