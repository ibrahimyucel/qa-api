<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/facebook-login/qa-facebook-login-page.php
	Description: Page which performs Facebook login action


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

class qa_openid_login_page
{
	private $directory;

	public function load_module($directory, $urltoroot)
	{
		$this->directory = $directory;
	}

	public function match_request( $request )
	{
		return strpos($request, 'openid') !== false;
	}
	
	public function process_request( $request )
	{		
		$tourl = qa_get('to');
		switch (qa_request_part(1)){
			case 'facebook':				
				$app_id = qa_opt('facebook_app_id');
				$app_secret = qa_opt('facebook_app_secret');
				
				if (!strlen($tourl)) $tourl = qa_path_absolute('');

				if (strlen($app_id) && strlen($app_secret)) {
					require_once $this->directory . 'qa-facebook.php';

					$facebook = new Facebook(array( 'appId' => $app_id, 'secret' => $app_secret, 'cookie' => true ));

					$fb_userid = $facebook->getUser();

					if ($fb_userid) {
						$user = $facebook->api('/me?fields=email,name,verified,location,website,about,picture.width(250)');

						if (is_array($user))
							qa_log_in_external_user('facebook', $fb_userid, array(
								'email' => @$user['email'],
								'handle' => @$user['name'],
								'confirmed' => @$user['verified'],
								'name' => @$user['name'],
								'location' => @$user['location']['name'],
								'website' => @$user['website'],
								'about' => @$user['bio'],
								'avatar' => strlen(@$user['picture']['data']['url']) ? qa_retrieve_url($user['picture']['data']['url']) : null,
							));
					} else {
						qa_redirect_raw($facebook->getLoginUrl(array('redirect_uri' => $tourl)));
					}
				}

				qa_redirect_raw($tourl);
				
				break;
				
			case 'google':
				$code = qa_get('code');
				$redirect_url = 'http://appsmata.kenyanexamsrevisions.co.ke';
				$client_id = qa_opt('google_client_id');
				$client_secret = qa_opt('google_client_secret');
				
				if (!strlen($tourl)) $tourl = qa_path_absolute('');
				
				if (strlen($client_id) && strlen($client_secret)) {
					require_once $this->directory . 'qa-google.php';

					if (isset($code)) {
						try {
							$google = new Google();
							$data = $google->GetAccessToken($client_id, $redirect_url, $client_secret, $code);
							$user = $google->GetUserProfileInfo($data['access_token']);
							
							if (is_array($user)) {
								qa_log_in_external_user('google', @$user['id'], array(
									'email' => @$user['email'],
									'handle' => @$user['name'],
									'confirmed' => @$user['verified_email'],
									'name' => @$user['name'],
									'avatar' => strlen(@$user['picture']),
							));
							} else {
								qa_redirect_raw($google->getLoginUrl(array('redirect_uri' => $tourl)));
							}
						}
						catch(Exception $e) {
							echo $e->getMessage();
						}
					}
				}
				qa_redirect_raw($tourl);
				
				break;
				
			case 'linkedin':
				
				break;
		}
	}
	
}
