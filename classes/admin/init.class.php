<?php
defined( 'ABSPATH' ) 
	or	die( 'No !' );

	class JM_LTSC_Init {


    	protected $JM_LTSC_Init;
        protected static $instance;


        public static function GetInstance(){
          
            if (!isset(self::$instance))
            {
              self::$instance = new self();
            }

            return self::$instance;
        }

		public static function init(){

			// Add a "Settings" link in the plugins list
			add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), array(__CLASS__, 'settings_action_links'), 10, 2 );

		}

		public static function settings_action_links( $links, $file ) {
			$settings_link = '<a href="' . admin_url( 'admin.php?page=jm_ltsc_options' ) . '">' . __("Settings") . '</a>';
			array_unshift( $links, $settings_link );
			return $links;
		}

		public static function on_activation() {
			$opts = get_option( 'jm_ltsc' );
			if ( !is_array($opts) )
			update_option( 'jm_ltsc', JM_LTSC_Init::get_default_options() );
		}

		public static function activate() {
			if( !is_multisite() ) {
				
				self::on_activation();
			
			} else {
			    // For regular options.
				global $wpdb;
				$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
				foreach ( $blog_ids as $blog_id ) 
				{
					switch_to_blog( $blog_id );
					self::on_activation();
					restore_current_blog();
				}
			
			}
			
		}

	}