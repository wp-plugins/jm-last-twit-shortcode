<?php
defined( 'ABSPATH' ) or	die( 'No !' );


// Remove any @ from input value
function jm_ltsc_remove_at($at) { 
	$noat = str_replace('@','',$at);
	return $noat;
}


/* quicktags
* */
add_action( 'admin_enqueue_scripts', 'jm_ltsc_add_quicktags' );
function jm_ltsc_add_quicktags( $hook_suffix ) {
	$opts = jm_ltsc_get_options(); 
		if( ('post.php' == $hook_suffix || 'post-new.php' == $hook_suffix ) && $opts['twitQuickTags'] == 'yes') // only on post edit and if user wants it
			wp_enqueue_script( 'jmltsc_quicktags_js', JM_LTSC_JS_URL.'quicktag.js', array( 'quicktags' ), null, true );
}

/* shortcode in sidebar
* */
if ( !is_admin() ) {
	add_filter('widget_text', 'do_shortcode', 11);
}


// Add a "Settings" link in the plugins list
add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), 'jm_ltsc_settings_action_links', 10, 2 );
function jm_ltsc_settings_action_links( $links, $file ) {
	$settings_link = '<a href="' . admin_url( 'admin.php?page=jm_ltsc_options' ) . '">' . __("Settings") . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}


//The add_action to add onto the WordPress menu.
add_action('admin_menu', 'jm_ltsc_add_options');
function jm_ltsc_add_options() {
	$ltscpage = add_menu_page( 
		'JM Last Twit Options', 
		'JM LTSC',  
		'manage_options', 
		'jm_ltsc_options', 
		'jm_ltsc_options_page', 
		'dashicons-twitter'
		);
	register_setting( 'jm-ltsc', 'jm_ltsc', 'jm_ltsc_sanitize' );
	add_action( 'load-'.$ltscpage, 'jm_ltsc_load_admin_scripts' );
}


function jm_ltsc_load_admin_scripts() {	
	add_action( 'admin_enqueue_scripts','jm_ltsc_admin_scripts' );
}

function jm_ltsc_admin_scripts() {  
	wp_enqueue_style( 'jm-style-ltw', JM_LTSC_CSS_URL.'jm-ltsc-admin-style.css' );
	wp_register_style( 'jm-basic-ltw', JM_LTSC_CSS_URL.'styles-basic.css' ); 
	wp_enqueue_style('jm-basic-ltw'); 
	wp_enqueue_script('jm-tab-ltw', JM_LTSC_JS_URL.'jm-ltsc-admin-tab.js', array('jquery'), '1.0', false);
} 

add_action('wp_enqueue_scripts', 'jm_ltsc_everywhere_scripts');
function jm_ltsc_everywhere_scripts() {  
	wp_register_style( 'jm-basic-ltw', JM_LTSC_CSS_URL.'styles-basic.css' );
}


// Settings page

function jm_ltsc_options_page() {
	$opts = jm_ltsc_get_options(); 
	?>
	<div class="wrap jm_ltsc" id="pluginwrapper">
		<h2 class="dashicons-before dashicons-twitter"><?php _e('JM Last Twit Shortcode', 'jm-ltsc'); ?></h2>
		<?php if ( isset( $_GET['settings-updated'] ) ) echo "<div class='updated'><p>".__('Settings saved.')."</p></div>"; ?>
		<ul id="tabs">

			  <li><a id="tab1"><?php _e('Options', 'jm-ltsc'); ?></a></li>
			  <li><a id="tab2"><?php _e('Styles', 'jm-ltsc'); ?></a></li>
			  <li><a id="tab3"><?php _e('About'); ?></a></li>

		</ul>
		<div class="container" id="tab1C">
			
			<blockquote><?php _e('Get your last tweets <br />the Twitter 1.1 way</br> with a simple shortcode','jm-ltsc');?>
			<br /><?php _e('To grab your feed you need to authenticate in the new version of Twitter API 1.1', 'jm-ltsc'); ?>
			<br /><?php _e('With this plugin you can display any Twitter timeline with a simple a shortcode', 'jm-ltsc'); ?></blockquote>
			
			<?php echo do_shortcode('[jmlt]'); ?>
							
							
			<form class="jm-ltsc-form" method="post" action="options.php">
			<?php settings_fields('jm-ltsc'); ?>
			
				
				<fieldset>
					<legend><?php _e('Options', 'jm-ltsc'); ?></legend>	
					<p>
						<label for="twitAccount"><?php _e('Provide your Twitter username (used by default and without @)', 'jm-ltsc'); ?> :</label>
						<input id="twitAccount" type="text" name="jm_ltsc[twitAccount]" size="50" value="<?php echo jm_ltsc_remove_at($opts['twitAccount']); ?>" />
					</p>
					<p>
						<label for="consumerKey"><?php _e('Provide your application consumer key', 'jm-ltsc'); ?> :</label>
						<input id="consumerKey" type="text" name="jm_ltsc[consumerKey]" size="50" value="<?php echo $opts['consumerKey']; ?>" />
					</p>
					<p>
						<label for="consumerSecret"><?php _e('Provide your application consumer secret', 'jm-ltsc'); ?> :</label>
						<input id="consumerSecret" type="text" name="jm_ltsc[consumerSecret]" size="50" value="<?php echo $opts['consumerSecret']; ?>" />
					</p>
					<p>
						<label for="twitQuickTags"><?php _e('Do you want to add Quicktags (buttons in HTML editor) in post edit?', 'jm-ltsc'); ?> :</label>
						<select class="styled-select" id="twitQuickTags" name="jm_ltsc[twitQuickTags]">
							<option value="yes" <?php echo $opts['twitQuickTags'] == 'yes' ? 'selected="selected"' : ''; ?> ><?php _e('Yes', 'jm-ltsc'); ?></option>
							<option value="no" <?php echo $opts['twitQuickTags'] == 'no' ? 'selected="selected"' : ''; ?> ><?php _e('No', 'jm-ltsc'); ?></option>
						</select>
						<br /><em>(<?php _e('Default is yes', 'jm-ltsc'); ?>)</em>
					</p>

				<?php submit_button(null, 'primary', '_submit'); ?>
				</fieldset>		
			</form>			
		
		</div>
		<div class="container" id="tab2C">

			<p>
				<pre>
add_action('wp_enqueue_scripts', '_use_twitter_ui_for_tweets');
function _use_twitter_ui_for_tweets(){
	wp_enqueue_style('jm-basic-ltw');
}
				</pre>
			</p>

		</div>

		<div class="container" id="tab3C">
			
			<h3><?php _e('About the developer', 'jm-ltsc'); ?></h3>	
			<p>
				<img class="profile" src="http://www.gravatar.com/avatar/<?php echo md5( 'tweetpressfr@gmail.com' ); ?>" width="80" height="80" alt="" />
				<strong>Julien Maury</strong><br />
				<?php _e('I am a WordPress Developer, I like to make it simple.', 'jm-ltsc') ?> <br />
				<a href="http://www.tweetpress.fr" target="_blank" title="TweetPress.fr - WordPress and Twitter tips">www.tweetpress.fr</a> - <a href="http://twitter.com/intent/user?screen_name=tweetpressfr" >@TweetPressFR</a><br />
				<a href="http://profiles.wordpress.org/jmlapam/" title="on WordPress.org"><?php _e('My WordPress Profile', 'jm-ltsc') ?></a><br />
			</p>
			<div class="clear"></div>
			
			<h3><?php _e('Other plugins you might dig', 'jm-ltsc'); ?></h3>	
			<ul>
				<li><a class="button" href="http://wordpress.org/plugins/jm-twitter-cards/">JM Twitter Cards</a></li>
				<li><a class="button" href="http://wordpress.org/plugins/jm-instagram-feed-widget/">JM Instagram Feed Widget</a></li>
				<li><a class="button" href="http://wordpress.org/plugins/jm-twit-this-comment/">JM Twit This Comment</a> - <?php _e('Make your comments tweetable','jm-ltsc');?></li>
			</ul>

			<h3><?php _e('Help me keep this free', 'jm-ltsc'); ?></h3>	
			<p><form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
				<input type="hidden" name="cmd" value="_s-xclick">
				<input type="hidden" name="hosted_button_id" value="STBXACUTMGJRL">
				<input type="image" src="https://www.paypalobjects.com/fr_FR/FR/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - la solution de paiement en ligne la plus simple et la plus sécurisée !">
				<img alt="" border="0" src="https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif" width="1" height="1">
				</form>
				</p>
			
			<h3><?php _e('Useful links', 'jm-ltsc'); ?></h3>	
			<ul>
				<li class="inbl"><a class="button normal redwp" target="_blank" href="http://wordpress.org/support/view/plugin-reviews/jm-last-twit-shortcode"><?php _e('Rate the plugin on WordPress.org', 'jm-ltsc') ?></a></li>
				<li class="inbl"><a class="button normal twitblue" target="_blank" href="<?php _e('https://twitter.com/intent/tweet?source=webclient&amp;hastags=WordPress,Plugin&amp;text=JM%20Last%20Twit%20%20Shortcode%20a%20great%20WordPress%20plugin%20to%20get%20your%20last%20tweet%20Try%20it!&amp;url=http://wordpress.org/extend/plugins/jm-last-twit-shortcode/&amp;related=TweetPressFr&amp;via=TweetPressFr','jm-ltsc'); ?>"><?php _e('Tweet it', 'jm-ltsc') ?></a></li>      
				<li class="inbl"><a class="button normal" target="_blank" href="https://twitter.com/intent/user?screen_name=TweetPressFr"><?php _e('follow me on Twitter', 'jm-ltsc'); ?></a></li>       
			</ul>
		
		</div>
		
	</div>

	<?php
}


/*
* OPTIONS TREATMENT
*/

// Process options when submitted
function jm_ltsc_sanitize($options) {
	return array_merge(jm_ltsc_get_options(), jm_ltsc_sanitize_options($options));
}

// Sanitize options
function jm_ltsc_sanitize_options($options) {
	$new = array();

	if ( !is_array($options) )
	return $new;

	if ( isset($options['twitAccount']) )
	$new['twitAccount']              = esc_attr(strip_tags( jm_ltsc_remove_at($options['twitAccount']) ));
	if ( isset($options['consumerKey']) )
	$new['consumerKey']              = esc_attr(strip_tags( $options['consumerKey'] ));
	if ( isset($options['consumerSecret']) )
	$new['consumerSecret']           = esc_attr(strip_tags( $options['consumerSecret'] ));
	if ( isset($options['twitQuickTags']) )
	$new['twitQuickTags']            = $options['twitQuickTags'] ;
	
	return $new;
}


// Retrieve and sanitize options
function jm_ltsc_get_options() {
	$options = get_option( 'jm_ltsc' );
	return array_merge(jm_ltsc_get_default_options(), jm_ltsc_sanitize_options($options));
}