<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}
require_once QA_INCLUDE_DIR.'qa-theme-base.php';
require_once QA_INCLUDE_DIR.'qa-app-blobs.php';
require_once QA_PLUGIN_DIR.'html-question/qa-htmlquiz.php';

class qa_html_theme_layer extends qa_html_theme_base {

	private $extradata;
	private $pluginurl;
	
	function main() {
		if($this->template == 'ask') {
			if(isset($this->content['form']['fields']))
				$this->qa_add_field(null, $this->content['form']['fields'], $this->content['form']);
		} else if(isset($this->content['form_q_edit']['fields'])) {
				$this->qa_add_field($this->content['q_view']['raw']['postid'], $this->content['form_q_edit']['fields'], $this->content['form_q_edit']);
		}
		qa_html_theme_base::main();
	}
	
	function qa_add_field($postid, &$fields, &$form) {
		global $qa_extra_question_fields;
		
		$field = array(
			'label' => qa_lang_html('htmlquiz/htmlquiz_upload_label'),
			'type' => 'file',
			'tags' => 'name="htmlfile"',
			'value' => qa_html(@$in['htmlfile']),
			'error' => qa_html(@$errors['htmlfile']),
		);

		qa_array_insert($fields, 'tags', array('extra' => $field));
	}
	
	function q_view_content($q_view) {
		if(!isset($this->content['form_q_edit'])) {
			$this->qa_eqf_output($q_view, qa_htmlquiz::FIELD_PAGE_POS_UPPER);
			$this->qa_eqf_output($q_view, qa_htmlquiz::FIELD_PAGE_POS_INSIDE);
			$this->qa_eqf_clearhook($q_view);
		}
		qa_html_theme_base::q_view_content($q_view);
	}
	
	function q_view_extra($q_view) {
		qa_html_theme_base::q_view_extra($q_view);
		if(!isset($this->content['form_q_edit'])) {
			$this->qa_eqf_output($q_view, qa_htmlquiz::FIELD_PAGE_POS_BELOW);
		}
	}
	
}

/*
	Omit PHP closing tag to help avoid accidental output
*/