<?php
defined( 'ABSPATH' ) or	die( 'No !' );

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
* CONNECTION TO API
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
		global $current_user;
		$errors = get_transient( 'jm_ltsc_disabled_notice' . $current_user->ID );
		// We got errors!
		if( !$errors && !class_exists('tmhOAuth') )
			require_once( JM_LTSC_LIB_DIR.'/vendor/autoload.php');//composer yeah !
		
		//connection
		$tcTmhOAuth = new tmhOAuth(array(
		'consumer_key' => $consumer_key,
		'consumer_secret' => $consumer_secret,
		'user_token' => $user_token,
		'user_secret' => $user_secret
		));
	}
}