<?php // If cheating exit
if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
exit();
	
	
function jm_ltsc_on_delete() {	

	delete_option( 'jm_ltsc' );
	global $wpdb;
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%_last_twit_shortcode'");
	
}

if( !is_multisite() ) {

	jm_ltsc_on_delete();

} else {
    // For regular options.
    global $wpdb;
    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
    foreach ( $blog_ids as $blog_id ) 
    {
        switch_to_blog( $blog_id );
		jm_ltsc_on_delete();
    }
	
    restore_current_blog();
}
