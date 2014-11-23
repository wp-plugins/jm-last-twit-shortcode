<?php
/*Plugin Name: JM Last Twit Shortcode
Plugin URI: http://support.tweetPress.fr
Description: Meant to add your last tweet with the lattest API way
Author: Julien Maury
Author URI: http://tweetPress.fr
Version: 4.2
License: GPL2++
*/

defined( 'ABSPATH' ) or	die( 'No !' );

define( 'JM_LTSC_VERSION', '4.2' );
define( 'JM_LTSC_DIR', plugin_dir_path( __FILE__ )  );
define( 'JM_LTSC_CLASS_DIR', plugin_dir_path( __FILE__ ) . 'classes/' );
define( 'JM_LTSC_LIB_DIR', JM_LTSC_DIR . 'admin/libs/' );

define( 'JM_LTSC_CSS_URL', plugin_dir_url( __FILE__ ). 'assets/css/' );
define( 'JM_LTSC_JS_URL', plugin_dir_url( __FILE__ ). 'assets/js/' );
define( 'JM_LTSC_IMG_URL', plugin_dir_url( __FILE__ ). 'assets/img/' );


define( 'JM_LTSC_LANG_DIR', dirname( plugin_basename(__FILE__) ) . '/languages/');
define( 'JM_LTSC_SLUG_NAME', 'jm-ltsc' );

//Call modules 
add_action('plugins_loaded','jm_ltsc_early_init');
function jm_ltsc_early_init() {

	if( is_admin() ) {

  		require( JM_LTSC_CLASS_DIR . 'admin/options.class.php' );	
  		require( JM_LTSC_CLASS_DIR . 'admin/init.class.php' );
		require( JM_LTSC_CLASS_DIR . 'admin/tinymce.class.php' ); 

		$JM_LTSC_Options = JM_LTSC_Options::GetInstance();
		$JM_LTSC_Init    = JM_LTSC_Init::GetInstance();
		$JM_LTSC_Tinymce = JM_LTSC_Tinymce::GetInstance();

		$JM_LTSC_Options->init();
		$JM_LTSC_Init->init();
		$JM_LTSC_Tinymce->init();

	}
	
	require( JM_LTSC_CLASS_DIR . 'authorize.class.php' );  
	require( JM_LTSC_CLASS_DIR . 'shortcode.class.php' );

	$JM_LTSC_Shortcode = JM_LTSC_Shortcode::GetInstance();
	$JM_LTSC_Shortcode->init();
		
	// allows to use shortcode as widget	
	add_filter('widget_text', 'do_shortcode', 11);

	// languages
	load_plugin_textdomain( 'jm-ltsc', false, JM_LTSC_LANG_DIR );

}

// On activation
register_activation_hook( __FILE__, array( 'JM_LTSC_Init', 'activate' ) );