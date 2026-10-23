<?php
/** Small, escaped Markdown subset for merchant-authored document sections. */
defined( 'ABSPATH' ) || exit;
final class Alkamy_Markdown {
 private static function inline( $source ) {
  $pattern = '~(?<!!)\[([^\]\n]+)\]\(([^\s)]+)\)|\*\*([^*\n]+)\*\*|(?<!\*)\*([^*\n]+)\*(?!\*)~';
  $html = '';$offset = 0;
  preg_match_all( $pattern, $source, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );
  foreach ( $matches as $match ) {
   $html .= esc_html( substr( $source, $offset, $match[0][1] - $offset ) );$raw = $match[0][0];
   if ( ! empty( $match[1][0] ) ) {
    $url = $match[2][0];
    $safe = preg_match( '~^https?://[^\s<>"\x27]+$~i', $url ) && filter_var( $url, FILTER_VALIDATE_URL );
    $html .= $safe ? '<a href="' . esc_attr( $url ) . '">' . esc_html( $match[1][0] ) . '</a>' : esc_html( $raw );
   } elseif ( isset( $match[3] ) && '' !== $match[3][0] ) { $html .= '<strong>' . esc_html( $match[3][0] ) . '</strong>'; }
   else { $html .= '<em>' . esc_html( $match[4][0] ) . '</em>'; }
   $offset = $match[0][1] + strlen( $raw );
  }
  return $html . esc_html( substr( $source, $offset ) );
 }
 /** Native IOEFW performs its own escaping; supply readable text, never HTML. */
 public static function plain( $source ) {
  $html = self::render( $source );
  $html = preg_replace( '~<a href="([^"]*)">([^<]*)</a>~', '$2 ($1)', $html );
  $html = preg_replace_callback( '~<li(?: value="(\d+)")?>~', static function ( $match ) { return isset( $match[1] ) ? $match[1] . '. ' : '- '; }, $html );
  $html = preg_replace( '~</(?:p|li|h[3-5])>~', "\n", $html );
  return trim( html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
 }
 public static function render( $source ) {
  $html = '';$list = '';
  foreach ( preg_split( '/\r\n|\r|\n/', (string) $source ) as $line ) {
   $type = ''; $number = null;
   if ( preg_match( '/^\s*[-*] +(.+)$/', $line, $match ) ) { $type = 'ul';$line = $match[1]; }
   elseif ( preg_match( '/^\s*(\d{1,6})[.)] +(.+)$/', $line, $match ) ) { $type = 'ol';$number = (int) $match[1];$line = $match[2]; }
   if ( $list !== $type ) { if ( $list ) { $html .= '</' . $list . '>'; }if ( $type ) { $html .= '<' . $type . '>'; }$list = $type; }
   if ( $type ) { $html .= '<li' . ( null !== $number ? ' value="' . $number . '"' : '' ) . '>' . self::inline( $line ) . '</li>'; }
   elseif ( preg_match( '/^(#{1,3}) +(.+)$/', $line, $match ) ) { $level = strlen( $match[1] ) + 2;$html .= '<h' . $level . '>' . self::inline( $match[2] ) . '</h' . $level . '>'; }
   elseif ( '' !== trim( $line ) ) { $html .= '<p>' . self::inline( $line ) . '</p>'; }
  }
  return $html . ( $list ? '</' . $list . '>' : '' );
 }
}
