<?php
	require_once '../qa-include/qa-base.php';
	require_once '../qa-include/qa-db-users.php';
	require_once '../qa-include/qa-db-selects.php';

	require_once '../qa-include/qa-app-format.php';
	require_once '../qa-include/qa-app-users.php';

	
	$inemail = qa_post_text('email');
	$inhandle = qa_post_text('handle');
	$inpassword = qa_post_text('password');
	$inremember = qa_post_text('remember');
	
	$success = 0;
	$message = '';
	$data = array();
	
	$userfields = qa_db_select_with_pending(
		qa_db_userfields_selectspec()
	);

	foreach ($userfields as $index => $userfield) {
		if (!($userfield['flags'] & QA_FIELD_FLAGS_ON_REGISTER))
			unset($userfields[$index]);
	}

	if (qa_opt('suspend_register_users')) {
		$success = 0;
		$message = qa_lang_html('users/register_suspended');
	}

	else if (qa_user_permit_error()) {
		$success = 0;
		$message = qa_lang_html('users/no_permission');
	}

	else if (strlen($inemail) && strlen($inhandle) && strlen($inpassword)) {
		require_once QA_INCLUDE_DIR . 'app/limits.php';

		if (qa_user_limits_remaining(QA_LIMIT_REGISTRATIONS)) {
			require_once QA_INCLUDE_DIR . 'app/users-edit.php';

			// core validation
			$errors = array_merge(
				qa_handle_email_filter($inhandle, $inemail),
				qa_password_validate($inpassword)
			);

			$inprofile = array();
			foreach ($userfields as $userfield)
				$inprofile[$userfield['fieldid']] = qa_post_text('field_' . $userfield['fieldid']);

			if (count($inprofile)) {
				$filtermodules = qa_load_modules_with('filter', 'filter_profile');
				foreach ($filtermodules as $filtermodule)
					$filtermodule->filter_profile($inprofile, $errors, null, null);
			}

			if (empty($errors)) {
				// register and redirect
				qa_limits_increment(null, QA_LIMIT_REGISTRATIONS);

				$userid = qa_create_new_user($inemail, $inpassword, $inhandle);
				$userinfo = qa_db_select_with_pending(qa_db_user_account_selectspec($userid, true));
				
				qa_set_logged_in_user($userid, $inhandle);
				
				$success = 1;
				$message = 'Registered and Logged in successfully';
				$data['userid'] = $userid;
				$data['email'] = $userinfo['email'];
				$data['level'] = $userinfo['level'];
				$data['handle'] = $userinfo['handle'];
				$data['created'] = $userinfo['created'];
				$data['loggedin'] = $userinfo['loggedin'];
				$data['avatarblobid'] = $userinfo['avatarblobid'];
				$data['points'] = $userinfo['points'];
				$data['wallposts'] = $userinfo['wallposts'];
				
				$topath = qa_get('to');

				//if (isset($topath)) qa_redirect_raw(qa_path_to_root() . $topath); // path already provided as URL fragment
				//else qa_redirect('');
			}

		} else {
			$success = 0;
			$message = qa_lang('users/register_limit');
		}

	} else {
		$success = 0;
		$message = 'You need to enter a handle, email and a  password to proceed';
	}
	
	$output = json_encode(array('success' => $success, 'message' => $message, 'data' => $data));	
	echo $output;