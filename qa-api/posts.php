<?php
	require_once '../qa-include/qa-base.php';
	require_once '../qa-include/qa-db-users.php';
	require_once '../qa-include/qa-db-selects.php';
	require_once '../qa-include/qa-app-users.php';

	$categoryslugs = qa_request_parts(1);
	$countslugs = count($categoryslugs);

	$sort = ($countslugs && !QA_ALLOW_UNINDEXED_QUERIES) ? null : qa_get('sort');
	$start = min(max(0, (int)qa_get('start')), QA_MAX_LIMIT_START);
	$userid = qa_get_logged_in_userid();

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

	list($questions, $categories, $categoryid) = qa_db_select_with_pending(
		qa_db_qs_selectspec($userid, $selectsort, $start, $categoryslugs, null, false, false, qa_opt_if_loaded('page_size_qs')),
		qa_db_category_nav_selectspec($categoryslugs, false, false, true),
		$countslugs ? qa_db_slugs_to_category_id_selectspec($categoryslugs) : null
	);
	
	$total = count($questions);
	$data = array();
	foreach( $questions as $question ){
		array_push($data, array(
			'postid' 			=> $question['postid'],
			'categoryid' 		=> $question['categoryid'],
			'type' 				=> $question['type'],
			'basetype' 			=> $question['basetype'],
			'hidden' 			=> $question['hidden'],
			'queued' 			=> $question['queued'],
			'acount' 			=> $question['acount'],
			'selchildid' 		=> $question['selchildid'],
			'closedbyid' 		=> $question['closedbyid'],
			'upvotes' 			=> $question['upvotes'],
			'downvotes' 		=> $question['downvotes'],
			'netvotes' 			=> $question['netvotes'],
			'views' 			=> $question['views'],
			'hotness' 			=> $question['hotness'],
			'flagcount' 		=> $question['flagcount'],
			'title' 			=> $question['title'],
			'tags' 				=> $question['tags'],
			'created' 			=> $question['created'],
			'name' 				=> $question['name'],
			'categoryname' 		=> $question['categoryname'],
			'categorybackpath' 	=> $question['categorybackpath'],
			'categoryids' 		=> $question['categoryids'],
			'uservote' 			=> @$question['uservote'],
			'userflag' 			=> @$question['userflag'],
			'userfavoriteq' 	=> @$question['userfavoriteq'],
			'userid' 			=> $question['userid'],
			'cookieid' 			=> $question['cookieid'],
			'createip' 			=> $question['createip'],
			'points' 			=> $question['points'],
			'flags' 			=> $question['flags'],
			'level' 			=> $question['level'],
			'email' 			=> $question['email'],
			'handle' 			=> $question['handle'],
			'avatarblobid' 		=> $question['avatarblobid'],
			'avatarwidth' 		=> $question['avatarwidth'],
			'avatarheight' 		=> $question['avatarheight'],
			'itemorder' 		=> $question['_order_'])
		);	
	}
	$output = json_encode(array('total' => $total, 'data' => $data));
	
	echo $output;