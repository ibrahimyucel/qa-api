<?php
/*
	Plugin Name: HTML Question Poster
	Plugin URI: 
	Plugin Description: Post a question with html file
	Plugin Version: 1.0
	Plugin Date: 2019-02-04
	Plugin Author: Jack Siro
	Plugin Author URI: http://github.com/jacksiro
	Plugin License: GPLv2
	Plugin Minimum Question2Answer Version: 1.6
	Plugin Update Check URI: 
*/
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}
	qa_register_plugin_phrases('qa-htmlquiz-lang-*.php', 'htmlquiz');
	qa_register_plugin_module('module', 'qa-htmlquiz.php', 'qa_htmlquiz', 'HTML Question');
	qa_register_plugin_module('event', 'qa-htmlquiz-event.php', 'qa_htmlquiz_event', 'HTML Question');
	qa_register_plugin_layer('qa-htmlquiz-layer.php', 'HTML Question');
	qa_register_plugin_module('filter', 'qa-htmlquiz-filter.php', 'qa_htmlquiz_filter', 'HTML Question');
/*
	Omit PHP closing tag to help avoid accidental output
*/