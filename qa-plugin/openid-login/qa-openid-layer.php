<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/facebook-login/qa-facebook-layer.php
	Description: Theme layer class for mouseover layer plugin


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

class qa_html_theme_layer extends qa_html_theme_base
{
	public function head_css()
	{
		qa_html_theme_base::head_css();

		if (strlen(qa_opt('facebook_app_id')) && strlen(qa_opt('facebook_app_secret'))) {
			$this->output('<link href="qa-plugin/openid-login/qa-open-login.css" rel="stylesheet" type="text/css">');
		}
	}
	
	public function body_script()
	{	
		$this->output('
<div class="fb-root"></div>');

		$this->output('
<script>window.fbAsyncInit = function() {
  FB.init({
        appId: \''.qa_opt('facebook_app_id').'\',
        autoLogAppEvents: true,
        xfbml: true,
        version: \'v3.0\', 
        cookie: true
  });
};
(function(d, s, id){
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) {return;}
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/sdk.js";
  fjs.parentNode.insertBefore(js, fjs);
  }(document, \'script\', \'facebook-jssdk\'));

// Variant where we implement the login button ourselves
$(document).ready(function() {
	$(\'.subscribe-facebook\').click(function(e) {
		e.preventDefault();
		FB.login(function(response) {
			if (response.authResponse) {
				window.location.href = \'openid/facebook?to='.qa_get('to').'\'
			}
		})
	});
});

function fbLoginCompleted() {
  window.location.href = \'openid/facebook\'
}</script>');
		$this->output('
<script>
jQuery(function() {
  return $.ajax({
    url: \'https://apis.google.com/js/client:plus.js?onload=gpAsyncInit\',
    dataType: \'script\',
    cache: true
  });
});

window.gpAsyncInit = function() {
	gapi.auth.authorize({
		immediate: true,
		response_type: \'code\',
		cookie_policy: \'single_host_origin\',
		client_id: \''.qa_opt('google_client_id').'\',
		scope: \'email profile\'
	}, function(response) {
		return;
	});
};

$(document).ready(function() {
	$(\'.subscribe-google\').click(function(e) {
		e.preventDefault();
		gapi.auth.authorize({
			immediate: false,
			cookie_policy: \'single_host_origin\',
			client_id: \''.qa_opt('google_client_id').'\',
			scope: \'email profile\'
		}, function(response) {
			if (response && !response.error) {
				delete response[\'g-oauth-window\'];
				// google authentication succeed, now post data to server.
				/*$.ajax({
					type: \'POST\', 
					dataType: "json", 
					url: \'/openid/google/callback?state=3e7bf90680396bc8f100da01c82bf73af28b3089\', 
					data: response
				});*/
				window.location.href = \'openid/google?code=code&&response=\' + response + \'&&to='.qa_get('to').'\';
			} else { /* google authentication failed */ }
		});
	});
})</script>');

		$this->output('
<div class="fb-root"></div>');

		qa_html_theme_base::body_script();
	}
	
	function doctype() {
		parent::doctype();
		
		if(QA_FINAL_EXTERNAL_USERS) {
			return;
		}

		// check if logged in
		$handle = qa_get_logged_in_handle();
		if (isset($handle)) {
		
			if(qa_request() == '' && count($_GET) > 0) {
				// Check if we need to associate another provider
				$this->process_login();
			}
			
			// see if the account pages are accessed
			$tmpl = array( 'account', 'favorites' );
			$user_pages = array('user', 'user-wall', 'user-activity', 
				'user-questions', 'user-answers' );
			$logins_page = qa_request() == 'logins' && !qa_get('confirm');
			$urlhandle = qa_request_part(1);
			
			if ( in_array($this->template, $tmpl) || $logins_page || 
				(in_array($this->template, $user_pages) && $handle == $urlhandle) ) {
				// add a navigation item
				$this->content['navigation']['sub']['logins'] = array(
					'label' => qa_lang_html('openid/my_logins_nav'),
					'url' => qa_path_html('logins'),
					'selected' => $logins_page
				);
				return;
			}
			
		} else {
			$show_form = qa_opt('openid_hide_login_registration') == '1' ? true : false;
			$show_facebook = qa_opt('openid_facebook_login') == '1' ? true : false;
			$show_google = qa_opt('openid_google_login') == '1' ? true : false;
			$show_linkedin = qa_opt('openid_linkedin_login') == '1' ? true : false;
			
			$tmpl = array( 'register', 'login' );
			if ( !in_array($this->template, $tmpl) ) {
				return;
			}
			$tourl = qa_opt('site_url') . qa_get('to');
			
			$form = $this->content['form'];
			unset($this->content['form']);
			
			$this->content['custom'] = '<center><h3>'.qa_lang_html('openid/login_title').'</h3></center>';
			
			$this->content['custom'] .= '<div class="text-center mb-1">';
			
			if ($show_facebook) $this->content['custom'] .= '<a class="subscribe-facebook btn-fb btn-auth max-width-230px reset-text-transform"  href="'.qa_path_absolute('openid/facebook', array('to' => $tourl)).'"><img alt="Facebook logo" class="i-fa i-fa-sm btn-fb-icon" src="qa-plugin/openid-login/images/facebook.png" href="" />Login with Facebook</a>';
			
			if ($show_form) $this->content['custom'] .= '</div><div class="text-center mb-1">';
			
			if ($show_google) $this->content['custom'] .= '&nbsp;<a class="subscribe-google btn-google btn-auth max-width-230px reset-text-transform" href="'.qa_path_absolute('openid/google', array('to' => $tourl)).'"><img alt="Google logo" class="i-fa i-fa-sm btn-fb-icon" src="qa-plugin/openid-login/images/google.png" />Login with Google</a>';
			
			$this->content['custom'] .= '</div>';
			
			// show regular login/register form on those pages only
			if (!$show_form) $this->content['form'] = $form;
		}
	}

}
