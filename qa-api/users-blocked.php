<?php
	require_once '../qa-include/qa-base.php';
	require_once '../qa-include/qa-db-users.php';
	require_once '../qa-include/qa-db-selects.php';
	require_once '../qa-include/qa-app-format.php';
	require_once '../qa-include/qa-app-users.php';

	$start = min(max(0, (int)qa_get('start')), QA_MAX_LIMIT_START);
	
	$usercount = qa_opt('cache_userpointscount');
	$pagesize = qa_opt('page_size_users');
	
	$userSpecCount = qa_db_selectspec_count(qa_db_users_with_flag_selectspec(QA_USER_FLAGS_USER_BLOCKED));
	$userSpec = qa_db_users_with_flag_selectspec(QA_USER_FLAGS_USER_BLOCKED, $start, $pagesize);

	list($numUsers, $users) = qa_db_select_with_pending($userSpecCount, $userSpec);
	$count = $numUsers['count'];

	$usershtml = qa_userids_handles_html($users);

	$categoryslugs = qa_request_parts(1);
	$countslugs = count($categoryslugs);

	$userid = qa_get_logged_in_userid();
	
	$total = count($users);
	$data = array();
	
	if (QA_FINAL_EXTERNAL_USERS) {
		die('User accounts are handled by external code');
	} else {
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
				'_order_' 		=> $user['_order_'],
				)
			);	
		}
		$output = json_encode(array('total' => $total, 'data' => $data));
	}
	
	echo $output;