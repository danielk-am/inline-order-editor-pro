<?php
/* Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained. */

/**
 * @package php-font-lib
 * @link    https://github.com/dompdf/php-font-lib
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace AlkamyPdfVendor\FontLib\WOFF;

use AlkamyPdfVendor\FontLib\Table\DirectoryEntry;
/**
 * WOFF font file table directory entry.
 *
 * @package php-font-lib
 */
class TableDirectoryEntry extends DirectoryEntry
{
    public $origLength;
    function __construct(File $font)
    {
        parent::__construct($font);
    }
    function parse()
    {
        parent::parse();
        $font = $this->font;
        $this->offset = $font->readUInt32();
        $this->length = $font->readUInt32();
        $this->origLength = $font->readUInt32();
        $this->checksum = $font->readUInt32();
    }
}
