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

	require_once QA_INCLUDE_DIR.'app/users.php';

	class pm_feedback
	{
		private $directory;
		private $urltoroot;
		
		public function load_module($directory, $urltoroot)
		{
			$this->directory = $directory;
			$this->urltoroot = $urltoroot;
		}

		public function match_request( $request )
		{
			return $request == 'send_feedback';
		}
		
		function option_default($option)
		{
			switch ($option) {
				case 'pm_message_editor': return '';
			}
		}
		
		function init_queries( $tableslc )
		{
			$tbl1 = qa_db_add_table_prefix('feedbacks');
			if ( in_array($tbl1, $tableslc)) return null;
			
			return array(
				'CREATE TABLE IF NOT EXISTS ^feedbacks (
					`feedbackid` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					`name` VARCHAR(800) DEFAULT NULL,
					`email` VARCHAR(800) DEFAULT NULL,
					`userid` INT(10) UNSIGNED DEFAULT 0,
					`parentid` INT(10) UNSIGNED DEFAULT 0,
					`format` VARCHAR(20) CHARACTER SET ascii NOT NULL DEFAULT \'\',
					`title` VARCHAR(800) DEFAULT NULL,
					`topic` VARCHAR(800) DEFAULT NULL,
					`content` VARCHAR(10000) DEFAULT NULL,
					`created` DATETIME NOT NULL,
					`referer` VARCHAR(100) DEFAULT NULL,
					`browser` VARCHAR(100) DEFAULT NULL,
					`cookieid` VARCHAR(10) DEFAULT NULL,
					`createip` VARCHAR(100) DEFAULT NULL,
					PRIMARY KEY (`feedbackid`),
					CONSTRAINT `feedbacks_ibfk_1` FOREIGN KEY (`userid`) REFERENCES ^users (`userid`) ON DELETE SET NULL
				) ENGINE=InnoDB DEFAULT CHARSET=utf8',
			);
		}
		
		public function process_request($request)
		{				
			require_once QA_INCLUDE_DIR . 'app/captcha.php';
			require_once QA_INCLUDE_DIR . 'db/selects.php';


			// Get useful information on the logged in user

			$userid = qa_get_logged_in_userid();

			if (isset($userid) && !QA_FINAL_EXTERNAL_USERS) {
				list($useraccount, $userprofile) = qa_db_select_with_pending(
					qa_db_user_account_selectspec($userid, true),
					qa_db_user_profile_selectspec($userid, true)
				);
			}

			$usecaptcha = qa_opt('captcha_on_feedback') && qa_user_use_captcha();

			if (!qa_opt('feedback_enabled')) return include QA_INCLUDE_DIR . 'qa-page-not-found.php';

			if (qa_user_permit_error()) {
				$qa_content = qa_content_prepare();
				$qa_content['error'] = qa_lang_html('users/no_permission');
				return $qa_content;
			}

			$feedbacksent = false;

			if (qa_clicked('dofeedback')) {
				require_once QA_INCLUDE_DIR . 'app/emails.php';
				require_once QA_INCLUDE_DIR . 'util/string.php';
				
				qa_get_post_content('editor', 'message', $in['editor'], $in['message'], $in['format'], $in['text']);

				$inmessage = $in['message'];
				$inname = qa_post_text('name');
				$intopic = qa_post_text('topic');
				$inemail = qa_post_text('email');
				$inreferer = qa_post_text('referer');
				$cookieid = isset($userid) ? qa_cookie_get() : qa_cookie_get_create();

				if (!qa_check_form_security_code('send_feedback', qa_post_text('code')))
					$pageerror = qa_lang_html('misc/form_security_again');

				else {
					if (empty($inmessage))
						$errors['message'] = qa_lang('misc/feedback_empty');

					if ($usecaptcha)
						qa_captcha_validate_post($errors);

					if (empty($errors)) {
						$this->pm_feedback_create($inreferer, 0, $userid, $cookieid, qa_remote_ip_address(), @$_SERVER['HTTP_USER_AGENT'], $intopic, $inmessage, $in['format'], $inemail, $inname, qa_get_logged_in_handle());

					}
				}
			}


			// Prepare content for theme

			$qa_content = qa_content_prepare();

			$qa_content['title'] = qa_lang_html('misc/feedback_title');

			$qa_content['error'] = @$pageerror;

			$editorname = isset($in['editor']) ? $in['editor'] : qa_opt('pm_message_editor');
			$editor = qa_load_editor(@$in['message'], @$in['format'], $editorname);

			$field = qa_editor_load_field($editor, $qa_content, @$in['message'], @$in['format'], 'message', 12, false);
			$field['label'] = qa_lang_html_sub('misc/feedback_message', qa_opt('site_title'));
			$field['tags'] = 'name="message" id="message"';
			$field['value'] = qa_html(@$inmessage);
			$field['rows'] = 8;
			$field['style'] = 'tall';
			$field['error'] = qa_html(@$errors['message']);

			$qa_content['form'] = array(
				'tags' => 'name="send_feedback" method="post" action="' . qa_self_html() . '"',
				'style' => 'wide',

				'fields' => array(
					'topic' => array(
						'label' => qa_lang('pm_lang/select_topic'),
						'type' => 'select',
						'tags' => 'name="topic"',
						'options' => pm_string_to_array(qa_opt('pm_feedbacks_topics')),
						'error' => qa_html(@$errors['topic']),
					),
						
					'name' => array(
						'type' => 'text',
						'label' => qa_lang_html('pm_lang/feedback_name'),
						'tags' => 'name="name"',
						'value' => qa_html(isset($inname) ? $inname : @$userprofile['name']),
						'error' => qa_html(@$errors['name']),
					),

					'email' => array(
						'type' => 'email',
						'label' => qa_lang_html('pm_lang/feedback_email'),
						'tags' => 'name="email"',
						'value' => qa_html(isset($inemail) ? $inemail : qa_get_logged_in_email()),
						'note' => $feedbacksent ? null : qa_opt('email_privacy'),
						'error' => qa_html(@$errors['email']),
					),
					
					'message' => $field,

				),

				'buttons' => array(
					'send' => array(
						'tags' => 'onclick="qa_show_waiting_after(this, false); '.
							(method_exists($editor, 'update_script') ? $editor->update_script('message') : '').'"',
						'label' => qa_lang_html('pm_lang/send_feedback'),
					),
				),

				'hidden' => array(
					'dofeedback' => '1',
					'editor' => qa_html($editorname),
					'code' => qa_get_form_security_code('send_feedback'),
					'referer' => qa_html(isset($inreferer) ? $inreferer : @$_SERVER['HTTP_REFERER']),
				),
			);

			if ($usecaptcha && !$feedbacksent)
				qa_set_up_captcha_field($qa_content, $qa_content['form']['fields'], @$errors);


			$qa_content['focusid'] = 'message';

			if ($feedbacksent) {
				$qa_content['form']['ok'] = qa_lang_html('misc/feedback_sent');
				unset($qa_content['form']['buttons']);
			}


			return $qa_content;
		}
		
		function pm_feedback_create($referer, $parentid, $userid, $cookieid, $ip, $browser, $topic, $message, $format, $email, $name, $handle )
		{
			qa_db_query_sub(
				'INSERT INTO ^feedbacks (referer, parentid, userid, cookieid, createip, topic, content, format, email, name, created) ' .
				'VALUES (#, $, #, UNHEX($), $, $, $, $, $, $, NOW())',
				$referer, $parentid, $userid, $cookieid, bin2hex(@inet_pton($ip)), $topic, $message, $format, $email, $name
			);

			//$feedbackid = qa_db_last_insert_id();
			
			$subs = array(
				'^message' => $message,
				'^name' => empty($name) ? '-' : $name,
				'^email' => empty($email) ? '-' : $email,
				'^previous' => empty($referer) ? '-' : $referer,
				'^url' => isset($userid) ? qa_path_absolute('user/' . $handle) : '-',
				'^ip' => $ip,
				'^browser' => $browser,
			);

			if (qa_send_email(array(
				'fromemail' => qa_opt('from_email'),
				'fromname' => $name,
				'replytoemail' => qa_email_validate(@$email) ? $email : null,
				'replytoname' => $name,
				'toemail' => qa_opt('feedback_email'),
				'toname' => qa_opt('site_title'),
				'subject' => qa_lang_sub('emails/feedback_subject', qa_opt('site_title')),
				'body' => strtr(qa_lang('emails/feedback_body'), $subs),
				'html' => false,
			))) {
				$feedbacksent = true;
			} else {
				$pageerror = qa_lang_html('main/general_error');
			}

			qa_report_event('feedback', $userid, $handle, $cookieid, array(
				'email' => $email,
				'name' => $name,
				'message' => $message,
				'previous' => $referer,
				'browser' => $browser,
			));
		}
	}
