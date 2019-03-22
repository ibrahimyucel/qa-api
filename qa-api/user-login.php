<?php
	require_once '../qa-include/qa-base.php';
	require_once '../qa-include/qa-db-users.php';
	require_once '../qa-include/qa-db-selects.php';

	require_once '../qa-include/qa-app-format.php';
	require_once '../qa-include/qa-app-users.php';

	
	$inemailhandle = qa_post_text('emailhandle');
	$inpassword = qa_post_text('password');
	$inremember = qa_post_text('remember');
	
	$success = 0;
	$message = '';
	$data = array();
	if (strlen($inemailhandle) || strlen($inpassword)) {
		require_once QA_INCLUDE_DIR . 'app/limits.php';

		if (qa_user_limits_remaining(QA_LIMIT_LOGINS)) {

			/*if (!qa_check_form_security_code('login', qa_post_text('code'))) {
				$success = 0;
				$message = qa_lang_html('misc/form_security_again');
			}
			else {*/
				qa_limits_increment(null, QA_LIMIT_LOGINS);

				$errors = array();

				if (qa_opt('allow_login_email_only') || strpos($inemailhandle, '@') !== false) { // handles can't contain @ symbols
					$matchusers = qa_db_user_find_by_email($inemailhandle);
				} else {
					$matchusers = qa_db_user_find_by_handle($inemailhandle);
				}

				if (count($matchusers) == 1) { // if matches more than one (should be impossible), don't log in
					$inuserid = $matchusers[0];
					$userinfo = qa_db_select_with_pending(qa_db_user_account_selectspec($inuserid, true));

					$legacyPassOk = hash_equals(strtolower($userinfo['passcheck']), strtolower(qa_db_calc_passcheck($inpassword, $userinfo['passsalt'])));

					if (QA_PASSWORD_HASH) {
						$haspassword = isset($userinfo['passhash']);
						$haspasswordold = isset($userinfo['passsalt']) && isset($userinfo['passcheck']);
						$passOk = password_verify($inpassword, $userinfo['passhash']);

						if (($haspasswordold && $legacyPassOk) || ($haspassword && $passOk)) {
							// upgrade password or rehash, when options like the cost parameter changed
							if ($haspasswordold || password_needs_rehash($userinfo['passhash'], PASSWORD_BCRYPT)) {
								qa_db_user_set_password($inuserid, $inpassword);
							}
						} else {
							$success = 0;
							$message = qa_lang('users/password_wrong');
						}
					} else {
						if (!$legacyPassOk) {
							$success = 0;
							$message = qa_lang('users/password_wrong');
						}
					}

					if (!isset($errors['password'])) {
						qa_set_logged_in_user($inuserid, $userinfo['handle'], !empty($inremember));
						$success = 1;
						$message = 'Logged in successfully';
						$data['userid'] = $inuserid;
						$data['email'] = $userinfo['email'];
						$data['level'] = $userinfo['level'];
						$data['handle'] = $userinfo['handle'];
						$data['created'] = $userinfo['created'];
						$data['loggedin'] = $userinfo['loggedin'];
						$data['avatarblobid'] = $userinfo['avatarblobid'];
						$data['points'] = $userinfo['points'];
						$data['wallposts'] = $userinfo['wallposts'];
												
						$topath = qa_post_text('to');
						
						/*if (isset($topath)) qa_redirect_raw(qa_path_to_root() . $topath); // path already provided as URL fragment
						elseif ($passwordsent) qa_redirect('account');
						else qa_redirect('');*/
					}

				} else {
					$success = 0;
					$message = qa_lang('users/user_not_found');
				}
			//}

		} else {
			$success = 0;
			$message = qa_lang('users/login_limit');
		}

	} else {
		$success = 0;
		$message = 'You need to enter a username or email and a  password to proceed';
	}
	
	$output = json_encode(array('success' => $success, 'message' => $message, 'data' => $data));	
	echo $output;