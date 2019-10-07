<?php
	require_once '../qa-include/qa-base.php';
	require_once '../qa-include/qa-db-users.php';
	require_once '../qa-include/qa-db-selects.php';
	require_once '../qa-include/qa-app-format.php';
	require_once '../qa-include/qa-app-users.php';
	require_once '../qa-include/qa-app-cookies.php';
	require_once '../qa-include/qa-page-question-view.php';

	$categoryslugs = qa_request_parts(1);
	$countslugs = count($categoryslugs);

	$questionid = qa_get('postid') ? qa_get('postid') : '';
	$userid = qa_get_logged_in_userid();
	$cookieid = qa_cookie_get();
	
	$success = 0;
	$message = '';
	$data = array();
	
	$cacheDriver = Q2A_Storage_CacheFactory::getCacheDriver();
	$cacheKey = "question:$questionid";
	$useCache = $userid === null && $cacheDriver->isEnabled() && !qa_is_http_post() && empty($pagestate);
	$saveCache = false;

	if ($useCache) {
		$questionData = $cacheDriver->get($cacheKey);
	}

	if (!isset($questionData)) {
		$questionData = qa_db_select_with_pending(
			qa_db_full_post_selectspec($userid, $questionid),
			qa_db_full_child_posts_selectspec($userid, $questionid),
			qa_db_full_a_child_posts_selectspec($userid, $questionid),
			qa_db_post_parent_q_selectspec($questionid),
			qa_db_post_close_post_selectspec($questionid),
			qa_db_post_duplicates_selectspec($questionid),
			qa_db_post_meta_selectspec($questionid, 'qa_q_extra'),
			qa_db_category_nav_selectspec($questionid, true, true, true),
			isset($userid) ? qa_db_is_favorite_selectspec($userid, QA_ENTITY_QUESTION, $questionid) : null
		);

		// whether to save the cache (actioned below, after basic checks)
		$saveCache = $useCache;
	}

	list($question, $childposts, $achildposts, $parentquestion, $closepost, $duplicateposts, $extravalue, $categories, $favorite) = $questionData;


	if (isset($question)) {		
		$question['extra'] = $extravalue;

		$answers = qa_page_q_load_as($question, $childposts);
		$commentsfollows = qa_page_q_load_c_follows($question, $childposts, $achildposts, $duplicateposts);

		$question = $question + qa_page_q_post_rules($question, null, null, $childposts + $duplicateposts); // array union

		if ($question['selchildid'] && (@$answers[$question['selchildid']]['type'] != 'A'))
			$question['selchildid'] = null; // if selected answer is hidden or somehow not there, consider it not selected

		foreach ($answers as $key => $answer) {
			$answers[$key] = $answer + qa_page_q_post_rules($answer, $question, $answers, $achildposts);
			$answers[$key]['isselected'] = ($answer['postid'] == $question['selchildid']);
		}

		foreach ($commentsfollows as $key => $commentfollow) {
			$parent = ($commentfollow['parentid'] == $questionid) ? $question : @$answers[$commentfollow['parentid']];
			$commentsfollows[$key] = $commentfollow + qa_page_q_post_rules($commentfollow, $parent, $commentsfollows, null);
		}
		
		$usershtml = qa_userids_handles_html(array_merge(array($question), $answers, $commentsfollows), true);

		foreach( $answers as $answer ){
			$answerid = $answer['postid'];
			$htmloptions = qa_post_html_options($answer, null, true);
			$htmloptions['avatarsize'] = qa_opt('avatar_q_page_q_size');
			
			$qa_content = qa_post_html_fields($answer, $userid, $cookieid, $usershtml, null, $htmloptions);
			
			$when = '<b>'.@$qa_content['when']['data'].' '.@$qa_content['when']['suffix'].'</b>';
			$who = @$qa_content['who']['prefix'].' <b>'.@$qa_content['who']['data'].'</b> ('. @$qa_content['who']['points']['data'].' '. 
				$qa_content['who']['points']['suffix'].')';
				
			array_push($data, array(
				'postid' 		=> $answerid,
				'basetype' 		=> $answer['basetype'],
				'created' 		=> $answer['created'],
				'what' 			=> $qa_content['what'],
				'when' 			=> trim($when),
				'who' 			=> trim($who),
				'content' 		=> $answer['content'],
				'netvotes' 		=> $answer['netvotes'],
				'userid' 		=> $answer['userid'],
				'level' 		=> $answer['level'],
				'avatar' 		=> $qa_content['avatar'],
				'vote_state' 	=> $qa_content['vote_state'])
			);
		}
		$success = 1;
	} else {
		$success = 0;
		$message = 'the question was either deleted or hidden.';
	}
	
	$output = json_encode(array('success' => $success, 'message' => $message, 'data' => $data));	
	
	echo $output;