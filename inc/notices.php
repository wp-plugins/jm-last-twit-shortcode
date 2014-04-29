<?php
defined( 'ABSPATH' ) or	die( 'No !' );
/** PHP version checking
*/

add_action( 'admin_init', 'jm_ltsc_check_version' );
function jm_ltsc_check_version()
{
	// This is where you set you needs
	$mandatory = array(	'PluginName'=>'JM Last Twit Shortcode', 
						'PHP'=>'5.3',
						'Function exists' => 'curl_init'
					);

	// Avoid Notice error
	$errors = array();

	// loop the mandatory things
	foreach( $mandatory as $what => $how ) {
		switch( $what ) {
			case 'PHP':
					if( version_compare( phpversion(), $how ) < 0 )
					{
						$errors[$what] = $how;
					}
				break;
			case 'Function exists':
					if( !function_exists( $how ) )
					{
						$errors[$what] = $how;
					}
				break;
		}
	}

	// Add a filter for devs
	$errors = apply_filters( 'validate_errors', $errors, $mandatory['PluginName'] );

	// We got errors!
	if( !empty( $errors ) )
	{
		global $current_user;

		// We add the plugin name for late use
		$errors['PluginName'] = $mandatory['PluginName'];

		// Set a transient with these errors
		set_transient( 'jm_ltsc_disabled_notice' . $current_user->ID, $errors );

		// Remove the activate flag
		unset( $_GET['activate'] );

		// Deactivate this plugin
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}
}

add_action( 'admin_notices', 'jm_ltsc_disabled_notice' );
function jm_ltsc_disabled_notice()
{
	global $current_user;
	// We got errors!
	if( $errors = get_transient( 'jm_ltsc_disabled_notice' . $current_user->ID ) )
	{
		// Remove the transient
		delete_transient( 'jm_ltsc_disabled_notice' . $current_user->ID );

		// Pop the plugin name
		$plugin_name = array_pop( $errors );

		// Begin the buffer output
		$error = '<ul>';

		// Loop on each error, you can change the "i18n domain" here -> jm_ltsc (i would like to avoid this)
		foreach( $errors as $what => $how) {
			$error .= '<li>'.sprintf( __( '&middot; Requires %s: <code>%s</code>', 'jm_ltsc' ), $what, $how ).'</li>';
		}

		// End the buffer output
		$error .= '</ul>';

		// Echo the output using a WordPress string (no i18n needed)
		echo '<div class="error"><p>' . sprintf( __( 'The plugin <code>%s</code> has been <strong>deactivated</strong> due to an error: %s' ), $plugin_name, $error ) . '</p></div>';
	}
}