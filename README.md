![Travis CI Build status](https://travis-ci.org/remkade/multisite-json-api.svg?branch=master) [![Code Climate](https://codeclimate.com/github/remkade/multisite-json-api.png)](https://codeclimate.com/github/remkade/multisite-json-api)
Wordpress Multisite JSON API
============================
This is a Wordpress Plugin that adds JSON endpoints for creating, listing, and deleting sites on multisite.

This plugin aims to be simple to make Wordpress polyglot environments not only possible, but practical. I'm not the best at PHP, ruby and Go are more my thing, hence why I'm making this API. Contributions are greatly appreciated.

Status
------
* **TODO**: Add some configuration options
* **TODO**: Add `full-stack-test.php` to make a MySQL connection and verify the whole stack loads, faster and more efficient than trying to do a full page rendering for your uptime checks.

PHP Versions
------------
This is theoretically compatible with PHP 5.4 and higher. However, upgrading to phpunit 8 required that I make tests require PHP 7.2+. So its probably still fine to run this on something less than 7.2, but since I can't get working tests on that version, I don't recommend it.

Security
--------
Make sure you limit access to the enpoints! You should not allow any yahoo off the internet to scan your site and look for these endpoints. I highly recommend some sort of `.htaccess` or nginx configuration settings to deny access to all but the local addresses you use for the API clients.

Something like this maybe:

Apache:

```
<Location /srv/wordpress/wp-content/plugins/multisite-json-api/endpoints>
DenyFrom All
AllowFrom 127.0.0.0/24 10.0.0.0/8
</Location>
```

Nginx:

```
location /wp-content/plugins/multisite-json-api/endpoints {
	deny all;
	allow 127.0.0.0/24 10.0.0.0/8;
}
```

Also, as of right now all user names and password are passed through http Headers. That means SSL is pretty much mandatory.

API Documentation
=================

Authentication
--------------
All of the enpoints require you to authenticate with an existing wordpress user. Currently all require the superadmin role, but that may change.

Username and password are passed with the HTTP Headers `User` and `Password` respectively. These are plain text so you need to be using SSL (which you are doing already right?).

Create Site
-----------
- **URL:** /wp-content/plugins/multisite-json-api/endpoints/create-site.php
- **Method:** POST
- **Payload example:** `{"email": "user@example.com", "site_name": "awesomeblog", "title": "Awesome Blog"}` 
- **Description:** Creates a site. If the email address does not exist this will create a new user with that email address. The `site_name` is the path or subdomain you would like to use.

List Sites
----------
- **URL:** /wp-content/plugins/multisite-json-api/endpoints/list-sites.php
- **Method:** GET
- **Payload example:** No payload, only GET variables
- **GET Variables:** public, spam, archived, deleted
- **Description:** Lists sites by wordpress tags. All of the variables are boolean 0 or 1, and will list sites where that variable is set to the boolean provided. For example: `?public=1&deleted=0` will list all sites that are public but not deleted.

Delete Site
-----------
- **URL:** /wp-content/plugins/multisite-json-api/endpoints/delete-site.php
- **Method:** DELETE
- **Payload example:** `{"blog_id": 49, "drop": false}`
- **Description:** Deletes a site. If `drop` is set to `true` wordpress will remove the site from the database completely. Otherwise the only thing this does is set the `deleted` attribute on the site to `true`.

Acknowledgements
----------------
Used the great Wordpress boiler plate template to get this thing off the ground.

License
-------
Same as WordPress GPLv2.
