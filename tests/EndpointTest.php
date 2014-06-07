<?php
namespace Multisite_JSON_API;


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

	public function testErrorConformsToHerokuErrors(){
		$this->expectOutputString("{\n    \"id\": \"error_id\",\n    \"message\": \"Error!\",\n    \"url\": \"http://github.com/remkade/multisite-json-api/wiki\"\n}");
		$this->api->error("Error!", "error_id", 400);
	}

	/**
	 * @dataProvider authenticateProvider
	 */
	public function testAuthenticate($username, $password, $result) {
		$_SERVER['HTTP_USER'] = $username;
		$_SERVER['HTTP_PASSWORD'] = $password;

		$this->assertEquals($this->api->authenticate(), $result);
	}

	public function authenticateProvider() {
		return array(
			array('invalid', 'invalid', false),
			array('fakeuser', 'password', false),
			array('user', 'not the right password', false),
			array('admin', 'password', get_user_by('login', 'admin'))
		);
	}

	public function testIsValidSiteTitle(){
		// Ensure that we have at least 1 character and that all characters are alphanumeric spaces or dashes
		$this->assertFalse($this->api->is_valid_site_title(''));
		$this->assertFalse($this->api->is_valid_site_title('!First character is not valid'));
		$this->assertFalse($this->api->is_valid_site_title('?Que?'));

		// Valid examples
		$this->assertTrue($this->api->is_valid_site_title('a1'));
		$this->assertTrue($this->api->is_valid_site_title('123'));
		$this->assertTrue($this->api->is_valid_site_title('singleword'));
		$this->assertTrue($this->api->is_valid_site_title('This is valid'));
		$this->assertTrue($this->api->is_valid_site_title('Hyphens-are-ok'));
	}
}
?>
