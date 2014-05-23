<?php
include_once('../includes/class-json_api.php');

$api = new Multisite_JSON_API_Endpoint();

/*
 * Make sure we are given the correct JSON
 */
if($api->json->title && $api->json->email && $api->json->domain) {
	/*
	 * Authenticate the user using WordPress
	 */
	$user = $api->authenticate();
	if($user) {
		/*
		 * Make sure user can actually create sites
		 */
		if($api->user_can_create_sites()) {
			error_log("Attempt to create site via Multisite JSON API with user '" . $_SERVER['HTTP_USER'] . "', but user does not have permission to manage sites in WordPress.");
			$api->error("You don't have permission to manage sites", 403);
		/*
		 * User can create sites
		 */
		} else {
			/*
			 * Start validating input
			 */
			$errors = array();
			// Domain is valid?
			if(!$api->is_valid_sitename($api->json->domain))
				array_push($errors, "Invalid domain '" . $api->json->domain . "'");
			// Next check Email is valid
			if(!$api->is_valid_email($api->json->email))
				array_push($errors, "Invalid email address: '" . $api->json->email . "'");
			// Make sure Title is valid
			if(!$api->is_valid_site_title($api->json->title))
				array_push($errors, "Invalid site title is '" . $api->json->title . "'");
			if(count($errors))
				$api->error($errors);

			// Start creating stuff
			$user_id = $api->create_user_by_email($api->json->email, $api->json->domain);
			if(is_wp_error($user_id)) {
				print_r($user_id);
				$api->error($user_id);
			}
			$site_id = $api->create_site($api->json->title,
				$api->json->domain,
				$user_id);
			if(is_wp_error($site_id)) {
				$errors = array();
				foreach($site_id->errors as $key => $error_array) {
					array_push($errors, $error_array[0]);
				}
				$api->error($errors);
			}
			$api->send_site_creation_notifications($site_id, $api->json->email);
			$api->respond_with_json(array(
				"success"=>true,
				"messages"=>array('Site created'),
				"url" => str_replace('\\', '', get_site_url($site_id))
			), 201);
		}
	} else {
		$api->error('Invalid Username or Password', 403);
		die();
	}
} else {
	$api->error('This endpoint needs a JSON payload of the form {"title": "Site Title", "email": "user@email.com", "domain": "sitedomain.com"}');
}
?>
