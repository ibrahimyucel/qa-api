<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}
require_once QA_INCLUDE_DIR.'qa-db-metas.php';
require_once QA_PLUGIN_DIR.'html-question/qa-htmlquiz.php';

class qa_htmlquiz_event {

	function process_event ($event, $userid, $handle, $cookieid, $params) {
		global $qa_extra_question_fields;
		switch ($event) {
		case 'q_queue':
		case 'q_post':
		case 'q_edit':
			for($key=1; $key<=qa_htmlquiz::FIELD_COUNT_MAX; $key++) {
				if((bool)qa_opt(qa_htmlquiz::FIELD_ACTIVE.$key)) {
					$name = qa_htmlquiz::FIELD_BASE_NAME.$key;
					if(isset($qa_extra_question_fields[$name]))
						$content = qa_sanitize_html($qa_extra_question_fields[$name]['value']);
					else
						$content = qa_db_single_select(qa_db_post_meta_selectspec($params['postid'], 'qa_q_'.$name));
					if(is_null($content))
						$content = '';
					qa_db_postmeta_set($params['postid'], 'qa_q_'.$name, $content);
				}
			}
			break;
		case 'q_delete':
			for($key=1; $key<=qa_htmlquiz::FIELD_COUNT_MAX; $key++) {
				if((bool)qa_opt(qa_htmlquiz::FIELD_ACTIVE.$key)) {
					$name = qa_htmlquiz::FIELD_BASE_NAME.$key;
					qa_db_postmeta_clear($params['postid'], 'qa_q_'.$name);
				}
			}
			break;
		}
	}
}
/*
	Omit PHP closing tag to help avoid accidental output
*/