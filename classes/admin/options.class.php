<?php
defined( 'ABSPATH' ) 
	or	die( 'No !' );

if ( ! class_exists('JM_LTSC_Options') ) {

	class JM_LTSC_Options{


    	protected $JM_LTSC_Options;
        protected static $instance;


        public static function GetInstance(){
          
            if (!isset(self::$instance))
            {
              self::$instance = new self();
            }

            return self::$instance;
        }

		public static function init(){

			add_action('admin_menu', array( __CLASS__, 'add_options' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ) );

		}


		// Remove any @ from input value
		public static function remove_at($at) { 
			$noat = str_replace('@','',$at);
			return $noat;
		}


		/* quicktags
		* */
		public static function add_quicktags( $hook_suffix ) {

			$opts = jm_ltsc_get_options(); 

		}


		//The add_action to add onto the WordPress menu.
		public static function add_options() {

			add_menu_page( 
			'JM LTSC Options', 
			'JM LTSC',  
			'manage_options', 
			'jm_ltsc_options', 
			 array(__CLASS__, 'options_page'), 
			'dashicons-twitter'
			);

			register_setting( JM_LTSC_SLUG_NAME, 'jm_ltsc' );

		}

		//
		public static function admin_scripts( $hook_suffix ) { 

			$opts = self::get_options();

			if( ('post.php' == $hook_suffix || 'post-new.php' == $hook_suffix ) && $opts['twitQuickTags'] == 'yes') { // only on post edit and if user wants it
					
					wp_enqueue_script( 'jmltsc_quicktags_js', JM_LTSC_JS_URL.'quicktag.js', array( 'quicktags' ), null, true ); 
			
			}

			if( 'toplevel_page_jm_ltsc_options' == $hook_suffix ) {

				wp_enqueue_style( 'jm-style-ltw', JM_LTSC_CSS_URL.'admin-style.css' );
				wp_register_style( 'jm-basic-ltw', JM_LTSC_CSS_URL.'styles-basic.css' ); 
				wp_enqueue_style('jm-basic-ltw'); 
				wp_enqueue_script('jm-tab-ltw', JM_LTSC_JS_URL.'admin-tab.js', array('jquery'), '1.0', false);
			}
		} 


		// Settings page
		public static function options_page() {

			$opts = self::get_options(); 

			//get view
			require( JM_LTSC_DIR . 'views/admin/settings.php' );
		}


		/*
		* OPTIONS TREATMENT
		*/

		// Process options when submitted
		protected static function sanitize($options) {

			return array_merge(self::get_options(), self::sanitize_options($options));

		}

		// Sanitize options
		protected static function sanitize_options($options) {

			$new = array();

			if ( !is_array($options) )
			return $new;

			if ( isset($options['twitAccount']) )
			$new['twitAccount']              = esc_attr(strip_tags( self::remove_at($options['twitAccount']) ));
			if ( isset($options['consumerKey']) )
			$new['consumerKey']              = esc_attr(strip_tags( $options['consumerKey'] ));
			if ( isset($options['consumerSecret']) )
			$new['consumerSecret']           = esc_attr(strip_tags( $options['consumerSecret'] ));
			if ( isset($options['twitQuickTags']) )
			$new['twitQuickTags']            = $options['twitQuickTags'] ;
			
			return $new;

		}


		// Retrieve and sanitize options
		public static function get_options() {

			$options = get_option( 'jm_ltsc' );
			return array_merge(self::get_default_options(), self::sanitize_options($options));

		}

		// Return default options
		public static function get_default_options() {

			return array(
			'twitAccount'              => '',
			'consumerKey'              => __('replace with your keys - required', JM_LTSC_SLUG_NAME),
			'consumerSecret'           => __('replace with your keys - required', JM_LTSC_SLUG_NAME),
			'twitQuickTags'            => 'yes'
			);

		}
	}
}