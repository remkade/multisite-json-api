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

	function __construct($ID, $login, $blog_id, $password = '') {
		$this->ID = $ID;
		$this->login = $login;
		$this->blog_id = $blog_id;
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
			new WP_User(1, 'admin', 1, 'password'),
			new WP_User(2, 'user', 1, 'password')
		);
		$this->current_user = null;
		$this->current_site = $this->sites[0];
	}

	private final function __clone() {}
	
	public static function get_instance() {
		if(self::$unique_instance === null)
			self::$unique_instance = new WP_State;
		return self::$unique_instance;
	}
}

function is_multisite() { return EndpointTest::$is_multisite; }
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
