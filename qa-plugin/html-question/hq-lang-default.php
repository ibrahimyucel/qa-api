<?php

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

	return array(
		'hq_askbulk' => 'Ask Bulk Questions',
		'hq_title_limit' => 'Limit of title content',
		'hq_title_limit_suffix' => 'words',
		'hq_html_delimeter' => 'String delimeter for your html file:',
		'hq_options_saved' => 'HTML Questions options saved',
		'hq_reset_button' => 'Reset Changes',
		'hq_save_button' => 'Save Changes',
		'hq_submit_questions' => 'Submit Questions',
		'hq_select_files' => 'Select multiple files',
		'hq_upload_label' => 'Or simply ask your question via a HTML File',
	);

/*
	Omit PHP closing tag to help avoid accidental output
*/
