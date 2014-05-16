<?php

/*
*	This Classs assumes that WordPress has already been loaded
*	and relies on some wordpress functions.
*
 */
class JSON_API {
	function __construct(){
		$this->json = $this->get_post_data();
	}

	public function respond_with_json($payload, $status=200) {
		\status_header($status);
		echo json_encode($payload)."\n";
		die();
	}

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

	public function get_post_data() {
		$post = file_get_contents('php://input');
		return(json_decode($post));
	}

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
			\set_current_user($u->id, '');
			if(\current_user_can('manage_sites'))
				return $user;
			else
				return false;
		}
	}

	/*
	 * Checks whether sitename is a valid domain name or site name
	 * Works on both domain and subdirectory
	 */
	public function is_valid_sitename($candidate) {
		if (\is_subdomain_install()) {
			/* This filter is documented in wp-includes/ms-functions.php */
			$subdirectory_reserved_names = \apply_filters( 'subdirectory_reserved_names', array( 'page', 'comments', 'blog', 'files', 'feed'));
			return(in_array($candidate, $subdirectory_reserved_names));
		} else {
			return(preg_match('|^([a-zA-Z0-9-])+$|', $candidate));
		}
	}

	public function is_valid_site_title($candidate) {
		// Make sure site title is not empty
		return(preg_match('|^([a-zA-Z0-9-])+|', $candidate));
	}

	public function is_valid_email($candidate) {
		$email = \sanitize_email($candidate);
		if(!empty($email) && \is_email($email))
			return true;
		else
			return false;
	}

	public function full_domain($sitename, $current_site = null) {
		if(empty($current_site))
			$current_site = \get_current_site();
		if(\is_subdomain_install()) {
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
	public function create_user_by_email($dirty_email) {
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

	public function sanity_check() {
		if(\is_multisite()) {
			return true;
		} else {
			error('This is not a multisite install, please enable multisite to use this plugin', 503);
		}
	}

	public function user_can_create_sites() {
		\current_user_can('manage_sites');
	}
}
?>
