<?php
/*Plugin Name: JM Last Twit Shortcode
Plugin URI: http://support.tweetPress.fr
Description: Meant to add your last tweet with the lattest API way
Author: Julien Maury
Author URI: http://tweetPress.fr
Version: 4.0
License: GPL2++
*/



defined( 'ABSPATH' ) or	die( 'No !' );

define( 'JM_LTSC_VERSION', '4.0' );
define( 'JM_LTSC_DIR', plugin_dir_path( __FILE__ )  );
define( 'JM_LTSC_INC_DIR', trailingslashit( JM_LTSC_DIR . 'inc') );
define( 'JM_LTSC_LIB_DIR', trailingslashit( JM_LTSC_DIR . 'admin/libs') );
define( 'JM_LTSC_CSS_URL', trailingslashit( plugin_dir_url( __FILE__ ). 'admin/css' ) );
define( 'JM_LTSC_JS_URL', trailingslashit( plugin_dir_url( __FILE__ ). 'admin/js' ) );
define( 'JM_LTSC_IMG_URL', trailingslashit( plugin_dir_url( __FILE__ ). 'admin/img' ) );
define( 'JM_LTSC_LANG_DIR', dirname( plugin_basename(__FILE__) ) . '/languages/');

//Call modules 
add_action('plugins_loaded','jm_ltsc_init');
function jm_ltsc_init() {

	require( JM_LTSC_INC_DIR.'options.php' );	

	if( is_admin() ) {
  
		require( JM_LTSC_INC_DIR.'tinymce.php' ); 
		require( JM_LTSC_INC_DIR.'notices.php' ); 

	}
	
	
	require( JM_LTSC_INC_DIR.'/class/authorize.class.php' );  
	require( JM_LTSC_INC_DIR.'main.php' );  

}


// Language support
add_action( 'init', 'jm_ltsc_lang_init' );// replace admin_init with init to get translation on front-end 
function jm_ltsc_lang_init() {
	load_plugin_textdomain( 'jm-ltsc', false, JM_LTSC_LANG_DIR );
}

// Plugin activation: create default values if they don't exist


function jm_ltsc_on_activation() {
	$opts = get_option( 'jm_ltsc' );
	if ( !is_array($opts) )
	update_option( 'jm_ltsc', jm_ltsc_get_default_options() );
}

register_activation_hook( __FILE__, 'jm_ltsc_activate' );

function jm_ltsc_activate() {
	if( !is_multisite() ) {
		
		jm_ltsc_on_activation();
	
	} else {
	    // For regular options.
		global $wpdb;
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
		foreach ( $blog_ids as $blog_id ) 
		{
			switch_to_blog( $blog_id );
			jm_ltsc_on_activation();
			restore_current_blog();
		}
	
	}
	
}

// Return default options
function jm_ltsc_get_default_options() {
	return array(
	'twitAccount'              => '',
	'consumerKey'              => __('replace with your keys - required', 'jm-ltsc'),
	'consumerSecret'           => __('replace with your keys - required', 'jm-ltsc'),
	'twitQuickTags'            => 'yes'
	);
}