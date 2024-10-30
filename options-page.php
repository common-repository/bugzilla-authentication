<?php
class BugzillaAuthenticationOptionsPage {
	var $plugin;
	var $group;
	var $page;
	var $options;
	var $title;

	function BugzillaAuthenticationOptionsPage($plugin, $group, $page, $options, $title = 'Bugzilla Authentication') {
		$this->plugin = $plugin;
		$this->group = $group;
		$this->page = $page;
		$this->options = $options;
		$this->title = $title;

		add_action('admin_init', array(&$this, 'register_options'));
		add_action('admin_menu', array(&$this, 'add_options_page'));
	}

	/*
	 * Register the options for this plugin so they can be displayed and updated below.
	 */
	function register_options() {
		register_setting($this->group, $this->group, array(&$this, 'sanitize_settings'));

		$section = 'bugzilla_authentication_main';
		add_settings_section($section, 'Main Options', array(&$this, '_display_options_section'), $this->page);
		add_settings_field('bugzilla_authentication_mysql_host', 'MySQL host:', array(&$this, '_display_option_mysql_host'), $this->page, $section);
		add_settings_field('bugzilla_authentication_mysql_port', 'MySQL port:', array(&$this, '_display_option_mysql_port'), $this->page, $section);
		add_settings_field('bugzilla_authentication_mysql_db', 'MySQL DB:', array(&$this, '_display_option_mysql_db'), $this->page, $section);
		add_settings_field('bugzilla_authentication_mysql_user', 'MySQL user:', array(&$this, '_display_option_mysql_user'), $this->page, $section);
		add_settings_field('bugzilla_authentication_mysql_pw', 'MySQL password:', array(&$this, '_display_option_mysql_pw'), $this->page, $section);
		add_settings_field('bugzilla_authentication_login_message', 'Extra loging message:', array(&$this, '_display_option_login_message'), $this->page, $section);
		add_settings_field('bugzilla_authentication_new_user_url', 'New user URL:', array(&$this, '_display_option_new_user_url'), $this->page, $section);
		add_settings_field('bugzilla_authentication_new_user_label', 'New user button label', array(&$this, '_display_option_new_user_label'), $this->page, $section);
		add_settings_field('bugzilla_authentication_auto_create_user', 'Automatically create accounts?', array(&$this, '_display_option_auto_create_user'), $this->page, $section);
	}

	/*
	 * Set the database version on saving the options.
	 */
	function sanitize_settings($input) {
		$output = $input;
		$output['db_version'] = $this->plugin->db_version;

		return $output;
	}

	/*
	 * Add an options page for this plugin.
	 */
	function add_options_page() {
		add_options_page($this->title, $this->title, 'manage_options', $this->page, array(&$this, '_display_options_page'));
	}

	/*
	 * Display the options for this plugin.
	 */
	function _display_options_page() {
		if (! current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
?>
<div class="wrap">
  <h2>Bugzilla Authentication Options</h2>
  <form action="options.php" method="post">
    <?php settings_errors(); ?>
    <?php settings_fields($this->group); ?>
    <?php do_settings_sections($this->page); ?>
    <p class="submit">
      <input type="submit" name="Submit" value="<?php esc_attr_e('Save Changes'); ?>" class="button-primary" />
    </p>
  </form>
</div>
<?php
	}

	/*
	 * Display explanatory text for the main options section.
	 */
	function _display_options_section() {
	}

	/*
	 * Display the MySQL host field.
	 */
	function _display_option_mysql_host() {
		$mysql_host = $this->options['mysql_host'];
		$this->_display_input_text_field('mysql_host', $mysql_host);
?>
Hostname or ip address of MySQL server.
<?php
	}

	/*
	 * Display the MySQL port field.
	 */
	function _display_option_mysql_port() {
		$mysql_port = $this->options['mysql_port'];
		$this->_display_input_text_field('mysql_port', $mysql_port);
?>
TCP IP port number where MySQL server is listening on.
<?php
	}

	/*
	 * Display the MySQL DB field.
	 */
	function _display_option_mysql_db() {
		$mysql_db = $this->options['mysql_db'];
		$this->_display_input_text_field('mysql_db', $mysql_db);
?>
Name of Database to use.
<?php
	}

	/*
	 * Display the MySQL user field.
	 */
	function _display_option_mysql_user() {
		$mysql_user = $this->options['mysql_user'];
		$this->_display_input_text_field('mysql_user', $mysql_user);
?>
Name of Database user to use.
<?php
	}

	/*
	 * Display the MySQL pw field.
	 */
	function _display_option_mysql_pw() {
		$mysql_pw = $this->options['mysql_pw'];
		$this->_display_input_password_field('mysql_pw', $mysql_pw);
?>
Password for Database user to use.
<?php
	}

	/*
	 * Display and extra message on the login page.
	 */
	function _display_option_login_message() {
		$login_message = $this->options['login_message'];
		$this->_display_input_text_field('login_message', $login_message);
?>
Extra message to show on the login screen. Will appear above the username/password box.
<?php
	}

	/*
	 * Display the Bugzilla new user URL field.
	 */
	function _display_option_new_user_url() {
		$new_user_url = $this->options['new_user_url'];
		$this->_display_input_text_field('new_user_url', $new_user_url);
?>
URL to the Bugzilla page to create a new user account.
<?php
	}

	/*
	 * Display the automatically create accounts checkbox.
	 */
	function _display_option_auto_create_user() {
		$auto_create_user = $this->options['auto_create_user'];
		$this->_display_checkbox_field('auto_create_user', $auto_create_user);
?>
Should a new user be created automatically if not already in the WordPress database?<br />
Created users will obtain the role defined under &quot;New User Default Role&quot; on the <a href="options-general.php">General Options</a> page.
<?php
	}

	/*
	 * Display the text on the create new user button.
	 */
	function _display_option_new_user_label() {
		$new_user_label = $this->options['new_user_label'];
		$this->_display_input_text_field('new_user_label', $new_user_label);
?>
Label for on the button to create a new user account. When clicked it will open a new window to the URL of the Bugzilla page to create a new user account.
<?php
	}

	/*
	 * Display a text input field.
	 */
	function _display_input_text_field($name, $value, $size = 75) {
?>
<input type="text" name="<?php echo htmlspecialchars($this->group); ?>[<?php echo htmlspecialchars($name); ?>]" id="bugzilla_authentication_<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value) ?>" size="<?php echo htmlspecialchars($size); ?>" /><br />
<?php
	}

	/*
	 * Display a checkbox field.
	 */
	function _display_checkbox_field($name, $value) {
?>
<input type="checkbox" name="<?php echo htmlspecialchars($this->group); ?>[<?php echo htmlspecialchars($name); ?>]" id="bugzilla_authentication_<?php echo htmlspecialchars($name); ?>"<?php if ($value) echo ' checked="checked"' ?> value="1" /><br />
<?php
	}

	/*
	 * Display a password field.
	 */
	function _display_input_password_field($name, $value, $size = 75) {
?>
<input type="password" name="<?php echo htmlspecialchars($this->group); ?>[<?php echo htmlspecialchars($name); ?>]" id="bugzilla_authentication_<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value) ?>" size="<?php echo htmlspecialchars($size); ?>" /><br />
<?php
	}
}
?>
