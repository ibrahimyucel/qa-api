<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}
class qa_htmlquiz {
	const PLUGIN						= 'htmlquiz';
	const FIELD_BASE_NAME				= 'htmlquiz';
	const FIELD_COUNT					= 'html_quiz_count';
	const FIELD_COUNT_DFL				= 3;
	const FIELD_COUNT_MAX				= 20;
	const MAXFILE_SIZE					= 'html_quiz_maxfile_size';
	const MAXFILE_SIZE_DFL				= 2097152;
	const ONLY_IMAGE					= 'html_quiz_only_image';
	const ONLY_IMAGE_DFL				= false;	// Can't change true.
	const IMAGE_MAXWIDTH				= 'html_quiz_image_maxwidth';
	const IMAGE_MAXWIDTH_DFL			= 600;
	const IMAGE_MAXHEIGHT				= 'html_quiz_image_maxheight';
	const IMAGE_MAXHEIGHT_DFL			= 600;
	const THUMB_SIZE					= 'html_quiz_thumb_size';
	const THUMB_SIZE_DFL				= 100;
	const LIGHTBOX_EFFECT				= 'html_quiz_lightbox_effect';
	const LIGHTBOX_EFFECT_DFL			= false;	// Can't change true.
	const FIELD_ACTIVE					= 'html_quiz_active';
	const FIELD_ACTIVE_DFL				= false;	// Can't change true.
	const FIELD_PROMPT					= 'html_quiz_prompt';
	const FIELD_NOTE					= 'html_quiz_note';
	const FIELD_NOTE_HEIGHT				= 2;
	const FIELD_TYPE					= 'html_quiz_type';
	const FIELD_TYPE_DFL				= 'text';
	const FIELD_TYPE_TEXT				= 'text';
	const FIELD_TYPE_TEXT_LABEL			= 'html_quiz_type_text';
	const FIELD_TYPE_TEXTAREA			= 'textarea';
	const FIELD_TYPE_TEXTAREA_LABEL		= 'html_quiz_type_textarea';
	const FIELD_TYPE_CHECK				= 'checkbox';
	const FIELD_TYPE_CHECK_LABEL		= 'html_quiz_type_checkbox';
	const FIELD_TYPE_SELECT				= 'select';
	const FIELD_TYPE_SELECT_LABEL		= 'html_quiz_type_select';
	const FIELD_TYPE_RADIO				= 'select-radio';
	const FIELD_TYPE_RADIO_LABEL		= 'html_quiz_type_radio';
	const FIELD_TYPE_FILE				= 'file';
	const FIELD_TYPE_FILE_LABEL			= 'html_quiz_type_file';
	const FIELD_OPTION					= 'html_quiz_option';
	const FIELD_OPTION_HEIGHT			= 2;
	const FIELD_OPTION_ROWS_DFL			= 3;
	const FIELD_OPTION_EXT_ERROR		= 'html_quiz_option_ext_error';
	const FIELD_ATTR					= 'html_quiz_attr';
	const FIELD_DEFAULT					= 'html_quiz_default';
	const FIELD_FORM_POS				= 'html_quiz_form_pos';
	const FIELD_FORM_POS_DFL			= 'content';
	const FIELD_FORM_POS_TOP			= 'top';
	const FIELD_FORM_POS_TOP_LABEL		= 'html_quiz_form_pos_top';
	const FIELD_FORM_POS_CUSTOM			= 'custom';
	const FIELD_FORM_POS_CUSTOM_LABEL	= 'html_quiz_form_pos_custom';
	const FIELD_FORM_POS_TITLE			= 'title';
	const FIELD_FORM_POS_TITLE_LABEL	= 'html_quiz_form_pos_title';
	const FIELD_FORM_POS_CATEGORY		= 'category';
	const FIELD_FORM_POS_CATEGORY_LABEL	= 'html_quiz_form_pos_category';
	const FIELD_FORM_POS_CONTENT		= 'content';
	const FIELD_FORM_POS_CONTENT_LABEL	= 'html_quiz_form_pos_content';
	const FIELD_FORM_POS_EXTRA			= 'extra';
	const FIELD_FORM_POS_EXTRA_LABEL	= 'html_quiz_form_pos_extra';
	const FIELD_FORM_POS_TAGS			= 'tags';
	const FIELD_FORM_POS_TAGS_LABEL		= 'html_quiz_form_pos_tags';
	const FIELD_FORM_POS_NOTIFY			= 'notify';
	const FIELD_FORM_POS_NOTIFY_LABEL	= 'html_quiz_form_pos_notify';
	const FIELD_FORM_POS_BOTTOM			= 'bottom';
	const FIELD_FORM_POS_BOTTOM_LABEL	= 'html_quiz_form_pos_bottom';
	const FIELD_DISPLAY					= 'html_quiz_display';
	const FIELD_DISPLAY_DFL				= false;	// Can't change true.
	const FIELD_LABEL					= 'html_quiz_label';
	const FIELD_PAGE_POS				= 'html_quiz_page_pos';
	const FIELD_PAGE_POS_DFL			= 'below';
	const FIELD_PAGE_POS_UPPER			= 'upper';
	const FIELD_PAGE_POS_UPPER_LABEL	= 'html_quiz_page_pos_upper';
	const FIELD_PAGE_POS_INSIDE			= 'inside';
	const FIELD_PAGE_POS_INSIDE_LABEL	= 'html_quiz_page_pos_inside';
	const FIELD_PAGE_POS_BELOW			= 'below';
	const FIELD_PAGE_POS_BELOW_LABEL	= 'html_quiz_page_pos_below';
	const FIELD_PAGE_POS_HOOK			= '[*attachment^*]';
	const FIELD_HIDE_BLANK				= 'html_quiz_hide_blank';
	const FIELD_HIDE_BLANK_DFL			= false;	// Can't change true.
	const FIELD_REQUIRED				= 'html_quiz_required';
	const FIELD_REQUIRED_DFL			= false;	// Can't change true.
	const SAVE_BUTTON					= 'html_quiz_save_button';
	const DFL_BUTTON					= 'html_quiz_dfl_button';
	const SAVED_MESSAGE					= 'html_quiz_saved_message';
	const RESET_MESSAGE					= 'html_quiz_reset_message';
	
	var $directory;
	var $urltoroot;

	var $html_quiz_count;
	var $html_quiz_maxfile_size;
	var $html_quiz_only_image;
	var $html_quiz_image_maxwidth;
	var $html_quiz_image_maxheight;
	var $html_quiz_thumb_size;
	var $html_quiz_lightbox_effect;
	var $extra_fields;
	var $html_quiz_note_height;
	var $html_quiz_option_height;

	public function __construct() {
		$this->html_quiz_count = self::FIELD_COUNT_DFL;
		$this->html_quiz_maxfile_size = self::MAXFILE_SIZE_DFL;
		$this->html_quiz_only_image = self::ONLY_IMAGE_DFL;
		$this->html_quiz_image_maxwidth = self::IMAGE_MAXWIDTH_DFL;
		$this->html_quiz_image_maxheight = self::IMAGE_MAXHEIGHT_DFL;
		$this->html_quiz_thumb_size = self::THUMB_SIZE_DFL;
		$this->html_quiz_lightbox_effect = self::LIGHTBOX_EFFECT_DFL;
		$this->init_extra_fields($this->html_quiz_count);
		$this->html_quiz_note_height = self::FIELD_NOTE_HEIGHT;
		$this->html_quiz_option_height = self::FIELD_OPTION_HEIGHT;
	}
	
	function init_extra_fields($count) {
		$this->extra_fields = array();
		for($key=1; $key<=$count; $key++) {
			$this->extra_fields[(string)$key] = array(
				'active' => self::FIELD_ACTIVE_DFL,
				'prompt' => qa_lang_html_sub(self::PLUGIN.'/'.self::FIELD_PROMPT.'_default',$key),
				'note' => '',
				'type' => self::FIELD_TYPE_DFL,
				'attr' => '',
				'option' => qa_lang_html_sub(self::PLUGIN.'/'.self::FIELD_OPTION.'_default',$key),
				'default' => qa_lang_html_sub(self::PLUGIN.'/'.self::FIELD_DEFAULT.'_default',$key),
				'form_pos' => self::FIELD_FORM_POS_DFL,
				'display' => self::FIELD_DISPLAY_DFL,
				'label' => qa_lang_html_sub(self::PLUGIN.'/'.self::FIELD_LABEL.'_default',$key),
				'page_pos' => self::FIELD_PAGE_POS_DFL,
				'displayblank' => self::FIELD_HIDE_BLANK_DFL,
				'required' => self::FIELD_REQUIRED_DFL,
			);
		}
	}
	function load_module($directory, $urltoroot) {
		$this->directory=$directory;
		$this->urltoroot=$urltoroot;
	}
	
}
/*
	Omit PHP closing tag to help avoid accidental output
*/