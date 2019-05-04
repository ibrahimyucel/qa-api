<?php
/*
	Private Message Adapter by Jackson Siro
	https://www.github.com/jacksiro/Q2A-PM-Adapter-Plugin

	Description: Adds an editor of your choice on the private message and feedback pages, including support for HTML messages.

*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}
	
	function pm_feedbacks_count($filters = null, $sql = '')
	{
		if (isset($filters)) {
			foreach ($filters as $filter) $sql .= $filter . ' AND ';
			$sql .= 'parentid=0';
		} else {
			$sql = 'parentid=0';
		}
		return qa_db_read_one_value(qa_db_query_sub("SELECT count(*) FROM ^feedbacks WHERE " . $sql));
	}
	
	function pm_load_feedbacks($limit_users, $load_filter = '')
	{
		if ( qa_opt('su_users_filter')=='active' ) {
			$load_filter = ' WHERE parentid = 0';
		} elseif ( qa_opt('pm_feedback_filter')=='inactive' ) {
			$load_filter = ' WHERE parentid = 0';
		}
		
		return qa_db_read_all_assoc(qa_db_query_sub('SELECT ^feedbacks.feedbackid, ^feedbacks.name, ^feedbacks.email, ^feedbacks.userid, ^feedbacks.parentid, ^feedbacks.format, ^feedbacks.title, ^feedbacks.topic, ^feedbacks.content, UNIX_TIMESTAMP(^feedbacks.created) AS created, u.handle, u.email, u.level, u.flags FROM ^feedbacks LEFT JOIN (SELECT userid, handle, email, level, flags FROM ^users) AS u ON u.userid=^feedbacks.userid' . $load_filter . $limit_users ));
	}
	
	function pm_page_form($page_users, $page_all, $page_on, $page_list = '')
	{
		for ($i=1; $i < $page_all+1; $i++) 
		{ 
			$page_list .= "<option value='" . $i . "' " . ($page_on == $i ? "selected" : "") . ">"
				. $i . "</option>";
		}
		return  '<div style="display: inline-block; width: 99%; padding: 0px 1% 10px 0px;">'.'<form method="get">'.
			'<b>' . $page_users . '</b> '.qa_lang('pm_lang/feedbacks_in_page').
			'<select name="page" id="page" onchange="this.form.submit()">' . $page_list . '</select>'.
			'</br><small>'.qa_lang('pm_lang/you_can_set_up').'</small>'.
			'<hr style="color: #000;border-top: 1px solid;height: 0;"></div></form>';
	}
	
	function pm_page_format($html = '')
	{
		return '<form name="admin_form" action="'.qa_self_html().'" method="post">
					<div style="display: inline-block; width: 98%; padding: 1%;">
						<div class="" style="float:left;">
							<select id="um-action" class="qa-form-wide-select input-sm" name="um-action" style="float: left;">
								<option selected="" value="none">'.qa_lang('pm_lang/select_action').'</option>
								<option value="actiondelete">'.qa_lang('pm_lang/action_delete').'</option>
							</select>
							<button id="do_action" class="qa-form-tall-button qa-form-tall-button-save btn btn-default" type="submit" name="do_action">'.qa_lang('pm_lang/apply_action').'</button>
							
						</div>
						<div style="float:right;">
							<a id="checkall" href="#">'.qa_lang('pm_lang/select').'</a> / 
							<a id="checknone" href="#">'.qa_lang('pm_lang/unselect').'</a>
						</div>
					</div>

					<table id="umtable" class="display" width="100%" cellspacing="0">
						<thead>
							<tr>
								<th></th>
								<th>'.qa_lang('pm_lang/name').'</th>
								<th>'.qa_lang('pm_lang/email').'</th>
								<th>'.qa_lang('pm_lang/topic').'</th>
								<th>'.qa_lang('pm_lang/summary').'</th>
								<th>'.qa_lang('pm_lang/sent').'</th>

							</tr>
						</thead>
						<tfoot>
							<tr>
								<th></th>
								<th>'.qa_lang('pm_lang/name').'</th>
								<th>'.qa_lang('pm_lang/topic').'</th>
								<th>'.qa_lang('pm_lang/email').'</th>
								<th>'.qa_lang('pm_lang/summary').'</th>
								<th>'.qa_lang('pm_lang/sent').'</th>

							</tr>
						</tfoot>
						<tbody>';
	}

	function pm_feedback_remover($fcount, $feedbackids)
	{
		$messages = array();
		qa_db_query_sub('DELETE FROM ^feedbacks WHERE feedbackid IN (#)', $feedbackids);
		
		//$messages[] = $n . ' users were removed.';
		$messages[] = qa_lang_html_sub_split('pm_lang/x_feedbacks_removed', $fcount);
		return $messages;
	}
	
/*
	Omit PHP closing tag to help avoid accidental output
*/