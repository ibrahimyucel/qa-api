<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}
require_once QA_INCLUDE_DIR.'qa-theme-base.php';
require_once QA_INCLUDE_DIR.'qa-app-blobs.php';

class qa_html_theme_layer extends qa_html_theme_base {

	private $extradata;
	private $hq_url;
	
	function __construct($template, $content, $rooturl, $request)
	{		
		global $qa_layers;
		$this->hq_url = $qa_layers['HTML Question Layer']['urltoroot'];
		qa_html_theme_base::qa_html_theme_base($template, $content, $rooturl, $request);
	}
		
	function doctypes() {
		qa_html_theme_base::doctype();
	}
	
	function head_css() {
		qa_html_theme_base::head_css();
		if ($this->request == 'bulkask') {
			$this->output('<link href="' . $this->hq_url . 'hq-style.css" rel="stylesheet" type="text/css">');
		}			
	}
	
	function head_script(){
		qa_html_theme_base::head_script();
		if ($this->request == 'bulkask') {
			$this->output('<script src="' . $this->hq_url . 'hq-script.js"></script>');
		}
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

/*
	Omit PHP closing tag to help avoid accidental output
*/