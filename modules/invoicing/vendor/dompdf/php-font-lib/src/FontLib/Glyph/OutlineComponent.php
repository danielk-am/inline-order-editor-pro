<?php
/* Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained. */

/**
 * @package php-font-lib
 * @link    https://github.com/dompdf/php-font-lib
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @version $Id: Font_Table_glyf.php 46 2012-04-02 20:22:38Z fabien.menager $
 */
namespace AlkamyPdfVendor\FontLib\Glyph;

/**
 * Glyph outline component
 *
 * @package php-font-lib
 */
class OutlineComponent
{
    public $flags;
    public $glyphIndex;
    public $a, $b, $c, $d, $e, $f;
    public $point_compound;
    public $point_component;
    public $instructions;
    function getMatrix()
    {
        return array($this->a, $this->b, $this->c, $this->d, $this->e, $this->f);
    }
}
