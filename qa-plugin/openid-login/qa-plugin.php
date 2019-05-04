<?php

/*
	Plugin Name: OpenID Login
	Plugin URI:http://github.com/jacksiro/q2a-openid-login
	Plugin Description: Allows users to log in via Facebook, Google and Linkedin
	Plugin Version: 0.1.3
	Plugin Date: 2019-01-01
	Plugin Author: Jack Siro
	Plugin Author URI: http://github.com/jacksiro
	Plugin License: GPLv2
	Plugin Minimum Question2Answer Version: 1.6.3
	Plugin Minimum PHP Version: 5
	Plugin Update Check URI: 
*/


if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


// login modules don't work with external user integration
if (!QA_FINAL_EXTERNAL_USERS) {	
	qa_register_plugin_phrases('langs/qa-open-lang-*.php', 'openid');
	qa_register_plugin_module('login', 'qa-openid-login.php', 'qa_openid_login', 'Openid Login');
	qa_register_plugin_module('page', 'qa-openid-login-page.php', 'qa_openid_login_page', 'Openid Login Page');
	qa_register_plugin_layer('qa-openid-layer.php', 'Openid Login Layer');
}
