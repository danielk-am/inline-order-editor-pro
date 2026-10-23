<?php
/* Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained. */

/**
 * @package php-svg-lib
 * @link    http://github.com/dompdf/php-svg-lib
 * @license GNU LGPLv3+ http://www.gnu.org/copyleft/lesser.html
 */
namespace AlkamyPdfVendor\Svg;

class DefaultStyle extends Style
{
    public $color = [0, 0, 0, 1];
    public $opacity = 1.0;
    public $display = 'inline';
    public $fill = [0, 0, 0, 1];
    public $fillOpacity = 1.0;
    public $fillRule = 'nonzero';
    public $stroke = 'none';
    public $strokeOpacity = 1.0;
    public $strokeLinecap = 'butt';
    public $strokeLinejoin = 'miter';
    public $strokeMiterlimit = 4;
    public $strokeWidth = 1.0;
    public $strokeDasharray = 0;
    public $strokeDashoffset = 0;
}
