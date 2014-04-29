<?php
defined( 'ABSPATH' ) or	die( 'No !' );
//tinymce button
function jm_ltsc_add_mce_button() {
	// check user permissions
	if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
		return;
	}
	// check if WYSIWYG is enabled
	if ( 'true' == get_user_option( 'rich_editing' ) ) {
		add_filter( 'mce_external_plugins', 'jm_ltsc_add_tinymce_plugin' );
		add_filter( 'mce_buttons', 'jm_ltsc_register_mce_button' );
	}
}
add_action('admin_head', 'jm_ltsc_add_mce_button');

// Declare script for new button
function jm_ltsc_add_tinymce_plugin( $plugin_array ) {
	$plugin_array['jm_ltsc_mce_button'] = JM_LTSC_JS_URL.'tinymce.js';
	return $plugin_array;
}

// Register new button in the editor
function jm_ltsc_register_mce_button( $buttons ) {
	array_push( $buttons, 'jm_ltsc_mce_button' );
	return $buttons;
}