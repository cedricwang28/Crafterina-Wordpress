<?php

	/**
	 * Configuration settings
	 * Basic Version
	 */

	class WS_Form_Config {

		// Caches
		public static $meta_keys = array();
		public static $field_types = array();
		public static $file_types = false;
		public static $settings_plugin = array();
		public static $settings_form_admin = false;
		public static $frameworks = array();
		public static $parse_variables = array();
		public static $parse_variable_help = array();
		public static $tracking = array();
		public static $ecommerce = false;

		// Get full public or admin config
		public static function get_config($parameters = false, $field_types = false) {

			// Determine if this is an admin or public API request
			$is_admin = (WS_Form_Common::get_query_var('form_is_admin', 'false', $parameters) == 'true');
			$form_id = WS_Form_Common::get_query_var('form_id', 0, $parameters);

			// Standard response
			$config = array();

			// Different for admin or public
			if($is_admin) {

				$config['meta_keys'] = self::get_meta_keys($form_id, false);
				$config['field_types'] = self::get_field_types(false);
				$config['settings_plugin'] = self::get_settings_plugin(false);
				$config['settings_form'] = self::get_settings_form_admin();
				$config['frameworks'] = self::get_frameworks(false);
				$config['parse_variables'] = self::get_parse_variables(false);
				$config['parse_variable_help'] = self::get_parse_variable_help($form_id, false);
				$config['actions'] = WS_Form_Action::get_settings();

			} else {

				$config['meta_keys'] = self::get_meta_keys($form_id, true);
				$config['field_types'] = self::get_field_types_public($field_types);
				$config['settings_plugin'] = self::get_settings_plugin();
				$config['settings_form'] = self::get_settings_form_public();
				$config['frameworks'] = self::get_frameworks();
				$config['parse_variables'] = self::get_parse_variables();
				$config['external'] = self::get_external();
			}

			// Add generic settings (Shared between both admin and public, e.g. language)
			$config['settings_form'] = array_merge_recursive($config['settings_form'], self::get_settings_form(!$is_admin));

			return $config;
		}

		// Attributes

		//	label 					Field type label
		//	label_default 			Default label injected into field when it is created
		//	label_position_force	Force position of the label on this field. This useful if you don't want label positioning to affect the masks for this fied (e.g. input type file)
		//	license 				true = Licensed to use, false = Not licensed to use
		//	required 				Whether or not required functionality applies to this field
		//	fieldsets 				Configuration fieldsets (meta_keys) shown in the sidebar

		//	data_source 			Type and ID of data source linked to this field (e.g. for rendering repeater elements such as options)

		//	mask 						Overall field mask wrapper (Defaults to #field if not specified)

		//	mask_group 					Mask for groups (e.g. <optgroup label="#group_label"#disabled>#group</optgroup>)
		//	mask_group_attributes		Which fields should be rendered as part of the #attributes tag
		//	mask_group_label			Mask for the group label (e.g. #group_label)
		//	mask_group_always 			Should the group mask always be rendered?

		//	mask_row 					Mask for each data grid row (e.g. <option value="#select_field_value"#attributes>#select_field_label</option> or <div#attributes>#row_label</div>)
		//	mask_row 					Mask for placeholder row (e.g. <option value="">#value</option for Select... row)
		//	mask_row_attributes			Which fields should be rendered as part of the #attributes tag
		//	mask_row_label 				Mask for each row label (Typically include #row_field)
		//	mask_row_label_attributes	Attributes to include in row labels
		//	mask_row_field 				Mask for each row field
		//	mask_row_field_attributes	Attributes to include in row labels
		//	mask_row_lookups			Which fields are made available in mask_row (These are lookups in the data)
		//	datagrid_column_value		Name of field that is saved as the submit value
		//	mask_row_default			String to use if a row is marked as default (e.g. ' selected' for a select field)

		//	mask_field 					Mask for the field itself (e.g. <input type="text" id="#id" name="#name" class="#class" value="#value"#attributes />)
		//	mask_field_attributes		Which fields should be rendered as part of the #attributes tag

		//	mask_field_label 			Mask for the field label when rendered (e.g. <label id="#label_id" for="#id" class="#class">#label</label>)
		//	mask_field_label_attributes	Which fields should be rendered as part of the #attributes tag for field labels
		//	mask_field_label_hide_group	Hide labels on groups

		//	mask_help 					Mask for field help (if omitted, falls back to framework mask_help)


		// Submit variables

		//	submit_save 				Should this field be saved to meta data (e.g. html_editor = false)
		//	submit_edit					Can this field be edited once submitted (e.g. signature = false)
		//	submit_edit_type			Override type for editing (e.g. hidden = text)
		//	submit_array				Should this field be treated as array (true for datagrid fields such as select, radio and checkbox)


		// Mask variables

		//	#id 					Field ID
		//	#label 					Field label
		//	#attributes 			Field attributes (attributes that may or may not have a value, e.g. maxlength)
		//	#value 					Field value

		//	#group_id 				Group ID (i.e. Unique to tab index in data grid)
		//	#group_label 			Group label (i.e. Tab name in data grid)

		//	#row_id 				Row ID (e.g. of option in optgroup or datalist)
		//	#row_label 				Render the mask_row_label mask
		//	#row_field 				Render the mask_row_field mask

		//	#data 					Outputs content of data grid (e.g. select optgroup/options or datalist)

		//	logics_enabled			Logics enabled on 'if' condition
		//	actions_enabled			Actions enavbled on 'then'/'else'
		//	condition_event 		Event that will fire a conditional check (space separate multiple events)

		// Configuration - Field Types
		public static function get_field_types($public = true) {

			// Check cache
			if(isset(self::$field_types[$public])) { return self::$field_types[$public]; }

			$field_types = array(

				'basic' => array(

					'label'	=> 'Basic',
					'types' => array(

						'text' => array (

							'label'				=>	__('Text', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('basic'),
							'kb_url'			=>	'/knowledgebase/text/',
							'label_default'		=>	__('Text', 'ws-form'),
							'data_source'		=>	array('type' => 'data_grid', 'id' => 'data_grid_datalist'),
							'submit_save'		=>	true,
							'submit_edit'		=>	true,
							'events'			=>	array(

								'event'				=>	'keyup',
								'event_category'	=>	'Field'
							),

							// Groups
							'mask_group'		=>	"\n\n<datalist id=\"#group_id\">#group</datalist>",
							'mask_group_always'	=> true,

							// Rows
							'mask_row'			=>	'<option value="#datalist_field_value">#datalist_field_text</option>',
							'mask_row_lookups'	=>	array('datalist_field_value', 'datalist_field_text'),
							'datagrid_column_value'	=>	'datalist_field_value',

							// Fields
							'mask_field'					=>	'<input type="text" id="#id" name="#name" value="#value"#attributes />#data#invalid_feedback#help',
							'mask_field_attributes'			=>	array('class', 'disabled', 'readonly', 'required', 'min_length', 'max_length', 'min_length_words', 'max_length_words', 'input_mask', 'placeholder', 'pattern', 'list', 'aria_describedby', 'aria_labelledby', 'aria_label', 'custom_attributes'),
							'mask_field_label'				=>	'<label id="#label_id" for="#id"#attributes>#label</label>',
							'mask_field_label_attributes'	=>	array('class'),

							'fieldsets'	=>	array(

								// Tab: Basic
								'basic'	=>	array(

									'label'		=>	__('Basic', 'ws-form'),
									'meta_keys'	=>	array('label_render', 'label_position', 'label_column_width', 'required', 'hidden', 'default_value', 'placeholder', 'help_count_char_word'),

									'fieldsets'	=>	array(

										array(
											'label'		=>	__('Accessibility', 'ws-form'),
											'meta_keys'	=>	array('aria_label')
										),

										array(
											'label'		=>	__('Exclusions', 'ws-form'),
											'meta_keys'	=>	array('exclude_email')
										)
									)
								),

								// Tab: Advanced
								'advanced'	=>	array(

									'label'	=>	__('Advanced', 'ws-form'),

									'fieldsets'		=>	array(

										array(
											'label'		=>	__('Styles', 'ws-form'),
											'meta_keys'	=>	array('class_single_vertical_align')
										),

										array(
											'label'		=>	__('Classes', 'ws-form'),
											'meta_keys'	=>	array('class_field_wrapper', 'class_field')
										),

										array(
											'label'		=>	__('Restrictions', 'ws-form'),
											'meta_keys'	=>	array('disabled', 'readonly', 'min_length', 'max_length', 'min_length_words', 'max_length_words', 'input_mask', 'pattern')
										),

										array(
											'label'		=>	__('Duplication', 'ws-form'),
											'meta_keys'	=>	array('dedupe','dedupe_message')
										),

										array(
											'label'		=>	__('Validation', 'ws-form'),
											'meta_keys'	=>	array('invalid_feedback_render', 'invalid_feedback')
										),

										array(
											'label'		=>	__('Custom Attributes', 'ws-form'),
											'meta_keys'	=>	array('custom_attributes')
										),
									)
								),

								// Tab: Autocomplete
								'datalist'	=> array(

									'label'		=>	__('Datalist', 'ws-form'),
									'meta_keys'	=> array('data_grid_datalist', 'datalist_field_text', 'datalist_field_value')
								)
							)
						),

						'textarea' => array (

							'label'				=>	__('Text Area', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('basic'),
							'kb_url'			=>	'/knowledgebase/textarea/',
							'label_default'		=>	__('Text Area', 'ws-form'),
							'submit_save'		=>	true,
							'submit_edit'		=>	true,
							'events'			=>	array(

								'event'				=>	'keyup',
								'event_category'	=>	'Field'
							),

							// Fields
							'mask_field'					=>	'<textarea id="#id" name="#name"#attributes>#value</textarea>#invalid_feedback#help',
							'mask_field_attributes'			=>	array('class', 'disabled', 'readonly', 'required', 'min_length', 'max_length', 'min_length_words', 'max_length_words', 'input_mask', 'placeholder', 'spellcheck', 'cols', 'rows', 'aria_describedby', 'aria_labelledby', 'aria_label', 'custom_attributes'),
							'mask_field_label'				=>	'<label id="#label_id" for="#id"#attributes>#label</label>',
							'mask_field_label_attributes'	=>	array('class'),

							'fieldsets'	=>	array(

								// Tab: Basic
								'basic'	=>	array(

									'label'			=>	__('Basic', 'ws-form'),
									'meta_keys'		=>	array('label_render', 'label_position', 'label_column_width', 'required', 'hidden', 'default_value_textarea', 'placeholder', 'help_count_char_word_with_default'),

									'fieldsets'		=>	array(

										array(
											'label'		=>	__('Accessibility', 'ws-form'),
											'meta_keys'	=>	array('aria_label')
										),

										array(
											'label'		=>	__('Exclusions', 'ws-form'),
											'meta_keys'	=>	array('exclude_email')
										)
									)
								),

								// Tab: Advanced
								'advanced'	=>	array(

									'label'			=>	__('Advanced', 'ws-form'),

									'fieldsets'	=>	array(

										array(
											'label'		=>	__('Styles', 'ws-form'),
											'meta_keys'	=>	array('class_single_vertical_align')
										),

										array(
											'label'		=>	__('Classes', 'ws-form'),
											'meta_keys'	=> array('class_field_wrapper', 'class_field')
										),

										array(
											'label'		=>	__('Restrictions', 'ws-form'),
											'meta_keys'	=> array('disabled', 'readonly', 'min_length', 'max_length', 'min_length_words', 'max_length_words', 'input_mask', 'cols', 'rows')
										),

										array(
											'label'		=>	__('Duplication', 'ws-form'),
											'meta_keys'	=>	array('dedupe','dedupe_message')
										),

										array(
											'label'		=>	__('Validation', 'ws-form'),
											'meta_keys'	=>	array('invalid_feedback_render', 'invalid_feedback')
										),

										array(
											'label'		=>	__('Custom Attributes', 'ws-form'),
											'meta_keys'	=>	array('custom_attributes')
										),
									)
								)
							)
						),

						'number' => array (

							'label'				=>	__('Number', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('basic'),
							'kb_url'			=>	'/knowledgebase/number/',
							'label_default'		=>	__('Number', 'ws-form'),
							'data_source'		=>	array('type' => 'data_grid', 'id' => 'data_grid_datalist'),
							'submit_save'		=>	true,
							'submit_edit'		=>	true,
							'compatibility_id'	=>	'input-number',
							'events'			=>	array(

								'event'				=>	'input',
								'event_category'	=>	'Field'
							),

							// Groups
							'mask_group'		=>	"\n\n<datalist id=\"#group_id\">#group</datalist>",
							'mask_group_always'	=> true,

							// Rows
							'mask_row'				=>	'<option value="#datalist_field_value">#datalist_field_text</option>',
							'mask_row_lookups'		=>	array('datalist_field_value', 'datalist_field_text'),
							'datagrid_column_value'	=>	'datalist_field_value',

							// Fields
							'mask_field'									=>	'<input type="number" id="#id" name="#name" value="#value"#attributes />#data#invalid_feedback#help',
							'mask_field_attributes'				=>	array('class', 'list', 'min', 'max', 'step', 'disabled', 'readonly', 'required', 'placeholder', 'aria_describedby', 'aria_labelledby', 'aria_label', 'custom_attributes'),
							'mask_field_label'						=>	'<label id="#label_id" for="#id"#attributes>#label</label>',
							'mask_field_label_attributes'	=>	array('class'),

							'fieldsets'	=> array(

								// Tab: Basic
								'basic'	=> array(

									'label'		=>	__('Basic', 'ws-form'),
									'meta_keys'	=>	array('label_render', 'label_position', 'label_column_width', 'required', 'hidden', 'default_value_number', 'placeholder', 'help'),

									'fieldsets'	=>	array(

										array(
											'label'		=>	__('Accessibility', 'ws-form'),
											'meta_keys'	=>	array('aria_label')
										),

										array(
											'label'		=>	__('Exclusions', 'ws-form'),
											'meta_keys'	=>	array('exclude_email')
										)
									)
								),

								// Tab: Advanced
								'advanced'	=>	array(

									'label'			=>	__('Advanced', 'ws-form'),

									'fieldsets'		=>	array(

										array(
											'label'		=>	__('Styles', 'ws-form'),
											'meta_keys'	=>	array('class_single_vertical_align')
										),

										array(
											'label'		=>	__('Classes', 'ws-form'),
											'meta_keys'	=> array('class_field_wrapper', 'class_field')
										),

										array(
											'label'		=>	__('Restrictions', 'ws-form'),
											'meta_keys'	=> array('disabled', 'readonly', 'min', 'max', 'step')
										),

										array(
											'label'		=>	__('Duplication', 'ws-form'),
											'meta_keys'	=>	array('dedupe','dedupe_message')
										),

										array(
											'label'		=>	__('Validation', 'ws-form'),
											'meta_keys'	=>	array('invalid_feedback_render', 'invalid_feedback')
										),

										array(
											'label'		=>	__('Custom Attributes', 'ws-form'),
											'meta_keys'	=>	array('custom_attributes')
										),
									)
								),

								// Datalist
								'datalist'	=> array(

									'label'			=>	__('Datalist', 'ws-form'),
									'meta_keys'		=> array('data_grid_datalist', 'datalist_field_text', 'datalist_field_value')
								)
							)
						),

						'tel' => array (

							'label'				=>	__('Phone', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('basic'),
							'kb_url'			=>	'/knowledgebase/tel/',
							'label_default'		=>	__('Phone', 'ws-form'),
							'data_source'		=>	array('type' => 'data_grid', 'id' => 'data_grid_datalist'),
							'submit_save'		=>	true,
							'submit_edit'		=>	true,
							'compatibility_id'	=>	'input-email-tel-url',
							'events'			=>	array(

								'event'				=>	'keyup',
								'event_category'	=>	'Field'
							),

							// Groups
							'mask_group'		=>	"\n\n<datalist id=\"#group_id\">#group</datalist>",
							'mask_group_always'	=> true,

							// Rows
							'mask_row'				=>	'<option value="#datalist_field_value">#datalist_field_text</option>',
							'mask_row_lookups'		=>	array('datalist_field_value', 'datalist_field_text'),
							'datagrid_column_value'	=>	'datalist_field_value',

							// Fields
							'mask_field'									=>	'<input type="tel" id="#id" name="#name" value="#value"#attributes />#data#invalid_feedback#help',
							'mask_field_attributes'				=>	array('class', 'disabled', 'readonly', 'min_length', 'max_length', 'pattern_tel', 'list', 'required', 'placeholder', 'aria_describedby', 'aria_labelledby', 'aria_label', 'input_mask', 'custom_attributes'),
							'mask_field_label'						=>	'<label id="#label_id" for="#id"#attributes>#label</label>',
							'mask_field_label_attributes'	=>	array('class'),

							'fieldsets'	=>	array(

								// Tab: Basic
								'basic'	=>	array(

									'label'				=>	__('Basic', 'ws-form'),
									'meta_keys'		=>	array('label_render', 'label_position', 'label_column_width', 'required', 'hidden', 'default_value_tel', 'placeholder', 'help_count_char'),

									'fieldsets'		=>	array(

										array(
											'label'			=>	__('Accessibility', 'ws-form'),
											'meta_keys'	=>	array('aria_label')
										),

										array(
											'label'		=>	__('Exclusions', 'ws-form'),
											'meta_keys'	=>	array('exclude_email')
										)
									)
								),

								// Tab: Advanced
								'advanced'		=>	array(

									'label'			=>	__('Advanced', 'ws-form'),

									'fieldsets'	=>	array(

										array(
											'label'		=>	__('Styles', 'ws-form'),
											'meta_keys'	=>	array('class_single_vertical_align')
										),

										array(
											'label'			=>	__('Classes', 'ws-form'),
											'meta_keys'	=> array('class_field_wrapper', 'class_field')
										),

										array(
											'label'			=>	__('Restrictions', 'ws-form'),
											'meta_keys'	=> array('disabled','readonly', 'min_length', 'max_length', 'input_mask', 'pattern_tel')
										),

										array(
											'label'		=>	__('Duplication', 'ws-form'),
											'meta_keys'	=>	array('dedupe','dedupe_message')
										),

										array(
											'label'			=>	__('Validation', 'ws-form'),
											'meta_keys'	=>	array('invalid_feedback_render', 'invalid_feedback')
										),

										array(
											'label'		=>	__('Custom Attributes', 'ws-form'),
											'meta_keys'	=>	array('custom_attributes')
										),
									)
								),

								// Datalist
								'datalist'	=> array(

									'label'			=>	__('Datalist', 'ws-form'),
									'meta_keys'	=> array('data_grid_datalist', 'datalist_field_text', 'datalist_field_value')
								)
							)
						),

						'email' => array (

							'label'					=>	__('Email', 'ws-form'),
							'pro_required'			=>	!WS_Form_Common::is_edition('basic'),
							'kb_url'				=>	'/knowledgebase/email/',
							'label_default'			=>	__('Email', 'ws-form'),
							'data_source'			=>	array('type' => 'data_grid', 'id' => 'data_grid_datalist'),
							'submit_save'			=>	true,
							'submit_edit'			=>	true,
							'compatibility_id'	=>	'input-email-tel-url',
							'events'				=>	array(

								'event'				=>	'keyup',
								'event_category'	=>	'Field'
							),

							// Groups
							'mask_group'			=>	"\n\n<datalist id=\"#group_id\">#group</datalist>",
							'mask_group_always'		=> true,

							// Rows
							'mask_row'				=>	'<option value="#datalist_field_value">#datalist_field_text</option>',
							'mask_row_lookups'		=>	array('datalist_field_value', 'datalist_field_text'),
							'datagrid_column_value'	=>	'datalist_field_value',

							// Fields
							'mask_field'						=>	'<input type="email" id="#id" name="#name" value="#value"#attributes />#data#invalid_feedback#help',
							'mask_field_attributes'				=>	array('class', 'multiple_email', 'min_length', 'max_length', 'list', 'disabled', 'readonly', 'required', 'placeholder', 'aria_describedby', 'aria_labelledby', 'aria_label', 'custom_attributes'),
							'mask_field_label'					=>	'<label id="#label_id" for="#id"#attributes>#label</label>',
							'mask_field_label_attributes'		=>	array('class'),

							'fieldsets'	=> array(

								// Tab: Basic
								'basic'	=> array(

									'label'		=>	__('Basic', 'ws-form'),
									'meta_keys'	=>	array('label_render', 'label_position', 'label_column_width', 'required', 'hidden', 'default_value_email', 'multiple_email', 'placeholder', 'help_count_char'),

									'fieldsets'	=>	array(

										array(
											'label'		=>	__('Accessibility', 'ws-form'),
											'meta_keys'	=>	array('aria_label')
										),

										array(
											'label'		=>	__('Exclusions', 'ws-form'),
											'meta_keys'	=>	array('exclude_email')
										)
									)
								),

								// Tab: Advanced
								'advanced'	=>	array(

									'label'		=>	__('Advanced', 'ws-form'),

									'fieldsets'	=>	array(

										array(
											'label'		=>	__('Styles', 'ws-form'),
											'meta_keys'	=>	array('class_single_vertical_align')
										),

										array(
											'label'		=>	__('Classes', 'ws-form'),
											'meta_keys'	=> array('class_field_wrapper', 'class_field')
										),

										array(
											'label'		=>	__('Restrictions', 'ws-form'),
											'meta_keys'	=> array('disabled','readonly', 'min_length', 'max_length', 'pattern')
										),

										array(
											'label'		=>	__('Duplication', 'ws-form'),
											'meta_keys'	=>	array('dedupe','dedupe_message')
										),

										array(
											'label'		=>	__('Validation', 'ws-form'),
											'meta_keys'	=>	array('invalid_feedback_render', 'invalid_feedback')
										),

										array(
											'label'		=>	__('Custom Attributes', 'ws-form'),
											'meta_keys'	=>	array('custom_attributes')
										),
									)
								),

								// Datalist
								'datalist'	=> array(

									'label'		=>	__('Datalist', 'ws-form'),
									'meta_keys'	=> array('data_grid_datalist', 'datalist_field_text', 'datalist_field_value')
								)
							)
						),

						'url' => array (

							'label'				=>	__('URL', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('basic'),
							'kb_url'			=>	'/knowledgebase/url/',
							'label_default'		=>	__('URL', 'ws-form'),
							'data_source'		=>	array('type' => 'data_grid', 'id' => 'data_grid_datalist'),
							'submit_save'		=>	true,
							'submit_edit'		=>	true,
							'compatibility_id'	=>	'input-email-tel-url',
							'events'						=>	array(

								'event'						=>	'keyup',
								'event_category'	=>	'Field'
							),

							// Groups
							'mask_group'				=>	"\n\n<datalist id=\"#group_id\">#group</datalist>",
							'mask_group_always'	=> true,

							// Rows
							'mask_row'							=>	'<option value="#datalist_field_value">#datalist_field_text</option>',
							'mask_row_lookups'			=>	array('datalist_field_value', 'datalist_field_text'),
							'datagrid_column_value'	=>	'datalist_field_value',

							// Fields
							'mask_field'									=>	'<input type="url" id="#id" name="#name" value="#value"#attributes />#data#invalid_feedback#help',
							'mask_field_attributes'				=>	array('class', 'min_length', 'max_length', 'list', 'disabled', 'readonly', 'required', 'placeholder', 'aria_describedby', 'aria_labelledby', 'aria_label', 'custom_attributes'),
							'mask_field_label'						=>	'<label id="#label_id" for="#id"#attributes>#label</label>',
							'mask_field_label_attributes'	=>	array('class'),

							'fieldsets'	=> array(

								// Tab: Basic
								'basic'	=> array(

									'label'			=>	__('Basic', 'ws-form'),
									'meta_keys'	=>	array('label_render', 'label_position', 'label_column_width', 'required', 'hidden', 'default_value_url', 'placeholder', 'help_count_char'),

									'fieldsets'	=>	array(

										array(
											'label'			=>	__('Accessibility', 'ws-form'),
											'meta_keys'	=>	array('aria_label')
										),

										array(
											'label'		=>	__('Exclusions', 'ws-form'),
											'meta_keys'	=>	array('exclude_email')
										)
									)
								),

								// Tab: Advanced
								'advanced'	=>	array(

									'label'	=>	__('Advanced', 'ws-form'),

									'fieldsets'	=>	array(

										array(
											'label'		=>	__('Styles', 'ws-form'),
											'meta_keys'	=>	array('class_single_vertical_align')
										),

										array(
											'label'			=>	__('Classes', 'ws-form'),
											'meta_keys'	=> array('class_field_wrapper', 'class_field')
										),

										array(
											'label'			=>	__('Restrictions', 'ws-form'),
											'meta_keys'	=> array('disabled','readonly', 'min_length', 'max_length', 'pattern')
										),

										array(
											'label'		=>	__('Duplication', 'ws-form'),
											'meta_keys'	=>	array('dedupe','dedupe_message')
										),

										array(
											'label'			=>	__('Validation', 'ws-form'),
											'meta_keys'	=>	array('invalid_feedback_render', 'invalid_feedback')
										),

										array(
											'label'		=>	__('Custom Attributes', 'ws-form'),
											'meta_keys'	=>	array('custom_attributes')
										),
									)
								),

								// Datalist
								'datalist'	=> array(

									'label'			=>	__('Datalist', 'ws-form'),
									'meta_keys'	=> array('data_grid_datalist', 'datalist_field_text', 'datalist_field_value')
								)
							)
						),

						'divider' => array (

							'label'					=>	__('Divider', 'ws-form'),
							'pro_required'			=>	!WS_Form_Common::is_edition('basic'),
							'kb_url'				=>	'/knowledgebase/divider/',
							'label_default'			=>	__('Divider', 'ws-form'),
							'mask_field'			=>	'<hr#attributes />',
							'submit_save'			=>	false,
							'submit_edit'			=>	false,
							'label_disabled'			=>	true,

							'fieldsets'	=> array(

								// Tab: Basic
								'basic'	=> array(

									'label'			=>	__('Basic', 'ws-form'),
									'meta_keys'	=>	array('hidden')
								),

								// Tab: Advanced
								'advanced'	=>	array(

									'label'		=>	__('Advanced', 'ws-form'),

									'fieldsets'	=>	array(

										array(
											'label'		=>	__('Styles', 'ws-form'),
											'meta_keys'	=>	array('class_single_vertical_align')
										),

										array(
											'label'			=>	__('Classes', 'ws-form'),
											'meta_keys'	=> array('class_field_wrapper', 'class_field')
										),
									)
								)
							)
						),

						'spacer' => array (

							'label'				=>	__('Spacer', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('basic'),
							'kb_url'			=>	'/knowledgebase/spacer/',
							'label_default'		=>	__('Spacer', 'ws-form'),
							'mask_field'		=>	'',
							'submit_save'		=>	false,
							'submit_edit'		=>	false,
							'label_disabled'	=>	true,

							'fieldsets'			=> array(

								// Tab: Basic
								'basic'	=> array(

									'label'			=>	__('Basic', 'ws-form'),
									'meta_keys'	=>	array('hidden')
								),

								// Tab: Advanced
								'advanced'	=>	array(

									'label'	=>	__('Advanced', 'ws-form'),

									'fieldsets'	=>	array(

										array(
											'label'		=>	__('Classes', 'ws-form'),
											'meta_keys'	=> array('class_field_wrapper')
										),
									)
								)
							)
						)
					)
				),

				'choice' => array(

					'label'	=> 'Choice',
					'types' => array(

						'select' => array (

							'label'				=>	__('Select', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('basic'),
							'kb_url'			=>	'/knowledgebase/select/',
							'label_default'		=>	__('Select', 'ws-form'),
							'data_source'		=>	array('type' => 'data_grid', 'id' => 'data_grid_select'),
							'submit_save'		=>	true,
							'submit_edit'		=>	true,
							'submit_array'		=>	true,
							'events'	=>	array(

								'event'						=>	'change',
								'event_category'			=>	'Field'
							),

							// Groups
							'mask_group'					=>	'<optgroup label="#group_label"#disabled>#group</optgroup>',
							'mask_group_label'				=>	'#group_label',

							// Rows
							'mask_row'						=>	'<option id="#row_id" data-id="#data_id" value="#select_field_value"#attributes>#select_field_label</option>',
							'mask_row_placeholder'			=>	'<option data-id="0" value="" data-placeholder>#value</option>',
							'mask_row_attributes'			=>	array('default', 'disabled'),
							'mask_row_lookups'				=>	array('select_field_value', 'select_field_label', 'select_cascade_field_filter'),
							'datagrid_column_value'			=>	'select_field_value',
							'mask_row_default' 				=>	' selected',

							// Fields
							'mask_field'					=>	'<select id="#id" name="#name"#attributes>#data</select>#invalid_feedback#help',
							'mask_field_attributes'			=>	array('class', 'size', 'multiple', 'required', 'disabled', 'aria_describedby', 'aria_labelledby', 'aria_label', 'custom_attributes'),
							'mask_field_label'				=>	'<label id="#label_id" for="#id"#attributes>#label</label>',
							'mask_field_label_attributes'	=>	array('class'),

							'fieldsets'	=> array(

								// Tab: Basic
								'basic'	=> array(

									'label'			=>	__('Basic', 'ws-form'),
									'meta_keys'		=> array('label_render', 'label_position', 'label_column_width', 'required', 'hidden', 'size', 'multiple', 'placeholder_row', 'help'),

									'fieldsets'		=>	array(

										array(
											'label'		=>	__('Accessibility', 'ws-form'),
											'meta_keys'	=>	array('aria_label')
										),

										array(
											'label'		=>	__('Exclusions', 'ws-form'),
											'meta_keys'	=>	array('exclude_email')
										)
									)
								),

								// Tab: Advanced
								'advanced'	=>	array(

									'label'	=>	__('Advanced', 'ws-form'),

									'fieldsets'	=>	array(

										array(
											'label'		=>	__('Styles', 'ws-form'),
											'meta_keys'	=>	array('class_single_vertical_align')
										),

										array(
											'label'		=>	__('Classes', 'ws-form'),
											'meta_keys'	=> array('class_field_wrapper', 'class_field')
										),

										array(
											'label'		=>	__('Restrictions', 'ws-form'),
											'meta_keys'	=> array('disabled')
										),

										array(
											'label'		=>	__('Validation', 'ws-form'),
											'meta_keys'	=>	array('invalid_feedback_render', 'invalid_feedback')
										),

										array(
											'label'		=>	__('Custom Attributes', 'ws-form'),
											'meta_keys'	=>	array('custom_attributes')
										),
									)
								),

								// Tab: Options
								'options'	=> array(

									'label'			=>	__('Options', 'ws-form'),
									'meta_keys'		=> array('data_grid_select', 'select_field_label', 'select_field_value', 'data_grid_rows_randomize'),
								)
							)
						),

						'checkbox' => array (

							'label'				=>	__('Checkbox', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('basic'),
							'kb_url'			=>	'/knowledgebase/checkbox/',
							'label_default'		=>	__('Checkbox', 'ws-form'),
							'data_source'		=>	array('type' => 'data_grid', 'id' => 'data_grid_checkbox'),
							'submit_save'		=>	true,
							'submit_edit'		=>	true,
							'submit_array'		=>	true,
							'events'	=>	array(

								'event'				=>	'change',
								'event_category'	=>	'Field'
							),

							// Groups
							'mask_group'		=>	'<fieldset#disabled>#group_label#group</fieldset>',
							'mask_group_label'	=>	'<legend#attributes>#group_label</legend>',

							// Rows
							'mask_row'					=>	'<div#attributes>#row_label</div>',
							'mask_row_attributes'		=>	array('class'),
							'mask_row_label'			=>	'<label id="#label_row_id" for="#row_id"#attributes>#row_field#checkbox_field_label#required</label>#invalid_feedback',
							'mask_row_label_attributes'	=>	array('class'),
							'mask_row_field'			=>	'<input type="checkbox" id="#row_id" name="#name" value="#checkbox_field_value"#attributes />',
							'mask_row_field_attributes'	=>	array('class', 'default', 'disabled', 'required', 'aria_labelledby'),
							'mask_row_lookups'			=>	array('checkbox_field_value', 'checkbox_field_label'),
							'datagrid_column_value'		=>	'checkbox_field_value',
							'mask_row_default' 			=>	' checked',

							// Fields
							'mask_field'					=>	'<div#attributes>#data</div>#help',
							'mask_field_label'				=>	'<label id="#label_id"#attributes>#label</label>',
							'mask_field_label_attributes'	=>	array('class'),
//							'mask_field_label_hide_group'	=>	true,

							'fieldsets'	=> array(

								// Tab: Basic
								'basic'	=> array(

									'label'		=>	__('Basic', 'ws-form'),
									'meta_keys'	=>	array('label_render_off', 'label_position', 'label_column_width', 'hidden', 'select_all', 'select_all_label', 'class_inline', 'help'),

									'fieldsets'	=>	array(

										array(
											'label'		=>	__('Accessibility', 'ws-form'),
											'meta_keys'	=>	array('aria_label')
										),

										array(
											'label'		=>	__('Exclusions', 'ws-form'),
											'meta_keys'	=>	array('exclude_email')
										)
									)
								),

								// Tab: Advanced
								'advanced'	=>	array(

									'label'	=>	__('Advanced', 'ws-form'),

									'fieldsets'	=>	array(

										array(
											'label'		=>	__('Styles', 'ws-form'),
											'meta_keys'	=>	array('class_single_vertical_align')
										),

										array(
											'label'		=>	__('Classes', 'ws-form'),
											'meta_keys'	=> array('class_field_wrapper', 'class_field')
										),

										array(
											'label'		=>	__('Validation', 'ws-form'),
											'meta_keys'	=>	array('invalid_feedback_render', 'invalid_feedback')
										),
									)
								),

								// Tab: Checkboxes
								'checkboxes' 	=> array(

									'label'		=>	__('Checkboxes', 'ws-form'),
									'meta_keys'	=> array('data_grid_checkbox', 'checkbox_field_label', 'checkbox_field_value', 'data_grid_rows_randomize')
								)
							)
						),

						'radio' => array (

							'label'				=>	__('Radio', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('basic'),
							'kb_url'			=>	'/knowledgebase/radio/',
							'label_default'		=>	__('Radio', 'ws-form'),
							'data_source'		=>	array('type' => 'data_grid', 'id' => 'data_grid_radio'),
							'submit_save'		=>	true,
							'submit_edit'		=>	true,
							'submit_array'		=>	true,
							'events'	=>	array(

								'event'				=>	'change',
								'event_category'	=>	'Field'
							),

							// Groups
							'mask_group'			=>	'<fieldset#disabled>#group_label#group</fieldset>',
							'mask_group_label'		=>	'<legend#attributes>#group_label</legend>',

							// Rows
							'mask_row'					=>	'<div#attributes>#row_label</div>',
							'mask_row_attributes'		=>	array('class'),
							'mask_row_label'			=>	'<label id="#label_row_id" for="#row_id" data-label-required-id="#label_id"#attributes>#row_field#radio_field_label</label>#invalid_feedback',
							'mask_row_label_attributes'	=>	array('class'),
							'mask_row_field'			=>	'<input type="radio" id="#row_id" name="#name" value="#radio_field_value"#attributes />',
							'mask_row_field_attributes'	=>	array('class', 'default', 'disabled', 'required_row', 'aria_labelledby', 'hidden'),
							'mask_row_lookups'			=>	array('radio_field_value', 'radio_field_label', 'radio_cascade_field_filter'),
							'datagrid_column_value'		=>	'radio_field_value',
							'mask_row_default' 			=>	' checked',

							// Fields
							'mask_field'					=>	'<div#attributes>#data</div>#help',
							'mask_field_attributes'			=>	array('class', 'required_attribute_no'),
							'mask_field_label'				=>	'<label id="#label_id"#attributes>#label</label>',
							'mask_field_label_attributes'	=>	array('class'),
//							'mask_field_label_hide_group'	=>	true,

							'invalid_feedback_last_row'		=> true,

							'fieldsets'	=> array(

								// Tab: Basic
								'basic'	=> array(

									'label'			=>	__('Basic', 'ws-form'),
									'meta_keys'		=>	array('label_render', 'label_position', 'label_column_width', 'required_attribute_no', 'hidden', 'class_inline', 'help'),

									'fieldsets'		=>	array(

										array(
											'label'		=>	__('Accessibility', 'ws-form'),
											'meta_keys'	=>	array('aria_label')
										),

										array(
											'label'		=>	__('Exclusions', 'ws-form'),
											'meta_keys'	=>	array('exclude_email')
										)
									)
								),

								// Tab: Advanced
								'advanced'	=>	array(

									'label'	=>	__('Advanced', 'ws-form'),

									'fieldsets'	=>	array(

										array(
											'label'		=>	__('Styles', 'ws-form'),
											'meta_keys'	=>	array('class_single_vertical_align')
										),

										array(
											'label'		=>	__('Classes', 'ws-form'),
											'meta_keys'	=> array('class_field_wrapper', 'class_field')
										),

										array(
											'label'		=>	__('Validation', 'ws-form'),
											'meta_keys'	=>	array('invalid_feedback_render', 'invalid_feedback')
										),
									)
								),

								// Tab: Radios
								'radios'	=> array(

									'label'		=>	__('Radios', 'ws-form'),
									'meta_keys'	=> array('data_grid_radio', 'radio_field_label', 'radio_field_value', 'data_grid_rows_randomize'),
								)
							)
						),

						'datetime' => array (

							'label'				=>	__('Date/Time', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('pro'),
							'kb_url'			=>	'/knowledgebase/datetime/',
						),

						'range' => array (

							'label'				=>	__('Range Slider', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('pro'),
							'kb_url'			=>	'/knowledgebase/range/',
						),

						'color' => array (

							'label'				=>	__('Color Picker', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('pro'),
							'kb_url'			=>	'/knowledgebase/color/',
						),

						'rating' => array (

							'label'				=>	__('Rating', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('pro'),
							'kb_url'			=>	'/knowledgebase/rating/',
						)
					)
				),

				'advanced' => array(

					'label'	=> 'Advanced',
					'types' => array(

						'file' => array (

							'label'							=>	__('File Upload', 'ws-form'),
							'pro_required'					=>	!WS_Form_Common::is_edition('pro'),
							'kb_url'						=>	'/knowledgebase/file/',
						),

						'hidden' => array (

							'label'						=>	__('Hidden', 'ws-form'),
							'pro_required'				=>	!WS_Form_Common::is_edition('pro'),
							'kb_url'					=>	'/knowledgebase/hidden/',
						),

						'recaptcha' => array (

							'label'							=>	__('reCAPTCHA', 'ws-form'),
							'pro_required'					=>	!WS_Form_Common::is_edition('pro'),
							'kb_url'						=>	'/knowledgebase/recaptcha/',
						),

						'signature' => array (

							'label'								=>	__('Signature', 'ws-form'),
							'pro_required'						=>	!WS_Form_Common::is_edition('pro'),
							'kb_url'							=>	'/knowledgebase/signature/',
						),

						'progress' => array (

							'label'				=>	__('Progress', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('pro'),
							'kb_url'			=>	'/knowledgebase/progress/',
						),

						'password' => array (

							'label'				=>	__('Password', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('pro'),
							'kb_url'			=>	'/knowledgebase/password/',
						),

						'search' => array (

							'label'				=>	__('Search', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('pro'),
							'kb_url'			=>	'/knowledgebase/search/',
						),

						'legal' => array (

							'label'					=>	__('Legal', 'ws-form'),
							'pro_required'			=>	!WS_Form_Common::is_edition('pro'),
							'kb_url'				=>	'/knowledgebase/legal/',
						)
					)
				),

				'content' => array(

					'label'	=> 'Content',
					'types' => array(

						'texteditor' => array (

							'label'					=>	__('Text Editor', 'ws-form'),
							'pro_required'			=>	!WS_Form_Common::is_edition('basic'),
							'kb_url'				=>	'/knowledgebase/texteditor/',
							'label_default'			=>	__('Text Editor', 'ws-form'),
							'mask_field'			=>	'<div#attributes>#value</div>',
							'mask_preview'			=>	'#text_editor',
							'meta_wpautop'			=>	'text_editor',
							'submit_save'			=>	false,
							'submit_edit'			=>	false,
							'static'				=>	'text_editor',

							'fieldsets'				=>	array(

								// Tab: Basic
								'basic'	=>	array(

									'label'		=>	__('Basic', 'ws-form'),
									'meta_keys'	=>	array('hidden', 'text_editor'),

									'fieldsets'		=>	array(

										array(
											'label'		=>	__('Exclusions', 'ws-form'),
											'meta_keys'	=>	array('exclude_email_on')
										)
									)
								),

								// Tab: Advanced
								'advanced'	=>	array(

									'label'		=>	__('Advanced', 'ws-form'),

									'fieldsets'	=>	array(

										array(
											'label'		=>	__('Styles', 'ws-form'),
											'meta_keys'	=>	array('class_single_vertical_align')
										),

										array(
											'label'		=>	__('Classes', 'ws-form'),
											'meta_keys'	=> array('class_field_wrapper')
										),

									)
								)
							)
						),

						'html' => array (

							'label'					=>	__('HTML', 'ws-form'),
							'pro_required'			=>	!WS_Form_Common::is_edition('pro'),
							'kb_url'				=>	'/knowledgebase/html/',
						)
					)
				),

				'buttons' => array(

					'label'	=> 'Buttons',
					'types' => array(

						'submit' => array (

							'label'							=>	__('Submit', 'ws-form'),
							'pro_required'					=>	!WS_Form_Common::is_edition('basic'),
							'kb_url'						=>	'/knowledgebase/submit/',
							'label_default'					=>	__('Submit', 'ws-form'),
							'label_position_force'			=>	'top',
							'mask_field'					=>	'<button type="submit" id="#id" name="#name"#attributes>#label</button>#help',
							'mask_field_attributes'			=>	array('class', 'disabled', 'aria_describedby', 'aria_labelledby', 'aria_label', 'custom_attributes'),
							'mask_field_label'				=>	'#label',
							'submit_save'					=>	false,
							'submit_edit'					=>	false,
							'events'	=>	array(

								'event'				=>	'click',
								'event_category'	=>	'Button'
							),

							'fieldsets'	=> array(

								// Tab: Basic
								'basic'	=> array(

									'label'		=>	__('Basic', 'ws-form'),
									'meta_keys'	=>	array('hidden', 'help'),

									'fieldsets'	=>	array(

										array(
											'label'		=>	__('Accessibility', 'ws-form'),
											'meta_keys'	=>	array('aria_label')
										),
									)
								),

								// Tab: Advanced
								'advanced'	=>	array(

									'label'		=>	__('Advanced', 'ws-form'),

									'fieldsets'	=>	array(

										array(
											'label'		=>	__('Styles', 'ws-form'),
											'meta_keys'	=>	array('class_single_vertical_align_bottom', 'class_field_button_type_primary', 'class_field_full_button_remove')
										),

										array(
											'label'		=>	__('Classes', 'ws-form'),
											'meta_keys'	=>	array('class_field_wrapper', 'class_field')
										),

										array(
											'label'		=>	__('Restrictions', 'ws-form'),
											'meta_keys'	=>	array('disabled')
										),

										array(
											'label'		=>	__('Custom Attributes', 'ws-form'),
											'meta_keys'	=>	array('custom_attributes')
										),
									)
								)
							)
						),

						'save' => array (

							'label'					=>	__('Save', 'ws-form'),
							'pro_required'			=>	!WS_Form_Common::is_edition('pro'),
							'kb_url'				=>	'/knowledgebase/save/',
						),

						'clear' => array (

							'label'					=>	__('Clear', 'ws-form'),
							'pro_required'			=>	!WS_Form_Common::is_edition('pro'),
							'kb_url'				=>	'/knowledgebase/clear/',
						),

						'reset' => array (

							'label'							=>	__('Reset', 'ws-form'),
							'pro_required'					=>	!WS_Form_Common::is_edition('pro'),
							'kb_url'						=>	'/knowledgebase/reset/',
						),

						'tab_previous' => array (

							'label'						=>	__('Previous Tab', 'ws-form'),
							'pro_required'				=>	!WS_Form_Common::is_edition('pro'),
							'kb_url'					=>	'/knowledgebase/tab_previous/',
							'icon'						=>	'previous',
						),

						'tab_next' => array (

							'label'					=>	__('Next Tab', 'ws-form'),
							'pro_required'			=>	!WS_Form_Common::is_edition('pro'),
							'kb_url'				=>	'/knowledgebase/tab_next/',
							'icon'					=>	'next',
						),

						'button' => array (

							'label'						=>	__('Custom', 'ws-form'),
							'pro_required'				=>	!WS_Form_Common::is_edition('pro'),
							'kb_url'					=>	'/knowledgebase/button/',
						)
					)
				),


				'ecommerce' => array(

					'label'	=> 'E-Commerce',
					'types' => array(

						'price' => array (

							'label'				=>	__('Price', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('pro'),
							'icon'				=>	'text',
							'kb_url'			=>	'/knowledgebase/price/',
						),

						'price_select' => array (

							'label'				=>	__('Price Select', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('pro'),
							'icon'				=>	'select',
							'kb_url'			=>	'/knowledgebase/price_select/',
						),

						'price_checkbox' => array (

							'label'				=>	__('Price Checkbox', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('pro'),
							'icon'				=>	'checkbox',
							'kb_url'			=>	'/knowledgebase/price_checkbox/',
							'events'		=>	array(

								'event'					=>	'change',
								'event_category'		=>	'Field'
							),

							// Groups
							'mask_group'				=>	'<fieldset#disabled>#group_label#group</fieldset>',
							'mask_group_label'			=>	'<legend#attributes>#group_label</legend>',

							// Rows
							'mask_row'					=>	'<div#attributes>#row_label</div>',
							'mask_row_attributes'		=>	array('class'),
							'mask_row_label'			=>	'<label id="#label_row_id" for="#row_id"#attributes>#row_field#checkbox_price_field_label#required</label>#invalid_feedback',
							'mask_row_label_attributes'	=>	array('class'),
							'mask_row_field'			=>	'<input type="checkbox" id="#row_id" name="#name" data-price="#checkbox_price_field_value" value="#row_value" data-ecommerce-price#attributes />',
							'mask_row_value'				=>	'#checkbox_price_field_label_html',
							'mask_row_field_attributes'	=>	array('class', 'default', 'disabled', 'required', 'aria_labelledby'),
							'mask_row_lookups'			=>	array('checkbox_price_field_value', 'checkbox_price_field_label'),
							'datagrid_column_value'		=>	'checkbox_price_field_value',
							'mask_row_default' 			=>	' checked',

							// Fields
							'mask_field'				=>	'<div#attributes>#data</div>#help',
							'mask_field_label'				=>	'<label id="#label_id"#attributes>#label</label>',
							'mask_field_label_attributes'	=>	array('class'),
//							'mask_field_label_hide_group'	=>	true,

							'fieldsets'	=> array(

								// Tab: Basic
								'basic'	=> array(

									'label'			=>	__('Basic', 'ws-form'),
									'meta_keys'		=>	array('label_render_off', 'label_position', 'label_column_width', 'hidden', 'select_all', 'select_all_label', 'class_inline', 'help'),

									'fieldsets'	=>	array(

										array(
											'label'		=>	__('Accessibility', 'ws-form'),
											'meta_keys'	=>	array('aria_label')
										),

										array(
											'label'		=>	__('Exclusions', 'ws-form'),
											'meta_keys'	=>	array('exclude_email')
										)
									)
								),

								// Tab: Advanced
								'advanced'	=>	array(

									'label'	=>	__('Advanced', 'ws-form'),

									'fieldsets'	=>	array(

										array(
											'label'		=>	__('Styles', 'ws-form'),
											'meta_keys'	=>	array('class_single_vertical_align')
										),

										array(
											'label'		=>	__('Classes', 'ws-form'),
											'meta_keys'	=> array('class_field_wrapper', 'class_field')
										),

										array(
											'label'		=>	__('Validation', 'ws-form'),
											'meta_keys'	=>	array('invalid_feedback_render', 'invalid_feedback')
										),
									)
								),

								// Tab: Checkboxes
								'checkboxes' 	=> array(

									'label'			=>	__('Products', 'ws-form'),
									'meta_keys'		=> array('data_grid_checkbox_price', 'checkbox_price_field_label', 'checkbox_price_field_value', 'data_grid_rows_randomize')
								)
							)
						),

						'price_radio' => array (

							'label'				=>	__('Price Radio', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('pro'),
							'icon'				=>	'radio',
							'kb_url'			=>	'/knowledgebase/price_radio/',
						),

						'price_range' => array (

							'label'				=>	__('Price Range', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('pro'),
							'icon'				=>	'range',
							'kb_url'			=>	'/knowledgebase/price_range/',
						),

						'quantity' => array (

							'label'				=>	__('Quantity', 'ws-form'),
							'pro_required'		=>	!WS_Form_Common::is_edition('pro'),
							'icon'				=>	'quantity',
							'kb_url'			=>	'/knowledgebase/quantity/',
						),

						'price_subtotal' => array (

							'label'						=>	__('Price Subtotal', 'ws-form'),
							'pro_required'				=>	!WS_Form_Common::is_edition('pro'),
							'icon'						=>	'calculator',
							'kb_url'					=>	'/knowledgebase/price_subtotal/',
						),

						'cart_price' => array (

							'label'					=>	__('Cart Detail', 'ws-form'),
							'pro_required'			=>	!WS_Form_Common::is_edition('pro'),
							'icon'					=>	'price',
							'kb_url'				=>	'/knowledgebase/cart_price/',
						),

						'cart_total' => array (

							'label'					=>	__('Cart Total', 'ws-form'),
							'pro_required'			=>	!WS_Form_Common::is_edition('pro'),
							'icon'					=>	'calculator',
							'kb_url'				=>	'/knowledgebase/cart_total/',
						)
					)
				)
			);

			if(!$public) {

				foreach($field_types as $group_key => $group) {

					$types = $group['types'];

					foreach($types as $field_key => $field_type) {

						// Set icons
						$field_icon = isset($field_type['icon']) ? $field_type['icon'] : $field_key;
						$field_types[$group_key]['types'][$field_key]['icon'] = self::get_icon_16_svg($field_icon);

						// Set compatibility
						if(isset($field_type['compatibility_id'])) {

							$field_types[$group_key]['types'][$field_key]['compatibility_url'] = str_replace('#compatibility_id', $field_type['compatibility_id'], WS_FORM_COMPATIBILITY_MASK);
							unset($field_types[$group_key]['types'][$field_key]['compatibility_id']);
						}
					}
				}
			}

			// Apply filter
			$field_types = apply_filters('wsf_config_field_types', $field_types);

			// Cache
			self::$field_types[$public] = $field_types;

			return $field_types;
		}

		// Configuration - Get field types public
		public static function get_field_types_public($field_types_filter) {

			$field_types = self::get_field_types_flat(true);

			// Filter by fields found in forms
			if($field_types_filter !== false) {

				$field_types_old = $field_types;
				$field_types = array();

				foreach($field_types_filter as $field_type) {

					if(isset($field_types_old[$field_type])) { $field_types[$field_type] = $field_types_old[$field_type]; }
				}
			}

			// Strip attributes
			$public_attributes_strip = array('label' => false, 'label_default' => false, 'submit_edit' => false, 'conditional' => array('logics_enabled', 'actions_enabled'), 'compatibility_id' => false, 'kb_url' => false, 'fieldsets' => false, 'pro_required' => false);

			foreach($field_types as $key => $field) {

				foreach($public_attributes_strip as $attribute_strip => $attributes_strip_sub) {

					if(isset($field_types[$key][$attribute_strip])) {

						if(is_array($attributes_strip_sub)) {

							foreach($attributes_strip_sub as $attribute_strip_sub) {

								if(isset($field_types[$key][$attribute_strip][$attribute_strip_sub])) {

									unset($field_types[$key][$attribute_strip][$attribute_strip_sub]);
								}
							}

						} else {

							unset($field_types[$key][$attribute_strip]);
						}
					}
				}
			}

			return $field_types;
		}

		// Configuration - Field types (Single dimension array)
		public static function get_field_types_flat($public = true) {

			$field_types = array();
			$field_types_config = self::get_field_types($public);

			foreach($field_types_config as $group) {

				$types = $group['types'];

				foreach($types as $key => $field_type) {

					$field_types[$key] = $field_type;
				}
			}

			return $field_types;
		}

		// Configuration - Customize
		public static function get_customize() {

			$customize	=	array(

				'colors'	=>	array(

					'heading'	=>	__('Colors', 'ws-form'),
					'fields'	=>	array(

						'skin_color_default'	=> array(

							'label'			=>	__('Default', 'ws-form'),
							'type'			=>	'color',
							'default'		=>	'#000000',
							'description'	=>	__('Main color used for fonts and borders.')
						),

						'skin_color_default_inverted'	=> array(

							'label'			=>	__('Default Inverted', 'ws-form'),
							'type'			=>	'color',
							'default'		=>	'#FFFFFF',
							'description'	=>	__('A color that contrast the default color.')
						),

						'skin_color_default_light'	=> array(

							'label'			=>	__('Default Light', 'ws-form'),
							'type'			=>	'color',
							'default'		=>	'#8E8E93',
							'description'	=>	__('A light version of the default color.')
						),

						'skin_color_default_lighter'	=> array(

							'label'			=>	__('Default Lighter', 'ws-form'),
							'type'			=>	'color',
							'default'		=>	'#CECED2',
							'description'	=>	__('A lighter version of the default color.')
						),

						'skin_color_default_lightest'	=> array(

							'label'			=>	__('Default Lightest', 'ws-form'),
							'type'			=>	'color',
							'default'		=>	'#EFEFF4',
							'description'	=>	__('The lightest version of the default color.')
						),

						'skin_color_primary'	=> array(

							'label'			=>	__('Primary', 'ws-form'),
							'type'			=>	'color',
							'default'		=>	'#205493',
							'description'	=>	__('Used for primary elements such as a submit button.')
						),

						'skin_color_secondary'	=> array(

							'label'			=>	__('Secondary', 'ws-form'),
							'type'			=>	'color',
							'default'		=>	'#5b616b',
							'description'	=>	__('Used for secondary elements such as a reset button.')
						),

						'skin_color_success'	=> array(

							'label'			=>	__('Success', 'ws-form'),
							'type'			=>	'color',
							'default'		=>	'#2e8540',
							'description'	=>	__('Used for success messages.')
						),

						'skin_color_information'	=> array(

							'label'			=>	__('Information', 'ws-form'),
							'type'			=>	'color',
							'default'		=>	'#02bfe7',
							'description'	=>	__('Used for information messages.')
						),

						'skin_color_warning'	=> array(

							'label'			=>	__('Warning', 'ws-form'),
							'type'			=>	'color',
							'default'		=>	'#fdb81e',
							'description'	=>	__('Used for warning messages.')
						),

						'skin_color_danger'	=> array(

							'label'			=>	__('Danger', 'ws-form'),
							'type'			=>	'color',
							'default'		=>	'#981b1e',
							'description'	=>	__('Used for danger messages.')
						)
					)
				),

				'typography'	=>	array(

					'heading'		=>	__('Typography', 'ws-form'),
					'fields'		=>	array(

						'skin_font_family'	=> array(

							'label'			=>	__('Font Family', 'ws-form'),
							'type'			=>	'text',
							'default'		=>	'inherit',
							'description'	=>	__('Font family used throughout the form.')
						),

						'skin_font_size'	=> array(

							'label'			=>	__('Font Size', 'ws-form'),
							'type'			=>	'number',
							'default'		=>	14,
							'description'	=>	__('Regular font size used on the form.')
						),

						'skin_font_size_large'	=> array(

							'label'			=>	__('Font Size Large', 'ws-form'),
							'type'			=>	'number',
							'default'		=>	16,
							'description'	=>	__('Large font size used on the form.')
						),

						'skin_font_size_small'	=> array(

							'label'			=>	__('Font Size Small', 'ws-form'),
							'type'			=>	'number',
							'default'		=>	12,
							'description'	=>	__('Small font size used on the form.')
						),

						'skin_font_weight'	=> array(

							'label'			=>	__('Font Weight', 'ws-form'),
							'type'			=>	'text',
							'default'		=>	'inherit',
							'description'	=>	__('Font weight used throughout the form.')
						),

						'skin_line_height'	=> array(

							'label'			=>	__('Line Height', 'ws-form'),
							'type'			=>	'number',
							'default'		=>	1.4,
							'description'	=>	__('Line height used throughout form.')
						)
					)
				),

				'borders'	=>	array(

					'heading'		=>	__('Borders', 'ws-form'),
					'fields'		=>	array(

						'skin_border'	=>	array(

							'label'			=>	__('Enabled', 'ws-form'),
							'type'			=>	'checkbox',
							'default'		=>	true,
							'description'	=>	__('When checked, borders will be shown.')
							),

						'skin_border_width'	=> array(

							'label'			=>	__('Width', 'ws-form'),
							'type'			=>	'number',
							'default'		=>	1,
							'description'	=>	__('Specify the width of borders used through the form. For example, borders around form fields.')
						),

						'skin_border_style'	=>	array(

							'label'			=>	__('Style', 'ws-form'),
							'type'			=>	'select',
							'default'		=>	'solid',
							'choices'		=>	array(

								'dashed'	=>	__('Dashed', 'ws-form'),
								'dotted'	=>	__('Dotted', 'ws-form'),
								'double'	=>	__('Double', 'ws-form'),
								'groove'	=>	__('Groove', 'ws-form'),
								'inset'		=>	__('Inset', 'ws-form'),
								'outset'	=>	__('Outset', 'ws-form'),
								'ridge'		=>	__('Ridge', 'ws-form'),
								'solid'		=>	__('Solid', 'ws-form')
							),
							'description'	=>	__('Border style used throughout the form.')
						),

						'skin_border_radius'	=> array(

							'label'			=>	__('Radius', 'ws-form'),
							'type'			=>	'number',
							'default'		=>	4,
							'description'	=>	__('Border radius used throughout the form.')
						)
					)
				),

				'transitions'	=>	array(

					'heading'	=>	__('Transitions', 'ws-form'),
					'fields'	=>	array(

						'skin_transition'	=>	array(

							'label'			=>	__('Enabled', 'ws-form'),
							'type'			=>	'checkbox',
							'default'		=>	true,
							'description'	=>	__('When checked, transitions will be used on the form.')
						),

						'skin_transition_speed'	=> array(

							'label'			=>	__('Speed', 'ws-form'),
							'type'			=>	'number',
							'default'		=>	200,
							'help'			=>	'Value in milliseconds.',
							'description'	=>	__('Transition speed in milliseconds.')
						),

						'skin_transition_timing_function'	=>	array(

							'label'			=>	__('Timing Function', 'ws-form'),
							'type'			=>	'select',
							'default'		=>	'ease-in-out',
							'choices'		=>	array(

								'ease'			=>	__('Ease', 'ws-form'),
								'ease-in'		=>	__('Ease In', 'ws-form'),
								'ease-in-out'	=>	__('Ease In Out', 'ws-form'),
								'ease-out'		=>	__('Ease Out', 'ws-form'),
								'linear'		=>	__('Linear', 'ws-form'),
								'step-end'		=>	__('Step End', 'ws-form'),
								'step-start'	=>	__('Step Start', 'ws-form')
							),
							'description'	=>	__('Speed curve of the transition effect.')
						)
					)
				),

				'advanced'	=>	array(

					'heading'	=>	__('Advanced', 'ws-form'),
					'fields'	=>	array(

						'skin_grid_gutter'	=> array(

							'label'			=>	__('Grid Gutter', 'ws-form'),
							'type'			=>	'number',
							'default'		=>	20,
							'description'	=>	__('Sets the distance between form elements.')
						)
					)
				)
			);

			// Apply filter
			$customize = apply_filters('wsf_config_customize', $customize);

			return $customize;
		}

		// Configuration - Options
		public static function get_options() {

			$options_v_1_0_0 = array(

				// Appearance
				'appearance'		=> array(

					'label'		=>	__('Appearance', 'ws-form'),
					'groups'	=>	array(

						'framework'	=>	array(

							'heading'		=>	__('Framework', 'ws-form'),
							'fields'	=>	array(

								'framework'	=> array(

									'label'				=>	__('Framework', 'ws-form'),
									'type'				=>	'select',
									'help'				=>	__('Framework used for rendering the front-end HTML.', 'ws-form'),
									'options'			=>	array(),	// Populated below
									'default'			=>	WS_FORM_DEFAULT_FRAMEWORK,
									'button'			=>	'wsf-framework-detect',
									'public'			=>	true,
									'data_change'		=>	'reload' 				// Reload settings on change
								)
							)
						),

						'preview'	=>	array(

							'heading'		=>	__('Preview', 'ws-form'),
							'fields'	=>	array(

								'preview_template'	=> array(

									'label'				=>	__('Template', 'ws-form'),
									'type'				=>	'select',
									'help'				=>	__('Page template used for previewing forms.', 'ws-form'),
									'options'			=>	array(),	// Populated below
									'default'			=>	''
								)
							)
						),

						'public'	=>	array(

							'heading'		=>	__('Public', 'ws-form'),
							'fields'	=>	array(

								'css_layout'	=>	array(

									'label'		=>	__('Framework CSS', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('Should the WS Form framework CSS be rendered?', 'ws-form'),
									'default'	=>	true,
									'public'	=>	true,
									'condition'	=>	array('framework' => 'ws-form')
								),

								'css_skin'	=>	array(

									'label'		=>	__('Skin CSS', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	sprintf(__('Should the WS Form skin CSS be rendered? <a href="%s">Click here</a> to customize the WS Form skin.', 'ws-form'), admin_url('customize.php?return=%2Fwp-admin%2Fadmin.php%3Fpage%3Dws-form-settings%26tab%3Dappearance')),
									'default'	=>	true,
									'public'	=>	true,
									'condition'	=>	array('framework' => 'ws-form')
								),

								'css_cache_duration'	=>	array(

									'label'		=>	__('CSS Cache Duration', 'ws-form'),
									'type'		=>	'number',
									'help'		=>	__('Expires header duration in seconds.', 'ws-form'),
									'default'	=>	86400,
									'public'	=>	true,
									'condition'	=>	array('framework' => 'ws-form')
								),

								'comments_html'	=>	array(

									'label'		=>	__('HTML Comments', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('Should form HTML include comments?', 'ws-form'),
									'default'	=>	false,
									'public'	=>	true
								),

								'comments_css'	=>	array(

									'label'		=>	__('CSS Comments', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('Should form CSS include comments?', 'ws-form'),
									'default'	=>	false,
									'public'	=>	true,
									'condition'	=>	array('framework' => 'ws-form')
								)
							)
						)
					)
				),

				// Advanced
				'advanced'	=> array(

					'label'		=>	__('Advanced', 'ws-form'),
					'groups'	=>	array(

						'helpers'	=>	array(

							'heading'	=>	__('Helpers', 'ws-form'),
							'fields'	=>	array(

								'helper_columns'	=>	array(

									'label'		=>	__('Column Guidelines', 'ws-form'),
									'type'		=>	'select',
									'help'		=>	__('Show column guidelines when editing forms?', 'ws-form'),
									'options'	=>	array(

										'off'		=>	array('text' => __('Off', 'ws-form')),
										'resize'	=>	array('text' => __('On resize', 'ws-form')),
										'on'		=>	array('text' => __('Always on', 'ws-form')),
									),
									'default'	=>	'resize'
								),
								'helper_compatibility' => array(

									'label'		=>	__('HTML Compatibility Helpers', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('Render HTML compatibility helper links (Data from', 'ws-form') . ' <a href="' . WS_FORM_COMPATIBILITY_URL . '" target="_blank">' . WS_FORM_COMPATIBILITY_NAME . '</a>).',
									'default'	=>	false,
									'mode'		=>	array(

										'basic'		=>	false,
										'advanced'	=>	true
									)
								),

								'helper_field_help' => array(

									'label'		=>	__('Help Text', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('Render help text in sidebar.'),
									'default'	=>	true
								),

								'helper_section_id'	=> array(

									'label'		=>	__('Section IDs', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('Show IDs on sections?', 'ws-form'),
									'default'	=>	true,
									'mode'		=>	array(

										'basic'		=>	false,
										'advanced'	=>	true
									)
								),

								'helper_field_id'	=> array(

									'label'		=>	__('Field IDs', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('Show IDs on fields? Useful for #field(nnn) variables.', 'ws-form'),
									'default'	=>	true,
									'mode'		=>	array(

										'basic'		=>	false,
										'advanced'	=>	true
									)
								),

								'mode'	=> array(

									'label'		=>	__('Mode', 'ws-form'),
									'type'		=>	'select',
									'help'		=>	__('Selecting advanced mode will enable more features for developers.', 'ws-form'),
									'default'	=>	'basic',
									'options'	=>	array(

										'basic'		=>	array('text' => __('Basic', 'ws-form')),
										'advanced'	=>	array('text' => __('Advanced', 'ws-form'))
									)
								)
							)
						),

						'api'	=>	array(

							'heading'	=>	__('API', 'ws-form'),
							'fields'	=>	array(

								'ajax_nonce' => array(

									'label'		=>	__('Enable nonce', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('Check this setting to enable user specific server side functions, e.g. #user_email. In order for nonce to work efficiently, any WordPress pages containing forms should not be cached or have short cache periods.', 'ws-form'),
									'default'	=>	false,
									'public'	=>	true
								),

								'ajax_http_method_override' => array(

									'label'		=>	__('Use X-HTTP-Method-Override for API Requests', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('Useful if your hosting provider does not support DELETE or PUT methods.', 'ws-form'),
									'default'	=>	true,
									'public'	=>	true
								)
							)
						),

						'stats'	=>	array(

							'heading'	=>	__('Statistics', 'ws-form'),
							'fields'	=>	array(

								'disable_form_stats'			=>	array(

									'label'		=>	__('Disable statistics', 'ws-form'),
									'type'		=>	'checkbox',
									'default'	=>	false,
									'help'		=>	__('If checked, WS Form will stop gathering statistical data about forms.', 'ws-form'),
								),

								'disable_count_submit_unread'	=>	array(

									'label'		=>	__('Disable unread submission bubbles', 'ws-form'),
									'type'		=>	'checkbox',
									'default'	=>	false
								)
							)
						),

						'cookie'	=>	array(

							'heading'	=>	__('Cookies', 'ws-form'),
							'fields'	=>	array(

								'cookie_timeout'	=>	array(

									'label'		=>	__('Cookie Timeout (Seconds)', 'ws-form'),
									'type'		=>	'number',
									'help'		=>	__('Duration in seconds cookies are valid for.', 'ws-form'),
									'default'	=>	60 * 60 * 24,	// 1 day
									'public'	=>	true
								),

								'cookie_prefix'	=>	array(

									'label'		=>	__('Cookie Prefix', 'ws-form'),
									'type'		=>	'text',
									'help'		=>	__('We recommend leaving this value as it is.', 'ws-form'),
									'default'	=>	WS_FORM_IDENTIFIER,
									'public'	=>	true
								)
							)
						),

						'lookup'	=>	array(

							'heading'	=>	__('Lookups', 'ws-form'),
							'fields'	=>	array(

								'ip_lookup_url_mask' => array(

									'label'		=>	__('IP Lookup URL Mask', 'ws-form'),
									'type'		=>	'text',
									'default'	=>	'https://whatismyipaddress.com/ip/#value',
									'help'		=>	__('#value will be replaced with the tracking IP address.', 'ws-form')
								),

								'latlon_lookup_url_mask' => array(

									'label'		=>	__('Geolocation Lookup URL Mask', 'ws-form'),
									'type'		=>	'text',
									'default'	=>	'https://www.google.com/search?q=#value',
									'help'		=>	__('#value will be replaced with latitude,longitude.', 'ws-form')
								)
							)
						),

						'javascript'	=>	array(

							'heading'	=>	__('Javascript', 'ws-form'),
							'fields'	=>	array(

								'jquery_footer'	=>	array(

									'label'		=>	__('Enqueue in Footer', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('If checked, scripts will be enqueued in the footer.', 'ws-form'),
									'default'	=>	''
								),

								'jquery_source'	=>	array(

									'label'		=>	__('jQuery Source', 'ws-form'),
									'type'		=>	'select',
									'help'		=>	__('Where should external libraries load from? Use \'Local\' if you are using optimization plugins.', 'ws-form'),
									'default'	=>	'local',
									'public'	=>	true,
									'options'	=>	array(

										'local'		=>	array('text' => __('Local', 'ws-form')),
										'cdn'		=>	array('text' => __('CDN', 'ws-form'))
									)
								),

							)
						),

						'framework'	=>	array(

							'heading'		=>	__('Framework', 'ws-form'),
							'fields'	=>	array(

								'framework_column_count'	=> array(

									'label'		=>	__('Column Count', 'ws-form'),
									'type'		=>	'select_number',
									'default'	=>	12,
									'minimum'	=>	1,
									'maximum'	=>	24,
									'public'	=>	true,
									'absint'	=>	true,
									'help'		=>	__('We recommend leaving this setting at 12.', 'ws-form')
								)
							)
						),
					)
				),

				// System
				'system'	=> array(

					'label'		=>	__('System', 'ws-form'),
					'fields'	=>	array(

						'system' => array(

							'label'		=>	__('System Report', 'ws-form'),
							'type'		=>	'static'
						),

						'setup'	=> array(

							'type'		=>	'hidden',
							'default'	=>	false
						)
					)
				),
				// Data
				'data'	=> array(

					'label'		=>	__('Data', 'ws-form'),
					'groups'	=>	array(

						'uninstall'	=>	array(

							'heading'	=>	__('Uninstall', 'ws-form'),
							'fields'	=>	array(

								'uninstall_options' => array(

									'label'		=>	__('Delete Plugin Settings on Uninstall', 'ws-form'),
									'type'		=>	'checkbox',
									'default'	=>	false
								),

								'uninstall_database' => array(

									'label'		=>	__('Delete Database Tables on Uninstall', 'ws-form'),
									'type'		=>	'checkbox',
									'default'	=>	false
								)
							)
						)
					)
				)
			);
			$options = $options_v_1_0_0;

			// Frameworks
			$frameworks = self::get_frameworks(false);
			foreach($frameworks['types'] as $key => $framework) {

				$name = $framework['name'];
				$options['appearance']['groups']['framework']['fields']['framework']['options'][$key] = array('text' => $name);
			}

			// Templates
			$options['appearance']['groups']['preview']['fields']['preview_template']['options'][''] = array('text' => __('Automatic', 'ws-form'));

			// Custom page templates
			$page_templates = array();
			$templates_path = get_template_directory();
			$templates = wp_get_theme()->get_page_templates();
			$templates['page.php'] = 'Page';
			$templates['singular.php'] = 'Singular';
			$templates['index.php'] = 'Index';
			$templates['front-page.php'] = 'Front Page';
			$templates['single-post.php'] = 'Single Post';
			$templates['single.php'] = 'Single';
			$templates['home.php'] = 'Home';

			foreach($templates as $template_file => $template_title) {

				// Build template path
				$template_file_full = $templates_path . '/' . $template_file;

				// Skip files that don't exist
				if(!file_exists($template_file_full)) { continue; }

				$page_templates[$template_file] = $template_title . ' (' . $template_file . ')';
			}

			asort($page_templates);

			foreach($page_templates as $template_file => $template_title) {

				$options['appearance']['groups']['preview']['fields']['preview_template']['options'][$template_file] = array('text' => $template_title);
			}

			// Apply filter
			$options = apply_filters('wsf_config_options', $options);

			return $options;
		}

		// Configuration - Settings - Admin
		public static function get_settings_form_admin() {

			// Check cache
			if(self::$settings_form_admin !== false) { return self::$settings_form_admin; }

			$settings_form_admin = array(

				'sidebars'	=> array(

					// Toolbox
					'toolbox'	=> array(

						'label'		=>	__('Toolbox', 'ws-form'),
						'icon'		=>	'tools',
						'buttons'	=>	array(

							array(

								'label' 	=> __('Close', 'ws-form'),
								'action' 	=> 'wsf-sidebar-cancel'
							)
						),
						'static'	=>	true,
						'nav'		=>	true,
						'expand'	=>	false,

						'meta'		=>	array(

							'fieldsets'	=>	array(

								'field-selector'	=>	array(

									'label'		=> __('Fields', 'ws-form'),
									'meta_keys'	=>	array('field_select')
								),

								'form-history'	=>	array(

									'label'		=>	__('Undo', 'ws-form'),
									'meta_keys'	=>	array('form_history')
								)
							)
						)
					),


					// Actions
					'action'	=> array(

						'label'		=>	__('Actions', 'ws-form'),
						'icon'		=>	'actions',
						'buttons'	=>	true,
						'static'	=>	false,
						'nav'		=>	true,
						'expand'	=>	true,
						'kb_url'	=>	'/knowledgebase_category/actions/',

						// When an action is fired...
						'events'	=>	array(

							'submit'	=>	array('label' => __('Form Submitted', 'ws-form'))
						),

						'meta'		=>	array(

							'fieldsets'	=>	array(

								'action'	=>	array(

									'meta_keys'	=>	array('action')
								)
							)
						),

						'actions_pro' => array(

							__('Conversion Tracking (PRO)', 'ws-form'),
							__('Run WordPress Hook (PRO)', 'ws-form'),
							__('Run JavaScript (PRO)', 'ws-form'),
							__('Push to Custom API (PRO)', 'ws-form'),
						)
					),

					// Support
					'support'	=> array(

						'label'		=>	__('Support', 'ws-form'),
						'icon'		=>	'support',
						'buttons'	=>	array(

							array(

								'label' => __('Close', 'ws-form'),
								'action' => 'wsf-sidebar-cancel'
							)
						),
						'static'	=>	true,
						'nav'		=>	true,
						'expand'	=>	true,

						'meta'		=>	array(

							'fieldsets'	=>	array(

								'knowledgebase'	=>	array(

									'label'		=> __('Knowledge Base', 'ws-form'),
									'meta_keys'	=>	array('knowledgebase')
								),

								'contact'		=>	array(

									'label'		=>	__('Contact', 'ws-form'),
									'meta_keys'	=>	array('contact_first_name', 'contact_last_name', 'contact_email', 'contact_inquiry', 'contact_push_form', 'contact_push_system', 'contact_gdpr', 'contact_submit')
								)
							)
						)
					),

					// Form
					'form' => array (

						'label'		=>	__('Settings', 'ws-form'),
						'icon'		=>	'settings',
						'buttons'	=>	true,
						'static'	=>	false,
						'nav'		=>	true,
						'expand'	=>	true,

						'meta' => array (

							'fieldsets'			=> array(

								// Tab: Basic
								'basic'	=> array(

									'label'		=>	__('Basic', 'ws-form'),

									'meta_keys'	=>	array('label_render_off'),

									'fieldsets'	=>	array(
										array(
											'label'			=>	__('Spam Protection', 'ws-form'),
											'meta_keys'	=> array('honeypot', 'spam_threshold')
										)
									)
								),

								// Tab: Advanced
								'advanced'	=> array(

									'label'			=>	__('Advanced', 'ws-form'),

									'fieldsets'	=>	array(

										array(
											'label'		=>	__('Form', 'ws-form'),
											'meta_keys'	=> array('label_mask_form', 'class_form_wrapper')
										),

										array(
											'label'		=>	__('Form Processing', 'ws-form'),
											'meta_keys'	=> array('submit_on_enter', 'submit_lock', 'submit_unlock', 'submit_reload', 'submit_show_errors', 'form_action')
										),

										array(
											'label'		=>	__('Tabs', 'ws-form'),
											'meta_keys'	=> array('cookie_tab_index', 'tab_validation', 'label_mask_group', 'class_group_wrapper')
										),

										array(
											'label'		=>	__('Sections', 'ws-form'),
											'meta_keys'	=> array('label_mask_section', 'class_section_wrapper')
										),

										array(
											'label'		=>	__('Fields', 'ws-form'),
											'meta_keys'	=> array('invalid_field_focus', 'class_field_wrapper', 'class_field', 'label_position_form', 'label_column_width_form', 'label_required', 'label_mask_required')
										)
									)
								),
							),

							// Hidden meta data used to render admin interface
							'hidden'	=> array(

								'meta_keys'	=>	array('breakpoint', 'tab_index', 'action')
							)
						)
					),

					// Groups
					'group' => array(

						'label'		=>	__('Group', 'ws-form'),
						'icon'		=>	'group',
						'buttons'	=>	true,
						'static'	=>	false,
						'nav'			=>	false,
						'expand'	=>	true,

						'meta' => array (

							'fieldsets'			=> array(

								// Tab: Basic
								'basic' 		=> array(

									'label'		=>	__('Basic', 'ws-form'),
									'meta_keys'	=>	array('label_render_off')
								),

								// Tab: Advanced
								'advanced'		=> array(

									'label'		=>	__('Advanced', 'ws-form'),
									'meta_keys'	=>	array('class_group_wrapper')
								)
							)
						)
					),

					// Sections
					'section' => array(

						'label'		=>	__('Section', 'ws-form'),
						'icon'		=>	'section',
						'buttons'	=>	true,
						'static'	=>	false,
						'nav'		=>	false,
						'expand'	=>	true,

						'meta' => array (

							'fieldsets'			=> array(

								// Tab: Basic
								'basic' 		=> array(

									'label'			=>	__('Basic', 'ws-form'),
									'meta_keys'	=>	array('label_render_off', 'hidden_section'),
								),

								// Tab: Advanced
								'advanced'		=> array(

									'label'			=>	__('Advanced', 'ws-form'),
									'fieldsets'	=>	array(

										array(
											'label'			=>	__('Classes', 'ws-form'),
											'meta_keys'	=> array('class_section_wrapper')
										),

										array(
											'label'			=>	__('Restrictions', 'ws-form'),
											'meta_keys'	=> array(

												'disabled_section'
											)
										),
									)
								)
							)
						)
					),

					// Fields
					'field' => array(

						'buttons'	=>	true,
						'static'	=>	false,
						'nav'			=>	false,
						'expand'	=>	true,
					)
				),

				'group' => array(

					'buttons' =>	array(

						array('name' => __('Clone', 'ws-form'), 'method' => 'clone'),
						array('name' => __('Delete', 'ws-form'), 'method' => 'delete'),
						array('name' => __('Edit', 'ws-form'), 'method' => 'edit')
					),
				),

				'section' => array(

					'buttons' =>	array(

						array('name' => __('Clone', 'ws-form'), 'method' => 'clone'),
						array('name' => __('Delete', 'ws-form'), 'method' => 'delete'),
						array('name' => __('Edit', 'ws-form'), 'method' => 'edit')
					),
				),

				'field' => array(

					'buttons' =>	array(

						array('name' => __('Clone', 'ws-form'), 'method' => 'clone'),
						array('name' => __('Delete', 'ws-form'), 'method' => 'delete'),
						array('name' => __('Edit', 'ws-form'), 'method' => 'edit')
					),
				),

				// Data grid
				'data_grid' => array(

					'rows_per_page_options' => array(

						5	=>	'5',
						10	=>	'10',
						25	=>	'25',
						50	=>	'50',
						100	=>	'100',
						150	=>	'150',
						200	=>	'200',
						250	=>	'250',
						500	=>	'500'
					)
				),

				// History
				'history'	=> array(

					'initial'	=> __('Initial form', 'ws-form'),

					'method' 	=> array(

						// All past tense
						'get'				=> __('Read', 'ws-form'),
						'put'				=> __('Updated', 'ws-form'),
						'put_clone'			=> __('Cloned', 'ws-form'),
						'put_resize'		=> __('Resized', 'ws-form'),
						'put_offset'		=> __('Offset', 'ws-form'),
						'put_sort_index'	=> __('Moved', 'ws-form'),
						'put_optimize'		=> __('Optimized', 'ws-form'),
						'put_reset'			=> __('Reset', 'ws-form'),
						'post'				=> __('Added', 'ws-form'),
						'post_upload_json'	=> __('Uploaded', 'ws-form'),
						'delete'			=> __('Deleted', 'ws-form'),
					),

					'object'	=> array(

						'form'		=> __('form', 'ws-form'),
						'group'		=> __('group', 'ws-form'),
						'section'	=> __('section', 'ws-form'),
						'field'		=> __('field', 'ws-form')
					)
				),

				// Icons
				'icons'		=> array(

					'actions'			=> self::get_icon_16_svg('actions'),
					'asterisk'			=> self::get_icon_16_svg('asterisk'),
					'check'				=> self::get_icon_16_svg('check'),
					'close-circle'		=> self::get_icon_16_svg('close-circle'),
					'contract'			=> self::get_icon_16_svg('contract'),
					'default'			=> self::get_icon_16_svg(),
					'disabled'			=> self::get_icon_16_svg('disabled'),
					'download'			=> self::get_icon_16_svg('download'),
					'edit'				=> self::get_icon_16_svg('edit'),
					'expand'			=> self::get_icon_16_svg('expand'),
					'hidden'			=> self::get_icon_16_svg('hidden'),
					'info-circle'		=> self::get_icon_16_svg('info-circle'),
					'first'				=> self::get_icon_16_svg('first'),
					'form'				=> self::get_icon_16_svg('settings'),
					'group'				=> self::get_icon_16_svg('group'),
					'last'				=> self::get_icon_16_svg('last'),
					'markup-circle'		=> self::get_icon_16_svg('markup-circle'),
					'menu'				=> self::get_icon_16_svg('menu'),
					'minus-circle'		=> self::get_icon_16_svg('minus-circle'),
					'next'				=> self::get_icon_16_svg('next'),
					'number'			=> self::get_icon_16_svg('number'),
					'plus'				=> self::get_icon_16_svg('plus'),
					'plus-circle'		=> self::get_icon_16_svg('plus-circle'),
					'previous'			=> self::get_icon_16_svg('previous'),
					'question-circle'	=> self::get_icon_16_svg('question-circle'),
					'readonly'			=> self::get_icon_16_svg('readonly'),
					'redo'				=> self::get_icon_16_svg('redo'),
					'section'			=> self::get_icon_16_svg('section'),
					'settings'			=> self::get_icon_16_svg('settings'),
					'sort'				=> self::get_icon_16_svg('sort'),
					'table'				=> self::get_icon_16_svg('table'),
					'tools'				=> self::get_icon_16_svg('tools'),
					'undo'				=> self::get_icon_16_svg('undo'),
					'upload'			=> self::get_icon_16_svg('upload'),
					'visible'			=> self::get_icon_16_svg('visible'),
					'warning'			=> self::get_icon_16_svg('warning'),
				),

				// Language
				'language'	=> array(

					// Custom
					'custom'		=>	'%s',

					// Objects
					'form'				=>	__('Form', 'ws-form'),
					'forms'				=>	__('Forms', 'ws-form'),
					'group'				=>	__('Tab', 'ws-form'),
					'groups'			=>	__('Tabs', 'ws-form'),
					'section'			=>	__('Section', 'ws-form'),
					'sections'			=>	__('Sections', 'ws-form'),
					'field'				=>	__('Field', 'ws-form'),
					'fields'			=>	__('Fields', 'ws-form'),
					'action'			=>	__('Action', 'ws-form'),
					'actions'			=>	__('Actions', 'ws-form'),
					'submission'		=>	__('Submission', 'ws-form'),
					'id'				=>	__('ID', 'ws-form'),

					// Buttons
					'add_group'			=>	__('Add Tab', 'ws-form'),
					'add_section'		=>	__('Add Section', 'ws-form'),
					'save'				=>	__('Save', 'ws-form'),
					'delete'			=>	__('Delete', 'ws-form'),
					'trash'				=>	__('Trash', 'ws-form'),
					'clone'				=>	__('Clone', 'ws-form'),
					'cancel'			=>	__('Cancel', 'ws-form'),
					'print'				=>	__('Print', 'ws-form'),
					'edit'				=>	__('Edit', 'ws-form'),
					'previous'			=>	__('Previous', 'ws-form'),
					'next'				=>	__('Next', 'ws-form'),
					'repost'			=>	__('Re-Run', 'ws-form'),
					'default'			=>	__('Default', 'ws-form'),
					'variables'			=>	__('Variables', 'ws-form'),
					'select_list'		=>	__('Select From List', 'ws-form'),
					'reset'				=>	__('Reset', 'ws-form'),
					'close'				=>	__('Close', 'ws-form'),

					// Tutorial
					'intro_learn_more'	=>	__('Learn More', 'ws-form'),
					'intro_skip'		=>	__('Skip Tutorial', 'ws-form'),

					// Form statuses
					'draft'				=>	__('Draft', 'ws-form'),
					'publish'			=>	__('Published', 'ws-form'),

					// Uses constants because these are used by the API also
					'default_label_form'		=>	__(WS_FORM_DEFAULT_FORM_NAME, 'ws-form'),
					'default_label_group'		=>	__(WS_FORM_DEFAULT_GROUP_NAME, 'ws-form'),
					'default_label_section'		=>	__(WS_FORM_DEFAULT_SECTION_NAME, 'ws-form'),
					'default_label_field'		=>	__(WS_FORM_DEFAULT_FIELD_NAME, 'ws-form'),

					// Error messages
					'error_field_type_unknown'			=>	__('Unknown field type', 'ws-form'),
					'error_admin_max_width'				=>	__('admin_max_width not defined for breakpoint: %s.', 'ws-form'),
					'error_object'						=>	__('Unable to find object', 'ws-form'),
					'error_object_data'					=>	__('Unable to retrieve object data', 'ws-form'),
					'error_object_meta_value'			=>	__('Unable to retrieve object meta', 'ws-form'),
					'error_object_type'					=>	__('Unable to determine object type', 'ws-form'),
					'error_meta_key'					=>	__('Unknown meta_key: %s', 'ws-form'),
					'error_data_grid'					=>	__('Data grid not specified', 'ws-form'),
					'error_data_grid_groups'			=>	__('Data grid has no groups', 'ws-form'),
					'error_data_grid_default_group'		=>	__('Default group missing in meta type', 'ws-form'),
					'error_data_grid_columns'			=>	__('Data grid has no columns', 'ws-form'),
					'error_data_grid_rows_per_page'		=>	__('Data grid has no rows per page value', 'ws-form'),
					'error_data_grid_csv_no_data'		=>	__('No data to export', 'ws-form'),
					'error_data_grid_row_id'			=>	__('Data grid row has no ID', 'ws-form'),
					'error_timeout_codemirror'			=>	__('Timeout waiting for CodeMirror to load', 'ws-form'),

					// Popover
					'confirm_group_delete'				=>	__('Are you sure you want to delete this tab?', 'ws-form'),
					'confirm_section_delete'			=>	__('Are you sure you want to delete this section?', 'ws-form'),
					'confirm_field_delete'				=>	__('Are you sure you want to delete this field?', 'ws-form'),
					'confirm_action_repost'				=>	__('Are you sure you want to re-run this action?', 'ws-form'),
					'confirm_submit_delete'				=>	__('Are you sure you want to trash this submission', 'ws-form'),

					// Blanks
					'blank_section'						=>	__('Drag a section here', 'ws-form'),
					'blank_field'						=>	__('Drag a field here', 'ws-form'),

					// Compatibility
					'attribute_compatibility'			=>	__('Compatibility', 'ws-form'),
					'field_compatibility'				=>	__('Compatibility', 'ws-form'),
					'field_kb_url'						=>	__('Knowledge Base', 'ws-form'),

					// CSV upload
					'data_grid_upload_csv'				=>	__('Drop file to upload', 'ws-form'),
					'form_upload_json'					=>	__('Drop file to upload', 'ws-form'),

					// Data grids - Groups
					'data_grid_settings'				=>	__('Settings', 'ws-form'),
					'data_grid_groups_label'			=>	__('Label', 'ws-form'),
					'data_grid_groups_label_render'		=>	__('Render Label', 'ws-form'),
					'data_grid_group_add'				=>	__('Add Group', 'ws-form'),
					'data_grid_group_label_default'		=>	__('Group', 'ws-form'),
					'data_grid_group_auto_group'		=>	__('Auto Group By', 'ws-form'),
					'data_grid_group_auto_group_select'	=>	__('Select...', 'ws-form'),
					'data_grid_group_disabled'			=>	__('Disabled', 'ws-form'),
					'data_grid_groups_group'			=>	__('Group These Values', 'ws-form'),
					'data_grid_group_delete'			=>	__('Delete Group', 'ws-form'),
					'data_grid_group_delete_confirm'	=>	__('Are you sure you want to delete this group?', 'ws-form'),

					// Data grids - Columns
					'data_grid_column_add'				=>	__('Add Column', 'ws-form'),
					'data_grid_column_label_default'	=>	__('New Column', 'ws-form'),
					'data_grid_column_delete'			=>	__('Delete Column', 'ws-form'),
					'data_grid_column_delete_confirm'	=>	__('Are you sure you want to delete this column?', 'ws-form'),

					// Data grids - Rows
					'data_grid_row_add'					=>	__('Add Row', 'ws-form'),
					'data_grid_row_sort'				=>	__('Sort Row', 'ws-form'),
					'data_grid_row_delete'				=>	__('Delete Row', 'ws-form'),
					'data_grid_row_delete_confirm'		=>	__('Are you sure you want to delete this row?', 'ws-form'),
					'data_grid_row_bulk_actions'		=>	__('Bulk Actions', 'ws-form'),
					'data_grid_row_default'				=>	__('Selected', 'ws-form'),
					'data_grid_row_required'			=>	__('Required', 'ws-form'),
					'data_grid_row_disabled'			=>	__('Disabled', 'ws-form'),
					'data_grid_row_hidden'				=>	__('Hidden', 'ws-form'),

					// Data grids - Bulk actions
					'data_grid_row_bulk_actions_select'			=>	__('Select...', 'ws-form'),
					'data_grid_row_bulk_actions_delete'			=>	__('Delete', 'ws-form'),
					'data_grid_row_bulk_actions_default'		=>	__('Set Default', 'ws-form'),
					'data_grid_row_bulk_actions_default_off'	=>	__('Set Not Default', 'ws-form'),
					'data_grid_row_bulk_actions_required'		=>	__('Set Required', 'ws-form'),
					'data_grid_row_bulk_actions_required_off'	=>	__('Set Not Required', 'ws-form'),
					'data_grid_row_bulk_actions_disabled'		=>	__('Set Disabled', 'ws-form'),
					'data_grid_row_bulk_actions_disabled_off'	=>	__('Set Not Disabled', 'ws-form'),
					'data_grid_row_bulk_actions_hidden'			=>	__('Set Hidden', 'ws-form'),
					'data_grid_row_bulk_actions_hidden_off'		=>	__('Set Not Hidden', 'ws-form'),
					'data_grid_row_bulk_actions_apply'			=>	__('Apply', 'ws-form'),

					// Data grids - Rows per page
					'data_grid_rows_per_page'				=>	__('Rows Per Page', 'ws-form'),
					'data_grid_rows_per_page_0'				=>	__('Show All', 'ws-form'),
					'data_grid_rows_per_page_apply'			=>	__('Apply', 'ws-form'),

					// Data grids - Upload
					'data_grid_group_upload_csv'			=>	__('Upload CSV', 'ws-form'),

					// Data grids - Download
					'data_grid_group_download_csv'			=>	__('Download CSV', 'ws-form'),

					// Data grids - Actions
					'data_grid_action_edit'					=>	__('Edit', 'ws-form'),
					'data_grid_action_action'				=>	__('Action', 'ws-form'),
					'data_grid_action_event'				=>	__('When should this action run?', 'ws-form'),
					'data_grid_action_event_conditional'	=>	sprintf(__('<a href="%s" target="_blank">Upgrade to PRO</a> for more actions and the ability to run actions using conditional logic.', 'ws-form'), WS_Form_Common::get_plugin_website_url('', 'siderbar_action')),


					// Data grids - Actions
					'data_grid_action_edit'					=>	__('Edit', 'ws-form'),

					// Repeaters
					'repeater_row_add'						=>	__('Add Row', 'ws-form'),
					'repeater_row_delete'					=>	__('Delete Row', 'ws-form'),


					// Sidebar titles
					'sidebar_title_form'					=>	__('Form', 'ws-form'),
					'sidebar_title_group'					=>	__('Tab', 'ws-form'),
					'sidebar_title_section'					=>	__('Section', 'ws-form'),
					'sidebar_title_history'					=>	__('History', 'ws-form'),
					'sidebar_button_image'					=>	__('Select', 'ws-form'),

					// Sidebar - Expand / Contract
					'data_sidebar_expand'					=>	__('Expand', 'ws-form'),
					'data_sidebar_contract'					=>	__('Contract', 'ws-form'),

					// Actions
					'action_label_default'					=>	__('New action', 'ws-form'),

					'column_size_change'						=>	__('Change column size', 'ws-form'),
					'offset_change'								=>	__('Change offset', 'ws-form'),

					// Submit
					'submit_status'								=>	__('Status', 'ws-form'),
					'submit_preview'							=>	__('Preview', 'ws-form'),
					'submit_date_added'							=>	__('Added', 'ws-form'),
					'submit_date_updated'						=>	__('Updated', 'ws-form'),
					'submit_user'								=>	__('User', 'ws-form'),
					'submit_status'								=>	__('Status', 'ws-form'),
					'submit_duration'							=>	__('Duration', 'ws-form'),
					'submit_duration_days'						=>	__('Days', 'ws-form'),
					'submit_duration_hours'						=>	__('Hours', 'ws-form'),
					'submit_duration_minutes'					=>	__('Minutes', 'ws-form'),
					'submit_duration_seconds'					=>	__('Seconds', 'ws-form'),
					'submit_tracking'							=>	__('Tracking', 'ws-form'),
					'submit_tracking_geo_location_permission_denied'	=>	__('User denied the request for geo location.', 'ws-form'),
					'submit_tracking_geo_location_position_unavailable'	=>	__('Geo location information was unavailable.', 'ws-form'),
					'submit_tracking_geo_location_timeout'				=>	__('The request to get user geo location timed out.', 'ws-form'),
					'submit_tracking_geo_location_default'				=>	__('An unknown error occurred whilst retrieving geo location.', 'ws-form'),
					'submit_actions'							=>	__('Actions', 'ws-form'),
					'submit_actions_column_index'				=>	'#',
					'submit_actions_column_action'				=>	__('Action', 'ws-form'),
					'submit_actions_column_meta_label'			=>	__('Setting', 'ws-form'),
					'submit_actions_column_meta_value'			=>	__('Value', 'ws-form'),
					'submit_actions_column_logs'				=>	__('Log', 'ws-form'),
					'submit_actions_column_errors'				=>	__('Error', 'ws-form'),
					'submit_actions_repost'						=>	__('Run Again', 'ws-form'),
					'submit_actions_meta'						=>	__('Settings', 'ws-form'),
					'submit_actions_logs'						=>	__('Logs', 'ws-form'),
					'submit_actions_errors'						=>	__('Errors', 'ws-form'),
					'submit_action_logs'						=>	__('Action Logs', 'ws-form'),
					'submit_action_errors'						=>	__('Action Errors', 'ws-form'),
					'submit_ecommerce'							=>	__('E-Commerce', 'ws-form'),
					'submit_encrypted'							=>	__('Encrypted', 'ws-form'),

					// Add form
					'form_add_create'		=>	__('Create', 'ws-form'),
					'form_import_confirm'	=>	__("Are you sure you want to import this file?\n\nImporting a form file will overwrite the existing form and create new field ID's.\n\nIt is not recommended that you use this feature for forms that are in use on your website.", 'ws-form'),

					// Sidebar - Expand / Contract
					'sidebar_expand'	=>	__('Expand', 'ws-form'),
					'sidebar_contract'	=>	__('Contract', 'ws-form'),

					// Knowledge Base
					'knowledgebase_search_label'		=>	__('Enter keyword(s) to search', 'ws-form'),
					'knowledgebase_search_button'		=>	__('Search', 'ws-form'),
					'knowledgebase_search_placeholder'	=>	__('Keyword(s)', 'ws-form'),
					'knowledgebase_popular'				=>	__('Popular Articles', 'ws-form'),

					// Contact
					'support_contact_thank_you'			=>	__('Thank you for your inquiry.', 'ws-form'),
					'support_contact_error'				=>	__('An error occurred when submitting your support inquiry. Please email support@wsform.com (%s)', 'ws-form'),

					// Starred
					'starred_on'						=>	__('Starred', 'ws-form'),
					'starred_off'						=>	__('Not Starred', 'ws-form'),

					// Viewed
					'viewed_on'							=>	__('Mark as Unread', 'ws-form'),
					'viewed_off'						=>	__('Mark as Read', 'ws-form'),

					// Form location
					'form_location_not_found'			=>	__('Form not found in content', 'ws-form'),

					// Shortcode copy
					'shortcode_copied'					=>	__('Shortcode copied', 'ws-form'),

					// API - List subs
					'list_subs_call'		=>	__('Retrieving...', 'ws-form'),
					'list_subs_select'		=>	__('Select...', 'ws-form')
				)
			);

			// Set icons
			foreach($settings_form_admin['group']['buttons'] as $key => $buttons) {

				$method = $buttons['method'];
				$settings_form_admin['group']['buttons'][$key]['icon'] = self::get_icon_16_svg($method);
			}
			foreach($settings_form_admin['section']['buttons'] as $key => $buttons) {

				$method = $buttons['method'];
				$settings_form_admin['section']['buttons'][$key]['icon'] = self::get_icon_16_svg($method);
			}
			foreach($settings_form_admin['field']['buttons'] as $key => $buttons) {

				$method = $buttons['method'];
				$settings_form_admin['field']['buttons'][$key]['icon'] = self::get_icon_16_svg($method);
			}

			// Apply filter
			$settings_form_admin = apply_filters('wsf_config_settings_form_admin', $settings_form_admin);

			// Cache
			self::$settings_form_admin = $settings_form_admin;

			return $settings_form_admin;
		}

		// Configuration - Settings - Public
		public static function get_settings_form_public() {

			$settings_form_public = array();


			// Apply filter
			$settings_form_public = apply_filters('wsf_config_settings_form_public', $settings_form_public);

			return $settings_form_public;
		}

		// Configuration - Settings (Shared with admin and public)
		public static function get_settings_form($public = true) {

			$settings_form = array(

				// Language
				'language'	=> array(

					// Errors
					'error_attributes'					=>	__('No attributes specified', 'ws-form'),
					'error_attributes_obj'				=>	__('No attributes object specified', 'ws-form'),
					'error_attributes_form_id'			=>	__('No attributes form ID specified', 'ws-form'),
					'error_form_id'						=>	__('Form ID not specified', 'ws-form'),
					'error_bad_request'					=>	__('400 Bad request response from server', 'ws-form'),
					'error_bad_request_message'			=>	__('400 Bad request response from server: %s', 'ws-form'),
					'error_forbidden'					=>	__('403 Forbidden response from server. <a href="https://wsform.com/knowledgebase/403-forbidden/" target="_blank">Learn more</a>.', 'ws-form'),
					'error_not_found'					=>	__('404 Not found response from server', 'ws-form'),
					'error_server'						=>	__('500 Server error response from server', 'ws-form'),
					'error_pro_required'				=>	__('WS Form PRO required', 'ws-form'),

					// Error message
					'dismiss'							=>  __('Dismiss', 'ws-form'),

					// Comments
					'comment_group_tabs'				=>	__('Tabs', 'ws-form'),
					'comment_groups'					=>	__('Tabs Content', 'ws-form'),
					'comment_group'						=>	__('Tab', 'ws-form'),
					'comment_sections'					=>	__('Sections', 'ws-form'),
					'comment_section'					=>	__('Section', 'ws-form'),
					'comment_fields'					=>	__('Fields', 'ws-form'),
					'comment_field'						=>	__('Field', 'ws-form'),

					// Word and character counts
					'character_singular'				=>	__('character', 'ws-form'),
					'character_plural'					=>	__('characters', 'ws-form'),
					'word_singular'						=>	__('word', 'ws-form'),
					'word_plural'						=>	__('words', 'ws-form'),

					// Select all
					'select_all_label'					=>	__('Select All', 'ws-form'),
				)
			);

			// Apply filter
			$settings_form = apply_filters('wsf_config_settings_form', $settings_form);

			return $settings_form;
		}

		// Get plug-in settings
		public static function get_settings_plugin($public = true) {

			// Check cache
			if(isset(self::$settings_plugin[$public])) { return self::$settings_plugin[$public]; }

			$settings_plugin = [];

			// Plugin options
			$options = self::get_options();

			// Set up options with default values
			foreach($options as $tab => $data) {

				if(isset($data['fields'])) {

					self::get_settings_plugin_process($data['fields'], $public, $settings_plugin);
				}

				if(isset($data['groups'])) {

					$groups = $data['groups'];

					foreach($groups as $group) {

						self::get_settings_plugin_process($group['fields'], $public, $settings_plugin);
					}
				}
			}

			// Apply filter
			$settings_plugin = apply_filters('wsf_config_settings_plugin', $settings_plugin);

			// Cache
			self::$settings_plugin[$public] = $settings_plugin;

			return $settings_plugin;
		}

		// Get plug-in settings process
		public static function get_settings_plugin_process($fields, $public, &$settings_plugin) {

			foreach($fields as $field => $attributes) {

				// Skip field if public only?
				$field_skip = false;
				if($public) {

					$field_skip = !isset($attributes['public']) || !$attributes['public'];
				}
				if($field_skip) { continue; }

				// Get default value (if available)
				if(isset($attributes['default'])) { $default_value = $attributes['default']; } else { $default_value = ''; }

				// Get option value
				$settings_plugin[$field] = WS_Form_Common::option_get($field, $default_value);
			}
		}

		// Configuration - Meta Keys
		public static function get_meta_keys($form_id = 0, $public = false) {

			// Check cache
			if(isset(self::$meta_keys[$public])) { return self::$meta_keys[$public]; }

			$label_position = array(

				array('value' => 'top', 'text' => __('Top', 'ws-form')),
				array('value' => 'right', 'text' => __('Right', 'ws-form')),
				array('value' => 'bottom', 'text' => __('Bottom', 'ws-form')),
				array('value' => 'left', 'text' => __('Left', 'ws-form'))
			);

			$button_types = array(

				array('value' => 'default', 	'text' => __('Default', 'ws-form')),
				array('value' => 'primary', 	'text' => __('Primary', 'ws-form')),
				array('value' => 'secondary', 	'text' => __('Secondary', 'ws-form')),
				array('value' => 'success', 	'text' => __('Success', 'ws-form')),
				array('value' => 'information', 'text' => __('Information', 'ws-form')),
				array('value' => 'warning', 	'text' => __('Warning', 'ws-form')),
				array('value' => 'danger', 		'text' => __('Danger', 'ws-form'))
			);

			$vertical_align = array(

				array('value' => '', 'text' => __('Top', 'ws-form')),
				array('value' => 'middle', 'text' => __('Middle', 'ws-form')),
				array('value' => 'bottom', 'text' => __('Bottom', 'ws-form'))
			);

			$meta_keys = array(

				// Forms

				// Should tabs be remembered?
				'cookie_tab_index'		=>	array(

					'label'		=>	__('Remember Last Tab Clicked', 'ws-form'),
					'type'		=>	'checkbox',
					'help'		=>	__('Should the last tab clicked be remembered?', 'ws-form'),
					'default'	=>	true
				),

				'tab_validation'		=>	array(

					'label'		=>	__('Tab Validation', 'ws-form'),
					'type'		=>	'checkbox',
					'help'		=>	__('Prevent the user from advancing to the next tab until the current tab is validated.', 'ws-form'),
					'default'	=>	false
				),

				// Add HTML to required labels
				'label_required'		=>	array(

					'label'		=>	__("Render 'Required' HTML", 'ws-form'),
					'type'		=>	'checkbox',
					'default'	=>	true,
					'help'		=>	__("Should '*' be added to labels if a field is required?", 'ws-form')
				),

				// Add HTML to required labels
				'label_mask_required'	=>	array(

					'label'			=>	__("Custom 'Required' HTML", 'ws-form'),
					'type'			=>	'text',
					'default'		=>	'',
					'help'			=>	__('Example: &apos; &lt;small&gt;Required&lt;/small&gt;&apos;.', 'ws-form'),
					'select_list'				=>	array(

						array('text' => __('&lt;small&gt;Required&lt;/small&gt;', 'ws-form'), 'value' => ' <small>Required</small>')
					),
					'condition'					=>	array(

						array(

							'logic'			=>	'==',
							'meta_key'		=>	'label_required',
							'meta_value'	=>	'on'
						)
					)
				),

				// Hidden
				'hidden'		=>	array(

					'label'						=>	__('Hidden', 'ws-form'),
					'mask'						=>	'data-hidden',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'checkbox',
					'default'					=>	'',
					'data_change'				=>	array('event' => 'change', 'action' => 'update')
				),

				'hidden_section'				=> array(

					'label'						=>	__('Hidden', 'ws-form'),
					'mask'						=>	'data-hidden',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'checkbox',
					'default'					=>	'',
					'data_change'				=>	array('event' => 'change', 'action' => 'update')
				),


				// Spam Protection - Honeypot
				'honeypot'		=> array(

					'label'						=>	__('HoneyPot', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'',
					'help'						=>	__('Adds a hidden field to fool spammers.', 'ws-form'),
				),

				// Spam Protection - Threshold
				'spam_threshold'	=> array(

					'label'						=>	__('Spam Threshold', 'ws-form'),
					'type'						=>	'range',
					'default'					=>	50,
					'min'						=>	0,
					'max'						=>	100,
					'help'						=>	__('If your form is configured to check for spam (e.g. Akismet or reCAPTCHA), each submission will be given a score between 0 (Not spam) and 100 (Blatant spam). Use this setting to determine the minimum score that will move a submission into the spam folder.', 'ws-form'),
				),

				// Duplicate Protection - Lock submit
				'submit_lock'		=> array(

					'label'						=>	__('Lock Save &amp; Submit Buttons', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'on',
					'help'						=>	__('Lock save and submit buttons when form is saved or submitted so that they cannot be double clicked.', 'ws-form')
				),

				// Duplicate Protection - Lock submit
				'submit_unlock'		=> array(

					'label'						=>	__('Unlock Save &amp; Submit Buttons', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'on',
					'help'						=>	__('Unlock save and submit buttons after form is saved or submitted.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'submit_lock',
							'meta_value'		=>	'on'
						)
					)
				),

				// Focus on invalid fields
				'invalid_field_focus'		=> array(

					'label'						=>	__('Focus Invalid Fields', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'on',
					'help'						=>	__('On form submit, should a field be focussed?', 'ws-form')
				),
				// Submit on enter
				'submit_on_enter'	=> array(

					'label'						=>	__('Enable Form Submit On Enter', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'',
					'help'						=>	__('Allow the form to be submitted if someone types Enter/Return. Not advised for e-commerce forms.', 'ws-form')
				),

				// Reload on submit
				'submit_reload'		=> array(

					'label'						=>	__('Reset Form After Submit', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'on',
					'help'						=>	__('Should the form be reset to its default state after it is submitted?', 'ws-form')
				),

				// Form action
				'form_action'		=> array(

					'label'						=>	__('Custom Form Action', 'ws-form'),
					'type'						=>	'text',
					'default'					=>	'',
					'help'						=>	__('Enter a custom action for this form. Leave blank to use WS Form (Recommended).', 'ws-form')
				),

				// Show errors on submit
				'submit_show_errors'			=> array(

					'label'						=>	__('Show Error Messages', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'on',
					'help'						=>	__('If a server side error occurs when a form is submitted, should WS Form show those as form error messages?', 'ws-form')
				),

				// Render label checkbox (On by default)
				'label_render'					=> array(

					'label'						=>	__('Render Label', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'on'
				),

				// Render label checkbox (Off by default)
				'label_render_off'				=> array(

					'label'						=>	__('Render Label', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'',
					'key'						=>	'label_render'
				),

				// Label position (Form)
				'label_position_form'			=> array(

					'label'						=>	__('Default Label Position', 'ws-form'),
					'type'						=>	'select',
					'help'						=>	__('Select the default position of field labels.', 'ws-form'),
					'options'					=>	$label_position,
					'options_framework_filter'	=>	'label_positions',
					'default'					=>	'top'
				),

				// Label position
				'label_position'		=> array(

					'label'						=>	__('Label Position', 'ws-form'),
					'type'						=>	'select',
					'help'						=>	__('Select the position of the field label.', 'ws-form'),
					'options'					=>	$label_position,
					'options_default'			=>	'label_position_form',
					'options_framework_filter'	=>	'label_positions',
					'default'					=>	'default',
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'label_render',
							'meta_value'		=>	'on'
						)
					)
				),

				// Label column width
				'label_column_width_form'				=> array(

					'label'						=>	__('Default Label Width (Columns)', 'ws-form'),
					'type'						=>	'select_number',
					'default'					=>	3,
					'minimum'					=>	1,
					'maximum'					=>	'framework_column_count',
					'help'						=>	__('Column width of labels if positioned left or right.', 'ws-form')
				),

				// Label column width
				'label_column_width'				=> array(

					'label'						=>	__('Label Width (Columns)', 'ws-form'),
					'type'						=>	'select_number',
					'options_default'			=>	'label_column_width_form',
					'default'					=>	'default',
					'minimum'					=>	1,
					'maximum'					=>	'framework_column_count',
					'help'						=>	__('Column width of label.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'label_position',
							'meta_value'		=>	'left'
						),

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'label_position',
							'meta_value'		=>	'right',
							'logic_previous'	=>	'||'
						)
					)
				),


				'class_field_full_button_remove'	=> array(

					'label'						=>	__('Remove Full Width Class', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	''
				),

				'class_field_button_type'			=> array(

					'label'						=>	__('Type', 'ws-form'),
					'type'						=>	'select',
					'default'					=>	'default',
					'options'					=>	$button_types,
					'fallback'					=>	'default'
				),

				'class_field_button_type_primary'		=> array(

					'label'						=>	__('Type', 'ws-form'),
					'type'						=>	'select',
					'default'					=>	'primary',
					'options'					=>	$button_types,
					'key'						=>	'class_field_button_type',
					'fallback'					=>	'primary'
				),

				'class_field_button_type_danger'		=> array(

					'label'						=>	__('Type', 'ws-form'),
					'type'						=>	'select',
					'default'					=>	'danger',
					'options'					=>	$button_types,
					'key'						=>	'class_field_button_type',
					'fallback'					=>	'danger'
				),

				'class_field_button_type_success'		=> array(

					'label'						=>	__('Type', 'ws-form'),
					'type'						=>	'select',
					'default'					=>	'success',
					'options'					=>	$button_types,
					'key'						=>	'class_field_button_type',
					'fallback'					=>	'success'
				),

				'class_fill_lower_track'			=> array(

					'label'						=>	__('Fill Lower Track', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'',
					'mask'						=>	'data-fill-lower-track',
					'mask_disregard_on_empty'	=>	true,
					'help'						=>	__('WS Form skin only.', 'ws-form'),
				),

				'class_single_vertical_align'			=> array(

					'label'						=>	__('Vertical Alignment', 'ws-form'),
					'type'						=>	'select',
					'default'					=>	'',
					'options'					=>	$vertical_align
				),

				'class_single_vertical_align_bottom'	=> array(

					'label'						=>	__('Vertical Alignment', 'ws-form'),
					'type'						=>	'select',
					'default'					=>	'bottom',
					'options'					=>	$vertical_align,
					'key'						=>	'class_single_vertical_align',
					'fallback'					=>	''
				),

				// Sets default value attribute (unless saved value exists)
				'default_value'			=> array(

					'label'						=>	__('Default Value', 'ws-form'),
					'type'						=>	'text',
					'default'					=>	'',
					'help'						=>	__('Default value entered in field.', 'ws-form'),
					'select_list'				=>	true
				),

				// Sets default value attribute (unless saved value exists)
				'default_value_number'	=> array(

					'label'						=>	__('Default Value', 'ws-form'),
					'type'						=>	'number',
					'type_advanced'				=>	'text',
					'default'					=>	'',
					'help'						=>	__('Default number entered in field.', 'ws-form'),
					'key'						=>	'default_value',
					'select_list'				=>	true
				),


				// Sets default value attribute (unless saved value exists)
				'default_value_email'		=> array(

					'label'						=>	__('Default Value', 'ws-form'),
					'type'						=>	'email',
					'default'					=>	'',
					'help'						=>	__('Default email entered in field.', 'ws-form'),
					'key'						=>	'default_value',
					'select_list'				=>	true
				),

				// Sets default value attribute (unless saved value exists)
				'default_value_tel'		=> array(

					'label'						=>	__('Default Value', 'ws-form'),
					'type'						=>	'tel',
					'default'					=>	'',
					'help'						=>	__('Default phone number entered in field.', 'ws-form'),
					'key'						=>	'default_value',
					'select_list'				=>	true
				),

				// Sets default value attribute (unless saved value exists)
				'default_value_url'		=> array(

					'label'						=>	__('Default Value', 'ws-form'),
					'type'						=>	'url',
					'default'					=>	'',
					'help'						=>	__('Default URL entered in field.', 'ws-form'),
					'key'						=>	'default_value',
					'select_list'				=>	true
				),

				// Sets default value attribute (unless saved value exists)
				'default_value_textarea'		=> array(

					'label'						=>	__('Default Value', 'ws-form'),
					'type'						=>	'textarea',
					'default'					=>	'',
					'help'						=>	__('Default value entered in field', 'ws-form'),
					'key'						=>	'default_value',
					'select_list'				=>	true
				),

				// Adds inline class to class attribute
				'class_inline'			=> array(

					'label'						=>	__('Orientation', 'ws-form'),
					'type'						=>	'select',
					'default'					=>	'',
					'options'					=>	array(

						array('value' => '', 'text' => __('Vertical', 'ws-form')),
						array('value' => 'on', 'text' => __('Horizontal', 'ws-form'))
					)
				),

				// Form label mask (Allows user to define custom mask)
				'label_mask_form'		=> array(

					'label'						=>	__('Custom Form Heading HTML', 'ws-form'),
					'mask'						=>	'#value',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'text',
					'default'					=>	'',
					'help'						=>	__('Example: &apos;&lt;h2&gt;#label&lt;/h2&gt;&apos;.', 'ws-form'),
					'placeholder'				=>	'&lt;h2&gt;#label&lt;/h2&gt'
				),

				// Group label mask (Allows user to define custom mask)
				'label_mask_group'		=> array(

					'label'						=>	__('Custom Tab Heading HTML', 'ws-form'),
					'mask'						=>	'#value',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'text',
					'default'					=>	'',
					'help'						=>	__('Example: &apos;&lt;h3&gt;#label&lt;/h3&gt;&apos;.', 'ws-form'),
					'placeholder'				=>	'&lt;h3&gt;#label&lt;/h3&gt'
				),

				// Section label mask (Allows user to define custom mask)
				'label_mask_section'		=> array(

					'label'						=>	__('Custom Section Legend HTML', 'ws-form'),
					'mask'						=>	'#value',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'text',
					'default'					=>	'',
					'help'						=>	__('Example: &apos;&lt;legend&gt;#label&lt;/legend&gt;&apos;.', 'ws-form'),
					'placeholder'				=>	'&lt;legend&gt;#label&lt;/legend&gt;'
				),

				// Wrapper classes
				'class_form_wrapper'		=> array(

					'label'						=>	__('Wrapper CSS Classes', 'ws-form'),
					'mask'						=>	'#value',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'text',
					'default'					=>	'',
					'help'						=>	__('Separate multiple classes by a space.', 'ws-form')
				),

				'class_group_wrapper'		=> array(

					'label'						=>	__('Wrapper CSS Classes', 'ws-form'),
					'mask'						=>	'#value',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'text',
					'default'					=>	'',
					'help'						=>	__('Separate multiple classes by a space.', 'ws-form')
				),

				'class_section_wrapper'		=> array(

					'label'						=>	__('Wrapper CSS Classes', 'ws-form'),
					'mask'						=>	'#value',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'text',
					'default'					=>	'',
					'help'						=>	__('Separate multiple classes by a space.', 'ws-form')
				),

				'class_field_wrapper'		=> array(

					'label'						=>	__('Wrapper CSS Classes', 'ws-form'),
					'mask'						=>	'#value',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'text',
					'default'					=>	'',
					'help'						=>	__('Separate multiple classes by a space.', 'ws-form')
				),

				// Classes
				'class_field'			=> array(

					'label'						=>	__('Field CSS Classes', 'ws-form'),
					'mask'						=>	'#value',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'text',
					'default'					=>	'',
					'help'						=>	__('Separate multiple classes by a space.', 'ws-form')
				),

				'contact_first_name'	=> array(

					'label'						=>	__('First Name', 'ws-form'),
					'type'						=>	'text',
					'default_static'			=>	'#user_first_name',
					'required'					=>	true
				),

				'contact_last_name'	=> array(

					'label'						=>	__('Last Name', 'ws-form'),
					'type'						=>	'text',
					'default_static'			=>	'#user_last_name',
					'required'					=>	true
				),

				'contact_email'	=> array(

					'label'						=>	__('Email', 'ws-form'),
					'type'						=>	'email',
					'default_static'			=>	'#user_email',
					'required'					=>	true
				),

				'contact_push_form'	=> array(

					'label'						=>	__('Attach form (Recommended)', 'ws-form'),
					'type'						=>	'checkbox'
				),

				'contact_push_system'	=> array(

					'label'						=>	sprintf('<a href="%s" target="_blank">%s</a> (%s).', WS_Form_Common::get_admin_url('ws-form-settings', false, 'tab=system'), __('Attach system info', 'ws-form'), __('Recommended', 'ws-form')),
					'type'						=>	'checkbox'
				),

				'contact_inquiry'	=> array(

					'label'						=>	__('Inquiry', 'ws-form'),
					'type'						=>	'textarea',
					'required'					=>	true
				),

				'contact_gdpr'	=> array(

					'label'						=>	__('I consent to having WS Form store my submitted information so they can respond to my inquiry.', 'ws-form'),
					'type'						=>	'checkbox',
					'required'					=>	true
				),

				'contact_submit'	=> array(

					'label'						=>	__('Request Support', 'ws-form'),
					'type'						=>	'button',
					'data-action'				=>	'wsf-contact-us'
				),

				'help'						=> array(

					'label'						=>	__('Help Text', 'ws-form'),
					'type'						=>	'textarea',
					'help'						=>	__('Help text to show alongside this field.', 'ws-form'),
					'select_list'				=>	true
				),


				'help_count_char'	=> array(

					'label'						=>	__('Help Text', 'ws-form'),
					'type'						=>	'textarea',
					'help'						=>	__('Help text to show alongside this field. Use #character_count to inject the current character count.', 'ws-form'),
					'default'					=>	'',
					'key'						=>	'help',
					'select_list'				=>	true
				),

				'help_count_char_word'	=> array(

					'label'						=>	__('Help Text', 'ws-form'),
					'type'						=>	'textarea',
					'help'						=>	__('Help text to show alongside this field. Use #character_count or #word_count to inject the current character or word count.', 'ws-form'),
					'default'					=>	'',
					'key'						=>	'help',
					'select_list'				=>	true
				),

				'help_count_char_word_with_default'	=> array(

					'label'						=>	__('Help Text', 'ws-form'),
					'type'						=>	'textarea',
					'help'						=>	__('Help text to show alongside this field. Use #character_count or #word_count to inject the current character or word count.', 'ws-form'),
					'default'					=>	'#character_count #character_count_label / #word_count #word_count_label',
					'key'						=>	'help',
					'select_list'				=>	true
				),


				'invalid_feedback'			=> array(

					'label'						=>	__('Invalid Feedback Text', 'ws-form'),
					'type'						=>	'textarea',
					'help'						=>	__('Text to show if this field is incorrectly completed.', 'ws-form'),
					'mask_placeholder'			=>	__('Please provide a valid #label_lowercase.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'			=>	'==',
							'meta_key'		=>	'invalid_feedback_render',
							'meta_value'	=>	'on'
						)
					),
					'variables'					=> true
				),

				'invalid_feedback_render'	=> array(

					'label'						=>	__('Render Invalid Feedback', 'ws-form'),
					'type'						=>	'checkbox',
					'help'						=>	__('Show invalid feedback text?', 'ws-form'),
					'default'					=>	'on'
				),

				'text_editor'			=> array(

					'label'						=>	__('Content', 'ws-form'),
					'mask'						=>	'#value',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'text_editor',
					'default'					=>	'',
					'help'						=>	__('Enter paragraphs of text.', 'ws-form'),
					'select_list'				=>	true
				),

				'required_message'		=> array(

					'label'						=>	__('Required Message', 'ws-form'),
					'type'						=>	'required_message',
					'help'						=>	__('Enter a custom message to show if this field is not completed.', 'ws-form'),
					'select_list'				=>	true
				),


				// Field - HTML 5 attributes

				'cols'						=> array(

					'label'						=>	__('Columns', 'ws-form'),
					'mask'						=>	'cols="#value"',
					'mask_disregard_on_empty'	=>	true,
					'mask_disregard_on_zero'	=>	true,
					'type'						=>	'number',
					'help'						=>	__('Number of columns.', 'ws-form')
				),

				'disabled'				=> array(

					'label'						=>	__('Disabled', 'ws-form'),
					'mask'						=>	'disabled',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'checkbox',
					'default'					=>	'',
					'data_change'				=>	array('event' => 'change', 'action' => 'update'),
					'condition'					=>	array(

						array(

							'logic'				=>	'!=',
							'meta_key'			=>	'required',
							'meta_value'		=>	'on'
						),

						array(

							'logic'				=>	'!=',
							'meta_key'			=>	'readonly',
							'meta_value'		=>	'on',
							'logic_previous'	=>	'&&'
						)
					),
				),

				'disabled_section'				=> array(

					'label'						=>	__('Disabled', 'ws-form'),
					'mask'						=>	'disabled',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'checkbox',
					'default'					=>	'',
					'data_change'				=>	array('event' => 'change', 'action' => 'update'),
					'compatibility_id'			=>	'fieldset-disabled'
				),

				'text_align'	=> array(

					'label'						=>	__('Text Align', 'ws-form'),
					'mask'						=>	'style="text-align: #value"',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'select',
					'help'						=>	__('Select the alignment of text in the field.', 'ws-form'),
					'options'					=>	array(

						array('value' => '', 'text' => __('Not Set', 'ws-form')),
						array('value' => 'left', 'text' => __('Left', 'ws-form')),
						array('value' => 'right', 'text' => __('Right', 'ws-form')),
						array('value' => 'center', 'text' => __('Center', 'ws-form')),
						array('value' => 'justify', 'text' => __('Justify', 'ws-form')),
						array('value' => 'inherit', 'text' => __('Inherit', 'ws-form')),
					),
					'default'					=>	'',
					'key'						=>	'text_align'
				),

				'text_align_right'	=> array(

					'label'						=>	__('Text Align', 'ws-form'),
					'mask'						=>	'style="text-align: #value"',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'select',
					'help'						=>	__('Select the alignment of text in the field.', 'ws-form'),
					'options'					=>	array(

						array('value' => '', 'text' => __('Not Set', 'ws-form')),
						array('value' => 'left', 'text' => __('Left', 'ws-form')),
						array('value' => 'right', 'text' => __('Right', 'ws-form')),
						array('value' => 'center', 'text' => __('Center', 'ws-form')),
						array('value' => 'justify', 'text' => __('Justify', 'ws-form')),
						array('value' => 'inherit', 'text' => __('Inherit', 'ws-form')),
					),
					'default'					=>	'right',
					'key'						=>	'text_align'
				),

				'text_align_center'	=> array(

					'label'						=>	__('Text Align', 'ws-form'),
					'mask'						=>	'style="text-align: #value"',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'select',
					'help'						=>	__('Select the alignment of text in the field.', 'ws-form'),
					'options'					=>	array(

						array('value' => '', 'text' => __('Not Set', 'ws-form')),
						array('value' => 'left', 'text' => __('Left', 'ws-form')),
						array('value' => 'right', 'text' => __('Right', 'ws-form')),
						array('value' => 'center', 'text' => __('Center', 'ws-form')),
						array('value' => 'justify', 'text' => __('Justify', 'ws-form')),
						array('value' => 'inherit', 'text' => __('Inherit', 'ws-form')),
					),
					'default'					=>	'center',
					'key'						=>	'text_align'
				),

				'autocomplete_new_password'	=> array(

					'label'						=>	__('Auto Complete Off', 'ws-form'),
					'mask'						=>	'autocomplete="new-password"',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'checkbox',
					'default'					=>	'on',
					'help'						=>	__('Adds autocomplete="new-password" attribute.', 'ws-form')
				),

				'password_strength_meter' => array(

					'label'						=>	__('Password Strength Meter', 'ws-form'),
					'type'						=>	'checkbox',
					'mask'						=>	'data-password-strength-meter',
					'mask_disregard_on_empty'	=>	true,
					'help'						=>	__('Show the WordPress password strength meter?', 'ws-form'),
					'default'					=>	'on',
				),

				'max_length'			=> array(

					'label'						=>	__('Maximum Characters', 'ws-form'),
					'mask'						=>	'maxlength="#value"',
					'mask_disregard_on_empty'	=>	true,
					'min'						=>	0,
					'type'						=>	'number',
					'default'					=>	'',
					'help'						=>	__('Maximum length for this field in characters.', 'ws-form'),
					'compatibility_id'			=>	'maxlength'
				),

				'min_length'			=> array(

					'label'						=>	__('Minimum Characters', 'ws-form'),
					'mask'						=>	'minlength="#value"',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'number',
					'min'						=>	0,
					'default'					=>	'',
					'help'						=>	__('Minimum length for this field in characters.', 'ws-form'),
					'compatibility_id'			=>	'input-minlength'
				),

				'max_length_words'			=> array(

					'label'						=>	__('Maximum Words', 'ws-form'),
					'type'						=>	'number',
					'min'						=>	0,
					'default'					=>	'',
					'help'						=>	__('Maximum words allowed in this field.', 'ws-form')
				),

				'min_length_words'			=> array(

					'label'						=>	__('Minimum Words', 'ws-form'),
					'min'						=>	0,
					'type'						=>	'number',
					'default'					=>	'',
					'help'						=>	__('Minimum words allowed in this field.', 'ws-form')
				),

				'min'						=> array(

					'label'						=>	__('Minimum', 'ws-form'),
					'mask'						=>	'min="#value"',
					'mask_disregard_on_empty'	=>	true,
					'mask_disregard_on_zero'	=>	false,
					'type'						=>	'number',
					'help'						=>	__('Minimum value this field can have.', 'ws-form')
				),

				'max'						=> array(

					'label'						=>	__('Maximum', 'ws-form'),
					'mask'						=>	'max="#value"',
					'mask_disregard_on_empty'	=>	true,
					'mask_disregard_on_zero'	=>	false,
					'type'						=>	'number',
					'help'						=>	__('Maximum value this field can have.', 'ws-form')
				),


				'multiple'						=> array(

					'label'						=>	__('Multiple', 'ws-form'),
					'mask'						=>	'multiple',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'checkbox',
					'help'						=>	__('Can multiple options can be selected at once?', 'ws-form'),
					'default'					=>	''
				),

				'multiple_email'		=> array(

					'label'						=>	__('Multiple', 'ws-form'),
					'mask'						=>	'multiple',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'checkbox',
					'default'					=>	'',
					'help'						=>	__('Can multiple email addresses be entered?', 'ws-form'),
				),

				'input_mask'			=> array(

					'label'						=>	__('Input Mask', 'ws-form'),
					'mask'						=>	'data-inputmask="\'mask\': \'#value\'"',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'text',
					'help'						=>	__('Input mask for the field, e.g. (999) 999-9999', 'ws-form'),
					'select_list'				=>	array(

						array('text' => __('US/Canadian Phone Number', 'ws-form'), 'value' => '(999) 999-9999'),
						array('text' => __('US/Canadian Phone Number (International)', 'ws-form'), 'value' => '+1 (999) 999-9999'),
						array('text' => __('US Zip Code', 'ws-form'), 'value' => '99999'),
						array('text' => __('US Zip Code +4', 'ws-form'), 'value' => '99999[-9999]'),
						array('text' => __('Canadian Post Code', 'ws-form'), 'value' => 'A9A-9A9'),
						array('text' => __('Short Date', 'ws-form'), 'value' => '99/99/9999'),
						array('text' => __('Social Security Number', 'ws-form'), 'value' => '999-99-9999')
					)
				),

				'pattern'			=> array(

					'label'						=>	__('Pattern', 'ws-form'),
					'mask'						=>	'pattern="#value"',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'text',
					'help'						=>	__('Regular expression value is checked against.', 'ws-form'),
					'select_list'				=>	array(

						array('text' => __('Alpha', 'ws-form'), 'value' => '^[a-zA-Z]+$'),
						array('text' => __('Alphanumeric', 'ws-form'), 'value' => '^[a-zA-Z0-9]+$'),
						array('text' => __('Color', 'ws-form'), 'value' => '^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$'),
						array('text' => __('Country Code (2 Character)', 'ws-form'), 'value' => '[A-Za-z]{2}'),
						array('text' => __('Country Code (3 Character)', 'ws-form'), 'value' => '[A-Za-z]{3}'),
						array('text' => __('Date (mm/dd)', 'ws-form'), 'value' => '(0[1-9]|1[012]).(0[1-9]|1[0-9]|2[0-9]|3[01])'),
						array('text' => __('Date (dd/mm)', 'ws-form'), 'value' => '(0[1-9]|1[0-9]|2[0-9]|3[01]).(0[1-9]|1[012])'),
						array('text' => __('Date (mm.dd.yyyy)', 'ws-form'), 'value' => '(0[1-9]|1[012]).(0[1-9]|1[0-9]|2[0-9]|3[01]).[0-9]{4}'),
						array('text' => __('Date (dd.mm.yyyy)', 'ws-form'), 'value' => '(0[1-9]|1[0-9]|2[0-9]|3[01]).(0[1-9]|1[012]).[0-9]{4}'),
						array('text' => __('Date (yyyy-mm-dd)', 'ws-form'), 'value' => '(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))'),
						array('text' => __('Date (mm/dd/yyyy)', 'ws-form'), 'value' => '(?:(?:0[1-9]|1[0-2])[\/\\-. ]?(?:0[1-9]|[12][0-9])|(?:(?:0[13-9]|1[0-2])[\/\\-. ]?30)|(?:(?:0[13578]|1[02])[\/\\-. ]?31))[\/\\-. ]?(?:19|20)[0-9]{2}'),
						array('text' => __('Date (dd/mm/yyyy)', 'ws-form'), 'value' => '^(?:(?:31(\/|-|\.)(?:0?[13578]|1[02]))\1|(?:(?:29|30)(\/|-|\.)(?:0?[1,3-9]|1[0-2])\2))(?:(?:1[6-9]|[2-9]\d)?\d{2})$|^(?:29(\/|-|\.)0?2\3(?:(?:(?:1[6-9]|[2-9]\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:0?[1-9]|1\d|2[0-8])(\/|-|\.)(?:(?:0?[1-9])|(?:1[0-2]))\4(?:(?:1[6-9]|[2-9]\d)?\d{2})$'),
						array('text' => __('Email', 'ws-form'), 'value' => '[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,3}$'),
						array('text' => __('IP (Version 4)', 'ws-form'), 'value' => '^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?).){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$'),
						array('text' => __('IP (Version 6)', 'ws-form'), 'value' => '((^|:)([0-9a-fA-F]{0,4})){1,8}$'),
						array('text' => __('ISBN', 'ws-form'), 'value' => '(?:(?=.{17}$)97[89][ -](?:[0-9]+[ -]){2}[0-9]+[ -][0-9]|97[89][0-9]{10}|(?=.{13}$)(?:[0-9]+[ -]){2}[0-9]+[ -][0-9Xx]|[0-9]{9}[0-9Xx])'),
						array('text' => __('Latitude or Longitude', 'ws-form'), 'value' => '-?\d{1,3}\.\d+'),
						array('text' => __('MD5 Hash', 'ws-form'), 'value' => '[0-9a-fA-F]{32}'),
						array('text' => __('Numeric', 'ws-form'), 'value' => '^[0-9]+$'),
						array('text' => __('Password (Numeric, lower, upper)', 'ws-form'), 'value' => '^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.*\s).*$'),
						array('text' => __('Password (Numeric, lower, upper, min 8)', 'ws-form'), 'value' => '(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}'),
						array('text' => __('Phone - UK', 'ws-form'), 'value' => '^\s*\(?(020[7,8]{1}\)?[ ]?[1-9]{1}[0-9{2}[ ]?[0-9]{4})|(0[1-8]{1}[0-9]{3}\)?[ ]?[1-9]{1}[0-9]{2}[ ]?[0-9]{3})\s*$'),
						array('text' => __('Phone - US: 123-456-7890', 'ws-form'), 'value' => '\d{3}[\-]\d{3}[\-]\d{4}'),
						array('text' => __('Phone - US: (123)456-7890', 'ws-form'), 'value' => '\([0-9]{3}\)[0-9]{3}-[0-9]{4}'),
						array('text' => __('Phone - US: (123) 456-7890', 'ws-form'), 'value' => '\([0-9]{3}\) [0-9]{3}-[0-9]{4}'),
						array('text' => __('Phone - US: Flexible', 'ws-form'), 'value' => '(?:\(\d{3}\)|\d{3})[- ]?\d{3}[- ]?\d{4}'),
						array('text' => __('Postal Code (UK)', 'ws-form'), 'value' => '[A-Za-z]{1,2}[0-9Rr][0-9A-Za-z]? [0-9][ABD-HJLNP-UW-Zabd-hjlnp-uw-z]{2}'),
						array('text' => __('Price (1.23)', 'ws-form'), 'value' => '\d+(\.\d{2})?'),
						array('text' => __('Slug', 'ws-form'), 'value' => '^[a-z0-9-]+$'),
						array('text' => __('Time (hh:mm:ss)', 'ws-form'), 'value' => '(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]){2}'),
						array('text' => __('URL', 'ws-form'), 'value' => 'https?://.+'),
						array('text' => __('Zip Code', 'ws-form'), 'value' => '(\d{5}([\-]\d{4})?)')						
					),
					'compatibility_id'			=>	'input-pattern'
				),

				'pattern_tel'			=> array(

					'label'						=>	__('Pattern', 'ws-form'),
					'mask'						=>	'pattern="#value"',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'text',
					'help'						=>	__('Regular expression value is checked against.', 'ws-form'),
					'select_list'				=>	array(

						array('text' => __('Phone - UK', 'ws-form'), 'value' => '^\s*\(?(020[7,8]{1}\)?[ ]?[1-9]{1}[0-9{2}[ ]?[0-9]{4})|(0[1-8]{1}[0-9]{3}\)?[ ]?[1-9]{1}[0-9]{2}[ ]?[0-9]{3})\s*$'),
						array('text' => __('Phone - US: 123-456-7890', 'ws-form'), 'value' => '\d{3}[\-]\d{3}[\-]\d{4}'),
						array('text' => __('Phone - US: (123)456-7890', 'ws-form'), 'value' => '\([0-9]{3}\)[0-9]{3}-[0-9]{4}'),
						array('text' => __('Phone - US: (123) 456-7890', 'ws-form'), 'value' => '\([0-9]{3}\) [0-9]{3}-[0-9]{4}'),
						array('text' => __('Phone - US: Flexible', 'ws-form'), 'value' => '(?:\(\d{3}\)|\d{3})[- ]?\d{3}[- ]?\d{4}')						
					),
					'compatibility_id'			=>	'input-pattern'
				),

				'pattern_date'			=> array(

					'label'						=>	__('Pattern', 'ws-form'),
					'mask'						=>	'pattern="#value"',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'text',
					'help'						=>	__('Regular expression value is checked against.', 'ws-form'),
					'select_list'				=>	array(

						array('text' => __('mm.dd.yyyy', 'ws-form'), 'value' => '(0[1-9]|1[012]).(0[1-9]|1[0-9]|2[0-9]|3[01]).[0-9]{4}'),
						array('text' => __('dd.mm.yyyy', 'ws-form'), 'value' => '(0[1-9]|1[0-9]|2[0-9]|3[01]).(0[1-9]|1[012]).[0-9]{4}'),
						array('text' => __('mm/dd/yyyy', 'ws-form'), 'value' => '(0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])[- /.](19|20)\d\d'),
						array('text' => __('dd/mm/yyyy', 'ws-form'), 'value' => '(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d'),
						array('text' => __('yyyy-mm-dd', 'ws-form'), 'value' => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])'),
						array('text' => __('hh:mm:ss', 'ws-form'), 'value' => '(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]){2}'),
						array('text' => __('yyyy-mm-ddThh:mm:ssZ', 'ws-form'), 'value' => '/([0-2][0-9]{3})\-([0-1][0-9])\-([0-3][0-9])T([0-5][0-9])\:([0-5][0-9])\:([0-5][0-9])(Z|([\-\+]([0-1][0-9])\:00))/')						
					),
					'compatibility_id'			=>	'input-pattern'
				),

				'placeholder'			=> array(

					'label'						=>	__('Placeholder', 'ws-form'),
					'mask'						=>	'placeholder="#value"',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'text',
					'help'						=>	__('Short hint that describes the expected value of the input field.', 'ws-form'),
					'compatibility_id'			=>	'input-placeholder',
					'select_list'				=>	true
				),

				'placeholder_row'			=> array(

					'label'						=>	__('First Row Placeholder (Blank for none)', 'ws-form'),
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'text',
					'default'					=>	__('Select...', 'ws-form'),
					'help'						=>	__('First value in the select pulldown.', 'ws-form')
				),

				'readonly'				=> array(

					'label'						=>	__('Read Only', 'ws-form'),
					'mask'						=>	'readonly',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'checkbox',
					'data_change'				=>	array('event' => 'change', 'action' => 'update'),
					'default'					=>	'',
					'condition'					=>	array(

						array(

							'logic'				=>	'!=',
							'meta_key'			=>	'required',
							'meta_value'		=>	'on'
						),

						array(

							'logic'				=>	'!=',
							'meta_key'			=>	'disabled',
							'meta_value'		=>	'on',
							'logic_previous'	=>	'&&'
						)
					),
					'compatibility_id'			=>	'readonly-attr'
				),

				'readonly_on'				=> array(

					'label'						=>	__('Read Only', 'ws-form'),
					'mask'						=>	'readonly',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'checkbox',
					'default'					=>	'on',
					'data_change'				=>	array('event' => 'change', 'action' => 'update'),
					'condition'					=>	array(

						array(

							'logic'				=>	'!=',
							'meta_key'			=>	'required',
							'meta_value'		=>	'on'
						),

						array(

							'logic'				=>	'!=',
							'meta_key'			=>	'disabled',
							'meta_value'		=>	'on',
							'logic_previous'	=>	'&&'
						)
					),
					'compatibility_id'			=>	'readonly-attr',
					'key'						=>	'readonly'
				),

				'scroll_to_top'				=> array(

					'label'						=>	__('Scroll To Top', 'ws-form'),
					'type'						=>	'select',
					'default'					=>	'',
					'options'					=>	array(

						array('value' => '', 'text' => __('None', 'ws-form')),
						array('value' => 'instant', 'text' => __('Instant', 'ws-form')),
						array('value' => 'smooth', 'text' => __('Smooth', 'ws-form'))
					)
				),

				'scroll_to_top_offset'		=> array(

					'label'						=>	__('Offset (Pixels)', 'ws-form'),
					'type'						=>	'number',
					'default'					=>	'0',
					'help'						=>	__('Number of pixels to offset the final scroll position by. Useful for sticky headers, e.g. if your header is 100 pixels tall, enter 100 into this setting.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'				=>	'!=',
							'meta_key'			=>	'scroll_to_top',
							'meta_value'		=>	''
						)
					)
				),

				'scroll_to_top_duration'	=> array(

					'label'						=>	__('Duration (ms)', 'ws-form'),
					'type'						=>	'number',
					'default'					=>	'400',
					'help'						=>	__('Duration of the smooth scroll in ms.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'scroll_to_top',
							'meta_value'		=>	'smooth'
						)
					)
				),

				'required'				=> array(

					'label'						=>	__('Required', 'ws-form'),
					'mask'						=>	'required data-required aria-required="true"',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'checkbox',
					'default'					=>	'',
					'compatibility_id'			=>	'form-validation',
					'data_change'				=>	array('event' => 'change', 'action' => 'update'),
					'condition'					=>	array(

						array(

							'logic'				=>	'!=',
							'meta_key'			=>	'disabled',
							'meta_value'		=>	'on'
						),

						array(

							'logic'				=>	'!=',
							'meta_key'			=>	'readonly',
							'meta_value'		=>	'on',
							'logic_previous'	=>	'&&'
						)
					)
				),

				'required_on'			=> array(

					'label'						=>	__('Required', 'ws-form'),
					'mask'						=>	'required data-required aria-required="true"',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'checkbox',
					'default'					=>	'on',
					'compatibility_id'			=>	'form-validation',
					'key'						=>	'required',
					'data_change'				=>	array('event' => 'change', 'action' => 'update'),
					'condition'					=>	array(

						array(

							'logic'			=>	'!=',
							'meta_key'		=>	'disabled',
							'meta_value'	=>	'on'
						),

						array(

							'logic'			=>	'!=',
							'meta_key'		=>	'readonly',
							'meta_value'	=>	'on',
							'logic_previous'	=>	'&&'
						)
					)
				),
				
				'required_attribute_no'	=> array(

					'label'						=>	__('Required', 'ws-form'),
					'mask'						=>	'',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'checkbox',
					'default'					=>	'',
					'compatibility_id'			=>	'form-validation',
					'data_change'				=>	array('event' => 'change', 'action' => 'update'),
					'key'						=>	'required'
				),

				'required_row'				=> array(

					'mask'						=>	'required data-required aria-required="true"',
					'mask_disregard_on_empty'	=>	true
				),

				'rows'						=> array(

					'label'						=>	__('Rows', 'ws-form'),
					'mask'						=>	'rows="#value"',
					'mask_disregard_on_empty'	=>	true,
					'mask_disregard_on_zero'	=>	true,
					'type'						=>	'number',
					'help'						=>	__('Number of rows.', 'ws-form')
				),

				'size'						=> array(

					'label'						=>	__('Size', 'ws-form'),
					'mask'						=>	'size="#value"',
					'mask_disregard_on_empty'	=>	true,
					'mask_disregard_on_zero'	=>	true,
					'type'						=>	'number',
					'attributes'				=>	array('min' => 0),
					'help'						=>	__('The number of visible options.', 'ws-form')
				),

				'select_all'				=> array(

					'label'						=>	__('Enable Select All', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'',
					'help'						=>	__('Show a \'Select All\' option above the first row.', 'ws-form')
				),

				'select_all_label'			=> array(

					'label'						=>	__('Select All Label', 'ws-form'),
					'type'						=>	'text',
					'default'					=>	'',
					'placeholder'				=>	__('Select All', 'ws-form'),
					'help'						=>	__('Enter custom label for \'Select All\' row.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'select_all',
							'meta_value'		=>	'on'
						)
					),
				),

				'spellcheck'	=> array(

					'label'						=>	__('Spell Check', 'ws-form'),
					'mask'						=>	'spellcheck="#value"',
					'mask_disregard_on_empty'	=>	true,
					'type'						=>	'select',
					'help'						=>	__('Spelling and grammar checking.', 'ws-form'),
					'options'					=>	array(

						array('value' => '', 		'text' => __('Browser default', 'ws-form')),
						array('value' => 'true', 	'text' => __('Enabled', 'ws-form')),
						array('value' => 'false', 	'text' => __('Disabled', 'ws-form'))
					),
					'compatibility_id'			=>	'spellcheck-attribute'
				),

				'step'						=> array(

					'label'						=>	__('Step', 'ws-form'),
					'mask'						=>	'step="#value"',
					'mask_disregard_on_empty'	=>	true,
					'mask_disregard_on_zero'	=>	false,
					'type'						=>	'number',
					'help'						=>	__('Increment/decrement by this value.', 'ws-form')
				),

				// Fields - Sidebars
				'field_select'	=> array(

					'type'					=>	'field_select'
				),

				'form_history'	=> array(

					'type'					=>	'form_history'
				),

				'knowledgebase'	=> array(

					'type'					=>	'knowledgebase'
				),

				'contact'	=> array(

					'type'					=>	'contact'
				),

				'ws_form_field'					=> array(

					'label'						=>	__('Form Field', 'ws-form'),
					'type'						=>	'select',
					'options'					=>	'fields',
					'options_blank'				=>	__('Select...', 'ws-form')
				),

				'ws_form_field_choice'		=> array(

					'label'						=>	__('Form Field', 'ws-form'),
					'type'						=>	'select',
					'options'					=>	'fields',
					'options_blank'				=>	__('Select...', 'ws-form'),
					'fields_filter_type'		=>	array('select', 'checkbox', 'radio'),
					'key'						=>	'ws_form_field'
				),

				'ws_form_field_file'		=> array(

					'label'						=>	__('Form Field', 'ws-form'),
					'type'						=>	'select',
					'options'					=>	'fields',
					'options_blank'				=>	__('Select...', 'ws-form'),
					'fields_filter_type'		=>	array('signature', 'file'),
					'key'						=>	'ws_form_field'
				),

				'ws_form_field_save'		=> array(

					'label'						=>	__('Form Field', 'ws-form'),
					'type'						=>	'select',
					'options'					=>	'fields',
					'options_blank'				=>	__('Select...', 'ws-form'),
					'fields_filter_attribute'	=>	array('submit_save'),
					'key'						=>	'ws_form_field'
				),

				'ws_form_field_edit'		=> array(

					'label'						=>	__('Form Field', 'ws-form'),
					'type'						=>	'select',
					'options'					=>	'fields',
					'options_blank'				=>	__('Select...', 'ws-form'),
					'fields_filter_attribute'	=>	array('submit_edit'),
					'key'						=>	'ws_form_field'
				),

				'ws_form_field_ecommerce_price_cart'	=> array(

					'label'						=>	__('Form Field', 'ws-form'),
					'type'						=>	'select',
					'options'					=>	'fields',
					'options_blank'				=>	__('Select...', 'ws-form'),
					'fields_filter_attribute'	=>	array('ecommerce_cart_price')
				),

				// Fields - Data grids

				'action'	=>	array(

					'label'					=>	__('Actions', 'ws-form'),
					'type'					=>	'data_grid',
					'type_sub'				=>	'action',	// Sub type
					'row_disabled'			=>	true,	// Is the disabled attribute supported on rows?
					'max_columns'			=>	1,		// Maximum number of columns
					'groups_label'			=>	false,	// Is the group label feature enabled?
					'groups_label_render'	=>	false,	// Is the group label render feature enabled?
					'groups_auto_group'		=>	false,	// Is auto group feature enabled?
					'groups_disabled'		=>	false,	// Is the group disabled attribute?
					'groups_group'			=>	false,	// Is the group mask supported?
					'upload_download'		=>	false,

					'default'			=>	array(

						// Config
						'rows_per_page'		=>	10,
						'group_index'		=>	0,
						'default'			=>	array(),

						// Columns
						'columns' => array(

							array('id' => 0, 'label' => __('Action', 'ws-form')),
							array('id' => 1, 'label' => __('Data', 'ws-form')),
						),

						// Group
						'groups' => array(

							array(

								'label' 		=> __('Actions', 'ws-form'),
								'page'			=> 0,
								'disabled'		=> '',
								'mask_group'	=> '',

								// Rows (Only injected for a new data grid, blank for new groups)
								'rows' 		=> array(
								)
							)
						)
					)
				),

				'data_grid_datalist'	=>	array(

					'label'					=>	__('Datalist', 'ws-form'),
					'type'					=>	'data_grid',
					'row_default'			=>	false,	// Is the default attribute supported on rows?
					'row_disabled'			=>	false,	// Is the disabled attribute supported on rows?
					'row_required'			=>	false,	// Is the required attribute supported on rows?
					'row_hidden'			=>	true,	// Is the hidden supported on rows?
					'groups_label'			=>	false,	// Is the group label feature enabled?
					'groups_label_render'	=>	false,	// Is the group label render feature enabled?
					'groups_auto_group'		=>	false,	// Is auto group feature enabled?
					'groups_disabled'		=>	false,	// Is the disabled attribute supported on groups?
					'groups_group'			=>	false,	// Can user add groups?
					'mask_group'			=>	false,	// Is the group mask supported?
					'upload_download'		=>	true,
					'compatibility_id'		=>	'datalist',

					'default'			=>	array(

						// Config
						'rows_per_page'		=>	10,
						'group_index'		=>	0,
						'default'			=>	array(),

						// Columns
						'columns' => array(

							array('id' => 0, 'label' => __('Value', 'ws-form')),
							array('id' => 1, 'label' => __('Label', 'ws-form'))
						),

						// Group
						'groups' => array(

							array(

								'label' 		=> __('Values', 'ws-form'),
								'page'			=> 0,
								'disabled'		=> '',
								'mask_group'	=> '',

								// Rows (Only injected for a new data grid, blank for new groups)
								'rows' 		=> array()
							)
						)
					)
				),

				'datalist_field_value'	=> array(

					'label'						=>	__('Values', 'ws-form'),
					'type'						=>	'data_grid_field',
					'data_grid'					=>	'data_grid_datalist',
					'default'					=>	0,
					'html_encode'				=>	true
				),

				'datalist_field_text'		=> array(

					'label'						=>	__('Labels', 'ws-form'),
					'type'						=>	'data_grid_field',
					'data_grid'					=>	'data_grid_datalist',
					'default'					=>	1
				),

				'data_grid_select'	=>	array(

					'label'					=>	__('Options', 'ws-form'),
					'type'					=>	'data_grid',
					'row_default'			=>	true,	// Is the default attribute supported on rows?
					'row_disabled'			=>	true,	// Is the disabled attribute supported on rows?
					'row_required'			=>	false,	// Is the required attribute supported on rows?
					'row_hidden'			=>	true,	// Is the hidden supported on rows?
					'groups_label'			=>	true,	// Is the group label feature enabled?
					'groups_label_label'	=>	__('Label', 'ws-form'),
					'groups_label_render'	=>	false,	// Is the group label render feature enabled?
					'groups_label_render_label'	=>	__('Render Label', 'ws-form'),
					'groups_auto_group'		=>	true,	// Is auto group feature enabled?
					'groups_disabled'		=>	true,	// Is the group disabled attribute?
					'groups_group'			=>	true,	// Is the group mask supported?
					'groups_group_label'	=>	__('Wrap In Optgroup', 'ws-form'),
					'upload_download'		=>	true,

					'default'			=>	array(

						// Config
						'rows_per_page'		=>	10,
						'group_index'		=>	0,
						'default'			=>	array(),

						// Columns
						'columns' => array(

							array('id' => 0, 'label' => __('Label', 'ws-form')),
						),

						// Group
						'groups' => array(

							array(

								'label' 		=> __('Options', 'ws-form'),
								'page'			=> 0,
								'disabled'		=> '',
								'mask_group'	=> '',

								// Rows (Only injected for a new data grid, blank for new groups)
								'rows' 		=> array(
									array(

										'id'		=> 1,
										'default'	=> '',
										'required'	=> '',
										'disabled'	=> '',
										'hidden'	=> '',
										'data'		=> array(__('Option 1', 'ws-form'))
									),
									array(

										'id'		=> 2,
										'default'	=> '',
										'required'	=> '',
										'disabled'	=> '',
										'hidden'	=> '',
										'data'		=> array(__('Option 2', 'ws-form'))
									),
									array(

										'id'		=> 3,
										'default'	=> '',
										'disabled'	=> '',
										'required'	=> '',
										'hidden'	=> '',
										'data'		=> array(__('Option 3', 'ws-form'))
									)
								)
							)
						)
					)
				),

				'select_field_value'			=> array(

					'label'						=>	__('Values', 'ws-form'),
					'type'						=>	'data_grid_field',
					'data_grid'					=>	'data_grid_select',
					'default'					=>	0,
					'html_encode'				=>	true
				),

				'select_field_label'			=> array(

					'label'						=>	__('Labels', 'ws-form'),
					'type'						=>	'data_grid_field',
					'data_grid'					=>	'data_grid_select',
					'default'					=>	0
				),

				'select_cascade'				=> array(

					'label'						=>	__('Cascade', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'',
					'help'						=>	__('Filter this data grid using a value from another field.', 'ws-form')
				),

				'select_cascade_field_id'		=> array(

					'label'						=>	__('Filter Value', 'ws-form'),
					'type'						=>	'select',
					'default'					=>	'',
					'options'					=>	'fields',
					'options_blank'				=>	__('Select...', 'ws-form'),
					'fields_filter_type'		=>	array('select', 'price_select', 'radio', 'price_radio'),
					'help'						=>	__('Select the field to use as the filter value.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'select_cascade',
							'meta_value'		=>	'on'
						)
					)
				),

				'select_cascade_field_filter'	=> array(

					'label'						=>	__('Filter Column', 'ws-form'),
					'type'						=>	'data_grid_field',
					'data_grid'					=>	'data_grid_select',
					'default'					=>	0,
					'help'						=>	__('Select the column to filter with the filter value.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'select_cascade',
							'meta_value'		=>	'on'
						)
					)
				),

				'data_grid_checkbox'	=>	array(

					'label'					=>	__('Checkboxes', 'ws-form'),
					'type'					=>	'data_grid',
					'row_default'			=>	true,	// Is the default attribute supported on rows?
					'row_disabled'			=>	true,	// Is the disabled attribute supported on rows?
					'row_required'			=>	true,	// Is the required attribute supported on rows?
					'row_hidden'			=>	true,	// Is the hidden supported on rows?
					'row_default_multiple'	=>	true,	// Can multiple defaults be selected?
					'row_required_multiple'	=>	true,	// Can multiple requires be selected?
					'groups_label'			=>	true,	// Is the group label feature enabled?
					'groups_label_label'	=>	__('Label', 'ws-form'),
					'groups_label_render'	=>	true,	// Is the group label render feature enabled?
					'groups_label_render_label'	=>	__('Render Label', 'ws-form'),
					'groups_auto_group'		=>	true,	// Is auto group feature enabled?
					'groups_disabled'		=>	true,	// Is the group disabled attribute?
					'groups_group'			=>	true,	// Is the group mask supported?
					'groups_group_label'	=>	__('Wrap In Fieldset', 'ws-form'),
					'upload_download'		=>	true,

					'default'			=>	array(

						// Config
						'rows_per_page'		=>	10,
						'group_index'		=>	0,
						'default'			=>	array(),

						// Columns
						'columns' => array(

							array('id' => 0, 'label' => __('Label', 'ws-form'))
						),

						// Group
						'groups' => array(

							array(

								'label' 		=> __('Checkboxes', 'ws-form'),
								'page'			=> 0,
								'disabled'		=> '',
								'mask_group'	=> '',

								// Rows (Only injected for a new data grid, blank for new groups)
								'rows' 		=> array(

									array(

										'id'		=> 1,
										'default'	=> '',
										'required'	=> '',
										'disabled'	=> '',
										'hidden'	=> '',
										'data'		=> array(__('Checkbox 1', 'ws-form'))
									),
									array(

										'id'		=> 2,
										'default'	=> '',
										'required'	=> '',
										'disabled'	=> '',
										'hidden'	=> '',
										'data'		=> array(__('Checkbox 2', 'ws-form'))
									),
									array(

										'id'		=> 3,
										'default'	=> '',
										'required'	=> '',
										'disabled'	=> '',
										'hidden'	=> '',
										'data'		=> array(__('Checkbox 3', 'ws-form'))
									)
								)
							)
						)
					)
				),

				'checkbox_field_value'	=> array(

					'label'						=>	__('Values', 'ws-form'),
					'type'						=>	'data_grid_field',
					'data_grid'					=>	'data_grid_checkbox',
					'default'					=>	0,
					'html_encode'				=>	true
				),

				'checkbox_field_label'		=> array(

					'label'						=>	__('Labels', 'ws-form'),
					'type'						=>	'data_grid_field',
					'data_grid'					=>	'data_grid_checkbox',
					'default'					=>	0
				),

				'data_grid_radio'	=>	array(

					'label'					=>	__('Radios', 'ws-form'),
					'type'					=>	'data_grid',
					'row_default'			=>	true,	// Is the default attribute supported on rows?
					'row_disabled'			=>	true,	// Is the disabled attribute supported on rows?
					'row_required'			=>	false,	// Is the required attribute supported on rows?
					'row_hidden'			=>	true,	// Is the hidden supported on rows?
					'row_default_multiple'	=>	false,	// Can multiple defaults be selected?
					'row_required_multiple'	=>	false,	// Can multiple requires be selected?
					'groups_label'			=>	true,	// Is the group label feature enabled?
					'groups_label_label'	=>	__('Label', 'ws-form'),
					'groups_label_render'	=>	true,	// Is the group label render feature enabled?
					'groups_label_render_label'	=>	__('Render Label', 'ws-form'),
					'groups_auto_group'		=>	true,	// Is auto group feature enabled?
					'groups_disabled'		=>	true,	// Is the group disabled attribute?
					'groups_group'			=>	true,	// Is the group mask supported?
					'groups_group_label'	=>	__('Wrap In Fieldset', 'ws-form'),
					'upload_download'		=>	true,

					'default'			=>	array(

						// Config
						'rows_per_page'		=>	10,
						'group_index'		=>	0,
						'default'			=>	array(),

						// Columns
						'columns' => array(

							array('id' => 0, 'label' => __('Label', 'ws-form'))
						),

						// Group
						'groups' => array(

							array(

								'label' 		=> __('Radios', 'ws-form'),
								'page'			=> 0,
								'disabled'		=> '',
								'mask_group'	=> '',

								// Rows (Only injected for a new data grid, blank for new groups)
								'rows' 		=> array(

									array(

										'id'		=> 1,
										'default'	=> '',
										'required'	=> '',
										'disabled'	=> '',
										'hidden'	=> '',
										'data'		=> array(__('Radio 1', 'ws-form'))
									),
									array(

										'id'		=> 2,
										'default'	=> '',
										'required'	=> '',
										'disabled'	=> '',
										'hidden'	=> '',
										'data'		=> array(__('Radio 2', 'ws-form'))
									),
									array(

										'id'		=> 3,
										'default'	=> '',
										'required'	=> '',
										'disabled'	=> '',
										'hidden'	=> '',
										'data'		=> array(__('Radio 3', 'ws-form'))
									)
								)
							)
						)
					)
				),

				'radio_field_value'	=> array(

					'label'						=>	__('Values', 'ws-form'),
					'type'						=>	'data_grid_field',
					'data_grid'					=>	'data_grid_radio',
					'default'					=>	0,
					'html_encode'				=>	true
				),

				'radio_field_label'		=> array(

					'label'						=>	__('Labels', 'ws-form'),
					'type'						=>	'data_grid_field',
					'data_grid'					=>	'data_grid_radio',
					'default'					=>	0
				),

				'radio_cascade'				=> array(

					'label'						=>	__('Cascade', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'',
					'help'						=>	__('Filter this data grid using a value from another field.', 'ws-form')
				),

				'radio_cascade_field_id'		=> array(

					'label'						=>	__('Filter Value', 'ws-form'),
					'type'						=>	'select',
					'default'					=>	'',
					'options'					=>	'fields',
					'options_blank'				=>	__('Select...', 'ws-form'),
					'fields_filter_type'		=>	array('select', 'price_select', 'radio', 'price_radio'),
					'help'						=>	__('Select the field to use as the filter value.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'radio_cascade',
							'meta_value'		=>	'on'
						)
					)
				),

				'radio_cascade_field_filter'	=> array(

					'label'						=>	__('Filter Column', 'ws-form'),
					'type'						=>	'data_grid_field',
					'data_grid'					=>	'data_grid_select',
					'default'					=>	0,
					'help'						=>	__('Select the column to filter with the filter value.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'radio_cascade',
							'meta_value'		=>	'on'
						)
					)
				),

				'data_grid_rows_randomize'	=> array(

					'label'						=>	__('Randomize Rows', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	''
				),

				'data_grid_select_price'	=>	array(

					'label'					=>	__('Products', 'ws-form'),
					'type'					=>	'data_grid',
					'row_default'			=>	true,	// Is the default attribute supported on rows?
					'row_disabled'			=>	true,	// Is the disabled attribute supported on rows?
					'row_required'			=>	false,	// Is the required attribute supported on rows?
					'row_hidden'			=>	true,	// Is the hidden supported on rows?
					'groups_label'			=>	true,	// Is the group label feature enabled?
					'groups_label_label'	=>	__('Label', 'ws-form'),
					'groups_label_render'	=>	false,	// Is the group label render feature enabled?
					'groups_label_render_label'	=>	__('Render Label', 'ws-form'),
					'groups_auto_group'		=>	true,	// Is auto group feature enabled?
					'groups_disabled'		=>	true,	// Is the group disabled attribute?
					'groups_group'			=>	true,	// Is the group mask supported?
					'groups_group_label'	=>	__('Wrap In Optgroup', 'ws-form'),
					'upload_download'		=>	true,

					'default'			=>	array(

						// Config
						'rows_per_page'		=>	10,
						'group_index'		=>	0,
						'default'			=>	array(),

						// Columns
						'columns' => array(

							array('id' => 0, 'label' => __('Product', 'ws-form')),
							array('id' => 1, 'label' => __('Price', 'ws-form')),
						),

						// Group
						'groups' => array(

							array(

								'label' 		=> __('Products', 'ws-form'),
								'page'			=> 0,
								'disabled'		=> '',
								'mask_group'	=> '',

								// Rows (Only injected for a new data grid, blank for new groups)
								'rows' 		=> array(
									array(

										'id'		=> 1,
										'default'	=> '',
										'required'	=> '',
										'disabled'	=> '',
										'hidden'	=> '',
										'data'		=> array(__('Product 1', 'ws-form'), '1.23')
									),
									array(

										'id'		=> 2,
										'default'	=> '',
										'required'	=> '',
										'disabled'	=> '',
										'hidden'	=> '',
										'data'		=> array(__('Product 2', 'ws-form'), '2.34')
									),
									array(

										'id'		=> 3,
										'default'	=> '',
										'disabled'	=> '',
										'required'	=> '',
										'hidden'	=> '',
										'data'		=> array(__('Product 3', 'ws-form'), '3.45')
									)
								)
							)
						)
					)
				),

				'select_price_field_label'	=> array(

					'label'						=>	__('Product Name', 'ws-form'),
					'type'						=>	'data_grid_field',
					'data_grid'					=>	'data_grid_select_price',
					'default'					=>	0
				),

				'select_price_field_value'		=> array(

					'label'						=>	__('Price', 'ws-form'),
					'type'						=>	'data_grid_field',
					'data_grid'					=>	'data_grid_select_price',
					'default'					=>	1,
					'html_encode'				=>	true,
					'price'						=>	true
				),

				'price_select_cascade'				=> array(

					'label'						=>	__('Cascade', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'',
					'help'						=>	__('Filter this data grid using a value from another field.', 'ws-form')
				),

				'price_select_cascade_field_id'		=> array(

					'label'						=>	__('Filter Value', 'ws-form'),
					'type'						=>	'select',
					'default'					=>	'',
					'options'					=>	'fields',
					'options_blank'				=>	__('Select...', 'ws-form'),
					'fields_filter_type'		=>	array('select', 'price_select', 'radio', 'price_radio'),
					'help'						=>	__('Select the field to use as the filter value.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'price_select_cascade',
							'meta_value'		=>	'on'
						)
					)
				),

				'price_select_cascade_field_filter'	=> array(

					'label'						=>	__('Filter Column', 'ws-form'),
					'type'						=>	'data_grid_field',
					'data_grid'					=>	'data_grid_select',
					'default'					=>	0,
					'help'						=>	__('Select the column to filter with the filter value.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'price_select_cascade',
							'meta_value'		=>	'on'
						)
					)
				),

				'data_grid_checkbox_price'	=>	array(

					'label'					=>	__('Products', 'ws-form'),
					'type'					=>	'data_grid',
					'row_default'			=>	true,	// Is the default attribute supported on rows?
					'row_disabled'			=>	true,	// Is the disabled attribute supported on rows?
					'row_required'			=>	true,	// Is the required attribute supported on rows?
					'row_hidden'			=>	true,	// Is the hidden supported on rows?
					'row_default_multiple'	=>	true,	// Can multiple defaults be selected?
					'row_required_multiple'	=>	true,	// Can multiple requires be selected?
					'groups_label'			=>	true,	// Is the group label feature enabled?
					'groups_label_label'	=>	__('Label', 'ws-form'),
					'groups_label_render'	=>	true,	// Is the group label render feature enabled?
					'groups_label_render_label'	=>	__('Render Label', 'ws-form'),
					'groups_auto_group'		=>	true,	// Is auto group feature enabled?
					'groups_disabled'		=>	true,	// Is the group disabled attribute?
					'groups_group'			=>	true,	// Is the group mask supported?
					'groups_group_label'	=>	__('Wrap In Fieldset', 'ws-form'),
					'upload_download'		=>	true,

					'default'			=>	array(

						// Config
						'rows_per_page'		=>	10,
						'group_index'		=>	0,
						'default'			=>	array(),

						// Columns
						'columns' => array(

							array('id' => 0, 'label' => __('Product', 'ws-form')),
							array('id' => 1, 'label' => __('Price', 'ws-form')),
						),

						// Group
						'groups' => array(

							array(

								'label' 		=> __('Products', 'ws-form'),
								'page'			=> 0,
								'disabled'		=> '',
								'mask_group'	=> '',

								// Rows (Only injected for a new data grid, blank for new groups)
								'rows' 		=> array(
									array(

										'id'		=> 1,
										'default'	=> '',
										'required'	=> '',
										'disabled'	=> '',
										'hidden'	=> '',
										'data'		=> array(__('Product 1', 'ws-form'), '1.23')
									),
									array(

										'id'		=> 2,
										'default'	=> '',
										'required'	=> '',
										'disabled'	=> '',
										'hidden'	=> '',
										'data'		=> array(__('Product 2', 'ws-form'), '2.34')
									),
									array(

										'id'		=> 3,
										'default'	=> '',
										'disabled'	=> '',
										'required'	=> '',
										'hidden'	=> '',
										'data'		=> array(__('Product 3', 'ws-form'), '3.45')
									)
								)
							)
						)
					)
				),

				'checkbox_price_field_label'		=> array(

					'label'						=>	__('Product Name', 'ws-form'),
					'type'						=>	'data_grid_field',
					'data_grid'					=>	'data_grid_checkbox_price',
					'default'					=>	0
				),

				'checkbox_price_field_value'	=> array(

					'label'						=>	__('Price', 'ws-form'),
					'type'						=>	'data_grid_field',
					'data_grid'					=>	'data_grid_checkbox_price',
					'default'					=>	1,
					'html_encode'				=>	true,
					'price'						=>	true
				),

				'data_grid_radio_price'	=>	array(

					'label'					=>	__('Products', 'ws-form'),
					'type'					=>	'data_grid',
					'row_default'			=>	true,	// Is the default attribute supported on rows?
					'row_disabled'			=>	true,	// Is the disabled attribute supported on rows?
					'row_required'			=>	false,	// Is the required attribute supported on rows?
					'row_hidden'			=>	true,	// Is the hidden supported on rows?
					'row_default_multiple'	=>	false,	// Can multiple defaults be selected?
					'row_required_multiple'	=>	false,	// Can multiple requires be selected?
					'groups_label'			=>	true,	// Is the group label feature enabled?
					'groups_label_label'	=>	__('Label', 'ws-form'),
					'groups_label_render'	=>	true,	// Is the group label render feature enabled?
					'groups_label_render_label'	=>	__('Render Label', 'ws-form'),
					'groups_auto_group'		=>	true,	// Is auto group feature enabled?
					'groups_disabled'		=>	true,	// Is the group disabled attribute?
					'groups_group'			=>	true,	// Is the group mask supported?
					'groups_group_label'	=>	__('Wrap In Fieldset', 'ws-form'),
					'upload_download'		=>	true,

					'default'			=>	array(

						// Config
						'rows_per_page'		=>	10,
						'group_index'		=>	0,
						'default'			=>	array(),

						// Columns
						'columns' => array(

							array('id' => 0, 'label' => __('Product', 'ws-form')),
							array('id' => 1, 'label' => __('Price', 'ws-form')),
						),

						// Group
						'groups' => array(

							array(

								'label' 		=> __('Products', 'ws-form'),
								'page'			=> 0,
								'disabled'		=> '',
								'mask_group'	=> '',

								// Rows (Only injected for a new data grid, blank for new groups)
								'rows' 		=> array(
									array(

										'id'		=> 1,
										'default'	=> '',
										'required'	=> '',
										'disabled'	=> '',
										'hidden'	=> '',
										'data'		=> array(__('Product 1', 'ws-form'), '1.23')
									),
									array(

										'id'		=> 2,
										'default'	=> '',
										'required'	=> '',
										'disabled'	=> '',
										'hidden'	=> '',
										'data'		=> array(__('Product 2', 'ws-form'), '2.34')
									),
									array(

										'id'		=> 3,
										'default'	=> '',
										'disabled'	=> '',
										'required'	=> '',
										'hidden'	=> '',
										'data'		=> array(__('Product 3', 'ws-form'), '3.45')
									)
								)
							)
						)
					)
				),

				'radio_price_field_label'		=> array(

					'label'						=>	__('Product Name', 'ws-form'),
					'type'						=>	'data_grid_field',
					'data_grid'					=>	'data_grid_radio_price',
					'default'					=>	0
				),

				'radio_price_field_value'	=> array(

					'label'						=>	__('Price', 'ws-form'),
					'type'						=>	'data_grid_field',
					'data_grid'					=>	'data_grid_radio_price',
					'default'					=>	1,
					'html_encode'				=>	true,
					'price'						=>	true
				),

				'price_radio_cascade'				=> array(

					'label'						=>	__('Cascade', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'',
					'help'						=>	__('Filter this data grid using a value from another field.', 'ws-form')
				),

				'price_radio_cascade_field_id'		=> array(

					'label'						=>	__('Filter Value', 'ws-form'),
					'type'						=>	'select',
					'default'					=>	'',
					'options'					=>	'fields',
					'options_blank'				=>	__('Select...', 'ws-form'),
					'fields_filter_type'		=>	array('select', 'price_select', 'radio', 'price_radio'),
					'help'						=>	__('Select the field to use as the filter value.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'price_radio_cascade',
							'meta_value'		=>	'on'
						)
					)
				),

				'price_radio_cascade_field_filter'	=> array(

					'label'						=>	__('Filter Column', 'ws-form'),
					'type'						=>	'data_grid_field',
					'data_grid'					=>	'data_grid_select',
					'default'					=>	0,
					'help'						=>	__('Select the column to filter with the filter value.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'price_radio_cascade',
							'meta_value'		=>	'on'
						)
					)
				),

				// Email
				'exclude_email'	=> array(

					'label'						=>	__('Exclude from emails', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'',
					'help'						=>	__('If checked, this field will not appear in emails containing the #email_submission variable.', 'ws-form')
				),

				'exclude_email_on'	=> array(

					'label'						=>	__('Exclude from emails', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'on',
					'help'						=>	__('If checked, this field will not appear in emails containing the #email_submission variable.', 'ws-form'),
					'key'						=>	'exclude_email'
				),

				// Custom attributes
				'custom_attributes'	=> array(

					'type'						=>	'repeater',
					'help'						=>	__('Add additional attributes to this field type.', 'ws-form'),
					'meta_keys'					=>	array(

						'custom_attribute_name',
						'custom_attribute_value'
					)
				),

				// Custom attributes - Name
				'custom_attribute_name'	=> array(

					'label'							=>	__('Name', 'ws-form'),
					'type'							=>	'text'
				),

				// Custom attributes - Value
				'custom_attribute_value'	=> array(

					'label'							=>	__('Value', 'ws-form'),
					'type'							=>	'text'
				),
				// No duplicates
				'dedupe'	=> array(

					'label'						=>	__('No Duplicates', 'ws-form'),
					'type'						=>	'checkbox',
					'help'						=>	__('Do not allow duplicate values for this field.', 'ws-form')
				),

				// No duplications - Message
				'dedupe_message'	=> array(

					'label'						=>	__('Duplication Message', 'ws-form'),
					'placeholder'				=>	__('The value entered for #label_lowercase has already been used.', 'ws-form'),
					'type'						=>	'textarea',
					'help'						=>	__('Enter a message to be shown if a duplicate value is entered for this field. Leave blank for the default message.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'dedupe',
							'meta_value'		=>	'on'
						)
					)
				),

				// Hidden (Never rendered but either have default values or are special attributes)

				'breakpoint'			=> array(

					'default'					=>	25
				),

				'tab_index'				=> array(

					'default'					=>	0
				),

				'list'					=> array(

					'mask'						=>	'list="#value"',
					'mask_disregard_on_empty'	=>	true,
					'mask_disregard_on_zero'	=>	false,
				),

				'aria_label'			=> array(

					'label'						=>	__('ARIA Label', 'ws-form'),
					'mask'						=>	'aria-label="#value"',
					'mask_disregard_on_empty'	=>	true,
					'mask_placeholder'			=>	'#label',
					'compatibility_id'			=>	'wai-aria',
					'select_list'				=>	true
				),

				'aria_labelledby'		=> array(

					'mask'						=>	'aria-labelledby="#value"',
					'mask_disregard_on_empty'	=>	true
				),

				'aria_describedby'		=> array(

					'mask'						=>	'aria-describedby="#value"',
					'mask_disregard_on_empty'	=>	true
				),

				'class'					=> array(

					'mask'						=>	'class="#value"',
					'mask_disregard_on_empty'	=>	true,
				),

				'default'						=> array(

					'mask'						=>	'#value',
					'mask_disregard_on_empty'	=>	true,
				)
			);


			// Apply filter
			$meta_keys = apply_filters('wsf_config_meta_keys', $meta_keys, $form_id);

			// Public parsing (To cut down on only output needed to render form
			if($public) {

				$public_attributes_public = array('key' => 'k', 'mask' => 'm', 'mask_disregard_on_empty' => 'e', 'mask_disregard_on_zero' => 'z', 'mask_placeholder' => 'p', 'html_encode' => 'h', 'price' => 'pr', 'default' => 'd');

				foreach($meta_keys as $key => $meta_key) {

					$meta_key_keep = false;

					foreach($public_attributes_public as $attribute => $attribute_public) {

						if(isset($meta_keys[$key][$attribute])) {

							$meta_key_keep = true;
							break;
						}
					}

					// Remove this meta key from public if it doesn't contain the keys we want for public
					if(!$meta_key_keep) { unset($meta_keys[$key]); }
				}

				$meta_keys_new = array();

				foreach($meta_keys as $key => $meta_key) {

					$meta_key_source = $meta_keys[$key];
					$meta_key_new = array();

					foreach($public_attributes_public as $attribute => $attribute_public) {

						if(isset($meta_key_source[$attribute])) {

							unset($meta_key_new[$attribute]);
							$meta_key_new[$attribute_public] = $meta_key_source[$attribute];
						}
					}

					$meta_keys_new[$key] = $meta_key_new;
				}

				$meta_keys = $meta_keys_new;
			}

			// Parse compatibility meta_keys
			if(!$public) {

				foreach($meta_keys as $key => $meta_key) {

					if(isset($meta_key['compatibility_id'])) {

						$meta_keys[$key]['compatibility_url'] = str_replace('#compatibility_id', $meta_key['compatibility_id'], WS_FORM_COMPATIBILITY_MASK);
						unset($meta_keys[$key]['compatibility_id']);
					}
				}
			}

			// Cache
			self::$meta_keys[$public] = $meta_keys;

			return $meta_keys;
		}

		// SVG - Logo
		public static function get_logo_svg() {

			return '<svg id="wsf_logo" viewBox="0 0 1500 428"><style>.st0{fill:#002d5d}.st1{fill:#a7a8aa}</style><path class="st0" d="M215.2 422.9l-44.3-198.4c-.4-1.4-.7-3-1-4.6-.3-1.6-3.4-18.9-9.3-51.8h-.6l-4.1 22.9-6.8 33.5-45.8 198.4H69.7L0 130.1h28.1L68 300.7l18.6 89.1h1.8c3.5-25.7 9.3-55.6 17.1-89.6l39.9-170H175l40.2 170.6c3.1 12.8 8.8 42.5 16.8 89.1h1.8c.6-5.9 3.5-20.9 8.7-44.8 5.2-23.9 21.9-95.5 50.1-214.8h27.8l-72.1 292.8h-33.1zM495 349.5c0 24.7-7.1 44-21.3 57.9-14.2 13.9-34.7 20.9-61.5 20.9-14.6 0-27.4-1.7-38.4-5.1-11-3.4-19.6-7.2-25.7-11.3l12.3-21.3c8.3 5.1 5.9 3.6 16.6 7.4 12 4.2 24.3 6.1 36.9 6.1 16.5 0 29.6-4.9 39-14.8 9.5-9.9 14.2-23.1 14.2-39.7 0-13-3.4-23.9-10.2-32.8-6.8-8.9-19.8-19-38.9-30.4-21.9-12.6-36.8-22.7-44.8-30.2-8-7.6-14.2-16-18.6-25.4-4.4-9.4-6.6-20.5-6.6-33.5 0-21.1 7.8-38.5 23.3-52.2 15.6-13.8 35.4-20.6 59.4-20.6 25.8 0 45.2 6.7 62.6 17.8L481 163.6c-16.2-9.9-33.3-14.8-51.4-14.8-16.6 0-29.8 4.5-39.6 13.4-9.9 8.9-14.8 20.6-14.8 35.2 0 13 3.3 23.8 10 32.5s20.9 19.3 42.6 31.7c21.3 12.8 35.9 23 43.7 30.6 7.9 7.6 13.7 16.1 17.6 25.4 4 9.2 5.9 19.9 5.9 31.9z"/><path class="st1" d="M643.8 152.8h-50.2V423h-27.8V152.8H525l.2-22.3h40.3l.3-25.5c0-37.2 3.6-60.9 13.4-77.2C589.5 10.7 606.6 0 630.5 0h28.9v23.6c-6.4 0-18.9.2-27.3.4-13.9.2-20.1 4.5-25.1 9.7-4.9 5.2-7.5 11.5-9.9 23.2-2.4 11.7-3.5 27.9-3.5 48.6v24.6h50.2v22.7zM857.1 275.8c0 49.3-8.5 87-25.6 113.2-17 26.2-41.4 39.3-73.1 39.3-31.3 0-55.3-13.1-72-39.3-16.7-26.2-25-63.9-25-113.2 0-100.9 32.7-151.4 98.1-151.4 30.7 0 54.7 13.2 71.8 39.7 17.2 26.4 25.8 63.7 25.8 111.7zm-166.4 0c0 42.3 5.5 74.2 16.6 95.8 11 21.6 28.3 32.4 51.7 32.4 45.9 0 68.9-42.7 68.9-128.2 0-84.7-23-127.1-68.9-127.1-24 0-41.4 10.6-52.2 31.8-10.7 21.3-16.1 53.1-16.1 95.3zM901.8 196.5c0-35.5 42.9-71.7 88.5-72 30.9-.3 42 8.6 53.2 13.7l-13.9 21.6c-9.7-5.1-18.8-9.2-39.9-9.9-13.3-.4-24.1 1.4-35.9 9.3-9.7 6.4-20.4 12.9-23.6 40.8-2.2 19-.8 45.9-.8 67.8V423h-28.1M1047.6 191.4c5.6-48.2 49.8-67.2 80.6-67.2 17.7 0 39.6 6.4 50.2 14.5 9.5 7.2 14.7 13.4 20.3 32.2 7.7-18 13.9-23.4 25.1-31.3 11.2-7.9 25.8-14.9 43.7-14.9 24.2 0 48.4 7.5 62.9 28.5 11.6 16.7 16.8 41 16.8 78.4V423h-27.8V223.5c.7-56.9-14.3-75.2-52-75.2-18.7 0-32.2 4.7-42.2 21.9-9.8 17-14.3 47.9-14.3 81.3v171.4h-27.8V223.5c0-24.8-3.8-43.3-11.5-55.5s-26.7-18.6-42.8-18.6c-21.3 0-35.6 10.4-45.3 28-9.7 17.6-8.6 45.1-8.6 84.6v160.9h-28.1M1467.2 109h-2.1l.4-28.5-6.1-.1v-2l14.3.2v2l-6.1-.1-.4 28.5zM1487.1 109.3l-7.8-27.8h-.2c.1 2.9.1 4.8.1 5.5l-.3 22.2h-2l.4-30.4h3l6.6 23.4c.6 2.1 1 3.8 1.1 5.3h.2c.2-1 .6-2.8 1.4-5.2l7.2-23.2h3.1l-.4 30.4h-2.1l.3-22c0-.9.1-2.7.3-5.7h-.2l-8.5 27.5h-2.2z"/><circle class="st1" cx="1412.6" cy="149.4" r="25.3"/><circle class="st1" cx="1412.6" cy="273" r="25.3"/><circle class="st1" cx="1412.6" cy="395" r="25.3"/></svg>';
		}

		// SVG - Icon 24 x 24 pixel
		public static function get_icon_24_svg($id = '') {

			$return_value = false;

			switch($id) {

				case 'bp-100' :

					$return_value = '<path fill="#444" d="M21 2H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h7l-2 3v1h8v-1l-2-3h7c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 12H3V4h18v10z"></path>';
					break;

				case 'bp-125' :

					$return_value = '<path fill="#444" d="M21 3H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h5v2h8v-2h5c1.1 0 1.99-.9 1.99-2L23 5c0-1.1-.9-2-2-2zm0 14H3V5h18v12z"></path>';
					break;

				case 'bp-25' :

					$return_value = '<path fill="#444" d="M15.5 1h-8C6.12 1 5 2.12 5 3.5v17C5 21.88 6.12 23 7.5 23h8c1.38 0 2.5-1.12 2.5-2.5v-17C18 2.12 16.88 1 15.5 1zm-4 21c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm4.5-4H7V4h9v14z"></path>';
					break;

				case 'bp-50' :

					$return_value = '<path fill="#444" d="M18.5 0h-14C3.12 0 2 1.12 2 2.5v19C2 22.88 3.12 24 4.5 24h14c1.38 0 2.5-1.12 2.5-2.5v-19C21 1.12 19.88 0 18.5 0zm-7 23c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm7.5-4H4V3h15v16z"></path>';
					break;

				case 'bp-75' :

					$return_value = '<path fill="#444" d="M20 18c1.1 0 1.99-.9 1.99-2L22 5c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2H0c0 1.1.9 2 2 2h20c1.1 0 2-.9 2-2h-4zM4 5h16v11H4V5zm8 14c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z"></path>';
					break;
			}

			// Apply filter
			$return_value = apply_filters('wsf_config_icon_24_svg', '<svg height="24" width="24" viewBox="0 0 24 24">' . $return_value . '</svg>', $id);

			return $return_value;
		}
		// SVG - Icon 16 x 16 pixel
		public static function get_icon_16_svg($id = '') {

			$return_value = false;

			switch($id) {

				case 'actions' :

					$return_value = '<path fill="#444" d="M7.99 0l-7.010 9.38 6.020-0.42-4.96 7.040 12.96-10-7.010 0.47 7.010-6.47h-7.010z"></path>';
					break;

				case 'asterisk' :

					$return_value = '<path fill="#444" d="M15.9 5.7l-2-3.4-3.9 2.2v-4.5h-4v4.5l-4-2.2-2 3.4 3.9 2.3-3.9 2.3 2 3.4 4-2.2v4.5h4v-4.5l3.9 2.2 2-3.4-4-2.3z"></path>';
					break;

				case 'button' :

					$return_value = '<path d="M15 12h-14c-0.6 0-1-0.4-1-1v-6c0-0.6 0.4-1 1-1h14c0.6 0 1 0.4 1 1v6c0 0.6-0.4 1-1 1z" fill="#444"></path>';
					break;

				case 'calculator' :

					$return_value = '<path d="M14.1 1.7v12.7c-.1.4-.2.8-.5 1-.4.4-.8.6-1.3.6h-9c-.4-.1-.8-.3-1.1-.6-.2-.4-.4-.7-.4-1.2V1.9c0-.3.1-.7.3-1 .3-.5.7-.8 1.2-.9h9.1c.2 0 .4.1.6.2.5.3.9.7 1 1.3.1.1.1.2.1.2zM7.9 5.6H12.3c.3-.1.5-.3.5-.6V1.9c0-.1 0-.2-.1-.3-.1-.2-.3-.3-.6-.3H3.6c-.3 0-.6.2-.6.6V5c0 .2.1.3.2.4.1.1.3.2.5.2h4.2zm3.7 3h.6c.3 0 .5-.3.6-.5v-.7-.1c-.1-.3-.3-.5-.6-.5H11h-.1c-.2 0-.5.3-.5.5V8.1c.1.3.3.5.6.5h.6zm0 3.1h.7c.3 0 .5-.3.6-.5v-.7c0-.3-.3-.6-.6-.6h-1.2-.1c-.3 0-.5.3-.5.5V11.2c.1.3.3.4.6.4.1.1.3.1.5.1zm0 3.1h.8c.3-.1.5-.3.5-.5v-.7c0-.3-.3-.6-.6-.6h-1.2-.1c-.2 0-.5.3-.5.5v.8c0 .3.3.5.6.5h.5zM4.3 8.6h.6c.3 0 .6-.3.6-.6v-.7c0-.3-.3-.6-.6-.6H3.7c-.4.1-.7.4-.7.7v.7c.1.3.3.5.6.5h.7zm0 3.1h.6c.3 0 .6-.3.6-.6v-.7c0-.3-.3-.6-.6-.6H3.6c-.3.1-.6.4-.6.7v.7c.1.3.3.5.6.5h.7zm3.6 3.1h.6c.3 0 .6-.3.6-.6v-.6c0-.3-.3-.6-.6-.6H7.2c-.3 0-.6.3-.6.6V14.4c.1.3.3.5.6.5.3-.1.5-.1.7-.1zm0-3.1h.6c.3 0 .6-.3.6-.6v-.6-.1c0-.3-.3-.5-.6-.5H7.2c-.3 0-.6.3-.6.6v.7c0 .3.3.6.6.6.3-.1.5-.1.7-.1zm-3.6 3.1H5c.3 0 .6-.3.6-.6v-.7c0-.3-.3-.6-.6-.6H3.7h-.1c-.4.1-.6.4-.6.7v.7c.1.3.3.5.6.5h.7zm3.6-6.2h.7c.3 0 .5-.2.6-.5v-.4-.4c0-.3-.3-.6-.6-.6H7.4h-.1c-.3 0-.5.3-.5.6V8.1c.1.3.3.5.6.5h.5z"/><path d="M3.5 0h-.2c-.6.2-1 .5-1.2 1-.2.3-.3.6-.3 1v12.3c0 .4.2.8.4 1.2.3.3.6.5 1.1.6h9c.5 0 1-.2 1.3-.6.3-.3.4-.6.5-1v-.1 1.7H1.8V.1C2.4 0 2.9 0 3.5 0zM14.1 1.7v-.2c-.1-.6-.5-1-1-1.3-.2-.1-.4-.2-.6-.2h1.7c-.1.6-.1 1.2-.1 1.7z"/><path d="M9.8 3.4v-.9c0-.3.2-.6.5-.6h1.3c.3 0 .5.2.6.5v2c0 .3-.2.5-.5.6h-1.3c-.3 0-.6-.2-.6-.5v-.1-1zm1.8.9V2.5h-1.2v1.8h1.2z"/><path d="M11.6 4.3h-1.2V2.5h1.2v1.8z"/>';
					break;

				case 'check' :

					$return_value = '<path fill="#444" d="M7.3 14.2l-7.1-5.2 1.7-2.4 4.8 3.5 6.6-8.5 2.3 1.8z"></path>';
					break;

				case 'checkbox' :

					$return_value = '<path d="M14 6.2v7.8h-12v-12h10.5l1-1h-12.5v14h14v-9.8z" fill="#444"></path><path d="M7.9 10.9l-4.2-4.2 1.5-1.4 2.7 2.8 6.7-6.7 1.4 1.4z" fill="#444"></path>';
					break;

				case 'clear' :

					$return_value = '<path fill="#444" d="M8.1 14l6.4-7.2c0.6-0.7 0.6-1.8-0.1-2.5l-2.7-2.7c-0.3-0.4-0.8-0.6-1.3-0.6h-1.8c-0.5 0-1 0.2-1.4 0.6l-6.7 7.6c-0.6 0.7-0.6 1.9 0.1 2.5l2.7 2.7c0.3 0.4 0.8 0.6 1.3 0.6h11.4v-1h-7.9zM6.8 13.9c0 0 0-0.1 0 0l-2.7-2.7c-0.4-0.4-0.4-0.9 0-1.3l3.4-3.9h-1l-3 3.3c-0.6 0.7-0.6 1.7 0.1 2.4l2.3 2.3h-1.3c-0.2 0-0.4-0.1-0.6-0.2l-2.8-2.8c-0.3-0.3-0.3-0.8 0-1.1l3.5-3.9h1.8l3.5-4h1l-3.5 4 3.1 3.7-3.5 4c-0.1 0.1-0.2 0.1-0.3 0.2z"></path>';
					break;

				case 'clone' :

					$return_value = '<path d="M6 0v3h3z" fill="#444"></path><path d="M9 4h-4v-4h-5v12h9z" fill="#444"></path><path d="M13 4v3h3z" fill="#444"></path><path d="M12 4h-2v9h-3v3h9v-8h-4z" fill="#444"></path>';
					break;

				case 'close-circle' :

					$return_value = '<path d="M8,0 C3.6,0 0,3.6 0,8 C0,12.4 3.6,16 8,16 C12.4,16 16,12.4 16,8 C16,3.6 12.4,0 8,0 Z" fill="#444"></path><polygon fill="#FFFFFF" points="12.2 10.8 10.8 12.2 8 9.4 5.2 12.2 3.8 10.8 6.6 8 3.8 5.2 5.2 3.8 8 6.6 10.8 3.8 12.2 5.2 9.4 8 12.2 10.8"></polygon>';
					break;

				case 'color' :

					$return_value = '<path d="M15 1c-1.8-1.8-3.7-0.7-4.6 0.1-0.4 0.4-0.7 0.9-0.7 1.5v0c0 1.1-1.1 1.8-2.1 1.5l-0.1-0.1-0.7 0.8 0.7 0.7-6 6-0.8 2.3-0.7 0.7 1.5 1.5 0.8-0.8 2.3-0.8 6-6 0.7 0.7 0.7-0.6-0.1-0.2c-0.3-1 0.4-2.1 1.5-2.1v0c0.6 0 1.1-0.2 1.4-0.6 0.9-0.9 2-2.8 0.2-4.6zM3.9 13.6l-2 0.7-0.2 0.1 0.1-0.2 0.7-2 5.8-5.8 1.5 1.5-5.9 5.7z" fill="#444"></path>';
					break;

				case 'conditional' :

					$return_value = '<path fill="#444" d="M14 13v-1c0-0.2 0-4.1-2.8-5.4-2.2-1-2.2-3.5-2.2-3.6v-3h-2v3c0 0.1 0 2.6-2.2 3.6-2.8 1.3-2.8 5.2-2.8 5.4v1h-2l3 3 3-3h-2v-1c0 0 0-2.8 1.7-3.6 1.1-0.5 1.8-1.3 2.3-2 0.5 0.8 1.2 1.5 2.3 2 1.7 0.8 1.7 3.6 1.7 3.6v1h-2l3 3 3-3h-2z" transform="translate(8.000000, 8.000000) rotate(-180.000000) translate(-8.000000, -8.000000)"></path>';
					break;

				case 'contract' :

					$return_value = '<path fill="#444" d="M12 0h-12v12l1-1v-10h10z"></path><path fill="#444" d="M4 16h12v-12l-1 1v10h-10z"></path><path fill="#444" d="M7 9h-5l1.8 1.8-3.8 3.8 1.4 1.4 3.8-3.8 1.8 1.8z"></path><path fill="#444" d="M16 1.4l-1.4-1.4-3.8 3.8-1.8-1.8v5h5l-1.8-1.8z"></path>';
					break;

				case 'datetime' :

					$return_value = '<path d="M3 0h1v3h-1v-3z" fill="#444"></path><path d="M11 0h1v3h-1v-3z" fill="#444"></path><path d="M6.6 14h-5.6v-8h13v0.6c0.4 0.2 0.7 0.4 1 0.7v-6.3h-2v3h-3v-3h-5v3h-3v-3h-2v14h7.3c-0.3-0.3-0.5-0.6-0.7-1z" fill="#444"></path><path d="M14 12h-3v-3h1v2h2z" fill="#444"></path><path d="M11.5 8c1.9 0 3.5 1.6 3.5 3.5s-1.6 3.5-3.5 3.5-3.5-1.6-3.5-3.5 1.6-3.5 3.5-3.5zM11.5 7c-2.5 0-4.5 2-4.5 4.5s2 4.5 4.5 4.5 4.5-2 4.5-4.5-2-4.5-4.5-4.5v0z" fill="#444"></path>';
					break;

				case 'delete' :

					$return_value = '<path d="M13 3s0-0.51-2-0.8v-0.7c-0.017-0.832-0.695-1.5-1.53-1.5-0 0-0 0-0 0h-3c-0.815 0.017-1.47 0.682-1.47 1.5 0 0 0 0 0 0v0.7c-0.765 0.068-1.452 0.359-2.007 0.806l-0.993-0.006v1h12v-1h-1zM6 1.5c0.005-0.274 0.226-0.495 0.499-0.5l3.001-0c0 0 0.001 0 0.001 0 0.282 0 0.513 0.22 0.529 0.499l0 0.561c-0.353-0.042-0.763-0.065-1.178-0.065-0.117 0-0.233 0.002-0.349 0.006-0.553-0-2.063-0-2.503 0.070v-0.57z" fill="#444"></path><path d="M2 5v1h1v9c1.234 0.631 2.692 1 4.236 1 0.002 0 0.003 0 0.005 0h1.52c0.001 0 0.003 0 0.004 0 1.544 0 3.002-0.369 4.289-1.025l-0.054-8.975h1v-1h-12zM6 13.92q-0.51-0.060-1-0.17v-6.75h1v6.92zM9 14h-2v-7h2v7zM11 13.72c-0.267 0.070-0.606 0.136-0.95 0.184l-0.050-6.904h1v6.72z" fill="#444"></path>';
					break;

				case 'disabled' :

					$return_value = '<path fill="#444" d="M8 0c-4.4 0-8 3.6-8 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zM8 2c1.3 0 2.5 0.4 3.5 1.1l-8.4 8.4c-0.7-1-1.1-2.2-1.1-3.5 0-3.3 2.7-6 6-6zM8 14c-1.3 0-2.5-0.4-3.5-1.1l8.4-8.4c0.7 1 1.1 2.2 1.1 3.5 0 3.3-2.7 6-6 6z"></path>';
					break;

				case 'divider' :

					$return_value = '<path fill="#444" d="M0 7.38h16v1.44H0z"/>';
					break;

				case 'down' :

					$return_value = '<path d="M15 3H1l7 10 7-10z"/>';
					break;

				case 'download' :

					$return_value = '<path fill="#444" d="M0 14h16v2h-16v-2z"></path><path fill="#444" d="M8 13l5-5h-3v-8h-4v8h-3z"></path>';
					break;

				case 'edit' :

					$return_value = '<path d="M16 9v-2l-1.7-0.6c-0.2-0.6-0.4-1.2-0.7-1.8l0.8-1.6-1.4-1.4-1.6 0.8c-0.5-0.3-1.1-0.6-1.8-0.7l-0.6-1.7h-2l-0.6 1.7c-0.6 0.2-1.2 0.4-1.7 0.7l-1.6-0.8-1.5 1.5 0.8 1.6c-0.3 0.5-0.5 1.1-0.7 1.7l-1.7 0.6v2l1.7 0.6c0.2 0.6 0.4 1.2 0.7 1.8l-0.8 1.6 1.4 1.4 1.6-0.8c0.5 0.3 1.1 0.6 1.8 0.7l0.6 1.7h2l0.6-1.7c0.6-0.2 1.2-0.4 1.8-0.7l1.6 0.8 1.4-1.4-0.8-1.6c0.3-0.5 0.6-1.1 0.7-1.8l1.7-0.6zM8 12c-2.2 0-4-1.8-4-4s1.8-4 4-4 4 1.8 4 4-1.8 4-4 4z" fill="#444"></path><path d="M10.6 7.9c0 1.381-1.119 2.5-2.5 2.5s-2.5-1.119-2.5-2.5c0-1.381 1.119-2.5 2.5-2.5s2.5 1.119 2.5 2.5z" fill="#444"></path>';
					break;

				case 'email' :

					$return_value = '<path d="M0 3h16v2.4l-8 4-8-4z" fill="#444"></path><path d="M0 14l5.5-4.8 2.5 1.4 2.5-1.4 5.5 4.8z" fill="#444"></path><path d="M4.6 8.8l-4.6-2.3v6.5z" fill="#444"></path><path d="M11.4 8.8l4.6-2.3v6.5z" fill="#444"></path>';
					break;

				case 'exchange' :

					$return_value = '<path fill="#444" d="M16 5v2h-13v2l-3-3 3-3v2z"></path><path fill="#444" d="M0 12v-2h13v-2l3 3-3 3v-2z"></path>';
					break;

				case 'expand' :

					$return_value = '<path fill="#444" d="M11 2h-9v9l1-1v-7h7z"></path><path fill="#444" d="M5 14h9v-9l-1 1v7h-7z"></path><path fill="#444" d="M16 0h-5l1.8 1.8-4.5 4.5 1.4 1.4 4.5-4.5 1.8 1.8z"></path><path fill="#444" d="M7.7 9.7l-1.4-1.4-4.5 4.5-1.8-1.8v5h5l-1.8-1.8z"></path>';
					break;

				case 'file' :

					$return_value = '<path d="M2.7 15.3c-0.7 0-1.4-0.3-1.9-0.8-0.9-0.9-1.2-2.5 0-3.7l8.9-8.9c1.4-1.4 3.8-1.4 5.2 0s1.4 3.8 0 5.2l-7.4 7.4c-0.2 0.2-0.5 0.2-0.7 0s-0.2-0.5 0-0.7l7.4-7.4c1-1 1-2.7 0-3.7s-2.7-1-3.7 0l-8.9 8.9c-0.8 0.8-0.6 1.7 0 2.2 0.6 0.6 1.5 0.8 2.2 0l8.9-8.9c0.2-0.2 0.2-0.5 0-0.7s-0.5-0.2-0.7 0l-7.4 7.4c-0.2 0.2-0.5 0.2-0.7 0s-0.2-0.5 0-0.7l7.4-7.4c0.6-0.6 1.6-0.6 2.2 0s0.6 1.6 0 2.2l-8.9 8.9c-0.6 0.4-1.3 0.7-1.9 0.7z" fill="#444"></path>';
					break;

				case 'file-code' :

					$return_value = '<path fill="#444" d="M10 0h-8v16h12v-12l-4-4zM9 5h4v10h-10v-14h6v4zM10 4v-3l3 3h-3z"></path><path fill="#444" d="M6.2 13h-0.7l-2-2.5 2-2.5h0.7l-2 2.5z"></path><path fill="#444" d="M9.8 13h0.7l2-2.5-2-2.5h-0.7l2 2.5z"></path><path fill="#444" d="M6.7 14h0.6l2.1-7h-0.8z"></path>';
					break;

				case 'file-default' :

					$return_value = '<path fill="#444" d="M10 0h-8v16h12v-12l-4-4zM9 5h4v10h-10v-14h6v4zM10 4v-3l3 3h-3z"></path>';
					break;

				case 'file-font' :

					$return_value = '<path fill="#444" d="M10 0h-8v16h12v-12l-4-4zM9 5h4v10h-10v-14h6v4zM10 4v-3l3 3h-3z"></path><path fill="#444" d="M5 7v2h2v5h2v-5h2v-2z"></path>';
					break;

				case 'file-movie' :

					$return_value = '<path fill="#444" d="M10 0h-8v16h12v-12l-4-4zM9 5h4v10h-10v-14h6v4zM10 4v-3l3 3h-3z"></path><path fill="#444" d="M10 10v-2h-6v5h6v-2l2 2v-5z"></path>';
					break;

				case 'file-picture' :

					$return_value = '<path fill="#444" d="M10 0h-8v16h12v-12l-4-4zM9 5h4v10h-10v-14h6v4zM10 4v-3l3 3h-3z"></path><path fill="#444" d="M4 11.5v2.5h8v-1.7c0 0 0.1-1.3-1.3-1.5-1.3-0.2-1.5 0.4-2.5 0.5-0.8 0-0.6-1.3-2.2-1.3-1.2 0-2 1.5-2 1.5z"></path><path fill="#444" d="M12 8.5c0 0.828-0.672 1.5-1.5 1.5s-1.5-0.672-1.5-1.5c0-0.828 0.672-1.5 1.5-1.5s1.5 0.672 1.5 1.5z"></path>';
					break;

				case 'file-presentation' :

					$return_value = '<path fill="#444" d="M10 0h-8v16h12v-12l-4-4zM13 15h-10v-14h6v4h4v10zM10 4v-3l3 3h-3z"></path><path fill="#444" d="M9 6h-2v1h-3v6h2v1h1v-1h2v1h1v-1h2v-6h-3v-1zM11 8v4h-6v-4h6z"></path><path fill="#444" d="M7 9v2l2-1z"></path>';
					break;

				case 'file-sound' :

					$return_value = '<path fill="#444" d="M11.4 10.5c0 1.2-0.4 2.2-1 3l0.4 0.5c0.7-0.9 1.2-2.1 1.2-3.5s-0.5-2.6-1.2-3.5l-0.4 0.5c0.6 0.8 1 1.9 1 3z"></path><path fill="#444" d="M9.9 8l-0.4 0.5c0.4 0.5 0.7 1.2 0.7 2s-0.3 1.5-0.7 2l0.4 0.5c0.5-0.6 0.8-1.5 0.8-2.5s-0.3-1.8-0.8-2.5z"></path><path fill="#444" d="M9.1 9l-0.4 0.5c0.2 0.3 0.3 0.6 0.3 1s-0.1 0.7-0.3 1l0.4 0.5c0.3-0.4 0.5-0.9 0.5-1.5s-0.2-1.1-0.5-1.5z"></path><path fill="#444" d="M10 0h-8v16h12v-12l-4-4zM9 5h4v10h-10v-14h6v4zM10 4v-3l3 3h-3z"></path><path fill="#444" d="M6 9h-2v3h2l2 2v-7z"></path>';
					break;

				case 'file-table' :

					$return_value = '<path fill="#444" d="M10 0h-8v16h12v-12l-4-4zM9 5h4v10h-10v-14h6v4zM10 4v-3l3 3h-3z"></path><path fill="#444" d="M4 7v6h8v-6h-8zM6 12h-1v-1h1v1zM6 10h-1v-1h1v1zM9 12h-2v-1h2v1zM9 10h-2v-1h2v1zM11 12h-1v-1h1v1zM11 10h-1v-1h1v1z"></path>';
					break;

				case 'file-text' :

					$return_value = '<path fill="#444" d="M10 0h-8v16h12v-12l-4-4zM9 5h4v10h-10v-14h6v4zM10 4v-3l3 3h-3z"></path><path fill="#444" d="M4 7h8v1h-8v-1z"></path><path fill="#444" d="M4 9h8v1h-8v-1z"></path><path fill="#444" d="M4 11h8v1h-8v-1z"></path>';
					break;

				case 'file-zip' :

					$return_value = '<path fill="#444" d="M10 0h-8v16h12v-12l-4-4zM9 15h-4v-2.8l0.7-2.2h2.4l0.9 2.2v2.8zM13 15h-3v-3l-1-3h-2v-1h-2v1l-1 3v3h-1v-14h4v1h2v1h-2v1h2v1h4v10zM10 4v-3l3 3h-3z"></path><path fill="#444" d="M5 6h2v1h-2v-1z"></path><path fill="#444" d="M5 2h2v1h-2v-1z"></path><path fill="#444" d="M5 4h2v1h-2v-1z"></path><path fill="#444" d="M7 5h2v1h-2v-1z"></path><path fill="#444" d="M7 7h2v1h-2v-1z"></path><path fill="#444" d="M6 12h2v2h-2v-2z"></path>';
					break;

				case 'first' :

					$return_value = '<path fill="#444" d="M14 15v-14l-10 7z"></path><path fill="#444" d="M2 1h2v14h-2v-14z"></path>';
					break;

				case 'group' :

					$return_value = '<path fill="#444" d="M14 4v-2h-14v12h16v-10h-2zM10 3h3v1h-3v-1zM6 3h3v1h-3v-1zM15 13h-14v-10h4v2h10v8z"></path>';
					break;

				case 'hidden' :

					$return_value = '<path d="M12.9 5.2l-0.8 0.8c1.7 0.9 2.5 2.3 2.8 3-0.7 0.9-2.8 3.1-7 3.1-0.7 0-1.2-0.1-1.8-0.2l-0.8 0.8c0.8 0.3 1.7 0.4 2.6 0.4 5.7 0 8.1-4 8.1-4s-0.6-2.4-3.1-3.9z" fill="#444"></path><path d="M12 7.1c0-0.3 0-0.6-0.1-0.8l-4.8 4.7c0.3 0 0.6 0.1 0.9 0.1 2.2 0 4-1.8 4-4z" fill="#444"></path><path d="M15.3 0l-4.4 4.4c-0.8-0.2-1.8-0.4-2.9-0.4-6.7 0-8 5.1-8 5.1s1 1.8 3.3 3l-3.3 3.2v0.7h0.7l15.3-15.3v-0.7h-0.7zM4 11.3c-1.6-0.7-2.5-1.8-2.9-2.3 0.3-0.7 1.1-2.2 3.1-3.2-0.1 0.4-0.2 0.8-0.2 1.3 0 1.1 0.5 2.2 1.3 2.9l-1.3 1.3zM6.2 7.9l-1 0.2c0 0-0.3-0.5-0.3-1.2 0-0.8 0.4-1.5 0.4-1.5 0.5-0.3 1.3-0.3 1.3-0.3s-0.5 0.9-0.5 1.7c-0.1 0.7 0.1 1.1 0.1 1.1z" fill="#444"></path>';
					break;

				case 'html' :

					$return_value = '<path d="M5.2 14l4.5-12h1.1l-4.5 12z" fill="#444"></path><path d="M11.1 13h1.2l3.7-5-3.7-5h-1.3l3.8 5z" fill="#444"></path><path d="M4.9 13h-1.2l-3.7-5 3.7-5h1.3l-3.8 5z" fill="#444"></path>';
					break;

				case 'info-circle' :

					$return_value = '<path d="M8,0 C3.6,0 0,3.6 0,8 C0,12.4 3.6,16 8,16 C12.4,16 16,12.4 16,8 C16,3.6 12.4,0 8,0 Z" fill="#444"></path><polygon fill="#FFFFFF" points="9 13 7 13 7 6 9 6"></polygon><polygon fill="#FFFFFF" points="9 5 7 5 7 3 9 3"></polygon>';
					break;

				case 'last' :

					$return_value = '<path fill="#444" d="M2 1v14l10-7z"></path><path fill="#444" d="M12 1h2v14h-2v-14z"></path>';
					break;

				case 'legal' :

					$return_value = '<path d="M5.2 5.3c.5 1 .9 2 1.4 3.1-.1 0-.2-.1-.3-.1-.4-.1-.7-.2-1-.3-.1 0-.1 0-.2.1-.2.3-.3.7-.5 1.1 0 0 0 .1-.1.1-.4-.9-.8-1.7-1.2-2.6-.4.9-.8 1.7-1.2 2.6l-.3-.6-.3-.6C1.4 8 1.4 8 1.3 8l-1.2.3H0c.5-1 1-2 1.4-3.1-.1 0-.1-.1-.2-.1-.3-.1-.5-.3-.5-.7 0-.3-.1-.6-.3-.8-.2-.3-.2-.6 0-.9.2-.3.3-.6.3-1 0-.3.2-.5.4-.6.4-.1.7-.3.9-.6.2-.2.5-.3.7-.2.4.1.7.1 1.1 0 .3-.1.5 0 .7.2.2.3.5.5.8.6.3.1.5.4.5.7 0 .3.1.6.3.8.1.1.1.2.1.3.1.2.1.3 0 .5 0 .1-.1.2-.1.3-.1.2-.2.4-.2.7v.3c0 .2-.2.4-.4.5-.1 0-.2 0-.3.1zm-.1-2.2c0-1-.8-1.9-1.8-1.9-1.1 0-1.9.8-1.9 1.9 0 1 .8 1.9 1.8 1.9 1.1 0 1.9-.9 1.9-1.9zM2.8 9.2c-.1.2-.2.5-.2.8v5.6h9.8v-3.9c-.4.4-.7.8-1.1 1.2V14.4H3.9V14 9.3v-.1c-.3-.4-.4-.7-.6-1.1-.2.4-.3.7-.5 1.1zm6.2 2l1.4 1.4c1.6-1.6 3.2-3.3 4.8-4.9l-1.4-1.4C12.2 8 10.6 9.6 9 11.2zm2.3-3.5l1-1c.1-.1.1-.1.1-.2V2.3v-.1H6.6c.1.2.2.4.2.6.1.2 0 .4.1.6h4.3c0 1.4 0 2.9.1 4.3zm-6.2 3.4h3.2s.1 0 .1-.1c.3-.3.7-.6 1-.9.1-.1.1-.1.2-.1H5.1c-.1.3-.1.7 0 1.1zM6.6 5v1.2H10V5H6.6zm.2 2.6c.1.3.3.7.4 1 0 .1.1.1.1.1h2.6V7.5c-1 0-2 0-3.1.1zm8.7-.3c.1-.1.3-.2.4-.4.2-.2.1-.4 0-.6l-.8-.8c-.1-.1-.4-.2-.5 0l-.4.4c.4.5.9 1 1.3 1.4zm-7.1 5.1c0 .1 0 .1 0 0 .2.3.4.5.6.7h.1c.3-.1.6-.1.8-.2-.4-.5-.9-.9-1.3-1.4-.1.4-.1.6-.2.9zm-.2 1c.3-.1.5-.1.7-.2-.2-.2-.4-.4-.5-.6-.1.3-.2.5-.2.8z"/>';
					break;

				case 'markup-circle' :

					$return_value = '<path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zM6.3 6.2L3.3 8l3 1.8v1.9L1.9 8.8V7.2l4.3-2.9v1.9zM7.6 13h-.9L8.3 3h.9L7.6 13zm2-1.3V9.8l3-1.8-3-1.8V4.3L14 7.2v1.6l-4.4 2.9z" fill="#444"/>';
					break;

				case 'menu' :

					$return_value = '<path d="M0 1h16v3h-16v-3z" fill="#444"></path><path d="M0 6h16v3h-16v-3z" fill="#444"></path><path d="M0 11h16v3h-16v-3z" fill="#444"></path>';
					break;

				case 'minus' :

                    $return_value = '<path fill="#444" d="M2 7h12v2h-12v-2z"></path>';
                    break;

				case 'minus-circle' :

					$return_value = '<path d="M8,0 C3.6,0 0,3.6 0,8 C0,12.4 3.6,16 8,16 C12.4,16 16,12.4 16,8 C16,3.6 12.4,0 8,0 Z" fill="#444"></path><polygon fill="#FFFFFF" points="13 9 3 9 3 7 13 7"></polygon>';
					break;

				case 'next' :

					$return_value = '<path d="M3 1v14l10-7L3 1z"/>';
					break;

				case 'number' :

					$return_value = '<path d="M15 6v-2h-2.6l0.6-2.8-2-0.4-0.7 3.2h-3l0.7-2.8-2-0.4-0.7 3.2h-3.3v2h2.9l-0.9 4h-3v2h2.6l-0.6 2.8 2 0.4 0.7-3.2h3l-0.7 2.8 2 0.4 0.7-3.2h3.3v-2h-2.9l0.9-4h3zM9 10h-3l1-4h3l-1 4z" fill="#444"></path>';
					break;

				case 'password' :

					$return_value = '<path d="M16 5c0-0.6-0.4-1-1-1h-14c-0.6 0-1 0.4-1 1v6c0 0.6 0.4 1 1 1h14c0.6 0 1-0.4 1-1v-6zM15 11h-14v-6h14v6z" fill="#444"></path><path d="M6 8c0 0.552-0.448 1-1 1s-1-0.448-1-1c0-0.552 0.448-1 1-1s1 0.448 1 1z" fill="#444"></path><path d="M9 8c0 0.552-0.448 1-1 1s-1-0.448-1-1c0-0.552 0.448-1 1-1s1 0.448 1 1z" fill="#444"></path><path d="M12 8c0 0.552-0.448 1-1 1s-1-0.448-1-1c0-0.552 0.448-1 1-1s1 0.448 1 1z" fill="#444"></path>';
					break;

				case 'plus' :

					$return_value = '<path fill="#444" d="M14 7h-5v-5h-2v5h-5v2h5v5h2v-5h5v-2z"></path>';
					break;

				case 'plus-circle' :

					$return_value = '<path d="M8,0 C3.6,0 0,3.6 0,8 C0,12.4 3.6,16 8,16 C12.4,16 16,12.4 16,8 C16,3.6 12.4,0 8,0 Z" fill="#444"></path><polygon fill="#FFFFFF" points="13 9 9 9 9 13 7 13 7 9 3 9 3 7 7 7 7 3 9 3 9 7 13 7"></polygon>';
					break;

				case 'previous' :

					$return_value = '<path d="M14 15V1L4 8z"/>';
					break;

				case 'price' :

					$return_value = '<path d="M5.6 16h-.2c-.2 0-.3-.1-.5-.3l-1.3-1.3-3.3-3.3c-.1-.2-.3-.4-.3-.6 0-.3.1-.6.3-.9l.1-.1C2.6 7.2 4.9 5 7.1 2.7c.1-.1.2-.1.3-.1h2.5v.3c0 .2 0 .4.1.6-.1.3 0 .5 0 .7-.2.3-.2.5-.2.8 0 .6.5 1.1 1.1 1.2.4.1.8 0 1.1-.4.3-.3.4-.6.4-1s-.2-.7-.5-1v-.1c0-.2-.1-.4-.1-.6v-.5h.2c.2 0 .4 0 .5.1.1 0 .3.1.4.1.1.2.2.2.2.4s.1.3.1.5.1.4.1.7c0 .2 0 .4.1.6 0 .2 0 .4.1.6V8.2c0 .2 0 .4-.1.5 0 .1-.1.1-.1.2l-6.8 6.8c-.2.2-.4.3-.7.3h-.2z"/><path d="M16 3.2c0 .3 0 .7-.1 1-.1.9-.4 1.8-.7 2.6-.1.2-.2.3-.3.5-.1.2-.2.3-.4.3s-.3-.1-.4-.2c-.1-.2-.1-.4 0-.5.3-.5.5-1.1.7-1.7.1-.3.1-.6.2-1 0-.4.1-.8 0-1.2 0-.4-.1-.8-.4-1.2-.2-.5-.6-.7-1.1-.8-.4-.1-.8 0-1.1.1-.6.3-.9.7-1.1 1.3-.1.2-.1.5 0 .7 0 .3.1.6.1.8 0 .3.1.6.2.9.1.3-.1.6-.4.6-.2 0-.4-.1-.5-.4-.1-.3-.1-.7-.2-1-.1-.4-.1-.7-.1-1.1 0-.7.2-1.3.6-1.8.4-.5.8-.8 1.4-1 .3-.1.6-.1.9-.1.8 0 1.4.4 1.9.9.4.4.6.9.7 1.5.1.3.1.5.1.8z"/>';
					break;

				case 'progress' :

					$return_value = '<path d="M0 5v6h16v-6h-16zM15 10h-14v-4h14v4z" fill="#444"></path><path d="M2 7h7v2h-7v-2z" fill="#444"></path>';
					break;

				case 'publish' :

					$return_value = '<path fill="#444" d="M14.1 10.9c0-0.2 0-0.4 0-0.6 0-2.4-1.9-4.3-4.2-4.3-0.3 0-0.6 0-0.9 0.1v-2.1h2l-3-4-3 4h2v1.5c-0.4-0.2-0.9-0.3-1.3-0.3-1.6 0-2.9 1.2-2.9 2.8 0 0.3 0.1 0.6 0.2 0.9-1.6 0.2-3 1.8-3 3.5 0 1.9 1.5 3.6 3.3 3.6h10.3c1.4 0 2.4-1.4 2.4-2.6s-0.8-2.2-1.9-2.5zM13.6 15h-10.3c-1.2 0-2.3-1.2-2.3-2.5s1.1-2.5 2.3-2.5c0.1 0 0.3 0 0.4 0l1.3 0.3-0.8-1.2c-0.2-0.3-0.4-0.7-0.4-1.1 0-1 0.8-1.8 1.8-1.8 0.5 0 1 0.2 1.3 0.6v3.2h2v-2.8c0.3-0.1 0.6-0.1 0.9-0.1 1.8 0 3.2 1.5 3.2 3.3 0 0.3 0 0.6-0.1 0.9l-0.2 0.6h0.8c0.7 0 1.4 0.7 1.4 1.5 0.1 0.7-0.5 1.6-1.3 1.6z"></path>';
					break;

				case 'quantity' :

					$return_value = '<path d="M16 5.2c0 .2-.1.3-.1.5-.4 1.7-1.9 2.8-3.6 2.7-1.7-.1-3-1.3-3.2-3-.3-2 1.1-3.8 3.1-4 1.8-.1 3.5 1.2 3.8 3.2v.6zm-4 .3v1.1c0 .3.1.4.4.4h.3c.2 0 .3-.1.3-.4V5.5h1.2c.3 0 .4-.1.4-.4v-.3c0-.3-.1-.4-.4-.4h-1.1v-.2-1c-.1-.1-.2-.2-.4-.2h-.4c-.2 0-.3 0-.3.3v1.1h-1.2c-.2 0-.3.1-.3.3V5c0 .3.1.3.3.3.4.2.8.2 1.2.2z"/><path d="M11.8 9.2c.3 0 .7 0 1 .1-.1.4-.2.7-.3 1.1-.1.5-.2.6-.8.6H4c-.3.1-.5.4-.5.7 0 .3.3.6.6.6 0 0 0-.1.1-.1.3-.5.7-.8 1.4-.8.6 0 1 .3 1.3.9.1.1.1.1.2.1H9c.1 0 .1 0 .2-.1.3-.6.7-.9 1.4-.9.6 0 1.1.3 1.4.9 0 .1.1.1.2.1h.5c.3 0 .5.2.5.5s-.2.5-.5.5h-.5c-.1 0-.2 0-.2.1-.5 1-1.7 1.2-2.5.4-.1-.1-.2-.2-.2-.4 0-.1-.1-.1-.2-.1h-2c-.1 0-.1 0-.1.1-.3.6-.7.9-1.4.9-.6 0-1.1-.3-1.3-.9 0-.1-.1-.1-.2-.1-.7-.2-1.3-.7-1.4-1.4-.1-.8.3-1.6 1.1-1.9.1 0 .1 0 .2-.1-.3-.4-.4-.8-.6-1.3C2.9 6.9 2.3 5 1.7 3.1c0-.1-.1-.2-.2-.2-.4.1-.7.1-1 .1-.3-.1-.5-.3-.5-.5 0-.3.2-.5.5-.5h1.4c.3 0 .5.1.5.4.2.5.3 1 .4 1.4.2.1.2.2.3.2h5.2c0 .3-.1.7-.1 1H7v1h1.3c.1.3.2.7.4 1H7v1H9.6c.4.4.9.7 1.4.9h.1-.5v1h1l.1-.1c0-.2.1-.4.1-.6zM6 5H3.3l.3.9s0 .1.1.1h2.2c.1-.4.1-.7.1-1zm1 5h2.5V9H7v1zM3.9 7c.1.3.2.5.3.8 0 .1.1.2.2.2H6V7H3.9zM6 10V9H4.7h-.1l.3.9.1.1h1zm.1 3c0-.3-.3-.6-.6-.6s-.6.3-.6.6.3.6.6.6c.3-.1.6-.3.6-.6zm5 0c0-.3-.3-.6-.6-.6s-.6.3-.6.6.3.6.6.6c.3-.1.6-.3.6-.6z"/>';
					break;

				case 'question-circle' :

					$return_value = '<path fill="#444" d="M8 0c-4.4 0-8 3.6-8 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zM8.9 13h-2v-2h2v2zM11 8.1c-0.4 0.4-0.8 0.6-1.2 0.7-0.6 0.4-0.8 0.2-0.8 1.2h-2c0-2 1.2-2.6 2-3 0.3-0.1 0.5-0.2 0.7-0.4 0.1-0.1 0.3-0.3 0.1-0.7-0.2-0.5-0.8-1-1.7-1-1.4 0-1.6 1.2-1.7 1.5l-2-0.3c0.1-1.1 1-3.2 3.6-3.2 1.6 0 3 0.9 3.6 2.2 0.4 1.1 0.2 2.2-0.6 3z"></path>';
					break;

				case 'radio' :

					$return_value = '<path d="M8 4c-2.2 0-4 1.8-4 4s1.8 4 4 4 4-1.8 4-4-1.8-4-4-4z" fill="#444"></path><path d="M8 1c3.9 0 7 3.1 7 7s-3.1 7-7 7-7-3.1-7-7 3.1-7 7-7zM8 0c-4.4 0-8 3.6-8 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8v0z" fill="#444"></path>';
					break;

				case 'range' :

					$return_value = '<path d="M16 6h-3.6c-0.7-1.2-2-2-3.4-2s-2.8 0.8-3.4 2h-5.6v4h5.6c0.7 1.2 2 2 3.4 2s2.8-0.8 3.4-2h3.6v-4zM1 9v-2h4.1c0 0.3-0.1 0.7-0.1 1s0.1 0.7 0.1 1h-4.1zM9 11c-1.7 0-3-1.3-3-3s1.3-3 3-3 3 1.3 3 3c0 1.7-1.3 3-3 3z" fill="#444"></path>';
					break;

				case 'rating' :

					$return_value = '<path d="M12.9 15.8c-1.6-1.2-3.2-2.5-4.9-3.7-1.6 1.3-3.3 2.5-4.9 3.7 0 0-.1 0-.1-.1.6-2 1.2-4 1.9-6C3.3 8.4 1.7 7.2 0 5.9h6C6.7 3.9 7.3 2 8 0h.1c.7 1.9 1.3 3.9 2 5.9H16V6c-1.6 1.3-3.2 2.5-4.9 3.8.6 1.9 1.3 3.9 1.8 6 .1-.1 0 0 0 0z" fill="#444"></path>';
					break;

				case 'readonly' :

					$return_value = '<path fill="#444" d="M12 8v-3.1c0-2.2-1.6-3.9-3.8-3.9h-0.3c-2.1 0-3.9 1.7-3.9 3.9v3.1h-1l0.1 5c0 0-0.1 3 4.9 3s5-3 5-3v-5h-1zM9 14h-1v-2c-0.6 0-1-0.4-1-1s0.4-1 1-1 1 0.4 1 1v3zM10 8h-4v-3.1c0-1.1 0.9-1.9 1.9-1.9h0.3c1 0 1.8 0.8 1.8 1.9v3.1z"></path>';
					break;

				case 'recaptcha' :

					$return_value = '<path fill="#444" d="M15.9745091,7.99272727 C15.9741867,7.87818182 15.9714864,7.76424242 15.9663679,7.65086869 L15.9663679,1.18662626 L14.1837649,2.97369697 C12.7247952,1.18339394 10.5053073,0.039959596 8.01941638,0.039959596 C5.43236486,0.039959596 3.13388304,1.27793939 1.68095879,3.19511111 L4.6029285,6.15511111 C4.88928153,5.62420202 5.29606001,5.16820202 5.78654789,4.82379798 C6.29666335,4.42472727 7.0194579,4.09842424 8.01933578,4.09842424 C8.14012396,4.09842424 8.23334487,4.11256566 8.30186002,4.13923232 C9.54065427,4.23725253 10.6144479,4.92262626 11.2468025,5.91741414 L9.17845093,7.99094949 C11.7981479,7.98064646 14.7575994,7.97458586 15.9743479,7.99232323" id="Shape"></path><path fill="#444" d="M7.97274547,0.0404040404 C7.85848638,0.0407272727 7.74483184,0.0434343434 7.63174153,0.0485656566 L1.1836597,0.0485656566 L2.96626273,1.83563636 C1.18043546,3.29826263 0.0398596971,5.52331313 0.0398596971,8.01543434 C0.0398596971,10.6089697 1.27474455,12.9132121 3.18712334,14.3697778 L6.13972335,11.4404848 C5.61014153,11.1534141 5.15528153,10.7456162 4.8117385,10.253899 C4.41366547,9.74250505 4.08817819,9.01789899 4.08817819,8.01551515 C4.08817819,7.89442424 4.10228425,7.8009697 4.12888425,7.73228283 C4.2266594,6.49038384 4.91031971,5.41389899 5.90262062,4.7799596 L7.97097214,6.85349495 C7.96069487,4.22723232 7.95464941,1.26036364 7.97234244,0.0405656566" id="Shape"></path><path fill="#444" d="M0.0403030304,8.01535354 C0.0406254546,8.12989899 0.0433257577,8.24383838 0.0484442425,8.35721212 L0.0484442425,14.8214545 L1.83104728,13.0343838 C3.29001698,14.8246869 5.50950486,15.9681212 7.99539578,15.9681212 C10.5824473,15.9681212 12.8809291,14.7301414 14.3338534,12.8129697 L11.4118837,9.8529697 C11.1255306,10.3838788 10.7187521,10.8398788 10.2282643,11.1842828 C9.71814881,11.5833535 8.99535426,11.9096566 7.99547638,11.9096566 C7.8746882,11.9096566 7.78146729,11.8955152 7.71295214,11.8688485 C6.47415789,11.7708283 5.40036426,11.0854545 4.76800971,10.0906667 L6.83636123,8.01713131 C4.21666425,8.02743434 1.25721273,8.03349495 0.0404642425,8.01575758" id="Shape"></path>';
					break;

				case 'redo' :

					$return_value = '<path fill="#444" d="M16 7v-4l-1.1 1.1c-1.3-2.5-3.9-4.1-6.9-4.1-4.4 0-8 3.6-8 8s3.6 8 8 8c2.4 0 4.6-1.1 6-2.8l-1.5-1.3c-1.1 1.3-2.7 2.1-4.5 2.1-3.3 0-6-2.7-6-6s2.7-6 6-6c2.4 0 4.5 1.5 5.5 3.5l-1.5 1.5h4z"></path><text class="count" fill="#444" font-size="7" line-spacing="7" x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"></text>';
					break;

				case 'reload' :

					$return_value = '<path fill="#444" d="M2.6,5.6 C3.5,3.5 5.6,2 8,2 C11,2 13.4,4.2 13.9,7 L15.9,7 C15.4,3.1 12.1,0 8,0 C5,0 2.4,1.6 1.1,4.1 L-8.8817842e-16,3 L-8.8817842e-16,7 L4,7 L2.6,5.6 L2.6,5.6 Z" id="Shape" transform="translate(7.950000, 3.500000) scale(-1, 1) translate(-7.950000, -3.500000) "></path><path fill="#444" d="M16,9 L11.9,9 L13.4,10.4 C12.5,12.5 10.4,14 7.9,14 C5,14 2.5,11.8 2,9 L0,9 C0.5,12.9 3.9,16 7.9,16 C10.9,16 13.5,14.3 14.9,11.9 L16,13 L16,9 Z" id="Shape" transform="translate(8.000000, 12.500000) scale(-1, 1) translate(-8.000000, -12.500000) "></path>';
					break;

				case 'reset' :

					$return_value = '<path fill="#444" d="M8 0c-3 0-5.6 1.6-6.9 4.1l-1.1-1.1v4h4l-1.5-1.5c1-2 3.1-3.5 5.5-3.5 3.3 0 6 2.7 6 6s-2.7 6-6 6c-1.8 0-3.4-0.8-4.5-2.1l-1.5 1.3c1.4 1.7 3.6 2.8 6 2.8 4.4 0 8-3.6 8-8s-3.6-8-8-8z"></path>';
					break;

				case 'save' :

					$return_value = '<path d="M15.791849,4.41655721 C15.6529844,4.08336982 15.4862083,3.8193958 15.2916665,3.625 L12.3749634,0.708260362 C12.1806771,0.513974022 11.916703,0.347234384 11.5833697,0.208260362 C11.2502188,0.0694322825 10.9445781,0 10.666849,0 L1.00003637,0 C0.722343724,0 0.486171803,0.0971614127 0.291703035,0.291630181 C0.0972342664,0.485989492 0.000109339408,0.722124927 0.000109339408,0.999963514 L0.000109339408,15.0002189 C0.000109339408,15.2781305 0.0972342664,15.5142659 0.291703035,15.7086617 C0.486171803,15.902948 0.722343724,16.0002189 1.00003637,16.0002189 L15.0002553,16.0002189 C15.2782033,16.0002189 15.5143023,15.902948 15.7086981,15.7086617 C15.9029844,15.5142659 16.0001093,15.2781305 16.0001093,15.0002189 L16.0001093,5.3334063 C16.0001093,5.05553123 15.9307135,4.75 15.791849,4.41655721 Z M6.66684898,1.66655721 C6.66684898,1.57629159 6.69986853,1.49832166 6.76587116,1.43220957 C6.83180082,1.36638938 6.90995318,1.3334063 7.0002188,1.3334063 L9.00032825,1.3334063 C9.09037496,1.3334063 9.16849083,1.3663164 9.23445698,1.43220957 C9.30060554,1.49832166 9.33358862,1.57629159 9.33358862,1.66655721 L9.33358862,4.99996351 C9.33358862,5.09037507 9.30038663,5.16845447 9.23445698,5.23445709 C9.16849083,5.30024081 9.09037496,5.33326036 9.00032825,5.33326036 L7.0002188,5.33326036 C6.90995318,5.33326036 6.83176433,5.30035026 6.76587116,5.23445709 C6.69986853,5.16834501 6.66684898,5.09037507 6.66684898,4.99996351 L6.66684898,1.66655721 Z M12.0003647,14.6669221 L4.00003637,14.6669221 L4.00003637,10.6667761 L12.0003647,10.6667761 L12.0003647,14.6669221 Z M14.6672503,14.6669221 L13.3336251,14.6669221 L13.3333697,14.6669221 L13.3333697,10.3334063 C13.3333697,10.0554947 13.2362083,9.81950525 13.0418125,9.62496351 C12.8474167,9.43056772 12.6112813,9.33329685 12.3336251,9.33329685 L3.66673952,9.33329685 C3.38893742,9.33329685 3.1527655,9.43056772 2.95829673,9.62496351 C2.76393742,9.81935931 2.66670303,10.0554947 2.66670303,10.3334063 L2.66670303,14.6669221 L1.33333322,14.6669221 L1.33333322,1.33326036 L2.66666655,1.33326036 L2.66666655,5.66670315 C2.66666655,5.94454174 2.76379148,6.18056772 2.95826024,6.37503649 C3.15272901,6.5693958 3.38890093,6.66666667 3.66670303,6.66666667 L9.66699492,6.66666667 C9.94465108,6.66666667 10.1810419,6.5693958 10.3751823,6.37503649 C10.5694687,6.18067717 10.666849,5.94454174 10.666849,5.66670315 L10.666849,1.33326036 C10.7709792,1.33326036 10.9063046,1.36792177 11.0731537,1.43735406 C11.2399663,1.50674985 11.3579611,1.57618214 11.4273933,1.64561442 L14.3547138,4.57286194 C14.4241096,4.64229422 14.4935784,4.76222271 14.5629742,4.93228255 C14.6326254,5.10248832 14.6672138,5.23620841 14.6672138,5.3334063 L14.6672138,14.6669221 L14.6672503,14.6669221 Z" fill="#444"></path>';
					break;

				case 'search' :

					$return_value = '<path d="M10.7 1.8C8.3-.6 4.3-.6 1.8 1.8c-2.4 2.4-2.4 6.4 0 8.9 2.2 2.2 5.2 2.6 7.7.9.1.2.5.3.7.5l3.6 3.6c.5.5 1.4.5 1.9 0s.5-1.4 0-1.9l-3.6-3.6c-.2-.2-.2-.6-.5-.6 1.7-2.5 1.3-5.6-.9-7.8zM9.6 9.6c-1.8 1.8-4.8 1.8-6.6 0C1.1 7.7 1.1 4.8 3 3c1.8-1.8 4.8-1.8 6.6 0 1.8 1.8 1.8 4.7 0 6.6z"/>';
					break;

				case 'section' :

					$return_value = '<path d="M0 1.8h1.8V0C.8 0 0 .8 0 1.8zm0 7.1h1.8V7.1H0v1.8zM3.6 16h1.8v-1.8H3.6V16zM0 5.3h1.8V3.6H0v1.7zM8.9 0H7.1v1.8h1.8V0zm5.3 0v1.8H16c0-1-.8-1.8-1.8-1.8zM1.8 16v-1.8H0c0 1 .8 1.8 1.8 1.8zM0 12.4h1.8v-1.8H0v1.8zM5.3 0H3.6v1.8h1.8V0zm1.8 16h1.8v-1.8H7.1V16zm7.1-7.1H16V7.1h-1.8v1.8zm0 7.1c1 0 1.8-.8 1.8-1.8h-1.8V16zm0-10.7H16V3.6h-1.8v1.7zm0 7.1H16v-1.8h-1.8v1.8zM10.7 16h1.8v-1.8h-1.8V16zm0-14.2h1.8V0h-1.8v1.8z"/>';
					break;

				case 'section-icons' :

					$return_value = '<path d="M11.5 16c-1.2 0-2.3-.5-3.2-1.3S7 12.7 7 11.5s.5-2.3 1.3-3.2 2-1.3 3.2-1.3 2.3.5 3.2 1.3 1.3 2 1.3 3.2-.5 2.3-1.3 3.2-2 1.3-3.2 1.3zm0-8.3c-2.1 0-3.8 1.7-3.8 3.8s1.7 3.8 3.8 3.8 3.8-1.7 3.8-3.8-1.7-3.8-3.8-3.8zm1.9 3.4H9.6v.7h3.9v-.7zM7.7 1.3C6.8.5 5.7 0 4.5 0S2.2.5 1.3 1.3 0 3.3 0 4.5s.5 2.3 1.3 3.2S3.3 9 4.5 9s2.3-.5 3.2-1.3S9 5.7 9 4.5s-.5-2.3-1.3-3.2zm-3.2 7C2.4 8.3.7 6.6.7 4.5S2.4.7 4.5.7s3.8 1.7 3.8 3.8-1.7 3.8-3.8 3.8zm.4-4.2h1.6v.7H4.9v1.6h-.8V4.9H2.6v-.8h1.6V2.6h.7v1.5z"/>';
					break;

				case 'select' :

					$return_value = '<path d="M15 4h-14c-0.6 0-1 0.4-1 1v6c0 0.6 0.4 1 1 1h14c0.6 0 1-0.4 1-1v-6c0-0.6-0.4-1-1-1zM10 11h-9v-6h9v6zM13 8.4l-2-1.4h4l-2 1.4z" fill="#444"></path>';
					break;

				case 'settings' :

					$return_value = '<path d="M16 9v-2l-1.7-0.6c-0.2-0.6-0.4-1.2-0.7-1.8l0.8-1.6-1.4-1.4-1.6 0.8c-0.5-0.3-1.1-0.6-1.8-0.7l-0.6-1.7h-2l-0.6 1.7c-0.6 0.2-1.2 0.4-1.7 0.7l-1.6-0.8-1.5 1.5 0.8 1.6c-0.3 0.5-0.5 1.1-0.7 1.7l-1.7 0.6v2l1.7 0.6c0.2 0.6 0.4 1.2 0.7 1.8l-0.8 1.6 1.4 1.4 1.6-0.8c0.5 0.3 1.1 0.6 1.8 0.7l0.6 1.7h2l0.6-1.7c0.6-0.2 1.2-0.4 1.8-0.7l1.6 0.8 1.4-1.4-0.8-1.6c0.3-0.5 0.6-1.1 0.7-1.8l1.7-0.6zM8 12c-2.2 0-4-1.8-4-4s1.8-4 4-4 4 1.8 4 4-1.8 4-4 4z" fill="#444"></path><path d="M10.6 7.9c0 1.381-1.119 2.5-2.5 2.5s-2.5-1.119-2.5-2.5c0-1.381 1.119-2.5 2.5-2.5s2.5 1.119 2.5 2.5z" fill="#444"></path>';
					break;

				case 'signature' :

					$return_value = '<path d="m 19.642,6.173 q -0.396,-0.275 -0.903,-0.275 -0.507,0 -0.892,0.297 -2.191,1.641 -2.951,4.173 -0.418,1.354 -0.297,2.345 1.938,-0.562 3.546,-1.795 Q 19.764,9.674 20.248,7.78 20.347,7.328 20.182,6.888 20.017,6.448 19.642,6.172 z M 1.011,18.131 h 21.967 v 1.652 H 1.011 v -1.652 z m 2.94,-6.21 1.883,-1.883 1.123,1.123 -1.883,1.894 1.894,1.894 L 5.845,16.072 3.94,14.178 2.123,16.006 1.011,14.883 2.817,13.055 1,11.227 2.123,10.104 z M 23,15.808 v 0.617 l -1.619,0.011 q 0.077,-0.914 -0.077,-1.013 -0.595,-0.385 -2.555,0.319 l -0.562,0.198 q -1.189,0.385 -2.533,0.077 -1.343,-0.308 -2.081,-1.376 -1.795,0.319 -4.922,0.22 v -1.652 q 2.896,0.066 4.327,-0.154 -0.231,-1.497 0.286,-3.215 0.518,-1.718 1.597,-3.127 1.079,-1.431 2.444,-2.136 1.663,-0.881 3.369,0.396 0.925,0.683 1.211,1.96 0.132,0.595 -0.088,1.475 -0.22,0.881 -0.87,1.85 -0.639,0.98 -1.509,1.718 -1.707,1.453 -3.975,2.191 1.145,0.672 2.588,0.077 0.716,-0.286 1.475,-0.44 h 0.011 q 1.442,-0.242 2.191,0.011 0.958,0.33 1.167,1.101 0.121,0.462 0.121,0.892 z" fill="#444"></path>';
					break;

				case 'sort' :

					$return_value = '<path fill="#444" d="M11 7h-6l3-4z"></path><path fill="#444" d="M5 9h6l-3 4z"></path>';
					break;

				case 'spacer' :

					$return_value = '<path fill="#444" d="M7 7h1v1h-1v-1z"></path><path fill="#444" d="M5 7h1v1h-1v-1z"></path><path fill="#444" d="M3 7h1v1h-1v-1z"></path><path fill="#444" d="M1 7h1v1h-1v-1z"></path><path fill="#444" d="M6 6h1v1h-1v-1z"></path><path fill="#444" d="M4 6h1v1h-1v-1z"></path><path fill="#444" d="M2 6h1v1h-1v-1z"></path><path fill="#444" d="M0 6h1v1h-1v-1z"></path><path fill="#444" d="M7 5h1v1h-1v-1z"></path><path fill="#444" d="M5 5h1v1h-1v-1z"></path><path fill="#444" d="M3 5h1v1h-1v-1z"></path><path fill="#444" d="M1 5h1v1h-1v-1z"></path><path fill="#444" d="M6 4h1v1h-1v-1z"></path><path fill="#444" d="M4 4h1v1h-1v-1z"></path><path fill="#444" d="M2 4h1v1h-1v-1z"></path><path fill="#444" d="M0 4h1v1h-1v-1z"></path><path fill="#444" d="M7 3h1v1h-1v-1z"></path><path fill="#444" d="M5 3h1v1h-1v-1z"></path><path fill="#444" d="M3 3h1v1h-1v-1z"></path><path fill="#444" d="M1 3h1v1h-1v-1z"></path><path fill="#444" d="M6 2h1v1h-1v-1z"></path><path fill="#444" d="M4 2h1v1h-1v-1z"></path><path fill="#444" d="M2 2h1v1h-1v-1z"></path><path fill="#444" d="M0 2h1v1h-1v-1z"></path><path fill="#444" d="M7 1h1v1h-1v-1z"></path><path fill="#444" d="M5 1h1v1h-1v-1z"></path><path fill="#444" d="M3 1h1v1h-1v-1z"></path><path fill="#444" d="M1 1h1v1h-1v-1z"></path><path fill="#444" d="M6 0h1v1h-1v-1z"></path><path fill="#444" d="M4 0h1v1h-1v-1z"></path><path fill="#444" d="M2 0h1v1h-1v-1z"></path><path fill="#444" d="M0 0h1v1h-1v-1z"></path><path fill="#444" d="M15 7h1v1h-1v-1z"></path><path fill="#444" d="M13 7h1v1h-1v-1z"></path><path fill="#444" d="M11 7h1v1h-1v-1z"></path><path fill="#444" d="M9 7h1v1h-1v-1z"></path><path fill="#444" d="M14 6h1v1h-1v-1z"></path><path fill="#444" d="M12 6h1v1h-1v-1z"></path><path fill="#444" d="M10 6h1v1h-1v-1z"></path><path fill="#444" d="M8 6h1v1h-1v-1z"></path><path fill="#444" d="M15 5h1v1h-1v-1z"></path><path fill="#444" d="M13 5h1v1h-1v-1z"></path><path fill="#444" d="M11 5h1v1h-1v-1z"></path><path fill="#444" d="M9 5h1v1h-1v-1z"></path><path fill="#444" d="M14 4h1v1h-1v-1z"></path><path fill="#444" d="M12 4h1v1h-1v-1z"></path><path fill="#444" d="M10 4h1v1h-1v-1z"></path><path fill="#444" d="M8 4h1v1h-1v-1z"></path><path fill="#444" d="M15 3h1v1h-1v-1z"></path><path fill="#444" d="M13 3h1v1h-1v-1z"></path><path fill="#444" d="M11 3h1v1h-1v-1z"></path><path fill="#444" d="M9 3h1v1h-1v-1z"></path><path fill="#444" d="M14 2h1v1h-1v-1z"></path><path fill="#444" d="M12 2h1v1h-1v-1z"></path><path fill="#444" d="M10 2h1v1h-1v-1z"></path><path fill="#444" d="M8 2h1v1h-1v-1z"></path><path fill="#444" d="M15 1h1v1h-1v-1z"></path><path fill="#444" d="M13 1h1v1h-1v-1z"></path><path fill="#444" d="M11 1h1v1h-1v-1z"></path><path fill="#444" d="M9 1h1v1h-1v-1z"></path><path fill="#444" d="M14 0h1v1h-1v-1z"></path><path fill="#444" d="M12 0h1v1h-1v-1z"></path><path fill="#444" d="M10 0h1v1h-1v-1z"></path><path fill="#444" d="M8 0h1v1h-1v-1z"></path><path fill="#444" d="M7 15h1v1h-1v-1z"></path><path fill="#444" d="M5 15h1v1h-1v-1z"></path><path fill="#444" d="M3 15h1v1h-1v-1z"></path><path fill="#444" d="M1 15h1v1h-1v-1z"></path><path fill="#444" d="M6 14h1v1h-1v-1z"></path><path fill="#444" d="M4 14h1v1h-1v-1z"></path><path fill="#444" d="M2 14h1v1h-1v-1z"></path><path fill="#444" d="M0 14h1v1h-1v-1z"></path><path fill="#444" d="M7 13h1v1h-1v-1z"></path><path fill="#444" d="M5 13h1v1h-1v-1z"></path><path fill="#444" d="M3 13h1v1h-1v-1z"></path><path fill="#444" d="M1 13h1v1h-1v-1z"></path><path fill="#444" d="M6 12h1v1h-1v-1z"></path><path fill="#444" d="M4 12h1v1h-1v-1z"></path><path fill="#444" d="M2 12h1v1h-1v-1z"></path><path fill="#444" d="M0 12h1v1h-1v-1z"></path><path fill="#444" d="M7 11h1v1h-1v-1z"></path><path fill="#444" d="M5 11h1v1h-1v-1z"></path><path fill="#444" d="M3 11h1v1h-1v-1z"></path><path fill="#444" d="M1 11h1v1h-1v-1z"></path><path fill="#444" d="M6 10h1v1h-1v-1z"></path><path fill="#444" d="M4 10h1v1h-1v-1z"></path><path fill="#444" d="M2 10h1v1h-1v-1z"></path><path fill="#444" d="M0 10h1v1h-1v-1z"></path><path fill="#444" d="M7 9h1v1h-1v-1z"></path><path fill="#444" d="M5 9h1v1h-1v-1z"></path><path fill="#444" d="M3 9h1v1h-1v-1z"></path><path fill="#444" d="M1 9h1v1h-1v-1z"></path><path fill="#444" d="M6 8h1v1h-1v-1z"></path><path fill="#444" d="M4 8h1v1h-1v-1z"></path><path fill="#444" d="M2 8h1v1h-1v-1z"></path><path fill="#444" d="M0 8h1v1h-1v-1z"></path><path fill="#444" d="M15 15h1v1h-1v-1z"></path><path fill="#444" d="M13 15h1v1h-1v-1z"></path><path fill="#444" d="M11 15h1v1h-1v-1z"></path><path fill="#444" d="M9 15h1v1h-1v-1z"></path><path fill="#444" d="M14 14h1v1h-1v-1z"></path><path fill="#444" d="M12 14h1v1h-1v-1z"></path><path fill="#444" d="M10 14h1v1h-1v-1z"></path><path fill="#444" d="M8 14h1v1h-1v-1z"></path><path fill="#444" d="M15 13h1v1h-1v-1z"></path><path fill="#444" d="M13 13h1v1h-1v-1z"></path><path fill="#444" d="M11 13h1v1h-1v-1z"></path><path fill="#444" d="M9 13h1v1h-1v-1z"></path><path fill="#444" d="M14 12h1v1h-1v-1z"></path><path fill="#444" d="M12 12h1v1h-1v-1z"></path><path fill="#444" d="M10 12h1v1h-1v-1z"></path><path fill="#444" d="M8 12h1v1h-1v-1z"></path><path fill="#444" d="M15 11h1v1h-1v-1z"></path><path fill="#444" d="M13 11h1v1h-1v-1z"></path><path fill="#444" d="M11 11h1v1h-1v-1z"></path><path fill="#444" d="M9 11h1v1h-1v-1z"></path><path fill="#444" d="M14 10h1v1h-1v-1z"></path><path fill="#444" d="M12 10h1v1h-1v-1z"></path><path fill="#444" d="M10 10h1v1h-1v-1z"></path><path fill="#444" d="M8 10h1v1h-1v-1z"></path><path fill="#444" d="M15 9h1v1h-1v-1z"></path><path fill="#444" d="M13 9h1v1h-1v-1z"></path><path fill="#444" d="M11 9h1v1h-1v-1z"></path><path fill="#444" d="M9 9h1v1h-1v-1z"></path><path fill="#444" d="M14 8h1v1h-1v-1z"></path><path fill="#444" d="M12 8h1v1h-1v-1z"></path><path fill="#444" d="M10 8h1v1h-1v-1z"></path><path fill="#444" d="M8 8h1v1h-1v-1z"></path>';
					break;

				case 'submit' :

					$return_value = '<path fill="#444" d="M16 7.9l-6-4.9v3c-0.5 0-1.1 0-2 0-8 0-8 8-8 8s1-4 7.8-4c1.1 0 1.8 0 2.2 0v2.9l6-5z"></path>';
					break;

				case 'table' :

					$return_value = '<path fill="#444" d="M0 1v15h16v-15h-16zM5 15h-4v-2h4v2zM5 12h-4v-2h4v2zM5 9h-4v-2h4v2zM5 6h-4v-2h4v2zM10 15h-4v-2h4v2zM10 12h-4v-2h4v2zM10 9h-4v-2h4v2zM10 6h-4v-2h4v2zM15 15h-4v-2h4v2zM15 12h-4v-2h4v2zM15 9h-4v-2h4v2zM15 6h-4v-2h4v2z"></path>';
					break;

				case 'tel' :

					$return_value = '<path d="M12.2 10c-1.1-0.1-1.7 1.4-2.5 1.8-1.3 0.7-3.7-1.8-3.7-1.8s-2.5-2.4-1.9-3.7c0.5-0.8 2-1.4 1.9-2.5-0.1-1-2.3-4.6-3.4-3.6-2.4 2.2-2.6 3.1-2.6 4.9-0.1 3.1 3.9 7 3.9 7 0.4 0.4 3.9 4 7 3.9 1.8 0 2.7-0.2 4.9-2.6 1-1.1-2.5-3.3-3.6-3.4z" fill="#444"></path>';
					break;

				case 'text' :

					$return_value = '<path d="M16 5c0-0.6-0.4-1-1-1h-14c-0.6 0-1 0.4-1 1v6c0 0.6 0.4 1 1 1h14c0.6 0 1-0.4 1-1v-6zM15 11h-14v-6h14v6z" fill="#444"></path><path d="M2 6h1v4h-1v-4z" fill="#444"></path>';
					break;

				case 'textarea' :

					$return_value = '<path d="M2 2h1v4h-1v-4z" fill="#444"></path><path d="M1 0c-0.6 0-1 0.4-1 1v14c0 0.6 0.4 1 1 1h15v-16h-15zM13 15h-12v-14h12v14zM15 15v0h-1v-1h1v1zM15 13h-1v-10h1v10zM15 2h-1v-1h1v1z" fill="#444"></path>';
					break;

				case 'texteditor' :

					$return_value = '<path fill="#444" d="M16 4c0 0 0-1-1-2s-1.9-1-1.9-1l-1.1 1.1v-2.1h-12v16h12v-8l4-4zM6.3 11.4l-0.6-0.6 0.3-1.1 1.5 1.5-1.2 0.2zM7.2 9.5l-0.6-0.6 5.2-5.2c0.2 0.1 0.4 0.3 0.6 0.5zM14.1 2.5l-0.9 1c-0.2-0.2-0.4-0.3-0.6-0.5l0.9-0.9c0.1 0.1 0.3 0.2 0.6 0.4zM11 15h-10v-14h10v2.1l-5.9 5.9-1.1 4.1 4.1-1.1 2.9-3v6z"></path>';
					break;

				case 'tools' :

					$return_value = '<path fill="#444" d="M10.3 8.2l-0.9 0.9 0.9 0.9-1.2 1.2 4.3 4.3c0.6 0.6 1.5 0.6 2.1 0s0.6-1.5 0-2.1l-5.2-5.2zM14.2 15c-0.4 0-0.8-0.3-0.8-0.8 0-0.4 0.3-0.8 0.8-0.8s0.8 0.3 0.8 0.8c0 0.5-0.3 0.8-0.8 0.8z"></path><path fill="#444" d="M3.6 8l0.9-0.6 1.5-1.7 0.9 0.9 0.9-0.9-0.1-0.1c0.2-0.5 0.3-1 0.3-1.6 0-2.2-1.8-4-4-4-0.6 0-1.1 0.1-1.6 0.3l2.9 2.9-2.1 2.1-2.9-2.9c-0.2 0.5-0.3 1-0.3 1.6 0 2.1 1.6 3.7 3.6 4z"></path><path fill="#444" d="M8 10.8l0.9-0.8-0.9-0.9 5.7-5.7 1.2-0.4 1.1-2.2-0.7-0.7-2.3 1-0.5 1.2-5.6 5.7-0.9-0.9-0.8 0.9c0 0 0.8 0.6-0.1 1.5-0.5 0.5-1.3-0.1-2.8 1.4-0.5 0.5-2.1 2.1-2.1 2.1s-0.6 1 0.6 2.2 2.2 0.6 2.2 0.6 1.6-1.6 2.1-2.1c1.4-1.4 0.9-2.3 1.3-2.7 0.9-0.9 1.6-0.2 1.6-0.2zM4.9 10.4l0.7 0.7-3.8 3.8-0.7-0.7z"></path>';
					break;

				case 'undo' :

					$return_value = '<path fill="#444" d="M8 0c-3 0-5.6 1.6-6.9 4.1l-1.1-1.1v4h4l-1.5-1.5c1-2 3.1-3.5 5.5-3.5 3.3 0 6 2.7 6 6s-2.7 6-6 6c-1.8 0-3.4-0.8-4.5-2.1l-1.5 1.3c1.4 1.7 3.6 2.8 6 2.8 4.4 0 8-3.6 8-8s-3.6-8-8-8z"></path><text class="count" fill="#444" font-size="7" line-spacing="7" x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"></text>';
					break;

				case 'up' :

					$return_value = '<path d="M1 13h14L8 3 1 13z"/>';
					break;

				case 'upload' :

					$return_value = '<path fill="#444" d="M0 14h16v2h-16v-2z"></path><path fill="#444" d="M8 0l-5 5h3v8h4v-8h3z"></path>';
					break;

				case 'url' :

					$return_value = '<path d="M14.9 1.1c-1.4-1.4-3.7-1.4-5.1 0l-4.4 4.3c-1.4 1.5-1.4 3.7 0 5.2 0.1 0.1 0.3 0.2 0.4 0.3l1.5-1.5c-0.1-0.1-0.3-0.2-0.4-0.3-0.6-0.6-0.6-1.6 0-2.2l4.4-4.4c0.6-0.6 1.6-0.6 2.2 0s0.6 1.6 0 2.2l-1.3 1.3c0.4 0.8 0.5 1.7 0.4 2.5l2.3-2.3c1.5-1.4 1.5-3.7 0-5.1z" fill="#444"></path><path d="M10.2 5.1l-1.5 1.5c0 0 0.3 0.2 0.4 0.3 0.6 0.6 0.6 1.6 0 2.2l-4.4 4.4c-0.6 0.6-1.6 0.6-2.2 0s-0.6-1.6 0-2.2l1.3-1.3c-0.4-0.8-0.1-1.3-0.4-2.5l-2.3 2.3c-1.4 1.4-1.4 3.7 0 5.1s3.7 1.4 5.1 0l4.4-4.4c1.4-1.4 1.4-3.7 0-5.1-0.2-0.1-0.4-0.3-0.4-0.3z" fill="#444"></path>';
					break;

				case 'visible' :

					$return_value = '<path fill="#444" d="M8 3.9c-6.7 0-8 5.1-8 5.1s2.2 4.1 7.9 4.1 8.1-4 8.1-4-1.3-5.2-8-5.2zM5.3 5.4c0.5-0.3 1.3-0.3 1.3-0.3s-0.5 0.9-0.5 1.6c0 0.7 0.2 1.1 0.2 1.1l-1.1 0.2c0 0-0.3-0.5-0.3-1.2 0-0.8 0.4-1.4 0.4-1.4zM7.9 12.1c-4.1 0-6.2-2.3-6.8-3.2 0.3-0.7 1.1-2.2 3.1-3.2-0.1 0.4-0.2 0.8-0.2 1.3 0 2.2 1.8 4 4 4s4-1.8 4-4c0-0.5-0.1-0.9-0.2-1.3 2 0.9 2.8 2.5 3.1 3.2-0.7 0.9-2.8 3.2-7 3.2z"></path>';
					break;

				case 'warning' :

					$return_value = '<path fill="#444" d="M8 1l-8 14h16l-8-14zM8 13c-0.6 0-1-0.4-1-1s0.4-1 1-1 1 0.4 1 1c0 0.6-0.4 1-1 1zM7 10v-4h2v4h-2z"></path>';
					break;

				case 'wizard' :

					$return_value = '<path fill="#444" d="M0 5h3v1h-3v-1z"></path><path fill="#444" d="M5 0h1v3h-1v-3z"></path><path fill="#444" d="M6 11h-1v-2.5l1 1z"></path><path fill="#444" d="M11 6h-1.5l-1-1h2.5z"></path><path fill="#444" d="M3.131 7.161l0.707 0.707-2.97 2.97-0.707-0.707 2.97-2.97z"></path><path fill="#444" d="M10.131 0.161l0.707 0.707-2.97 2.97-0.707-0.707 2.97-2.97z"></path><path fill="#444" d="M0.836 0.199l3.465 3.465-0.707 0.707-3.465-3.465 0.707-0.707z"></path><path fill="#444" d="M6.1 4.1l-2.1 2 9.8 9.9 2.2-2.1-9.9-9.8zM6.1 5.5l2.4 2.5-0.6 0.6-2.5-2.5 0.7-0.6z"></path>';
					break;

				case 'woo' :

					$return_value = '<path d="M2 0h12c.6 0 1 .2 1.4.6.4.3.6.8.6 1.3v10.2c0 .5-.2 1-.6 1.4-.4.4-.9.6-1.4.6H9.5V16l-2-1.9H2c-.6 0-1-.2-1.4-.6-.4-.4-.6-.8-.6-1.4V1.9C0 1.4.2.9.6.5 1 .2 1.4 0 2 0zm5.6 4.7c0-.2 0-.3-.1-.3-.1-.1-.2-.1-.3-.1-.2 0-.4.2-.5.5-.1.3-.2.7-.3 1-.2.4-.2.7-.3 1.1v.6c-.1-.3-.2-.5-.2-.7-.1-.3-.1-.5-.2-.6 0-.1-.1-.3-.2-.6 0-.3-.1-.4-.3-.4-.1 0-.2.1-.4.4-.2.3-.4.5-.5.9-.2.3-.3.6-.4.9-.2.2-.2.4-.3.4v-.1-.1c-.1-.5-.2-1-.2-1.5-.1-.5-.1-1-.2-1.4 0-.1-.1-.2-.2-.2s-.2-.1-.2-.1c-.2 0-.3.1-.4.2 0 .1-.1.3-.1.4 0 .1.1.4.2 1s.2 1.1.3 1.6c0 .1.1.4.2 1s.3.9.5.9c.1 0 .3-.1.5-.4.1-.1.3-.4.4-.7.2-.3.3-.6.4-.9l.2-.4s.1.2.1.5l.3.9c.2.2.4.5.6.8.2.3.4.4.5.4.1 0 .2 0 .3-.1 0-.1.1-.2.1-.3V8.1c0-.3 0-.6.1-1s.2-.8.2-1.1c.1-.3.2-.6.2-.9l.2-.4zm2.6.7c-.2-.4-.6-.6-1-.6s-.8.1-1 .4c-.3.3-.5.8-.6 1.3V7.8c0 .1.1.3.2.5.2.4.4.5.7.6 0 .1.1.1.2.1h.2c.6 0 1-.3 1.3-.9.3-.6.4-1.1.4-1.5-.1-.4-.2-.8-.4-1.2zm-.6 1.3c0 .1-.1.4-.2.8s-.3.6-.5.6-.3-.1-.4-.4c-.1-.4-.1-.5-.1-.6 0-.1.1-.3.2-.7.1-.4.3-.6.6-.6.2 0 .3.1.4.4v.5zm3.9-1.3c-.2-.4-.6-.6-1-.6s-.8.1-1 .4c-.3.3-.5.8-.6 1.3 0 .3-.1.5-.1.7 0 .2 0 .4.1.6 0 .1.1.3.2.5s.3.4.6.5c.1 0 .1 0 .2.1h.2c.6 0 1-.3 1.3-.9.3-.6.4-1.1.4-1.5 0-.3-.1-.7-.3-1.1zm-.8 2.1c-.1.4-.3.6-.6.6-.2 0-.3-.1-.4-.4 0-.3-.1-.5-.1-.5 0-.1 0-.3.1-.7.1-.4.3-.6.6-.6.2 0 .3.1.4.4 0 .2.1.4.1.4.1.1 0 .4-.1.8z"/><path fill="none" d="M174.6 238.5h-8.5 8.5z"/>';
					break;

				default :

					$return_value = '<path fill="#444" d="M9 11h-3c0-3 1.6-4 2.7-4.6 0.4-0.2 0.7-0.4 0.9-0.6 0.5-0.5 0.3-1.2 0.2-1.4-0.3-0.7-1-1.4-2.3-1.4-2.1 0-2.5 1.9-2.5 2.3l-3-0.4c0.2-1.7 1.7-4.9 5.5-4.9 2.3 0 4.3 1.3 5.1 3.2 0.7 1.7 0.4 3.5-0.8 4.7-0.5 0.5-1.1 0.8-1.6 1.1-0.9 0.5-1.2 1-1.2 2z"></path><path fill="#444" d="M9.5 14c0 1.105-0.895 2-2 2s-2-0.895-2-2c0-1.105 0.895-2 2-2s2 0.895 2 2z"></path>';
			}

			// Apply filter
			$return_value = apply_filters('wsf_config_icon_16_svg', '<svg height="16" width="16" viewBox="0 0 16 16">' . $return_value . '</svg>', $id);

			return $return_value;
		}


		// Configuration - Frameworks
		public static function get_frameworks($public = true) {

			// Check cache
			if(isset(self::$frameworks[$public])) { return self::$frameworks[$public]; }

			$framework_foundation_init_js =	"if(typeof $(document).foundation === 'function') {

				// Abide
				if(typeof(Foundation.Abide) === 'function') {

					if($('[data-abide]').length) { Foundation.reInit($('[data-abide]')); }

				} else {

					if(typeof $('#form_canvas_selector')[0].ws_form_log_error === 'function') {
						$('#form_canvas_selector')[0].ws_form_log_error('error_framework_plugin', 'Abide', 'framework');
					}
				}

				// Tabs
				if(typeof(Foundation.Tabs) === 'function') {

					if($('[data-tabs]').length) { var wsf_foundation_tabs = new Foundation.Tabs($('[data-tabs]')); }

				} else {

					if(typeof($('#form_canvas_selector')[0].ws_form_log_error) === 'function') {

						$('#form_canvas_selector')[0].ws_form_log_error('error_framework_plugin', 'Tabs', 'framework');
					}
				}
			}";

			$frameworks = array(

				'icons'	=> array(

					'25'	=>	self::get_icon_24_svg('bp-25'),
					'50'	=>	self::get_icon_24_svg('bp-50'),
					'75'	=>	self::get_icon_24_svg('bp-75'),
					'100'	=>	self::get_icon_24_svg('bp-100'),
					'125'	=>	self::get_icon_24_svg('bp-125')
				),

				'types' => array(

					'ws-form'		=> array(

						'name'						=>	__('WS Form', 'ws-form'),

						'label_positions'			=>	array('default', 'top', 'left', 'right', 'bottom'),

						'minicolors_args' 			=>	array(

							'theme' 					=> 'ws-form'
						),

						'columns'					=>	array(

							'column_count' 			=> 	12,
							'column_class'				=>	'wsf-#id-#size',
							'column_css_selector'		=>	'.wsf-#id-#size',
							'offset_class'				=>	'wsf-offset-#id-#offset',
							'offset_css_selector'		=>	'.wsf-offset-#id-#offset'
						),

						'breakpoints'				=>	array(

							25	=>	array(
								'id'					=>	'extra-small',
								'name'					=>	__('Extra Small', 'ws-form'),
								'admin_max_width'		=>	575,
								'column_size_default'	=>	'column_count'
							),

						),

						'form' => array(

							'admin' => array('mask_single' => '#form'),
							'public' => array(

								'mask_single' 	=> '#label#form',
								'mask_label'	=> '<h2>#label</h2>',
							),
						),

						'tabs' => array(

							'admin' => array(

								'mask_wrapper'		=>	'<ul class="wsf-group-tabs">#tabs</ul>',
								'mask_single'		=>	'<li class="wsf-group-tab" data-id="#data_id" title="#label"><a href="#href"><input type="text" value="#label" data-label="#data_id" readonly></a></li>'
							),

							'public' => array(

								'mask_wrapper'		=>	'<ul class="wsf-group-tabs">#tabs</ul>',
								'mask_single'		=>	'<li class="wsf-group-tab" data-id="#data_id"><a href="#href">#label</a></li>',
								'activate_js'		=>	"$('#form .wsf-group-tabs .wsf-group-tab:eq(#index) a').click();",
								'event_js'			=>	'tab_show',
								'event_type_js'		=>	'tab',
								'class_disabled'	=>	'wsf-tab-disabled'
							),
						),

						'message' => array(

							'public'	=>	array(

								'mask_wrapper'		=>	'<div class="wsf-alert #mask_wrapper_class">#message</div>',

								'types'	=>	array(

									'success'		=>	array('mask_wrapper_class' => 'wsf-alert-success', 'text_class' => 'wsf-text-success'),
									'information'	=>	array('mask_wrapper_class' => 'wsf-alert-information', 'text_class' => 'wsf-text-information'),
									'warning'		=>	array('mask_wrapper_class' => 'wsf-alert-warning', 'text_class' => 'wsf-text-warning'),
									'danger'		=>	array('mask_wrapper_class' => 'wsf-alert-danger', 'text_class' => 'wsf-text-danger')
								)
							)
						),

						'groups' => array(

							'admin' => array(

								// mask_wrapper is placed around all of the groups
								'mask_wrapper'	=>	'<div class="wsf-groups">#groups</div>',

								// mask_single is placed around each individual group
								'mask_single'	=>	'<div class="wsf-group" id="#id" data-id="#data_id" data-group-index="#data_group_index">#group</div>',
							),

							'public' => array(

								'mask_wrapper'	=>	'<div class="wsf-groups">#groups</div>',
								'mask_single' 	=> '<div class="#class" id="#id" data-id="#data_id" data-group-index="#data_group_index">#label#group</div>',
								'mask_label' 	=> '<h3>#label</h3>',
								'class'			=> 'wsf-group'
							)
						),

						'sections' => array(

							'admin' => array(

								'mask_wrapper' 	=> '<ul class="wsf-sections" id="#id" data-id="#data_id">#sections</ul>',
								'mask_single' 	=> sprintf('<li class="#class" id="#id" data-id="#data_id"><div class="wsf-section-inner">#label<div class="wsf-section-type">%s#section_id</div>#section</div></li>', __('Section', 'ws-form')),
								'mask_label' 	=> '<div class="wsf-section-label"><span class="wsf-section-repeatable">' . self::get_icon_16_svg('redo') . '</span><span class="wsf-section-hidden">' . self::get_icon_16_svg('hidden') . '</span><span class="wsf-section-disabled">' . self::get_icon_16_svg('disabled') . '</span><input type="text" value="#label" data-label="#data_id" readonly></div>',
								'class_single'	=> array('wsf-section')
							),

							'public' => array(

								'mask_wrapper'	=> '<div class="wsf-grid wsf-sections" id="#id" data-id="#data_id">#sections</div>',
								'mask_single' 	=> '<div#attributes class="#class" id="#id" data-id="#data_id"#hidden>#section</div>',
								'class_single'	=> array('wsf-tile', 'wsf-section')
							)
						),

						'fields' => array(

							'admin' => array(

								'mask_wrapper' 	=> '<ul class="wsf-fields" id="#id" data-id="#data_id">#fields</ul>',
								'mask_single' 	=> '<li class="#class" id="#id" data-id="#data_id" data-type="#type"></li>',
								'mask_label' 	=> '<h4>#label</h4>',
								'class_single'	=> array('wsf-field-wrapper')
							),

							'public' => array(

								// Label position - Left
								'left' => array(

									'mask'							=>	'<div class="wsf-grid wsf-fields">#field</div>',
									'mask_field_label_wrapper'		=>	'<div class="wsf-extra-small-#column_width_label wsf-tile">#label</div>',
									'mask_field_wrapper'			=>	'<div class="wsf-extra-small-#column_width_field wsf-tile">#field</div>',
								),

								// Label position - Right
								'right' => array(

									'mask'							=>	'<div class="wsf-grid wsf-fields">#field</div>',
									'mask_field_label_wrapper'		=>	'<div class="wsf-extra-small-#column_width_label wsf-tile">#label</div>',
									'mask_field_wrapper'			=>	'<div class="wsf-extra-small-#column_width_field wsf-tile">#field</div>',
								),

								// Flex within a fieldset doesn't work, so we embed a div within the fieldset to get it working
								'mask_wrapper' 			=> '<fieldset#attributes>#label<div class="wsf-grid wsf-fields" id="#id" data-id="#data_id">#fields</div></fieldset>',
								'mask_wrapper_label'	=> '<legend>#label</legend>',
								'mask_single' 			=> '<div class="#class" id="#id" data-id="#data_id" data-type="#type"#attributes>#field</div>',

								// Required
								'mask_required_label'	=> ' <strong class="wsf-text-danger">*</strong>',

								// Help
								'mask_help'				=>	'<small id="#help_id" class="#help_class">#help#help_append</small>',

								// Invalid feedback
								'mask_invalid_feedback'	=>	'<div id="#invalid_feedback_id" class="#invalid_feedback_class">#invalid_feedback</div>',

								// Classes - Default
								'class_single'					=> array('wsf-tile', 'wsf-field-wrapper'),
								'class_field'					=> array('wsf-field'),
								'class_field_label'				=> array('wsf-label'),
								'class_help'					=> array('wsf-help'),
								'class_invalid_feedback'		=> array('wsf-invalid-feedback'),
								'class_inline' 					=> array('wsf-inline'),
								'class_form_validated'			=> array('wsf-validated'),
								'class_single_vertical_align'	=> array(

									'middle'	=>	'wsf-middle',
									'bottom'	=>	'wsf-bottom'
								),
								'class_field_button_type'	=> array(

									'primary'		=>	'wsf-button-primary',
									'secondary'		=>	'wsf-button-secondary',
									'success'		=>	'wsf-button-success',
									'information'	=>	'wsf-button-information',
									'warning'		=>	'wsf-button-warning',
									'danger'		=>	'wsf-button-danger'
								),

								// Custom settings by field type
								'field_types'		=> array(

									'checkbox' 	=> array(

										'class_field'			=> array(),
										'class_row_field'		=> array('wsf-field'),
										'class_row_field_label'	=> array('wsf-label'),
										'mask_row_label'		=>	'#row_field<label id="#label_row_id" for="#row_id"#attributes>#checkbox_field_label</label>#invalid_feedback',
									),

									'radio' 	=> array(

										'class_field'			=> array(),
										'class_row_field'		=> array('wsf-field'),
										'class_row_field_label'	=> array('wsf-label'),
										'mask_row_label'		=>	'#row_field<label id="#label_row_id" data-label-required-id="#label_id" for="#row_id"#attributes>#radio_field_label</label>#invalid_feedback',
									),

									'spacer' 	=> array(
										'class_single'			=> array('wsf-tile'),
									),


									'submit' 	=> array(
										'class_field'						=> array('wsf-button'),
										'class_field_full_button'			=> array('wsf-button-full'),
										'class_field_button_type_fallback'	=> 'primary',
									),

								)
							)
						)
					),

					'bootstrap3'	=> array(

						'name'						=>	__('Bootstrap 3.x', 'ws-form'),

						'default'					=>	false,

						'css_file'					=>	'bootstrap3.css',

						'label_positions'			=>	array('default', 'top', 'left', 'right', 'bottom'),

						'minicolors_args'			=>	array(

							'changeDelay' 	=> 200,
							'letterCase' 	=> 'uppercase',
							'theme' 		=> 'bootstrap'
						),

						'columns'					=>	array(

							'column_count' 				=> 	12,
							'column_class'				=>	'col-#id-#size',
							'column_css_selector'		=>	'.col-#id-#size',
							'offset_class'				=>	'col-#id-offset-#offset',
							'offset_css_selector'		=>	'.col-#id-offset-#offset'
						),

						'breakpoints'				=>	array(

							// Up to 767px
							25	=>	array(
								'id'				=>	'xs',
								'name'				=>	__('Extra Small', 'ws-form'),
								'admin_max_width'	=>	767,
								'column_size_default'	=>	'column_count'	// Set to column count if XS framework breakpoint size is not set in object meta
							),
						),

						'form' => array(

							'admin' => array('mask_single' => '#form'),
							'public' => array(

								'mask_single' 	=> '#label#form',
								'mask_label'	=> '<h2>#label</h2>',
							),
						),

						'tabs' => array(

							'public' => array(

								'mask_wrapper'		=>	'<ul class="nav nav-tabs">#tabs</ul>',
								'mask_single'		=>	'<li><a class="nav-link" href="#href" data-toggle="tab">#label</a></li>',
								'activate_js'		=>	"$('#form ul.nav-tabs li:eq(#index) a').tab('show');",
								'event_js'			=>	'shown.bs.tab',
								'event_type_js'		=>	'tab',
								'class_disabled'	=>	'disabled'
							),
						),

						'message' => array(

							'public'	=>	array(

								'mask_wrapper'		=>	'<div class="alert #mask_wrapper_class">#message</div>',

								'types'	=>	array(

									'success'		=>	array('mask_wrapper_class' => 'alert-success', 'text_class' => 'text-success'),
									'information'	=>	array('mask_wrapper_class' => 'alert-info', 'text_class' => 'text-info'),
									'warning'		=>	array('mask_wrapper_class' => 'alert-warning', 'text_class' => 'text-warning'),
									'danger'		=>	array('mask_wrapper_class' => 'alert-danger', 'text_class' => 'text-danger')
								)
							)
						),

						'groups' => array(

							'public' => array(

								'mask_wrapper'	=>	'<div class="tab-content">#groups</div>',
								'mask_single' 	=> '<div class="#class" id="#id" data-id="#data_id" data-group-index="#data_group_index">#label#group</div>',
								'mask_label' 	=> '<h3>#label</h3>',
								'class'			=> 'tab-pane',
								'class_active'	=> 'active',
							)
						),

						'sections' => array(

							'public' => array(

								'mask_wrapper'	=> '<div class="row" id="#id" data-id="#data_id">#sections</div>',
								'mask_single' 	=> '<div#attributes class="#class" id="#id" data-id="#data_id"#hidden>#section</div>',
								'class_single'	=> array('col')
							)
						),

						'fields' => array(

							'public' => array(

								// Label position - Left
								'left' => array(

									'mask'							=>	'<div class="row">#field</div>',
									'mask_field_label_wrapper'		=>	'<div class="col-xs-#column_width_label control-label text-right">#label</div>',
									'mask_field_wrapper'			=>	'<div class="col-xs-#column_width_field">#field</div>',
								),

								// Label position - Right
								'right' => array(

									'mask'							=>	'<div class="row">#field</div>',
									'mask_field_label_wrapper'		=>	'<div class="col-xs-#column_width_label control-label">#label</div>',
									'mask_field_wrapper'			=>	'<div class="col-xs-#column_width_field">#field</div>',
								),

								// Flex within a fieldset doesn't work, so we embed a div within the fieldset to get it working
								'mask_wrapper' 		=> '<fieldset#attributes>#label<div class="row" id="#id" data-id="#data_id">#fields</div></fieldset>',
								'mask_wrapper_label'	=> '<legend>#label</legend>',
								'mask_single' 		=> '<div class="#class" id="#id" data-id="#data_id" data-type="#type"#attributes>#field</div>',

								// Required
								'mask_required_label'	=> ' <strong class="text-danger">*</strong>',

								// Help
								'mask_help'			=>	'<span id="#help_id" class="#help_class">#help#help_append</span>',

								// Invalid feedback
								'mask_invalid_feedback'	=>	'<div id="#invalid_feedback_id" class="#invalid_feedback_class">#invalid_feedback</div>',

								// Classes - Default
								'class_single'				=> array('form-group'),
								'class_field'				=> array('form-control'),
								'class_field_label'			=> array(),
								'class_help'				=> array('help-block'),
								'class_invalid_feedback'	=> array('help-block', 'wsf-invalid-feedback'),
								'class_inline' 				=> array('form-inline'),
								'class_form_validated'		=> array('wsf-validated'),
								'class_field_button_type'	=> array(

									'default'		=>	'btn-default',
									'primary'		=>	'btn-primary',
									'success'		=>	'btn-success',
									'information'	=>	'btn-info',
									'warning'		=>	'btn-warning',
									'danger'		=>	'btn-danger'
								),

								// Classes - Custom by field type
								'field_types'		=> array(

									'checkbox' 	=> array(

										'class_field'			=> array(),
										'mask_row_label'		=> '<label id="#label_row_id" for="#row_id"#attributes>#row_field#checkbox_field_label#required#invalid_feedback</label>',
										'class_row'				=> array('checkbox'),
										'class_row_disabled'	=> array('disabled'),
										'class_inline' 			=> array('checkbox-inline'),
									),

									'radio' 	=> array(

										'class_field'			=> array(),
										'mask_row_label'		=> '<label id="#label_row_id" for="#row_id" data-label-required-id="#label_id"#attributes>#row_field#radio_field_label#required#invalid_feedback</label>',
										'class_row'				=> array('radio'),
										'class_row_disabled'	=> array('disabled'),
										'class_inline' 			=> array('radio-inline'),
									),

									'spacer' 	=> array(
										'class_single'			=> array(),
									),

									'submit' 	=> array(
										'class_field'						=> array('btn'),
										'class_field_full_button'			=> array('btn-block'),
										'class_field_button_type_fallback'	=> 'primary'
									),

								)
							)
						)
					),

					'bootstrap4'	=> array(

						'name'						=>	__('Bootstrap 4.0', 'ws-form'),

						'default'					=>	false,

						'css_file'					=>	'bootstrap4.css',

						'label_positions'			=>	array('default', 'top', 'left', 'right', 'bottom'),

						'minicolors_args'			=>	array(

							'changeDelay' 	=> 200,
							'letterCase' 	=> 'uppercase',
							'theme' 		=> 'bootstrap'
						),

						'columns'					=>	array(

							'column_count' 			=> 	12,
							'column_class'				=>	'col-#id-#size',
							'column_css_selector'		=>	'.col-#id-#size',
							'offset_class'			=>	'offset-#id-#offset',
							'offset_css_selector'	=>	'.offset-#id-#offset'
						),

						'breakpoints'				=>	array(

							// Up to 575px
							25	=>	array(
								'id'				=>	'xs',
								'name'				=>	__('Extra Small', 'ws-form'),
								'column_class'			=>	'col-#size',
								'column_css_selector'	=>	'.col-#size',
								'offset_class'			=>	'offset-#offset',
								'offset_css_selector'	=>	'.offset-#offset',
								'admin_max_width'		=>	575,
								'column_size_default'	=>	'column_count'	// Set to column count if XS framework breakpoint size is not set in object meta
							),
						),

						'form' => array(

							'admin' => array('mask_single' => '#form'),
							'public' => array(

								'mask_single' 	=> '#label#form',
								'mask_label'	=> '<h2>#label</h2>',
							),
						),

						'tabs' => array(

							'public' => array(

								'mask_wrapper'		=>	'<ul class="nav nav-tabs">#tabs</ul>',
								'mask_single'		=>	'<li class="nav-item"><a class="nav-link" href="#href" data-toggle="tab">#label</a></li>',
								'activate_js'		=>	"$('#form ul.nav-tabs li:eq(#index) a').tab('show');",
								'event_js'			=>	'shown.bs.tab',
								'event_type_js'		=>	'tab',
								'class_disabled'	=>	'disabled'
							),
						),

						'message' => array(

							'public'	=>	array(

								'mask_wrapper'		=>	'<div class="alert #mask_wrapper_class">#message</div>',

								'types'	=>	array(

									'success'		=>	array('mask_wrapper_class' => 'alert-success', 'text_class' => 'text-success'),
									'information'	=>	array('mask_wrapper_class' => 'alert-info', 'text_class' => 'text-info'),
									'warning'		=>	array('mask_wrapper_class' => 'alert-warning', 'text_class' => 'text-warning'),
									'danger'		=>	array('mask_wrapper_class' => 'alert-danger', 'text_class' => 'text-danger')
								)
							)
						),

						'action_js' => array(

							'message'	=>	array(

								'mask_wrapper'		=>	'<div class="alert #mask_wrapper_class">#message</div>',

								'types'	=>	array(

									'success'		=>	array('mask_wrapper_class' => 'alert-success'),
									'information'	=>	array('mask_wrapper_class' => 'alert-info'),
									'warning'		=>	array('mask_wrapper_class' => 'alert-warning'),
									'danger'		=>	array('mask_wrapper_class' => 'alert-danger')
								)
							)
						),

						'groups' => array(

							'public' => array(

								'mask_wrapper'	=>	'<div class="tab-content">#groups</div>',
								'mask_single' 	=> '<div class="#class" id="#id" data-id="#data_id" data-group-index="#data_group_index">#label#group</div>',
								'mask_label' 	=> '<h3>#label</h3>',
								'class'			=> 'tab-pane',
								'class_active'	=> 'active',
							)
						),

						'sections' => array(

							'public' => array(

								'mask_wrapper'	=> '<div class="row" id="#id" data-id="#data_id">#sections</div>',
								'mask_single' 	=> '<div#attributes class="#class" id="#id" data-id="#data_id"#hidden>#section</div>',
							)
						),

						'fields' => array(

							'public' => array(

								// Label position - Left
								'left' => array(

									'mask'							=>	'<div class="row">#field</div>',
									'mask_field_label_wrapper'		=>	'<div class="col-#column_width_label col-form-label text-right">#label</div>',
									'mask_field_wrapper'			=>	'<div class="col-#column_width_field">#field</div>',
								),

								// Label position - Right
								'right' => array(

									'mask'							=>	'<div class="row">#field</div>',
									'mask_field_label_wrapper'		=>	'<div class="col-#column_width_label col-form-label">#label</div>',
									'mask_field_wrapper'			=>	'<div class="col-#column_width_field">#field</div>',
								),

								// Flex within a fieldset doesn't work, so we embed a div within the fieldset to get it working
								'mask_wrapper' 		=> '<fieldset#attributes>#label<div class="row" id="#id" data-id="#data_id">#fields</div></fieldset>',
								'mask_wrapper_label'	=> '<legend>#label</legend>',
								'mask_single' 		=> '<div class="#class" id="#id" data-id="#data_id" data-type="#type"#attributes>#field</div>',

								// Required
								'mask_required_label'	=> ' <strong class="text-danger">*</strong>',

								// Help
								'mask_help'			=>	'<small id="#help_id" class="#help_class">#help#help_append</small>',

								// Invalid feedback
								'mask_invalid_feedback'	=>	'<div id="#invalid_feedback_id" class="#invalid_feedback_class">#invalid_feedback</div>',

								// Classes - Default
								'class_single'					=> array('form-group'),
//								'class_single_required'			=> array('required'),
								'class_field'					=> array('form-control'),
								'class_field_label'				=> array(),
								'class_help'					=> array('form-text', 'text-muted'),
								'class_invalid_feedback'		=> array('invalid-feedback'),
								'class_inline' 					=> array('form-inline'),
								'class_form_validated'			=> array('was-validated'),
								'class_single_vertical_align'	=> array(

									'middle'	=>	'align-self-center',
									'bottom'	=>	'align-self-end'
								),
								'class_field_button_type'	=> array(

									'default'		=>	'btn-secondary',
									'primary'		=>	'btn-primary',
									'secondary'		=>	'btn-secondary',
									'success'		=>	'btn-success',
									'information'	=>	'btn-info',
									'warning'		=>	'btn-warning',
									'danger'		=>	'btn-danger'
								),

								// Classes - Custom by field type
								'field_types'		=> array(

									'select' 	=> array(
										'class_field'			=> array('custom-select')
									),

									'checkbox' 	=> array(
										
										'class_field'			=> array(),
										'class_row'				=> array('custom-control', 'custom-checkbox'),
										'class_row_disabled'	=> array('disabled'),
										'class_row_field'		=> array('custom-control-input'),
										'class_row_field_label'	=> array('custom-control-label'),
										'class_inline' 			=> array('custom-control-inline'),
										'mask_field'			=> '<div#attributes>#data</div>#help',
										'mask_row_label'		=> '#row_field<label id="#label_row_id" for="#row_id"#attributes>#checkbox_field_label</label>#invalid_feedback',
									),

									'radio' 	=> array(

										'class_field'			=> array(),
										'class_row'				=> array('custom-control', 'custom-radio'),
										'class_row_disabled'	=> array('disabled'),
										'class_row_field'		=> array('custom-control-input'),
										'class_row_field_label'	=> array('custom-control-label'),
										'class_inline' 			=> array('custom-control-inline'),
										'mask_row_label'		=> '#row_field<label id="#label_row_id" for="#row_id" data-label-required-id="#label_id"#attributes>#radio_field_label</label>#invalid_feedback'
									),

									'spacer' 	=> array(
										'class_single'			=> array(),
									),


									'submit' 	=> array(
										'class_field'						=> array('btn'),
										'class_field_full_button'			=> array('btn-block'),
										'class_field_button_type_fallback'	=> 'primary'
									),

								)
							)
						)
					),

					'bootstrap41'	=> array(

						'name'						=>	__('Bootstrap 4.1+', 'ws-form'),

						'default'					=>	false,

						'css_file'					=>	'bootstrap41.css',

						'label_positions'			=>	array('default', 'top', 'left', 'right', 'bottom'),

						'minicolors_args'			=>	array(

							'changeDelay' 	=> 200,
							'letterCase' 	=> 'uppercase',
							'theme' 		=> 'bootstrap'
						),

						'columns'					=>	array(

							'column_count' 			=> 	12,
							'column_class'				=>	'col-#id-#size',
							'column_css_selector'		=>	'.col-#id-#size',
							'offset_class'			=>	'offset-#id-#offset',
							'offset_css_selector'	=>	'.offset-#id-#offset'
						),

						'breakpoints'				=>	array(

							// Up to 575px
							25	=>	array(
								'id'				=>	'xs',
								'name'				=>	__('Extra Small', 'ws-form'),
								'column_class'			=>	'col-#size',
								'column_css_selector'	=>	'.col-#size',
								'offset_class'			=>	'offset-#offset',
								'offset_css_selector'	=>	'.offset-#offset',
								'admin_max_width'		=>	575,
								'column_size_default'	=>	'column_count'	// Set to column count if XS framework breakpoint size is not set in object meta
							),
						),

						'form' => array(

							'admin' => array('mask_single' => '#form'),
							'public' => array(

								'mask_single' 	=> '#label#form',
								'mask_label'	=> '<h2>#label</h2>',
							),
						),

						'tabs' => array(

							'public' => array(

								'mask_wrapper'		=>	'<ul class="nav nav-tabs">#tabs</ul>',
								'mask_single'		=>	'<li class="nav-item"><a class="nav-link" href="#href" data-toggle="tab">#label</a></li>',
								'activate_js'		=>	"$('#form ul.nav-tabs li:eq(#index) a').tab('show');",
								'event_js'			=>	'shown.bs.tab',
								'event_type_js'		=>	'tab',
								'class_disabled'	=>	'disabled'
							),
						),

						'message' => array(

							'public'	=>	array(

								'mask_wrapper'		=>	'<div class="alert #mask_wrapper_class">#message</div>',

								'types'	=>	array(

									'success'		=>	array('mask_wrapper_class' => 'alert-success', 'text_class' => 'text-success'),
									'information'	=>	array('mask_wrapper_class' => 'alert-info', 'text_class' => 'text-info'),
									'warning'		=>	array('mask_wrapper_class' => 'alert-warning', 'text_class' => 'text-warning'),
									'danger'		=>	array('mask_wrapper_class' => 'alert-danger', 'text_class' => 'text-danger')
								)
							)
						),

						'action_js' => array(

							'message'	=>	array(

								'mask_wrapper'		=>	'<div class="alert #mask_wrapper_class">#message</div>',

								'types'	=>	array(

									'success'		=>	array('mask_wrapper_class' => 'alert-success'),
									'information'	=>	array('mask_wrapper_class' => 'alert-info'),
									'warning'		=>	array('mask_wrapper_class' => 'alert-warning'),
									'danger'		=>	array('mask_wrapper_class' => 'alert-danger')
								)
							)
						),

						'groups' => array(

							'public' => array(

								'mask_wrapper'	=>	'<div class="tab-content">#groups</div>',
								'mask_single' 	=> '<div class="#class" id="#id" data-id="#data_id" data-group-index="#data_group_index">#label#group</div>',
								'mask_label' 	=> '<h3>#label</h3>',
								'class'			=> 'tab-pane',
								'class_active'	=> 'active',
							)
						),

						'sections' => array(

							'public' => array(

								'mask_wrapper'	=> '<div class="row" id="#id" data-id="#data_id">#sections</div>',
								'mask_single' 	=> '<div#attributes class="#class" id="#id" data-id="#data_id"#hidden>#section</div>',
							)
						),

						'fields' => array(

							'public' => array(

								// Label position - Left
								'left' => array(

									'mask'							=>	'<div class="row">#field</div>',
									'mask_field_label_wrapper'		=>	'<div class="col-#column_width_label col-form-label text-right">#label</div>',
									'mask_field_wrapper'			=>	'<div class="col-#column_width_field">#field</div>',
								),

								// Label position - Right
								'right' => array(

									'mask'							=>	'<div class="row">#field</div>',
									'mask_field_label_wrapper'		=>	'<div class="col-#column_width_label col-form-label">#label</div>',
									'mask_field_wrapper'			=>	'<div class="col-#column_width_field">#field</div>',
								),

								// Flex within a fieldset doesn't work, so we embed a div within the fieldset to get it working
								'mask_wrapper' 		=> '<fieldset#attributes>#label<div class="row" id="#id" data-id="#data_id">#fields</div></fieldset>',
								'mask_wrapper_label'	=> '<legend>#label</legend>',
								'mask_single' 		=> '<div class="#class" id="#id" data-id="#data_id" data-type="#type"#attributes>#field</div>',

								// Required
								'mask_required_label'	=> ' <strong class="text-danger">*</strong>',

								// Help
								'mask_help'			=>	'<small id="#help_id" class="#help_class">#help#help_append</small>',

								// Invalid feedback
								'mask_invalid_feedback'	=>	'<div id="#invalid_feedback_id" class="#invalid_feedback_class">#invalid_feedback</div>',

								// Classes - Default
								'class_single'					=> array('form-group'),
//								'class_single_required'			=> array('required'),
								'class_field'					=> array('form-control'),
								'class_field_label'				=> array(),
								'class_help'					=> array('form-text', 'text-muted'),
								'class_invalid_feedback'		=> array('invalid-feedback'),
								'class_inline' 					=> array('form-inline'),
								'class_form_validated'			=> array('was-validated'),
								'class_single_vertical_align'	=> array(

									'middle'	=>	'align-self-center',
									'bottom'	=>	'align-self-end'
								),
								'class_field_button_type'	=> array(

									'default'		=>	'btn-secondary',
									'primary'		=>	'btn-primary',
									'secondary'		=>	'btn-secondary',
									'success'		=>	'btn-success',
									'information'	=>	'btn-info',
									'warning'		=>	'btn-warning',
									'danger'		=>	'btn-danger'
								),

								// Classes - Custom by field type
								'field_types'		=> array(

									'select' 	=> array(
										'class_field'			=> array('custom-select')
									),

									'checkbox' 	=> array(

										'class_field'			=> array(),
										'class_row'				=> array('custom-control', 'custom-checkbox'),
										'class_row_disabled'	=> array('disabled'),
										'class_row_field'		=> array('custom-control-input'),
										'class_row_field_label'	=> array('custom-control-label'),
										'class_inline' 			=> array('custom-control-inline'),
										'mask_field'			=> '<div#attributes>#data</div>#help',
										'mask_row_label'		=> '#row_field<label id="#label_row_id" for="#row_id"#attributes>#checkbox_field_label</label>#invalid_feedback',
									),

									'radio' 	=> array(

										'class_field'			=> array(),
										'class_row'				=> array('custom-control', 'custom-radio'),
										'class_row_disabled'	=> array('disabled'),
										'class_row_field'		=> array('custom-control-input'),
										'class_row_field_label'	=> array('custom-control-label'),
										'class_inline' 			=> array('custom-control-inline'),
										'mask_row_label'		=> '#row_field<label id="#label_row_id" for="#row_id" data-label-required-id="#label_id"#attributes>#radio_field_label</label>#invalid_feedback'
									),

									'spacer' 	=> array(
										'class_single'			=> array(),
									),

									'submit' 	=> array(
										'class_field'						=> array('btn'),
										'class_field_full_button'			=> array('btn-block'),
										'class_field_button_type_fallback'	=> 'primary'
									),

								)
							)
						)
					),

					'foundation5'	=> array(

						'name'						=>	__('Foundation 5.x', 'ws-form'),

						'default'					=>	false,

						'css_file'					=>	'foundation5.css',

						'label_positions'			=>	array('default', 'top', 'left', 'right', 'bottom'),

						'init_js'					=>	"if(typeof($(document).foundation) === 'function') { $(document).foundation('tab', 'reflow'); }",

						'minicolors_args'			=>	array(

							'theme' 					=> 'foundation'
						),

						'columns'					=>	array(

							'column_count' 			=> 	12,
							'column_class'				=>	'#id-#size',
							'column_css_selector'		=>	'.#id-#size',
							'offset_class'				=>	'#id-offset-#offset',
							'offset_css_selector'		=>	'.#id-offset-#offset'
						),

						'breakpoints'				=>	array(

							// Up to 639px
							25	=>	array(
								'id'				=>	'small',
								'name'				=>	__('Small', 'ws-form'),
								'column_class'			=>	'#id-#size',
								'column_css_selector'	=>	'.#id-#size',
								'admin_max_width'	=>	640,
								'column_size_default'	=>	'column_count'	// Set to column count if XS framework breakpoint size is not set in object meta
							),
						),

						'form' => array(

							'admin' => array('mask_single' => '#form'),
							'public' => array(

								'mask_single' 	=> '#label#form',
								'mask_label'	=> '<h2>#label</h2>',
								'attributes' => array('data-abide' => '')
							),
						),

						'tabs' => array(

							'public' => array(

								'mask_wrapper'				=>	'<dl class="tabs" data-tab id="#id">#tabs</dl>',
								'mask_single'				=>	'<dd class="tab-title#active"><a href="#href">#label</a></dd>',
								'active'					=>	' active',
								'activate_js'				=>	"$('#form .tabs .tab-title:eq(#index) a').click();",
								'event_js'					=>	'toggled',
								'event_type_js'				=>	'wrapper',
								'event_selector_wrapper_js'	=>	'dl[data-tab]',
								'event_selector_active_js'	=>	'dd.active',
								'class_parent_disabled'		=>	'wsf-tab-disabled'
							),
						),

						'message' => array(

							'public'	=>	array(

								'mask_wrapper'		=>	'<div class="alert-box #mask_wrapper_class">#message</div>',

								'types'	=>	array(

									'success'		=>	array('mask_wrapper_class' => 'success'),
									'information'	=>	array('mask_wrapper_class' => 'info'),
									'warning'		=>	array('mask_wrapper_class' => 'warning'),
									'danger'		=>	array('mask_wrapper_class' => 'alert')
								)
							)
						),

						'groups' => array(

							'public' => array(

								'mask_wrapper'	=>	'<div class="tabs-content" data-tabs-content="#id">#groups</div>',
								'mask_single' 	=> '<div class="#class" id="#id" data-id="#data_id" data-group-index="#data_group_index">#label#group</div>',
								'mask_label' 	=> '<h3>#label</h3>',
								'class'			=> 'content',
								'class_active'	=> 'active',
							)
						),

						'sections' => array(

							'public' => array(

								'mask_wrapper'	=> '<div class="row" id="#id" data-id="#data_id">#sections</div>',
								'mask_single' 	=> '<div#attributes class="#class" id="#id" data-id="#data_id"#hidden>#section</div>',
								'class_single'	=> array('columns')
							)
						),

						'fields' => array(

							'public' => array(

								// Honeypot attributes
								'honeypot_attributes' => array('data-abide-ignore'),

								// Label position - Left
								'left' => array(

									'mask'									=>	'<div class="row">#field</div>',
									'mask_field_label_wrapper'				=>	'<div class="small-#column_width_label columns">#label</div>',
									'mask_field_label'						=>	'<label id="#label_id" for="#id"#attributes>#label</label>',
									'mask_field_wrapper'					=>	'<div class="small-#column_width_field columns">#field</div>',
									'class_field_label'						=>	array('text-right', 'middle'),
								),

								// Label position - Right
								'right' => array(

									'mask'									=>	'<div class="row">#field</div>',
									'mask_field_label_wrapper'				=>	'<div class="small-#column_width_label columns">#label</div>',
									'mask_field_label'						=>	'<label id="#label_id" for="#id"#attributes>#label</label>',
									'mask_field_wrapper'					=>	'<div class="small-#column_width_field columns">#field</div>',
									'class_field_label'						=>	array('middle'),
								),

								// Flex within a fieldset doesn't work, so we embed a div within the fieldset to get it working
								'mask_wrapper' 			=> '<fieldset#attributes>#label<div class="row" id="#id" data-id="#data_id">#fields</div></fieldset>',
								'mask_wrapper_label'	=> '<legend>#label</legend>',
								'mask_single' 			=> '<div class="#class" id="#id" data-id="#data_id" data-type="#type"#attributes>#field</div>',
								'mask_field_label'		=>	'<label id="#label_id" for="#id"#attributes>#label#field</label>',

								// Required
								'mask_required_label'	=> ' <small>Required</small>',

								// Help
								'mask_help'				=>	'<p id="#help_id">#help#help_append</p>',

								// Invalid feedback
								'mask_invalid_feedback'	=>	'<small id="#invalid_feedback_id" data-form-error-for="#id" class="#invalid_feedback_class">#invalid_feedback</small>',

								// Classes - Default
								'class_single'				=> array('columns'),
								'class_field'				=> array(),
								'class_field_label'			=> array(),
								'class_help'				=> array(),
								'class_invalid_feedback'	=> array('error'),
								'class_inline' 				=> array('form-inline'),
								'class_form_validated'		=> array('was-validated'),
								'class_field_button_type'	=> array(

									'secondary'		=>	'secondary',
									'success'		=>	'success',
									'information'	=>	'info',
									'danger'		=>	'alert'
								),

								// Attributes
								'attribute_field_match'		=> array('data-equalto' => '#field_match_id'),

								// Classes - Custom by field type
								'field_types'		=> array(

									'checkbox' 	=> array(

										'class_inline' 			=> array(),
										'mask_field'			=> '<div#attributes>#data</div>#help',
										'mask_row_label'		=> '#row_field<label id="#label_row_id" for="#row_id"#attributes>#checkbox_field_label</label>#invalid_feedback',
									),

									'radio' 	=> array(

										'class_inline' 			=> array(),
										'mask_field'			=> '<div#attributes>#data</div>#help',
										'mask_row_label'		=> '#row_field<label id="#label_row_id" for="#row_id" data-label-required-id="#label_id"#attributes>#radio_field_label</label>#invalid_feedback',
									),

									'spacer'	=> array(

										'mask_field_label'		=>	'',
									),

									'texteditor'	=> array(

										'mask_field_label'		=>	'',
									),


									'submit' 	=> array(

										'mask_field_label'					=> '#label',
										'class_field'						=> array('button'),
										'class_field_full_button'			=> 	array('expand'),
										'class_field_button_type_fallback'	=> 'secondary'
									),

								)
							)
						)
					),

					'foundation6'	=> array(

						'name'						=>	__('Foundation 6.0-6.3.1', 'ws-form'),

						'default'					=>	false,

						'css_file'					=>	'foundation6.css',

						'label_positions'			=>	array('default', 'top', 'left', 'right', 'bottom'),

						'init_js'					=>	$framework_foundation_init_js,

						'minicolors_args'			=>	array(

							'theme' 				=> 'foundation'
						),

						'columns'					=>	array(

							'column_count' 				=> 	12,
							'column_class'				=>	'#id-#size',
							'column_css_selector'		=>	'.#id-#size',
							'offset_class'				=>	'#id-offset-#offset',
							'offset_css_selector'		=>	'.#id-offset-#offset'
						),

						'breakpoints'				=>	array(

							// Up to 639px
							25	=>	array(
								'id'					=>	'small',
								'name'					=>	__('Small', 'ws-form'),
								'column_class'			=>	'#id-#size',
								'column_css_selector'	=>	'.#id-#size',
								'admin_max_width'		=>	639,
								'column_size_default'	=>	'column_count'	// Set to column count if XS framework breakpoint size is not set in object meta
							),
						),

						'form' => array(

							'admin' => array('mask_single' => '#form'),
							'public' => array(

								'mask_single' 	=> '#label#form',
								'mask_label'	=> '<h2>#label</h2>',
								'attributes' => array('data-abide' => '')
							),
						),

						'tabs' => array(

							'public' => array(

								'mask_wrapper'				=>	'<ul class="tabs" data-tabs id="#id">#tabs</ul>',
								'mask_single'				=>	'<li class="tabs-title#active"><a href="#href">#label</a></li>',
								'active'					=>	' is-active',
								'activate_js'				=>	"$('#form .tabs .tabs-title:eq(#index) a').click();",
								'event_js'					=>	'change.zf.tabs',
								'event_type_js'				=>	'wrapper',
								'event_selector_wrapper_js'	=>	'ul[data-tabs]',
								'event_selector_active_js'	=>	'li.is-active',
								'class_parent_disabled'		=>	'wsf-tab-disabled'
							),
						),

						'message' => array(

							'public'	=>	array(

								'mask_wrapper'		=>	'<div class="callout #mask_wrapper_class">#message</div>',

								'types'	=>	array(

									'success'		=>	array('mask_wrapper_class' => 'success'),
									'information'	=>	array('mask_wrapper_class' => 'primary'),
									'warning'		=>	array('mask_wrapper_class' => 'warning'),
									'danger'		=>	array('mask_wrapper_class' => 'alert')
								)
							)
						),

						'groups' => array(

							'public' => array(

								'mask_wrapper'	=>	'<div class="tabs-content" data-tabs-content="#id">#groups</div>',
								'mask_single' 	=> '<div class="#class" id="#id" data-id="#data_id" data-group-index="#data_group_index">#label#group</div>',
								'mask_label' 	=> '<h3>#label</h3>',
								'class'			=> 'tabs-panel',
								'class_active'	=> 'is-active',
							)
						),

						'sections' => array(

							'public' => array(

								'mask_wrapper'	=> '<div class="row" id="#id" data-id="#data_id">#sections</div>',
								'mask_single' 	=> '<div#attributes class="#class" id="#id" data-id="#data_id"#hidden>#section</div>',
								'class_single'	=> array('columns')
							)
						),

						'fields' => array(

							'public' => array(

								// Honeypot attributes
								'honeypot_attributes' => array('data-abide-ignore'),

								// Label position - Left
								'left' => array(

									'mask'							=>	'<div class="row">#field</div>',
									'mask_field_label_wrapper'		=>	'<div class="small-#column_width_label columns">#label</div>',
									'mask_field_label'				=>	'<label id="#label_id" for="#id"#attributes>#label</label>',
									'mask_field_wrapper'			=>	'<div class="small-#column_width_field columns">#field</div>',
									'class_field_label'				=>	array('text-right', 'middle'),
								),

								// Label position - Right
								'right' => array(

									'mask'							=>	'<div class="row">#field</div>',
									'mask_field_label_wrapper'		=>	'<div class="small-#column_width_label columns">#label</div>',
									'mask_field_label'				=>	'<label id="#label_id" for="#id"#attributes>#label</label>',
									'mask_field_wrapper'			=>	'<div class="small-#column_width_field columns">#field</div>',
									'class_field_label'				=>	array('middle'),
								),

								// Flex within a fieldset doesn't work, so we embed a div within the fieldset to get it working
								'mask_wrapper' 			=> '<fieldset#attributes>#label<div class="row" id="#id" data-id="#data_id">#fields</div></fieldset>',
								'mask_wrapper_label'	=> '<legend>#label</legend>',
								'mask_single' 			=> '<div class="#class" id="#id" data-id="#data_id" data-type="#type"#attributes>#field</div>',
								'mask_field_label'		=>	'<label id="#label_id" for="#id"#attributes>#label#field</label>',


								// Required
								'mask_required_label'	=> ' <small>Required</small>',

								// Help
								'mask_help'				=>	'<p id="#help_id" class="#help_class">#help#help_append</p>',

								// Invalid feedback
								'mask_invalid_feedback'	=>	'<span id="#invalid_feedback_id" data-form-error-for="#id" class="#invalid_feedback_class">#invalid_feedback</span>',

								// Classes - Default
								'class_single'					=> array('columns'),
								'class_field'					=> array(),
								'class_field_label'				=> array(),
								'class_help'					=> array('help-text'),
								'class_invalid_feedback'		=> array('form-error'),
								'class_inline' 					=> array('form-inline'),
								'class_form_validated'			=> array('was-validated'),
								'class_single_vertical_align'	=> array(

									'middle'	=>	'align-self-middle',
									'bottom'	=>	'align-self-bottom'
								),
								'class_field_button_type'	=> array(

									'primary'		=>	'primary',
									'secondary'		=>	'secondary',
									'success'		=>	'success',
									'warning'		=>	'warning',
									'danger'		=>	'alert'
								),

								// Attributes
								'attribute_field_match'		=> array('data-equalto' => '#field_match_id'),

								// Classes - Custom by field type
								'field_types'				=> array(

									'checkbox' 	=> array(

										'class_inline' 			=> array(),
										'mask_field'			=> '<div#attributes>#data</div>#help',
										'mask_row_label'		=> '#row_field<label id="#label_row_id" for="#row_id"#attributes>#checkbox_field_label</label>#invalid_feedback',
									),

									'radio' 				=> array(

										'class_inline' 			=> array(),
										'mask_field'			=> '<div#attributes>#data</div>#help',
										'mask_row_label'		=> '#row_field<label id="#label_row_id" for="#row_id" data-label-required-id="#label_id"#attributes>#radio_field_label</label>#invalid_feedback',
									),

									'spacer'	=> array(

										'mask_field_label'		=>	'',
									),

									'texteditor'	=> array(

										'mask_field_label'		=>	'',
									),

									'submit' 	=> array(

										'mask_field_label'					=>	'#label',
										'class_field'						=>	array('button'),
										'class_field_full_button'			=> array('expanded'),
										'class_field_button_type_fallback'	=> 'primary'
									),
								)
							)
						)
					),

					'foundation64'	=> array(

						'name'						=>	__('Foundation 6.4+', 'ws-form'),

						'default'					=>	false,

						'css_file'					=>	'foundation64.css',

						'label_positions'			=>	array('default', 'top', 'left', 'right', 'bottom'),

						'init_js'					=>	$framework_foundation_init_js,

						'minicolors_args'			=>	array(

							'theme' => 'foundation'
						),

						'columns'					=>	array(

							'column_count' 			=> 	12,
							'column_class'				=>	'#id-#size',
							'column_css_selector'		=>	'.#id-#size',
							'offset_class'				=>	'#id-offset-#offset',
							'offset_css_selector'		=>	'.#id-offset-#offset'
						),

						'breakpoints'				=>	array(

							// Up to 639px
							25	=>	array(
								'id'					=>	'small',
								'name'					=>	__('Small', 'ws-form'),
								'column_class'			=>	'#id-#size',
								'column_css_selector'	=>	'.#id-#size',
								'admin_max_width'		=>	639,
								'column_size_default'	=>	'column_count'	// Set to column count if XS framework breakpoint size is not set in object meta
							),
						),

						'form' => array(

							'admin' => array('mask_single' => '#form'),
							'public' => array(

								'mask_single' 	=> '#label#form',
								'mask_label'	=> '<h2>#label</h2>',
								'attributes' 	=> array('data-abide' => '')
							),
						),

						'tabs' => array(

							'public' => array(

								'mask_wrapper'				=>	'<ul class="tabs" data-tabs id="#id">#tabs</ul>',
								'mask_single'				=>	'<li class="tabs-title#active"><a href="#href">#label</a></li>',
								'active'					=>	' is-active',
								'activate_js'				=>	"$('#form .tabs .tabs-title:eq(#index) a').click();",
								'event_js'					=>	'change.zf.tabs',
								'event_type_js'				=>	'wrapper',
								'event_selector_wrapper_js'	=>	'ul[data-tabs]',
								'event_selector_active_js'	=>	'li.is-active',
								'class_parent_disabled'		=>	'wsf-tab-disabled'
							),
						),

						'message' => array(

							'public'	=>	array(

								'mask_wrapper'		=>	'<div class="callout #mask_wrapper_class">#message</div>',

								'types'	=>	array(

									'success'		=>	array('mask_wrapper_class' => 'success'),
									'information'	=>	array('mask_wrapper_class' => 'primary'),
									'warning'		=>	array('mask_wrapper_class' => 'warning'),
									'danger'		=>	array('mask_wrapper_class' => 'alert')
								)
							)
						),

						'groups' => array(

							'public' => array(

								'mask_wrapper'	=>	'<div class="tabs-content" data-tabs-content="#id">#groups</div>',
								'mask_single' 	=> '<div class="#class" id="#id" data-id="#data_id" data-group-index="#data_group_index">#label#group</div>',
								'mask_label' 	=> '<h3>#label</h3>',
								'class'			=> 'tabs-panel',
								'class_active'	=> 'is-active',
							)
						),

						'sections' => array(

							'public' => array(

								'mask_wrapper'	=> '<div class="grid-x grid-margin-x" id="#id" data-id="#data_id">#sections</div>',
								'mask_single' 	=> '<div#attributes class="#class" id="#id" data-id="#data_id"#hidden>#section</div>',

								'class_single'	=> array('cell')
							)
						),

						'fields' => array(

							'public' => array(

								// Honeypot attributes
								'honeypot_attributes' => array('data-abide-ignore'),

								// Label position - Left
								'left' => array(

									'mask'							=>	'<div class="grid-x grid-padding-x">#field</div>',
									'mask_field_label_wrapper'		=>	'<div class="small-#column_width_label cell">#label</div>',
									'mask_field_label'				=>	'<label id="#label_id" for="#id"#attributes>#label</label>',
									'mask_field_wrapper'			=>	'<div class="small-#column_width_field cell">#field</div>',
									'class_field_label'				=>	array('text-right', 'middle'),
								),

								// Label position - Right
								'right' => array(

									'mask'							=>	'<div class="grid-x grid-padding-x">#field</div>',
									'mask_field_label_wrapper'		=>	'<div class="small-#column_width_label cell">#label</div>',
									'mask_field_label'				=>	'<label id="#label_id" for="#id"#attributes>#label</label>',
									'mask_field_wrapper'			=>	'<div class="small-#column_width_field cell">#field</div>',
									'class_field_label'				=>	array('middle'),
								),

								// Flex within a fieldset doesn't work, so we embed a div within the fieldset to get it working
								'mask_wrapper' 			=> '<fieldset#attributes>#label<div class="grid-x grid-margin-x" id="#id" data-id="#data_id">#fields</div></fieldset>',
								'mask_wrapper_label'	=> '<legend>#label</legend>',
								'mask_single' 			=> '<div class="#class" id="#id" data-id="#data_id" data-type="#type"#attributes>#field</div>',
								'mask_field_label'		=> '<label id="#label_id" for="#id"#attributes>#label#field</label>',

								// Required
								'mask_required_label'	=> ' <small>Required</small>',

								// Help
								'mask_help'			=>	'<p id="#help_id" class="#help_class">#help#help_append</p>',

								// Invalid feedback
								'mask_invalid_feedback'		=>	'<span id="#invalid_feedback_id" data-form-error-for="#id" class="#invalid_feedback_class">#invalid_feedback</span>',

								// Classes - Default
								'class_single'				=> array('cell'),
								'class_field'				=> array(),
								'class_field_label'			=> array(),
								'class_help'				=> array('help-text'),
								'class_invalid_feedback'	=> array('form-error'),
								'class_inline' 				=> array('form-inline'),
								'class_form_validated'		=> array('was-validated'),
								'class_single_vertical_align'	=> array(

									'middle'	=>	'align-self-middle',
									'bottom'	=>	'align-self-bottom'
								),
								'class_field_button_type'	=> array(

									'primary'		=>	'primary',
									'secondary'		=>	'secondary',
									'success'		=>	'success',
									'warning'		=>	'warning',
									'danger'		=>	'alert'
								),

								// Attributes
								'attribute_field_match'		=> array('data-equalto' => '#field_match_id'),

								// Classes - Custom by field type
								'field_types'		=> array(

									'checkbox' 	=> array(

										'class_inline' 			=> array(),
										'mask_field'			=> '<div#attributes>#data</div>#help',
										'mask_row_label'		=> '#row_field<label id="#label_row_id" for="#row_id"#attributes>#checkbox_field_label</label>#invalid_feedback',
									),

									'radio' 	=> array(

										'class_inline' 			=> array(),
										'mask_field'			=> '<div#attributes>#data</div>#help',
										'mask_row_label'		=> '#row_field<label id="#label_row_id" for="#row_id" data-label-required-id="#label_id"#attributes>#radio_field_label</label>#invalid_feedback',
									),

									'spacer'	=> array(

										'mask_field_label'		=>	'',
									),

									'texteditor'	=> array(

										'mask_field_label'		=>	'',
									),

									'submit' 	=> array(

										'mask_field_label'					=> '#label',
										'class_field'						=> array('button'),
										'class_field_full_button'			=> array('expanded'),
										'class_field_button_type_fallback'	=> 'default'
									),

								)
							)
						)
					)
				),

				// Auto detection of framework based on string searching in CSS files for a website
				'auto_detect'	=> array(

					// Exclude filenames containing the following strings
					'exclude_filenames' => array(

						'ws-form',
						'jquery',
						'plugins',
						'uploads',
						'wp-includes'
					),

					// Strings to look for in CSS for each framework type
					'types'	=> array(

						'bootstrap41'	=> array(

							'Bootstrap v4',
							'.col-form-label',
							'.form-control-plaintext',
							'.row',
							'.custom-range'
						),

						'bootstrap4'	=> array(

							'Bootstrap v4',
							'.col-form-label',
							'.form-control-plaintext',
							'.row'
						),

						'bootstrap3'	=> array(

							'Bootstrap v3',
							'.control-label',
							'.form-control-static'
						),

						'foundation64'	=> array(

							'.cell',
							'.grid-x',
							'.grid-y' 
						),

						'foundation6'	=> array(

							'.columns',
							'.hide-for-small-only'
						),

						'foundation5'	=> array(

							'.hide-for-small',
							'.tab-title'
						)				
					)
				)
			);

			// Apply filter
			$frameworks = apply_filters('wsf_config_frameworks', $frameworks);

			// Public filter
			if($public) {

				// Get current framework
				$framework = WS_Form_Common::option_get('framework', 'ws-form');

				// Remove irrelevant frameworks
				foreach($frameworks['types'] as $type => $value) {

					if($type != $framework) { unset($frameworks['types'][$type]); }
				}

				// Remove icons
				unset($frameworks['icons']);
			}

			// Cache
			self::$frameworks[$public] = $frameworks;

			return $frameworks;
		}


		// Parse variables
		public static function get_parse_variables($public = true) {

			// Check cache
			if(isset(self::$parse_variables[$public])) { return self::$parse_variables[$public]; }

			// Get email logo
			$email_logo = '';
			$action_email_logo = absint(WS_Form_Common::option_get('action_email_logo'));
			$action_email_logo_size = WS_Form_Common::option_get('action_email_logo_size');
			if($action_email_logo_size == '') { $action_email_logo_size = 'full'; }
			if($action_email_logo > 0) {

				$email_logo = wp_get_attachment_image($action_email_logo, $action_email_logo_size);
			}

			// Parse variables
			$parse_variables = array(

				// Blog
				'blog'	=>	array(

					'label'		=> __('Blog', 'ws-form'),

					'variables'	=> array(

						'blog_url'			=> array('label' => __('URL', 'ws-form'), 'value' => get_bloginfo('url')),
						'blog_name'			=> array('label' => __('Name', 'ws-form'), 'value' => get_bloginfo('name')),
						'blog_language'		=> array('label' => __('Language', 'ws-form'), 'value' => get_bloginfo('language')),
						'blog_charset'		=> array('label' => __('Character Set', 'ws-form'), 'value' => get_bloginfo('charset')),
						'blog_admin_email'	=> array('label' => __('Admin Email', 'ws-form'), 'value' => get_bloginfo('admin_email')),

						'blog_time' => array('label' => __('Current Time', 'ws-form'), 'value' => date(get_option('time_format'), current_time('timestamp')), 'description' => __('Returns the blog time in the format configured in WordPress.', 'ws-form')),

						'blog_date_custom' => array(

							'label' => __('Custom Date', 'ws-form'),

							'value' => date('Y-m-d H:i:s', current_time('timestamp')),

							'attributes' => array(

								array('id' => 'format', 'required' => false, 'default' => 'm/d/Y H:i:s'),
							),

							'kb_slug' => 'date-formats',

							'description' => __('Returns the blog date and time in a specified format (PHP date format).', 'ws-form')
						),

						'blog_date' => array('label' => __('Current Date', 'ws-form'), 'value' => date(get_option('date_format'), current_time('timestamp')), 'description' => __('Returns the blog date in the format configured in WordPress.', 'ws-form')),
					)
				),

				// Client
				'client'	=>	array(

					'label'		=>__('Client', 'ws-form'),

					'variables'	=> array(

						'client_time' => array('label' => __('Current Time', 'ws-form'), 'limit' => 'in client facing areas', 'description' => __('Returns the users web browser local time in the format configured in WordPress.', 'ws-form')),

						'client_date_custom' => array(

							'label' => __('Custom Date', 'ws-form'),

							'attributes' => array(

								array('id' => 'format', 'required' => false, 'default' => 'm/d/Y H:i:s'),
							),

							'kb_slug' => 'date-formats',

							'limit' => 'in client facing areas',

							'description' => __('Returns the users web browser local date and time in a specified format (PHP date format).', 'ws-form')
						),

						'client_date' => array('label' => __('Current Date', 'ws-form'), 'limit' => 'in client facing areas', 'description' => __('Returns the users web browser local date in the format configured in WordPress.', 'ws-form')),
					)
 				),

				// Server
				'server'	=>	array(

					'label'		=>__('Server', 'ws-form'),

					'variables'	=> array(

						'server_time' => array('label' => __('Current Time', 'ws-form'), 'value' => date(get_option('time_format')), 'description' => __('Returns the server time in the format configured in WordPress.', 'ws-form')),

						'server_date_custom' => array(

							'label' => __('Custom Date', 'ws-form'),

							'value' => date('Y-m-d H:i:s'),

							'attributes' => array(

								array('id' => 'format', 'required' => false, 'default' => 'm/d/Y H:i:s'),
							),

							'kb_slug' => 'date-formats',

							'description' => __('Returns the server date and time in a specified format (PHP date format).', 'ws-form')
						),

						'server_date' => array('label' => __('Current Date', 'ws-form'), 'value' => date(get_option('date_format')), 'description' => __('Returns the server date in the format configured in WordPress.', 'ws-form')),
					)
 				),

				// Form
				'form' 		=> array(

					'label'		=> __('Form', 'ws-form'),

					'variables'	=> array(

						'form_obj_id'		=>	array('label' => __('DOM Selector ID', 'ws-form')),
						'form_label'		=>	array('label' => __('Label', 'ws-form')),
						'form_hash'			=>	array('label' => __('Session ID', 'ws-form')),
						'form_instance_id'	=>	array('label' => __('Instance ID', 'ws-form')),
						'form_id'			=>	array('label' => __('ID', 'ws-form')),
						'form_framework'	=>	array('label' => __('Framework', 'ws-form')),
						'form_checksum'		=>	array('label' => __('Checksum', 'ws-form')),
					)
				),

				// Submit
				'submit' 		=> array(

					'label'		=> __('Submission', 'ws-form'),

					'variables'	=> array(

						'submit_id'			=>	array('label' => __('ID', 'ws-form')),
						'submit_hash'		=>	array('label' => __('Hash', 'ws-form')),
						'submit_user_id'	=>	array('label' => __('User ID', 'ws-form')),
						'submit_admin_url'	=>	array('label' => __('Link to submission in WordPress admin', 'ws-form'))
					)
				),

				// Skin
				'skin'			=> array(

					'label'		=> __('Skin', 'ws-form'),

					'variables' => array(

						// Color
						'skin_color_default'		=>	array('label' => __('Color - Default', 'ws-form'), 'kb_slug' => 'customize-appearance', 'value' => WS_Form_Common::option_get('skin_color_default')),
						'skin_color_default_inverted'		=>	array('label' => __('Color - Default (Inverted)', 'ws-form'), 'kb_slug' => 'customize-appearance', 'value' => WS_Form_Common::option_get('skin_color_default_inverted')),
						'skin_color_default_light'		=>	array('label' => __('Color - Default (Light)', 'ws-form'), 'kb_slug' => 'customize-appearance', 'value' => WS_Form_Common::option_get('skin_color_default_light')),
						'skin_color_default_lighter'		=>	array('label' => __('Color - Default (Lighter)', 'ws-form'), 'kb_slug' => 'customize-appearance', 'value' => WS_Form_Common::option_get('skin_color_default_lighter')),
						'skin_color_default_lightest'		=>	array('label' => __('Color - Default (Lightest)', 'ws-form'), 'kb_slug' => 'customize-appearance', 'value' => WS_Form_Common::option_get('skin_color_default_lightest')),
						'skin_color_primary'		=>	array('label' => __('Color - Primary', 'ws-form'), 'kb_slug' => 'customize-appearance', 'value' => WS_Form_Common::option_get('skin_color_primary')),
						'skin_color_secondary'		=>	array('label' => __('Color - Secondary', 'ws-form'), 'kb_slug' => 'customize-appearance', 'value' => WS_Form_Common::option_get('skin_color_secondary')),
						'skin_color_success'		=>	array('label' => __('Color - Success', 'ws-form'), 'kb_slug' => 'customize-appearance', 'value' => WS_Form_Common::option_get('skin_color_success')),
						'skin_color_information'		=>	array('label' => __('Color - Information', 'ws-form'), 'kb_slug' => 'customize-appearance', 'value' => WS_Form_Common::option_get('skin_color_information')),
						'skin_color_warning'		=>	array('label' => __('Color - Warning', 'ws-form'), 'kb_slug' => 'customize-appearance', 'value' => WS_Form_Common::option_get('skin_color_warning')),
						'skin_color_danger'		=>	array('label' => __('Color - Danger', 'ws-form'), 'kb_slug' => 'customize-appearance', 'value' => WS_Form_Common::option_get('skin_color_danger')),

						// Font
						'skin_font_family'		=>	array('label' => __('Font - Family', 'ws-form'), 'kb_slug' => 'customize-appearance', 'value' => WS_Form_Common::option_get('skin_font_family')),
						'skin_font_size'		=>	array('label' => __('Font - Size', 'ws-form'), 'kb_slug' => 'customize-appearance', 'value' => WS_Form_Common::option_get('skin_font_size')),
						'skin_font_size_large'		=>	array('label' => __('Font - Size (Large)', 'ws-form'), 'kb_slug' => 'customize-appearance', 'value' => WS_Form_Common::option_get('skin_font_size_large')),
						'skin_font_size_small'		=>	array('label' => __('Font - Size (Small)', 'ws-form'), 'kb_slug' => 'customize-appearance', 'value' => WS_Form_Common::option_get('skin_font_size_small')),
						'skin_font_weight'		=>	array('label' => __('Font - Weight', 'ws-form'), 'kb_slug' => 'customize-appearance', 'value' => WS_Form_Common::option_get('skin_font_weight')),
						'skin_line_height'		=>	array('label' => __('Line Height', 'ws-form'), 'kb_slug' => 'customize-appearance', 'value' => WS_Form_Common::option_get('skin_line_height')),

						// Border
						'skin_border_width'		=>	array('label' => __('Border - Width', 'ws-form'), 'kb_slug' => 'customize-appearance', 'value' => WS_Form_Common::option_get('skin_border_width')),
						'skin_border_style'		=>	array('label' => __('Border - Style', 'ws-form'), 'kb_slug' => 'customize-appearance', 'value' => WS_Form_Common::option_get('skin_border_style')),
						'skin_border_radius'		=>	array('label' => __('Border - Style', 'ws-form'), 'kb_slug' => 'customize-appearance', 'value' => WS_Form_Common::option_get('skin_border_radius'))
					)
				),
				// Field
				'field' 	=> array(

					'label'		=> __('Field', 'ws-form'),

					'variables'	=> array(

						'field'			=>	array(

							'label' => __('Field Value', 'ws-form'),

							'attributes' => array(

								array('id' => 'id', 'required' => true),
							),

							'description' => __('Use this variable to pull back the value of a field on your form. For example: <code>#field(123)</code> where \'123\' is the field ID shown in the form editor.', 'ws-form')
						),
					)
				),

				// Select option text
				'select' 	=> array(

					'label'		=> __('Selects', 'ws-form'),

					'variables'	=> array(

						'select_option_text'			=>	array(

							'label' => __('Select Option Text', 'ws-form'),

							'attributes' => array(

								array('id' => 'id', 'required' => true),
							),

							'description' => __('Use this variable to pull back the selected option text of a select field on your form. For example: <code>#select_option_text(123)</code> where \'123\' is the field ID shown in the form editor.', 'ws-form')
						),
					)
				),

				// Email
				'email' 	=> array(

					'label'		=> __('Email', 'ws-form'),

					'variables'	=> array(

						'email_subject'			=>	array('label' => __('Subject', 'ws-form'), 'limit' => __('in the Send Email action', 'ws-form'), 'kb_slug' => 'send-email'),
						'email_content_type'	=>	array('label' => __('Content type', 'ws-form'), 'limit' => __('in the Send Email action', 'ws-form'), 'kb_slug' => 'send-email'),
						'email_charset'			=>	array('label' => __('Character set', 'ws-form'), 'limit' => __('in the Send Email action', 'ws-form'), 'kb_slug' => 'send-email'),
						'email_submission'		=>	array(

							'label' => __('Submitted Fields', 'ws-form'),

							'attributes' => array(

								array('id' => 'tab_labels', 'required' => false, 'default' => WS_Form_Common::option_get('action_email_group_labels', 'auto'), 'valid' => array('true', 'false', 'auto')),
								array('id' => 'section_labels', 'required' => false, 'default' => WS_Form_Common::option_get('action_email_section_labels', 'auto'), 'valid' => array('true', 'false', 'auto')),
								array('id' => 'blank_fields', 'required' => false, 'default' => (WS_Form_Common::option_get('action_email_exclude_empty') ? 'false' : 'true'), 'valid' => array('true', 'false')),
								array('id' => 'static_fields', 'required' => false, 'default' => (WS_Form_Common::option_get('action_email_static_fields') ? 'true' : 'false'), 'valid' => array('true', 'false')),
							),

							'kb_slug' => 'send-email',

							'limit' => __('in the Send Email action', 'ws-form'),

							'description' => __('This variable outputs a list of the fields captured during a submission. You can either use: <code>#email_submission</code> or provide additional parameters to toggle tab labels, section labels, blank fields and static fields (such as text or HTML areas of your form). Specify \'true\' or \'false\' for each parameter, for example: <code>#email_submission(true, true, false, true)</code>', 'ws-form')
						),
						'email_ecommerce'		=>	array(

							'label' => __('E-Commerce Values', 'ws-form'),

							'kb_slug' => 'e-commerce',

							'limit' => __('in the Send Email action', 'ws-form'),

							'description' => __('This variable outputs a list of the e-commerce transaction details such as total, transaction ID and status fields.', 'ws-form')
						),
						'email_tracking'		=>	array('label' => __('Tracking data', 'ws-form'), 'limit' => __('in the Send Email action', 'ws-form'), 'kb_slug' => 'send-email'),
						'email_logo'			=>	array('label' => __('Logo', 'ws-form'), 'value' => $email_logo, 'limit' => __('in the Send Email action', 'ws-form'), 'kb_slug' => 'send-email'),
						'email_pixel'			=>	array('label' => __('Pixel'), 'value' => '<img src="' . WS_FORM_PLUGIN_DIR_URL . 'public/images/email/p.gif" width="100%" height="5" />', 'description' => __('Outputs a transparent gif. We use this to avoid Mac Mail going into dark mode when viewing emails.', 'ws-form'))
					)
				),

				// Query
				'query' 	=> array(

					'label'		=> __('Query Variables', 'ws-form'),

					'variables'	=> array(

						'query_var'		=>	array(

							'label' => __('Variable', 'ws-form'),

							'attributes' => array(

								array('id' => 'variable', 'required' => true)
							)
						)
					)
				),

				// Post
				'post' 	=> array(

					'label'		=> __('Post Variables', 'ws-form'),

					'variables'	=> array(

						'post_var'	=>	array(

							'label' => __('Variable', 'ws-form'),

							'attributes' => array(

								array('id' => 'variable', 'required' => true)
							)
						)
					)
				),

				// Random
				'random' 	=> array(

					'label'		=> __('Random Values', 'ws-form'),

					'variables'	=> array(

						'random_number'	=>	array(

							'label' => __('Random Number', 'ws-form'),

							'attributes' => array(

								array('id' => 'min', 'required' => false, 'default' => 0),
								array('id' => 'max', 'required' => false, 'default' => 100)
							),

							'description' => __('Outputs an integer between the specified minimum and maximum attributes. This function does not generate cryptographically secure values, and should not be used for cryptographic purposes.', 'ws-form'),

							'single_parse' => true
						),

						'random_string'	=>	array(

							'label' => __('Random String', 'ws-form'),

							'attributes' => array(

								array('id' => 'length', 'required' => false, 'default' => 32),
								array('id' => 'characters', 'required' => false, 'default' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
							),

							'description' => __('Outputs a string of random characters. Use the length attribute to control how long the string is and use the characters attribute to control which characters are randomly selected. This function does not generate cryptographically secure values, and should not be used for cryptographic purposes.', 'ws-form'),

							'single_parse' => true
						)
					)
				),

				// Character
				'character'	=> array(

					'label'		=> __('Character', 'ws-form'),

					'variables' => array(

						'character_count'	=>	array(

							'label'	=> __('Count', 'ws-form'),
							'description' => __('The total character count.', 'ws-form'),
							'limit'	=> __('in the Help setting for text based Fields', 'ws-form'),
							'kb_slug' => 'word-and-character-count'
						),

						'character_count_label'	=>	array(

							'label'	=> __('Count Label', 'ws-form'),
							'description' => __("Shows 'character' or 'characters' depending on the character count.", 'ws-form'),
							'limit'	=> __('in the Help setting for text based Fields', 'ws-form'),
							'kb_slug' => 'word-and-character-count'
						),

						'character_remaining'	=>	array(

							'label'	=> __('Count Remaining', 'ws-form'),
							'description' => __('If you set a maximum character length for a field, this will show the total remaining character count.', 'ws-form'),
							'limit'	=> __('in the Help setting for text based Fields', 'ws-form'),
							'kb_slug' => 'word-and-character-count'
						),

						'character_remaining_label'	=>	array(

							'label'	=> __('Count Remaining Label', 'ws-form'),
							'description' => __('If you set a maximum character length for a field, this will show the total remaining character count.', 'ws-form'),
							'limit'	=> __('in the Help setting for text based Fields', 'ws-form'),
							'kb_slug' => 'word-and-character-count'
						),

						'character_min'	=>	array(

							'label'	=> __('Minimum', 'ws-form'),
							'description' => __('Shows the minimum character length that you set for a field.'),
							'limit'	=> __('in the Help setting for text based Fields', 'ws-form'),
							'kb_slug' => 'word-and-character-count'
						),

						'character_min_label'	=>	array(

							'label'	=> __('Minimum Label', 'ws-form'),
							'description' => __("Shows 'character' or 'characters' depending on the minimum character length.", 'ws-form'),
							'limit'	=> __('in the Help setting for text based Fields', 'ws-form'),
							'kb_slug' => 'word-and-character-count'
						),

						'character_max'	=>	array(

							'label'	=> __('Maximum', 'ws-form'),
							'description' => __('Shows the maximum character length that you set for a field.'),
							'limit'	=> __('in the Help setting for text based Fields', 'ws-form'),
							'kb_slug' => 'word-and-character-count'
						),

						'character_max_label'	=>	array(

							'label'	=> __('Maximum Label', 'ws-form'),
							'description' => __("Shows 'character' or 'characters' depending on the maximum character length.", 'ws-form'),
							'limit'	=> __('in the Help setting for text based Fields', 'ws-form'),
							'kb_slug' => 'word-and-character-count'
						)
					)
				),

				// Word
				'word'	=> array(

					'label'		=> __('Word', 'ws-form'),

					'variables' => array(

						'word_count'	=>	array(

							'label'	=> __('Count', 'ws-form'),
							'description' => __('The total word count.', 'ws-form'),
							'limit'	=> __('in the Help setting for text based Fields', 'ws-form'),
							'kb_slug' => 'word-and-character-count'
						),

						'word_count_label'	=>	array(

							'label'	=> __('Count Label', 'ws-form'),
							'description' => __("Shows 'word' or 'words' depending on the word count.", 'ws-form'),
							'limit'	=> __('in the Help setting for text based Fields', 'ws-form'),
							'kb_slug' => 'word-and-character-count'
						),

						'word_remaining'	=>	array(

							'label'	=> __('Count Remaining', 'ws-form'),
							'description' => __('If you set a maximum word length for a field, this will show the total remaining word count.', 'ws-form'),
							'limit'	=> __('in the Help setting for text based Fields', 'ws-form'),
							'kb_slug' => 'word-and-character-count'
						),

						'word_remaining_label'	=>	array(

							'label'	=> __('Count Remaining Label', 'ws-form'),
							'description' => __('If you set a maximum word length for a field, this will show the total remaining word count.', 'ws-form'),
							'limit'	=> __('in the Help setting for text based Fields', 'ws-form'),
							'kb_slug' => 'word-and-character-count'
						),

						'word_min'	=>	array(

							'label'	=> __('Minimum', 'ws-form'),
							'description' => __('Shows the minimum word length that you set for a field.', 'ws-form'),
							'limit'	=> __('in the Help setting for text based Fields', 'ws-form'),
							'kb_slug' => 'word-and-character-count'
						),

						'word_min_label'	=>	array(

							'label'	=> __('Minimum Label', 'ws-form'),
							'description' => __("Shows 'word' or 'words' depending on the minimum word length.", 'ws-form'),
							'limit'	=> __('in the Help setting for text based Fields', 'ws-form'),
							'kb_slug' => 'word-and-character-count'
						),

						'word_max'	=>	array(

							'label'	=> __('Maximum', 'ws-form'),
							'description' => __('Shows the maximum word length that you set for a field.', 'ws-form'),
							'limit'	=> __('in the Help setting for text based Fields', 'ws-form'),
							'kb_slug' => 'word-and-character-count'
						),

						'word_max_label'	=>	array(

							'label'	=> __('Maximum Label', 'ws-form'),
							'description' => __("Shows 'word' or 'words' depending on the maximum word length.", 'ws-form'),
							'limit'	=> __('in the Help setting for text based Fields', 'ws-form'),
							'kb_slug' => 'word-and-character-count'
						)
					)
				)
			);

			// Post
			$post = WS_Form_Common::get_post_root();

			$parse_variables['post'] = array(

				'label'		=> __('Post', 'ws-form'),

				'variables'	=> array(

					'post_url'			=>	array('label' => __('URL', 'ws-form'), 'value' => !is_null($post) ? get_permalink($post->ID) : ''),
					'post_type'			=>	array('label' => __('Type', 'ws-form'), 'value' => !is_null($post) ? $post->post_type : ''),
					'post_title'		=>	array('label' => __('Title', 'ws-form'), 'value' => !is_null($post) ? $post->post_title : ''),
					'post_time'			=>	array('label' => __('Time', 'ws-form'), 'value' => !is_null($post) ? date(get_option('time_format'), strtotime($post->post_date)) : ''),
					'post_id'			=>	array('label' => __('ID', 'ws-form'), 'value' => !is_null($post) ? $post->ID : ''),
					'post_date'			=>	array('label' => __('Date', 'ws-form'), 'value' => !is_null($post) ? date(get_option('date_format'), strtotime($post->post_date)) : ''),

					// http://blog.stevenlevithan.com/archives/date-time-format
					'post_date_custom'	=>	array(

						'label' => __('Post Custom Date', 'ws-form'),

						'value' => !is_null($post) ? date('c', strtotime($post->post_date)) : '',

						'attributes' => array(

							array('id' => 'format', 'required' => false, 'default' => 'F j, Y, g:i a')
						),

						'kb_slug' => 'date-formats'
					),
					'post_meta'			=>	array(

						'label' => __('Meta Value', 'ws-form'),

						'attributes' => array(

							array('id' => 'key', 'required' => true)
						),

						'description' => __('Returns the post meta value for the key specified. Server side only.')
					)
				)
			);

			// Author
			$post_author_id = !is_null($post) ? $post->post_author : 0;
			$parse_variables['author'] = array(

				'label'		=> __('Author', 'ws-form'),

				'variables'	=> array(

					'author_id'				=>	array('label' => __('ID', 'ws-form'), 'value' => $post_author_id),
					'author_display_name'	=>	array('label' => __('Display Name', 'ws-form'), 'value' => get_the_author_meta('display_name', $post_author_id)),
					'author_first_name'		=>	array('label' => __('First Name', 'ws-form'), 'value' => get_the_author_meta('first_name', $post_author_id)),
					'author_last_name'		=>	array('label' => __('Last Name', 'ws-form'), 'value' => get_the_author_meta('last_name', $post_author_id)),
					'author_nickname'		=>	array('label' => __('Nickname', 'ws-form'), 'value' => get_the_author_meta('nickname', $post_author_id)),
					'author_email'			=>	array('label' => __('Email', 'ws-form'), 'value' => get_the_author_meta('user_email', $post_author_id)),
				)
			);

			// URL
			$parse_variables['url'] = array(

				'label'		=> __('URL', 'ws-form'),

				'variables'	=> array(

					'url_login'				=>	array('label' => __('Login', 'ws-form'), 'value' => wp_login_url()),
					'url_logout'			=>	array('label' => __('Logout', 'ws-form'), 'value' => wp_logout_url()),
					'url_lost_password'				=>	array('label' => __('Login', 'ws-form'), 'value' => wp_lostpassword_url()),
					'url_register'				=>	array('label' => __('Register', 'ws-form'), 'value' => wp_registration_url()),
				)
			);

			// ACF
			if(class_exists('acf')) { 

				$parse_variables['acf'] =  array(

					'label'		=> __('ACF', 'ws-form'),

					'variables'	=> array(

						'acf_repeater_field'	=>	array(

							'label' => __('Repeater Field', 'ws-form'),

							'attributes' => array(

								array('id' => 'parent_field', 'required' => true),
								array('id' => 'sub_field', 'required' => true),
							),

							'description' => __('Used to obtain an ACF repeater field. Server side only. You can separate parent_fields with commas to access deep variables.', 'ws-form')
						),
					)
				);
			}

			// User
			$current_user = WS_Form_Action::get_user();
			if($current_user === false) {

				if(function_exists('wp_get_current_user')) {

					$current_user = wp_get_current_user();
					$current_user_id = $current_user->ID;

				} else {

					$current_user_id = 0;
				}

			} else {

				$current_user_id = $current_user->ID;
			}

			$parse_variables['user'] = array(

				'label'		=> __('User', 'ws-form'),

				'variables'	=> array(

					'user_id' 			=>	array('label' => __('ID', 'ws-form'), 'value' => $current_user_id, 'limit' => __('if a user is currently signed in', 'ws-form')),
					'user_login' 		=>	array('label' => __('Login', 'ws-form'), 'value' => ($current_user_id > 0) ? $current_user->user_login : '', 'limit' => __('if a user is currently signed in', 'ws-form')),
					'user_nicename' 	=>	array('label' => __('Nice Name', 'ws-form'), 'value' => ($current_user_id > 0) ? $current_user->user_nicename : '', 'limit' => __('if a user is currently signed in', 'ws-form')),
					'user_email' 		=>	array('label' => __('Email', 'ws-form'), 'value' => ($current_user_id > 0) ? $current_user->user_email : '', 'limit' => __('if a user is currently signed in', 'ws-form')),
					'user_display_name' =>	array('label' => __('Display Name', 'ws-form'), 'value' => ($current_user_id > 0) ? $current_user->display_name : '', 'limit' => __('if a user is currently signed in', 'ws-form')),
					'user_url' 			=>	array('label' => __('URL', 'ws-form'), 'value' => ($current_user_id > 0) ? $current_user->user_url : '', 'limit' => __('if a user is currently signed in', 'ws-form')),
					'user_registered' 	=>	array('label' => __('Registration Date', 'ws-form'), 'value' => ($current_user_id > 0) ? $current_user->user_registered : '', 'limit' => __('if a user is currently signed in', 'ws-form')),
					'user_first_name'	=>	array('label' => __('First Name', 'ws-form'), 'value' => ($current_user_id > 0) ? get_user_meta($current_user_id, 'first_name', true) : '', 'limit' => __('if a user is currently signed in', 'ws-form')),
					'user_last_name'	=>	array('label' => __('Last Name', 'ws-form'), 'value' => ($current_user_id > 0) ? get_user_meta($current_user_id, 'last_name', true) : '', 'limit' => __('if a user is currently signed in', 'ws-form')),
					'user_bio'			=>	array('label' => __('Bio', 'ws-form'), 'value' => ($current_user_id > 0) ? get_user_meta($current_user_id, 'description', true) : '', 'limit' => __('if a user is currently signed in', 'ws-form')),
					'user_nickname' 	=>	array('label' => __('Nickname', 'ws-form'), 'value' => ($current_user_id > 0) ? get_user_meta($current_user_id, 'nickname', true) : '', 'limit' => __('if a user is currently signed in', 'ws-form')),
					'user_admin_color' 	=>	array('label' => __('Admin Color', 'ws-form'), 'value' => ($current_user_id > 0) ? get_user_meta($current_user_id, 'admin_color', true) : '', 'limit' => __('if a user is currently signed in', 'ws-form')),
					'user_lost_password_key' => array('label' => __('Lost Password Key', 'ws-form'), 'value' => ($current_user_id > 0) ? $current_user->lost_password_key : '', 'limit' => __('if a user is currently signed in', 'ws-form')),
					'user_lost_password_url' => array(

						'label'			=> __('Lost Password URL', 'ws-form'),
						'attributes'	=> array(

							array('id' => 'path', 'required' => false, 'default' => '')
						),
						'limit' => __('if a user is currently signed in', 'ws-form')
					),
					'user_meta'			=>	array(

						'label' => __('Meta Value', 'ws-form'),

						'attributes' => array(

							array('id' => 'key', 'required' => true)
						),

						'description' => __('Returns the user meta value for the key specified. Server side only.')
					)
				)
			);

			// Search
			$parse_variables['search'] = array(

				'label'		=> __('Search', 'ws-form'),

				'variables'	=> array(

					'search_query' => array('label' => __('Query', 'ws-form'), 'value' => get_search_query())
				)
			);

			// Apply filter
			$parse_variables = apply_filters('wsf_config_parse_variables', $parse_variables);

			// Public - Optimize
			if($public) {

				$parameters_exclude = array('label', 'description', 'limit', 'kb_slug');

				foreach($parse_variables as $variable_group => $variable_group_config) {

					foreach($variable_group_config['variables'] as $variable => $variable_config) {

						unset($parse_variables[$variable_group]['label']);

						foreach($parameters_exclude as $parameter_exclude) {

							if(isset($parse_variables[$variable_group]['variables'][$variable][$parameter_exclude])) {

								unset($parse_variables[$variable_group]['variables'][$variable][$parameter_exclude]);
							}
						}
					}
				}
			}

			// Cache
			self::$parse_variables[$public] = $parse_variables;

			return $parse_variables;
		}

		// Parse variable
		public static function get_parse_variable_help($form_id = 0, $public = true, $group = false) {

			// Check cache
			if(isset(self::$parse_variable_help[$public])) { return self::$parse_variable_help[$public]; }

			$parse_variable_help = array();

			// Get admin variables
			$parse_variables_config = self::get_parse_variables($public);

			// Get all parse variablers
			$parse_variables = [];
			foreach($parse_variables_config as $parse_variable_group_id => $parse_variable_group) {

				if(!isset($parse_variable_group['label'])) { continue; }

				if(($group !== false) && (strpos($group, $parse_variable_group_id) === false)) { continue; }

				$group_label = $parse_variable_group['label'];

				foreach($parse_variable_group['variables'] as $parse_variable_key => $parse_variables_single) {

					$parse_variables_single['group_label'] = $group_label;
					$parse_variables_single['key'] = $parse_variable_key;
					$parse_variables[] = $parse_variables_single;
				}
			}

			// Sort parse variables
			uasort($parse_variables, function ($parse_variable_1, $parse_variable_2) {

				if($parse_variable_1['group_label'] == $parse_variable_2['group_label']) {

					if($parse_variable_1['label'] == $parse_variable_2['label']) return 0;
					return $parse_variable_1['label'] < $parse_variable_2['label'] ? -1 : 1;
				}

				return $parse_variable_1['group_label'] < $parse_variable_2['group_label'] ? -1 : 1;
			});

			// Process variables
			foreach($parse_variables as $parse_variable) {

				if(!isset($parse_variable['label'])) { continue; }

				$parse_variable_key = $parse_variable['key'];
				$parse_variable_label = $parse_variable['group_label'] . ': ' . $parse_variable['label'];

				// Attributes
				if(isset($parse_variable['attributes'])) {

					if(
						($parse_variable_key == 'field') &&
						($form_id > 0)
					) {

						// Read form fields
						$ws_form_form = new WS_Form_Form();
						$ws_form_form->id = $form_id;
						$fields = $ws_form_form->db_get_fields(false);

						// Sort fields by group label then label
						uasort($fields, function ($field_1, $field_2) {

							if($field_1['label'] == $field_2['label']) return 0;
							return $field_1['label'] < $field_2['label'] ? -1 : 1;
						});

						foreach($fields as $field) {

							$parse_variable_help_single = array('text' => $parse_variable['group_label'] . ': ' . $field['label'] . ' (' . $field['id'] . ')', 'value' => '#' . $parse_variable_key . '(' . $field['id'] . ')', 'description' => isset($parse_variable['description']) ? $parse_variable['description'] : '');

							self::parse_variable_help_add($parse_variable_help, $parse_variable_help_single);
						}

					} else {

						// Regular functions
						$attributes_text = [];
						$attributes_value = [];
						foreach($parse_variable['attributes'] as $parse_variable_attribute) {

							$parse_variable_attribute_id = $parse_variable_attribute['id'];
							$parse_variable_attribute_required = isset($parse_variable_attribute['required']) ? $parse_variable_attribute['required'] : false;
							$parse_variable_attribute_default = isset($parse_variable_attribute['default']) ? $parse_variable_attribute['default'] : false;

							$attributes_text[] = $parse_variable_attribute_id . ($parse_variable_attribute_required ? '*' : '');

							$attributes_value[] = $parse_variable_attribute_id;
						}

						$value = $parse_variable_key . '(' . implode(', ', $attributes_value) . ')';
						$parse_variable_help_single = array('text' => $parse_variable_label, 'value' => '#' . $value, 'description' => isset($parse_variable['description']) ? $parse_variable['description'] : '');

						if(isset($parse_variable['kb_slug'])) { $parse_variable_help_single['kb_slug'] = $parse_variable['kb_slug']; }

						if(isset($parse_variable['limit'])) { $parse_variable_help_single['limit'] = $parse_variable['limit']; }

						self::parse_variable_help_add($parse_variable_help, $parse_variable_help_single);
					}

				} else {

					$value = $parse_variable_key;
					$parse_variable_help_single = array('text' => $parse_variable_label, 'value' => '#' . $value, 'description' => isset($parse_variable['description']) ? $parse_variable['description'] : '');

					if(isset($parse_variable['kb_slug'])) { $parse_variable_help_single['kb_slug'] = $parse_variable['kb_slug']; }

					if(isset($parse_variable['limit'])) { $parse_variable_help_single['limit'] = $parse_variable['limit']; }

					self::parse_variable_help_add($parse_variable_help, $parse_variable_help_single);
				}
			}

			// Apply filter
			$parse_variable_help = apply_filters('wsf_config_parse_variable_help', $parse_variable_help);

			// Cache
			self::$parse_variable_help[$public] = $parse_variable_help;

			return $parse_variable_help;
		}

		// Parse variables help add
		public static function parse_variable_help_add(&$parse_variable_help, $parse_variable_help_single) {

			$passthrough_attributes = array('description', 'limit', 'kb_slug');

			// Passthrough attributes
			foreach($passthrough_attributes as $passthrough_attribute) {

				if(isset($parse_variable[$passthrough_attribute])) { $parse_variable_help_single[$passthrough_attribute] = $parse_variable[$passthrough_attribute]; }

			}

			$parse_variable_help[] = $parse_variable_help_single;
		}

		// System report
		public static function get_system() {

			global $wpdb, $required_mysql_version;

			$system = array(

				// WS Form
				'ws_form' => array(

					'label'		=>	WS_FORM_NAME_PRESENTABLE,
					'variables'	=> array(

						'version'		=> array('label' => __('Version', 'ws-form'), 'value' => WS_FORM_VERSION),
						'edition'		=> array('label' => __('Edition', 'ws-form'), 'value' => WS_FORM_EDITION, 'type' => 'edition'),
						'framework'		=> array('label' => __('Framework', 'ws-form'), 'value' => WS_Form_Common::option_get('framework')),
					)
				),

				// WordPress
				'wordpress' => array(

					'label'		=>	'WordPress',
					'variables'	=> array(

						'version' 			=> array('label' => __('Version', 'ws-form'), 'value' => get_bloginfo('version'), 'valid' => (version_compare(get_bloginfo('version'), WS_FORM_MIN_VERSION_WORDPRESS) >= 0), 'min' => WS_FORM_MIN_VERSION_WORDPRESS),
						'multisite'			=> array('label' => __('Multisite Enabled', 'ws-form'), 'value' => is_multisite(), 'type' => 'boolean'),
						'home_url' 			=> array('label' => __('Home URL', 'ws-form'), 'value' => get_home_url(), 'type' => 'url'),
						'site_url' 			=> array('label' => __('Site URL', 'ws-form'), 'value' => get_site_url(), 'type' => 'url'),
						'plugins_active' 	=> array('label' => __('Plugins', 'ws-form'), 'value' => (array) get_option('active_plugins', array()), 'type' => 'plugins'),
						'memory_limit'		=> array('label' => __('Memory Limit', 'ws-form'), 'value' => (defined('WP_MEMORY_LIMIT') ? WP_MEMORY_LIMIT : 0)),
						'locale'			=> array('label' => __('Locale', 'ws-form'), 'value' => get_locale()),
						'max_upload_size'	=> array('label' => __('Max Upload Size', 'ws-form'), 'value' => wp_max_upload_size(), 'type' => 'size'),
					)
				),

				// Web Server
				'web_server' => array(

					'label'		=>	__('Web Server', 'ws-form'),
					'variables'	=> array(

						'name'				=> array('label' => __('Name', 'ws-form'), 'value' => WS_Form_Common::get_http_env('SERVER_SOFTWARE')),
						'ip'				=> array('label' => __('IP', 'ws-form'), 'value' => WS_Form_Common::get_http_env(array('SERVER_ADDR', 'LOCAL_ADDR'))),
						'post_max_size'	=> array('label' => __('Max Upload Size', 'ws-form'), 'value' => ini_get('post_max_size')),
						'max_input_vars'	=> array('label' => __('Max Input Variables', 'ws-form'), 'value' => ini_get('max_input_vars'), 'valid' => (ini_get('max_input_vars') >= WS_FORM_MIN_INPUT_VARS), 'min' => WS_FORM_MIN_INPUT_VARS),
						'max_execution_time'	=> array('label' => __('Max Execution Time', 'ws-form'), 'value' => ini_get('max_execution_time'), 'suffix' => __(' seconds', 'ws-form')),
					)
				),

				// SMTP
				'smtp' => array(

					'label'		=>	__('SMTP', 'ws-form'),
					'variables'	=> array(

						'smtp'				=> array('label' => __('SMTP Hostname', 'ws-form'), 'value' => ini_get('SMTP')),
						'smtp_port'			=> array('label' => __('SMTP Port', 'ws-form'), 'value' => ini_get('smtp_port')),
					)
				),

				// PHP
				'php' => array(

					'label'		=>	__('PHP', 'ws-form'),
					'variables'	=> array(

						'version'				=> array('label' => __('Version', 'ws-form'), 'value' => phpversion(), 'valid' => (version_compare(phpversion(), WS_FORM_MIN_VERSION_PHP) >= 0), 'min' => WS_FORM_MIN_VERSION_PHP),
						'curl'					=> array('label' => __('CURL Installed', 'ws-form'), 'value' => (function_exists('curl_init') && function_exists('curl_setopt')), 'type' => 'boolean', 'valid' => true),
						'suhosin'				=> array('label' => __('SUHOSIN Extension Loaded', 'ws-form'), 'value' => extension_loaded('suhosin'), 'type' => 'boolean'),
						'date_default_timezone'	=> array('label' => __('Default Timezone', 'ws-form'), 'value' => date_default_timezone_get()),
					)
				),

				// MySQL
				'mysql' => array(

					'label'		=>	__('MySQL', 'ws-form'),
					'variables'	=> array(

						'version'	=> array('label' => __('Version', 'ws-form'), 'value' => $wpdb->db_version(), 'valid' => version_compare($wpdb->db_version(), $required_mysql_version, '>'), 'min' => $required_mysql_version)
					)
				)
			);


			// Apply filter
			$system = apply_filters('wsf_config_system', $system);

			return $system;
		}

		// Javascript
		public static function get_external() {

			// CDN or local source?
			$jquery_source = WS_Form_Common::option_get('jquery_source', 'cdn');

			$external = array(

				// Input mask bundle - v5.0.3
				'inputmask_js' => (($jquery_source == 'local') ? 

					WS_FORM_PLUGIN_DIR_URL . 'public/js/external/jquery.inputmask.min.js?ver=5.0.3' :
					'https://cdn.jsdelivr.net/gh/RobinHerbots/jquery.inputmask@5.0.3/dist/jquery.inputmask.min.js'
				)
			);

			// Apply filter
			$external = apply_filters('wsf_config_external', $external);

			return $external;
		}

	}