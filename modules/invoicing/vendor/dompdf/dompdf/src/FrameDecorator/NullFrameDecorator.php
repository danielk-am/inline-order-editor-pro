<?php
/* Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained. */

/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace AlkamyPdfVendor\Dompdf\FrameDecorator;

use AlkamyPdfVendor\Dompdf\Dompdf;
use AlkamyPdfVendor\Dompdf\Frame;
/**
 * Dummy decorator
 *
 * @package dompdf
 */
class NullFrameDecorator extends AbstractFrameDecorator
{
    /**
     * NullFrameDecorator constructor.
     * @param Frame $frame
     * @param Dompdf $dompdf
     */
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);
        $style = $this->_frame->get_style();
        $style->width = 0;
        $style->height = 0;
        $style->margin = 0;
        $style->padding = 0;
    }
}
