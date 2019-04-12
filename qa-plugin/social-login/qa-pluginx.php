<?php

/*
	Question2Answer (c) Gideon Greenspan
	Social Login Plugin (c) Alex Lixandru

	http://www.question2answer.org/

	
	File: qa-plugin/social-login/qa-plugin.php
	Version: 3.0.0
	Description: Initiates Social Login plugin


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
	Plugin Name: Social Login
	Plugin URI: https://github.com/jacksiro/q2a-social-login
	Plugin Description: Allows users to log in via Facebook, Google , Twitter and other socia login providers
	Plugin Version: 3.0.0
	Plugin Date: 2019-01-01
	Plugin Author: Jack Siro
	Plugin Author URI: https://github.com/jacksiro
	Plugin License: GPLv2
	Plugin Minimum Question2Answer Version: 1.7.0
	Plugin Minimum PHP Version: 5
	Plugin Update Check URI: 
*/

/*
	Based on Facebook Login plugin
*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


if (!QA_FINAL_EXTERNAL_USERS) { // login modules don't work with external user integration

	qa_register_plugin_phrases('lang/qa-sl-lang-*.php', 'social_login');
	qa_register_plugin_overrides('qa-sl-overrides.php');
	qa_register_plugin_layer('qa-sl-layer.php', 'Social Login Layer');
	qa_register_plugin_module('page', 'qa-sl-page.php', 'qa_social_login_page', 'Social Login Configuration');
	qa_register_plugin_module('widget', 'qa-sl-widget.php', 'qa_social_login_widget', 'Social Login Providers');
	
	// sice we're not allowed to access the database at this step, take the information from a local file
	// note: the file providers.php will be automatically generated when the configuration of the plugin
	// is updated on the Administration page
	$providers = @include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'providers.php';
	if ($providers) {
		// loop through all active providers and register them
		$providerList = explode(',', $providers);
		foreach($providerList as $provider) {
			qa_register_plugin_module('login', 'qa-social-login.php', 'qa_social_login', $provider);
		}
	}
	
}

/*
	Omit PHP closing tag to help avoid accidental output
*/
