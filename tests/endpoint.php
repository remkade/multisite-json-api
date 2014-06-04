<?php
namespace Multisite_JSON_API;

function is_multisite() { return true; }
function is_plugin_active_for_network($name) { return EndpointTest::$plugin_is_active; }

function get_current_site() {
}

require_once 'PHPUnit\Framework\TestCase.php';

class EndpointTest extends \PHPUnit_Framework_TestCase {
	public $plugin_is_active;
	public $is_multisite;
	public $api;
	
	protected function setUp() {
		$plugin_is_active = true;
		$is_multisite = true;
		$api = new Endpoint();
	}

	public function testSanityCheckFailsWhenPluginDisabled() {
	}

	public function testIsValidSiteTitle() {
		$this->assertEqual($api->is_valid_site_title(''), false);
	}
}

?>
