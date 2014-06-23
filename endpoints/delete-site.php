<?php
include_once('../includes/boot.php');
include_once('../includes/class-endpoint.php');

$api = new Multisite_JSON_API\Endpoint();

/*
 * Make sure we are given the correct JSON
 */
if(isset($api->json->blog_id)) {
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
			die();
		/*
		 * User can create sites
		 */
		} else {
			// Start killing stuff
			try {
				$site = $api->delete_site($api->json->blog_id, $api->json->drop);
				if($site) {
					$site = $api->site_strings_to_values($api->get_site_by_id($site_id));
					$api->respond_with_json($site, 202);
				}
			} catch(MultiSite_JSON_API\SiteNotFoundException $e) {
				$api->json_exception($e);
				die();
			}
		}
	} else {
		$api->error('Invalid Username or Password', 403);
		die();
	}
} else {
	$api->error('This endpoint needs a JSON payload of the form {"blog_id": 1, "drop": true}');
}
?>
