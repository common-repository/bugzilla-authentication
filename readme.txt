=== Bugzilla Authentication ===
Contributors: wpmiv
Tags: authentication, bugzilla
Requires at least: 3.0
Tested up to: 3.3.1
Stable tag: 1.1

Use the Bugzilla MySQL database as an external authentication source in WordPress.

== Description ==

The Bugzilla Authentication plugin allows you to authenticate a user also against the profiles table in a Bugzilla database.

= Requirements =
- Network access to your Bugzilla MySQL database.
- PHP with MySQL support.
- Bugzilla version 3.* or higher. Tested with version 4.2

= More info =
To follow updates to this plugin, visit:

http://www.1st-setup.nl/wordpress/?page_id=343

For help with this version, visit:

http://www.1st-setup.nl/wordpress/?page_id=343

== Installation ==

1. Login as an existing user, such as admin.
2. Upload the `bugzilla-authentication` folder to your plugins folder, usually `wp-content/plugins`. (Or simply via the built-in installer.)
3. Activate the plugin on the Plugins screen.
4. Configure the plugin settings. 
5. If auto create user is turned off add one or more users to WordPress, specifying the Bugzilla username for the Nickname field. Also be sure to set the role for each user. Or set auto create user on.
6. Logout.
7. Protect `wp-login.php` and `wp-admin` using your external authentication (using, for example, `.htaccess` files).
8. Try logging in with your Bugzilla user account and password.

Note: This version works with WordPress 3.0 and above.

== Frequently Asked Questions ==

= How does this plugin authenticate users? =

When a user tries to login the plugin will try to find the user in the profiles table, identified by login_name, of the specified Bugzilla database. When the user account is enabled in Bugzilla and found it will use the salt of the crypted Bugzilla password to generate a crypted version of the specified login password and if the crypted passwords match it will log you in.

If it did not find a valid username or password match in the Bugzilla database it will try to authenticate against the WordPress user list.

By default, this plugin generates a random password each time you create a user or edit an existing user's profile. However, since this plugin requires an external authentication mechanism, this password is not requested by WordPress. Generating a random password helps protect accounts, preventing one authorized user from pretending to be another.

= If I disable this plugin, how will I login? =

When you disable this plugin you are left only with the default authentication against the WordPress user list.

Because this plugin generates a random password when you create a new user or edit an existing user's profile, you will most likely have to reset each user's password if you disable this plugin. WordPress provides a link for requesting a new password on the login screen.

Also, you should leave the `admin` user as a fallback, i.e. create a new account to use with this plugin. As long as you don't edit the `admin` profile, WordPress will store the password set when you installed WordPress.

In the worst case scenario, you may have to use phpMyAdmin or the MySQL command line to [reset a user's password](http://codex.wordpress.org/Resetting_Your_Password).

= Can I configure the plugin to support standard WordPress logins? =

Yes. You can authenticate some users via an external, single sign-on system and other users via the built-in username and password combination. (Note: When mixed authentication is in use, this plugin does not scramble passwords as described above.)

= Does this plugin support multisite (WordPress MU) setups? =

Yes, you can enable this plugin across a network or on individual sites. However, options will need to be set on individual sites.

If you have suggestions on how to improve network support, please submit a comment.

== Screenshots ==

1. Plugin options, allowing Bugzilla authentication
2. WordPress login form with Bugzilla create new user button and Extra message text

== Changelog ==

= 1.0 =
* First release.

== Upgrade Notice ==

= 1.0 =
First release

