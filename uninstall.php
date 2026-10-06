<?php
/**
 * Removes the add-on's settings when it is deleted.
 *
 * Notes and data already saved on orders are part of those orders' records, so they stay.
 *
 * @package Inline_Order_Editor_Pro
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'ioefwp_saved_terms' );
