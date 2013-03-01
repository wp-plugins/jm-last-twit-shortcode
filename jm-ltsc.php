<?php
/*Plugin Name: JM Last Twit Shortcode
Plugin URI: http://tweetPress.fr
Description: Meant to add your last tweet with the lattest API way
Author: Julien Maury
Author URI: http://tweetPress.fr
Version: 2.6
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
                    
                    // Get any existing copy of our transient data
                    if ( false === ( $tweets = get_site_transient( 'last_twit' ) ) ) {
                    //It wasn't there, so regenerate the data and save the transient              
                    $tweets = $connection->get($query);
                    set_site_transient( 'last_twit', $tweets, $opts['time'] );
                   }
                    
                 //output
                if(!empty($consumer_key) && !empty($consumer_secret) && !empty($oauth_token) && !empty($oauth_token_secret)) {
                //error code API Twitter
                $errors = $connection->http_code;
                            if(!empty($tweets) && (empty($errors) || $errors == 200)){ foreach($tweets as $tweet){
                                $output ='
                                <div class="twitter_status" id="'.$tweet->id_str.'">
                                <div class="bloc_content">
                                    <a href="http://twitter.com/intent/user?screen_name='.$tweet->user->screen_name.'">
                                            <img src="'.$tweet->user->profile_image_url.'" alt="@'.$tweet->user->name.'" class="userimg tw_userimg"/>
                                            <span class="username tw_username">@'.$tweet->user->screen_name.'</span>
                                        </a>
                                        <p class="status tw_status">'.parseTweets($tweet->text).'
                                    </p></div>
                                    <div class="bloc_caption"> 
                                        <span class="intent-meta"><a style="background-image:url('.plugins_url('styles/img/everything-spritev2.png',__FILE__).');" class="in-reply-to" href="http://twitter.com/intent/tweet?in_reply_to='.$tweet->id_str.'">'.__('Reply','jm-ltsc').'</a></span>
                                        <span class="intent-meta"><a style="background-image:url('.plugins_url('styles/img/everything-spritev2.png',__FILE__).');" class="retweet" href="http://twitter.com/intent/retweet?tweet_id='.$tweet->id_str.'">'.__('Retweet','jm-ltsc').'</a></span>
                                        <span class="intent-meta"><a style="background-image:url('.plugins_url('styles/img/everything-spritev2.png',__FILE__).');" class="favorite" href="http://twitter.com/intent/favorite?tweet_id='.$tweet->id_str.'">'.__('Favorite','jm-ltsc').'</a></span>
                                        </span>
                                        <span class="timestamp tw_timestamp">'.date('d M / H:i',strtotime($tweet->created_at)).'</span>
                                    </div>
                                </div>';
                                }
                              }
                              else {
                             delete_site_transient( 'last_twit' );//to avoid waiting for 30'
                                $output ='
                                <div class="twitter_status">
                                    <p><a href="http://dev.twitter.com/status/" title="Twitter API Status health">'.__('API Twitter is down or settings are wrong.','jm-ltsc').'</a>'.__('','jm-ltsc').'
                                <p></div>';
                              }
                            
                                $output .='
                            <div style="clear:both;"></div>
                        ';
                    } else { ?>
                        <p> <?php _e('Please update your settings to provide valid credentials','jm-ltsc'); ?></p> <?php
                    }
                    return $output;
               } 
              add_shortcode( 'jmlt', 'jm_ltsc_output' );
            }//end of output
            

         //styles 
         
         function jm_ltsc_style_front() {
         //tip made by BAW (http://boiteaweb.fr) / I slightly modified it.
                   global $post;
                   if( !$post ) return;
                   $matches = array();
                   $pattern = get_shortcode_regex();
                   preg_match_all( '/' . $pattern . '/s', $post->post_content, $matches );
                   foreach( $matches[2] as $value ) {
                       if( $value == 'jmlt' ) {
                          wp_enqueue_style('front_style', plugins_url('/styles/jm-ltsc-front-style.css',__FILE__)); 
               
                         
                           break;
                       }
                   }
               }
               add_action( 'wp_enqueue_scripts', 'jm_ltsc_style_front' );




          /*
          * ADMIN OPTION PAGE
          */
  
           // Language support
          add_action( 'admin_init', 'jm_ltsc_lang_init' );
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
                  
                  <p><?php _e('This plugin allows you to get your last Tweet with the last Twitter API (1.1). Pretty useful because <strong>Twitter API 1.0 is to cease functioning in march 2013</strong>.', 'jm-ltsc'); ?></p>
                  <h3><?php _e('Before', 'jm-ltsc'); ?></h3>
                  <p><?php _e('Do not forget to go to', 'jm-ltsc'); ?> <a href="https://dev.twitter.com/apps/" target="_blank">dev.twitter.com</a> <?php _e('to create your application <strong>before anything</strong> cause you might forget get it after. In any case you will need token to proceed.','jm-ltsc'); ?></p>
             
                  
                  <form id="jm-ltsc-form" method="post" action="options.php">
                      <?php settings_fields('jm-ltsc'); ?>
       
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
                              </p> 
                              <p><em><?php _e('*This is the time in the course of which your tweet will be stored. This allows us to limit server requests.', 'jm-ltsc'); ?></em>
                           </p>

                                  <?php submit_button(null, 'primary', 'JM_submit'); ?>
                      
                  
                  </form>

                
                     <h3><?php _e('How to', 'jm-ltsc') ?></h3>
                     <ol>
                  <li><?php _e('Really easy, just put <strong>[jmlt]</strong> in your posts.','jm-ltsc');?></li>
                  <li><?php _e('You can even change timeline, e.g <strong>[jmlt timeline="mentions_timeline"]</strong> will display last mention of your Twitter account. Default is user_timeline. Other options are retweets_of_me and home_timeline.','jm-ltsc');?></li>
                 <li> <?php _e('To use the shortcode in templates, just use <em>echo apply_filters("the_content","[jmlt]")</em>','jm-ltsc'); ?></li>    
                    </ol>
                    
                  <h3><?php _e('Useful links', 'jm-ltsc') ?></h3>
                              <ul class="jm-other-links">
                    <li><a class="jm-rating" target="_blank" href="http://wordpress.org/support/view/plugin-reviews/jm-last-twit-shortcode"><?php _e('Rate the plugin on WordPress.org', 'jm-tc') ?></a></li>
                    <li><a class="jm-twitter" target="_blank" href="<?php _e('https://twitter.com/intent/tweet?source=webclient&amp;hastags=WordPress,Plugin&amp;text=JM%20Last%20Twit%20%20Shortcode%20a%20great%20WordPress%20plugin%20to%20get%20your%20last%20tweet%20Try%20it!&amp;url=http://wordpress.org/extend/plugins/jm-last-twit-shortcode/&amp;related=TweetPressFr&amp;via=TweetPressFr','jm-ltsc'); ?>"><?php _e('Tweet it', 'jm-tc') ?></a></li> 
                    <li><a class="jm-api-version" target="_blank" href="https://dev.twitter.com/docs/api/1.1"><?php _e('REST API version 1.1 (last version)', 'jm-tc'); ?></a></li>         
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
            $new['consumerSecret']            = esc_attr(strip_tags( $options['consumerSecret'] ));
                        if ( isset($options['oauthToken']) )
            $new['oauthToken']                = esc_attr(strip_tags( $options['oauthToken'] ));
                        if ( isset($options['tokenSecret']) )
            $new['tokenSecret']              = esc_attr(strip_tags( $options['tokenSecret'] ));
                        if ( isset($options['time']) )
            $new['time']                        =  (int) $options['time'];// because it comes from an input type number
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
            'time'                     => 1800
            );
            }

            // Retrieve and sanitize options
            function jm_ltsc_get_options() {
            $options = get_option( 'jm_ltsc' );
            return array_merge(jm_ltsc_get_default_options(), jm_ltsc_sanitize_options($options));
            }
        

