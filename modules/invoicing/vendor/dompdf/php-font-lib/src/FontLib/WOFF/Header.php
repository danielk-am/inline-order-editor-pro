<?php
/* Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained. */

/**
 * @package php-font-lib
 * @link    https://github.com/dompdf/php-font-lib
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace AlkamyPdfVendor\FontLib\WOFF;

/**
 * WOFF font file header.
 *
 * @package php-font-lib
 */
class Header extends \AlkamyPdfVendor\FontLib\TrueType\Header
{
    protected $def = array("format" => self::uint32, "flavor" => self::uint32, "length" => self::uint32, "numTables" => self::uint16, self::uint16, "totalSfntSize" => self::uint32, "majorVersion" => self::uint16, "minorVersion" => self::uint16, "metaOffset" => self::uint32, "metaLength" => self::uint32, "metaOrigLength" => self::uint32, "privOffset" => self::uint32, "privLength" => self::uint32);
}
