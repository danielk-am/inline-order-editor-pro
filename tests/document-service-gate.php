<?php
define('ABSPATH',__DIR__);
function __($s,$d=''){return $s;}function esc_html__($s,$d=''){return $s;}function esc_html($s){return htmlspecialchars($s);}function esc_attr($s){return htmlspecialchars($s);}function esc_attr__($s,$d=''){return $s;}function esc_url($s){return $s;}function admin_url($s){return '/wp-admin/'.$s;}function wp_create_nonce($s){return 'test-nonce';}
class Alkamy_Local_Pdf{public static function available(){return true;}}
class Alkamy_Service{public static $ready=false;public static function configured(){return self::$ready;}}
class Alkamy_Documents{public static function records($order){return [];}}
require dirname(__DIR__).'/modules/invoicing/includes/class-alkamy-workspace.php';
$order=new class{public function get_id(){return 1;}};
foreach([false,true] as $ready){Alkamy_Service::$ready=$ready;ob_start();Alkamy_Workspace::preview($order);$preview=ob_get_clean();ob_start();Alkamy_Workspace::actions($order);$actions=ob_get_clean();
 if(strpos($preview,'<option value="invoice">')===false)throw new Exception('Local layout types disabled');
 if(strpos($preview,'value="pdf" disabled')!==false||preg_match_all('/data-alkamy-action="local_pdf"[^>]*disabled/',$actions))throw new Exception('Local PDFs incorrectly require payment service');
 if(substr_count($actions,'data-alkamy-action="local_pdf"')!==4)throw new Exception('Missing local document download controls');
 if($ready&&(strpos($preview,' disabled')!==false||strpos($actions,' disabled')!==false))throw new Exception('Available service actions blocked');
}
echo "PASS local PDF preview/download independent of payment service\n";
