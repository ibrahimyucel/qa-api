<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}
require_once QA_INCLUDE_DIR.'qa-theme-base.php';
require_once QA_INCLUDE_DIR.'qa-app-blobs.php';

class qa_html_theme_layer extends qa_html_theme_base {

	private $extradata;
	private $pluginurl;
	
	function main() {
		if($this->template == 'ask') {
			if(isset($this->content['form']))
				$this->qa_html_poster(null, $this->content['form']);
		} /*else if(isset($this->content['form_q_edit'])) {
				$this->qa_html_poster($this->content['q_view']['raw']['postid'], $this->content['form_q_edit']);
		}*/
		qa_html_theme_base::main();
	}
	
	function qa_html_poster($postid, &$form) {
		global $qa_extra_question_fields;
		//unset ($form['fields']['title']);
		unset ($form['fields']['content']);
		unset ($form['buttons']);
		unset ($form['hidden']);
		
		if (qa_clicked('doaskhq')) {
			require_once QA_INCLUDE_DIR.'app/post-create.php';
			require_once QA_INCLUDE_DIR.'util/string.php';

			$categoryids = array_keys(qa_category_path($categories, @$in['categoryid']));
			$userlevel = qa_user_level_for_categories($categoryids);

			$in['name'] = qa_opt('allow_anonymous_naming') ? qa_post_text('name') : null;
			$in['notify'] = strlen(qa_post_text('notify')) > 0;
			$in['email'] = qa_post_text('email');
			$in['queued'] = qa_user_moderation_reason($userlevel) !== false;
			
			//$upload = fopen($_FILES['htmlfile']['tmp_name'], 'rb');
			$upload = file_get_contents($_FILES['htmlfile']['tmp_name']);
			$content = explode('<h1>Answer</h1>', $upload);
			$errors = array();

			if (!qa_check_form_security_code('ask', qa_post_text('code'))) {
				$errors['page'] = qa_lang_html('misc/form_security_again');
			}
			else {
				$filtermodules = qa_load_modules_with('filter', 'filter_question');
				foreach ($filtermodules as $filtermodule) {
					$oldin = $in;
					$filtermodule->filter_question($in, $errors, null);
					qa_update_post_text($in, $oldin);
				}

				if (qa_using_categories() && count($categories) && (!qa_opt('allow_no_category')) && !isset($in['categoryid'])) {
					// check this here because we need to know count($categories)
					$errors['categoryid'] = qa_lang_html('question/category_required');
				}
				elseif (qa_user_permit_error('permit_post_q', null, $userlevel)) {
					$errors['categoryid'] = qa_lang_html('question/category_ask_not_allowed');
				}

				if ($captchareason) {
					require_once QA_INCLUDE_DIR.'app/captcha.php';
					qa_captcha_validate_post($errors);
				}

				if (empty($errors)) {
					// check if the question is already posted
					$testTitleWords = implode(' ', qa_string_to_words($in['title']));
					$testContentWords = implode(' ', qa_string_to_words($in['content']));
					$recentQuestions = qa_db_select_with_pending(qa_db_qs_selectspec(null, 'created', 0, null, null, false, true, 5));

					foreach ($recentQuestions as $question) {
						if (!$question['hidden']) {
							$qTitleWords = implode(' ', qa_string_to_words($question['title']));
							$qContentWords = implode(' ', qa_string_to_words($question['content']));

							if ($qTitleWords == $testTitleWords && $qContentWords == $testContentWords) {
								$errors['page'] = qa_lang_html('question/duplicate_content');
								break;
							}
						}
					}
				}

				if (empty($errors)) {
					$cookieid = isset($userid) ? qa_cookie_get() : qa_cookie_get_create(); // create a new cookie if necessary

					$questionid = qa_question_create($followanswer, $userid, qa_get_logged_in_handle(), $cookieid,
						$in['title'], $in['content'], $in['format'], $in['text'], isset($in['tags']) ? qa_tags_to_tagstring($in['tags']) : '',
						$in['notify'], $in['email'], $in['categoryid'], $in['extra'], $in['queued'], $in['name']);

					qa_redirect(qa_q_request($questionid, $in['title'])); // our work is done here
				}
			}
		}

		$field = array(
			'label' => qa_lang_html('hq_lang/hq_upload_label'),
			'type' => 'file',
			'tags' => 'name="htmlfile"',
			'value' => qa_html(@$in['htmlfile']),
			'error' => qa_html(@$errors['htmlfile']),
		);
		
		$form['buttons'] = array(
				'askhq' => array(
					'tags' => 'onclick="qa_show_waiting_after(this, false);"',
					'label' => qa_lang_html('question/ask_button'),
				),
			);

		$form['hidden'] = array(
				'code' => qa_get_form_security_code('askhq'),
				'doaskhq' => '1',
			);
		
		qa_array_insert($form['fields'], 'tags', array('htmlfile' => $field));
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