<?php
namespace MultiSite_JSON_API;

class GenericException extends \Exception {
	public $url;
	public $id;

	public function __construct($message = '', $id = '', $url = 'http://github.com/remkade/multisite-json-api') {
		parent::__construct($message, $code = 500, NULL);
		$this->url = $url;
		$this->id = $id;
	}
}

class SiteNotFoundException extends \Exception {
	public function __construct($message = 'Error Creating Site', $id = 'site_creation_error', $url = 'http://github.com/remkade/multisite-json-api') {
		parent::__construct($message, $id, $url);
	}
}
class SiteCreationException extends \Exception {}
