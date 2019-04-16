<?php
/*
	Plugin Name: HTML Question
	Plugin Description: Enables questions to be posted quickly by uploading HTML files
	Plugin Version: 1.0
	Plugin Date: 2019-10-04
	Plugin Author: Jack Siro
	Plugin Author URI: https://www.github.com/jacksiro
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.6

*/

if ( !defined('QA_VERSION') )
{
	header('Location: ../../');
	exit;
	
}

	$plugin_dir = dirname( __FILE__ ) . '/';
	$plugin_url = qa_path_to_root().'qa-plugin/html-question';
	define( "QA_HTMLQUIZ_DIR",  $plugin_url.'/'  );
	
	qa_register_plugin_phrases('hq-lang-*.php', 'hq_lang');
	qa_register_plugin_layer('hq-adapter.php', 'HTML Question Layer');
	//qa_register_plugin_module('page', 'html-question.php', 'html_question', 'HTML Question');
	//qa_register_plugin_module('page', 'pm-feedback.php', 'pm_feedback', 'Feedback Adapter');
