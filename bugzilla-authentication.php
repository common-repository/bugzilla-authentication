<?php
/*
Plugin Name: Bugzilla Authentication
Version: 1.1
Plugin URI: http://www.1st-setup.nl/
Description: Authenticate users against the Bugzilla profiles database and WordPress user list. 
Author: Michel Verbraak
Author URI: http://www.1st-setup.nl/
Date: 8th of March 2012
*/

/* For this plugin I used the HTTP Authentication, from Daniel Westermann-Clark, as an example. */

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'options-page.php');

class BugzillaAuthenticationPlugin {
	var $db_version = 2;
	var $option_name = 'bugzilla_authentication_options';
	var $options;
	var $debug = false;


	function BugzillaAuthenticationPlugin() {

		$this->options = get_option($this->option_name);

		if (is_admin()) {
			$options_page = new BugzillaAuthenticationOptionsPage(&$this, $this->option_name, __FILE__, $this->options);
			add_action('admin_init', array(&$this, 'check_options'));
		}

		add_action('login_head', array(&$this, 'add_login_css'));
		add_action('login_footer', array(&$this, 'add_create_user_link'));
		add_action('check_passwords', array(&$this, 'generate_password'), 10, 3);
		add_filter('login_url', array(&$this, 'bypass_reauth'));
		add_filter('show_password_fields', array(&$this, 'allow_wp_auth'));
		add_filter('allow_password_reset', array(&$this, 'allow_wp_auth'));
		add_filter('authenticate', array(&$this, 'authenticate'), 10, 3);
		add_filter('login_message', array(&$this, 'get_login_message') );
	}

	function logLine($aLine)
	{
		if ($this->debug) {
			$fh = fopen("/tmp/wp.txt","a");
			fwrite($fh, $aLine);
			fclose($fh);
		}
	}

	function get_login_message($message) {
		echo $message."<P>".$this->options['login_message']."</P>";
	}

	/*
	 * Check the options currently in the database and upgrade if necessary.
	 */
	function check_options() {
		if ($this->options === false || ! isset($this->options['db_version']) || $this->options['db_version'] < $this->db_version) {
			if (! is_array($this->options)) {
				$this->options = array();
			}

                        $current_db_version = isset($this->options['db_version']) ? $this->options['db_version'] : 0;
			$this->upgrade($current_db_version);
			$this->options['db_version'] = $this->db_version;
			update_option($this->option_name, $this->options);
                }
	}

	/*
	 * Upgrade options as needed depending on the current database version.
	 */
	function upgrade($current_db_version) {
		$default_options = array(
			'mysql_host' => 'localhost',
			'mysql_port' => '3306',
			'mysql_db' => 'bugs',
			'mysql_user' => 'bugs',
			'mysql_pw' => '',
			'new_user_url' => 'https://localhost/bugzilla/createaccount.cgi',
			'auto_create_user' => false,
			'auth_label' => 'Bugzilla authenticate',
			'new_user_label' => 'Create new Bugzilla user',
			'login_message' => 'You can now also login with your Bugzilla user account.',
		);

		if ($current_db_version < $this->db_version) {
			foreach ($default_options as $key => $value) {
				// Handle migrating existing options from before we stored a db_version
				if (! isset($this->options[$key])) {
					$this->options[$key] = $value;
				}
			}
		}
	}

	function add_login_css() {
?>
<style type="text/css">
p#bugzilla-authentication-link {
	width: 100%;
    height: 4em;
    text-align: center;
    margin-top: 2em;
}
p#bugzilla-authentication-link a {
    margin: 0 auto;
    float: none;
}
</style>
<?php
	}

	/*
	 * Add a link to the create user page of Bugzilla.
	 */
	function add_create_user_link() {
		global $redirect_to;

		$new_user_url = $this->options['new_user_url'];
		$new_user_label = $this->options['new_user_label'];

		echo "\t" . '<p id="bugzilla-authentication-link"><a class="button-primary" href="' . htmlspecialchars($new_user_url) . '" target="_blank">' . htmlspecialchars($new_user_label) . '</a></p>' . "\n";
	}

	/*
	 * Generate a password for the user. This plugin does not require the
	 * administrator to enter this value, but we need to set it so that user
	 * creation and editing works.
	 */
	function generate_password($username, $password1, $password2) {
		if (! $this->allow_wp_auth()) {
			$password1 = $password2 = wp_generate_password();
		}
	}

	/*
	 * Remove the reauth=1 parameter from the login URL, if applicable. This allows
	 * us to transparently bypass the mucking about with cookies that happens in
	 * wp-login.php immediately after wp_signon when a user e.g. navigates directly
	 * to wp-admin.
	 */
	function bypass_reauth($login_url) {
		$login_url = remove_query_arg('reauth', $login_url);

		return $login_url;
	}

	/*
	 * Can we fallback to built-in WordPress authentication?
	 */
	function allow_wp_auth() {
		return true;
	}

	/*
	 * Authenticate the user, first using the external authentication source.
	 * If allowed, fall back to WordPress password authentication.
	 */
	function authenticate($user, $username, $password) {
		$this->logLine("authenticate:".$username."\n");

		$user = $this->check_bugzilla_user($username, $password);

		if (! is_wp_error($user)) {
			// User was authenticated via REMOTE_USER
			$user = new WP_User($user->ID);
		}
		else {
			// REMOTE_USER is invalid; now what?

			if (! $this->allow_wp_auth()) {
				// Bail with the WP_Error when not falling back to WordPress authentication
				wp_die($user);
			}

			// Fallback to built-in hooks (see wp-includes/user.php)
		}

		return $user;
	}

	/*
	 * Check against the specified Bugzilla database
	 */
	function check_bugzilla_user($username, $password) {

		// Create DB connection.
		$mysql_server=$this->options['mysql_host'].":".$this->options['mysql_port'];
		$mysql_user=$this->options['mysql_user'];
		$mysql_password=$this->options['mysql_pw'];

		$mysql_link = mysql_connect($mysql_server, $mysql_user, $mysql_password);

		if (!$mysql_link) {
			return new WP_Error('no_mysql_connection', '<strong>ERROR</strong>: Could not connect to Bugzilla MySQL server.');
		}

		$mysql_database=$this->options['mysql_db'];
		$mysql_db_link = mysql_select_db($mysql_database, $mysql_link);

		if (!$mysql_db_link) {
			return new WP_Error('no_db_connection', '<strong>ERROR</strong>: Could not select the Bugzilla database.');
		}

		if (strpos($username, 'is_enabled') > -1) {
			return new WP_Error('wrong_username', '<strong>ERROR</strong>: Invalid username.');
		}

		// select profile record from Bugzilla
		$query = "SELECT * FROM profiles WHERE login_name='". mysql_real_escape_string($username)."' AND is_enabled=1";
		if ($result = mysql_query($query, $mysql_link)) {
			if ($row = mysql_fetch_assoc($result)) {
				$crypt_pw = $row["cryptpassword"];
			}
			else {
				return new WP_Error('authentication_failed', __('<strong>ERROR</strong>: Invalid username or incorrect password.'));
			}
		}
		else {
			return new WP_Error('authentication_failed', __('<strong>ERROR</strong>: Invalid username or incorrect password.'));
		}

		$this->logLine("user:".$username."\ncrypt_pw:".$crypt_pw."\n");
		// Generate password
		// echo $salt.base64_encode(hash("sha256",$password.$salt, true))
		$salt = substr($crypt_pw,0, 8);
		$calc_crypt_pw = $salt.base64_encode(hash("sha256",$password.$salt, true));
		$calc_crypt_pw = substr($calc_crypt_pw, 0, strlen($calc_crypt_pw)-1)."{SHA-256}";
		$this->logLine("user:".$username."\ncalc_crypt_pw:".$calc_crypt_pw."\n");
		if ($calc_crypt_pw != $crypt_pw) {
			return new WP_Error('authentication_failed', __('<strong>ERROR</strong>: Invalid username or incorrect password.'));
		}

		// Create new users automatically, if configured
		$user = get_userdatabylogin($username);
		if (! $user)  {
			if ((bool) $this->options['auto_create_user']) {
				$user = $this->_create_user($username, $row);
			}
			else {
				// Bail out to avoid showing the login form
				$user = new WP_Error('authentication_failed', __('<strong>ERROR</strong>: Invalid username or incorrect password.'));
			}
		}

		return $user;
	}

	/*
	 * Create a new WordPress account for the specified username.
	 */
	function _create_user($username, $bugzilla_data) {
		$password = wp_generate_password();

		require_once(WPINC . DIRECTORY_SEPARATOR . 'registration.php');
		$user_id = wp_create_user($username, $password, $username);
		wp_update_user( array ('ID' => $user_id, 'display_name' => $bugzilla_data['realname'], 'user_identity' => $bugzilla_data['realname']) ) ;
		$user = get_user_by('id', $user_id);

		return $user;
	}

	/*
	 * Fill the specified URI with the site URI and the specified return location.
	 */
	function _generate_uri($uri, $redirect_to) {
		// Support tags for staged deployments
		$base = $this->_get_base_url();

		$tags = array(
			'host' => $_SERVER['HTTP_HOST'],
			'base' => $base,
			'site' => home_url(),
			'redirect' => $redirect_to,
		);

		foreach ($tags as $tag => $value) {
			$uri = str_replace('%' . $tag . '%', $value, $uri);
			$uri = str_replace('%' . $tag . '_encoded%', urlencode($value), $uri);
		}

		// Support previous versions with only the %s tag
		if (strstr($uri, '%s') !== false) {
			$uri = sprintf($uri, urlencode($redirect_to));
		}

		return $uri;
	}

	/*
	 * Return the base domain URL based on the WordPress home URL.
	 */
	function _get_base_url() {
		$home = parse_url(home_url());
		$base = str_replace(array($home['path'], $home['query'], $home['fragment']), '', home_url());

		return $base;
	}
}

// Load the plugin hooks, etc.
$bugzilla_authentication_plugin = new BugzillaAuthenticationPlugin();
?>
