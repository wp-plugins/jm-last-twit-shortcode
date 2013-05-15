<?php
/*Plugin Name: JM Last Twit Shortcode
Plugin URI: http://tweetPress.fr
Description: Meant to add your last tweet with the lattest API way
Author: Julien Maury
Author URI: http://tweetPress.fr
Version: 3.1.8
License: GPL2++
*/

//Source : freely inspired by https://github.com/NOEinteractive/twitterapi1.1



// Plugin activation: create default values if they don't exist
register_activation_hook( __FILE__, 'jm_ltsc_init' );
function jm_ltsc_init() {
	$opts = get_option( 'jm_ltsc' );
	if ( !is_array($opts) )
	update_option('jm_ltsc', jm_ltsc_get_default_options());
}


// Plugin uninstall: delete option
register_uninstall_hook( __FILE__, 'jm_ltsc_uninstall' );
function jm_ltsc_uninstall() {
	delete_option( 'jm_ltsc' );
}


// Remove any @ from input value
function jm_ltsc_remove_at($at) { 
	$noat = str_replace('@','',$at);
	return $noat;
}

/*
* OUTPUT
*/
//set site transient
if(!function_exists('jm_last_twit_transient')) {
	function jm_last_twit_transient($content) {
		$opts = jm_ltsc_get_options();
		set_site_transient( 'last_twit', $content, 60*$opts['time'] );
	}
}

if(!function_exists('jm_ltsc_output')) {
	function jm_ltsc_output( $atts ) {
		extract(shortcode_atts(array(
		'username'   => '',
		'tl' => 'user_timeline'
		), $atts));

		$opts = jm_ltsc_get_options(); 
		
		//add some flexibility, you can add whatever account
		if ($username == '') $username = $opts['twitAccount'];
		
		//add some checking to avoid misuse of shortcode
		$checking = array("user_timeline", "mentions_timeline", "home_timeline");
		if (!in_array($tl, $checking))	$tl = 'user_timeline';
		
		//avoid broken display in particular case which are not allowed by Twitter
		if ( ($tl == 'mentions_timeline' && $username !== $opts['twitAccount']) || ($tl == 'retweets_of_me' && $username !== $opts['twitAccount']) ) $tl = 'user_timeline';


		//config 
		$consumer_key = $opts['consumerKey'];
		$consumer_secret = $opts['consumerSecret'];
		$user_token = $opts['oauthToken'];
		$user_secret = $opts['oauthToken_secret'];
		

		//libs
		require_once(plugin_dir_path( __FILE__ ) .'/admin/libs/tmhOAuth.php');
		require_once(plugin_dir_path( __FILE__ ) .'/admin/libs/tmhUtilities.php');

		//query

		$tmhOAuth = new tmhOAuth(array(
		'consumer_key'    => $consumer_key,
		'consumer_secret' => $consumer_secret,
		'user_token'      => $user_token,
		'user_secret'     => $user_secret
		));

		$timeline = $tmhOAuth->request('GET', $tmhOAuth->url('1.1/statuses/'.$tl), 
		array(
		'include_entities' => '1',
		'screen_name'      => $username
		));
	

		//set our transient if there's no recent copy
		if ( false === get_site_transient( 'last_twit' ) ) jm_last_twit_transient($timeline);
		$code = $tmhOAuth->response['code'];
         
		//output
		switch ($code) {
		case '200':
		case '304':
				$timeline = json_decode($tmhOAuth->response['response'], true);
				foreach ($timeline as $tweet) :
					$entified_tweet = tmhUtilities::entify_with_options($tweet);
					$is_retweet = isset($tweet['retweeted_status']);

					$diff = time() - strtotime($tweet['created_at']);
					if ($diff < 60*60)
					$created_at = floor($diff/60) . __(' minutes ago','jm-ltsc');
					elseif ($diff < 60*60*24)
					$created_at = floor($diff/(60*60)) . __(' hours ago','jm-ltsc');
					else
					$created_at = date('d M', strtotime($tweet['created_at']));

					$permalink  = str_replace(
					array(
					'%screen_name%',
					'%id%',
					'%created_at%'
					),
					array(
					$tweet['user']['screen_name'],
					$tweet['id_str'],
					$created_at,
					),
					'<a href="https://twitter.com/%screen_name%/%id%">%created_at%</a>'
					);
				$output = '<div class="twitstatus" id="'.$tweet['id_str'].'" style="margin-bottom: 1em">
							<span class="twittar"><img src="'. $tweet['user']['profile_image_url'] .'" alt="@'. $tweet['user']['name'] .'" /></span>
							<span class="twitusername"><a href="http://twitter.com/intent/user?screen_name='. $tweet['user']['screen_name'] .'">'. $tweet['user']['name'] .'</a></span><br />
							<span class="twitscreenname"><a href="http://twitter.com/intent/user?screen_name='. $tweet['user']['screen_name'] .'">'. $tweet['user']['screen_name'] .'</a></span><br />
							<span class="twitentitied">'. $entified_tweet .'</span><br />
							<span class="twitpermalink"><small>'. $permalink .'</small></span>
							<span class="twitsource"><small>via '. $tweet['source'].'</small></span>
							<span class="twitintent-meta"><small><a class="in-reply-to" href="http://twitter.com/intent/tweet?in_reply_to='.$tweet['id_str'].'"><span>'.__('Reply','jm-ltsc').'</span></a></small></span>
							<span class="twitintent-meta"><small><a class="retweet" href="http://twitter.com/intent/retweet?tweet_id='.$tweet['id_str'].'"><span>'.__('Retweet','jm-ltsc').'</span></a></small></span>
							<span class="twitintent-meta"><small><a class="favorite" href="http://twitter.com/intent/favorite?tweet_id='.$tweet['id_str'].'"><span>'.__('Favorite','jm-ltsc').'</span></small></a></span>
						   </div>';
				endforeach;		   
			break;	
			
		case '400':
		case '401':
		case '403':
		case '404':
		case '406':
			$output = '<div class="large pa1 error">'.__('Your credentials might be unset or incorrect or username is wrong. In any case this error is not due to Twitter API.','jm-ltsc').'</div>';
			break;
			
		case '429':
			$output = '<div class="large pa1 error">'.__('Rate limits are exceed!','jm-ltsc').'</div>';
			break;
			
		case '500':
		case '502':
		case '503':
			$output = '<div class="large pa1 error">'.__('Twitter is overwhelmed or something bad happened with its API.','jm-ltsc').'</div>';
			break;
		default:
			$output = __('Something is wrong or missing. ','jm-ltsc');
		}
		
		return $output;
		
	} 
	add_shortcode( 'jmlt', 'jm_ltsc_output' );
	add_shortcode( 'widget','jmlt');
}//end of output




/* quicktags
* */
add_action( 'admin_enqueue_scripts', 'jm_ltsc_add_quicktags' );
function jm_ltsc_add_quicktags( $hook_suffix ) {
	if( 'post.php' == $hook_suffix || 'post-new.php' == $hook_suffix ) // only on post edit
	wp_enqueue_script( 'jmltsc_quicktags_js', plugins_url('admin/quicktag.js',__FILE__), array( 'quicktags' ), null, true );
}

/* shortcode in sidebar
* */
if ( !is_admin() ) {
	add_filter('widget_text', 'do_shortcode', 11);
}

/*
* ADMIN OPTION PAGE
*/

// Language support
add_action( 'init', 'jm_ltsc_lang_init' );// replace admin_init with init to get translation on front-end 
function jm_ltsc_lang_init() {
	load_plugin_textdomain( 'jm-ltsc', false, dirname( plugin_basename(__FILE__) ) . '/languages/' );
}

// Add a "Settings" link in the plugins list
add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), 'jm_ltsc_settings_action_links', 10, 2 );
function jm_ltsc_settings_action_links( $links, $file ) {
	$settings_link = '<a href="' . admin_url( 'options-general.php?page=jm_ltsc_options' ) . '">' . __("Settings") . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}


//The add_action to add onto the WordPress menu.
add_action('admin_menu', 'jm_ltsc_add_options');
function jm_ltsc_add_options() {
	$page = add_submenu_page( 'options-general.php', 'JM Last Twit Options', 'JM Last Twit', 'manage_options', 'jmltscoptions', 'jm_ltsc_options_page' );
	register_setting( 'jm-ltsc', 'jm_ltsc', 'jm_ltsc_sanitize' );
	add_action( 'admin_print_styles-' . $page, 'jm_ltsc_admin_css' );
	add_action( 'admin_head-' . $page, 'jm_ltsc_screen_icon' );
}


// Add styles the WordPress Way >> http://codex.wordpress.org/Function_Reference/wp_enqueue_style#Load_stylesheet_only_on_a_plugin.27s_options_page
function jm_ltsc_admin_css() {  
	wp_enqueue_style( 'jm-style-ltw', plugins_url('admin/jm-ltsc-admin-style.css', __FILE__)); 
} 
// Add screen icon
function jm_ltsc_screen_icon() {
	?>
	<style type="text/css">
	#icon-jmltsc {
		background: url(<?php echo plugins_url('admin/icons/bird_gray_32.png', __FILE__); ?>) no-repeat 50% 50%;
	}
	</style>
	<?php
}

// Settings page
function jm_ltsc_options_page() {
	$opts = jm_ltsc_get_options();
	?>
	<div id="jm-ltsc">
	<span id="icon-jmltsc" class="icon32"></span>
	<h1><?php _e('JM Last Twit Shortcode Options', 'jm-ltsc'); ?></h1>
	<div class="form-like">
	<p><?php _e('This plugin allows you to get your last Tweet with the last Twitter API (1.1). Pretty useful because <strong>Twitter API 1.0 is to cease functioning in june 2013</strong>.', 'jm-ltsc'); ?></p>
	<h2><?php _e('Before', 'jm-ltsc'); ?></h2>
	<p><?php _e('Do not forget to go to', 'jm-ltsc'); ?> <a href="https://dev.twitter.com/apps/" target="_blank">dev.twitter.com</a> <?php _e('to create your application <strong>before anything</strong> cause you might forget get it after. In any case you will need token to proceed.','jm-ltsc'); ?></p>

	</div>
	
	<form class="jm-ltsc-form" method="post" action="options.php">
	<?php settings_fields('jm-ltsc'); ?>
	<fieldset>

	<h3><?php _e('Options', 'jm-ltsc'); ?></h3>
	<p>
	<label for="twitAccount"><?php _e('Provide your Twitter username (used by default and without @)', 'jm-ltsc'); ?> :</label>
	<input id="twitAccount" type="text" name="jm_ltsc[twitAccount]" value="<?php echo jm_ltsc_remove_at($opts['twitAccount']); ?>" />
	</p>
	<p>
	<label for="consumerKey"><?php _e('Provide your application consumer key', 'jm-ltsc'); ?> :</label><br />
	<input id="consumerKey" type="text" name="jm_ltsc[consumerKey]" size="70" value="<?php echo $opts['consumerKey']; ?>" />
	</p>
	<p>
	<label for="consumerSecret"><?php _e('Provide your application consumer secret', 'jm-ltsc'); ?> :</label><br />
	<input id="consumerSecret" type="text" name="jm_ltsc[consumerSecret]" size="70" value="<?php echo $opts['consumerSecret']; ?>" />
	</p>
	<p>
	<label for="oauthToken"><?php _e('Provide your oAuth Token', 'jm-ltsc'); ?> :</label><br />
	<input id="oauthToken" type="text" name="jm_ltsc[oauthToken]" size="70" value="<?php echo $opts['oauthToken']; ?>" />
	</p>
	<p>
	<label for="oauthToken_secret"><?php _e('Provide your oAuth Token Secret', 'jm-ltsc'); ?> :</label><br />
	<input id="oauthToken_secret" type="text" name="jm_ltsc[oauthToken_secret]" size="70" value="<?php echo $opts['oauthToken_secret']; ?>" />
	</p>
	<p>
	<label for="time"><?php _e('Set expired time for transient (30 min at least)', 'jm-ltsc'); ?> :</label><br />
	<input id="time" type="number" min="30" name="jm_ltsc[time]" size="70" value="<?php echo $opts['time']; ?>" />
	<br /><em><?php _e('*This is the time in the course of which your tweet will be stored. This allows us to limit server requests.', 'jm-ltsc'); ?></em>
	</p>


	<?php submit_button(null, 'primary right', '_submit'); ?>

	</fieldset>
	</form>

	<div class="form-like">
	<h2><?php _e('How to', 'jm-ltsc') ?></h2>
	<ul class="instructions">
	<li><?php _e('Really easy, just put <strong>[jmlt]</strong> in your posts.','jm-ltsc');?></li>
	<li><?php _e('To change Twitter Acount in a post, just put <strong>[jmlt username="twitter"]</strong> and you will get tweets by @twitter','jm-ltsc');?></li>
	<li><?php _e('You can even change timeline, e.g <strong>[jmlt tl="mentions_timeline"]</strong> will display last mention of your Twitter account. Default is user_timeline. Other options are retweets_of_me and home_timeline.','jm-ltsc');?></li>
	<li><?php _e('Use quicktags buttons in HTML editor if you are not sur of how to use shortcode or if you just want to spare time.','jm-ltsc');?></li>
	<li> <?php _e('To use the shortcode in templates, just use <em>echo apply_filters("the_content","[jmlt]")</em>','jm-ltsc'); ?></li>    
	<li> <?php _e('To use the shortcode in text widgets, just use shortcode like you do in posts.','jm-ltsc'); ?></li>    
	<li><div class="error"> <?php _e('Do not try to display mentions or retweets from other accounts from yours. This logically impossible !','jm-ltsc'); ?></div></li> 
	<li> <?php _e('To add your own style, just use these CSS classes in your main stylesheet','jm-ltsc');?></li>
	</ul>
	<div class="updated">
	<pre>
	.twitstatus {}
	.twittar  {}
	.twitusername  {}
	.twitscreenname  {}
	.twitentitied  {}
	.twitpermalink  {}
	.twitsource  {}
	.twitintent-meta  {}
	.twitintent-meta  {}
	.twitintent-meta  {}
	</pre>
	</div>
	</div>
	<h2><?php _e('Useful links', 'jm-ltsc') ?></h2>
	<ul>
	<li class="inbl"><a class="button normal redwp" target="_blank" href="http://wordpress.org/support/view/plugin-reviews/jm-last-twit-shortcode"><?php _e('Rate the plugin on WordPress.org', 'jm-ltsc') ?></a></li>
	<li class="inbl"><a class="button normal twitblue" target="_blank" href="<?php _e('https://twitter.com/intent/tweet?source=webclient&amp;hastags=WordPress,Plugin&amp;text=JM%20Last%20Twit%20%20Shortcode%20a%20great%20WordPress%20plugin%20to%20get%20your%20last%20tweet%20Try%20it!&amp;url=http://wordpress.org/extend/plugins/jm-last-twit-shortcode/&amp;related=TweetPressFr&amp;via=TweetPressFr','jm-ltsc'); ?>"><?php _e('Tweet it', 'jm-ltsc') ?></a></li>      
	<li class="inbl"><a class="button normal paypal" target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=jmlapam%40gmail%2ecom&item_name=JM%20Last%20Twit%20Shortcode&no_shipping=0&no_note=1&tax=0&currency_code=EUR&bn=PP%2dDonationsBF&charset=UTF%2d8"><?php _e('Donate to this plugin', 'jm-ltsc'); ?></a></li>    
	<li class="inbl"><a class="button normal" target="_blank" href="https://twitter.com/intent/user?screen_name=TweetPressFr"><?php _e('follow me on Twitter', 'jm-ltsc'); ?></a></li>       
	</ul>
	
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
	if ( isset($options['oauthToken']) )
	$new['oauthToken']               = esc_attr(strip_tags( $options['oauthToken'] ));
	if ( isset($options['oauthToken_secret']) )
	$new['oauthToken_secret']        = esc_attr(strip_tags( $options['oauthToken_secret'] ));
	if ( isset($options['time']) )
	$new['time']                     = (int) $options['time'];// because it comes from an input type number
	return $new;
}

// Return default options
function jm_ltsc_get_default_options() {
	return array(
	'twitAccount'              => 'TweetPressFr',
	'consumerKey'              => __('replace with your keys - required', 'jm-ltsc'),
	'consumerSecret'           => __('replace with your keys - required', 'jm-ltsc'),
	'oauthToken'               => __('replace with your keys - required', 'jm-ltsc'),
	'oauthToken_secret'        => __('replace with your keys - required', 'jm-ltsc'),
	'time'                     => 30
	);
}

// Retrieve and sanitize options
function jm_ltsc_get_options() {
	$options = get_option( 'jm_ltsc' );
	return array_merge(jm_ltsc_get_default_options(), jm_ltsc_sanitize_options($options));
}


