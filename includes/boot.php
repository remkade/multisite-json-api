<?php
// Offloaded so we can test things in an isolated way
if(!defined('DOING_AJAX'))
	define('DOING_AJAX', true);
if(!defined('NOBLOGREDIRECT'))
	define('NOBLOGREDIRECT', true);

include_once('../../../../wp-load.php');
include_once(ABSPATH . 'wp-admin/includes/plugin.php');
include_once(ABSPATH . 'wp-admin/includes/ms.php');
?>
