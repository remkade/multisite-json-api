<?php
include_once('../includes/boot.php');
include_once('../includes/class-endpoint.php');

$api = new Multisite_JSON_API\Endpoint();

/*
 * Authenticate the user using WordPress
 */
$user = $api->authenticate();

if($user) {
	/*
	 * Make sure user has permissions to create sites
	 */
	if($api->user_can_create_sites()) {
		error_log("Attempt to list sites by user '" . $_SERVER['HTTP_USER'] . "', but user does not have permission to manage sites in WordPress.");
		$api->error("You don't have permission to manage sites", 403);
	/*
	 * User can create sites, so let them list sites
	 */
	} else {
		$public = null;
		$spam = null;
		$archived = null;
		$deleted = null;

		if(isset($_GET['public']))
			$public = $_GET['public'];
		if(isset($_GET['spam']))
			$spam = $_GET['spam'];
		if(isset($_GET['archived']))
			$archived = $_GET['archived'];
		if(isset($_GET['deleted']))
			$deleted = $_GET['deleted'];

		$sites = get_sites(array(
			"public" => $public,
			"spam" => $spam,
			"archived" => $archived,
			"deleted" => $deleted
		));
		$fixed = $api->fix_site_values($sites);
		$api->respond_with_json($fixed, 200);
	}
} else {
	$api->error('Invalid Username or Password', 403);
	die();
}
?>
