<?php
	require_once '../qa-include/qa-base.php';
	require_once '../qa-include/qa-db-users.php';
	require_once '../qa-include/qa-db-selects.php';
	require_once '../qa-include/qa-app-format.php';
	require_once '../qa-include/qa-app-users.php';
	require_once '../qa-include/qa-app-cookies.php';

	$sort = qa_get('sort');
	$start = min(max(0, (int)qa_get('start')), QA_MAX_LIMIT_START);
	$userid = qa_get_logged_in_userid();
	$cookieid = qa_cookie_get();

	switch ($sort) {
		case 'hot':
			$selectsort = 'hotness';
			break;

		case 'votes':
			$selectsort = 'netvotes';
			break;

		case 'answers':
			$selectsort = 'acount';
			break;

		case 'views':
			$selectsort = 'views';
			break;

		default:
			$selectsort = 'created';
			break;
	}

	$questions = qa_db_select_with_pending( qa_db_qs_selectspec($userid, $selectsort, $start, null, null, false, false, qa_opt_if_loaded('page_size_qs')));
	
	$total = count($questions);
	
	$data = array();
	$usershtml = qa_userids_handles_html($questions, true);
	foreach( $questions as $question ){
		$questionid = $question['postid'];
		$htmloptions = qa_post_html_options($question, null, true);
		$htmloptions['answersview'] = false; // answer count is displayed separately so don't show it here
		$htmloptions['avatarsize'] = qa_opt('avatar_q_page_q_size');
		$htmloptions['q_request'] = qa_q_request($questionid, $question['title']);
		
		$qa_content = qa_post_html_fields($question, $userid, $cookieid, $usershtml, null, $htmloptions);
		
		$when = '<b>'.@$qa_content['when']['data'].' '.@$qa_content['when']['suffix'].'</b>';
		$where = @$qa_content['where']['prefix'].' <b>'.@$qa_content['where']['data'].'</b>';
		$who = @$qa_content['who']['prefix'].' <b>'.@$qa_content['who']['data'].'</b> ('. @$qa_content['who']['points']['data'].' '. 
			$qa_content['who']['points']['suffix'].')';
			
		array_push($data, array(
			'postid' 			=> $questionid,
			'basetype' 			=> $question['basetype'],
			'title' 			=> $question['title'],
			'tags' 				=> $question['tags'],
			'created' 			=> $question['created'],
			'categoryid' 		=> $question['categoryid'],
			'meta_order' 		=> $qa_content['meta_order'],
			'what' 				=> $qa_content['what'],
			'when' 				=> trim($when),
			'where' 			=> trim($where),
			'who' 				=> trim($who),
			'netvotes' 			=> $question['netvotes'],
			'views' 			=> $question['views'],
			'hotness' 			=> $question['hotness'],
			'acount' 			=> $question['acount'],
			'userid' 			=> $question['userid'],
			'level' 			=> $question['level'],
			'avatar' 			=> $qa_content['avatar'],
			'vote_state' 		=> $qa_content['vote_state'])
		);
	}
	$output = json_encode(array('total' => $total, 'data' => $data));
	
	echo $output;