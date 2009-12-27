<?php
/*
Plugin Name: WP-DenyHost
Plugin URI: http://soderlind.no/denyhost
Description: Basted on a users IP address, WP-DenyHost will block a spammer if he already has been tagged as a spammer. Use it together with the Akismet plugin. Akismet tags the spammer, and WP-DenyHost prevents him from adding more comment spam.
Version: 1.1.1
Author: PerS
Author URI: http://soderlind.no
*/
/*

Changelog:
v1.1.1: Added languages/wp-denyhost.pot
v1.1.0: Major rewrite. Added option page
v1.0.1: Replaced LIKE (‘%$suspect%’) with = ‘$suspect’ i.e. look for exact match
v1.0: Initial release

I recommend the plugin template at http://pressography.com/plugins/wordpress-plugin-template/, I used it when I wrote this plugin.

Best regards,
PerS

*/


if (!class_exists('ps_wp_denyhost')) {
    class ps_wp_denyhost {
        /**
        * @var string The options string name for this plugin
        */
        var $optionsName = 'ps_wp_denyhost_options';

        /**
        * @var string $localizationDomain Domain used for localization
        */
        var $localizationDomain = "ps_wp_denyhost";

        /**
        * @var string $pluginurl The path to this plugin
        */ 
        var $url = '';
        /**
        * @var string $pluginurlpath The path to this plugin
        */
        var $urlpath = '';

        /**
        * @var array $options Stores the options for this plugin
        */
        var $options = array();

        //Class Functions
        /**
        * PHP 4 Compatible Constructor
        */
        function ps_wp_denyhost(){$this->__construct();}

        /**
        * PHP 5 Constructor
        */        
        function __construct(){
            //Language Setup
            $locale = get_locale();
            $mo = dirname(__FILE__) . "/languages/" . $this->localizationDomain . "-".$locale.".mo";
            load_textdomain($this->localizationDomain, $mo);

            //"Constants" setup

			$this->url = trailingslashit( get_bloginfo('wpurl') ) . substr( __FILE__, strlen($_SERVER['DOCUMENT_ROOT'])+1);
			$this->urlpath = dirname($this->url);

            //Initialize the options
            $this->getOptions();

            //Actions        
            add_action("admin_menu", array(&$this,"admin_menu_link"));		
			add_action('wp_print_scripts', array(&$this,'ps_wp_denyhost_script'));
			
			if ($this->options['ps_wp_denyhost_threshold'] && $this->options['ps_wp_denyhost_threshold'] > 0) {
				add_action("init", array(&$this,"ps_wp_denyhost_init"));
			}
			
        }

		function ps_wp_denyhost_init() {
		  global $wpdb;

		  $suspect = $this->get_IP();
		  $count = (int) $wpdb->get_var("SELECT COUNT(comment_ID) FROM $wpdb->comments  WHERE comment_approved = 'spam' AND comment_author_IP = '$suspect'");
		
		  if ($count >= $this->options['ps_wp_denyhost_threshold']) {
			switch ($this->options['ps_wp_denyhost_response']) {
				case '404':
					header("HTTP/1.0 404 Not Found");
					break;
				case 'google':
					header('Location: http://www.google.com/');
					break;
			}
			exit; 
		  }	
		}
		
		function ps_wp_denyhost_script() {
			wp_enqueue_script('jquery');
			wp_enqueue_script('jquery-validate', 'http://ajax.microsoft.com/ajax/jquery.validate/1.6/jquery.validate.min.js', array('jquery'));
			wp_enqueue_script('ps-wp-denyhost-script', $this->url.'?ps_wp_denyhost_javascript', array('jquery-validate')); // see end of this file
			wp_localize_script( 'ps-wp-denyhost-script', 'ps_wp_denyhost_lang', array(
				'required' => __('Please enter a number.', $this->localizationDomain),
				'number'   => __('Please enter a number.', $this->localizationDomain),
				'min'      => __('Please enter a value greater than or equal to 1.', $this->localizationDomain),
			));
		}
		
		
        /**
        * @desc Retrieves the users IP address.
		*
		* get_IP() Credits To Lester 'GaMerZ' Chan - http://lesterchan.net
        */
        function get_IP() {
                if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                        $ip_address = $_SERVER["REMOTE_ADDR"];
                } else {
                        $ip_address = $_SERVER["HTTP_X_FORWARDED_FOR"];
                }
                if(strpos($ip_address, ',') !== false) {
                        $ip_address = explode(',', $ip_address);
                        $ip_address = $ip_address[0];
                }
                return $ip_address;
        }


        /**
        * @desc Retrieves the plugin options from the database.
        * @return array
        */
        function getOptions() {
            if (!$theOptions = get_option($this->optionsName)) {
                $theOptions = array('default'=>'options');
                update_option($this->optionsName, $theOptions);
            }
            $this->options = $theOptions;
        }
        /**
        * Saves the admin options to the database.
        */
        function saveAdminOptions(){
            return update_option($this->optionsName, $this->options);
        }

        /**
        * @desc Adds the options subpanel
        */
        function admin_menu_link() {
            add_options_page('WP-DenyHost', 'WP-DenyHost', 10, basename(__FILE__), array(&$this,'admin_options_page'));
            add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'filter_plugin_actions'), 10, 2 );
        }

        /**
        * @desc Adds the Settings link to the plugin activate/deactivate page
        */
        function filter_plugin_actions($links, $file) {
           $settings_link = '<a href="options-general.php?page=' . basename(__FILE__) . '">' . __('Settings') . '</a>';
           array_unshift( $links, $settings_link ); // before other links

           return $links;
        }

        /**T
        * Adds settings/options page
        */
        function admin_options_page() { 
            if($_POST['ps_wp_denyhost_save']){
                if (! wp_verify_nonce($_POST['_wpnonce'], 'ps_wp_denyhost-update-options') ) die('Whoops! There was a problem with the data you posted. Please go back and try again.'); 
                $this->options['ps_wp_denyhost_threshold'] = (int)$_POST['ps_wp_denyhost_threshold'];                   
                $this->options['ps_wp_denyhost_response'] = $_POST['ps_wp_denyhost_response'];                   

                $this->saveAdminOptions();

                echo '<div class="updated"><p>Success! Your changes were sucessfully saved!</p></div>';
            }
?>                                   
                <div class="wrap">
                <h2>WP-DenyHost</h2>
				<p>
				<?php _e('Basted on a users IP address, WP-DenyHost will block a spammer if he already has been tagged as a spammer. Use it together with the Akismet plugin. Akismet tags the spammer, and WP-DenyHost prevents him from adding more comment spam.', $this->localizationDomain); ?>
				</p>
                <form method="post" id="ps_wp_denyhost_options">
                <?php wp_nonce_field('ps_wp_denyhost-update-options'); ?>
                    <table width="100%" cellspacing="2" cellpadding="5" class="form-table"> 
                        <tr valign="top"> 
                            <th width="33%" scope="row"><?php _e('Threshold:', $this->localizationDomain); ?></th> 
                            <td>
								<input name="ps_wp_denyhost_threshold" type="text" id="ps_wp_denyhost_threshold" size="45" value="<?php echo $this->options['ps_wp_denyhost_threshold'] ;?>"/>
								<br /><span class="setting-description"><?php _e('Number of comment spams accepted before blocking a user. This is to prevent an innocent commenter, with comments wrongly tagged as spam, from being blocked.', $this->localizationDomain); ?>
                        	</td> 
                        </tr>
                        <tr valign="top"> 
                            <th><label for="ps_wp_denyhost_response"><?php _e('Response:', $this->localizationDomain); ?></label></th>
							<td>
								<select id="ps_wp_denyhost_response" name="ps_wp_denyhost_response">
								  <option value="exit" <?=($this->options['ps_wp_denyhost_response']=="exit")?'selected="selected"':''?>><?php _e('PHP exit', $this->localizationDomain); ?></option>
								  <option value="404" <?=($this->options['ps_wp_denyhost_response']=="404")?'selected="selected"':''?>><?php _e('404 Not found', $this->localizationDomain); ?></option>
								  <option value="google" <?=($this->options['ps_wp_denyhost_response']=="google")?'selected="selected"':''?>><?php _e('Redirect to Google', $this->localizationDomain); ?></option>
								</select>
								<br /><span class="setting-description"><?php _e('What kind of response a blocked spammer will get', $this->localizationDomain); ?>
							</td>
                        </tr>
                    </table>
					<p class="submit"> 
						<input type="submit" name="ps_wp_denyhost_save" class="button-primary" value="<?php _e('Save Changes', $this->localizationDomain); ?>" /> <a href="/wp-admin/edit-comments.php?comment_status=spam"><?php _e('Edit Comment Spam', $this->localizationDomain); ?></a>
					</p>
                </form>				
                <?php
        }

  } //End Class
} //End if class exists statement


if (isset($_GET['ps_wp_denyhost_javascript'])) {

	Header("content-type: application/x-javascript");
	echo<<<ENDJS
/**
* @desc WP-DenyHost
* @author Per Soderlind - soderlind.no
*/

jQuery(document).ready(function(){
  	jQuery("#ps_wp_denyhost_options").validate({
		rules: {
	    	ps_wp_denyhost_threshold: {
				required: true,
				number: true,
				min: 1
			}
		},
		messages: {
			ps_wp_denyhost_threshold: {
				// the ps_wp_denyhost_lang object is define using wp_localize_script() in function ps_wp_denyhost_script() 
				required: ps_wp_denyhost_lang.required,
				number: ps_wp_denyhost_lang.number,
				min: ps_wp_denyhost_lang.min
			}
		}
	});
});

ENDJS;

} else {
	if (class_exists('ps_wp_denyhost')) { 
    	$ps_wp_denyhost_var = new ps_wp_denyhost();
	}
}
?>