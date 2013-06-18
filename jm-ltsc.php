<?php
/*Plugin Name: JM Last Twit Shortcode
Plugin URI: http://tweetPress.fr
Description: Meant to add your last tweet with the lattest API way
Author: Julien Maury
Author URI: http://tweetPress.fr
Version: 3.2.1
License: GPL2++
*/

// New sources => http://clark-technet.com/2013/03/updated-wordpress-twitter-functions#comment-148551 (slightly modified)
// and https://dev.twitter.com/docs/platform-objects/entities


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

//function created by @clarktechnet
if(!function_exists('jc_twitter_format')) {
function jc_twitter_format( $raw_text, $tweet = NULL ) {
    // first set output to the value we received when calling this function
    $output = $raw_text;
 
    // create xhtml safe text (mostly to be safe of ampersands)
    $output = htmlentities( html_entity_decode( $raw_text, ENT_NOQUOTES, 'UTF-8' ), ENT_NOQUOTES, 'UTF-8' );
 
    // parse urls
    if ( $tweet == NULL ) {
        // for regular strings, just create <a> tags for each url
        $pattern = '/([A-Za-z]+:\/\/[A-Za-z0-9-_]+\.[A-Za-z0-9-_:%&\?\/.=]+)/i';
        $replacement = '<a href="${1}" rel="external">${1}</a>';
        $output = preg_replace( $pattern, $replacement, $output );
    } else {
        // for tweets, let's extract the urls from the entities object
        foreach ( $tweet->entities->urls as $url ) {
            $old_url = $url->url;
            $expanded_url = ( empty( $url->expanded_url ) ) ? $url->url : $url->expanded_url;
            $display_url = ( empty( $url->display_url ) ) ? $url->url : $url->display_url;
            $replacement = '<a href="' . $expanded_url . '" rel="external">' . $display_url . '</a>';
            $output = str_replace( $old_url, $replacement, $output );
        }
 
        // let's extract the hashtags from the entities object
        foreach ( $tweet->entities->hashtags as $hashtags ) {
            $hashtag = '#' . $hashtags->text;
            $replacement = '<a href="http://twitter.com/search?q=%23' . $hashtags->text . '" rel="external">' . $hashtag . '</a>';
            $output = str_ireplace( $hashtag, $replacement, $output );
        }
 
        // let's extract the usernames from the entities object
        foreach ( $tweet->entities->user_mentions as $user_mentions ) {
            $username = '@' . $user_mentions->screen_name;
            $replacement = '<a href="http://twitter.com/' . $user_mentions->screen_name . '" rel="external" title="' . $user_mentions->name . ''.__('on Twitter','jm-ltsc').'">' . $username . '</a>';
            $output = str_ireplace( $username, $replacement, $output );
        }
 
        // if we have media attached, let's extract those from the entities as well
        if ( isset( $tweet->entities->media ) ) {
            foreach ( $tweet->entities->media as $media ) {
                $old_url = $media->url;
                $replacement = '<a href="' . $media->expanded_url . '" rel="external" class="twitter-media" data-media="' . $media->media_url . '">' . $media->display_url . '</a>';
                $output = str_replace( $old_url, $replacement, $output );
            }
        }
    }
 
    return $output;
	}
}




/*
* OUTPUT
*/

if(!function_exists('jm_ltsc_output')) {
	function jm_ltsc_output( $atts ) {
		extract(shortcode_atts(array(
		'username'   => '',
		'tl' => 'user_timeline',
		'count'=> 1,
		'show_twittar' => 'off'
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
		require_once(plugin_dir_path( __FILE__ ) .'admin/libs/tmhOAuth.php');
		require_once(plugin_dir_path( __FILE__ ) .'admin/libs/tmhUtilities.php');

		//query

		$tmhOAuth = new tmhOAuth(array(
		'consumer_key'    => $consumer_key,
		'consumer_secret' => $consumer_secret,
		'user_token'      => $user_token,
		'user_secret'     => $user_secret
		));

		$code = $tmhOAuth->request('GET', $tmhOAuth->url('1.1/statuses/'.$tl), 
		array(
		'include_entities' => '1',
		'screen_name'      => $username,
		'count'			   => $count	
		));
		
		if ( empty( $code ) ) {
			return __('There is no tweet to display yet.','jm-ltsc');
		}

		//set our transient if there's no recent copy
		$transient = "_last_twit";
		$i = 1;
		$incache = get_site_transient( $transient );
		
		if ( false !== $incache ) {
		$output = $incache . '<!--'. __('JM Last Twit Shortcode - cache','jm-ltsc') .'-->';
		}
         
		//output
		switch ($code) {
		case '200':
		case '304':				
				$data = json_decode( $tmhOAuth->response['response'] );
				 $output = "<ul>\r\n";
						while ( $i <= $count ) {
							//Assign feed to $feed
							if ( isset( $data[$i - 1] ) ) {
								$feed = jc_twitter_format( $data[$i - 1]->text, $data[$i - 1] );
								$id_str = $data[$i - 1]->id_str;
								$twittar = '';
								if ( $show_twittar == 'on') $twittar = '<img width="24" height="24" src="'.$data[$i - 1]->user->profile_image_url.'" alt=@"'.$data[$i - 1]->user->screen_name.'" />'; 
								$output .= "<li class='tweetfeed'>" . $twittar ." ". $feed . " - <em>\r\n<a href='http://twitter.com/$username/status/$id_str'>" . human_time_diff( strtotime( $data[$i - 1]->created_at ), current_time( 'timestamp' ) ) . " " . __( 'ago', 'jm-ltsc' ) . "</a></em></li>\r\n";
							}
							$i++;
						}
				 
						$output .="</ul>";
				set_site_transient( $transient, $output, $opts['time']*60 );
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
	<input id="twitAccount" type="text" name="jm_ltsc[twitAccount]" class="paDemi" value="<?php echo jm_ltsc_remove_at($opts['twitAccount']); ?>" />
	</p>
	<p>
	<label for="consumerKey"><?php _e('Provide your application consumer key', 'jm-ltsc'); ?> :</label><br />
	<input id="consumerKey" type="text" name="jm_ltsc[consumerKey]" class="paDemi" size="70" value="<?php echo $opts['consumerKey']; ?>" />
	</p>
	<p>
	<label for="consumerSecret"><?php _e('Provide your application consumer secret', 'jm-ltsc'); ?> :</label><br />
	<input id="consumerSecret" type="text" name="jm_ltsc[consumerSecret]" class="paDemi" size="70" value="<?php echo $opts['consumerSecret']; ?>" />
	</p>
	<p>
	<label for="oauthToken"><?php _e('Provide your oAuth Token', 'jm-ltsc'); ?> :</label><br />
	<input id="oauthToken" type="text" name="jm_ltsc[oauthToken]" class="paDemi" size="70" value="<?php echo $opts['oauthToken']; ?>" />
	</p>
	<p>
	<label for="oauthToken_secret"><?php _e('Provide your oAuth Token Secret', 'jm-ltsc'); ?> :</label><br />
	<input id="oauthToken_secret" type="text" name="jm_ltsc[oauthToken_secret]" class="paDemi" size="70" value="<?php echo $opts['oauthToken_secret']; ?>" />
	</p>
	<p>
	<label for="time"><?php _e('Set expired time for transient (30 min at least)', 'jm-ltsc'); ?> :</label><br />
	<input id="time" type="number" min="30" name="jm_ltsc[time]" class="paDemi" size="70" value="<?php echo $opts['time']; ?>" />
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
	</ul>
	</div>
	<h2><?php _e('Useful links', 'jm-ltsc') ?></h2>
	<ul>
	<li class="inbl"><a class="button normal redwp" target="_blank" href="http://wordpress.org/support/view/plugin-reviews/jm-last-twit-shortcode"><?php _e('Rate the plugin on WordPress.org', 'jm-ltsc') ?></a></li>
	<li class="inbl"><a class="button normal twitblue" target="_blank" href="<?php _e('https://twitter.com/intent/tweet?source=webclient&amp;hastags=WordPress,Plugin&amp;text=JM%20Last%20Twit%20%20Shortcode%20a%20great%20WordPress%20plugin%20to%20get%20your%20last%20tweet%20Try%20it!&amp;url=http://wordpress.org/extend/plugins/jm-last-twit-shortcode/&amp;related=TweetPressFr&amp;via=TweetPressFr','jm-ltsc'); ?>"><?php _e('Tweet it', 'jm-ltsc') ?></a></li>      
	<li class="inbl"><a class="button normal paypal" target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=tweetpressfr%40gmail%2ecom&item_name=JM%20Last%20Twit%20Shortcode&no_shipping=0&no_note=1&tax=0&currency_code=EUR&bn=PP%2dDonationsBF&charset=UTF%2d8"><?php _e('Donate to this plugin', 'jm-ltsc'); ?></a></li>    
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


