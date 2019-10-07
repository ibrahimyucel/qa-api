<?php
	require_once '../qa-include/qa-base.php';
	require_once '../qa-include/qa-db-users.php';
	require_once '../qa-include/qa-db-selects.php';
	require_once '../qa-include/qa-app-users.php';

	$categoryslugs = qa_request_parts(1);
	$countslugs = count($categoryslugs);
	$userid = qa_get_logged_in_userid();

	list($questions, $categories, $categoryid) = qa_db_select_with_pending(
		qa_db_recent_a_qs_selectspec($userid, 0, $categoryslugs),
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
			'uservote' 			=> $question['uservote'],
			'userflag' 			=> $question['userflag'],
			'userfavoriteq' 	=> $question['userfavoriteq'],
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