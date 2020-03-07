<?php

	class WS_Form_Public {

		// The ID of this plugin.
		private $plugin_name;

		// The version of this plugin.
		private $version;

		// Customizer
		private $customizer;

		// Form index (Incremented for each rendered with a short code)
		public $form_instance = 1;

		// Error index
		public $error_index = 0;

		// Debug
		public $debug = false;

		// JSON
		public $wsf_form_json = array();

		// Footer JS
		public $footer_js = '';

		// Enqueuing
		public $enqueue_wp_editor = false;
		public $enqueue_wp_html_editor = false;
		public $enqueue_input_mask = false;
		public $enqueue_sortable = false;
		public $enqueue_signature = false;
		public $enqueue_datetime_picker = false;
		public $enqueue_color_picker = false;
		public $enqueue_password_strength = false;

		// Enqueued
		public $enqueued_wp_editor = false;
		public $enqueued_wp_html_editor = false;
		public $enqueued_input_mask = false;
		public $enqueued_sortable = false;
		public $enqueued_signature = false;
		public $enqueued_datetime_picker = false;
		public $enqueued_color_picker = false;
		public $enqueued_password_strength = false;

		// Config filtering
		public $field_types = array();

		// Initialize the class and set its properties.
		public function __construct($plugin_name, $version) {

			$this->plugin_name = $plugin_name;
			$this->version = $version;
			$this->customizer = (WS_Form_Common::get_query_var('customize_theme') !== '');
		}

		public function init() {

			// Preview engine
			$plugin_preview = new WS_Form_Preview();
		}

		public function wp() {

			// Get post
			global $post;
			$GLOBALS['ws_form_post_root'] = isset($post) ? $post : null;
		}

		// Register the stylesheets for the public-facing side of the site.
		public function enqueue_styles() {


			// CSS - Layout
			if((WS_Form_Common::option_get('css_layout', true)) && (WS_Form_Common::option_get('framework', 'ws-form') == 'ws-form')) {

				wp_enqueue_style($this->plugin_name . '-css-layout', WS_Form_Common::get_api_path('helper/ws_form_css'), array(), $this->version, 'all');
			}


			// CSS - Skin
			if((WS_Form_Common::option_get('css_skin', true)) && (WS_Form_Common::option_get('framework', 'ws-form') == 'ws-form')) {

				if($this->customizer) {

					add_action('wp_head', array($this, 'public_css_inline'));

				} else {

					wp_enqueue_style($this->plugin_name . '-css-skin', WS_Form_Common::get_api_path('helper/ws_form_css_skin'), array(), $this->version, 'all');
				}
			}
		}

		// Public CSS - Render inline (For customizer only)
		public function public_css_inline() {

			echo '<style>';

			// Output CSS
			$ws_form_css = new WS_Form_CSS();
			$ws_form_css->render_skin();

			echo '</style>';
		}

		// Register the JavaScript for the public-facing side of the site.
		public function enqueue_scripts() {

			// Enqueue in footer?
			$jquery_footer = (WS_Form_Common::option_get('jquery_footer', '') == 'on');

			// Enqueued scripts settings
			$ws_form_settings = self::localization_object($this->debug);

			// Form class
			wp_register_script($this->plugin_name . '-form-common', plugin_dir_url(__DIR__) . 'shared/js/ws-form.js', array('jquery'), $this->version, $jquery_footer);
			wp_localize_script($this->plugin_name . '-form-common', 'ws_form_settings', $ws_form_settings);
			wp_enqueue_script($this->plugin_name . '-form-common');

			// Form class - Public
			wp_register_script($this->plugin_name . '-form-public', plugin_dir_url(__DIR__) . 'public/js/ws-form-public.js', array($this->plugin_name . '-form-common'), $this->version, $jquery_footer);
			wp_enqueue_script($this->plugin_name . '-form-public');

		}

		/* Shortcode: ws_form */
		public function shortcode_ws_form($atts) {

			// Read form ID
			if(isset($atts['id'])) { $form_id = absint($atts['id']); } else { return ''; }
			$element = isset($atts['element']) ? $atts['element'] : 'form';

			// Published?
			$published = isset($atts['published']) ? ($atts['published'] == 'true') : true;

			// Preview?
			$preview = isset($atts['preview']) ? ($atts['preview'] == 'true') : false;

			// Query string overrides
			if(WS_Form_Common::get_query_var('wsf_published') === 'false') { $published = false; }

			// Check for preview mode
			if($preview) {

				// Reset form instance (This is required to ensure wp_head calls resulting in do_shortocde('ws-form') don't stack up on each other)
				if(isset($this->wsf_form_json[$form_id])) { unset($this->wsf_form_json[$form_id]); }
				$this->form_instance = 1;
			}

			if($form_id > 0) {

				// Embed form data (Avoids an API call)
				$ws_form_form = New WS_Form_Form();
				$ws_form_form->id = $form_id;

				try {

					if($published) {

						$form_array = $ws_form_form->db_read_published();

					} else {

						$form_array = $ws_form_form->db_read(true, true);
					}

				} catch(Exception $e) { return $e->getMessage(); }

				// Filter
				$form_array = apply_filters('wsf_pre_render_' . $form_id, $form_array, $preview);
				$form_array = apply_filters('wsf_pre_render', $form_array, $preview);

				if($form_array !== false) {

					// Set up footer enqueues
					self::wp_footer_enqueues_init($form_array);
				}

				// Get form HTML
				$return_value = self::form_html($this->form_instance++, $form_id, $form_array, $element, $published, $preview);

				return $return_value;

			} else {

				// Error
				return __('Invalid form ID', 'ws-form');
			}
		}

		// Footer scripts
		public function wp_footer() {

			if(count($this->wsf_form_json) == 0) { return; };

			if(count($this->field_types) > 0) { $this->field_types = array_unique($this->field_types); }

			echo "\n<script id=\"wsf-wp-footer\">\n\n";

			// Embed config data (Avoids an API call)
			$json_config = json_encode(WS_Form_Config::get_config(false, $this->field_types));
			echo sprintf("\tvar wsf_form_json_config = %s;\n", $json_config);

			// Init form data
			echo "\tvar wsf_form_json = [];\n";
			echo "\tvar wsf_form_json_populate = [];\n";

			// Footer JS
			echo $this->footer_js;

			echo "\n</script>\n\n";
		}

		// Footer scripts - Initialize
		public function wp_footer_enqueues_init($form_array) {
 
			// Field types
			$field_types = WS_Form_Config::get_field_types_flat();

 			// Check to see if modal pop-up software installed, if so we should just enqueue everything

 			// Get form fields
			$fields = WS_Form_Common::get_fields_from_form(json_decode(json_encode($form_array)));

			// Process fields
			foreach($fields as $field) {

				if(!isset($field->type)) { continue; }

				// Get field type
				$field_type = $field->type;

				// Add field type to array (This is used later on to filter the field configs rendered on the page)
				$this->field_types[] = $field_type;

				// Check to see if an input_mask is set
				if(!$this->enqueue_input_mask) {

					$input_mask = WS_Form_Common::get_object_meta_value($field, 'input_mask', '');
					if($input_mask !== '') { $this->enqueue_input_mask = true; }

				}

				// Check by field type
				switch($field_type) {

					// Check to see if a textarea field is using wp_editor or wp_html_editor
					case 'textarea' :

						$input_type_textarea = WS_Form_Common::get_object_meta_value($field, 'input_type_textarea', '');
						if($input_type_textarea == 'tinymce') { $this->enqueue_wp_editor = true; }
						if($input_type_textarea == 'html') { $this->enqueue_wp_html_editor = true; }

						break;
				}
			}

			// Enqueue in footer?
			$jquery_footer = (WS_Form_Common::option_get('jquery_footer', '') == 'on');

			// External scripts
			$external = WS_Form_Config::get_external();

			// Input Mask
			if(!$this->enqueued_input_mask && apply_filters('wsf_enqueue_input_mask', $this->enqueue_input_mask)) {

				// External - Input Mask Bundle
				wp_enqueue_script($this->plugin_name . '-external-inputmask', $external['inputmask_js'], array('jquery'), null, $jquery_footer);
				$this->enqueued_input_mask = true;
			}
			// If a textarea exists in a form that requires wp_editor or wp_code_editor, enqueue the scripts
			global $wp_version;

			// WP Editor
			if(
				!$this->enqueued_wp_editor && 
				apply_filters('wsf_enqueue_wp_editor', $this->enqueue_wp_editor) &&
				version_compare($wp_version, '4.8', '>=') &&
				user_can_richedit()
			) {

				wp_enqueue_editor();

				$this->enqueued_wp_editor = true;
			}

			// WP HTML Editor
			if(
				!$this->enqueued_wp_html_editor && 
				apply_filters('wsf_enqueue_wp_html_editor', $this->enqueue_wp_html_editor) &&
				version_compare($wp_version, '4.9', '>=') &&
				(!is_user_logged_in() || (wp_get_current_user()->syntax_highlighting))
			) {

				wp_enqueue_code_editor(array('type' => 'text/html'));

				$this->enqueued_wp_html_editor = true;
			}
		}
		// Form - HTML
		public function form_html($form_instance, $form_id, $form_array, $element = 'form', $published = true, $preview = false) {

			if($form_array === false) { return __('Unpublished form', 'ws-form'); }
			if(!is_array($form_array)) { return __('Invalid form data', 'ws-form'); }

			// Do not render if draft or trash
			switch($form_array['status']) {

				case 'draft' :
				case 'trash' :

					if($published) { return ''; };
			}

			// Init framework config
			$framework_id = WS_Form_Common::option_get('framework', WS_FORM_DEFAULT_FRAMEWORK);
			$frameworks = WS_Form_Config::get_frameworks();
			$framework = $frameworks['types'][$framework_id];

			// Check for form attributes
			$form_attributes = '';

			if(isset($framework['form']['public']['attributes'])) {

				$form_attribute_array = $framework['form']['public']['attributes'];

				foreach($form_attribute_array as $key => $value) {

					$form_attributes .= ' ' . $key . (($value != '') ? '="' . $value . '"' : '');
				}
			}

			// Preview attribute
			if(!$published) { $form_attributes .= ' data-preview'; }

			// CSS - Framework
			if((isset($framework['css_file'])) && ($framework['css_file'] != '')) {

				$css_file_path = plugin_dir_url(__FILE__) . 'css/framework/' . $framework['css_file'];
				wp_enqueue_style($this->plugin_name . '-css-framework', $css_file_path, array(), $this->version, 'all');
			}

			// Form action
			$form_action = WS_Form_Common::get_api_path() . 'submit';

			// Check for custom form action
			$form_action_custom = trim(WS_Form_Common::get_object_meta_value((object) $form_array, 'form_action', ''));
			if($form_action_custom != '') { $form_action = $form_action_custom; }

			// Filter - Form action
			$form_action = apply_filters('wsf_shortcode_form_action', $form_action);

			// Form method
			$form_method = 'POST';
			$form_method = apply_filters('wsf_shortcode_form_method', $form_method);

			// Form wrapper
			switch($element) {

				case 'form' :

					$return_value = sprintf('<form action="%1$s" class="wsf-form wsf-form-canvas" id="ws-form-%2$u" data-id="%3$u" data-instance-id="%2$u" method="%4$s"%5$s></form>', $form_action, $form_instance, $form_id, $form_method, $form_attributes);
					break;

				default :

					$return_value = sprintf('<%4$s class="wsf-form wsf-form-canvas" id="ws-form-%1$u" data-id="%2$u" data-instance-id="%1$u"%3$s></%4$s>', $form_instance, $form_id, $form_attributes, $element);
					break;
			}

			// Shortcode filter
			$return_value = apply_filters('wsf_shortcode', $return_value);

			// Build JSON
			$form_json = json_encode($form_array);

			// Form data (Only render once per form ID)
			if(!isset($this->wsf_form_json[$form_id])) {

				// Form JSON
				$this->footer_js .= sprintf("\twindow.wsf_form_json[%u] = %s;", $form_id, $form_json) . "\n";

				// Form JSON populate
				$populate_array = self::get_populate_array($form_json);
				$populate_array = apply_filters('wsf_populate', $populate_array);
				if(($populate_array !== false) && count($populate_array) > 0) {

					$this->footer_js .= sprintf("\twindow.wsf_form_json_populate[%u] = %s;", $form_id, json_encode($populate_array)) . "\n";
				}

				$this->wsf_form_json[$form_id] = true;
			}

			return $return_value;
		}
		public function localization_object($debug = false) {

			global $post;

			// Stats
			$ws_form_form_stat = New WS_Form_Form_Stat();

			// Localization array
			$return_array = array(

				// URL
				'url'					=>	WS_Form_Common::get_api_path(),

				// Admin framework (Fallover)
				'framework_admin'		=> 'ws-form',

				// Should framework CSS be rendered? (WS Form framework only)
				'css_framework'			=> WS_Form_Common::option_get('css_framework', true),

				// Is design enabled?
				'design'				=> (WS_Form_Common::option_get('css_skin', true)) && (WS_Form_Common::option_get('framework', 'ws-form') == 'ws-form'),

				// Max upload size
				'max_upload_size'		=> absint(WS_Form_Common::option_get('max_upload_size', 0)),

				// Field prefix
				'field_prefix'			=> WS_FORM_FIELD_PREFIX,

				// Use X-HTTP-Method-Override?
				'ajax_http_method_override'	=> WS_Form_Common::option_get('ajax_http_method_override', true),

				// Date / time format
				'date_format'			=> get_option('date_format'),
				'time_format'			=> get_option('time_format'),

				// Locale
				'locale'				=> get_locale(),

				// Stats
				'stat'					=> $ws_form_form_stat->form_stat_check(),

				// Skin - Grid gutter
				'skin_grid_gutter'		=> WS_Form_Common::option_get('skin_grid_gutter', true),

			);
			// Pass through post ID
			if(isset($post) && ($post->ID > 0)) {

				$return_array['post_id'] = $post->ID;
			}

			// Nonce
			if(WS_Form_Common::can_user('manage_options_wsform') || WS_Form_Common::option_get('ajax_nonce', false)) {

				$return_array['nonce'] = wp_create_nonce('wp_rest');
			}

			return $return_array;
		}

		// Populate from action
		public function get_populate_array($form_json) {

			// Get populate data
			$form_object = json_decode($form_json);

			// Check form populate is enabled
			$form_populate_enabled = WS_Form_Common::get_object_meta_value($form_object, 'form_populate_enabled', '');
			if(!$form_populate_enabled) { return false; }

			// Read form populate data - Action ID
			$form_populate_action_id = WS_Form_Common::get_object_meta_value($form_object, 'form_populate_action_id', '');
			if($form_populate_action_id == '') { return false; }
			if(!isset(WS_Form_Action::$actions[$form_populate_action_id])) { return false; }

			// Get action
			$action = WS_Form_Action::$actions[$form_populate_action_id];

			// Check get method exists
			if(!method_exists($action, 'get')) { return false; }

			// Read form populate data - List ID
			$action_get_require_list_id = isset($action->get_require_list_id) ? $action->get_require_list_id : true;
			if($action_get_require_list_id) {

				$form_populate_list_id = WS_Form_Common::get_object_meta_value($form_object, 'form_populate_list_id', '');
				if($form_populate_list_id == '') { return false; }
			}

			// Read form populate data - Field mapping
			$form_populate_field_mapping = WS_Form_Common::get_object_meta_value($form_object, 'form_populate_field_mapping', array());

			if(method_exists($action, 'get_tags')) {

				// Read form populate data - Tag mapping
				$form_populate_tag_mapping = WS_Form_Common::get_object_meta_value($form_object, 'form_populate_tag_mapping', array());
			}

			// Get user data
			$current_user = wp_get_current_user();

			// Set list ID
			if($action_get_require_list_id) {

				$action->list_id = $form_populate_list_id;
			}

			// Try to get action data
			try {

				$get_array = $action->get($form_object, $current_user);

			} catch(Exception $e) { return false; }

			if($get_array === false) { return false; }

			$return_array = array();

			// Process field mapping data
			$field_mapping_lookup = array();
			if(is_array($form_populate_field_mapping)) {

				foreach($form_populate_field_mapping as $field_mapping) {

					$action_field = $field_mapping->form_populate_list_fields;
					$ws_form_field = $field_mapping->ws_form_field;

					if(!isset($field_mapping_lookup[$action_field])) {

						$field_mapping_lookup[$action_field] = $ws_form_field;
					}
				}
			}

			// Map fields
			if(isset($get_array['fields'])) {

				foreach($get_array['fields'] as $id => $value) {

					if(is_numeric($id)) {

						$return_array[WS_FORM_FIELD_PREFIX . $id] = $value;

					} else {

						if(isset($field_mapping_lookup[$id])) {

							$return_array[WS_FORM_FIELD_PREFIX . $field_mapping_lookup[$id]] = $value;
						}
					}
				}
			}

			if(method_exists($action, 'get_tags')) {

				// Process tag mapping data
				$tag_mapping_array = array();
				foreach($get_array['tags'] as $id => $value) {

					if($value) {

						$tag_mapping_array[] = $id;
					}
				}

				// Map tags
				foreach($form_populate_tag_mapping as $tag_mapping) {

					$ws_form_field = $tag_mapping->ws_form_field;

					$return_array[WS_FORM_FIELD_PREFIX . $ws_form_field] = $tag_mapping_array;
				}
			}

			return array('action_label' => $action->label, 'data' => $return_array);
		}
	}
