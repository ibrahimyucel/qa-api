<?php
	require_once '../qa-include/qa-base.php';
	require_once '../qa-include/qa-db-users.php';
	require_once '../qa-include/qa-db-selects.php';
	require_once '../qa-include/qa-app-format.php';
	require_once '../qa-include/qa-app-users.php';

	$start = min(max(0, (int)qa_get('start')), QA_MAX_LIMIT_START);
	$users = qa_db_select_with_pending(qa_db_top_users_selectspec($start, qa_opt_if_loaded('page_size_users')));

	$usercount = qa_opt('cache_userpointscount');
	$pagesize = qa_opt('page_size_users');
	$users = array_slice($users, 0, $pagesize);
	$usershtml = qa_userids_handles_html($users);

	$total = count($users);
	$data = array();

	foreach( $users as $user ){
		array_push($data, array(
			'userid' 		=> $user['userid'],
			'handle' 		=> $user['handle'],
			'points' 		=> $user['points'],
			'flags' 		=> $user['flags'],
			'email' 		=> $user['email'],
			'avatarblobid' 	=> $user['avatarblobid'],
			'avatarwidth' 	=> $user['avatarwidth'],
			'avatarheight' 	=> $user['avatarheight'],
			'_order_' 		=> $user['_order_'])
		);	
	}
	
	$output = json_encode(array('total' => $total, 'data' => $data));
	
	echo $output;