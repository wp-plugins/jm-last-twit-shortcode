<?php
defined( 'ABSPATH' ) or	die( 'No !' );

/*
* OUTPUT
*/

if(!function_exists('jm_ltsc_output')) {
	function jm_ltsc_output( $atts ) {
		$args = shortcode_atts(array(
		'username'     	     => '',
		'cache'         	 => 1800,
		'count'       	     => 1,
		'include_rts'  		 => true,	
		'exclude_replies'	 => false,
		'display_media'		 => false
		), $atts, 'ltsc');

		
		//add some flexibility, you can add whatever account
		$opts 				= jm_ltsc_get_options(); 
		$consumer_key 		= $opts['consumerKey'];
		$consumer_secret 	= $opts['consumerSecret'];
		if ($args['username'] == '') 
			$args['username'] = $opts['twitAccount'];

		
		$init =  new TokenToMe(
					$consumer_key, 
					$consumer_secret, 
					'statuses/user_timeline', 
					array(
						'count'			   	=> $args['count'],
						'include_rts'	   	=> $args['include_rts'],
						'exclude_replies'  	=> $args['exclude_replies'],
						'screen_name' 		=> $args['username']
						),
					$args['cache'],
					$args['display_media']
				);

		//output
		$output = $init->display_infos();

		return apply_filters( 'ltsc_shortcode_markup', $output );
		
	} 
	add_shortcode( 'jmlt', 'jm_ltsc_output' );
	add_shortcode( 'widget','jmlt');
}//end of output