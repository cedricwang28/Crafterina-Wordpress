<?php

	class WS_Form_Wizard extends WS_Form_Core {

		public $id = false;
		public $label = '';
		public $pro_required = false;
		public $svg = '';
		public $form = '';

		public $action_id = false;

		private $config;
		private $config_file;

		public $color_default;
		public $color_default_inverted;
		public $color_default_lighter;
		public $color_primary;
		public $color_success;
		public $color_danger;
		public $border_radius;
		public $grid_gutter;

		public function __construct() {

			global $wpdb;

			$this->config_files = array(WS_FORM_PLUGIN_DIR_PATH . 'includes/wizard/config.json');

			// Read skin
			$this->color_default = WS_Form_Common::option_get('skin_color_default');
			$this->color_default_inverted = WS_Form_Common::option_get('skin_color_default_inverted');
			$this->color_default_lighter = WS_Form_Common::option_get('skin_color_default_lighter');
			$this->color_primary = WS_Form_Common::option_get('skin_color_primary');
			$this->color_success = WS_Form_Common::option_get('skin_color_success');
			$this->color_danger = WS_Form_Common::option_get('skin_color_danger');
			$this->border_radius = floatval(WS_Form_Common::option_get('skin_border_radius'));
			if($this->border_radius > 0) { $this->border_radius = ($this->border_radius / 4); }
			$this->grid_gutter = floatval(WS_Form_Common::option_get('skin_grid_gutter'));
			if($this->grid_gutter > 0) { $this->grid_gutter = ($this->grid_gutter / 4); }
		}

		// Read wizard
		public function read() {

			self::db_check_id();

			$config_full = self::read_config();

			foreach($config_full as $wizard_category) {

				$file_path = $wizard_category->file_path;

				foreach($wizard_category->wizards as $wizard) {

					if($wizard->id == $this->id) {

						// Set class variables
						$this->id = $wizard->id;
						$this->label = $wizard->label;
						$this->pro_required = $wizard->pro_required;
						$this->svg = $wizard->svg;

						// Read file JSON
						$file_json = $wizard->file_json;
						$file = $file_path . $file_json;
						if(!file_exists($file)) { self::db_throw_error(sprintf(__('Unable to read wizard JSON file: %s', 'ws-form'), $file)); }
						$form = file_get_contents($file);
						$this->form = json_decode($form, true);

						return $this;
					}
				}
			}

			self::db_throw_error(__('Wizard not found', 'ws-form'));
		}

		// Read config
		public function read_config($config_files = false) {

			// Run filter (to allow appending of additional config files)
			$this->config_files = ($config_files === false) ? apply_filters('wsf_wizard_config_files', $this->config_files) : $config_files;

			$config = array();

			foreach($this->config_files as $config_file) {

				// Read config file
				$config_file_string = file_get_contents($config_file);
				if($config_file_string === false) { self::db_throw_error(sprintf(__('Unable to read wizard config file: %s', $config_file), 'ws-form')); }

				// JSON decode
				$config_object = json_decode($config_file_string);
				if(is_null($config_object)) { self::db_throw_error(sprintf(__('Unable to JSON decode wizard config file: %s', $config_file), 'ws-form')); }

				foreach($config_object->wizard_categories as $wizard_category_key => $wizard_category) {

					$file_path = $config_object->wizard_categories[$wizard_category_key]->file_path;
					$file_path = sprintf('%s/%s', dirname($config_file), $file_path);
					$config_object->wizard_categories[$wizard_category_key]->file_path = $file_path;

					foreach($wizard_category->wizards as $wizard_key => $wizard) {

						$file_svg = isset($wizard->file_svg) ? $wizard->file_svg : '';
						$wizard_svg = '';
						if(!empty($file_svg)) {

							$file = $file_path . $file_svg;
							if(!file_exists($file)) { self::db_throw_error(sprintf(__('Unable to read wizard SVG file: %s', 'ws-form'), $file)); }
							$wizard_svg = file_get_contents($file);
						}
						$config_object->wizard_categories[$wizard_category_key]->wizards[$wizard_key]->svg = $wizard_svg;
					}
				}

				$config = array_merge($config, $config_object->wizard_categories);
			}

			return $config;
		}

		// Build SVG from form
		public function svg($form_array = false) {

			if($form_array !== false) {

				$this->form = $form_array;
				$label = $this->form['label'];

			} else {

				self::db_check_id();
				self::read();
				$label = false;
			}

			$fields = self::form_fields($this->form);

			// Build SVG
			$svg = sprintf(
				'<svg class="wsf-responsive" viewBox="0 0 140 180"><rect width="100%%" height="100%%" fill="%s"/><text fill="%s" class="wsf-wizard-title"><tspan x="5" y="16">%s</tspan></text>',
				$this->color_default_inverted,
				$this->color_default,
				(($label !== false) ? $label : '#label')
			);

			$col_index = 0;
			$col_index_max = 12;
			$col_index_field = 0;
			$col_width = 10.8333;
			$gutter_width = $this->grid_gutter;

			$text_height = 8;
			$text_y_offset = 5;
			$field_height = 8;
			$field_adjust_x = -0.17;
			$row_spacing = $this->grid_gutter;

			$xpos_origin = 0;
			$ypos_origin = 27;

			$xpos_offset = ($col_width / 2);
			$ypos_offset = 0;

			$input_found = false;

			$field_type_buttons = apply_filters('wsf_wizard_svg_buttons', array(

				'submit' => array('fill' => $this->color_primary, 'color' => $this->color_default_inverted),
				'save' => array('fill' => $this->color_success, 'color' => $this->color_default_inverted),
				'clear' => array('fill' => $this->color_default_lighter, 'color' => $this->color_default),
				'reset' => array('fill' => $this->color_default_lighter, 'color' => $this->color_default),
				'tab_previous' => array('fill' => $this->color_default_lighter, 'color' => $this->color_default),
				'tab_next' => array('fill' => $this->color_default_lighter, 'color' => $this->color_default),
				'button' => array('fill' => $this->color_default_lighter, 'color' => $this->color_default),
				'section_add' => array('fill' => $this->color_default_lighter, 'color' => $this->color_default),
				'section_delete' => array('fill' => $this->color_danger, 'color' => $this->color_default_inverted),
				'section_up' => array('fill' => $this->color_default_lighter, 'color' => $this->color_default),
				'section_down' => array('fill' => $this->color_default_lighter, 'color' => $this->color_default)

			));

			$field_type_price_span = apply_filters('wsf_wizard_svg_price_span', array());

			foreach($fields as $field) {

				// Field
				$field_size = ($field['size'] > 0) ? $field['size'] : 12;
				$field_offset = ($field['offset'] > 0) ? $field['offset'] : 0;

				if(isset($field_type_buttons[$field['type']])) {

					$row_height = $field_height + $row_spacing;
					$field_ypos = ($ypos_origin + $ypos_offset);
					$button_fill = $field_type_buttons[$field['type']]['fill'];
					$text_color = $field_type_buttons[$field['type']]['color'];

				} elseif(isset($field_type_price_span[$field['type']])) {

					$input_found = true;
					$row_height = $field_height + $row_spacing;
					$field_ypos = $ypos_origin + $ypos_offset;

				} else {

					switch($field['type']) {

						case 'file' :
						case 'divider' :

							$row_height = $field_height + $row_spacing;
							$field_ypos = $ypos_origin + $ypos_offset;
							break;

						case 'textarea' :

							$input_found = true;
							$row_height = $text_height + ($field_height * 2) + $row_spacing;
							$field_ypos = $ypos_origin + $ypos_offset + $text_height;
							break;

						case 'texteditor' :
						case 'html' :

							$input_found = true;
							$row_height = ($field_height * 2) + $row_spacing;
							$field_ypos = $ypos_origin + $ypos_offset;
							break;

						default :

							$input_found = true;
							$row_height = $text_height + $field_height + $row_spacing;
							$field_ypos = $ypos_origin + $ypos_offset + $text_height;
					}
				}

				// Number of columns
				$field_cols = ($col_index_max / $field_size);
				$field_cols_offset = ($field_offset > 0) ? ($col_index_max / $field_offset) : 0;

				// Field width
				$field_width = ($field_size * $col_width) - (($field_cols > 1) ? ((1 - (1 / $field_cols)) * $gutter_width) : 0);

				// Field offset width
				$field_width_offset = ($field_cols_offset > 0) ? ($field_offset * $col_width) - (($field_cols_offset > 1) ? ((1 - (1 / $field_cols_offset)) * $gutter_width) - $gutter_width : 0) : 0;

				// Label
				$text_xpos = ($xpos_origin + $xpos_offset) + $field_width_offset;
				$text_ypos = ($ypos_origin + $ypos_offset);

				// Field
				$field_xpos = ($xpos_origin + $xpos_offset + $field_adjust_x) + $field_width_offset;

				if(isset($field_type_buttons[$field['type']])) {

					$button_text_xpos = ($field_width / 2);
					$alignment_y = ($input_found) ? $text_height : 0;

					$svg .= '<rect x="' . $field_xpos . '" y="' . ($field_ypos + $alignment_y) . '" class="wsf-wizard-button" fill="' . $button_fill . '" stroke="' . $button_fill . '" rx="' . $this->border_radius . '" width="' . $field_width . '" height="' . $field_height . '"/>';
					$svg .= '<text transform="matrix(1 0 0 1 ' . ($text_xpos + $button_text_xpos) . ' ' . ($field_ypos + $text_y_offset + 1 + $alignment_y) . ')" class="wsf-wizard-label" fill="' . $text_color . '" text-anchor="middle">' . $field['label'] . '</text>';

				} elseif (isset($field_type_price_span[$field['type']])) {

					$svg .= '<rect x="' . $field_xpos . '" y="' . $field_ypos . '" fill="' . $this->color_default_inverted . '" stroke="' . $this->color_default_lighter . '" stroke-dasharray="2 1" rx="' . $this->border_radius . '" width="' . $field_width . '" height="' . $field_height . '"/>';
					$svg .= '<text fill="' . $this->color_default . '" transform="matrix(1 0 0 1 ' . ($text_xpos + 1) . ' ' . ($field_ypos + $text_y_offset + 1) . ')" class="wsf-wizard-label">' . $field['label'] . '</text>';

				} else {

					switch($field['type']) {

						case 'progress' :

							$svg .= '<text fill="' . $this->color_default . '" transform="matrix(1 0 0 1 ' . $text_xpos . ' ' . ($text_ypos + $text_y_offset) . ')" class="wsf-wizard-label">' . $field['label'] . '</text>';
							$svg .= '<rect x="' . $field_xpos . '" y="' . $field_ypos . '" fill="' . $this->color_default_lighter . '" stroke="' . $this->color_default_lighter . '" rx="' . $this->border_radius . '" width="' . $field_width . '" height="' . ($field_height / 2) . '"/>';
							$svg .= '<rect x="' . $field_xpos . '" y="' . $field_ypos . '" fill="' . $this->color_primary . '" stroke="' . $this->color_primary . '" rx="' . $this->border_radius . '" width="' . ($field_width / 3) . '" height="' . ($field_height / 2) . '"/>';
							break;

						case 'range' :
						case 'price_range' :

							$range_thumb_xpos = ($field_width / 2);

							$svg .= '<text fill="' . $this->color_default . '" transform="matrix(1 0 0 1 ' . $text_xpos . ' ' . ($text_ypos + $text_y_offset) . ')" class="wsf-wizard-label">' . $field['label'] . '</text>';
							$svg .= '<rect x="' . $field_xpos . '" y="' . ($field_ypos + ($field_height / 2 - 1)) . '" fill="' . $this->color_default_inverted . '" stroke="' . $this->color_default_lighter . '" rx="' . $this->border_radius . '" width="' . $field_width . '" height="' . ($field_height / 4) . '"/>';
							$svg .= '<circle r="' . ($field_height / 2) . '" transform="matrix(1 0 0 1 ' . ($text_xpos + $range_thumb_xpos) . ' ' . ($field_ypos + ($text_y_offset - 1)) . ')" fill="' . $this->color_primary . '"/>
							';

							break;

						case 'textarea' :

							$svg .= '<text fill="' . $this->color_default . '" transform="matrix(1 0 0 1 ' . $text_xpos . ' ' . ($text_ypos + $text_y_offset) . ')" class="wsf-wizard-label">' . $field['label'] . '</text>';
							$svg .= '<rect x="' . $field_xpos . '" y="' . $field_ypos . '" fill="' . $this->color_default_inverted . '" stroke="' . $this->color_default_lighter . '" rx="' . $this->border_radius . '" width="' . $field_width . '" height="' . ($field_height * 2) . '"/>';
							break;

						case 'texteditor' :
						case 'html' :

							$svg .= '<rect x="' . $field_xpos . '" y="' . $field_ypos . '" fill="' . $this->color_default_inverted . '" stroke="' . $this->color_default_lighter . '" stroke-dasharray="2 1" rx="' . $this->border_radius . '" width="' . $field_width . '" height="' . ($field_height * 2) . '"/>';
							$svg .= '<text fill="' . $this->color_default . '" transform="matrix(1 0 0 1 ' . ($text_xpos + 1) . ' ' . ($field_ypos + $text_y_offset + 1) . ')" class="wsf-wizard-label">' . $field['label'] . '</text>';
							break;

						case 'divider' :

							$svg .= '<line x1="' . $field_xpos . '" x2="' . ($field_xpos + $field_width) . '" y1="' . ($field_ypos + ($field_height / 2)) . '" y2="' . ($field_ypos + ($field_height / 2)) . '" stroke="' . $this->color_default_lighter . '"/>';
							break;

						case 'file' :

							$button_text_xpos = $field_width;
							$alignment_y = ($input_found) ? $text_height : 0;

							$svg .= '<rect x="' . $field_xpos . '" y="' . ($field_ypos + $alignment_y) . '" class="wsf-wizard-button" fill="' . $this->color_default_inverted . '" stroke="' . $this->color_default_lighter . '" rx="' . $this->border_radius . '" width="' . $field_width . '" height="' . $field_height . '"/>';
							$svg .= '<text fill="' . $this->color_default . '" transform="matrix(1 0 0 1 ' . ($text_xpos + 1) . ' ' . ($field_ypos + $text_y_offset + 1 + $alignment_y) . ')" class="wsf-wizard-label">' . $field['label'] . '</text>';
							$svg .= '<rect x="' . ($field_width - $field_xpos - 12) . '" y="' . ($field_ypos + $alignment_y) . '" class="wsf-wizard-button" fill="' . $this->color_default_lighter . '" stroke="' . $this->color_default_lighter . '" rx="' . $this->border_radius . '" width="22" height="' . $field_height . '"/>';
							$svg .= '<text fill="' . $this->color_default . '" transform="matrix(1 0 0 1 ' . ($field_width - $field_xpos - 11) . ' ' . ($field_ypos + $text_y_offset + 1 + $alignment_y) . ')" class="wsf-wizard-label">Browse</text>';
							break;

						default :

							$svg .= '<text fill="' . $this->color_default . '" transform="matrix(1 0 0 1 ' . $text_xpos . ' ' . ($text_ypos + $text_y_offset) . ')" class="wsf-wizard-label">' . $field['label'] . '</text>';
							$svg .= '<rect x="' . $field_xpos . '" y="' . $field_ypos . '" fill="' . $this->color_default_inverted . '" stroke="' . $this->color_default_lighter . '" rx="' . $this->border_radius . '" width="' . $field_width . '" height="' . $field_height . '"/>';
					}
				}

				// Col index
				$col_index += $field_size + $field_offset;
				if($col_index >= $col_index_max) {

					$col_index = 0;
					$col_index_field = 0;
					$xpos_offset = ($col_width / 2);
					$ypos_offset += $row_height;
					$input_found = false;

				} else {

					$xpos_offset += $field_width + $gutter_width;
					$col_index_field++;
				}
			}

			$svg .= '</svg>';

			return $svg;
		}

		public function form_fields($form) {

			$fields = array();

			foreach($form['groups'] as $group) {

				foreach($group['sections'] as $section) {

					foreach($section['fields'] as $field) {

						$fields[] = array(

							'label'		=>	$field['label'],
							'type'		=>	$field['type'],
							'size'		=>	intval((isset($field['meta']['breakpoint_size_25'])) ? $field['meta']['breakpoint_size_25'] : 12),
							'offset'	=>	intval((isset($field['meta']['breakpoint_offset_25'])) ? $field['meta']['breakpoint_offset_25'] : 0)
						);
					}
				}
			}

			return $fields;
		}

		// Get wizards for each action installed
		public function db_get_actions() {

			$return_array = array();

			if(!isset(WS_Form_Action::$actions)) { parent::db_throw_error(__('No actions installed', 'ws-form')); }

			// Capabilities required of each action
			$capabilities_required = array('get_lists', 'get_list', 'get_list_fields');

			// Get actions that have above capabilities
			$actions = WS_Form_Action::get_actions_with_capabilities($capabilities_required);

			// Run through each action
			foreach($actions as $action) {

				// Add to return array
				$return_array[] = (object) array(

					'id'					=>	$action->id,
					'label'					=>	$action->label,
					'reload'				=>	isset($action->add_new_reload) ? $action->add_new_reload : true,
					'list_sub_modal_label'	=>	isset($action->list_sub_modal_label) ? $action->list_sub_modal_label : false
				);
			}

			return $return_array;
		}

		// Get wizards for each action installed
		public function db_get_action_wizards() {

			$return_array = array();

			if(!isset(WS_Form_Action::$actions)) { parent::db_throw_error(__('No actions installed', 'ws-form')); }

			// Check action ID
			self::db_check_action_id();

			// Capabilities required of each action
			$capabilities_required = array('get_lists', 'get_list', 'get_list_fields');

			// Get actions that have above capabilities
			$actions = WS_Form_Action::get_actions_with_capabilities($capabilities_required);

			if(!isset($actions[$this->action_id])) { parent::db_throw_error(__('Action not compatible with this function', 'ws-form')); }

			$action = $actions[$this->action_id];

			// Labels
			$field_label = isset($action->field_label) ? $action->field_label : false;
			$record_label = isset($action->record_label) ? $action->record_label : false;

			// Get lists
			$lists = $action->get_lists();

			foreach($lists as $list) {

				// Add to return array
				$return_array[] = array(

					'id'			=>	$list['id'],
					'label'			=>	$list['label'],
					'field_count'	=>	$list['field_count'],
					'record_count'	=>	$list['record_count'],
					'list_sub'		=>	isset($list['list_sub']) ? $list['list_sub'] : false,
					'svg'			=>	WS_Form_Action::get_svg($this->action_id, $list['id'], $list['label'], $list['field_count'], $list['record_count'], $field_label, $record_label)
				);
			}

			return $return_array;
		}

		// Render wizard category
		public function wizard_category_render($wizard_category, $button_class = 'wsf-button wsf-button-primary wsf-button-full') {

			// Colors
			$this->color_default = WS_Form_Common::option_get('skin_color_default');
			$this->color_default_inverted = WS_Form_Common::option_get('skin_color_default_inverted');
			$this->color_default_lighter = WS_Form_Common::option_get('skin_color_default_lighter');
?>
<!-- Blank -->
<li>
<div class="wsf-template" data-action="wsf-add-blank" data-id="blank">
	<svg class="wsf-responsive" viewBox="0 0 140 180"><rect width="100%" height="100%" fill="<?php echo $this->color_default_inverted; ?>"/><text fill="<?php echo $this->color_default; ?>'" class="wsf-wizard-title"><tspan x="5" y="16"><?php _e('Blank', 'ws-form'); ?></tspan></text><g fill="none" fill-rule="evenodd" transform="translate(5 7)"><path stroke="<?php echo $this->color_default_lighter; ?>" stroke-dasharray="4 2" d="M.5 17.5h129v149H.5z"/><path fill="<?php echo $this->color_default; ?>" fill-rule="nonzero" d="M72 88.5h-5v-5h-2v5h-5v2h5v5h2v-5h5z"/></g></svg>
</div>
<button class="<?php echo $button_class; ?>" data-action="wsf-add-blank" data-id="blank"><?php _e('Create', 'ws-form'); ?></button>
</li>
<!-- /Blank -->
<?php
			if(isset($wizard_category->wizards)) {

				// Loop through wizards
				foreach ($wizard_category->wizards as $wizard)  {

					// Is pro required to use this template?
					$pro_required = !WS_Form_Common::is_edition($wizard->pro_required ? 'pro' : 'basic');

?><li<?php if($pro_required) { ?> class="wsf-pro-required"<?php } ?>>
<div class="wsf-template"<?php if(!$pro_required) { ?> data-action="wsf-add-wizard" data-id="<?php echo $wizard->id; ?>"<?php } ?> title="<?php echo htmlentities($wizard->label); ?>">
<?php
					if($pro_required) {
?><a href="<?php echo WS_Form_Common::get_plugin_website_url('', 'add_form'); ?>" target="_blank"><?php
					}

					// Parse SVG
					$svg = $wizard->svg;

					if(empty($svg)) {

						$this->id = $wizard->id;
						$svg = $this->svg();
					}
					$svg = str_replace('#label', htmlentities($wizard->label), $svg);
					echo $svg;

					if($pro_required) {
?></a><?php
					}
?>
</div>
<?php
					if($pro_required) {
?>
<a href="<?php echo WS_Form_Common::get_plugin_website_url('', 'add_form'); ?>" class="wsf-button wsf-button-primary wsf-button-full" target="_blank"><?php _e('PRO', 'ws-form'); ?></a>
<?php
					} else {
?>
<button class="<?php echo $button_class; ?>" data-action="wsf-add-wizard" data-id="<?php echo $wizard->id; ?>"><?php _e('Create', 'ws-form'); ?></button>
<?php
					}
?>
</li>
<?php
				}
			}
		}

		// Check id
		public function db_check_id() {

			if(empty($this->id)) { parent::db_throw_error(__('Invalid ID', 'ws-form')); }
			return true;
		}

		// Check action_id
		public function db_check_action_id() {

			if($this->action_id === false) { parent::db_throw_error(__('Invalid action ID', 'ws-form')); }
			return true;
		}
	}