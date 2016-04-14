<?php
/**
 * Plugin Name: WP-DenyHost
 * Plugin URI: http://soderlind.no/denyhost
 * Description: Based on a users IP address, WP-DenyHost will block a spammer if he already has been tagged as a spammer. Use it together with the Akismet plugin. Akismet tags the spammer, and WP-DenyHost prevents him from adding more comment spam.
 * Version: 1.3.0
 * Author: PerS
 * Author URI: http://soderlind.no
 * Text Domain: wp-denyhost
 * Domain Path: /languages
 *
 * @package wp-denyhost
 */

define( 'CLOUDFLARE_APIURL', 'https://www.cloudflare.com/api_json.html' );

if ( ! class_exists( 'PS_WP_DenyHost' ) ) {
	/**
	 * Class
	 */
	class PS_WP_DenyHost {
		/**
		 * The options name for this plugin
		 *
		 * @var string
		 */
		var $options_name = 'ps_wp_denyhost_options';

		 /**
		 * The path to this plugin
		 *
		 * @var string $pluginurl
		 */
		var $pluginurl;

		/**
		 * [$url description]
		 *
		 * @var string
		 */
		var $url = '';

		/**
		 * The path to this plugin
		 *
		 * @var string $urlpath
		 */
		var $urlpath = '';

		/**
		 * Stores the options for this plugin
		 *
		 * @var array $options
		 */
		var $options = array();

		 /**
		  * Class constructor
		  *
		  * @author soderlind
		  * @version [version]
		  */
		function __construct() {
			add_action( 'admin_init', array( $this, 'ps_wp_denyhost_admin_init' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu_link' ) );
			add_action( 'init', array( $this, 'ps_wp_denyhost_init' ) );
		}

		/**
		 * Initialize the admin.
		 *
		 * @author soderlind
		 * @version [version]
		 */
		function ps_wp_denyhost_admin_init() {
			$this->get_options();

			load_plugin_textdomain( 'wp-denyhost', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
			// "Constants" setup.
			$this->url = plugins_url( basename( __FILE__ ), __FILE__ );
			$this->urlpath = plugins_url( '', __FILE__ );
		}

		/**
		 * Initialize the front-end.
		 *
		 * @author soderlind
		 * @version [version]
		 */
		function ps_wp_denyhost_init() {
			global $wpdb;
			$this->get_options();
			if ( $this->options['ps_wp_denyhost_threshold'] && $this->options['ps_wp_denyhost_threshold'] > 0 ) {
				$suspect = $this->get_ip();
				$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(comment_ID) FROM $wpdb->comments  WHERE comment_approved = 'spam' AND comment_author_IP = '%s'", $suspect ) );

				if ( $count >= $this->options['ps_wp_denyhost_threshold'] ) {
					// Add to cloudflare?
					if ( $this->options['ps_wp_denyhost_enable_cloudflare'] ) {
						$this->add_to_cloudflare_blacklist( $suspect );
					}
					switch ( $this->options['ps_wp_denyhost_response'] ) {
						case '404':
							header( 'HTTP/1.0 404 Not Found' );
							break;
						case '403':
							header( 'HTTP/1.1 403 Forbidden' );
							break;
						case 'google':
							header( 'Location: http://www.google.com/' );
							break;
					}
					exit;
				}
			}
		}

		/**
		 * Add an IP-address to CloudFlare blacklist.
		 *
		 * @author soderlind
		 * @version [version]
		 * @param string $ip The IP address.
		 */
		function add_to_cloudflare_blacklist( $ip ) {
			$response = wp_remote_request( CLOUDFLARE_APIURL, array(
					'method'	=> 'POST',
					'timeout'   => 60,
					'sslverify' => false,
					'body'	    => array(
						'email'	=> $this->options['ps_wp_denyhost_cloudflare_email'],
						'tkn'	=> $this->options['ps_wp_denyhost_cloudflare_api'],
						'a'		=> 'ban', // See https://www.cloudflare.com/docs/client-api.html actions ('a') and parameters.
						'key'   => $ip,
					),
				)
			);
		}

		/**
		 * Test if CloudFlare is accessible
		 *
		 * @author soderlind
		 * @version [version]
		 * @param string $email  CloudFlare account username.
		 * @param string $apikey CloudFlare account username.
		 * @param string $errmsg Error message.
		 * @return bool          success or not.
		 */
		function can_access_cloudflare( $email, $apikey, &$errmsg = '' ) {
			$response = wp_remote_request( CLOUDFLARE_APIURL, array(
					'method'	=> 'POST',
					'timeout'   => 60,
					'sslverify' => false,
					'body'	  => array(
						'email'	=> $email,
						'tkn'	  => $apikey,
						'a'		=> 'ip_lkup', // See https://www.cloudflare.com/docs/client-api.html actions ('a') and parameters.
						'ip'   => '0.0.0.0',
					),
				)
			);
			if ( is_wp_error( $response ) ) {
				$errmsg = $response->get_error_message(); // Connection failed etc.
				return false;
			} else {
				$rs = json_decode( wp_remote_retrieve_body( $response ) );
				if ( 'error' == $rs->result ) {
					$errmsg = sprintf( '%s (error code: %s)' ,$rs->msg,$rs->err_code );
					return false;
				} else {
					return true;
				}
			}
		}


		/**
		 * Retrieves the users IP address.
		 *
		 * @credit get_ip() Credits To Lester 'GaMerZ' Chan - http://lesterchan.net
		 */
		function get_ip() {
			if ( empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				$ip_address = $_SERVER['REMOTE_ADDR'];
			} else {
				$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
			}
			if ( strpos( $ip_address, ',' ) !== false ) {
				$ip_address = explode( ',', $ip_address );
				$ip_address = $ip_address[0];
			}
			return $ip_address;
		}


		/**
		 * Retrieves the plugin options from the database.
		 */
		function get_options() {
			$the_options = get_option( $this->options_name, 'empty' );
			if ( 'empty' == $the_options ) {
				$the_options = array(
					'ps_wp_denyhost_threshold' => 3,
					'ps_wp_denyhost_response' => '403',
					'ps_wp_denyhost_enable_cloudflare' => 0,
					'ps_wp_denyhost_cloudflare_email' => '',
					'ps_wp_denyhost_cloudflare_api' => '',
				);
				update_option( $this->options_name, $the_options );
			}
			$this->options = $the_options;
		}
		/**
		 * Saves the admin options to the database.
		 */
		function save_admin_options() {
			return update_option( $this->options_name, $this->options );
		}

		/**
		 * Adds the options subpanel
		 */
		function admin_menu_link() {
			add_options_page( 'WP-DenyHost', 'WP-DenyHost', 'activate_plugins' , basename( __FILE__ ), array( $this, 'admin_options_page' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( &$this, 'filter_plugin_actions' ), 10, 2 );
		}

		/**
		 * Adds the Settings link to the plugin activate/deactivate page.
		 *
		 * @author soderlind
		 * @version [version]
		 * @param array  $links Settings links.
		 * @param string $file  [description].
		 * @return array           Modified links.
		 */
		function filter_plugin_actions( $links, $file ) {
			$settings_link = '<a href="options-general.php?page=' . basename( __FILE__ ) . '">' . __( 'Settings' ) . '</a>';
			array_unshift( $links, $settings_link ); // before other links

			return $links;
		}


		/**
		 * Adds settings/options page.
		 */
		function admin_options_page() {
			$has_errormsg = false;
			if ( isset( $_POST['ps_wp_denyhost_save'] ) ) {
				if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'ps_wp_denyhost-update-options' ) ) {
					die( 'Whoops! There was a problem with the data you posted. Please go back and try again.' );
				}
				$this->options['ps_wp_denyhost_threshold'] = (int) $_POST['ps_wp_denyhost_threshold'];
				$this->options['ps_wp_denyhost_response'] = $_POST['ps_wp_denyhost_response'];
				$this->options['ps_wp_denyhost_cloudflare_email'] = trim( $_POST['ps_wp_denyhost_cloudflare_email'] );
				$this->options['ps_wp_denyhost_cloudflare_api'] = trim( $_POST['ps_wp_denyhost_cloudflare_api'] );

				if ( ! $this->options['ps_wp_denyhost_enable_cloudflare'] && ( isset( $_POST['ps_wp_denyhost_enable_cloudflare'] ) && 'on' == $_POST['ps_wp_denyhost_enable_cloudflare'] ) ) {
					//valid cloudflare email and apikey?
					if ( ! $this->can_access_cloudflare( $this->options['ps_wp_denyhost_cloudflare_email'], $this->options['ps_wp_denyhost_cloudflare_api'], $errmsg ) ) {
						echo '<div id="message" class="error"><p>CloudFlare: ' . $errmsg . '</p></div>';
						$has_errormsg = true;
					}
				}
				$this->options['ps_wp_denyhost_enable_cloudflare'] = ( ! $has_errormsg && isset( $_POST['ps_wp_denyhost_enable_cloudflare'] ) && 'on' == $_POST['ps_wp_denyhost_enable_cloudflare'] ) ? 1 : 0;

				$this->save_admin_options();
				if ( ! $has_errormsg ) {
					echo '<div id="message" class="updated "><p>Success! Your changes were sucessfully saved!</p></div>';
				}
			}
			?>
				<div class="wrap">
				<h2>WP-DenyHost</h2>
				<p>
				<?php _e( 'Based on a users IP address, WP-DenyHost will block a spammer if he already has been tagged as a spammer. Use it together with the Akismet plugin. Akismet tags the spammer, and WP-DenyHost prevents him from adding more comment spam.', 'wp-denyhost' ); ?>
				</p>
				<form method="post" id="ps_wp_denyhost_options">
				<?php wp_nonce_field( 'ps_wp_denyhost-update-options' ); ?>
					<table width="100%" cellspacing="2" cellpadding="5" class="form-table">
						<tr valign="top">
							<th width="33%" scope="row"><?php _e( 'Threshold:', 'wp-denyhost' ); ?></th>
							<td>
								<input name="ps_wp_denyhost_threshold" type="text" id="ps_wp_denyhost_threshold" size="45" value="<?php echo $this->options['ps_wp_denyhost_threshold'] ;?>"/>
								<br /><span class="setting-description"><?php _e( 'Number of comment spams accepted before blocking a user. This is to prevent an innocent commenter, with comments wrongly tagged as spam, from being blocked.', 'wp-denyhost' ); ?>
							</td>
						</tr>
						<tr valign="top">
							<th><label for="ps_wp_denyhost_response"><?php _e( 'Response:', 'wp-denyhost' ); ?></label></th>
							<td>
								<select id="ps_wp_denyhost_response" name="ps_wp_denyhost_response">
									<option value="exit" <?php echo ( 'exit' == $this->options['ps_wp_denyhost_response'] )?'selected="selected"':''?>><?php _e( 'PHP exit', 'wp-denyhost' ); ?></option>
									<option value="404" <?php echo ( '404' == $this->options['ps_wp_denyhost_response'] )?'selected="selected"':''?>><?php _e( '404 Not found', 'wp-denyhost' ); ?></option>
									<option value="403" <?php echo ( '403' == $this->options['ps_wp_denyhost_response'] )?'selected="selected"':''?>><?php _e( '403 Forbidden', 'wp-denyhost' ); ?></option>
								  <option value="google" <?php echo ( 'google' == $this->options['ps_wp_denyhost_response'] )?'selected="selected"':''?>><?php _e( 'Redirect to Google', 'wp-denyhost' ); ?></option>
								</select>
								<br /><span class="setting-description"><?php _e( 'What kind of response a blocked spammer will get', 'wp-denyhost' ); ?>
							</td>
						</tr>
						<tr valign="top">
							<th width="33%" scope="row"><?php _e( '<a href="https://www.cloudflare.com/">CloudFlare</a>:', 'wp-denyhost' ); ?></th>
							<td>
								<input name="ps_wp_denyhost_enable_cloudflare" type="checkbox" id="ps_wp_denyhost_enable_cloudflare" size="45" <?php if ( $this->options['ps_wp_denyhost_enable_cloudflare'] ) {echo 'checked="checked"';}?>"/>
								<span class="setting-description"><?php _e( '<strong>Enable</strong>', 'wp-denyhost' ); ?>
								<br /><span class="setting-description"><?php _e( 'If enabled, WP-DenyHost will add spammers to <a href="https://www.cloudflare.com/threat-control">CloudFlare Block list</a>', 'wp-denyhost' ); ?>
							</td>
						</tr>
						<tr valign="top">
							<th width="33%" scope="row"><?php _e( 'CloudFlare account email:', 'wp-denyhost' ); ?></th>
							<td>
								<input name="ps_wp_denyhost_cloudflare_email" type="text" id="ps_wp_denyhost_cloudflare_email" size="45" value="<?php echo $this->options['ps_wp_denyhost_cloudflare_email'] ;?>"/>
							</td>
						</tr>
						<tr valign="top">
							<th width="33%" scope="row"><?php _e( 'CloudFlare API key:', 'wp-denyhost' ); ?></th>
							<td>
								<input name="ps_wp_denyhost_cloudflare_api" type="text" id="ps_wp_denyhost_cloudflare_api" size="45" value="<?php echo $this->options['ps_wp_denyhost_cloudflare_api'] ;?>"/>
								<span class="setting-description"><?php _e( '(<a href="https://www.cloudflare.com/my-account.html">find this</a>)', 'wp-denyhost' ); ?>
							</td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" name="ps_wp_denyhost_save" class="button-primary" value="<?php _e( 'Save Changes', 'wp-denyhost' ); ?>" /> <a href="/wp-admin/edit-comments.php?comment_status=spam"><?php _e( 'Edit Comment Spam', 'wp-denyhost' ); ?></a>
					</p>
				</form>
				<?php
		}
	} //End Class
} //End if class exists statement

if ( class_exists( 'ps_wp_denyhost' ) ) {
	$ps_wp_denyhost_var = new ps_wp_denyhost();
}
