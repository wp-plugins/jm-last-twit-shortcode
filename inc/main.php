<?php
defined( 'ABSPATH' ) or	die( 'No !' );

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
		), $atts, 'ltsc'));

		
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
				$output = '<ul class="tweetfeed">';
				while ( $i <= $count ) {
					//Assign feed to $feed
					if ( isset( $data[$i - 1] ) ) {
						$feed = jc_twitter_format( $data[$i - 1]->text, $data[$i - 1] );
						$id_str = $data[$i - 1]->id_str;
						$screen_name = $data[$i - 1]->user->screen_name;
						$name = $data[$i - 1]->user->name;
						$date = $data[$i - 1]->created_at;
						$date_format = 'j/m/y - '.get_option('time_format');
						$profile_image_url = $data[$i - 1]->user->profile_image_url;
						
						
						//class for markup
						$class_li 				= 'tweet-container';
						$class_twittar  		= 'tweet-twittar'; 
						$class_reply			= 'tweet-reply';
						$class_retweet			= 'tweet-retweet';
						$class_favorite			= 'tweet-favorite';
						$class_intent_container = 'intent-meta';
						$size 	 				= '36';
						$class_screen_name		= 'tweet-screen-name';
						$class_username		    = 'tweet-username';
						$class_content			= 'tweet-content';
						$class_timestamp		= 'tweet-timestamp';
						$class_timedate			= 'time-date';
						$class_timediff			= 'tweet-timediff';
					
						//header			
						$output .= '<li class="'.apply_filters('jmltsc_li_class', $class_li).'">';						
						$output .= '<a href="https://twitter.com/'.$screen_name.'">';
						$output .= '<img class="'.apply_filters('jmltsc_twittar_class', $class_twittar).'" width="'.apply_filters('jmltsc_twittar_size', $size).'" height="'.apply_filters('jmltsc_twittar_size', $size).'" src="'.$profile_image_url.'" alt="@'.$screen_name .'" />'; 				
						$output .= '</a>'; 
						$output .= '<span class="'.apply_filters('jmltsc_screen_name_class', $class_screen_name) .'">';
						$output .= '@<a href="https://twitter.com/'.$screen_name.'">'.$screen_name.'</a>';
						$output .= '</span>';
						$output .= '<span class="'.apply_filters('jmltsc_username_class', $class_username) .'">';
						$output .= '<a href="https://twitter.com/'.$screen_name.'">'.$name.'</a>';
						$output .= '</span>';	
						
						
						//main content
						$output .= '<div class="'.apply_filters('jmltsc_content_class', $class_content) .'">';
						$output .= $feed;
						$output .= '</div>';
						
						
						//timestamp
						$output .= '<span class="'.apply_filters('jmltsc_timestamp_class', $class_timestamp) .'">';
							$output .= '<span class="'.apply_filters('jmltsc_timedate_class', $class_timedate) .'">';
								$output .= '<a href="https://twitter.com/'.$username.'/status/'.$id_str.'">'. date( $date_format, strtotime($date) ) .'</a>';
							$output .= '</span>';
							
							/*$output .= '<span class="'.apply_filters('jmltsc_timediff_class', $class_timediff) .'">';
								$output .= human_time_diff( strtotime( $date ), current_time( 'timestamp', 1 ) ).__(' ago','jm-ltsc');
							$output .= '</span>';*/
						
						$output .= '</span>'; //end of timestamp
						
						//intent
						$output .= '<span class="'.apply_filters('jmltsc_intent_container_class', $class_intent_container) .'">';
						
							$output .= '<span class="'.apply_filters('jmltsc_reply_class', $class_reply) .'"><a href="https://twitter.com/intent/tweet?in_reply_to='.$id_str.'">'. __( 'Reply', 'jm-ltsc' ) .'</a></span>';
							$output .= '<span class="'.apply_filters('jmltsc_retweet_class', $class_retweet) .'"><a href="https://twitter.com/intent/retweet?tweet_id='.$id_str.'">'. __( 'Retweet', 'jm-ltsc' ) .'</a> </span>';
							$output .= '<span class="'.apply_filters('jmltsc_favorite_class', $class_favorite) .'"><a href="https://twitter.com/intent/favorite?tweet_id='.$id_str.'">'. __( 'Favorite', 'jm-ltsc' ) .'</a></span>';
						
						$output .= '</span>';
						
						$output .= '</li>';
					}
					$i++;
				}
				
				$output .='</ul>';
				break;	
				
			case '400':
			case '401':
			case '403':
			case '404':
			case '406':
				
				$output = '<div class="error"><p>'.__('Your credentials might be unset or incorrect or username is wrong. In any case this error is not due to Twitter API.','jm-ltsc').'</p></div>';
				break;
				
			case '429':
				
				$output = '<div class="error"><p>'.__('Rate limits are exceed!','jm-ltsc').'</p></div>';
				break;
				
			case '500':
			case '502':
			case '503':
				
				$output = '<div class="error"><p>'.__('Twitter is overwhelmed or something bad happened with its API.','jm-ltsc').'</p></div>';
				break;
			default:
				$output = __('Something is wrong or missing. ','jm-ltsc');
			}
			
			set_site_transient( $transient, $output, $cache );
			
		} else {
			return $incache . '<!--'. __('JM Last Twit Shortcode - cache','jm-ltsc') .'-->';
		}
		return apply_filters( 'ltsc_shortcode_markup', $output );
		
	} 
	add_shortcode( 'jmlt', 'jm_ltsc_output' );
	add_shortcode( 'widget','jmlt');
}//end of output