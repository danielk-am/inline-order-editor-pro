<?php
/** php tests/customer-document-access.php */
define('ABSPATH',__DIR__.'/');
class WC_Order {public $customer=7;public function get_customer_id(){return $this->customer;}public function get_id(){return 33;}}
class WC_Order_Refund extends WC_Order {}
$GLOBALS['logged']=true;$GLOBALS['user']=7;$GLOBALS['manager']=false;$GLOBALS['editor']=false;
function is_user_logged_in(){return $GLOBALS['logged'];}function get_current_user_id(){return $GLOBALS['user'];}function current_user_can($cap,$id=0){return 'manage_woocommerce'===$cap?$GLOBALS['manager']:$GLOBALS['editor'];}
require dirname(__DIR__).'/modules/invoicing/includes/class-alkamy-customer-documents.php';
$checks=0;function check($yes,$label){global $checks;if(!$yes){throw new RuntimeException($label);}++$checks;}
$o=new WC_Order();check(Alkamy_Customer_Documents::allowed($o),'exact owner');$GLOBALS['user']=8;check(!Alkamy_Customer_Documents::allowed($o),'other user denied');$o->customer=0;check(!Alkamy_Customer_Documents::allowed($o),'guest order denied');$GLOBALS['manager']=true;check(!Alkamy_Customer_Documents::allowed($o),'manager requires order edit');$GLOBALS['editor']=true;check(Alkamy_Customer_Documents::allowed($o),'manager+edit allowed');$GLOBALS['logged']=false;check(!Alkamy_Customer_Documents::allowed($o),'logged out admin denied');$GLOBALS['logged']=true;check(!Alkamy_Customer_Documents::allowed(new WC_Order_Refund()),'refund denied');check(!Alkamy_Customer_Documents::allowed(null),'missing denied');echo "$checks customer document access checks passed.\n";
