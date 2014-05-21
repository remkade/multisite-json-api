=== Multisite JSON API ===
Contributors: remkade
Tags: json, api, multisite
Requires at least: 3.5.1
Tested up to: 3.9.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This provides several endpoints for creating, listing, and deleting sites with JSON formatted data.

== Description ==
This is a Wordpress Plugin that adds JSON endpoints for creating, listing, and deleting sites on multisite.

This plugin aims to be simple to make Wordpress polyglot environments not only possible, but practical.

== Installation ==

You can install this using all the usual methods. The only thing different is that this plugin **must** but network activated.

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'multisite json api'
3. Click 'Install Now'
4. Network Activate the plugin on the Plugin dashboard

= Uploading in WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `multisite-json-api.zip` from your computer
4. Click 'Install Now'
5. Network Activate the plugin in the Plugin dashboard

= Using FTP =

1. Download `multisite-json-api.zip`
2. Extract the `multisite-json-api` directory to your computer
3. Upload the `multisite-json-api` directory to the `/wp-content/plugins/` directory
4. Network Activate the plugin in the Plugin dashboard


== Frequently Asked Questions ==

= Where is the documentation for the API? =

Take a look at the [github page](http://github.com/remkade/multisite-json-api/) for the full documentation.

== Screenshots ==

None

== Changelog ==

= 0.5.0 =
* Almost feature complete

= 0.0.1 =
* Initial creation

== Upgrade Notice ==

= 0.5.0 =
List and Create endpoints work.

== API Documentation ==

= Security =

Make sure you limit access to the enpoints! You should not allow any yahoo off the internet to scan your site and look for these endpoints. I highly recommend some sort of `.htaccess` or nginx configuration settings to deny access to all but the local addresses you use for the API clients. More info on the [github page](http://github.com/remkade/multisite-json-api/).

= Authentication =

All of the endpoints require you to authenticate with an existing wordpress user. Currently all require the superadmin role, but that may change.

Username and password are passed with the HTTP Headers `Username` and `Password` respectively. These are plain text so you need to be using SSL (which you are doing already right?).

= Create Site =
- **URL:** /wp-content/multisite-json-api/endpoints/create-site.php
- **Method:** POST
- **Works with subdomains?:** yes
- **Works with subdirectories?** yes
- **Payload example:** `{"email": "user@example.com", "sitename": "awesomeblog", "title": "Awesome Blog"}` 
- **Description:** Creates a site. If the email address does not exist this will create a new user with that email address. The `sitename` is the the path or subdomain you would like to use.

= List Sites =
- **URL:** /wp-content/multisite-json-api/endpoints/list-sites.php
- **Method:** GET
- **Works with subdomains?:** yes
- **Works with subdirectories?** yes
- **Payload example:** No payload, only GET variables
- **GET Variables:** public, spam, archived, deleted
- **Description:** Lists sites by wordpress tags. All of the variables are boolean 0 or 1, and will list sites where that variable is set to the boolean provided. For example: `?public=1&deleted=0` will list all sites that are public but not deleted.

== Updates ==

The basic structure of this plugin was cloned from the [WordPress-Plugin-Boilerplate](https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate) project.
This plugin supports the [GitHub Updater](https://github.com/afragen/github-updater) plugin, so if you install that, this plugin becomes automatically updateable direct from GitHub. Any submission to WP.org repo will make this redundant.
