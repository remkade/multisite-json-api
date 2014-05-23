<?php
include_once('../includes/class-json_api.php');

$api = new Multisite_JSON_API_Endpoint();

/*
 * Make sure we are given the correct JSON
 */
if($api->json->id) {
	if(!isset($api->json->drop))
		$api->json->drop = false;

	/*
	 * Authenticate the user using WordPress
	 */
	$user = $api->authenticate();
	if($user) {
		/*
		 * Make sure user can actually create sites
		 */
		if($api->user_can_create_sites()) {
			error_log("Attempt to delete site with user '" . $_SERVER['HTTP_USER'] . "', but user does not have permission to manage sites in WordPress.");
			$api->error("You don't have permission to manage sites", 403);
		/*
		 * User can create sites
		 */
		} else {
			// Start killing stuff
			$site_id = $api->delete_site($api->json->id, $api->json->drop);
			if(is_wp_error($site_id)) {
				$errors = array();
				foreach($site_id->errors as $key => $error_array) {
					array_push($errors, $error_array[0]);
				}
				$api->error($errors);
			}

			$api->respond_with_json(array(
				"success"=>true,
				"messages"=>array('Site deleted'),
				"url" => str_replace('\\', '', get_site_url($site_id))
			), 202);
		}
	} else {
		$api->error('Invalid Username or Password', 403);
		die();
	}
} else {
	$api->error('This endpoint needs a JSON payload of the form {"id": 1, "drop": true}');
}
?>
