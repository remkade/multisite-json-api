<?php
namespace Multisite_JSON_API;

@include_once 'PHPUnit/Framework/TestCase.php';

class EndpointTest extends \PHPUnit_Framework_TestCase {
	public $api;
	public static $plugin_is_active = true;
	public static $is_multisite = true;
	public static $is_subdomain = true;
	
	protected function setUp() {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		self::$plugin_is_active = true;
		self::$is_multisite = true;
		self::$is_subdomain = true;
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
			array('admin', 'password', get_user_by('login', 'admin')),
			// THis will return false since its not an admin and can't manage sites
			array('user', 'password', false)
		);
	}

	/**
	 * @dataProvider sitenameProvider
	 */
	public function testIsValidSiteName($expected, $sitename) {
		$this->assertEquals($this->api->is_valid_sitename($sitename), $expected);
	}

	public function sitenameProvider() {
		return array(
			array(true, 'potatoes'),
			array(true, 'dashes-are-ok'),
			array(false, 'Odds & Ends * not $ok'),
			array(false, 'No spaces')
		);
	}

	/**
	 * @dataProvider emailProvider
	 */
	public function testIsEmail($expected, $email) {
		$this->assertEquals($this->api->is_valid_email($email), $expected);
	}

	public function emailProvider() {
		return array(
			array(true, 'joe@awesome.com'),
			array(true, 'valid@bbc.co.uk'),
			array(true, 'valid+tag@gmail.com'),
			array(true, 'newproviders@email.email', true),
			array(true, 'testing@email.ninja'),
			array(false, 'notanemail'),
			array(false, 'notanemail.com')
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

	/**
	 * @dataProvider fullDomainWithSubdomainsProvider
	 */
	public function testFullDomainWithSubdomains($current_site, $sitename, $expected) {
		self::$is_subdomain = true;
		$this->assertEquals($expected, $this->api->full_domain($sitename, $current_site));
	}

	public function fullDomainWithSubdomainsProvider() {
		return array(
			array(null, 'potato', 'potato.example.com'),
			array(null, 'test-domain', 'test-domain.example.com'),
			array((object)array('domain'=>'multisite.com'), 'api', 'api.multisite.com')
		);
	}

	/**
	 * @dataProvider fullDomainWithSubdirectoryProvider
	 */
	public function testFullDomainWithSubdirectory($current_site, $sitename, $expected) {
		self::$is_subdomain = false;
		$this->assertEquals($expected, $this->api->full_domain($sitename, $current_site));
	}

	public function fullDomainWithSubdirectoryProvider() {
		return array(
			array(null, 'potato', 'example.com'),
			array(null, 'test-domain', 'example.com'),
			array((object)array('domain'=>'www.example.com'), 'test-domain', 'www.example.com'),
			array((object)array('domain'=>'api.multisite.com'), 'api', 'api.multisite.com')
		);
	}

	/**
	 * @dataProvider fullPathWithSubdirectoryProvider
	 */
	public function testFullPathWithSubdirectory($current_site, $sitename, $expected) {
		self::$is_subdomain = false;
		$this->assertEquals($expected, $this->api->full_path($sitename, $current_site));
	}

	public function fullPathWithSubdirectoryProvider() {
		return array(
			array(null, 'potato', '/potato/'),
			array(null, 'test-domain', '/test-domain/'),
			array((object)array('domain'=>'www.example.com', 'path' => '/sub/'), 'test-site', '/sub/test-site/'),
			array((object)array('domain'=>'api.multisite.com', 'path' => '/blog-with-dashes/'), 'coolsite', '/blog-with-dashes/coolsite/')
		);
	}

	/**
	 * @dataProvider fullPathWithSubdomainProvider
	 */
	public function testFullPathWithSubdomain($current_site, $sitename, $expected) {
		self::$is_subdomain = true;
		$this->assertEquals($expected, $this->api->full_path($sitename, $current_site));
	}

	public function fullPathWithSubdomainProvider() {
		return array(
			array(null, 'potato', '/'),
			array(null, 'test-domain', '/'),
			array((object)array('domain'=>'www.example.com', 'path' => '/sub/'), 'test-site', '/sub/'),
			array((object)array('domain'=>'api.multisite.com', 'path' => '/blog-with-dashes/'), 'coolsite', '/blog-with-dashes/')
		);
	}

	public function testGetOrCreateUserByEmail() {
		$state = WP_State::get_instance();
		$user = $this->api->get_or_create_user_by_email('test@gmail.com', 'testCreateUserByEmail');
		$this->assertEquals($user, get_user_by('login', 'testCreateUserByEmail'));

		// Test again creating the same user, should return exactly the same user
		$user = $this->api->get_or_create_user_by_email('test@gmail.com', 'testCreateUserByEmail');
		$this->assertEquals($user, get_user_by('email', 'test@gmail.com'));
	}

	public function testCreateSiteWithSubdomain() {
		self::$is_subdomain = true;
		$state = WP_State::get_instance();
		$site = $this->api->create_site('Site Title', 'domain', 2);
		$this->assertNotEquals(false, $site);
		$this->assertObjectHasAttribute('blog_id', $site);
		$this->assertObjectHasAttribute('domain', $site);
		$this->assertEquals('domain.example.com', $site->domain);
		$this->assertObjectHasAttribute('path', $site);
		$this->assertEquals('/', $site->path);
	}

	public function testCreateSiteWithSubdirectory() {
		self::$is_subdomain = false;
		$state = WP_State::get_instance();
		$site = $this->api->create_site('Site Title', 'domain', 2);
		$this->assertNotEquals(false, $site);
		$this->assertObjectHasAttribute('blog_id', $site);
		$this->assertObjectHasAttribute('domain', $site);
		$this->assertEquals('example.com', $site->domain);
		$this->assertObjectHasAttribute('path', $site);
		$this->assertEquals('/domain/', $site->path);
	}
	
	public function testDeleteExistingSite() {
		$state = WP_State::get_instance();
		$site = $state->sites[count($state->sites) - 1];
		$site = $this->api->delete_site($site['id']);
		$this->assertNotEquals(false, $site);
		$this->assertObjectHasAttribute('blog_id', $site);
		$this->assertObjectHasAttribute('domain', $site);
		$this->assertObjectHasAttribute('path', $site);
		$this->assertTrue($site->deleted);
	}

	public function testDeleteMissingSite() {
		$state = WP_State::get_instance();
		$site = $this->api->delete_site(9999);
		$this->assertFalse($site);
	}

	public function testGetExistingSiteById() {
		$state = WP_State::get_instance();
		$site = $this->api->get_site_by_id(1);
		$this->assertNotEquals(false, $site);
		$this->assertObjectHasAttribute('blog_id', $site);
		$this->assertObjectHasAttribute('domain', $site);
		$this->assertObjectHasAttribute('path', $site);
	}

	public function testGetMissingSiteById() {
		$state = WP_State::get_instance();
		$site = $this->api->get_site_by_id(9999);
		$this->assertFalse($site);
	}

	public function testSanityCheckWhenNotMultisite() {
		self::$is_multisite = false;
		self::$plugin_is_active = true;
		$this->expectOutputString("{\n    \"id\": \"not_multisite\",\n    \"message\": \"This is not a multisite install! Please enable multisite to use this plugin.\",\n    \"url\": \"http://codex.wordpress.org/Create_A_Network\"\n}");
		$this->api->sanity_check();
	}

	public function testSanityCheckWhenActivated() {
		self::$is_multisite = true;
		self::$plugin_is_active = false;
		$this->expectOutputString("{\n    \"id\": \"plugin_not_active\",\n    \"message\": \"This plugin is not active, please activate it network wide before using.\",\n    \"url\": \"http://codex.wordpress.org/Create_A_Network\"\n}");
		$this->api->sanity_check();
	}
}
?>
