<?php
defined( 'ABSPATH' ) or	die( 'No !' );


if ( ! class_exists( '_WP_Editors' ) )
    require( ABSPATH . WPINC . '/class-wp-editor.php' );

	
function jm_ltsc_tinymce_plugin_translation() {

	$strings = array(
		'popup_title' 	=> esc_js( __('Insert Twitter Shortcode', 'jm-ltsc' ) ) ,
		'account_input' => esc_js( __('Votre compte Twitter (sans @)', 'jm-ltsc' ) ) ,
		'count_input' 	=> esc_js( __('How many Tweets?', 'jm-ltsc' ) ) ,
		'cache_input'	=> esc_js( __('Cache duration (hours)', 'jm-ltsc' ) ) ,
		'inc_input' 	=> esc_js( __('Include RTs?', 'jm-ltsc' ) ) ,
		'exc_input' 	=> esc_js( __('Exclude replies?', 'jm-ltsc' ) ) ,
	
	);

	$locale = _WP_Editors::$mce_locale;
    $translated = 'tinyMCE.addI18n("' . $locale . '.jm_ltsc_tinymce_plugin", ' . json_encode( $strings ) . ");\n";

    return $translated;
}

$strings = jm_ltsc_tinymce_plugin_translation();