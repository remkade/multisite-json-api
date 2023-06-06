<?php
namespace MultiSite_JSON_API;

class GenericException extends \Exception {
	public $url;
	public $id;

	public function __construct($message = '', $id = '', $url = 'https://github.com/remkade/multisite-json-api', $code = 400, Exception $previous = null) {
		parent::__construct($message, $code);
		$this->url = $url;
		$this->id = $id;
	}
}

class SiteNotFoundException extends GenericException {
	public function __construct($message = 'Unable to Find Site', $id = 'site_not_found', $url = 'https://github.com/remkade/multisite-json-api') {
		parent::__construct($message, $id, $url, 404);
	}
}
class SiteCreationException extends GenericException {
	public function __construct($message = 'Error Creating Site', $id = 'site_creation_error', $url = 'https://github.com/remkade/multisite-json-api') {
		parent::__construct($message, $id, $url, 400);
	}
}

class UserCreationException extends GenericException {
	public function __construct($message = 'Error Creating User', $id = 'user_creation_error', $url = 'https://github.com/remkade/multisite-json-api') {
		parent::__construct($message, $id, $url, 400);
	}
}
