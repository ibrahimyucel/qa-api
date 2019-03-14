<?php
	require_once '../qa-include/qa-base.php';
	require_once '../qa-include/qa-db-users.php';
	require_once '../qa-include/qa-db-selects.php';
	require_once '../qa-include/qa-app-format.php';
	require_once '../qa-include/qa-app-users.php';

	$handle = isset( $_GET['handle'] ) ? $_GET['handle'] : "";
	$handle = isset( $_GET['handle'] ) ? $_GET['handle'] : "";
	
	/*if (isset($handle)) {
		$userid = qa_handle_to_userid($handle);
	} else {
		die('handle is required.');
	}*/
	$userid = qa_handle_to_userid($handle);
	//echo $output;