<?php
	require_once '../qa-include/qa-base.php';
	require_once '../qa-include/qa-db-users.php';
	require_once '../qa-include/qa-db-selects.php';
	require_once '../qa-include/qa-app-format.php';
	require_once '../qa-include/qa-app-users.php';
	require_once '../qa-include/qa-app-cookies.php';

	$categoryslugs = qa_request_parts(1);
	$countslugs = count($categoryslugs);

	$sort = ($countslugs && !QA_ALLOW_UNINDEXED_QUERIES) ? null : qa_get('sort');
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

	list($questions, $categories, $categoryid) = qa_db_select_with_pending(
		qa_db_qs_selectspec($userid, $selectsort, $start, $categoryslugs, null, false, false, qa_opt_if_loaded('page_size_qs')),
		qa_db_category_nav_selectspec($categoryslugs, false, false, true),
		$countslugs ? qa_db_slugs_to_category_id_selectspec($categoryslugs) : null
	);
	
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
		//Array ( [raw] => Array ( [postid] => 8 [categoryid] => 1 [type] => Q [basetype] => Q [hidden] => 0 [queued] => 0 [acount] => 0 [selchildid] => [closedbyid] => [upvotes] => 0 [downvotes] => 0 [netvotes] => 0 [views] => 1 [hotness] => 58006000000 [flagcount] => 0 [title] => George Boinnet quits amid mixed reactions from Kenyans [tags] => [created] => 1552320997 [name] => [categoryname] => General [categorybackpath] => general [categoryids] => 1,1 [userid] => 1 [cookieid] => [createip] =>  [points] => 310 [flags] => 0 [level] => 120 [email] => jacksiro@gmail.com [handle] => jacksiro [avatarblobid] => [avatarwidth] => [avatarheight] => [_order_] => 6 ) [hidden] => 0 [queued] => 0 [tags] => id="q8" [classes] => [url] => 8/george-boinnet-quits-amid-mixed-reactions-from-kenyans [title] => George Boinnet quits amid mixed reactions from Kenyans [q_tags] => Array ( ) [where] => Array ( [prefix] => in [data] => General [suffix] => ) [upvotes_raw] => 0 [downvotes_raw] => 0 [netvotes_raw] => 0 [vote_view] => net [vote_on_page] => enabled [upvotes_view] => Array ( [prefix] => [data] => 0 [suffix] => like ) [downvotes_view] => Array ( [prefix] => [data] => 0 [suffix] => dislike ) [netvotes_view] => Array ( [prefix] => [data] => 0 [suffix] => votes ) [vote_tags] => id="voting_8" [vote_up_tags] => title="Click to vote up" name="vote_8_1_q8" onclick="return qa_vote_click(this);" [vote_state] => enabled [vote_down_tags] => title="Click to vote down" name="vote_8_-1_q8" onclick="return qa_vote_click(this);" [meta_order] => ^what^when^where^who [what] => asked [what_url] => 8/george-boinnet-quits-amid-mixed-reactions-from-kenyans [what_url_tags] => itemprop="url" [when] => Array ( [data] => Mar 11 ) [who] => Array ( [prefix] => by [data] => jacksiro [suffix] => [points] => Array ( [prefix] => [data] => 310 [suffix] => points ) [title] => [level] => Super Administrator ) [avatar] => )
		
		//$who = $qa_content['who'];
		
		$when = '<b>'.@$qa_content['when']['data'].' '.@$qa_content['when']['suffix'].'</b>';
		$where = @$qa_content['where']['prefix'].' <b>'.@$qa_content['where']['data'].'</b>';
		if (array_key_exists('points', @$qa_content['who']))
		$points = ' ('. @$qa_content['who']['points']['data'].' '. $qa_content['who']['points']['suffix'].')';
		else $points = '';
		$who = @$qa_content['who']['prefix'].' <b>'.@$qa_content['who']['data'].'</b>'. $points;

		array_push($data, array(
			'postid' 			=> $questionid,			
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
			'categoryid' 		=> $question['categoryid'],
			'name' 				=> $question['name'],
			'categoryname' 		=> $question['categoryname'],
			'categorybackpath' 	=> $question['categorybackpath'],
			'categoryids' 		=> $question['categoryids'],
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
			'avatar' 			=> $qa_content['avatar'],
			'vote_state' 		=> $qa_content['vote_state'],
			'meta_order' 		=> $qa_content['meta_order'],
			'meta_what' 		=> $qa_content['what'],
			'meta_when'			=> strip_tags($when),
			'meta_where' 		=> strip_tags($where),
			'meta_who' 			=> strip_tags($who))
		);
	}
	$output = json_encode(array('total' => $total, 'data' => $data));
	
	echo $output;