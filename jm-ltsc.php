<?php
/*Plugin Name: JM Last Twit Shortcode
Plugin URI: http://support.tweetPress.fr
Description: Meant to add your last tweet with the lattest API way
Author: Julien Maury
Author URI: http://tweetPress.fr
Version: 3.4.3
License: GPL2++
*/

// New sources => http://clark-technet.com/2013/03/updated-wordpress-twitter-functions#comment-148551 (slightly modified)
// and https://dev.twitter.com/docs/platform-objects/entities
// https://github.com/BoiteAWeb/ActivationTester/blob/master/index.php




defined( 'ABSPATH' ) or	die( 'No !' );

define( 'JM_LTSC_VERSION', '3.4.3' );
define( 'JM_LTSC_DIR', plugin_dir_path( __FILE__ )  );
define( 'JM_LTSC_INC_DIR', trailingslashit( JM_LTSC_DIR . 'inc') );
define( 'JM_LTSC_LIB_DIR', trailingslashit( JM_LTSC_DIR . 'admin/libs') );
define( 'JM_LTSC_CSS_URL', trailingslashit( plugin_dir_url( __FILE__ ). 'admin/css' ) );
define( 'JM_LTSC_JS_URL', trailingslashit( plugin_dir_url( __FILE__ ). 'admin/js' ) );
define( 'JM_LTSC_IMG_URL', trailingslashit( plugin_dir_url( __FILE__ ). 'admin/img' ) );

//Call modules 
add_action('plugins_loaded','jm_ltsc_init');
function jm_ltsc_init() {

	require( JM_LTSC_INC_DIR.'options.php' );

	if( is_admin() ) {
	
		require( JM_LTSC_INC_DIR.'utilities.php' );  
		require( JM_LTSC_INC_DIR.'tinymce.php' ); 
		require( JM_LTSC_INC_DIR.'notices.php' ); 

	}
	
	
	require( JM_LTSC_INC_DIR.'format.php' );  
	require( JM_LTSC_INC_DIR.'main.php' );  
}


// Language support
add_action( 'init', 'jm_ltsc_lang_init' );// replace admin_init with init to get translation on front-end 
function jm_ltsc_lang_init() {
	load_plugin_textdomain( 'jm-ltsc', false, dirname( plugin_basename(__FILE__) ) . '/languages/' );
}

// Plugin activation: create default values if they don't exist
register_activation_hook( __FILE__, 'm_ltsc_activate' );
function jm_ltsc_activate() {
	$opts = get_option( 'jm_ltsc' );
	if ( !is_array($opts) )
	update_option('jm_ltsc', jm_ltsc_get_default_options());
}


// Plugin uninstall: delete option
register_uninstall_hook( __FILE__, 'jm_ltsc_uninstall' );
function jm_ltsc_uninstall() {
	delete_option( 'jm_ltsc' );
}