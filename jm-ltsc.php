<?php
/*Plugin Name: JM Last Twit Shortcode
Plugin URI: http://support.tweetPress.fr
Description: Meant to add your last tweet with the lattest API way
Author: Julien Maury
Author URI: http://tweetPress.fr
Version: 5.0
License: GPL2++
*/

defined( 'ABSPATH' ) or die( 'No !' );

define( 'JM_LTSC_VERSION', '5.0' );
define( 'JM_LTSC_DIR', plugin_dir_path( __FILE__ ) );
define( 'JM_LTSC_URL', plugin_dir_url( __FILE__ ) );

define( 'JM_LTSC_CSS_URL', JM_LTSC_URL . 'assets/css/' );
define( 'JM_LTSC_JS_URL', JM_LTSC_URL. 'assets/js/' );
define( 'JM_LTSC_IMG_URL', JM_LTSC_URL . 'assets/img/' );


define( 'JM_LTSC_LANG_DIR', dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
define( 'JM_LTSC_SLUG_NAME', 'jm-ltsc' );

// Function for easy load files
function _jm_ltsc_load_files( $dir, $files, $suffix = '' ) {
	foreach ( $files as $file ) {
		if ( is_file( $dir . $file . '.' . $suffix . '.php' ) ) {
			require_once( $dir . $file . '.' . $suffix . '.php' );
		}
	}
}

// Call modules
if ( is_admin() ) {
	_jm_ltsc_load_files( JM_LTSC_DIR . 'classes/admin/', array( 'options', 'init', 'tinymce' ), 'class' );
}

_jm_ltsc_load_files( JM_LTSC_DIR . 'classes/', array( 'shortcode', 'authorize' ), 'class' );

add_action( 'plugins_loaded', '_jm_ltsc_early_init' );
function _jm_ltsc_early_init() {

	if ( is_admin() ) {

		$JM_LTSC_Options = TokenToMe\wp_shortcodes\Options::_get_instance();
		$JM_LTSC_Init    = TokenToMe\wp_shortcodes\Init::_get_instance();
		$JM_LTSC_Tinymce = TokenToMe\wp_shortcodes\Tinymce::_get_instance();

		$JM_LTSC_Options->init();
		$JM_LTSC_Init->init();
		$JM_LTSC_Tinymce->init();

	}

	$JM_LTSC_Shortcode = TokenToMe\wp_shortcodes\WP_Twitter_Shortcode::_get_instance();
	$JM_LTSC_Shortcode->init();

	// allows to use shortcode as widget
	add_filter( 'widget_text', 'do_shortcode', 11 );

	// languages
	load_plugin_textdomain( 'jm-ltsc', false, JM_LTSC_LANG_DIR );

}

// On activation
register_activation_hook( __FILE__, array( 'TokenToMe\wp_shortcodes\Init', 'activate' ) );