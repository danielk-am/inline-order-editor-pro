<?php
/* Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained. */

/**
 * @package php-svg-lib
 * @link    http://github.com/dompdf/php-svg-lib
 * @license GNU LGPLv3+ http://www.gnu.org/copyleft/lesser.html
 */
namespace AlkamyPdfVendor\Svg\Tag;

use AlkamyPdfVendor\Sabberworm\CSS;
class StyleTag extends AbstractTag
{
    protected $text = "";
    public function end()
    {
        $parser = new CSS\Parser($this->text);
        $this->document->appendStyleSheet($parser->parse());
    }
    public function appendText($text)
    {
        $this->text .= $text;
    }
}
