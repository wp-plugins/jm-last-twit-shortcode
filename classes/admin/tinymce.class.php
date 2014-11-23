<?php
defined( 'ABSPATH' ) 
	or	die( 'No !' );

if ( ! class_exists( 'JM_LTSC_Tinymce' ) ) {

	class JM_LTSC_Tinymce {


    	protected $JM_LTSC_Tinymce;
        protected static $instance;


        public static function GetInstance(){
          
            if (!isset(self::$instance))
            {
              self::$instance = new self();
            }

            return self::$instance;
        }

		public static function init(){

			//tinymce button
			add_action('admin_head', array(__CLASS__, 'add_mce_button') );
			add_filter( 'mce_external_languages', array(__CLASS__, 'add_tinymce_lang'), 10, 1 );

		}

		public static function add_mce_button() {

			// check user permissions
			if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
				return;
			}
			// check if WYSIWYG is enabled
			if ( 'true' == get_user_option( 'rich_editing' ) ) {
				add_filter( 'mce_external_plugins', array(__CLASS__, 'add_tinymce_plugin') );
				add_filter( 'mce_buttons', array(__CLASS__, 'register_mce_button') );
			}
		}

		// Add button
		public static function add_tinymce_plugin( $plugin_array ) {

			$plugin_array['jm_ltsc_mce_button'] = JM_LTSC_JS_URL.'tinymce.js';
			return $plugin_array;	
		}

		// Localize
		public static function add_tinymce_lang( $arr ){

		    $arr[] = JM_LTSC_DIR. 'languages/translation.php';
		    return $arr;
		}

		// Register new button in the editor
		public static function register_mce_button( $buttons ) {

			array_push( $buttons, 'jm_ltsc_mce_button' );
			return $buttons;
		}


	}

}