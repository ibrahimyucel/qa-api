<?php
	require_once '../qa-include/qa-base.php';
	require_once '../qa-include/qa-db-users.php';
	require_once '../qa-include/qa-db-selects.php';

	require_once '../qa-include/qa-app-format.php';
	require_once '../qa-include/qa-app-users.php';
	
	$handle = qa_get('handle');
	$userid = qa_handle_to_userid($handle);
	$loginuserid = qa_get_logged_in_userid();
	$identifier = QA_FINAL_EXTERNAL_USERS ? $userid : $handle;

	list($useraccount, $userprofile, $userfields, $usermessages, $userpoints, $userlevels, $navcategories, $userrank) =
		qa_db_select_with_pending(
			QA_FINAL_EXTERNAL_USERS ? null : qa_db_user_account_selectspec($handle, false),
			QA_FINAL_EXTERNAL_USERS ? null : qa_db_user_profile_selectspec($handle, false),
			QA_FINAL_EXTERNAL_USERS ? null : qa_db_userfields_selectspec(),
			QA_FINAL_EXTERNAL_USERS ? null : qa_db_recent_messages_selectspec(null, null, $handle, false, qa_opt_if_loaded('page_size_wall')),
			qa_db_user_points_selectspec($identifier),
			qa_db_user_levels_selectspec($identifier, QA_FINAL_EXTERNAL_USERS, true),
			qa_db_category_nav_selectspec(null, true),
			qa_db_user_rank_selectspec($identifier)
		);

	if (!QA_FINAL_EXTERNAL_USERS && $handle !== qa_get_logged_in_handle()) {
		foreach ($userfields as $index => $userfield) {
			if (isset($userfield['permit']) && qa_permit_value_error($userfield['permit'], $loginuserid, qa_get_logged_in_level(), qa_get_logged_in_flags()))
				unset($userfields[$index]); // don't pay attention to user fields we're not allowed to view
		}
	}
	
	$data = array();

	
	array_push($data, array(
		'userid' 				=> $useraccount['userid'],
		'passsalt' 				=> $useraccount['passsalt'],
		'passcheck' 			=> $useraccount['passcheck'],
		'passhash' 				=> $useraccount['passhash'],
		'email' 				=> $useraccount['email'],
		'level' 				=> $useraccount['level'],
		'emailcode' 			=> $useraccount['emailcode'],
		'handle' 				=> $useraccount['handle'],
		'created' 				=> $useraccount['created'],
		'sessioncode' 			=> $useraccount['sessioncode'],
		'sessionsource' 		=> $useraccount['sessionsource'],
		'flags' 				=> $useraccount['flags'],
		'loggedin' 				=> $useraccount['loggedin'],
		'loginip' 				=> $useraccount['loginip'],
		'written' 				=> $useraccount['written'],
		'writeip' 				=> $useraccount['writeip'],
		'avatarblobid' 			=> $useraccount['avatarblobid'],
		'avatarwidth' 			=> $useraccount['avatarwidth'],
		'avatarheight' 			=> $useraccount['avatarheight'],
		'points' 				=> $useraccount['points'],
		'wallposts' 			=> $useraccount['wallposts'],
		)
	);	
	
	
	$output = json_encode(array('data' => $data));
	
	echo $output;