<?php // If cheating exit
if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
exit();


delete_option( 'jm_ltsc' );
global $wpdb;
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%_last_twit_shortcode'");


