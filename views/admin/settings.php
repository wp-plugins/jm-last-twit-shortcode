<?php
defined( 'ABSPATH' ) 
	or	die( 'No !' );
?>
<div class="wrap jm_ltsc" id="pluginwrapper">
	<h2 class="dashicons-before dashicons-twitter"><?php _e('JM Last Twit Shortcode', JM_LTSC_SLUG_NAME); ?></h2>
	<?php if ( isset( $_GET['settings-updated'] ) ) echo "<div class='updated'><p>".__('Settings saved.')."</p></div>"; ?>
	<ul id="tabs">

		  <li><a id="tab1"><?php _e('Options', JM_LTSC_SLUG_NAME); ?></a></li>
		  <li><a id="tab2"><?php _e('Styles', JM_LTSC_SLUG_NAME); ?></a></li>
		  <li><a id="tab3"><?php _e('About'); ?></a></li>

	</ul>
	<div class="container" id="tab1C">
		
		<blockquote><?php _e('Get your last tweets <br />the Twitter 1.1 way</br> with a simple shortcode',JM_LTSC_SLUG_NAME);?>
		<br /><?php _e('To grab your feed you need to authenticate in the new version of Twitter API 1.1', JM_LTSC_SLUG_NAME); ?>
		<br /><?php _e('With this plugin you can display any Twitter timeline with a simple a shortcode', JM_LTSC_SLUG_NAME); ?></blockquote>
		
		<?php echo do_shortcode('[jmlt]'); ?>
						
						
		<form class="jm-ltsc-form" method="post" action="options.php">
		<?php settings_fields(JM_LTSC_SLUG_NAME); ?>
			
			<fieldset>
				<legend><?php _e('Options', JM_LTSC_SLUG_NAME); ?></legend>	
				<p>
					<label for="twitAccount"><?php _e('Provide your Twitter username (used by default and without @)', JM_LTSC_SLUG_NAME); ?> :</label>
					<input id="twitAccount" type="text" name="jm_ltsc[twitAccount]" size="50" value="<?php echo JM_LTSC_Options::remove_at($opts['twitAccount']); ?>" />
				</p>
				<p>
					<label for="consumerKey"><?php _e('Provide your application consumer key', JM_LTSC_SLUG_NAME); ?> :</label>
					<input id="consumerKey" type="text" name="jm_ltsc[consumerKey]" size="50" value="<?php echo $opts['consumerKey']; ?>" />
				</p>
				<p>
					<label for="consumerSecret"><?php _e('Provide your application consumer secret', JM_LTSC_SLUG_NAME); ?> :</label>
					<input id="consumerSecret" type="text" name="jm_ltsc[consumerSecret]" size="50" value="<?php echo $opts['consumerSecret']; ?>" />
				</p>
				<p>
					<label for="twitQuickTags"><?php _e('Do you want to add Quicktags (buttons in HTML editor) in post edit?', JM_LTSC_SLUG_NAME); ?> :</label>
					<select class="styled-select" id="twitQuickTags" name="jm_ltsc[twitQuickTags]">
						<option value="yes" <?php echo $opts['twitQuickTags'] == 'yes' ? 'selected="selected"' : ''; ?> ><?php _e('Yes', JM_LTSC_SLUG_NAME); ?></option>
						<option value="no" <?php echo $opts['twitQuickTags'] == 'no' ? 'selected="selected"' : ''; ?> ><?php _e('No', JM_LTSC_SLUG_NAME); ?></option>
					</select>
					<br /><em>(<?php _e('Default is yes', JM_LTSC_SLUG_NAME); ?>)</em>
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
		
		<h3><?php _e('About the developer', JM_LTSC_SLUG_NAME); ?></h3>	
		<p>
			<img class="profile" src="http://www.gravatar.com/avatar/<?php echo md5( 'tweetpressfr@gmail.com' ); ?>" width="80" height="80" alt="" />
			<strong>Julien Maury</strong><br />
			<?php _e('I am a WordPress Developer, I like to make it simple.', JM_LTSC_SLUG_NAME) ?> <br />
			<a href="http://www.tweetpress.fr" target="_blank" title="TweetPress.fr - WordPress and Twitter tips">www.tweetpress.fr</a> - <a href="http://twitter.com/intent/user?screen_name=tweetpressfr" >@TweetPressFR</a><br />
			<a href="http://profiles.wordpress.org/jmlapam/" title="on WordPress.org"><?php _e('My WordPress Profile', JM_LTSC_SLUG_NAME) ?></a><br />
		</p>
		<div class="clear"></div>
		
		<h3><?php _e('Other plugins you might dig', JM_LTSC_SLUG_NAME); ?></h3>	
		<ul>
			<li><a class="button" href="http://wordpress.org/plugins/jm-twitter-cards/">JM Twitter Cards</a></li>
			<li><a class="button" href="http://wordpress.org/plugins/jm-instagram-feed-widget/">JM Instagram Feed Widget</a></li>
			<li><a class="button" href="http://wordpress.org/plugins/jm-twit-this-comment/">JM Twit This Comment</a> - <?php _e('Make your comments tweetable',JM_LTSC_SLUG_NAME);?></li>
		</ul>

		<h3><?php _e('Help me keep this free', JM_LTSC_SLUG_NAME); ?></h3>	
		<p><form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="hosted_button_id" value="STBXACUTMGJRL">
			<input type="image" src="https://www.paypalobjects.com/fr_FR/FR/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - la solution de paiement en ligne la plus simple et la plus sécurisée !">
			<img alt="" border="0" src="https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif" width="1" height="1">
			</form>
			</p>
		
		<h3><?php _e('Useful links', JM_LTSC_SLUG_NAME); ?></h3>	
		<ul>
			<li class="inbl"><a class="button normal redwp" target="_blank" href="http://wordpress.org/support/view/plugin-reviews/jm-last-twit-shortcode"><?php _e('Rate the plugin on WordPress.org', JM_LTSC_SLUG_NAME) ?></a></li>
			<li class="inbl"><a class="button normal twitblue" target="_blank" href="<?php _e('https://twitter.com/intent/tweet?source=webclient&amp;hastags=WordPress,Plugin&amp;text=JM%20Last%20Twit%20%20Shortcode%20a%20great%20WordPress%20plugin%20to%20get%20your%20last%20tweet%20Try%20it!&amp;url=http://wordpress.org/extend/plugins/jm-last-twit-shortcode/&amp;related=TweetPressFr&amp;via=TweetPressFr',JM_LTSC_SLUG_NAME); ?>"><?php _e('Tweet it', JM_LTSC_SLUG_NAME) ?></a></li>      
			<li class="inbl"><a class="button normal" target="_blank" href="https://twitter.com/intent/user?screen_name=TweetPressFr"><?php _e('follow me on Twitter', JM_LTSC_SLUG_NAME); ?></a></li>       
		</ul>
	
	</div>
				
</div>