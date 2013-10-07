<?php
/*Plugin Name: JM Last Twit Shortcode
Plugin URI: http://support.tweetPress.fr
Description: Meant to add your last tweet with the lattest API way
Author: Julien Maury
Author URI: http://tweetPress.fr
Version: 3.3.6
License: GPL2++
*/

// New sources => http://clark-technet.com/2013/03/updated-wordpress-twitter-functions#comment-148551 (slightly modified)
// and https://dev.twitter.com/docs/platform-objects/entities




/** PHP version checking
	*/
if (version_compare(PHP_VERSION, '5.3', '<=')) {
	add_action( 'admin_notices', 'jm_ltsc_admin_notice' );
	function jm_ltsc_admin_notice(){
		global $current_screen;
		if ( $current_screen->parent_base == 'plugins' ) echo '<div class="error" style="padding:1em;">'.sprintf('JM Last Twit Shortcode requires PHP 5.3 or higher. You’re still on %s.',PHP_VERSION).'</div>';
		if ( class_exists('tmhOAuth') ) echo '<div class="error" style="padding:1em;">'.__('Class tmhOAuth is already running in your website, this could break the plugin JMLTSC !','jm-ltsc').'</div>';
	}
}	

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
* CONNECT
*/

add_action( 'init', 'jm_tc_connect_twitter_api' );// make the API connection
if(!function_exists('jm_tc_connect_twitter_api')) {
	function jm_tc_connect_twitter_api() {
		//config
		global $tcTmhOAuth;


		//config 
		$opts = jm_ltsc_get_options(); 
		$consumer_key = $opts['consumerKey'];
		$consumer_secret = $opts['consumerSecret'];
		$user_token = $opts['oauthToken'];
		$user_secret = $opts['oauthToken_secret'];	

		//libs
		if(!class_exists('tmhOAuth') ) require_once(plugin_dir_path( __FILE__ ) .'admin/libs/vendor/autoload.php');//composer yeah !
		
		//connection
		$tcTmhOAuth = new tmhOAuth(array(
		'consumer_key' => $consumer_key,
		'consumer_secret' => $consumer_secret,
		'user_token' => $user_token,
		'user_secret' => $user_secret
		));
	}
}


/*
* OUTPUT
*/

if(!function_exists('jm_ltsc_output')) {
	function jm_ltsc_output( $atts ) {
		extract(shortcode_atts(array(
		'username'     	     => '',
		'cache'         	 => 1800,
		'count'       	     => 1,
		'include_rts'  		 => 'true',	
		'exclude_replies'	 => 'false'	
		), $atts));

		
		//add some flexibility, you can add whatever account
		$opts = jm_ltsc_get_options(); 
		if ($username == '') $username = $opts['twitAccount'];

		
		//set our transient if there's no recent copy
		$transient = $username."_last_twit_shortcode";
		$i = 1;
		$incache = get_site_transient( $transient );
		
		if( !$incache ) {
			
			//connection to the API
			global $tcTmhOAuth;
			

			$code = $tcTmhOAuth->request('GET', $tcTmhOAuth->url('1.1/statuses/user_timeline'), 
			array(
			'include_entities' => '1',//actually entities are always loaded with tweets in API 1.1 ;)
			'screen_name'      => $username,
			'count'			   => $count,
			'include_rts'	   => $include_rts,
			'exclude_replies'  => $exclude_replies				
			));    
			
			
			//output
			switch ($code) {
			case '200':
			case '304':				
				$data = json_decode( $tcTmhOAuth->response['response'] );
				$output = "<ul class='tweetfeed'>";
				while ( $i <= $count ) {
					//Assign feed to $feed
					if ( isset( $data[$i - 1] ) ) {
						$feed = jc_twitter_format( $data[$i - 1]->text, $data[$i - 1] );
						$id_str = $data[$i - 1]->id_str;
						$screen_name = $data[$i - 1]->user->screen_name;
						$date = $data[$i - 1]->created_at;
						$date_format = 'j/m/y - '.get_option('time_format');
						$profile_image_url = $data[$i - 1]->user->profile_image_url;
						$twittar = '<img class="tweet-twittar" width="36" height="36" src="'.$profile_image_url.'" alt="@'.$screen_name .'" />'; 								
						$output .= "<li>" . $twittar ."<span class='tweet-name'><a class='' href='http://twitter.com/".$screen_name."'>".$username."</a></span><span class='tweet-screen-name'>@<a class='' href='http://twitter.com/".$screen_name."'>".$screen_name."</a></span> <p class='tweet-content'>".$feed . "</p><em><span class='tweet-timestamp'><a href='http://twitter.com/".$username."/status/".$id_str."'><span class='time-date small'>".date( $date_format, strtotime($date))."</span></a> <span class='tweet-timediff'>" .human_time_diff( strtotime( $date ), current_time( 'timestamp', 1 ) ).__(' ago','jm-ltsc')."</span></span> </em><span class='intent-meta'><a href='http://twitter.com/intent/tweet?in_reply_to=".$id_str."'><span class='tweet-reply'>". __( 'Reply', 'jm-ltsc' ) ."</span></a> <a href='http://twitter.com/intent/retweet?tweet_id=".$id_str."'> <span class='tweet-retweet'>". __( 'Retweet', 'jm-ltsc' ) ."</span></a> <a href='http://twitter.com/intent/favorite?tweet_id=".$id_str."'><span class='tweet-favorite'>". __( 'Favorite', 'jm-ltsc' ) ."</span></a></span></li>";
					}
					$i++;
				}
				
				$output .="</ul>";
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
			
			set_site_transient( $transient, $output, $cache );
			
		} else {
			return $incache . '<!--'. __('JM Last Twit Shortcode - cache','jm-ltsc') .'-->';
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
	$opts = jm_ltsc_get_options(); 
		if( ('post.php' == $hook_suffix || 'post-new.php' == $hook_suffix ) && $opts['twitQuickTags'] == 'yes') // only on post edit and if user wants it
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
	$settings_link = '<a href="' . admin_url( 'admin.php?page=jm_ltsc_options' ) . '">' . __("Settings") . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}


//The add_action to add onto the WordPress menu.
add_action('admin_menu', 'jm_ltsc_add_options');
function jm_ltsc_add_options() {
	$ltscpage = add_menu_page( 'JM Last Twit Options', 'JM LTSC',  'manage_options', 'jm_ltsc_options', 'jm_ltsc_options_page', plugins_url('admin/img/bird_orange_16.png', __FILE__),98);
	register_setting( 'jm-ltsc', 'jm_ltsc', 'jm_ltsc_sanitize' );
	add_action( 'load-'.$ltscpage, 'jm_ltsc_load_admin_css' );
}

function jm_ltsc_load_admin_css() {	
	add_action( 'admin_enqueue_scripts','jm_ltsc_admin_css' );
}

function jm_ltsc_admin_css() {  
	wp_enqueue_style( 'jm-style-ltw', plugins_url('admin/jm-ltsc-admin-style.css', __FILE__) ); 
} 


// Settings page
function jm_ltsc_options_page() {
	$opts = jm_ltsc_get_options();
	?>
	<div class="jm-ltsc" id="pluginwrapper">
			<!-- column-1 -->
			<div class="column column-1">
				<aside class="header">
					<div class="box">
						<h1><?php _e('JM Last Twit Shortcode', 'jm-ltsc'); ?></h1>
						<h2 class="white"><?php _e('Get your last tweets <br />the Twitter 1.1 way</br> with a simple shortcode','jm-ltsc');?></h2>
						<p class="plugin-desc"><?php _e('To grab your feed you need to authenticate in the new version of Twitter API 1.1', 'jm-ltsc'); ?></p>
						<p class="plugin-desc white"><?php _e('With this plugin you can display any Twitter timeline with a simple a shortcode', 'jm-ltsc'); ?></p>
					</div>	
				</aside>
			</div><!-- /.column-1 -->
			
			<!-- div column-2 -->
			<div class="column column-2">
	
				<form class="jm-ltsc-form" method="post" action="options.php">
				<?php settings_fields('jm-ltsc'); ?>
					<section class="postbox">
					<h1 class="hndle"><?php _e('Options', 'jm-ltsc'); ?></h1>	
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
						<label for="twitQuickTags"><?php _e('Do you want to add Quicktags (buttons in HTML editor) in post edit?', 'jm-ltsc'); ?> :</label>
						<select class="styled-select" id="twitQuickTags" name="jm_ltsc[twitQuickTags]">
							<option value="yes" <?php echo $opts['twitQuickTags'] == 'yes' ? 'selected="selected"' : ''; ?> ><?php _e('Yes', 'jm-tc'); ?></option>
							<option value="no" <?php echo $opts['twitQuickTags'] == 'no' ? 'selected="selected"' : ''; ?> ><?php _e('No', 'jm-tc'); ?></option>
						</select>
						<br /><em>(<?php _e('Default is yes', 'jm-ltsc'); ?>)</em>
					</p>

					<?php submit_button(null, 'primary right', '_submit'); ?>

					</section>
				</form>
				
			<section class="postbox">
				<h1 class="hndle"><?php _e('How to', 'jm-ltsc'); ?></h1>
				<ul class="howtouse"> 
					<li><?php _e('Basic use, no option => whether it is in a post or in a Text widget use: <code>[jmlt]</code>', 'jm-ltsc'); ?> </code></li>
					<li><?php _e('Different username => whether it is in a post or in a Text widget use: <code>[jmlt username="rihanna"]</code>', 'jm-ltsc'); ?></li>
					<li><?php _e('More than 1 tweet, e.g 5 => whether it is in a post or in a Text widget use: <code>[jmlt count="5"]</code>', 'jm-ltsc'); ?></li>
					<li><?php _e('Exclude replies => whether it is in a post or in a Text widget use: <code>[jmlt exclude_replies="true"]</code>', 'jm-ltsc'); ?></li>
					<li><?php _e('Exclude RTs => whether it is in a post or in a Text widget use: <code>[jmlt include_rts="false"]</code>', 'jm-ltsc'); ?></li>			
					<li><?php _e('Set cache duration => whether it is in a post or in a Text widget use: <code>[jmlt cache="3600"]</code> to put tweets in cache for 1 hour', 'jm-ltsc'); ?></li>			
					<li><?php _e('To reuse tokens you set to use the plugin => just write in your templates and codes :<code>global $tcTmhOAuth;</code>', 'jm-ltsc'); ?></li>	
				</ul>
			</section>
				
				
			<section class="postbox">
				<h1 class="hndle"><?php _e('Styles', 'jm-ltsc'); ?></h1>	
				<p><?php _e('Plugin displays tweets in an unordered list you can style in your own stylesheet with CSS classes <code>tweet-name {}</code>, <code>.tweet-screen-name {}</code>, <code>.tweet-twittar {}</code>, <code>.tweet-timestamp{}</code>, <code>.time-date{}</code>, <code>.tweet-timediff{}</code>, <code>.intent-meta{}</code>, <code>.tweet-reply{}</code>, <code>.tweet-retweet{}</code>, <code>.tweet-favorite{}</code>. <br />To apply styles to the text of you tweets just us CSS class <code>.tweet-content{}</code>','jm-ltsc');?></p>
			</section>
			
			<section class="postbox">
				<h1 class="hndle"><?php _e('Useful links', 'jm-ltsc'); ?></h1>	
				<ul>
					<li class="inbl"><a class="button normal redwp" target="_blank" href="http://wordpress.org/support/view/plugin-reviews/jm-last-twit-shortcode"><?php _e('Rate the plugin on WordPress.org', 'jm-ltsc') ?></a></li>
					<li class="inbl"><a class="button normal twitblue" target="_blank" href="<?php _e('https://twitter.com/intent/tweet?source=webclient&amp;hastags=WordPress,Plugin&amp;text=JM%20Last%20Twit%20%20Shortcode%20a%20great%20WordPress%20plugin%20to%20get%20your%20last%20tweet%20Try%20it!&amp;url=http://wordpress.org/extend/plugins/jm-last-twit-shortcode/&amp;related=TweetPressFr&amp;via=TweetPressFr','jm-ltsc'); ?>"><?php _e('Tweet it', 'jm-ltsc') ?></a></li>      
					<li class="inbl"><a class="button normal" target="_blank" href="https://twitter.com/intent/user?screen_name=TweetPressFr"><?php _e('follow me on Twitter', 'jm-ltsc'); ?></a></li>       
				</ul>
			</section>
			
			<section class="postbox">
				<h1 class="hndle"><?php _e('About the developer', 'jm-ltsc'); ?></h1>	
				<p>
					<img src="http://www.gravatar.com/avatar/<?php echo md5( 'tweetpressfr'.'@'.'gmail'.'.'.'com' ); ?>" style="float:left;margin-right:10px;"/>
					<strong>Julien Maury</strong><br />
					<?php _e('I am a WordPress Developer, I like to make it simple.', 'jm-ltsc') ?> <br />
					<a href="http://www.tweetpress.fr" target="_blank" title="TweetPress.fr - WordPress and Twitter tips">www.tweetpress.fr</a> - <a href="http://twitter.com/intent/user?screen_name=tweetpressfr" >@TweetPressFR</a><br />
					<a href="http://profiles.wordpress.org/jmlapam/" title="on WordPress.org"><?php _e('My WordPress Profile', 'jm-ltsc') ?></a><br />
				</p>
			</section>

			<section class="postbox">
				<h1 class="hndle"><?php _e('Help me keep this free', 'jm-ltsc'); ?></h1>	
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
				<input type="hidden" name="cmd" value="_s-xclick">
				<input type="hidden" name="hosted_button_id" value="2NBS57W3XG62L">
				<input type="image" src="https://www.paypalobjects.com/fr_FR/FR/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - la solution de paiement en ligne la plus simple et la plus sécurisée !">
				<img alt="" border="0" src="https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif" width="1" height="1">
				</form>
			</section>	

			<section class="postbox">
				<h1 class="hndle"><?php _e('Other plugins you might dig', 'jm-ltsc'); ?></h1>	
				<ul>
					<li><a href="http://wordpress.org/plugins/jm-twitter-cards/">JM Twitter Cards</a></li>
					<li><a href="http://wordpress.org/plugins/jm-html5-and-responsive-gallery/">JM HTML5 and Responsive Gallery</a> - <?php _e('Fix poor native markup for WordPress gallery with some HTML5 markup and add responsive rules.','jm-ltsc');?></li>
					<li><a href="http://wordpress.org/plugins/jm-twit-this-comment/">JM Twit This Comment</a> - <?php _e('Make your comments tweetable','jm-ltsc');?></li>
				</ul>
			</section>	
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
	if ( isset($options['oauthToken']) )
	$new['oauthToken']               = esc_attr(strip_tags( $options['oauthToken'] ));
	if ( isset($options['oauthToken_secret']) )
	$new['oauthToken_secret']        = esc_attr(strip_tags( $options['oauthToken_secret'] ));
	if ( isset($options['twitQuickTags']) )
	$new['twitQuickTags']            = $options['twitQuickTags'] ;
	
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
	'twitQuickTags'            => 'yes'
	);
}

// Retrieve and sanitize options
function jm_ltsc_get_options() {
	$options = get_option( 'jm_ltsc' );
	return array_merge(jm_ltsc_get_default_options(), jm_ltsc_sanitize_options($options));
}


