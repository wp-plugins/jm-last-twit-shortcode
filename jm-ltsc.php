<?php
/*Plugin Name: JM Last Twit Shortcode
Plugin URI: http://tweetPress.fr
Description: Meant to add your last tweet with the lattest API way
Author: Julien Maury
Author URI: http://tweetPress.fr
Version: 3.1.5
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

/*
* LINKIFY
*/

if(!function_exists('parseTweets')) {
function parseTweets($text) {

$text = preg_replace('#http://[a-z0-9._/-]+#i', '<a target="_blank" href="$0">$0</a>', $text); //Link
$text = preg_replace('#@([a-z0-9_]+)#i', '@<a target="_blank" href="http://twitter.com/intent/user?screen_name=$1">$1</a>', $text); //accounts
$text = preg_replace('# \#([a-z0-9_-]+)#i', ' #<a target="_blank" href="http://search.twitter.com/search?q=%23$1">$1</a>', $text); //Hashtags
$text = preg_replace('#https://[a-z0-9._/-]+#i', '<a target="_blank" href="$0">$0</a>', $text); //Links
return $text;
}

}

/*
* OUTPUT
*/


if(!function_exists('jm_ltsc_output')) {
function jm_ltsc_output( $atts ) {
extract(shortcode_atts(array(
'timeline' => 'user_timeline'               
), $atts));

$opts = jm_ltsc_get_options(); 

/* required parameters for our query */
$consumer_key = $opts['consumerKey']; // application consumer key
$consumer_secret = $opts['consumerSecret']; // application consumer secret
$oauth_token = $opts['oauthToken']; // oAuth Token
$oauth_token_secret = $opts['tokenSecret']; // oAuth Token Secret

//libs
require_once(plugin_dir_path( __FILE__ ) .'/admin/libs/twitteroauth.php');

//query
$connection = new TwitterOAuth($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret);
$connection->host = "https://api.twitter.com/1.1/";

$query = 'https://api.twitter.com/1.1/statuses/'.$timeline.'.json?count=1&screen_name='.$opts['twitAccount']; //Our query        
$tweet = $connection->get($query);

//output
foreach ($tweet as $twit) {
switch ($connection->http_code) {
	case '200':
	case '304':
		$output ='
		<div class="twitter_status" id="'.$twit->id_str.'">
		<div class="bloc_content">
		<a href="http://twitter.com/intent/user?screen_name='.$twit->user->screen_name.'">
		<span class="inyblock">
		<img src="'.$twit->user->profile_image_url.'" alt="@'.$twit->user->name.'" class="userimg tw_userimg" />
		</span>
		<span class="inyblock">
		<span class="username dark yblock">'.$twit->user->name.'</span>
		<span class="username tw_username">@'.$twit->user->screen_name.'</span>
		</span>
		</a>
		<span class="floatyRight"><a href="http://twitter.com/intent/user?screen_name='.$twit->user->screen_name.'"> Suivre</a></span>
		<p class="status tw_status">'.parseTweets($twit->text).'
		</p></div>
		<div class="bloc_caption floatyRight"> 
		<span class="intent-meta"><a style="background-image:url('.plugins_url('styles/img/everything-spritev2.png',__FILE__).');" class="in-reply-to" href="http://twitter.com/intent/tweet?in_reply_to='.$twit->id_str.'"><span>'.__('Reply','jm-ltsc').'</span></a></span>
		<span class="intent-meta"><a style="background-image:url('.plugins_url('styles/img/everything-spritev2.png',__FILE__).');" class="retweet" href="http://twitter.com/intent/retweet?tweet_id='.$twit->id_str.'"><span>'.__('Retweet','jm-ltsc').'</span></a></span>
		<span class="intent-meta"><a style="background-image:url('.plugins_url('styles/img/everything-spritev2.png',__FILE__).');" class="favorite" href="http://twitter.com/intent/favorite?tweet_id='.$twit->id_str.'"><span>'.__('Favorite','jm-ltsc').'</span></a></span>
		</span>
		</div>
		<span class="timestamp tw_timestamp">'.date('d M Y - H:i A',strtotime($twit->created_at)).'</span>
		</div>';
	 break;	
	
	case '400':
	case '401':
	case '403':
	case '404':
	case '406':
		$output = '<div class="large pa1 error">'.__('Your credentials might be unset or incorrect. In any case this error is not due to Twitter API.','jm-ltsc').'</div>';
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
} 
add_shortcode( 'jmlt', 'jm_ltsc_output' );
add_shortcode( 'widget','jmlt');
}//end of output


//set site transient
if(!function_exists('jm_last_twit_transient')) {
function jm_last_twit_transient($content) {
$opts = jm_ltsc_get_options();
set_site_transient( 'last_twit', $content, $opts['time'] );
}
}
//set our transient if there's no recent copy
if ( false === ( $tweet = get_site_transient( 'last_twit' ) ) ) jm_last_twit_transient($tweet);


/* quicktags
* */
add_action( 'admin_enqueue_scripts', 'jm_ltsc_add_quicktags' );
function jm_ltsc_add_quicktags( $hook_suffix ) {
if( 'post.php' == $hook_suffix || 'post-new.php' == $hook_suffix ) // only on post edit
wp_enqueue_script( 'jmltsc_quicktags_js', plugins_url('admin/quicktag.js',__FILE__), array( 'quicktags' ), null, true );
}

//styles 
$opts = jm_ltsc_get_options();
if($opts['twitStyles'] === 'yes') {
function jm_ltsc_style_front() {
//tip made by BAW (http://boiteaweb.fr) / I slightly modified it.
global $post;
if( !$post ) return;
$matches = array();
$pattern = get_shortcode_regex();
preg_match_all( '/' . $pattern . '/s', $post->post_content, $matches );
foreach( $matches[2] as $value ) {
if( $value == 'jmlt') {
wp_enqueue_style('front_style', plugins_url('/styles/jm-ltsc-front-style.css',__FILE__)); 
break;
}
}
}
add_action( 'wp_enqueue_scripts', 'jm_ltsc_style_front' );
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
$settings_link = '<a href="' . admin_url( 'options-general.php?page=jmltscoptions' ) . '">' . __("Settings") . '</a>';
array_unshift( $links, $settings_link );
return $links;
}


//The add_action to add onto the WordPress menu.
add_action('admin_menu', 'jm_ltsc_add_options');
function jm_ltsc_add_options() {
$page = add_submenu_page( 'options-general.php', 'JM Last Twit Options', 'JM Last Twit', 'manage_options', 'jmltscoptions', 'jm_ltsc_options_page' );
register_setting( 'jm-ltsc', 'jm_ltsc', 'jm_ltsc_sanitize' );
add_action( 'admin_print_styles-' . $page, 'jm_ltsc_admin_css' );//add styles for our options page the WordPress way
}


// Add styles the WordPress Way >> http://codex.wordpress.org/Function_Reference/wp_enqueue_style#Load_stylesheet_only_on_a_plugin.27s_options_page
function jm_ltsc_admin_css() {  
wp_enqueue_style( 'jm-style-ltw', plugins_url('admin/jm-ltsc-admin-style.css', __FILE__)); 
} 

// Check if a plugin is active (> SEO by Yoast)
function jm_ltsc_is_plugin_active( $plugin ) {
return in_array( $plugin, (array) get_option( 'active_plugins', array() ) ) || is_plugin_active_for_network( $plugin ); 
}       
// Settings page
function jm_ltsc_options_page() {
$opts = jm_ltsc_get_options();
?>
<div id="jm-ltsc">
<?php screen_icon('options-general'); ?>
<h2><?php _e('JM Last Twit Shortcode Options', 'jm-ltsc'); ?></h2>

<p><?php _e('This plugin allows you to get your last Tweet with the last Twitter API (1.1). Pretty useful because <strong>Twitter API 1.0 is to cease functioning in may 2013</strong>.', 'jm-ltsc'); ?></p>
<h3><?php _e('Before', 'jm-ltsc'); ?></h3>
<p><?php _e('Do not forget to go to', 'jm-ltsc'); ?> <a href="https://dev.twitter.com/apps/" target="_blank">dev.twitter.com</a> <?php _e('to create your application <strong>before anything</strong> cause you might forget get it after. In any case you will need token to proceed.','jm-ltsc'); ?></p>


<form id="jm-ltsc-form" method="post" action="options.php">
<?php settings_fields('jm-ltsc'); ?>

<fieldset>

<h3><?php _e('Options', 'jm-ltsc'); ?></h3>
<p>
<label for="twitAccount"><?php _e('Provide your Twitter username without @', 'jm-ltsc'); ?> :</label>
<input id="twitAccount" type="text" name="jm_ltsc[twitAccount]" class="regular-text" value="<?php echo $opts['twitAccount']; ?>" />
</p>
<p>
<label for="consumerKey"><?php _e('Provide your application consumer key', 'jm-ltsc'); ?> :</label>
<input id="consumerKey" type="text" name="jm_ltsc[consumerKey]" class="regular-text" value="<?php echo $opts['consumerKey']; ?>" />
</p>
<p>
<label for="consumerSecret"><?php _e('Provide your application consumer secret', 'jm-ltsc'); ?> :</label>
<input id="consumerSecret" type="text" name="jm_ltsc[consumerSecret]" class="regular-text" value="<?php echo $opts['consumerSecret']; ?>" />
</p>
<p>
<label for="oauthToken"><?php _e('Provide your oAuth Token', 'jm-ltsc'); ?> :</label>
<input id="oauthToken" type="text" name="jm_ltsc[oauthToken]" class="regular-text" value="<?php echo $opts['oauthToken']; ?>" />
</p>
<p>
<label for="tokenSecret"><?php _e('Provide your oAuth Token Secret', 'jm-ltsc'); ?> :</label>
<input id="tokenSecret" type="text" name="jm_ltsc[tokenSecret]" class="regular-text" value="<?php echo $opts['tokenSecret']; ?>" />
</p>
<p>
<label for="time"><?php _e('Set expired time for transient (min:1800s)', 'jm-ltsc'); ?> :</label>
<input id="time" type="number" min="1800" name="jm_ltsc[time]" class="regular-text" value="<?php echo $opts['time']; ?>" />
<br /><em><?php _e('*This is the time in the course of which your tweet will be stored. This allows us to limit server requests.', 'jm-ltsc'); ?></em>
</p>
<p>
<label for="twitStyles"><?php _e('Use default styles?', 'jm-ltsc'); ?> :</label>
<select id="twitStyles" name="jm_ltsc[twitStyles]">
<option value="yes" <?php echo $opts['twitStyles'] == 'yes' ? 'selected="selected"' : ''; ?> ><?php _e('yes', 'jm-ltsc'); ?></option>
<option value="no" <?php echo $opts['twitStyles'] == 'no' ? 'selected="selected"' : ''; ?> ><?php _e('no', 'jm-ltsc'); ?></option>
</select>

<br /><em><?php _e('If you do no want to use my styles and use your own in your main stylesheet I recommand you to copy-paste code of my CSS in /jm-last-twit-shortcode/styles/ because I use sprites to display web intents','jm-ltsc');?></em>
</p>


<?php submit_button(null, 'primary', 'JM_submit'); ?>

</fieldset>
</form>


<h3><?php _e('How to', 'jm-ltsc') ?></h3>
<ol>
<li><?php _e('Really easy, just put <strong>[jmlt]</strong> in your posts.','jm-ltsc');?></li>
<li><?php _e('You can even change timeline, e.g <strong>[jmlt timeline="mentions_timeline"]</strong> will display last mention of your Twitter account. Default is user_timeline. Other options are retweets_of_me and home_timeline.','jm-ltsc');?></li>
<li> <?php _e('To use the shortcode in templates, just use <em>echo apply_filters("the_content","[jmlt]")</em>','jm-ltsc'); ?></li>    
<li> <?php _e('To use the shortcode in widget text, just use shortcode like you do in posts. But you have to use your own styles to do such a thing, default styles will not applied in this section.','jm-ltsc'); ?></li>    
</ol>

<h3><?php _e('Useful links', 'jm-ltsc') ?></h3>
<ul class="jm-other-links">
<li><a class="jm-rating" target="_blank" href="http://wordpress.org/support/view/plugin-reviews/jm-last-twit-shortcode"><?php _e('Rate the plugin on WordPress.org', 'jm-tc') ?></a></li>
<li><a class="jm-twitter" target="_blank" href="<?php _e('https://twitter.com/intent/tweet?source=webclient&amp;hastags=WordPress,Plugin&amp;text=JM%20Last%20Twit%20%20Shortcode%20a%20great%20WordPress%20plugin%20to%20get%20your%20last%20tweet%20Try%20it!&amp;url=http://wordpress.org/extend/plugins/jm-last-twit-shortcode/&amp;related=TweetPressFr&amp;via=TweetPressFr','jm-ltsc'); ?>"><?php _e('Tweet it', 'jm-tc') ?></a></li> 
<li><a class="jm-api-version" target="_blank" href="https://dev.twitter.com/docs/api/1.1"><?php _e('REST API version 1.1 (last version)', 'jm-tc'); ?></a></li>         
<li><a class="jm-donation" target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=jmlapam%40gmail%2ecom&item_name=JM%20Last%20Twit%20Shortcode&no_shipping=0&no_note=1&tax=0&currency_code=EUR&bn=PP%2dDonationsBF&charset=UTF%2d8"><?php _e('Donate to this plugin', 'jm-tc'); ?></a></li>         
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
$new['twitAccount']              = esc_attr(strip_tags( $options['twitAccount'] ));
if ( isset($options['consumerKey']) )
$new['consumerKey']              = esc_attr(strip_tags( $options['consumerKey'] ));
if ( isset($options['consumerSecret']) )
$new['consumerSecret']           = esc_attr(strip_tags( $options['consumerSecret'] ));
if ( isset($options['oauthToken']) )
$new['oauthToken']               = esc_attr(strip_tags( $options['oauthToken'] ));
if ( isset($options['tokenSecret']) )
$new['tokenSecret']              = esc_attr(strip_tags( $options['tokenSecret'] ));
if ( isset($options['time']) )
$new['time']                     = (int) $options['time'];// because it comes from an input type number
if ( isset($options['twitStyles']) )
$new['twitStyles']               =  $options['twitStyles'];// because it comes from an input type number
return $new;
}

// Return default options
function jm_ltsc_get_default_options() {
return array(
'twitAccount'              => 'TweetPressFr',
'consumerKey'              => __('replace with your keys - required', 'jm-ltsc'),
'consumerSecret'           => __('replace with your keys - required', 'jm-ltsc'),
'oauthToken'               => __('replace with your keys - required', 'jm-ltsc'),
'tokenSecret'              => __('replace with your keys - required', 'jm-ltsc'),
'time'                     => 1800,
'twitStyles'			   => 'yes'
);
}

// Retrieve and sanitize options
function jm_ltsc_get_options() {
$options = get_option( 'jm_ltsc' );
return array_merge(jm_ltsc_get_default_options(), jm_ltsc_sanitize_options($options));
}


