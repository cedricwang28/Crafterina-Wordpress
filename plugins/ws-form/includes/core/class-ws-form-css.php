<?php

	class WS_Form_CSS {

		// Admin
		public function get_admin() {

			// Get form column count
			$columns = abs(WS_Form_Common::option_get('framework_column_count', 0));
			if($columns == 0) { self::db_throw_error(__('Invalid framework column count', 'ws-form')); }

			// Read frameworks
			$frameworks = WS_Form_Config::get_frameworks();

			// Get framework ID
			$framework_id = WS_Form_Common::option_get('framework', 'ws-form');

			// Get framework
			$framework = $frameworks['types'][$framework_id];

			// Get column class mask
			$column_class = $framework['columns']['column_css_selector'];
			$columns_default = $framework['columns']['column_count'];

			// Get current framework breakpoints
			$breakpoints_outer = $framework['breakpoints'];
			$breakpoints_inner = $framework['breakpoints'];

			// Build CSS
			$css_return = ".wsf-group:before {\n\tbackground-image: repeating-linear-gradient(to right, #E5E5E5, #E5E5E5 calc((100% / $columns) - 12px), transparent calc((100% / $columns) - 12px), transparent calc(100% / $columns));\n\tbackground-size: calc(100% + 12px) 100%;\n}\n\n";
			$css_return .= ".wsf-section > .wsf-section-inner:before {\n\tbackground-image: repeating-linear-gradient(to right, #E5E5E5, #E5E5E5 calc((100% / $columns) - 12px), transparent calc((100% / $columns) - 12px), transparent calc(100% / $columns));\n\tbackground-size: calc(100% - 12px) 100%;\n\tbackground-position-x: 12px;\n}\n\n";

			// Grid
			$css_return .= ".wsf-sections, .wsf-fields {\n";

			$css_return .= "\tdisplay: -webkit-box;\n";
			$css_return .= "\tdisplay: -ms-flexbox;\n";
			$css_return .= "\tdisplay: flex;\n";
			$css_return .= "\t-ms-flex-wrap: wrap;\n";
			$css_return .= "\tflex-wrap: wrap;\n";

			$css_return .= "}\n\n";

			$breakpoint_outer_index = 0;
			foreach($breakpoints_outer as $key_outer => $breakpoint_outer) {

				// Get outer breakpoint ID and name
				$breakpoint_outer_id = $breakpoint_outer['id'];
				$breakpoint_outer_name = $breakpoint_outer['name'];

				// Output comment
				$css_return .= WS_Form_Common::comment_css($breakpoint_outer_name);

				// Add classes for breakpoint widths to resize admin
				if(WS_Form_Common::option_get('helper_breakpoint_width', false)) {

					// Output max-width statements
					if($breakpoint_outer_index != (count($breakpoints_outer) - 1)) {

						if(!isset($breakpoint_outer['admin_max_width'])) {

							self::db_throw_error(__('Admin max width not defined: ' . $breakpoint_outer_id, 'ws-form'));

						} else {

							$breakpoint_outer_max_width = $breakpoint_outer['admin_max_width'];
						}

						$css_return .= "#wsf-form[data-breakpoint=\"" . $breakpoint_outer_id . "\"] { max-width: " . $breakpoint_outer_max_width . "px; }\n\n";
					}
				}

				// Check for breakpoint specific CSS selector
				if(isset($breakpoint_outer['column_css_selector'])) {

					$column_class_single = $breakpoint_outer['column_css_selector'];

				} else {

					$column_class_single = $column_class;
				}

				// Columns - Run through each column
				for($column_index = 1; $column_index <= $columns; $column_index++) {

					// Create CSS for each column and each breakpoint
					$breakpoint_inner_index = 1;
					foreach($breakpoints_inner as $key_inner => $breakpoint_inner) {

						// Get inner breakpoint ID
						$breakpoint_inner_id = $breakpoint_inner['id'];

						// Build mask values for parser
						$mask_values = ['id' => $breakpoint_outer_id, 'size' => $column_index];

						// COLUMN

						// Get single class
						$class_single = WS_Form_Common::mask_parse($column_class_single, $mask_values);

						// Build CSS selectors
						$css_return .= "#wsf-form[data-breakpoint=\"" . $breakpoint_inner_id . '"] ' . $class_single;

						// Get key of top breakpoint (we'll remove this for the next run)
						if($breakpoint_inner_index == 1) { $breakpoint_inner_key_to_delete = $key_inner; }

						if($breakpoint_inner_index == count($breakpoints_inner)) {

							$column_width_percentage = ($column_index / $columns) * 100;

							$css_return .= " {";

							$css_return .= "\n\t-webkit-box-flex: 0;";
							$css_return .= "\n\t-ms-flex: 0 0 " . $column_width_percentage . "%;";
							$css_return .= "\n\tflex: 0 0 " . $column_width_percentage . "%;";
							$css_return .= "\n\tmax-width: " . $column_width_percentage . "%;";

							$css_return .= "\n}\n\n";

						} else {

							// Add comma (not at last inner breakpoint yet)
							$css_return .= ",\n";
						}

						$breakpoint_inner_index++;
					}
				}

				// Take top key off the inner breakpoints
				unset($breakpoints_inner[$breakpoint_inner_key_to_delete]);

				$breakpoint_outer_index++;
			}

			// Offsets - Run through each column
			$offset_class = $framework['columns']['offset_css_selector'];

			// Get current framework breakpoints
			$breakpoints_outer = $framework['breakpoints'];
			$breakpoints_inner = $framework['breakpoints'];

			foreach($breakpoints_outer as $key_outer => $breakpoint_outer) {

				// Get outer breakpoint ID and name
				$breakpoint_outer_id = $breakpoint_outer['id'];
				$breakpoint_outer_name = $breakpoint_outer['name'];

				// Check for breakpoint specific CSS selector
				if(isset($breakpoint_outer['offset_css_selector'])) {

					$offset_class_single = $breakpoint_outer['offset_css_selector'];

				} else {

					$offset_class_single = $offset_class;
				}

				// Output comment
				$css_return .= WS_Form_Common::comment_css($breakpoint_outer_name . ' - Offsets');

				for($column_index = 0; $column_index < $columns; $column_index++) {

					// Create CSS for each column and each breakpoint
					$breakpoint_inner_index = 1;
					foreach($breakpoints_inner as $key_inner => $breakpoint_inner) {

						// Get inner breakpoint ID
						$breakpoint_inner_id = $breakpoint_inner['id'];

						// Build mask values for parser
						$mask_values = ['id' => $breakpoint_outer_id, 'offset' => $column_index];

						// Get single offset
						$offset_single = WS_Form_Common::mask_parse($offset_class_single, $mask_values);

						// Get key of top breakpoint (we'll remove this for the next run)
						if($breakpoint_inner_index == 1) { $breakpoint_inner_key_to_delete = $key_inner; }

						// Build CSS selectors
						$css_return .= "#wsf-form[data-breakpoint=\"" . $breakpoint_inner_id . '"] ' . $offset_single;

						// Get key of top breakpoint (we'll remove this for the next run)
						if($breakpoint_inner_index == 1) { $breakpoint_inner_key_to_delete = $key_inner; }

						if($breakpoint_inner_index == count($breakpoints_inner)) {

							$column_width_percentage = ($column_index / $columns) * 100;

							// Build offset CSS
							$css_return .= " {";

							$css_return .= "\n\tbackground-size: " . $column_width_percentage . "%;";
							$css_return .= "\n\tmargin-left: " . $column_width_percentage . "%;";

							$css_return .= "\n}\n\n";

						} else {

							// Add comma (not at last inner breakpoint yet)
							$css_return .= ",\n";
						}

						$breakpoint_inner_index++;
					}
				}

				// Take top key off the inner breakpoints
				unset($breakpoints_inner[$breakpoint_inner_key_to_delete]);
			}

			return $css_return;
		}

		// Public
		public function get_public() {

			// Build CSS
			$css_return = '';

			// Check for wizard call
			$wizard = WS_Form_Common::get_query_var('wizard', false);
			if($wizard) {

				global $wizard;
				$wizard = true;
			}

			// Read frameworks
			$frameworks = WS_Form_Config::get_frameworks();

			// Get framework ID
			$framework_id = WS_Form_Common::option_get('framework', 'ws-form');

			// Get framework
			$framework = $frameworks['types'][$framework_id];

			// Get column class mask
			$column_class = $framework['columns']['column_css_selector'];
			$columns_default = $framework['columns']['column_count'];

			// Get form column count
			$columns = abs(WS_Form_Common::option_get('framework_column_count', 0));
			if($columns == 0) { self::db_throw_error(__('Invalid framework column count', 'ws-form')); }

			$grid_spacing = 0;
			$grid_spacing_unit = 'px';

			// Invalid Feedback
			$css_return .= ".wsf-invalid-feedback {\n";
			$css_return .= "\tdisplay: none;\n";
			$css_return .= "}\n\n";

			$css_return .= ".wsf-validated .wsf-field:invalid ~ .wsf-invalid-feedback, .wsf-validated .wsf-field.wsf-invalid ~ .wsf-invalid-feedback {\n";
			$css_return .= "\tdisplay: block;\n";
			$css_return .= "}\n\n";

			// Grid
			$css_return .= ".wsf-grid {\n";

			$css_return .= "\tdisplay: -webkit-box;\n";
			$css_return .= "\tdisplay: -ms-flexbox;\n";
			$css_return .= "\tdisplay: flex;\n";
			$css_return .= "\t-ms-flex-wrap: wrap;\n";
			$css_return .= "\tflex-wrap: wrap;\n";

			if($grid_spacing > 0) {

				$css_return .= "\tmargin-left: " . (($grid_spacing / 2) * -1) . $grid_spacing_unit . ";\n";
				$css_return .= "\tmargin-right: " . (($grid_spacing / 2) * -1) . $grid_spacing_unit . ";\n";
			}

			$css_return .= "}\n\n";

			// Tile
			$css_return .= ".wsf-tile {\n";
			$css_return .= "\tposition: relative;\n";
			$css_return .= "\twidth: 100%;\n";
			$css_return .= "\tbox-sizing: border-box;\n";

			if($grid_spacing > 0) {
				$css_return .= "\tpadding-left: " . ($grid_spacing / 2) . $grid_spacing_unit . ";\n";
				$css_return .= "\tpadding-right: " . ($grid_spacing / 2) . $grid_spacing_unit . ";\n";
			}
			$css_return .= "}\n\n";

			// Breakpoint CSS
			foreach($framework['breakpoints'] as $key => $breakpoint) {

				// Get outer breakpoint ID and name
				$breakpoint_id = $breakpoint['id'];
				$breakpoint_name = $breakpoint['name'];
				if(isset($breakpoint['min_width'])) {
					$breakpoint_min_width = $breakpoint['min_width'];
				} else {
					$breakpoint_min_width = 0;
				}

				// Output comment
				$css_return .= WS_Form_Common::comment_css($breakpoint_name);

				// Output media query
				$css_indent = '';
				if($breakpoint_min_width > 0) {

					$css_return .= "@media (min-width: " . $breakpoint_min_width . "px) {\n\n";
					$css_indent = "\t";
				}

				// Check for breakpoint specific CSS selector
				if(isset($breakpoint['column_css_selector'])) {

					$column_class_single = $breakpoint['column_css_selector'];

				} else {

					$column_class_single = $column_class;
				}

				// Run through each column
				for($column_index = 1; $column_index <= $columns; $column_index++) {

					// Build mask values for parser
					$mask_values = ['id' => $breakpoint_id, 'size' => $column_index];

					// Get single class
					$class_single = WS_Form_Common::mask_parse($column_class_single, $mask_values);

					// Build CSS selectors
					$css_return .= $css_indent . $class_single;

					$column_width_percentage = ($column_index / $columns) * 100;

					$css_return .= " {";

					$css_return .= "\n" . $css_indent . "\t-webkit-box-flex: 0;";
					$css_return .= "\n" . $css_indent . "\t-ms-flex: 0 0 " . $column_width_percentage . "%;";
					$css_return .= "\n" . $css_indent . "\tflex: 0 0 " . $column_width_percentage . "%;";
					$css_return .= "\n" . $css_indent . "\tmax-width: " . $column_width_percentage . "%;";

					$css_return .= "\n" . $css_indent . "}\n\n";
				}

				// Close media query
				if($breakpoint_min_width > 0) {

					$css_return .= "}\n\n";
				}
			}

			// Offsets - Run through each column
			$offset_class = $framework['columns']['offset_css_selector'];

			// Breakpoint CSS
			foreach($framework['breakpoints'] as $key => $breakpoint) {

				// Get outer breakpoint ID and name
				$breakpoint_id = $breakpoint['id'];
				$breakpoint_name = $breakpoint['name'];
				if(isset($breakpoint['min_width'])) {
					$breakpoint_min_width = $breakpoint['min_width'];
				} else {
					$breakpoint_min_width = 0;
				}

				// Output comment
				$css_return .= WS_Form_Common::comment_css($breakpoint_name . ' - Offsets');

				// Output media query
				$css_indent = '';
				if($breakpoint_min_width > 0) {

					$css_return .= "@media (min-width: " . $breakpoint_min_width . "px) {\n\n";
					$css_indent = "\t";
				}

				// Check for breakpoint specific CSS selector
				if(isset($breakpoint['offset_css_selector'])) {

					$offset_class_single = $breakpoint['offset_css_selector'];

				} else {

					$offset_class_single = $offset_class;
				}

				for($column_index = 0; $column_index <= $columns; $column_index++) {

					// Build mask values for parser
					$mask_values = ['id' => $breakpoint_id, 'offset' => $column_index];

					// Get single offset
					$offset_single = WS_Form_Common::mask_parse($offset_class_single, $mask_values);

					$column_width_percentage = ($column_index / $columns) * 100;

					// Build CSS selectors
					$css_return .= $css_indent . $offset_single . " {\n";

					// Build offset CSS
					$css_return .= $css_indent . "\tmargin-left: " . $column_width_percentage . "%;\n";

					$css_return .= $css_indent . "}\n\n";
				}

				// Close media query
				if($breakpoint_min_width > 0) {

					$css_return .= "}\n\n";
				}
			}

			$css_return .= ".wsf-bottom .wsf-grid {\n";
			$css_return .= "\talign-items: flex-end;\n";
			$css_return .= "}\n\n";

			$css_return .= ".wsf-top .wsf-grid {\n";
			$css_return .= "\talign-items: flex-start;\n";
			$css_return .= "}\n\n";

			$css_return .= ".wsf-middle .wsf-grid {\n";
			$css_return .= "\talign-items: center;\n";
			$css_return .= "}\n\n";

			$css_return .= ".wsf-field-wrapper.wsf-bottom {\n";
			$css_return .= "\talign-self: flex-end;\n";
			$css_return .= "}\n\n";

			$css_return .= ".wsf-field-wrapper.wsf-top {\n";
			$css_return .= "\talign-self: flex-start;\n";
			$css_return .= "}\n\n";

			$css_return .= ".wsf-field-wrapper.wsf-middle {\n";
			$css_return .= "\talign-self: center;\n";
			$css_return .= "}\n\n";

			return $css_return;
		}

		// Skin
		public function render_skin() {

			// Customizer
			$enable_cache = !(WS_Form_Common::get_query_var('customize_theme') !== '');

			// Colors
			$color_default = WS_Form_Common::option_get('skin_color_default', false, false, $enable_cache);
			$color_default_inverted = WS_Form_Common::option_get('skin_color_default_inverted', false, false, $enable_cache);
			$color_default_light = WS_Form_Common::option_get('skin_color_default_light', false, false, $enable_cache);
			$color_default_lighter = WS_Form_Common::option_get('skin_color_default_lighter', false, false, $enable_cache);
			$color_default_lightest = WS_Form_Common::option_get('skin_color_default_lightest', false, false, $enable_cache);
			$color_primary = WS_Form_Common::option_get('skin_color_primary', false, false, $enable_cache);
			$color_secondary = WS_Form_Common::option_get('skin_color_secondary', false, false, $enable_cache);
			$color_success = WS_Form_Common::option_get('skin_color_success', false, false, $enable_cache);
			$color_information = WS_Form_Common::option_get('skin_color_information', false, false, $enable_cache);
			$color_warning = WS_Form_Common::option_get('skin_color_warning', false, false, $enable_cache);
			$color_danger = WS_Form_Common::option_get('skin_color_danger', false, false, $enable_cache);

			// Components
			$border = (WS_Form_Common::option_get('skin_border', false, false, $enable_cache) == 'true');
			$border_width = WS_Form_Common::option_get('skin_border_width', false, false, $enable_cache);
			$border_style = WS_Form_Common::option_get('skin_border_style', false, false, $enable_cache);
			$border_radius = WS_Form_Common::option_get('skin_border_radius', false, false, $enable_cache);

			// Transitions
			$transition = (WS_Form_Common::option_get('skin_transition', false, false, $enable_cache) == 'true');
			$transition_speed = WS_Form_Common::option_get('skin_transition_speed', false, false, $enable_cache);
			$transition_timing_function = WS_Form_Common::option_get('skin_transition_timing_function', false, false, $enable_cache);

			// Typography
			$font_family = WS_Form_Common::option_get('skin_font_family', false, false, $enable_cache);
			$font_size = WS_Form_Common::option_get('skin_font_size', false, false, $enable_cache);
			$font_size_large = WS_Form_Common::option_get('skin_font_size_large', false, false, $enable_cache);
			$font_size_small = WS_Form_Common::option_get('skin_font_size_small', false, false, $enable_cache);
			$font_weight = WS_Form_Common::option_get('skin_font_weight', false, false, $enable_cache);
			$line_height = WS_Form_Common::option_get('skin_line_height', false, false, $enable_cache);

			// Advanced
			$unit_of_measurement = 'px';
			$grid_gutter = WS_Form_Common::option_get('skin_grid_gutter', false, false, $enable_cache);

			// Spacing
			$spacing = 20;
			$spacing_extra_large = 80;
			$spacing_large = 40;
			$spacing_small = 10;
			$spacing_extra_small = 5;

			// Forms
			$form_background_color = $color_default_inverted;
			$form_border = $border; // true | false
			$form_border_color = $color_default_lighter;
			$form_border_style = $border_style;
			$form_border_width = $border_width;
			$form_border_radius = $border_radius;
			$form_checked_color = $color_primary;
			$form_color = $color_default;
			$form_disabled_background_color = $color_default_lightest;
			$form_disabled_border_color = $form_border_color;
			$form_disabled_color = $color_default_light;
			$form_error_background_color = $form_background_color;
			$form_error_border_color = $color_danger;
			$form_error_color = $form_color;
			$form_focus = true; // true | false
			$form_focus_background_color = $form_background_color;
			$form_focus_border_color = $color_primary;
			$form_focus_color = $form_color;
			$form_font_size = $font_size;
			$form_font_size_large = $font_size_large;
			$form_font_size_small = $font_size_small;
			$form_help_color = $color_default_light;
			$form_invalid_feedback_color = $color_danger;
			$form_hover = false; // true | false
			$form_hover_background_color = $form_background_color;
			$form_hover_border_color = $color_primary;
			$form_hover_color = $form_color;
			$form_label_color = $form_color;
			$form_placeholder_color = $color_default_lighter;
			$form_spacing_horizontal = $spacing_small;
			$form_spacing_vertical = ($spacing_small * .85);
			$form_transition = $transition; // true | false
			$form_transition_speed = $transition_speed;
			$form_transition_timing_function = $transition_timing_function;
			$input_height = ((round($form_font_size * $line_height) + ($form_spacing_vertical * 2)) + ($form_border_width * 2));
			$checkbox_size = round($form_font_size * $line_height);
			$radio_size = round($form_font_size * $line_height);
			$color_size = $input_height;

			// Wizard mode
			if(WS_Form_Common::get_query_var('wizard') != '') {

				$form_background_color = '#CECED2';
			}
?>
.wsf-form {
	box-sizing: border-box;
	color: <?php echo $color_default; ?>;
	font-family: <?php echo $font_family; ?>;
	font-size: <?php echo $font_size . $unit_of_measurement; ?>;
	font-weight: <?php echo $font_weight; ?>;
	line-height: <?php echo $line_height; ?>;
	-webkit-tap-highlight-color: transparent;
	text-size-adjust: 100%;
}

.wsf-form *, .wsf-form *:before, .wsf-form *:after {
	box-sizing: inherit;
}

.wsf-form fieldset {
	border: none;
	margin: 0;
	min-width: 0;
	padding: 0;
}

.wsf-form fieldset legend {
	border: 0;
	font-size: 25px;
	margin-bottom: <?php echo $spacing_small . $unit_of_measurement; ?>;
	padding: 0;
}

.wsf-form small {
	font-size: <?php echo $font_size_small . $unit_of_measurement; ?>;
}

.wsf-form ul.wsf-group-tabs {
	border-bottom: <?php echo $border_width . $unit_of_measurement . ' ' . $border_style . ' ' . $color_default_lighter; ?>;
	display: flex;
	flex-direction: row;
	flex-wrap: wrap;
	list-style: none;
	margin-bottom: <?php echo $spacing . $unit_of_measurement; ?>;
	margin-left: -<?php echo $spacing_extra_small . $unit_of_measurement; ?>;
	margin-right: -<?php echo $spacing_extra_small . $unit_of_measurement; ?>;
	margin-top: 0;
	padding-left: 0;
	position: relative;
}

.wsf-form ul.wsf-group-tabs > li {
	box-sizing: border-box;
	margin-bottom: -<?php echo $border_width . $unit_of_measurement; ?>;
	outline: none;
	padding: 0 <?php echo $spacing_extra_small . $unit_of_measurement; ?>;
	position: relative;
}

.wsf-form ul.wsf-group-tabs > li > a {
	background-color: transparent;
	border: <?php echo $border_width . $unit_of_measurement . ' ' . $border_style . ' transparent'; ?>;
<?php if ($border_radius > 0) { ?>
	border-top-left-radius: <?php echo $form_border_radius . $unit_of_measurement; ?>;
	border-top-right-radius: <?php echo $form_border_radius . $unit_of_measurement; ?>;
<?php } ?>
	box-shadow: none;
	color: <?php echo $color_default; ?>;
	cursor: pointer;
	display: block;
	font-size: <?php echo $form_font_size . $unit_of_measurement; ?>;
	padding: 11px <?php echo $spacing . $unit_of_measurement; ?>;
	text-align: center;
	text-decoration: none;
<?php if ($transition) { ?>
	transition: background-color <?php echo $transition_speed . 'ms ' . $transition_timing_function; ?>, border-color <?php echo $transition_speed . 'ms ' . $transition_timing_function; ?>;
<?php } ?>
	white-space: nowrap;
}

.wsf-form ul.wsf-group-tabs > li.wsf-tab-active {
	z-index: 1;
}

.wsf-form ul.wsf-group-tabs > li.wsf-tab-active > a {
	background-color: <?php echo $color_default_inverted; ?>;
	border-color: <?php echo  $color_default_lighter; ?>;
	border-bottom-color: transparent;
	color: <?php echo $color_default; ?>;
	cursor: default;
}

.wsf-form ul.wsf-group-tabs > li > a.wsf-tab-disabled {
	color: <?php echo $color_default_light; ?>;
	cursor: not-allowed;
	pointer-events: none;
}

.wsf-grid {
	margin-left: -<?php echo ($grid_gutter / 2) . $unit_of_measurement; ?>;
	margin-right: -<?php echo ($grid_gutter / 2) . $unit_of_measurement; ?>;
}

.wsf-tile {
	padding-left: <?php echo ($grid_gutter / 2) . $unit_of_measurement; ?>;
	padding-right: <?php echo ($grid_gutter / 2) . $unit_of_measurement; ?>;
}

.wsf-field-wrapper {
	margin-bottom: <?php echo $grid_gutter . $unit_of_measurement; ?>;
}

.wsf-field-wrapper[data-type='divider'],
.wsf-field-wrapper[data-type='texteditor'],
.wsf-field-wrapper[data-type='html'] {
	margin-bottom: 0;
}

.wsf-inline {
	display: inline-flex;
	flex-direction: column;
	margin-right: <?php echo $spacing_small . $unit_of_measurement; ?>;
}

html.rtl .wsf-inline,
body.rtl .wsf-inline {
	margin-left: <?php echo $spacing_small . $unit_of_measurement; ?>;
	margin-right: 0;
}

label.wsf-label {
	display: block;
<?php if ($form_label_color != $color_default) { ?>
	color: <?php echo $form_label_color; ?>;
<?php } ?>
	font-size: <?php echo $form_font_size . $unit_of_measurement; ?>;
	line-height: <?php echo $line_height; ?>;
	margin-bottom: <?php echo $spacing_extra_small . $unit_of_measurement; ?>;
}

.wsf-invalid-feedback {
	color: <?php echo $form_invalid_feedback_color; ?>;
	font-size: <?php echo $form_font_size_small . $unit_of_measurement; ?>;
	line-height: <?php echo $line_height; ?>;
	margin-top: <?php echo $spacing_extra_small . $unit_of_measurement; ?>;
}

.wsf-help {
	color: <?php echo $form_help_color; ?>;
	display: block;
	font-size: <?php echo $form_font_size_small . $unit_of_measurement; ?>;
	line-height: <?php echo $line_height; ?>;
	margin-top: <?php echo $spacing_extra_small . $unit_of_measurement; ?>;
}

input[type=email].wsf-field,
input[type=number].wsf-field,
input[type=tel].wsf-field,
input[type=text].wsf-field,
input[type=search].wsf-field,
input[type=url].wsf-field,
select.wsf-field,
textarea.wsf-field {
	-webkit-appearance: none;
	background-color: <?php echo $form_background_color; ?>;
<?php if ($form_border) { ?>
	border: <?php echo $form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color; ?>;
<?php } else { ?>
	border: none;
<?php } ?>
	border-radius: <?php echo $form_border_radius . $unit_of_measurement; ?>;
	color: <?php echo $form_color; ?>;
	font-family: <?php echo $font_family; ?>;
	font-size: <?php echo $form_font_size . $unit_of_measurement; ?>;
	font-weight: <?php echo $font_weight; ?>;
	line-height: <?php echo $line_height; ?>;
	margin: 0;
	padding: <?php echo $form_spacing_vertical . $unit_of_measurement . ' ' . $form_spacing_horizontal . $unit_of_measurement; ?>;
	touch-action: manipulation;
<?php if ($form_transition) { ?>
	transition: background-color <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>, border-color <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>, background-image <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>;
<?php } ?>
	width: 100%;
}

input[type=email].wsf-field,
input[type=number].wsf-field,
input[type=tel].wsf-field,
input[type=text].wsf-field,
input[type=search].wsf-field,
input[type=url].wsf-field,
select.wsf-field:not([multiple]):not([size]) {
	height: <?php echo $input_height . $unit_of_measurement; ?>;
}


input[type=email].wsf-field::placeholder,
input[type=number].wsf-field::placeholder,
input[type=tel].wsf-field::placeholder,
input[type=text].wsf-field::placeholder,
input[type=search].wsf-field::placeholder,
input[type=url].wsf-field::placeholder,
select.wsf-field::placeholder,
textarea.wsf-field::placeholder {
	color: <?php echo $form_placeholder_color; ?>;
	opacity: 1;
}

<?php if ($form_hover) { ?>
input[type=email].wsf-field:hover:enabled,
input[type=number].wsf-field:hover:enabled,
input[type=tel].wsf-field:hover:enabled,
input[type=text].wsf-field:hover:enabled,
input[type=search].wsf-field:hover:enabled,
input[type=url].wsf-field:hover:enabled,
select.wsf-field:hover:enabled,
textarea.wsf-field:hover:enabled {
<?php if ($form_hover_background_color != $form_background_color) { ?>
	background-color: <?php echo $form_hover_background_color; ?>;
<?php } ?>
<?php if ($form_hover_border_color != $form_border_color) { ?>
	border-color: <?php echo $form_hover_border_color ?>;
<?php } ?>
<?php if ($form_hover_color != $form_color) { ?>
	color: <?php echo $form_hover_color ?>;
<?php } ?>
}
<?php } ?>

input[type=email].wsf-field:focus,
input[type=number].wsf-field:focus,
input[type=tel].wsf-field:focus,
input[type=text].wsf-field:focus,
input[type=search].wsf-field:focus,
input[type=url].wsf-field:focus,
select.wsf-field:focus,
textarea.wsf-field:focus {
<?php if ($form_focus) { ?>
<?php if ($form_focus_background_color != $form_background_color) { ?>
	background-color: <?php echo $form_focus_background_color; ?>;
<?php } ?>
<?php if ($form_focus_border_color != $form_border_color) { ?>
	border-color: <?php echo $form_focus_border_color; ?>;
<?php } ?>
<?php if ($form_focus_color != $form_color) { ?>
	color: <?php echo $form_focus_color; ?>;
<?php } ?>
<?php } ?>
	outline: 0;
}

input[type=email].wsf-field:disabled,
input[type=number].wsf-field:disabled,
input[type=tel].wsf-field:disabled,
input[type=text].wsf-field:disabled,
input[type=search].wsf-field:disabled,
input[type=url].wsf-field:disabled,
select.wsf-field:disabled,
textarea.wsf-field:disabled {
<?php if ($form_disabled_background_color != $form_background_color) { ?>
	background-color: <?php echo $form_disabled_background_color; ?>;
<?php } ?>
<?php if ($form_border) { ?>
<?php if ($form_disabled_border_color != $form_border_color) { ?>
	border-color: <?php echo $form_disabled_border_color; ?>;
<?php } ?>
<?php } ?>
<?php if ($form_disabled_color != $form_color) { ?>
	color: <?php echo $form_disabled_color; ?>;
	-webkit-text-fill-color: <?php echo $form_disabled_color; ?>;
<?php } else { ?>
	-webkit-text-fill-color: <?php echo $form_color; ?>;
<?php } ?>
	cursor: not-allowed;
	opacity: 1;
	user-select: none;
}

input[type=email].wsf-field::-moz-focus-inner,
input[type=number].wsf-field::-moz-focus-inner,
input[type=tel].wsf-field::-moz-focus-inner,
input[type=text].wsf-field::-moz-focus-inner,
input[type=search].wsf-field::-moz-focus-inner,
input[type=url].wsf-field::-moz-focus-inner,
select.wsf-field::-moz-focus-inner,
textarea.wsf-field::-moz-focus-inner {
	border: 0;
	padding: 0;
}

input[type=number].wsf-field::-webkit-inner-spin-button,
input[type=number].wsf-field::-webkit-outer-spin-button {
	height: auto;
}


select.wsf-field:not([multiple]):not([size]) {
	background-image: url('data:image/svg+xml,<svg%20width%3D"10"%20height%3D"5"%20viewBox%3D"169%20177%2010%205"%20xmlns%3D"http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg"><path%20fill%3D"<?php echo urlencode($form_color); ?>"%20fill-rule%3D"evenodd"%20d%3D"M174%20182l5-5h-10"%2F><%2Fsvg>');
	background-position: right <?php echo $spacing_small . $unit_of_measurement; ?> center;
	background-repeat: no-repeat;
	background-size: <?php echo $spacing_small . $unit_of_measurement . ' ' . $spacing_extra_small . $unit_of_measurement; ?>;
	padding-right: <?php echo (($form_spacing_horizontal * 2) + $spacing_small) . $unit_of_measurement; ?>;
}

select.wsf-field:not([multiple]):not([size])::-ms-expand {
	display: none;
}

<?php if ($form_hover) { ?>
<?php if ($form_hover_color != $form_color) { ?>
	select.wsf-field:not([multiple]):not([size]):hover {
		background-image: url('data:image/svg+xml,<svg%20width%3D"10"%20height%3D"5"%20viewBox%3D"169%20177%2010%205"%20xmlns%3D"http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg"><path%20fill%3D"<?php echo urlencode($form_hover_color); ?>"%20fill-rule%3D"evenodd"%20d%3D"M174%20182l5-5h-10"%2F><%2Fsvg>');
	}
<?php } ?>
<?php } ?>

<?php if ($form_focus) { ?>
<?php if ($form_focus_color != $form_color) { ?>
select.wsf-field:not([multiple]):not([size]):focus {
	background-image: url('data:image/svg+xml,<svg%20width%3D"10"%20height%3D"5"%20viewBox%3D"169%20177%2010%205"%20xmlns%3D"http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg"><path%20fill%3D"<?php echo urlencode($form_focus_color); ?>"%20fill-rule%3D"evenodd"%20d%3D"M174%20182l5-5h-10"%2F><%2Fsvg>');
}
<?php } ?>
<?php } ?>

select.wsf-field:not([multiple]):not([size]):-moz-focusring {
	color: transparent;
	text-shadow: 0 0 0 #000;
}

select.wsf-field:not([multiple]):not([size]):disabled {
<?php if ($form_disabled_color != $form_color) { ?>
	border-color: <?php echo $form_disabled_border_color; ?>;
	background-image: url('data:image/svg+xml,<svg%20width%3D"10"%20height%3D"5"%20viewBox%3D"169%20177%2010%205"%20xmlns%3D"http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg"><path%20fill%3D"<?php echo urlencode($form_disabled_color); ?>"%20fill-rule%3D"evenodd"%20d%3D"M174%20182l5-5h-10"%2F><%2Fsvg>');
<?php } ?>
}

select.wsf-field optgroup {
	font-weight: bold;
}

<?php if ($form_disabled_color != $form_color) { ?>
select.wsf-field option:disabled {
	color: <?php echo $form_disabled_color; ?>;
}
<?php } ?>

textarea.wsf-field {
	overflow: auto;
	resize: vertical;
}

textarea.wsf-field[data-textarea-type='tinymce'] {
	border-top-left-radius: 0;
	border-top-right-radius: 0;
}

input[type=checkbox].wsf-field {
	height: <?php echo $checkbox_size . $unit_of_measurement; ?>;
	margin: 0;
	opacity: 0;
	position: absolute;
	width: <?php echo $checkbox_size . $unit_of_measurement; ?>;
}

input[type=checkbox].wsf-field + label.wsf-label {
	display: inline-block;
	margin-bottom: <?php echo $spacing_small . $unit_of_measurement; ?>;
	padding-left: <?php echo ($checkbox_size + $spacing_extra_small) . $unit_of_measurement; ?>;
	position: relative;
	user-select: none;
}

input[type=checkbox].wsf-field + label.wsf-label:before {
	background-color: <?php echo $form_background_color; ?>;
<?php if ($form_border) { ?>
	border: <?php echo $form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color; ?>;
<?php } ?>
<?php if ($form_border_radius > 0) { ?>
	border-radius: <?php echo $form_border_radius . $unit_of_measurement; ?>;
<?php } ?>
	content: '';
	cursor: pointer;
	display: inline-block;
	height: <?php echo $checkbox_size . $unit_of_measurement; ?>;
	left: 0;
	margin-bottom: -<?php echo $spacing_extra_small . $unit_of_measurement; ?>;
	margin-right: <?php echo $spacing_extra_small . $unit_of_measurement; ?>;
	position: absolute;
<?php if ($form_transition) { ?>
	transition: background-color <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>, border-color <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>, box-shadow <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>;
<?php } ?>
	vertical-align: top;
	width: <?php echo $checkbox_size . $unit_of_measurement; ?>;
}

input[type=checkbox].wsf-field + label.wsf-label + .wsf-invalid-feedback {
	margin-bottom: <?php echo $spacing_small . $unit_of_measurement; ?>;
	margin-top: -<?php echo $spacing_extra_small . $unit_of_measurement; ?>;
}

<?php if ($form_hover) { ?>
input[type=checkbox].wsf-field:enabled:hover + label.wsf-label:before {
<?php if ($form_hover_background_color != $form_background_color) { ?>
	background-color: <?php echo $form_hover_background_color; ?>;
<?php } ?>
<?php if ($form_hover_border_color != $form_border_color) { ?>
	border-color: <?php echo $form_hover_border_color; ?>
<?php } ?>
}
<?php } ?>

<?php if ($form_focus) { ?>
input[type=checkbox].wsf-field:focus + label.wsf-label:before {
<?php if ($form_focus_background_color != $form_background_color) { ?>
	background-color: <?php echo $form_focus_background_color; ?>;
<?php } ?>
<?php if ($form_focus_border_color != $form_border_color) { ?>
	border-color: <?php echo $form_focus_border_color; ?>;
<?php } ?>
}
<?php } ?>

input[type=checkbox].wsf-field:disabled + label.wsf-label {
<?php if ($form_disabled_color != $form_color) { ?>
	color: <?php echo $form_disabled_color; ?>;
<?php } ?>
}

input[type=checkbox].wsf-field:disabled + label.wsf-label:before {
<?php if ($form_disabled_background_color != $form_background_color) { ?>
	background-color: <?php echo $form_disabled_background_color; ?>;
<?php } ?>
<?php if ($form_border) { ?>
<?php if ($form_disabled_border_color != $form_border_color) { ?>
	border-color: <?php echo $form_disabled_border_color; ?>;
<?php } ?>
<?php } ?>
cursor: not-allowed;
}

input[type=checkbox].wsf-field:checked + label.wsf-label:before {
	background-color: <?php echo $form_checked_color; ?>;
	border-color: <?php echo $form_checked_color; ?>;
	box-shadow: inset 0 0 0 2px <?php echo $color_default_inverted; ?>;
}

input[type=checkbox].wsf-field:checked:disabled + label.wsf-label:before {
	opacity: .5;
}

input[type=checkbox].wsf-field.wsf-switch {
	width: <?php echo ($checkbox_size * 2) . $unit_of_measurement; ?>;
}

input[type=checkbox].wsf-field.wsf-switch + label.wsf-label {
	padding-left: <?php echo (($checkbox_size * 2) + $spacing_extra_small) . $unit_of_measurement; ?>;
	position: relative;
}

input[type=checkbox].wsf-field.wsf-switch + label.wsf-label:before {
	position: absolute;
	width: <?php echo ($checkbox_size * 2) . $unit_of_measurement; ?>;
}

input[type=checkbox].wsf-field.wsf-switch + label.wsf-label:after {
	background-color: <?php echo $form_border_color; ?>;
<?php if ($form_border) { ?>
	border: <?php echo $form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color; ?>;
<?php } ?>
<?php if ($form_border_radius > 0) { ?>
	border-radius: <?php echo $form_border_radius . $unit_of_measurement; ?>;
<?php } ?>
	content: '';
	cursor: pointer;
	display: inline-block;
	height: <?php echo $checkbox_size . $unit_of_measurement; ?>;
	left: 0;
	position: absolute;
	top: 0;
<?php if ($form_transition) { ?>
	transition: background-color <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>, border-color <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>, left <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>;
<?php } ?>
	vertical-align: top;
	width: <?php echo $checkbox_size . $unit_of_measurement; ?>;
}

<?php if ($form_hover) { ?>
input[type=checkbox].wsf-field.wsf-switch:enabled:hover + label.wsf-label:after {
<?php if ($form_hover_border_color != $form_border_color) { ?>
	border-color: <?php echo $form_hover_border_color; ?>
<?php } ?>
}
<?php } ?>

<?php if ($form_focus) { ?>
input[type=checkbox].wsf-field.wsf-switch:focus + label.wsf-label:after {
<?php if ($form_focus_border_color != $form_border_color) { ?>
	border-color: <?php echo $form_focus_border_color; ?>;
<?php } ?>
}
<?php } ?>

input[type=checkbox].wsf-field.wsf-switch:disabled + label.wsf-label:after {
<?php if ($form_border) { ?>
<?php if ($form_disabled_border_color != $form_border_color) { ?>
	border-color: <?php echo $form_disabled_border_color; ?>;
<?php } ?>
<?php } ?>
	cursor: not-allowed;
}

input[type=checkbox].wsf-field.wsf-switch:checked + label.wsf-label:before {
	background-color: <?php echo $form_background_color; ?>;
	box-shadow: none;
}

input[type=checkbox].wsf-field.wsf-switch:checked + label.wsf-label:after {
	background-color: <?php echo $form_checked_color; ?>;
	border-color: <?php echo $form_checked_color; ?>;
	left: <?php echo $checkbox_size . $unit_of_measurement; ?>;
}

input[type=checkbox].wsf-field.wsf-switch:checked:disabled + label.wsf-label:before,
input[type=checkbox].wsf-field.wsf-switch:checked:disabled + label.wsf-label:after {
	opacity: .5;
}

html.rtl input[type=checkbox].wsf-field + label.wsf-label,
body.rtl input[type=checkbox].wsf-field + label.wsf-label {
	padding-left: 0;
	padding-right: <?php echo ($checkbox_size + $spacing_extra_small) . $unit_of_measurement; ?>;
}

html.rtl input[type=checkbox].wsf-field + label.wsf-label:before,
body.rtl input[type=checkbox].wsf-field + label.wsf-label:before {
	left: initial;
	margin-right: <?php echo $spacing_extra_small . $unit_of_measurement; ?>;
	margin-right: 0;
	right: 0;
}

input[type=radio].wsf-field {
	height: <?php echo $radio_size . $unit_of_measurement; ?>;
	margin: 0;
	opacity: 0;
	position: absolute;
	width: <?php echo $radio_size . $unit_of_measurement; ?>;
}

input[type=radio].wsf-field + label.wsf-label {
	display: inline-block;
	margin-bottom: <?php echo $spacing_small . $unit_of_measurement; ?>;
	padding-left: <?php echo ($radio_size + $spacing_extra_small) . $unit_of_measurement; ?>;
	position: relative;
	user-select: none;
}

input[type=radio].wsf-field + label.wsf-label:before {
	background-color: <?php echo $form_background_color; ?>;
<?php if ($form_border) { ?>
	border: <?php echo $form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color; ?>;
<?php } ?>
	border-radius: 50%;
	content: '';
	cursor: pointer;
	display: inline-block;
	height: <?php echo $radio_size . $unit_of_measurement; ?>;
	left: 0;
	margin-bottom: -<?php echo $spacing_extra_small . $unit_of_measurement; ?>;
	margin-right: <?php echo $spacing_extra_small . $unit_of_measurement; ?>;
	position: absolute;
<?php if ($form_transition) { ?>
	transition: background-color <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>, border-color <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>, box-shadow <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>;
<?php } ?>
	vertical-align: top;
	width: <?php echo $radio_size . $unit_of_measurement; ?>;
}

input[type=radio].wsf-field + label.wsf-label + .wsf-invalid-feedback {
	margin-bottom: <?php echo $spacing_small . $unit_of_measurement; ?>;
	margin-top: -<?php echo $spacing_extra_small . $unit_of_measurement; ?>;
}

<?php if ($form_hover) { ?>
input[type=radio].wsf-field:enabled:hover + label.wsf-label:before {
<?php if ($form_hover_background_color != $form_background_color) { ?>
	background-color: <?php echo $form_hover_background_color; ?>;
<?php } ?>
<?php if ($form_hover_border_color != $form_border_color) { ?>
	border-color: <?php echo $form_hover_border_color; ?>
<?php } ?>
}
<?php } ?>

<?php if ($form_focus) { ?>
input[type=radio].wsf-field:focus + label.wsf-label:before {
<?php if ($form_focus_background_color != $form_background_color) { ?>
	background-color: <?php echo $form_focus_background_color; ?>;
<?php } ?>
<?php if ($form_focus_border_color != $form_border_color) { ?>
	border-color: <?php echo $form_focus_border_color; ?>;
<?php } ?>
}
<?php } ?>

input[type=radio].wsf-field:disabled + label.wsf-label {
<?php if ($form_disabled_color != $form_color) { ?>
	color: <?php echo $form_disabled_color; ?>;
<?php } ?>
}

input[type=radio].wsf-field:disabled + label.wsf-label:before {
<?php if ($form_disabled_background_color != $form_background_color) { ?>
	background-color: <?php echo $form_disabled_background_color; ?>;
<?php } ?>
<?php if ($form_border) { ?>
<?php if ($form_disabled_border_color != $form_border_color) { ?>
	border-color: <?php echo $form_disabled_border_color; ?>;
<?php } ?>
<?php } ?>
	cursor: not-allowed;
}

input[type=radio].wsf-field:checked + label.wsf-label:before {
	background-color: <?php echo $form_checked_color; ?>;
	border-color: <?php echo $form_checked_color; ?>;
	box-shadow: inset 0 0 0 2px <?php echo $color_default_inverted; ?>;
}

input[type=radio].wsf-field:checked:disabled + label.wsf-label:before {
	opacity: .5;
}

html.rtl input[type=radio].wsf-field + label.wsf-label,
body.rtl input[type=radio].wsf-field + label.wsf-label {
	padding-left: 0;
	padding-right: <?php echo ($radio_size + $spacing_extra_small) . $unit_of_measurement; ?>;
}

html.rtl input[type=radio].wsf-field + label.wsf-label:before,
body.rtl input[type=radio].wsf-field + label.wsf-label:before {
	left: initial;
	margin-right: <?php echo $spacing_extra_small . $unit_of_measurement; ?>;
	margin-right: 0;
	right: 0;
}

input[type=checkbox].wsf-field.wsf-button + label.wsf-label,
input[type=radio].wsf-field.wsf-button + label.wsf-label {
  	appearance: none;
  	background-color: <?php echo $color_default_lighter; ?>;
  <?php if ($form_border) { ?>
  	border: <?php echo $form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color; ?>;
  <?php } else { ?>
  	border: none;
  <?php } ?>
  <?php if ($form_border_radius > 0) { ?>
  	border-radius: <?php echo $form_border_radius . $unit_of_measurement; ?>;
  <?php } ?>
  	color: <?php echo $form_color; ?>;
  	cursor: pointer;
  	display: inline-block;
  	font-family: <?php echo $font_family; ?>;
  	font-size: <?php echo $form_font_size . $unit_of_measurement; ?>;
  	font-weight: <?php echo $font_weight; ?>;
  	line-height: <?php echo $line_height; ?>;
  	padding: <?php echo $form_spacing_vertical . $unit_of_measurement . ' ' . $form_spacing_horizontal . $unit_of_measurement; ?>;
  	margin: 0 0 <?php echo ($grid_gutter / 2) . $unit_of_measurement; ?>;
  	text-align: center;
  	text-decoration: none;
  	touch-action: manipulation;
  <?php if ($form_transition) { ?>
  	transition: background-color <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>, border-color <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>, color <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>;
  <?php } ?>
  	user-select: none;
  	vertical-align: middle;
}

input[type=checkbox].wsf-field.wsf-button + label.wsf-label:before,
input[type=radio].wsf-field.wsf-button + label.wsf-label:before {
	display: none;
}

input[type=checkbox].wsf-field.wsf-button:disabled + label.wsf-label,
input[type=radio].wsf-field.wsf-button:disabled + label.wsf-label {
	cursor: not-allowed;
	opacity: .5;
}

input[type=checkbox].wsf-field.wsf-button:checked + label.wsf-label,
input[type=radio].wsf-field.wsf-button:checked + label.wsf-label {
	background-color: <?php echo $color_primary; ?>;
	border-color: <?php echo $color_primary; ?>;
	color: <?php echo $color_default_inverted; ?>;
}

input[type=checkbox].wsf-field.wsf-color,
input[type=radio].wsf-field.wsf-color {
	height: <?php echo $color_size . $unit_of_measurement; ?>;
	width: <?php echo $color_size . $unit_of_measurement; ?>;
}

input[type=checkbox].wsf-field.wsf-color + label.wsf-label,
input[type=radio].wsf-field.wsf-color + label.wsf-label {
	margin-left: 0;
	padding-left: 0;
	position: relative;
}

input[type=checkbox].wsf-field.wsf-color + label.wsf-label:before,
input[type=radio].wsf-field.wsf-color + label.wsf-label:before {
	display: none;
}

input[type=checkbox].wsf-field.wsf-color + label.wsf-label > span {
<?php if ($form_border) { ?>
	border: <?php echo $form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color; ?>;
<?php } ?>
<?php if ($form_border_radius > 0) { ?>
	border-radius: <?php echo $form_border_radius . $unit_of_measurement; ?>;
<?php } ?>
	cursor: pointer;
	display: inline-block;
	height: <?php echo $color_size . $unit_of_measurement; ?>;
<?php if ($form_transition) { ?>
	transition: border-color <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>, box-shadow <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>;
<?php } ?>
	vertical-align: middle;
	width: <?php echo $color_size . $unit_of_measurement; ?>;
}

input[type=radio].wsf-field.wsf-color + label.wsf-label > span {
<?php if ($form_border) { ?>
	border: <?php echo $form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color; ?>;
<?php } ?>
	border-radius: 50%;
	cursor: pointer;
	display: inline-block;
	height: <?php echo $color_size . $unit_of_measurement; ?>;
<?php if ($form_transition) { ?>
	transition: border-color <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>, box-shadow <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>;
<?php } ?>
	vertical-align: middle;
	width: <?php echo $color_size . $unit_of_measurement; ?>;
}

<?php if ($form_hover) { ?>
input[type=checkbox].wsf-field.wsf-color:enabled:hover + label.wsf-label > span,
input[type=radio].wsf-field.wsf-color:enabled:hover + label.wsf-label > span {
<?php if ($form_hover_background_color != $form_background_color) { ?>
	background-color: <?php echo $form_hover_background_color; ?>;
<?php } ?>
<?php if ($form_hover_border_color != $form_border_color) { ?>
	border-color: <?php echo $form_hover_border_color; ?>
<?php } ?>
}
<?php } ?>

<?php if ($form_focus) { ?>
input[type=checkbox].wsf-field.wsf-color:focus + label.wsf-label > span,
input[type=radio].wsf-field.wsf-color:focus + label.wsf-label > span {
<?php if ($form_focus_background_color != $form_background_color) { ?>
	background-color: <?php echo $form_focus_background_color; ?>;
<?php } ?>
<?php if ($form_focus_border_color != $form_border_color) { ?>
	border-color: <?php echo $form_focus_border_color; ?>;
<?php } ?>
}
<?php } ?>

input[type=checkbox].wsf-field.wsf-color:disabled + label.wsf-label > span,
input[type=radio].wsf-field.wsf-color:disabled + label.wsf-label > span {
	cursor: not-allowed;
	opacity: .5;
}

input[type=checkbox].wsf-field.wsf-color:checked + label.wsf-label > span,
input[type=radio].wsf-field.wsf-color:checked + label.wsf-label > span {
	border-color: <?php echo $form_checked_color; ?>;
	box-shadow: inset 0 0 0 2px <?php echo $color_default_inverted; ?>;
}


.wsf-validated input[type=email].wsf-field:invalid,
.wsf-validated input[type=number].wsf-field:invalid,
.wsf-validated input[type=tel].wsf-field:invalid,
.wsf-validated input[type=text].wsf-field:invalid,
.wsf-validated input[type=search].wsf-field:invalid,
.wsf-validated input[type=url].wsf-field:invalid,
.wsf-validated select.wsf-field:invalid,
.wsf-validated textarea.wsf-field:invalid {
<?php if ($form_error_background_color != $form_background_color) { ?>
	background-color: <?php echo $form_error_background_color; ?>;
<?php } ?>
<?php if ($form_border) { ?>
<?php if ($form_error_border_color != $form_border_color) { ?>
	border-color: <?php echo $form_error_border_color; ?>;
<?php } ?>
<?php } ?>
<?php if ($form_error_border_color != $form_color) { ?>
	color: <?php echo $form_error_color; ?>;
<?php } ?>
}

.wsf-validated input[type=email].wsf-field:-moz-ui-invalid,
.wsf-validated input[type=number].wsf-field:-moz-ui-invalid,
.wsf-validated input[type=tel].wsf-field:-moz-ui-invalid,
.wsf-validated input[type=text].wsf-field:-moz-ui-invalid,
.wsf-validated input[type=search].wsf-field:-moz-ui-invalid,
.wsf-validated input[type=url].wsf-field:-moz-ui-invalid,
.wsf-validated select.wsf-field:-moz-ui-invalid,
.wsf-validated textarea.wsf-field:-moz-ui-invalid {
	box-shadow: none;
}

<?php if ($form_error_color != $form_color) { ?>
select.wsf-field:not([multiple]):not([size]):invalid {
	background-image: url('data:image/svg+xml,<svg%20width%3D"10"%20height%3D"5"%20viewBox%3D"169%20177%2010%205"%20xmlns%3D"http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg"><path%20fill%3D"<?php echo $form_error_color; ?>"%20fill-rule%3D"evenodd"%20d%3D"M174%20182l5-5h-10"%2F><%2Fsvg>');
}
<?php } ?>

<?php if ($form_border) { ?>
<?php if ($form_error_border_color != $form_border_color) { ?>
.wsf-validated input[type=checkbox].wsf-field:invalid + label.wsf-label:before,
.wsf-validated input[type=checkbox].wsf-field.wsf-switch:invalid + label.wsf-label:after,
.wsf-validated input[type=radio].wsf-field:invalid + label.wsf-label:before {
	border-color: <?php echo $form_error_border_color; ?>;
}
<?php } ?>
<?php } ?>

button.wsf-button {
	appearance: none;
	background-color: <?php echo $color_default_lighter; ?>;
<?php if ($form_border) { ?>
	border: <?php echo $form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color; ?>;
<?php } else { ?>
	border: none;
<?php } ?>
<?php if ($form_border_radius > 0) { ?>
	border-radius: <?php echo $form_border_radius . $unit_of_measurement; ?>;
<?php } ?>
	color: <?php echo $form_color; ?>;
	cursor: pointer;
	display: inline-block;
	font-family: <?php echo $font_family; ?>;
	font-size: <?php echo $form_font_size . $unit_of_measurement; ?>;
	font-weight: <?php echo $font_weight; ?>;
	line-height: <?php echo $line_height; ?>;
	padding: <?php echo $form_spacing_vertical . $unit_of_measurement . ' ' . $form_spacing_horizontal . $unit_of_measurement; ?>;
	margin: 0;
	text-align: center;
	text-decoration: none;
	touch-action: manipulation;
<?php if ($form_transition) { ?>
	transition: background-color <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>, border-color <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>;
<?php } ?>
	user-select: none;
	vertical-align: middle;
}

button.wsf-button.wsf-button-full {
	width: 100%;
}

<?php if ($form_hover) { ?>
button.wsf-button:hover {
	background-color: <?php echo WS_Form_Common::hex_darken_percentage($form_border_color, 10); ?>;
	border-color: <?php echo WS_Form_Common::hex_darken_percentage($form_border_color, 10); ?>;
}
<?php } ?>

button.wsf-button:focus,
button.wsf-button:active {
<?php if ($form_focus) { ?>
	background-color: <?php echo WS_Form_Common::hex_darken_percentage($color_default_lighter, 20); ?>;
	border-color: <?php echo WS_Form_Common::hex_darken_percentage($form_border_color, 20); ?>;
<?php } ?>
	outline: 0;
}

button.wsf-button:disabled {
	background-color: <?php echo $color_default_lighter; ?>;
	border-color: <?php echo $form_border_color; ?>;
}

button.wsf-button.wsf-button-primary {
	background-color: <?php echo $color_primary; ?>;
	border-color: <?php echo $color_primary; ?>;
	color: <?php echo $color_default_inverted; ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-primary:hover {
	background-color: <?php echo WS_Form_Common::hex_darken_percentage($color_primary, 10); ?>;
	border-color: <?php echo WS_Form_Common::hex_darken_percentage($color_primary, 10); ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-primary:focus,
button.wsf-button.wsf-button-primary:active {
	background-color: <?php echo WS_Form_Common::hex_darken_percentage($color_primary, 20); ?>;
	border-color: <?php echo WS_Form_Common::hex_darken_percentage($color_primary, 20); ?>;
}
<?php } ?>

button.wsf-button.wsf-button-primary:disabled {
	background-color: <?php echo $color_primary; ?>;
	border-color: <?php echo $color_primary; ?>;
}

button.wsf-button.wsf-button-secondary {
	background-color: <?php echo $color_secondary; ?>;
	border-color: <?php echo $color_secondary; ?>;
	color: <?php echo $color_default_inverted; ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-secondary:hover {
	background-color: <?php echo WS_Form_Common::hex_darken_percentage($color_secondary, 10); ?>;
	border-color: <?php echo WS_Form_Common::hex_darken_percentage($color_secondary, 10); ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-secondary:focus,
button.wsf-button.wsf-button-secondary:active {
	background-color: <?php echo WS_Form_Common::hex_darken_percentage($color_secondary, 20); ?>;
	border-color: <?php echo WS_Form_Common::hex_darken_percentage($color_secondary, 20); ?>;
}
<?php } ?>

button.wsf-button.wsf-button-secondary:disabled {
	background-color: <?php echo $color_secondary; ?>;
	border-color: <?php echo $color_secondary; ?>;
}

button.wsf-button.wsf-button-success {
	background-color: <?php echo $color_success; ?>;
	border-color: <?php echo $color_success; ?>;
	color: <?php echo $color_default_inverted; ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-success:hover {
	background-color: <?php echo WS_Form_Common::hex_darken_percentage($color_success, 10); ?>;
	border-color: <?php echo WS_Form_Common::hex_darken_percentage($color_success, 10); ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-success:focus,
button.wsf-button.wsf-button-success:active {
	background-color: <?php echo WS_Form_Common::hex_darken_percentage($color_success, 20); ?>;
	border-color: <?php echo WS_Form_Common::hex_darken_percentage($color_success, 20); ?>;
}
<?php } ?>

button.wsf-button.wsf-button-success:disabled {
	background-color: <?php echo $color_success; ?>;
	border-color: <?php echo $color_success; ?>;
}

button.wsf-button.wsf-button-information {
	background-color: <?php echo $color_information; ?>;
	border-color: <?php echo $color_information; ?>;
	color: <?php echo $color_default; ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-information:hover {
	background-color: <?php echo WS_Form_Common::hex_darken_percentage($color_information, 10); ?>;
	border-color: <?php echo WS_Form_Common::hex_darken_percentage($color_information, 10); ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-information:focus,
button.wsf-button.wsf-button-information:active {
	background-color: <?php echo WS_Form_Common::hex_darken_percentage($color_information, 20); ?>;
	border-color: <?php echo WS_Form_Common::hex_darken_percentage($color_information, 20); ?>;
}
<?php } ?>

button.wsf-button.wsf-button-information:disabled {
	background-color: <?php echo $color_information; ?>;
	border-color: <?php echo $color_information; ?>;
}

button.wsf-button.wsf-button-warning {
	background-color: <?php echo $color_warning; ?>;
	border-color: <?php echo $color_warning; ?>;
	color: <?php echo $color_default; ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-warning:hover {
	background-color: <?php echo WS_Form_Common::hex_darken_percentage($color_warning, 10); ?>;
	border-color: <?php echo WS_Form_Common::hex_darken_percentage($color_warning, 10); ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-warning:focus,
button.wsf-button.wsf-button-warning:active {
	background-color: <?php echo WS_Form_Common::hex_darken_percentage($color_warning, 20); ?>;
	border-color: <?php echo WS_Form_Common::hex_darken_percentage($color_warning, 20); ?>;
}
<?php } ?>

button.wsf-button.wsf-button-warning:disabled {
	background-color: <?php echo $color_warning; ?>;
	border-color: <?php echo $color_warning; ?>;
}

button.wsf-button.wsf-button-danger {
	background-color: <?php echo $color_danger; ?>;
	border-color: <?php echo $color_danger; ?>;
	color: <?php echo $color_default_inverted; ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-danger:hover {
	background-color: <?php echo WS_Form_Common::hex_darken_percentage($color_danger, 10); ?>;
	border-color: <?php echo WS_Form_Common::hex_darken_percentage($color_danger, 10); ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-danger:focus,
button.wsf-button.wsf-button-danger:active {
	background-color: <?php echo WS_Form_Common::hex_darken_percentage($color_danger, 20); ?>;
	border-color: <?php echo WS_Form_Common::hex_darken_percentage($color_danger, 20); ?>;
}
<?php } ?>

button.wsf-button.wsf-button-danger:disabled {
	background-color: <?php echo $color_danger; ?>;
	border-color: <?php echo $color_danger; ?>;
}

<?php if ($form_border) { ?>
button.wsf-button.wsf-button-inverted {
	background-color: <?php echo $form_background_color; ?>;
	border-color: <?php echo $form_border_color; ?>;
	color: <?php echo $form_color; ?>;
<?php if ($form_transition) { ?>
	transition: background-color <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>, color <?php echo $form_transition_speed . 'ms ' . $form_transition_timing_function; ?>;
<?php } ?>
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-inverted:hover {
	background-color: <?php echo $color_default_lighter; ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-inverted:focus,
button.wsf-button.wsf-button-inverted:active {
	background-color: <?php echo $color_default_lighter; ?>;
}
<?php } ?>

button.wsf-button.wsf-button-inverted:disabled {
	background-color: <?php echo $form_background_color; ?>;
}

button.wsf-button.wsf-button-inverted.wsf-button-primary {
	border-color: <?php echo $color_primary; ?>;
	color: <?php echo $color_primary; ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-primary:hover {
	background-color: <?php echo $color_primary; ?>;
	border-color: <?php echo $color_primary; ?>;
	color: <?php echo $color_default_inverted; ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-primary:focus {
	background-color: <?php echo $color_primary; ?>;
	border-color: <?php echo $color_primary; ?>;
	color: <?php echo $color_default_inverted; ?>;
}
<?php } ?>

button.wsf-button.wsf-button-inverted.wsf-button-primary:disabled {
	background-color: <?php echo $form_background_color; ?>;
	border-color: <?php echo $color_primary; ?>;
	color: <?php echo $color_primary; ?>;
}

button.wsf-button.wsf-button-inverted.wsf-button-secondary {
	border-color: <?php echo $color_secondary; ?>;
	color: <?php echo $color_secondary; ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-secondary:hover {
	background-color: <?php echo $color_secondary; ?>;
	border-color: <?php echo $color_secondary; ?>;
	color: <?php echo $color_default_inverted; ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-secondary:focus {
	background-color: <?php echo $color_secondary; ?>;
	border-color: <?php echo $color_secondary; ?>;
	color: <?php echo $color_default_inverted; ?>;
}
<?php } ?>

button.wsf-button.wsf-button-inverted.wsf-button-secondary:disabled {
	background-color: <?php echo $form_background_color; ?>;
	border-color: <?php echo $color_secondary; ?>;
	color: <?php echo $color_secondary; ?>;
}

button.wsf-button.wsf-button-inverted.wsf-button-success {
	border-color: <?php echo $color_success; ?>;
	color: <?php echo $color_success; ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-success:hover {
	background-color: <?php echo $color_success; ?>;
	border-color: <?php echo $color_success; ?>;
	color: <?php echo $color_default_inverted; ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-success:focus {
	background-color: <?php echo $color_success; ?>;
	border-color: <?php echo $color_success; ?>;
	color: <?php echo $color_default_inverted; ?>;
}
<?php } ?>

button.wsf-button.wsf-button-inverted.wsf-button-success:disabled {
	background-color: <?php echo $form_background_color; ?>;
	border-color: <?php echo $color_success; ?>;
	color: <?php echo $color_success; ?>;
}

button.wsf-button.wsf-button-inverted.wsf-button-information {
	border-color: <?php echo $color_information; ?>;
	color: <?php echo $color_information; ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-information:hover {
	background-color: <?php echo $color_information; ?>;
	border-color: <?php echo $color_information; ?>;
	color: <?php echo $color_default_inverted; ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-information:focus {
	background-color: <?php echo $color_information; ?>;
	border-color: <?php echo $color_information; ?>;
	color: <?php echo $color_default_inverted; ?>;
}
<?php } ?>

button.wsf-button.wsf-button-inverted.wsf-button-information:disabled {
	background-color: <?php echo $form_background_color; ?>;
	border-color: <?php echo $color_information; ?>;
	color: <?php echo $color_information; ?>;
}

button.wsf-button.wsf-button-inverted.wsf-button-warning {
	border-color: <?php echo $color_warning; ?>;
	color: <?php echo $color_warning; ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-warning:hover {
	background-color: <?php echo $color_warning; ?>;
	border-color: <?php echo $color_warning; ?>;
	color: <?php echo $color_default_inverted; ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-warning:focus {
	background-color: <?php echo $color_warning; ?>;
	border-color: <?php echo $color_warning; ?>;
	color: <?php echo $color_default_inverted; ?>;
}
<?php } ?>

button.wsf-button.wsf-button-inverted.wsf-button-warning:disabled {
	background-color: <?php echo $form_background_color; ?>;
	border-color: <?php echo $color_warning; ?>;
	color: <?php echo $color_warning; ?>;
}

button.wsf-button.wsf-button-inverted.wsf-button-danger {
	border-color: <?php echo $color_danger; ?>;
	color: <?php echo $color_danger; ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-danger:hover {
	background-color: <?php echo $color_danger; ?>;
	border-color: <?php echo $color_danger; ?>;
	color: <?php echo $color_default_inverted; ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-danger:focus {
	background-color: <?php echo $color_danger; ?>;
	border-color: <?php echo $color_danger; ?>;
	color: <?php echo $color_default_inverted; ?>;
}
<?php } ?>

button.wsf-button.wsf-button-inverted.wsf-button-danger:disabled {
	background-color: <?php echo $form_background_color; ?>;
	border-color: <?php echo $color_danger; ?>;
	color: <?php echo $color_danger; ?>;
}
<?php } ?>

button.wsf-button::-moz-focus-inner {
	border: 0;
	margin: 0;
	padding: 0;
}

button.wsf-button:disabled {
	cursor: not-allowed;
	opacity: .5;
	transition: none;
}

.wsf-form-post-lock-progress button[type="submit"].wsf-button {
	cursor: progress;
}

.wsf-alert {
	background-color: <?php echo $color_default_lightest; ?>;
<?php if ($form_border) { ?>
	border: <?php echo $form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color; ?>;
<?php } ?>
<?php if ($form_border_radius > 0) { ?>
	border-radius: <?php echo $form_border_radius . $unit_of_measurement; ?>;
<?php } ?>
	font-family: <?php echo $font_family; ?>;
	font-size: <?php echo $form_font_size . $unit_of_measurement; ?>;
	font-weight: <?php echo $font_weight; ?>;
	line-height: <?php echo $line_height; ?>;
	padding: <?php echo $spacing_small . $unit_of_measurement; ?>;
	margin-bottom: <?php echo $grid_gutter . $unit_of_measurement; ?>;
}

.wsf-alert > :last-child {
	margin-bottom: 0;
}

.wsf-alert-success {
	background-color: <?php echo WS_Form_Common::hex_lighten_percentage($color_success, 85); ?>;
<?php if ($form_border) { ?>
	border-color: <?php echo WS_Form_Common::hex_lighten_percentage($color_success, 40); ?>;
<?php } ?>
}

.wsf-alert-information {
	background-color: <?php echo WS_Form_Common::hex_lighten_percentage($color_information, 85); ?>;
<?php if ($form_border) { ?>
	border-color: <?php echo WS_Form_Common::hex_lighten_percentage($color_information, 40); ?>;
<?php } ?>
}

.wsf-alert-warning {
	background-color: <?php echo WS_Form_Common::hex_lighten_percentage($color_warning, 85); ?>;
<?php if ($form_border) { ?>
	border-color: <?php echo WS_Form_Common::hex_lighten_percentage($color_warning, 40); ?>;
<?php } ?>
}

.wsf-alert-danger {
	background-color: <?php echo WS_Form_Common::hex_lighten_percentage($color_danger, 85); ?>;
<?php if ($form_border) { ?>
	border-color: <?php echo WS_Form_Common::hex_lighten_percentage($color_danger, 40); ?>;
<?php } ?>
}

.wsf-text-primary {
	color: <?php echo $color_primary; ?>;
}

.wsf-text-secondary {
	color: <?php echo $color_secondary; ?>;
}

.wsf-text-success {
	color: <?php echo $color_success; ?>;
}

.wsf-text-information {
	color: <?php echo $color_information; ?>;
}

.wsf-text-warning {
	color: <?php echo $color_warning; ?>;
}

.wsf-text-danger {
	color: <?php echo $color_danger; ?>;
}
<?php

	$cheese_background_image = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayI+PGRlZnM+PHBhdGggaWQ9ImEiIGQ9Ik0wIDBoMTAwdjEwMEgweiIvPjxsaW5lYXJHcmFkaWVudCB4MT0iMTMuMiUiIHkxPSIxNC42JSIgeDI9Ijg1LjUlIiB5Mj0iODguMiUiIGlkPSJkIj48c3RvcCBzdG9wLWNvbG9yPSIjRUFCNzU4IiBvZmZzZXQ9IjAlIi8+PHN0b3Agc3RvcC1jb2xvcj0iI0ZFRTJBMyIgb2Zmc2V0PSIxMDAlIi8+PC9saW5lYXJHcmFkaWVudD48bGluZWFyR3JhZGllbnQgeDE9IjE0LjglIiB5MT0iMTMuMSUiIHgyPSI4Ni40JSIgeTI9Ijg1LjklIiBpZD0iYyI+PHN0b3Agc3RvcC1jb2xvcj0iI0ZBRTNBMCIgb2Zmc2V0PSIwJSIvPjxzdG9wIHN0b3AtY29sb3I9IiNGRUYzQ0QiIG9mZnNldD0iMTAwJSIvPjwvbGluZWFyR3JhZGllbnQ+PGxpbmVhckdyYWRpZW50IHgxPSI2LjQlIiB5MT0iMTIuOSUiIHgyPSIyOC4yJSIgeTI9IjI2LjIlIiBpZD0iZSI+PHN0b3Agc3RvcC1jb2xvcj0iI0UxQUIzRiIgb2Zmc2V0PSIwJSIvPjxzdG9wIHN0b3AtY29sb3I9IiNGQUUzQTAiIG9mZnNldD0iMTAwJSIvPjwvbGluZWFyR3JhZGllbnQ+PGxpbmVhckdyYWRpZW50IHgxPSI2LjQlIiB5MT0iMTIuOSUiIHgyPSIyMC41JSIgeTI9IjQyJSIgaWQ9ImYiPjxzdG9wIHN0b3AtY29sb3I9IiNFMUFCM0YiIG9mZnNldD0iMCUiLz48c3RvcCBzdG9wLWNvbG9yPSIjRkFFM0EwIiBvZmZzZXQ9IjEwMCUiLz48L2xpbmVhckdyYWRpZW50PjxsaW5lYXJHcmFkaWVudCB4MT0iMTYuMSUiIHkxPSIxNC40JSIgeDI9Ijg2LjQlIiB5Mj0iODUuOSUiIGlkPSJnIj48c3RvcCBzdG9wLWNvbG9yPSIjRkFFM0EwIiBvZmZzZXQ9IjAlIi8+PHN0b3Agc3RvcC1jb2xvcj0iI0ZFRjNDRCIgb2Zmc2V0PSIxMDAlIi8+PC9saW5lYXJHcmFkaWVudD48L2RlZnM+PGcgZmlsbD0ibm9uZSIgZmlsbC1ydWxlPSJldmVub2RkIj48bWFzayBpZD0iYiIgZmlsbD0iI2ZmZiI+PHVzZSB4bGluazpocmVmPSIjYSIvPjwvbWFzaz48cGF0aCBmaWxsPSIjRkFFM0EwIiBtYXNrPSJ1cmwoI2IpIiBkPSJNMCAwaDEwMHYxMDBIMHoiLz48Y2lyY2xlIHN0cm9rZT0idXJsKCNjKSIgZmlsbD0idXJsKCNkKSIgbWFzaz0idXJsKCNiKSIgY3g9IjM3LjUiIGN5PSI2Mi41IiByPSI0LjUiLz48Y2lyY2xlIHN0cm9rZT0idXJsKCNjKSIgZmlsbD0idXJsKCNkKSIgbWFzaz0idXJsKCNiKSIgY3g9IjM3LjUiIGN5PSI2Mi41IiByPSI0LjUiLz48Y2lyY2xlIHN0cm9rZT0idXJsKCNjKSIgZmlsbD0idXJsKCNkKSIgbWFzaz0idXJsKCNiKSIgY3g9IjIyLjUiIGN5PSI2MC41IiByPSI1LjUiLz48Y2lyY2xlIHN0cm9rZT0idXJsKCNjKSIgZmlsbD0idXJsKCNkKSIgbWFzaz0idXJsKCNiKSIgY3g9IjE0LjUiIGN5PSI1MC41IiByPSIzLjUiLz48Y2lyY2xlIHN0cm9rZT0idXJsKCNjKSIgZmlsbD0idXJsKCNkKSIgbWFzaz0idXJsKCNiKSIgY3g9IjI1LjUiIGN5PSI0MC41IiByPSI1LjUiLz48Y2lyY2xlIHN0cm9rZT0idXJsKCNjKSIgZmlsbD0idXJsKCNkKSIgbWFzaz0idXJsKCNiKSIgY3g9IjgiIGN5PSI0MiIgcj0iNCIvPjxjaXJjbGUgc3Ryb2tlPSJ1cmwoI2MpIiBmaWxsPSJ1cmwoI2QpIiBtYXNrPSJ1cmwoI2IpIiBjeD0iMTEuNSIgY3k9IjY2LjUiIHI9IjQuNSIvPjxjaXJjbGUgc3Ryb2tlPSJ1cmwoI2MpIiBmaWxsPSJ1cmwoI2QpIiBtYXNrPSJ1cmwoI2IpIiBjeD0iODUuNSIgY3k9IjMzLjUiIHI9IjUuNSIvPjxjaXJjbGUgc3Ryb2tlPSJ1cmwoI2MpIiBmaWxsPSJ1cmwoI2QpIiBtYXNrPSJ1cmwoI2IpIiBjeD0iMjAiIGN5PSI4MSIgcj0iOSIvPjxjaXJjbGUgc3Ryb2tlPSJ1cmwoI2MpIiBmaWxsPSJ1cmwoI2QpIiBtYXNrPSJ1cmwoI2IpIiBjeD0iMzQuNSIgY3k9IjgxLjUiIHI9IjIuNSIvPjxjaXJjbGUgc3Ryb2tlPSJ1cmwoI2MpIiBmaWxsPSJ1cmwoI2QpIiBtYXNrPSJ1cmwoI2IpIiBjeD0iNy41IiBjeT0iOTIuNSIgcj0iMi41Ii8+PGNpcmNsZSBzdHJva2U9InVybCgjYykiIGZpbGw9InVybCgjZCkiIG1hc2s9InVybCgjYikiIGN4PSI1MCIgY3k9IjcyIiByPSI4Ii8+PGNpcmNsZSBzdHJva2U9InVybCgjYykiIGZpbGw9InVybCgjZCkiIG1hc2s9InVybCgjYikiIGN4PSIxNSIgY3k9IjIzIiByPSIxMyIvPjxlbGxpcHNlIHN0cm9rZT0idXJsKCNlKSIgbWFzaz0idXJsKCNiKSIgY3g9IjEwMCIgY3k9IjU1IiByeD0iMTIiIHJ5PSIxMCIvPjxjaXJjbGUgc3Ryb2tlPSJ1cmwoI2YpIiBtYXNrPSJ1cmwoI2IpIiBjeD0iMzgiIGN5PSI5OCIgcj0iMTAiLz48Y2lyY2xlIHN0cm9rZT0idXJsKCNnKSIgZmlsbD0idXJsKCNkKSIgbWFzaz0idXJsKCNiKSIgY3g9IjkxIiBjeT0iOSIgcj0iOCIvPjxjaXJjbGUgc3Ryb2tlPSJ1cmwoI2MpIiBmaWxsPSJ1cmwoI2QpIiBtYXNrPSJ1cmwoI2IpIiBjeD0iNzkiIGN5PSI1NCIgcj0iNCIvPjxjaXJjbGUgc3Ryb2tlPSJ1cmwoI2MpIiBmaWxsPSJ1cmwoI2QpIiBtYXNrPSJ1cmwoI2IpIiBjeD0iODYiIGN5PSI0NiIgcj0iMiIvPjxjaXJjbGUgc3Ryb2tlPSJ1cmwoI2MpIiBmaWxsPSJ1cmwoI2QpIiBtYXNrPSJ1cmwoI2IpIiBjeD0iNjgiIGN5PSI5NiIgcj0iMyIvPjxjaXJjbGUgc3Ryb2tlPSJ1cmwoI2MpIiBmaWxsPSJ1cmwoI2QpIiBtYXNrPSJ1cmwoI2IpIiBjeD0iNzAuNSIgY3k9IjcwLjUiIHI9IjUuNSIvPjxjaXJjbGUgc3Ryb2tlPSJ1cmwoI2MpIiBmaWxsPSJ1cmwoI2QpIiBtYXNrPSJ1cmwoI2IpIiBjeD0iNC41IiBjeT0iNzYuNSIgcj0iMi41Ii8+PGNpcmNsZSBzdHJva2U9InVybCgjYykiIGZpbGw9InVybCgjZCkiIG1hc2s9InVybCgjYikiIGN4PSIyMC41IiBjeT0iNS41IiByPSIzLjUiLz48Y2lyY2xlIHN0cm9rZT0idXJsKCNjKSIgZmlsbD0idXJsKCNkKSIgbWFzaz0idXJsKCNiKSIgY3g9IjQuNSIgY3k9IjguNSIgcj0iMi41Ii8+PGNpcmNsZSBzdHJva2U9InVybCgjYykiIGZpbGw9InVybCgjZCkiIG1hc2s9InVybCgjYikiIGN4PSI0NSIgY3k9IjUwIiByPSI2Ii8+PGNpcmNsZSBzdHJva2U9InVybCgjYykiIGZpbGw9InVybCgjZCkiIG1hc2s9InVybCgjYikiIGN4PSI4MS41IiBjeT0iNjkuNSIgcj0iMS41Ii8+PGNpcmNsZSBzdHJva2U9InVybCgjYykiIGZpbGw9InVybCgjZCkiIG1hc2s9InVybCgjYikiIGN4PSI0Ni41IiBjeT0iMzYuNSIgcj0iMS41Ii8+PGNpcmNsZSBzdHJva2U9InVybCgjYykiIGZpbGw9InVybCgjZCkiIG1hc2s9InVybCgjYikiIGN4PSIzNCIgY3k9IjMzIiByPSIyIi8+PGNpcmNsZSBzdHJva2U9InVybCgjYykiIGZpbGw9InVybCgjZCkiIG1hc2s9InVybCgjYikiIGN4PSIzOC41IiBjeT0iNDIuNSIgcj0iMS41Ii8+PGNpcmNsZSBzdHJva2U9InVybCgjYykiIGZpbGw9InVybCgjZCkiIG1hc2s9InVybCgjYikiIGN4PSI2NiIgY3k9Ijg0IiByPSI0Ii8+PGNpcmNsZSBzdHJva2U9InVybCgjYykiIGZpbGw9InVybCgjZCkiIG1hc2s9InVybCgjYikiIGN4PSI3NS41IiBjeT0iMjAuNSIgcj0iNS41Ii8+PGNpcmNsZSBzdHJva2U9InVybCgjYykiIGZpbGw9InVybCgjZCkiIG1hc2s9InVybCgjYikiIGN4PSI2MC41IiBjeT0iMTguNSIgcj0iNC41Ii8+PGNpcmNsZSBzdHJva2U9InVybCgjYykiIGZpbGw9InVybCgjZCkiIG1hc2s9InVybCgjYikiIGN4PSI1Ni41IiBjeT0iNi41IiByPSIzLjUiLz48Y2lyY2xlIHN0cm9rZT0idXJsKCNjKSIgZmlsbD0idXJsKCNkKSIgbWFzaz0idXJsKCNiKSIgY3g9Ijc2LjUiIGN5PSIzLjUiIHI9IjIuNSIvPjxjaXJjbGUgc3Ryb2tlPSJ1cmwoI2MpIiBmaWxsPSJ1cmwoI2QpIiBtYXNrPSJ1cmwoI2IpIiBjeD0iNjEuNSIgY3k9IjM3LjUiIHI9IjEyLjUiLz48Y2lyY2xlIHN0cm9rZT0idXJsKCNjKSIgZmlsbD0idXJsKCNkKSIgbWFzaz0idXJsKCNiKSIgY3g9Ijg0LjUiIGN5PSI4My41IiByPSIxMS41Ii8+PGNpcmNsZSBzdHJva2U9InVybCgjYykiIGZpbGw9InVybCgjZCkiIG1hc2s9InVybCgjYikiIGN4PSI2MS41IiBjeT0iMzcuNSIgcj0iMTIuNSIvPjxjaXJjbGUgc3Ryb2tlPSJ1cmwoI2MpIiBmaWxsLW9wYWNpdHk9Ii44IiBmaWxsPSJ1cmwoI2QpIiBtYXNrPSJ1cmwoI2IpIiBjeD0iNDEuNSIgY3k9IjIwLjUiIHI9IjkuNSIvPjxjaXJjbGUgc3Ryb2tlPSJ1cmwoI2MpIiBmaWxsPSJ1cmwoI2QpIiBtYXNrPSJ1cmwoI2IpIiBjeD0iOTUiIGN5PSIyNCIgcj0iNCIvPjwvZz48L3N2Zz4=';
?>
input[type=email].wsf-field.cheese,
input[type=number].wsf-field.cheese,
input[type=tel].wsf-field.cheese,
input[type=text].wsf-field.cheese,
input[type=search].wsf-field.cheese,
input[type=url].wsf-field.cheese,
select.wsf-field.cheese,
textarea.wsf-field.cheese,
input[type=checkbox].wsf-field.cheese + label.wsf-label:before,
input[type=radio].wsf-field.cheese + label.wsf-label:before {
	background-image: url(<?php echo $cheese_background_image; ?>);
	border-color: orange;
}
<?php
		}

		public function get_email() {

			$css = '	svg { max-width: 100%; }

	h1, h2, h3, h4 {

		font-family: sans-serif;
		font-weight: bold;
		margin: 0;
		margin-bottom: 10px;"
	}
	h1 {
		font-size: 24px !important;
	}
	h2 {
		font-size: 22px !important;
	}
	h3 {
		font-size: 20px !important;
	}
	h4 {
		font-size: 18px !important;
	}
	p,li,td,span,a {

		font-family: sans-serif;
		font-size: 14px;
		font-weight: normal;
		margin: 0;
		margin-bottom: 10px;"
 	}

	@media only screen and (max-width: 620px) {

		p,li,td,span,a {
			font-size: 16px;
	 	}
		.wrapper {
			padding: 10px !important;
		}
		.content {
			padding: 0 !important;
		}
		.container {
			padding: 0 !important;
			width: 100% !important;
		}
		.main {
			border-left-width: 0 !important;
			border-radius: 0 !important;
			border-right-width: 0 !important;
		}
	}
			';

			return $css;
		}
	}
