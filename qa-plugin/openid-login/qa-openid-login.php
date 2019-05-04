<?php

class qa_openid_login
{
	
	public function load_module($directory, $urltoroot)
	{
		$this->directory = $directory;
	}

	public function admin_form(&$qa_content)
	{
		$formokhtml = null;
			
		if (qa_clicked('openid_save_button')) {
			qa_opt('openid_hide_login_registration', (int)qa_post_text('openid_hide_login_registration_field'));
			qa_opt('openid_facebook_login', (int)qa_post_text('openid_facebook_login_field'));
			qa_opt('facebook_app_id', qa_post_text('facebook_app_id_field'));
			qa_opt('facebook_app_secret', qa_post_text('facebook_app_secret_field'));
			qa_opt('openid_google_login', (int)qa_post_text('openid_google_login_field'));
			qa_opt('google_client_id', qa_post_text('google_client_id_field'));
			qa_opt('google_client_secret', qa_post_text('google_client_secret_field'));
			qa_opt('google_project_id', qa_post_text('google_project_id_field'));
			qa_opt('openid_linkedin_login', (int)qa_post_text('openid_linkedin_login_field'));
			qa_opt('linkedin_client_id', qa_post_text('linkedin_client_id_field'));
			qa_opt('linkedin_client_secret', qa_post_text('linkedin_client_secret_field'));
			
			$formokhtml = qa_lang('openid/openid_preferences_saved');
		}

		qa_set_display_rules($qa_content, array(
			'facebook_app_id_display' => 'openid_facebook_login_field',
			'facebook_app_secret_display' => 'openid_facebook_login_field',
			'google_client_id_display' => 'openid_google_login_field',
			'google_client_secret_display' => 'openid_google_login_field',
			'google_project_id_display' => 'openid_google_login_field',
			'linkedin_client_id_display' => 'openid_linkedin_login_field',
			'linkedin_client_secret_display' => 'openid_linkedin_login_field',
		));
		
		$ready = strlen(qa_opt('facebook_app_id')) && strlen(qa_opt('facebook_app_secret'));

		return array(
			'ok' => $formokhtml,
			//'tags' => 'method="post" action="'.qa_path_html(qa_request()).'"',
			'style' => 'tall',
			
			'fields' => array(
				array(
					'label' => qa_lang('openid/openid_hide_login_registration'),
					'value' => (int)qa_opt('openid_hide_login_registration'),
					'tags' => 'name="openid_hide_login_registration_field"',
					'type' => 'checkbox',
				),
				
				array(
					'label' => qa_lang('openid/openid_facebook_login'),
					'value' => (int)qa_opt('openid_facebook_login'),
					'tags' => 'name="openid_facebook_login_field" id="openid_facebook_login_field"',
					'type' => 'checkbox',
				),
				
				array(
					'id' => 'facebook_app_id_display',
					'label' => qa_lang('openid/openid_facebook_app_id'),
					'value' => qa_html(qa_opt('facebook_app_id')),
					'tags' => 'name="facebook_app_id_field"',
				),

				array(
					'id' => 'facebook_app_secret_display',
					'label' => qa_lang('openid/openid_facebook_app_secret'),
					'value' => qa_html(qa_opt('facebook_app_secret')),
					'tags' => 'name="facebook_app_secret_field"',
				),
				
				array(
					'label' => qa_lang('openid/openid_google_login'),
					'value' => (int)qa_opt('openid_google_login'),
					'tags' => 'name="openid_google_login_field" id="openid_google_login_field"',
					'type' => 'checkbox',
				),
				
				array(
					'id' => 'google_client_id_display',
					'label' => qa_lang('openid/openid_google_client_id'),
					'value' => qa_html(qa_opt('google_client_id')),
					'tags' => 'name="google_client_id_field"',
				),

				array(
					'id' => 'google_client_secret_display',
					'label' => qa_lang('openid/openid_google_client_secret'),
					'value' => qa_html(qa_opt('google_client_secret')),
					'tags' => 'name="google_client_secret_field"',
				),
				
				array(
					'id' => 'google_project_id_display',
					'label' => qa_lang('openid/openid_google_project_id').((strlen(qa_opt('google_project_id')) != 0) ? ' <a href="https://console.developers.google.com/apis/credentials?project=' . qa_opt('google_project_id').'" target="_blank">Edit your project on Google Console</a>' : ''),
					'value' => qa_html(qa_opt('google_project_id')),
					'tags' => 'name="google_project_id_field"',
				),
				
				/*array(
					'label' => qa_lang('openid/openid_linkedin_login'),
					'value' => (int)qa_opt('openid_linkedin_login'),
					'tags' => 'name="openid_linkedin_login_field" id="openid_linkedin_login_field"',
					'type' => 'checkbox',
				),
				
				array(
					'id' => 'linkedin_client_id_display',
					'label' => qa_lang('openid/openid_linkedin_client_id'),
					'value' => qa_html(qa_opt('linkedin_client_id')),
					'tags' => 'name="linkedin_client_id_field"',
				),

				array(
					'id' => 'linkedin_client_secret_display',
					'label' => qa_lang('openid/openid_linkedin_client_secret'),
					'value' => qa_html(qa_opt('linkedin_client_secret')),
					'tags' => 'name="linkedin_client_secret_field"',
				),*/
			),

			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'name="openid_save_button"',
				),
			),
		);
	}
}
