<?php

class html_question
{
	private $directory;
	private $urltoroot;
	
	public function load_module($directory, $urltoroot)
	{
		$this->directory = $directory;
		$this->urltoroot = $urltoroot;
	}

	public function suggest_requests() // for display in admin INTerface
	{
		return array(
			array(
				'title' => 'Bulk Ask',
				'request' => 'bulkask',
				'nav' => 'M',
			),
		);
	}

	public function match_request( $request )
	{
		return strpos($request, 'bulkask') !== false;
	}
		
	public function option_default($option)
	{
		switch ($option) {
			case 'hq_title_limit':
				return 50;
			case 'hq_html_delimeter':
				return '<h3 class="widget-title">Solution</h3>';
		}
	}

	public function admin_form()
	{
		$saved = false;

		if (qa_clicked('hq_save_button')) {
			qa_opt('hq_title_limit', $this->option_default('hq_title_limit'));
			qa_opt('hq_html_delimeter', $this->option_default('hq_html_delimeter'));
			$saved = true;
		}

		else if (qa_clicked('hq_reset_button')) {
			qa_opt('hq_title_limit', qa_post_text('hq_title_limit_field'));
			qa_opt('hq_html_delimeter', qa_post_text('hq_html_delimeter_field'));
			$saved = true;
		}

		return array(
			'ok' => $saved ? qa_lang_html('hq_lang/hq_options_saved') : null,

			'fields' => array(
				'title_limit' => array(
					'label' => qa_lang_html('hq_lang/hq_title_limit'),
					'value' => qa_html(qa_opt('hq_title_limit') ? qa_opt('hq_title_limit') :
						$this->option_default('hq_title_limit')),
					'tags' => 'name="hq_title_limit_field"',
					'type' => 'number',
					'suffix' => qa_lang_html('hq_lang/hq_title_limit_suffix'),
				),
				'html_delimeter' => array(
					'label' => qa_lang_html('hq_lang/hq_html_delimeter'),
					'value' => qa_html(qa_opt('hq_html_delimeter') ? qa_opt('hq_html_delimeter') :
						$this->option_default('hq_html_delimeter')),
					'tags' => 'name="hq_html_delimeter_field"',
				),
			),
			
			'buttons' => array(
				'save' => array(
					'label' => qa_lang_html('hq_lang/hq_save_button'),
					'tags' => 'name="hq_save_button"',
				),
				
				'reset' => array(
					'label' => qa_lang_html('hq_lang/hq_reset_button'),
					'tags' => 'name="hq_reset_button"',
				),
			),
		);
	}
	
	
		public function process_request( $request )
		{
			global $qa_request;
			$qa_content = qa_content_prepare();
			
			$permiterror = qa_user_maximum_permit_error('permit_post_q', QA_LIMIT_QUESTIONS);

			if ($permiterror) {
				$qa_content = qa_content_prepare();

				switch ($permiterror) {
					case 'login':
						$qa_content['error'] = qa_insert_login_links(qa_lang_html('question/ask_must_login'), qa_request(), isset($followpostid) ? array('follow' => $followpostid) : null);
						break;

					case 'confirm':
						$qa_content['error'] = qa_insert_login_links(qa_lang_html('question/ask_must_confirm'), qa_request(), isset($followpostid) ? array('follow' => $followpostid) : null);
						break;

					case 'limit':
						$qa_content['error'] = qa_lang_html('question/ask_limit');
						break;

					case 'approve':
						$qa_content['error'] = strtr(qa_lang_html('question/ask_must_be_approved'), array(
							'^1' => '<a href="' . qa_path_html('account') . '">',
							'^2' => '</a>',
						));
						break;

					default:
						$qa_content['error'] = qa_lang_html('users/no_permission');
						break;
				}

				return $qa_content;
			}

			$captchareason = qa_user_captcha_reason();

			$qa_content = qa_content_prepare();

			$qa_content['title'] = qa_lang_html('hq_lang/hq_askbulk');
			$qa_content['error'] = @$errors['page'];
						
			if (qa_clicked('dobulkask')) {
				require_once QA_INCLUDE_DIR . 'app/post-create.php';
				require_once QA_INCLUDE_DIR . 'app/post-create.php';
				require_once QA_INCLUDE_DIR . 'app/post-update.php';
				require_once QA_INCLUDE_DIR . 'util/string.php';
				require_once QA_INCLUDE_DIR . 'pages/question-view.php';
				require_once QA_INCLUDE_DIR . 'app/updates.php';
				require_once QA_INCLUDE_DIR . 'db/post-update.php';
				
				$delimiter =  qa_opt('hq_html_delimeter') ? qa_opt('hq_html_delimeter') : '<h3 class="widget-title">Solution</h3>';
				$userid = qa_get_logged_in_userid();
				$handle = qa_get_logged_in_handle();
				
				$total = count($_FILES['questions']['name']);
				
				for ($i = 0; $i < $total; $i++) {
					$upload = file_get_contents($_FILES['questions']['tmp_name'][$i]);
					$postcontent = explode($delimiter, $upload);
					
					if (count($postcontent)) {
						$title = $this->set_title($postcontent[0]);
						$content = $this->set_question($postcontent[0]);
						$text = qa_remove_utf8mb4(qa_viewer_text($content, 'html'));
						
						$cookieid = isset($userid) ? qa_cookie_get() : qa_cookie_get_create(); // create a new cookie if necessary

						$questionid = qa_question_create(null, $userid, qa_get_logged_in_handle(), $cookieid, $title, $content, 'html', $text, null, null, null, null, null, null, null);
						
						$question = array( 'postid' => $questionid, 'categoryid' => null );
						
						$acontent = $this->set_answer($postcontent[1]);
						$atext = qa_remove_utf8mb4(qa_viewer_text($acontent, 'html'));
					
						$answerid = qa_answer_create($userid, $handle, $cookieid, $acontent, 'html', $atext, null, null, $question, null, null);
					
						$this->set_selected_answer($question, $answerid, $userid, $handle, $cookieid);
					}
				}
				qa_redirect('');	
			}
			
			$qa_content['form'] = array(
				'tags' => 'name="bulkask" method="post" action="'.qa_self_html().'" enctype="multipart/form-data"',

				'style' => 'tall',

				'fields' => array(
					'custom' => array(
						'type' => 'custom',
						'html' => '<table id="selectedFiles"></table>' . 
							'<div id="countfiles"></div>',
					),

					'questions' => array(
						'label' => qa_lang_html('hq_lang/hq_select_files'),
						'tags' => 'name="questions[]" id="questions" multiple',
						'value' => qa_html(@$questions),
						'type' => 'file',
						'error' => qa_html(@$errors['questions']),
					),

				),

				'buttons' => array(
					'ask' => array(
						'tags' => 'onclick="qa_show_waiting_after(this, false);"',
						'label' => qa_lang_html('hq_lang/hq_submit_questions'),
					),
				),

				'hidden' => array(
					'code' => qa_get_form_security_code('bulkask'),
					'dobulkask' => '1',
				),
			);

			if ($captchareason) {
				require_once QA_INCLUDE_DIR.'app/captcha.php';
				qa_set_up_captcha_field($qa_content, $qa_content['form']['fields'], @$errors, qa_captcha_reason_note($captchareason));
			}

			$qa_content['focusid'] = 'title';

			return $qa_content;
		}
		
		function set_title($question){
			$titlelimit = qa_opt('hq_title_limit') ? qa_opt('hq_title_limit') : 50;
			if (strpos($question, '</p>') < 10) $question = str_replace("</p>", ":", $question);
			
			$title = substr($question, 0, strpos($question, ' ', $titlelimit));
			return strip_tags($title);
			//return $p_occurs;
		}
		
		function set_question($question){
			$content = str_replace("\\", "", $question);
			//$content = str_replace(". (", ".<br> (", $content);
			$content = str_replace("). ", "). <br><br>", $content);
			return $content;
		}
		
		function set_answer($question){
			$content = str_replace("\\", "", $question);
			$content = str_replace(";}", ";<br>}", $content);
			$content = str_replace("). ", "). <br><br>", $content);
			return $content;
		}
		
		function set_selected_answer($question, $answerid, $userid, $handle, $cookieid){		
			qa_db_post_set_selchildid($question['postid'], $answerid, $userid);
			qa_db_points_update_ifuser($userid, 'aselecteds');

			qa_report_event('a_select', $userid, $handle, $cookieid, array(
				'parentid' => $question['postid'],
				'parent' => $question,
				'postid' => $answerid,
				'answer' => $answerid,
			));
			
		}
		
	}
