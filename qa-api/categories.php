<?php
	require_once '../qa-include/qa-base.php';
	require_once '../qa-include/qa-db-users.php';
	require_once '../qa-include/qa-db-selects.php';
	require_once '../qa-include/qa-app-users.php';

	$categoryslugs = qa_request_parts(1);
	$countslugs = count($categoryslugs);

	$userid = qa_get_logged_in_userid();

	list($categories, $categoryid, $favoritecats) = qa_db_select_with_pending(
		qa_db_category_nav_selectspec($categoryslugs, false, false, true),
		$countslugs ? qa_db_slugs_to_category_id_selectspec($categoryslugs) : null,
		isset($userid) ? qa_db_user_favorite_categories_selectspec($userid) : null
	);
	
	$total = count($categories);
	$data = array();

	foreach( $categories as $category ){
		array_push($data, array(
			'categoryid' 	=> $category['categoryid'],
			'parentid' 		=> $category['parentid'],
			'title' 		=> $category['title'],
			'tags' 			=> $category['tags'],
			'qcount' 		=> $category['qcount'],
			'position' 		=> $category['position'],
			'childcount' 	=> $category['childcount'],
			'content' 		=> $category['content'],
			'backpath' 		=> $category['backpath'],
			'itemorder' 	=> $category['_order_'])
		);	
	}
	
	$output = json_encode(array('total' => $total, 'data' => $data));
	
	echo $output;