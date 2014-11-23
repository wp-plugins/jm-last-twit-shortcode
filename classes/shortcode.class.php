<?php
defined( 'ABSPATH' ) 
	or	die( 'No !' );

if( ! class_exists('JM_LTSC_Shortcode') ) {

	class JM_LTSC_Shortcode {

    	protected $JM_LTSC_Shortcode;
        protected static $instance;


        public static function GetInstance(){
          
            if (!isset(self::$instance))
            {
              self::$instance = new self();
            }

            return self::$instance;
        }

		public static function init(){

			add_shortcode( 'jmlt', array(__CLASS__, 'output' ) );

		}


		public static function output( $atts ) {

			$args = shortcode_atts(array(
				'username'     	     => '',
				'cache'         	 => 1800,
				'count'       	     => 1,
				'include_rts'  		 => true,	
				'request'			 => 'statuses/user_timeline',
				'exclude_replies'	 => false,
				'display_media'		 => false
			), $atts, 'ltsc_filter');

			
			//add some flexibility, you can add whatever account
			$opts 				= get_option('jm_ltsc'); 
			$consumer_key 		= $opts['consumerKey'];
			$consumer_secret 	= $opts['consumerSecret'];
			if ($args['username'] == '') 
				$args['username'] = $opts['twitAccount'];

			$params = array(
			'count'			   	=> $args['count'],
			'include_rts'	   	=> $args['include_rts'],
			'exclude_replies'  	=> $args['exclude_replies'],
			'screen_name' 		=> $args['username']
			);

			
			$init =  new TokenToMe( $consumer_key, $consumer_secret, $args['request'], $params, $args['cache'], $args['display_media']);

			//output
			$output = $init->display_infos();

			return apply_filters( 'ltsc_shortcode_markup', $output );
			
		} 

	}

}