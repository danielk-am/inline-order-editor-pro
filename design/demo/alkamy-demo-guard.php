<?php
/** Synthetic local demo: never send mail or remote HTTP requests. */
if (!defined('DISABLE_WP_CRON')) define('DISABLE_WP_CRON',true);
if (!defined('AUTOMATIC_UPDATER_DISABLED')) define('AUTOMATIC_UPDATER_DISABLED',true);
add_filter('pre_wp_mail',static function($result,$atts){update_option('alkamy_demo_last_mail',array('to'=>$atts['to'],'subject'=>$atts['subject']),false);return true;},PHP_INT_MAX,2);
add_filter('pre_http_request',static function(){return new WP_Error('alkamy_demo_offline','Remote HTTP is disabled in this synthetic demo.');},PHP_INT_MAX,3);
add_filter('automatic_updater_disabled','__return_true');
add_filter('woocommerce_available_payment_gateways','__return_empty_array',PHP_INT_MAX);
add_filter('woocommerce_webhook_should_deliver','__return_false',PHP_INT_MAX);
add_filter('action_scheduler_queue_runner_concurrent_batches',static function(){return 0;});
