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

	class pm_message
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
			return strpos($request, 'messanger') !== false;
		}
		
		function option_default($option)
		{
			switch ($option) {
				case 'pm_message_editor': return '';
			}
		}
		
		function admin_form(&$qa_content)
		{
			$saved=false;
			if (qa_clicked('save_button')) {
				qa_set_option('pm_message_editor', qa_post_text('option_pm_message_editor'));
				qa_set_option('pm_feedbacks_topics', qa_post_text('option_pm_feedbacks_topics'));
				$saved=true;
			}
			
			$seteditor = qa_opt('pm_message_editor');
			$editorfield = array();
			$editorfield['label'] = qa_lang_html('pm_lang/pm_editor');
			$editorfield['tags'] = 'name="option_pm_message_editor" id="option_pm_message_editor"';
					
			$editors = qa_list_modules('editor');
			$selectoptions = array();
			foreach ($editors as $editor) {
				$selectoptions[qa_html($editor)] = strlen($editor) ? qa_html($editor) : qa_lang_html('admin/basic_editor');
				if ($editor == $seteditor) {
					$module = qa_load_module('editor', $editor);
					if (method_exists($module, 'admin_form')) {
						$editorfield['note'] = '<a href="' . qa_admin_module_options_path('editor', $editor) . '">' . qa_lang_html('admin/options') . '</a>';
					}
				}
			}
			pm_optionfield_make_select($editorfield, $selectoptions, $seteditor, '');
			
			return array(
				'ok' => $saved ? qa_lang_html('pm_lang/settings_saved') : null,
				
				'fields' => array(
					'pm_message_editor' => $editorfield,
					'pm_feedbacks_topics' => array(
						'type' => 'textarea',
						'label' => qa_lang('pm_lang/feedback_topics'),
						'value' => qa_opt('pm_feedbacks_topics'),
						'rows' => 4,
						'tags' => 'name="option_pm_feedbacks_topics" id="option_pm_feedbacks_topics"',
					),
				),
				
				'buttons' => array(
					array(
						'label' => qa_lang_html('admin/save_options_button'),
						'tags' => 'name="save_button"',
					),
				),
			);
		}

		public function process_request($request)
		{			
			require_once QA_INCLUDE_DIR . 'db/selects.php';
			require_once QA_INCLUDE_DIR . 'app/users.php';
			require_once QA_INCLUDE_DIR . 'app/format.php';
			require_once QA_INCLUDE_DIR . 'app/limits.php';

			$handle = qa_request_part(1);
			$loginuserid = qa_get_logged_in_userid();
			$fromhandle = qa_get_logged_in_handle();

			$qa_content = qa_content_prepare();

			// Check we have a handle, we're not using Q2A's single-sign on integration and that we're logged in
			if (QA_FINAL_EXTERNAL_USERS)
				qa_fatal_error('User accounts are handled by external code');

			if (!strlen($handle)) qa_redirect('users');

			if (!isset($loginuserid)) {
				$qa_content['error'] = qa_insert_login_links(qa_lang_html('misc/message_must_login'), qa_request());
				return $qa_content;
			}

			if ($handle === $fromhandle) {
				// prevent users sending messages to themselves
				$qa_content['error'] = qa_lang_html('users/no_permission');
				return $qa_content;
			}


			// Find the user profile and their recent private messages

			list($toaccount, $torecent, $fromrecent) = qa_db_select_with_pending(
				qa_db_user_account_selectspec($handle, false),
				qa_db_recent_messages_selectspec($loginuserid, true, $handle, false),
				qa_db_recent_messages_selectspec($handle, false, $loginuserid, true)
			);


			// Check the user exists and work out what can and can't be set (if not using single sign-on)

			if (!qa_opt('allow_private_messages') || !is_array($toaccount))
				return include QA_INCLUDE_DIR . 'qa-page-not-found.php';

			//  Check the target user has enabled private messages and inform the current user in case they haven't

			if ($toaccount['flags'] & QA_USER_FLAGS_NO_MESSAGES) {
				$qa_content['error'] = qa_lang_html_sub(
					'profile/user_x_disabled_pms',
					sprintf('<a href="%s">%s</a>', qa_path_html('user/' . $handle), qa_html($handle))
				);
				return $qa_content;
			}

			// Check that we have permission and haven't reached the limit, but don't quit just yet

			switch (qa_user_permit_error(null, QA_LIMIT_MESSAGES)) {
				case 'limit':
					$pageerror = qa_lang_html('misc/message_limit');
					break;

				case false:
					break;

				default:
					$pageerror = qa_lang_html('users/no_permission');
					break;
			}


			// Process sending a message to user

			// check for messages or errors
			$state = qa_get_state();
			$in = array();
			$messagesent = $state == 'message-sent';
			if ($state == 'email-error')
				$pageerror = qa_lang_html('main/email_error');

			if (qa_post_text('domessage')) {
				qa_get_post_content('editor', 'message', $in['editor'], $in['message'], $in['format'], $in['text']);
				$inmessage = $in['message'];
				
				if (isset($pageerror)) {
					// not permitted to post, so quit here
					$qa_content['error'] = $pageerror;
					return $qa_content;
				}

				if (!qa_check_form_security_code('messanger-' . $handle, qa_post_text('code')))
					$pageerror = qa_lang_html('misc/form_security_again');

				else {
					if (empty($inmessage))
						$errors['message'] = qa_lang('misc/message_empty');

					if (empty($errors)) {
						require_once QA_INCLUDE_DIR . 'db/messages.php';
						require_once QA_INCLUDE_DIR . 'app/emails.php';

						if (qa_opt('show_message_history'))
							$messageid = qa_db_message_create($loginuserid, $toaccount['userid'], $inmessage, $in['format'], false);
						else
							$messageid = null;

						$canreply = !(qa_get_logged_in_flags() & QA_USER_FLAGS_NO_MESSAGES);

						$more = strtr(qa_lang($canreply ? 'emails/private_message_reply' : 'emails/private_message_info'), array(
							'^f_handle' => $fromhandle,
							'^url' => qa_path_absolute($canreply ? ('messanger/' . $fromhandle) : ('user/' . $fromhandle)),
						));

						$subs = array(
							'^message' => $inmessage,
							'^f_handle' => $fromhandle,
							'^f_url' => qa_path_absolute('user/' . $fromhandle),
							'^more' => $more,
							'^a_url' => qa_path_absolute('account'),
						);

						if (qa_send_notification($toaccount['userid'], $toaccount['email'], $toaccount['handle'],
							qa_lang('emails/private_message_subject'), qa_lang('emails/private_message_body'), $subs))
							$messagesent = true;

						qa_report_event('u_message', $loginuserid, qa_get_logged_in_handle(), qa_cookie_get(), array(
							'userid' => $toaccount['userid'],
							'handle' => $toaccount['handle'],
							'messageid' => $messageid,
							'message' => $inmessage,
						));

						// show message as part of general history
						if (qa_opt('show_message_history'))
							qa_redirect(qa_request(), array('state' => ($messagesent ? 'message-sent' : 'email-error')));
					}
				}
			}


			// Prepare content for theme

			$hideForm = !empty($pageerror) || $messagesent;

			$qa_content['title'] = qa_lang_html('misc/private_message_title');
			$qa_content['error'] = @$pageerror;
			
			$editorname = isset($in['editor']) ? $in['editor'] : qa_opt('pm_message_editor');
			$editor = qa_load_editor(@$in['message'], @$in['format'], $editorname);

			$field = qa_editor_load_field($editor, $qa_content, @$in['message'], @$in['format'], 'message', 12, false);
			$field['label'] = qa_lang_html_sub('misc/message_for_x', qa_get_one_user_html($handle, false));
			$field['tags'] = 'name="message" id="message"';
			$field['value'] = qa_html(@$inmessage, $messagesent);
			$field['rows'] = 8;
			$field['note'] = qa_lang_html_sub('misc/message_explanation', qa_html(qa_opt('site_title')));
			$field['error'] = qa_html(@$errors['message']);

			$qa_content['form'] = array(
				'tags' => 'name="messanger" method="post" action="'.qa_self_html().'"',
				'style' => 'tall',
				'fields' => array('message' => $field),

				'buttons' => array(
					'send' => array(
						'tags' => 'onclick="qa_show_waiting_after(this, false); '.
							(method_exists($editor, 'update_script') ? $editor->update_script('message') : '').'"',
						'label' => qa_lang_html('pm_lang/send_message'),
					),
				),

				'hidden' => array(
					'editor' => qa_html($editorname),
					'code' => qa_get_form_security_code('messanger-' . $handle),
					'domessage' => '1',
				),
			);

			$qa_content['focusid'] = 'message';

			if ($hideForm) {
				unset($qa_content['form_message']['buttons']);

				if (qa_opt('show_message_history'))
					unset($qa_content['form_message']['fields']['message']);
				else {
					unset($qa_content['form_message']['fields']['message']['note']);
					unset($qa_content['form_message']['fields']['message']['label']);
				}
			}

			if (qa_opt('show_message_history')) {
				$recent = array_merge($torecent, $fromrecent);

				qa_sort_by($recent, 'created');

				$showmessages = array_slice(array_reverse($recent, true), 0, QA_DB_RETRIEVE_MESSAGES);

				if (count($showmessages)) {
					$qa_content['message_list'] = array(
						'title' => qa_lang_html_sub('misc/message_recent_history', qa_html($toaccount['handle'])),
					);

					$options = qa_message_html_defaults();

					foreach ($showmessages as $message)
						$qa_content['message_list']['messages'][] = qa_message_html_fields($message, $options);
				}
				$qa_content['navigation']['sub'] = qa_user_sub_navigation($fromhandle, 'messages', true);
			}
			$qa_content['raw']['account'] = $toaccount; 
			
			return $qa_content;
		}

	}
