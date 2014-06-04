<?php

namespace Multisite_JSON_API;

class Endpoint {
	function __construct(){
		$this->sanity_check();
		$this->json = $this->get_post_data();
		$this->request_method = $_SERVER['REQUEST_METHOD'];
	}

	/*
	 * Dumps the given object to JSON and responds with the given status code
	 * @since '0.0.1'
	 * @return void
	 */
	public function respond_with_json($payload, $status=200) {
		\status_header($status);
		echo json_format($payload) . "\n";
		die();
	}

	/*
	 * Sends an error in JSON with the given status, then dies
	 * @since '0.0.1'
	 */
	public function error($errors, $status=400) {
		if(is_array($errors)) {
			error_log(join(', ', $errors));
			$output = array('errors' => $errors);
		} else {
			error_log($errors);
			$output = array('errors' => array($errors));
		}
		$this->respond_with_json($output, $status);
	}

	/*
	 * Pulls the post data in a hacky way required by PHP :(
	 * @since '0.0.1'
	 */
	public function get_post_data() {
		$post = file_get_contents('php://input');
		return(json_decode($post));
	}

	/*
	 * Authenticates using the HTTP Headers User and Password
	 * @since '0.0.1'
	 */
	public function authenticate() {
		$creds = array();
		$creds['user_login'] = $_SERVER['HTTP_USER'];
		$creds['user_password'] = $_SERVER['HTTP_PASSWORD'];
		$creds['remember'] = true;
		$user = \wp_signon( $creds, false );
		if(\is_wp_error($user)) {
			return false;
		} else {
			$u = \get_user_by('login', $creds['user_login']);
			\wp_set_current_user($u->ID, '');
			if(\current_user_can('manage_sites'))
				return $user;
			else
				return false;
		}
	}

	/*
	 * Checks whether sitename is a valid domain name or site name
	 * Works on both domain and subdirectory
	 * @since '0.0.1'
	 */
	public function is_valid_sitename($candidate) {
		if (\is_subdomain_install()) {
			return(preg_match('|^[a-zA-Z0-9-]+$|', $candidate));
		} else {
			/* This filter is documented in wp-includes/ms-functions.php */
			$subdirectory_reserved_names = \apply_filters( 'subdirectory_reserved_names', array( 'page', 'comments', 'blog', 'files', 'feed', 'wp-admin'));
			return(!in_array($candidate, $subdirectory_reserved_names));
		}
	}

	/*
	 * Validates that the site title is at least 2 alphanumerics and doesn't start with a space
	 * @since '0.0.1'
	 */
	public function is_valid_site_title($candidate) {
		// Make sure site title is not empty
		return(preg_match('|^[a-zA-Z0-9-_][a-zA-Z0-9-_ ]+|', $candidate));
	}

	/*
	 * Validates emails via the wordpress functions.
	 * @since '0.0.1'
	 * @param email_address
	 */
	public function is_valid_email($candidate) {
		$email = \sanitize_email($candidate);
		if(!empty($email) && \is_email($email))
			return true;
		else
			return false;
	}

	public function full_domain($sitename, $current_site = null) {
		if(empty($current_site))
			$current_site = get_current_site();
		if(is_subdomain_install()) {
			$newdomain = $domain . '.' . preg_replace( '|^www\.|', '', $current_site->domain );
		} else {
			$newdomain = $current_site->domain;
		}
		return $newdomain;
	}

	public function full_path($sitename, $current_site = null) {
		if(empty($current_site))
			$current_site = \get_current_site();
		if(\is_subdomain_install()) {
			$path = $current_site->path;
		} else {
			$path = $current_site->path . $sitename . '/';
		}
		return $path;
	}

	/*
	 * Creates a new user if one doesn't already exist.
	 * If it does exist, just returns the existing user's id.
	 * Sanitizes email address automatically.
	 */
	public function create_user_by_email($dirty_email, $domain) {
		$email = \sanitize_email($dirty_email);
		$user_id = \email_exists($email);
		if ($user_id) {
			return($user_id);
		} else {
			// Create a new user with a random password
			$password = \wp_generate_password(12, false);
			$user_id = \wpmu_create_user($domain, $password, $email);
			if($user_id)
				\wp_new_user_notification($user_id, $password);
			return($user_id);
		}
	}

	public function create_site($title, $domain, $user_id) {
		$current_site = \get_current_site();
		return wpmu_create_blog($this->full_domain($domain, $current_site),
			$this->full_path($domain, $current_site),
			$title,
			$user_id,
			array('public' => true),
			$current_site->id);
	}

	/*
	 * Wraps the wordpress delete blog function
	 * Apparently, this returns NULL always, so I wrap it to return the site or false if site doesn't exist.
	 * @since '0.5.0'
	 */
	public function delete_site($id, $drop = false) {
		$site = $this->get_site_by_id($id);
		if($site){
			\wpmu_delete_blog($id, $drop);
			$site->deleted = true;
			return $site;
		} else {
			return false;
		}
	}

	/*
	 * Wraps the wordpress get_blog_details function and converts attributes to proper JSON values.
	 * @since '0.5.0'
	 */
	public function get_site_by_id($id) {
		$site = \get_blog_details($id);
		if($site && !\is_wp_error($site))
			return $this->site_strings_to_values($site);
		else
			return $site;
	}

	/*
	 * TODO Have the automatic email thing configurable through Admin panel
	 */
	public function send_site_creation_notifications($id, $dirty_email) {
		$email = \sanitize_email($dirty_email);
		// Set the contents of the email
		$admin_content = sprintf("New site created by Multisite JSON API User: %1$s\n\n\n\tAddress: %2$s\nName: %3$s",
			$current_user->user_login,
			\get_site_url($id),
			\wp_unslash($title));

		// Send the email to admins
		\wp_mail(\get_site_option('admin_email'),
			sprintf('[%s] New Site Created', $current_site->site_name),
			$admin_content,
			'From: "Mannasites Webmonkey" <' . \get_site_option('admin_email') . '>');

		// Send the email to the owner of the new site
		\wpmu_welcome_notification( $id, $user_id, $password, $title, array( 'public' => 1 ));
	}

	private function plugin_is_active() {
		if(! \is_plugin_active_for_network('multisite-json-api/multisite-json-api.php'))
			$this->error('This plugin is not active', 500);
	}

	/*
	 * Check that this is Multsite and that this plugin is active
	 * @since '0.0.1'
	 */
	public function sanity_check() {
		if(is_multisite()) {
			if(! is_plugin_active_for_network('multisite-json-api/multisite-json-api.php'))
				$this->error('This plugin is not active', 500);
			else
				return true;
		} else {
			error('This is not a multisite install, please enable multisite to use this plugin', 503);
		}
	}

	public function user_can_create_sites() {
		\current_user_can('manage_sites');
	}

	/*
	 * Fixes the odd string values returned by wordpress so that the JSON is correct
	 */
	public function fix_site_values($sites) {
		return array_map('self::site_strings_to_values', $sites);
	}

	/*
	 * This is necessary to convert all the odd strings wordpress gives to proper JSON values.
	 * Sometimes WP gives an object and sometimes it gives just an array. I don't understand.
	 * @since '0.5.0'
	 */
	public function site_strings_to_values($bad_site) {
		// For cases when we have an array, convert it to an object
		if(is_array($bad_site))
			$bad_site = (object)$bad_site;

		// Clone the original so we don't modify the original
		$site = clone($bad_site);

		// Fix the bad strings
		$site->blog_id = intval($bad_site->blog_id);
		$site->site_id = intval($bad_site->site_id);
		$site->lang_id = intval($bad_site->lang_id);
		$site->spam = $this->string_int_to_boolean($bad_site->spam);
		$site->public = $this->string_int_to_boolean($bad_site->public);
		$site->mature = $this->string_int_to_boolean($bad_site->mature);
		$site->deleted = $this->string_int_to_boolean($bad_site->deleted);
		$site->archived = $this->string_int_to_boolean($bad_site->archived);
		return $site;
	}

	/*
	 * Takes a string "0" or "1" and returns an actual boolean
	 */
	public function string_int_to_boolean($string) {
		if(intval($string))
			return true;
		else
			return false;
	}
}
?>
