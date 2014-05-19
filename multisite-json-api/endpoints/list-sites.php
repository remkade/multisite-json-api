<?php

if(!defined('DOING_AJAX'))
	define('DOING_AJAX', true);
if(!defined('NOBLOGREDIRECT'))
	define('NOBLOGREDIRECT', true);

include_once($_SERVER['DOCUMENT_ROOT'].'/wp-load.php');
include_once('../includes/class-json_api.php');
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

$api = new Multisite_JSON_API_Endpoint();

// Make sure the plugin is actually active
if(!is_plugin_active('multisite-json-api.php'))
	$api->error('This plugin is not active', 500);
/*
 * Authenticate the user using WordPress
 */
$user = $api->authenticate();
if($user) {
	/*
	 * Make sure user can actually create sites
	 */
	if($api->user_can_create_sites) {
		error_log("Attempt to list sites by user '" . $_SERVER['HTTP_USER'] . "', but user does not have permission to manage sites in WordPress.");
		$api->error("You don't have permission to manage sites", 403);
	/*
	 * User can list sites
	 */
	} else {
		$sites = wp_get_sites(array(
			"public" => $_GET['public'],
			"spam" => $_GET['spam'],
			"archived" => $_GET['archived'],
			"deleted" => $_GET['deleted']
		));
		$api->respond_with_json($sites, 200);
	}
} else {
	$api->error('Invalid Username or Password', 403);
	die();
}
?>
