<?php

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

	return array(
		'htmlquiz_upload_label' => 'Upload your question as a HTML File',
	);

/*
	Omit PHP closing tag to help avoid accidental output
*/
