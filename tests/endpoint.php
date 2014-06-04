<?php
namespace Multisite_JSON_API;

function is_multisite() { return true; }
function is_plugin_active_for_network($name) { return EndpointTest::$plugin_is_active; }
function status_header($status_code) { }

function get_current_site() {
}

require_once 'PHPUnit/Framework/TestCase.php';

class EndpointTest extends \PHPUnit_Framework_TestCase {
	public static $plugin_is_active;
	public static $is_multisite;
	public static $api;
	
	protected function setUp() {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->plugin_is_active = true;
		$this->is_multisite = true;
		$this->api = new Endpoint();
	}

	public function testIsValidSiteTitle() {
		// Ensure that we have at least 1 character and that all characters are alphanumeric spaces or dashes
		$this->assertFalse($this->api->is_valid_site_title(''));
		$this->assertFalse($this->api->is_valid_site_title('!First character is not valid'));
		$this->assertFalse($this->api->is_valid_site_title('?Que?'));

		// Valid examples
		$this->assertTrue($this->api->is_valid_site_title('a'));
		$this->assertTrue($this->api->is_valid_site_title('1'));
		$this->assertTrue($this->api->is_valid_site_title('This is valid'));
		$this->assertTrue($this->api->is_valid_site_title('Hyphens-are-ok'));
	}
}
?>
