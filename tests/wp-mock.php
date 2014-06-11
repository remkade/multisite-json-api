<?php
namespace Multisite_JSON_API;


class WP_Error {
	public $code;
	public $message;
	public $data;

	function __construct($code = '', $message = '', $data = '') {
		$this->code = $code;
		$this->message = $message;
		$this->data = $data;
	}
}

class WP_User {
	public $ID;
	public $login;
	public $blog_id;
	public $password;
	public $email;

	function __construct($ID, $login, $blog_id, $password = '', $email = '') {
		$this->ID = $ID;
		$this->login = $login;
		$this->blog_id = $blog_id;
		$this->email = $email;
		$this->password = $password;
	}
}

class WP_State {
	public $users;
	public $sites;
	public $current_user;
	public $current_site;
	public static $unique_instance;

	protected function __construct() {
		$this->sites = array(
			array('blog_id' => 1,
				'site_id' => 1,
				'domain' => 'example.com',
				'path' => '/',
				'registered' => date('Y-m-d H:M:S'),
				'last_updated' => date('Y-m-d H:M:S'),
				'public' => 1,
				'archived' => 0,
				'mature' => 0,
				'spam' => 0,
				'deleted' => 0,
				'lang_id' => 0
			),
			array('blog_id' => 1,
				'site_id' => 1,
				'domain' => 'widgets.example.com',
				'path' => '/',
				'registered' => date('Y-m-d H:M:S'),
				'last_updated' => date('Y-m-d H:M:S'),
				'public' => 1,
				'archived' => 0,
				'mature' => 0,
				'spam' => 0,
				'deleted' => 0,
				'lang_id' => 0
			)
		);
		$this->users = array(
			new WP_User(1, 'admin', 1, 'password', 'admin@example.com'),
			new WP_User(2, 'user', 1, 'password', 'user@example.com')
		);
		$this->current_user = null;
		$this->current_site = (object)$this->sites[0];
	}

	private final function __clone() {}
	
	public static function get_instance() {
		if(self::$unique_instance === null)
			self::$unique_instance = new WP_State;
		return self::$unique_instance;
	}
}

function is_multisite() { return EndpointTest::$is_multisite; }
function is_subdomain_install() { return EndpointTest::$is_subdomain; }
function is_plugin_active_for_network($name) { return EndpointTest::$plugin_is_active; }
function status_header($status_code) { return; }

function get_user_by($property = 'login', $value) {
	$found = false;
	$state = WP_State::get_instance();
	foreach($state->users as $user) {
		if($user->$property === $value) {
			$found = $user;
			break;
		}
	}
	return $found;
}

function wp_signon($args, $secure_cookie = false) {
	$user = get_user_by('login', $args['user_login']);
	if($user) {
		if($user->password == $args['user_password'])
			return $user;
		else
			return new WP_Error(403, 'Invalid username or password');
	} else {
		return new WP_Error(403, 'Invalid username');
	}
}

function apply_filters($filtername, $args) {
	if($filtername == 'subdirectory_reserved_names') {
		return array_merge($args, array('wp-admin', 'wp-content', 'wp-includes'));
	}
	return false;
}

function sanitize_email( $email ) {
	// Test for the minimum length the email can be
	if ( strlen( $email ) < 3 ) {
		/**
		 * Filter a sanitized email address.
		 *
		 * This filter is evaluated under several contexts, including 'email_too_short',
		 * 'email_no_at', 'local_invalid_chars', 'domain_period_sequence', 'domain_period_limits',
		 * 'domain_no_periods', 'domain_no_valid_subs', or no context.
		 *
		 * @since 2.8.0
		 *
		 * @param string $email   The sanitized email address.
		 * @param string $email   The email address, as provided to sanitize_email().
		 * @param string $message A message to pass to the user.
		 */
		return "";
	}

	// Test for an @ character after the first position
	if ( strpos( $email, '@', 1 ) === false ) {
		/** This filter is documented in wp-includes/formatting.php */
		return "";
	}

	// Split out the local and domain parts
	list( $local, $domain ) = explode( '@', $email, 2 );

	// LOCAL PART
	// Test for invalid characters
	$local = preg_replace( '/[^a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~\.-]/', '', $local );
	if ( '' === $local ) {
		return "";
	}

	// DOMAIN PART
	// Test for sequences of periods
	$domain = preg_replace( '/\.{2,}/', '', $domain );
	if ( '' === $domain ) {
		return "";
	}

	// Test for leading and trailing periods and whitespace
	$domain = trim( $domain, " \t\n\r\0\x0B." );
	if ( '' === $domain ) {
		return "";
	}

	// Split the domain into subs
	$subs = explode( '.', $domain );

	// Assume the domain will have at least two subs
	if ( 2 > count( $subs ) ) {
		return "";
	}

	// Create an array that will contain valid subs
	$new_subs = array();

	// Loop through each sub
	foreach ( $subs as $sub ) {
		// Test for leading and trailing hyphens
		$sub = trim( $sub, " \t\n\r\0\x0B-" );

		// Test for invalid characters
		$sub = preg_replace( '/[^a-z0-9-]+/i', '', $sub );

		// If there's anything left, add it to the valid subs
		if ( '' !== $sub ) {
			$new_subs[] = $sub;
		}
	}

	// If there aren't 2 or more valid subs
	if ( 2 > count( $new_subs ) ) {
		return "";
	}

	// Join valid subs into the new domain
	$domain = join( '.', $new_subs );

	// Put the email back together
	$email = $local . '@' . $domain;

	// Congratulations your email made it!
	/** This filter is documented in wp-includes/formatting.php */
	return $email;
}

function is_email($email) {
	if ( strlen( $email ) < 3 ) {
		return false;
	}

	// Test for an @ character after the first position
	if ( strpos( $email, '@', 1 ) === false ) {
		return false;
	}

	// Split out the local and domain parts
	list( $local, $domain ) = explode( '@', $email, 2 );
	if ( !preg_match( '/^[a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~\.-]+$/', $local ) ) {
		return false;
	}
	if ( preg_match( '/\.{2,}/', $domain ) ) {
		return false;
	}
	if ( trim( $domain, " \t\n\r\0\x0B." ) !== $domain ) {
		return false;
	}
	$subs = explode( '.', $domain );
	if ( 2 > count( $subs ) ) {
		return false;
	}
	foreach ( $subs as $sub ) {
		if ( trim( $sub, " \t\n\r\0\x0B-" ) !== $sub ) {
			return false;
		}

		// Test for invalid characters
		if ( !preg_match('/^[a-z0-9-]+$/i', $sub ) ) {
			return false;
		}
	}

	// Congratulations your email made it!
	return $email;
}

function is_wp_error($thing) {
	if($thing instanceof WP_Error)
		return true;
	else
		return false;
}

function set_current_site($site) {
	$state = WP_State::get_instance();
	$state->current_site = $site;
}

function get_current_site() {
	$state = WP_State::get_instance();
	return $state->current_site;
}

function get_current_user() {
	$state = WP_State::get_instance();
	return $state->current_user;
}

// This one is hard coded because we only require one permission
// and that permission is granted only to admins
function current_user_can($permission) {
	if(get_current_user()->login == 'admin')
		return true;
	else
		return false;
}

function wp_set_current_user($id) {
	$state = WP_State::get_instance();
	$state->current_user = get_user_by('ID', $id);
}
?>
