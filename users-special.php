<?php
	require_once '../qa-include/qa-base.php';
	require_once '../qa-include/qa-db-users.php';
	require_once '../qa-include/qa-db-selects.php';
	require_once '../qa-include/qa-app-format.php';
	require_once '../qa-include/qa-app-users.php';

	$users = qa_db_select_with_pending(qa_db_users_from_level_selectspec(QA_USER_LEVEL_EXPERT));

	$total = count($users);
	$data = array();

	foreach( $users as $user ){
		array_push($data, array(
			'userid' 		=> $user['userid'],
			'handle' 		=> $user['handle'],
			'level' 		=> $user['level'],
			'_order_' 		=> $user['_order_'],
			)
		);	
	}
	
	$output = json_encode(array('total' => $total, 'data' => $data));
	
	echo $output;