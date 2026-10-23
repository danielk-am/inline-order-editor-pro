<?php
// protects local private PDF output; fails when rendering can fetch assets, execute script, or leak temp files.
define('ABSPATH',__DIR__);
function __($s,$domain=''){return $s;}
function wp_delete_file($file){unlink($file);}
class Alkamy_Layout { public static $html='<!doctype html><html><body><p>Local invoice</p></body></html>'; public static function html($record){return self::$html;} }
$file=dirname(__DIR__).'/modules/invoicing/includes/class-alkamy-local-pdf.php';
if(!is_file($file)){fwrite(STDERR,"FAIL local PDF generator missing\n");exit(1);}require $file;
function check($ok,$name){if(!$ok)throw new Exception($name);echo 'PASS '.$name."\n";}
check(Alkamy_Local_Pdf::available(),'Bundled isolated renderer available');
$before=glob(sys_get_temp_dir().'/alkamy-pdf-*');
$pdf=Alkamy_Local_Pdf::render(['snapshot'=>['type'=>'invoice','layout'=>['brand'=>['paper'=>'a4']]]]);
check(str_starts_with($pdf,'%PDF-')&&str_contains($pdf,'%%EOF'),'Produces actual PDF');
check(glob(sys_get_temp_dir().'/alkamy-pdf-*')===$before,'Private temp removed after rendering');
Alkamy_Layout::$html='<html><body><script type="text/php">file_put_contents("/tmp/alkamy-pdf-script-executed","bad");</script><img src="file:///etc/passwd"><img src="https://example.test/track"><p>Safe content</p></body></html>';
$pdf=Alkamy_Local_Pdf::render(['snapshot'=>['type'=>'invoice']]);
check(str_starts_with($pdf,'%PDF-')&&!file_exists('/tmp/alkamy-pdf-script-executed'),'Script and non-embedded assets blocked');
try{Alkamy_Local_Pdf::render(['snapshot'=>['type'=>'bad']]);throw new Exception('Unknown type accepted');}catch(InvalidArgumentException $e){echo "PASS Unknown document rejected\n";}
