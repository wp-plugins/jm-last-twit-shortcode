=== JM Last Twit Shortcode ===
Contributors: jmlapam
Tags: twitter, tweet, API 1.1, shortcode
Requires at least: 3.9.1
Tested up to: 3.9.1
Donate Link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=STBXACUTMGJRL
License: GPLv2 or later
Stable tag: trunk
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A plugin meant to add your last tweet with the lattest API way :

== Description ==

Once activated the plugin displays your latest tweet according to your settings with authenticated connexion and thanks to a shortcode. 
See **FAQ** here and/or option page of plugin on your installation.

**Requires PHP 5.3++**

<a href="http://twitter.com/tweetpressfr">Follow me on Twitter</a>


––––
En Français 
–––––––––––––––––––––––––––––––––––

Une fois activé le plugin s'occupe d'afficher votre dernier tweet avec une connexion authentifiée et grâce à un shortcode. 
Voir la **FAQ** et/ou la page d'options du plugin sur votre installation

**Requiert PHP 5.3 au minimum**

<a href="http://twitter.com/tweetpressfr">Me suivre sur Twitter</a>

== Installation ==

1. Upload plugin files to the /wp-content/plugins/ directory
2. Activate the plugin through the Plugins menu in WordPress
3. Then go to settings > JM Last Twit Shortcode to configure the plugin
4. Do not forget to create your application on <a href="https://dev.twitter.com/apps/" target="_blank">dev.twitter</a>

––––
En Français 
–––––––––––––––––––––––––––––––––––

1. Chargez les fichiers de l'archive dans le dossier /wp-content/plugins/ 
2. Activez le plugin dans le menu extensions de WordPress
3. Allez dans réglages > JM Last Twit Shortcode pour configurer le plugin
4. N'oubliez pas de créer votre application sur <a href="https://dev.twitter.com/apps/" target="_blank">dev.twitter</a>

<a href="http://www.dailymotion.com/video/xxv3p6_creer-une-application-twitter-basique-pour-recuperer-des-tokens_tech">Tutoriel vidéo pour créer son application sur Twitter</a>

== Frequently asked questions ==

= I get an error message = 
1. If it's `Please update your settings to provide valid credentials` then your credentials (token, keys) are missing or not valid so please check it again.
2. If it's `Call to undefined function curl_init()` then the curl extension is not active on your server. Developers who use WampServer might activate curl extension in PHP > PHP Extension > curl
3. If there are no message but you do not get your Tweet it's probably to Twitter itself so please <a href="http://dev.twitter.com/status/" title="Twitter API Status health">check this link </a>

= How can I get more than 1 tweet? = 
Simple, just use the parameter "count" in shorcode, see example :
`[jmlt count="4"]`
This will display the last 4 tweets from the user specified in option page

= How can I get tweets from another Twitter account? =
`[jmlt username="TweetPressFr"]`
This will display tweet from TweetPressFr

= How can I set cache ? =
`[jmlt cache="1800"]`
This will store tweets during 30 minutes allowing you to limit your API requests 
			
= How can I use it in a template ? =
The appropriate method would be for example : 
`
if( function_exists('jm_ltsc_output') ) {
	echo jm_ltsc_output( array('count' => 5, 'include_rts' => false, 'exclude_replies' => true, 'username' => 'your_username') ) ;
}
`

= How can I use the filter? =
example :
`
add_filter('ltsc_shortcode_markup','test_filter_jmltsc');
function test_filter_jmltsc($output){
	 return '<div class="extra_extra_markup">' . $output . '</div>';
}
`

----
En Français
–––––––––––––––––––––––––––––––––––

= J'ai un message d'erreur = 
1. S'il s'agit de `Please update your settings to provide valid credentials` vos identifiants (token, clés) sont manquants ou ne sont pas valides vérifiez-les à nouveau SVP.
2. S'il s'agit de `Call to undefined function curl_init()` alors l'extension curl est désactivée sur votre serveur. Les développeurs sous WampServer peuvent activer cette extension dans PHP > PHP Extension > curl
3. Si vous n'avez pas de message d'erreur mais n'obtenez toujours pas vos Tweet c'est probablement du à Twitter lui-même donc SVP <a href="http://dev.twitter.com/status/" title="Twitter API Status health">utilisez ce lien </a>

= Et pour avoir plus d'un tweet? = 
Simple, utilisez le paramètre "count" dans le shorcode, par exemple :
`[jmlt count="4"]`
affichera les 4 derniers tweets de l'utilisateur spécifié en page d'option

= Et pour afficher les tweets d'un autre compte? = 
Utilisez le paramètre "username" dans le shortcode :
`[jmlt username="TweetPressFr"]`
affichera le dernier tweet du compte TweetPressFr

= Comment je fixe le cache ? =
`[jmlt cache="1800"]`
Cela mettra les tweets en cache durant 30 minutes et permettra de limiter les requêtes API

= Comment l'utiliser dans un template ? =
La méthode appropriée est la suivante : 
`
if( function_exists('jm_ltsc_output') ) {
	echo jm_ltsc_output( array('count' => 5, 'include_rts' => false, 'exclude_replies' => true, 'username' => 'your_username') ) ;
}
`
= Comment se hooker sur le filtre? =
Exemple basique :
`
add_filter('ltsc_shortcode_markup','test_filter_jmltsc');
function test_filter_jmltsc($output){
	 return '<div class="extra_extra_markup">' . $output . '</div>';
}
`

== Screenshots ==
1. front-end result with default styles
2. quicktags in posts
3. use in widget
4. front-end result when use in widget while using in post
5. UI button (tinymce)


== Changelog ==

= 4.0 =
* 18 July 2014
* Use its own class to get Twitter object
* Rebuild everything
* Delete useless options (there were too many)
* Still you can choose what you want to use with the visual editor in post edit
* Delete class filters
* Add basic styles option, it follows the style guides of Twitter, same font, etc.
* To include basic styles just use the following snippet :

`add_action('wp_enqueue_scripts', '_use_twitter_ui_for_tweets');
function _use_twitter_ui_for_tweets(){
	wp_enqueue_style('jm-basic-ltw');
}`

* You'll get layout and icons for intents (reply, favorite, retweets)
		

= 1.1.4 =
* 25 jan 2013
* Adds transient (thanks for the comment Juliobox)

= 1.1.3 =
* 22 jan 2013
* Initial release

== Upgrade notice ==
Nothing
= 1.1.3 =


