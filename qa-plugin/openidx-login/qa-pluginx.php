<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/facebook-login/qa-plugin.php
	Description: Initiates OpenID Login plugin


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

/*
	Plugin Name: OpenID Login
	Plugin URI:
	Plugin Description: Allows users to log in via Facebook
	Plugin Version: 0.1.4
	Plugin Date: 2019-01-10
	Plugin Author: Jack Siro
	Plugin Author URI: http://github.com/jacksiro
	Plugin License: GPLv2
	Plugin Minimum Question2Answer Version: 1.5
	Plugin Minimum PHP Version: 5
	Plugin Update Check URI:
*/


if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


// login modules don't work with external user integration
if (!QA_FINAL_EXTERNAL_USERS) {
	qa_register_plugin_module('login', 'qa-openid-login.php', 'qa_openid_login', 'OpenID Login');
	qa_register_plugin_module('page', 'qa-openid-login-page.php', 'qa_openid_login_page', 'OpenID Login Page');
	qa_register_plugin_layer('qa-openid-layer.php', 'OpenID Login Layer');
}
