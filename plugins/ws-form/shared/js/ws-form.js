(function($) {

	'use strict';

	$.WS_Form = function(atts) {

		// Global this (Only for admin, public side needs multiple instances)
		$.WS_Form.this = this;

		// Admin sizing
		this.admin_size_init();

		// Admin?
		this.is_admin = this.set_is_admin();

		// User roles
		this.user_roles = ws_form_settings.user_roles;

		// Form interface built?
		this.form_interface = false;

		// Group data cache
		this.group_data_cache = [];

		// Section data cache
		this.section_data_cache = [];

		// Field data cache
		this.field_data_cache = [];

		// Field data cache
		this.action_data_cache = [];

		// Invalid feedback cache
		this.invalid_feedback_cache = [];

		// Object cache
		this.object_cache = [];
		this.object_cache['condition'] = [];
		this.object_cache['then'] = [];
		this.object_cache['else'] = [];

		// Actions
		this.action = false;

		// New object data (for reverting fied data back to an older state)
		this.object_data_scratch = false;

		// Form history
		this.form_history = [];
		this.history_index = 0;

		// Column resizing
		this.framework = '';
		this.column_resize_obj = false;
		this.column_size_value = 0;
		this.column_size_value_old = 0;
		this.offset_value = 0;
		this.offset_value_old = 0;

		// Draggable
		this.dragged_field = null;
		this.section_field_being_dragged = false;

		// Sortable
		this.next_sibling_id_old = null;
		this.section_id_old = null;
		this.group_id_old = null;
		this.section_repeatable_dragged = false;

		// Checksum
		this.checksum = false;
		this.checksum_setTimeout = false;
		this.published_checksum = '';


		// Key down events
		this.keydown = [];

		// Page component load timeout
		this.timeout_interval = 100;

		// API
		this.api_call_queue = [];
		this.api_call_queue_running = false;

		// Number to word
		this.number_to_word = ['nought','one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve','thirteen','fourteen','fifteen','sixteen','seventeen','eighteen','nineteen','twenty'];

		// Old object label
		this.label_old = '';

		// Submit data
		this.submit = false;
		this.action_js = [];
		this.form_draft = false;

		// Populate
		this.submit_auto_populate = false;

		// Prefixes
		this.form_id_prefix = 'wsf-';
		this.field_name_prefix = ws_form_settings.field_prefix;

		// Meta key options
		this.meta_key_options_cache = [];

		// Options cache
		this.options_action_objects = [];
		this.options_action_cache = [];

		// Sidebars
		this.sidebar_conditions = [];
		this.sidebar_conditions_events_added = false;
		this.sidebar_expanded_obj = false;

		// Devices
		this.touch_device = ('ontouchend' in document);

		// Hash
		this.hash = '';

		// Form locking
		this.form_post_locked = false;

		// Required fields bypass
		this.field_required_bypass = [];

		// Real time form validation
		this.form_valid = false;
		this.form_valid_old = null;
		this.form_validation_real_time_hooks = [];


		// Custom action URL
		this.form_action_custom = false;
		this.form_ajax = true;

		// Object focus
		this.object_focus = false;

		// Section ID for field type click
		this.field_type_click_drag_check = false;

		// Password strength meter
		this.password_strength_status = 0;

		// Cascade selects
		this.cascade_select_cache = [];

		// Search AJAX cache
		this.select_ajax_cache = [];
	}

	// Render
	$.WS_Form.prototype.render = function(atts) {

		var ws_this = this;

		// Check attributes
		if(typeof atts === 'undefined') { this.error(this.language('error_attributes')); }
		if(typeof atts.obj === 'undefined') { this.error(this.language('error_attributes_obj')); }
		if(typeof atts.form_id === 'undefined') { this.error(this.language('error_attributes_form_id')); }

		// Form canvas (Could be something other than form if element defined)
		this.form_canvas_obj = atts.obj instanceof $ ? atts.obj : $(atts.obj);

		// Form object ID
		this.form_obj_id = this.form_canvas_obj.attr('id');

		// Form ID
		this.form_id = atts.form_id;

		// Form instance
		this.form_instance_id = this.form_canvas_obj.attr('data-instance-id');

		// Form ID prefix
		this.form_id_prefix = this.is_admin ? 'wsf-' : 'wsf-' + this.form_instance_id + '-';
		this.form_id_prefix_function = this.is_admin ? 'wsf_' : 'wsf_' + this.form_instance_id + '_';

		// Form object (Form tag)
		this.form_obj = this.form_canvas_obj.closest('form');
		if(this.form_obj.length) {

			this.form_obj.attr('novalidate', '');

		} else {

			this.form_obj = this.form_canvas_obj;
		}

		// Move wsf-form class to outer form tag
		if(this.form_obj[0] != this.form_canvas_obj[0]) {

			if(!this.form_obj.hasClass('wsf-form')) { this.form_obj.addClass('wsf-form'); }
			if(this.form_canvas_obj.hasClass('wsf-form')) { this.form_canvas_obj.removeClass('wsf-form'); }
		}

		// Get configuration
		this.get_configuration(function() {

			// Get form
			ws_this.get_form(function() {

				// Initialize
				ws_this.init();
			});
		});
	}

	// Configuration objects
	$.WS_Form.configured = false;
	$.WS_Form.css_rendered = false;
	$.WS_Form.settings_plugin;
	$.WS_Form.settings_form = null;
	$.WS_Form.frameworks;
	$.WS_Form.parse_variables;
	$.WS_Form.parse_variable_help;
	$.WS_Form.actions;
	$.WS_Form.field_types;
	$.WS_Form.field_type_cache = [];
	$.WS_Form.file_types;
	$.WS_Form.meta_keys;
	$.WS_Form.meta_keys_required_setting = [];
	$.WS_Form.breakpoints;

	// Admin sizing - Init
	$.WS_Form.prototype.admin_size_init = function() {

		this.admin_size();

		$(window).on('resize', function() {

			$.WS_Form.this.admin_size();
		});
	}

	// Admin sizing - Init
	$.WS_Form.prototype.admin_size = function() {

		// Admin resize
		$.WS_Form.this.admin_size_loader();
		$.WS_Form.this.admin_size_sidebar();
		$.WS_Form.this.admin_size_breakpoint();
	}

	// Admin sizing - Loader
	$.WS_Form.prototype.admin_size_loader = function() {

		if(!$('#wsf-loader').length) { return; }

		// Loader top
		var admin_bar_height = (window.matchMedia('(min-width: 601px)').matches) ? ($('#wpadminbar:visible').length ? $('#wpadminbar').height() : 0) : 0;
		$('#wsf-loader').css({'top': admin_bar_height + 'px'});

		// Loader left
		var admin_menu_width = $('#adminmenu:visible').length ? $('#adminmenu').width() : 0;
		$('#wsf-loader').css({'left': admin_menu_width + 'px'});

		// Loader width
		var window_width = $(window).width();
		var sidebar_width = (window.matchMedia('(min-width: 851px)').matches) ? ($('.wsf-sidebar.wsf-sidebar-open').length ? $('.wsf-sidebar.wsf-sidebar-open').first().width() : 0) : 0;
		var breakpoint_selector_width = window_width - (admin_menu_width + sidebar_width);
		$('#wsf-loader').css({'width': breakpoint_selector_width + 'px'});
	}

	// Admin sizing - Sidebar
	$.WS_Form.prototype.admin_size_sidebar = function() {

		if(!$('.wsf-sidebar').length) { return; }

		// Sidebar top
		var admin_bar_height = (window.matchMedia('(min-width: 601px)').matches) ? ($('#wpadminbar:visible').length ? $('#wpadminbar').height() : 0) : 0;
		$('.wsf-sidebar').css({'top': admin_bar_height + 'px'});

		// Sidebar height
		var window_height = $(window).height();
		var sidebar_height = (window.matchMedia('(min-width:601px)').matches) ? (window_height - admin_bar_height) : window_height;
		$('.wsf-sidebar').css({'height': sidebar_height + 'px'});
	}

	// Admin sizing - Breakpoint
	$.WS_Form.prototype.admin_size_breakpoint = function() {
		
		if(!$('#wsf-breakpoints').length) { return ; }

		// Breakpoint selector left
		var admin_menu_width = $('#adminmenu:visible').length ? $('#adminmenu').width() : 0;
		$('#wsf-breakpoints').css({'left': admin_menu_width + 'px'});

		// Breakpoint selector width
		var window_width = $(window).width();
		var sidebar_width = (window.matchMedia('(min-width: 851px)').matches) ? $('.wsf-sidebar').first().width() : 0;
		var breakpoint_selector_padding = 40;
		var breakpoint_selector_width = window_width - (admin_menu_width + sidebar_width + breakpoint_selector_padding);
		$('#wsf-breakpoints').css({'width': breakpoint_selector_width + 'px'});
	}

	// Get configuration
	$.WS_Form.prototype.get_configuration = function(success_callback, force, bypass_loader) {

		if(typeof(force) === 'undefined') { var force = false; }
		if(typeof(bypass_loader) === 'undefined') { var bypass_loader = false; }

		// Clear caches
		this.options_action_cache = [];

		// Loader on
		if(!bypass_loader) { this.loader_on(); }

		if(!$.WS_Form.configured || force) {

			if(typeof(wsf_form_json_config) === 'undefined') {

				// Get configuration via AJAX
				var ws_this = this;
				this.api_call('config', 'GET', false, function(response) {

					ws_this.set_configuration(response.data);

					if(typeof(success_callback) === 'function') { success_callback(); }

				}, false, bypass_loader);

			} else {

				// Get configuration from dom
				this.set_configuration(wsf_form_json_config);

				if(typeof(success_callback) === 'function') { success_callback(); }
			}

		} else {

			// Get form without configuration (Configuration already loaded)
			if(typeof(success_callback) === 'function') { success_callback(); }
		}
	}

	// Set configuration
	$.WS_Form.prototype.set_configuration = function(config) {

		// Store configuration
		$.WS_Form.settings_plugin = config.settings_plugin;
		$.WS_Form.settings_form = config.settings_form;
		$.WS_Form.frameworks = config.frameworks;
		$.WS_Form.field_types = config.field_types;
		$.WS_Form.file_types = config.file_types;
		$.WS_Form.meta_keys = config.meta_keys;
		$.WS_Form.parse_variables = config.parse_variables;
		$.WS_Form.parse_variable_help = config.parse_variable_help;
		$.WS_Form.actions = config.actions;

		// Build field type cache
		this.field_type_cache_build();

		// Set that WS Form is configured
		$.WS_Form.configured = true;
	}

	// Get configuration
	$.WS_Form.prototype.get_form = function(success_callback) {

		// Start rendering
		if(this.form_id == 0) { this.error(this.language('error_form_id')); }

		// Set form data-id attribute
		$('#' + this.form_obj_id).attr('data-id', this.form_id);

		if(
			(typeof(wsf_form_json) === 'undefined') ||
			(typeof(wsf_form_json[this.form_id]) === 'undefined')
		) {

			// Get form from API
			var ws_this = this;
			this.api_call('form/' + this.form_id + '/full', 'GET', false, function(response) {

				// Store form data
				ws_this.form = response.form;

				// Build data cache
				ws_this.data_cache_build();

				// Success callback
				if(typeof(success_callback) === 'function') { success_callback(); }

				// Loader off
				ws_this.loader_off();
			});

		} else {

			// Get form from dom
			this.form = wsf_form_json[this.form_id];

			// Build data cache
			this.data_cache_build();

			// Success callback
			if(typeof(success_callback) === 'function') { success_callback(); }

			// Loader off
			this.loader_off();
		}
	}

	// Build form
	$.WS_Form.prototype.form_build = function() {

		// Timer - Start
		this.timer_start = new Date();

		// Form html
		var form_html = this.get_form_html(this.form);

		// Push form_html to form_obj
		this.form_canvas_obj.html(form_html);

		// Add container class
		if(!this.is_admin) {

			var class_form_wrapper = this.get_object_meta_value(this.form, 'class_form_wrapper', '');
			if(class_form_wrapper != '') {

				var form_class = this.form_canvas_obj.attr('class');
				form_class += ' '  + $.trim(class_form_wrapper);
				this.form_canvas_obj.attr('class', form_class);
			}
		}

		// Render form
		this.form_render();
	
		// Timer - Duration
		this.timer_duration = new Date() - this.timer_start;

	}

	// Get form HTML
	$.WS_Form.prototype.get_form_html = function(form) {

		// Form
		if(typeof form === 'undefined') { return ''; }
		if(typeof form.groups === 'undefined') { return ''; }

		// Get current framework
		var framework_type = this.is_admin ? ws_form_settings.framework_admin : $.WS_Form.settings_plugin.framework;
		var framework = $.WS_Form.frameworks.types[framework_type];
		var framework_form = framework['form'][this.is_admin ? 'admin' : 'public'];

		// Label
		var form_label = this.html_encode(form.label);
		var label_render = !this.is_admin && (this.get_object_meta_value(form, 'label_render', 'on') == 'on') ? true : false;

		if(label_render) {

			var label_mask_form = !this.is_admin ? this.get_object_meta_value(this.form, 'label_mask_form', '') : '';
			var mask = (label_mask_form != '') ? label_mask_form : (typeof framework_form['mask_label'] !== 'undefined') ? framework_form['mask_label'] : '';
			var mask_values = {'label': form_label};
			var label_html_parsed = this.mask_parse(mask, mask_values);

		} else {

			var label_html_parsed = '';
		}

		// Tabs
		var form_html = this.get_tabs_html(form.groups);

		// Groups
		form_html += this.get_groups_html(form.groups);

		// Parse wrapper form
		var mask = framework_form['mask_single'];
		var mask_values = {'form': form_html, 'id': this.form_id_prefix + 'tabs', 'label': label_html_parsed};
		var form_html_parsed = this.comment_html('Form: ' + form_label) + this.mask_parse(mask, mask_values) + this.comment_html('Form: ' + form_label, true);

		return form_html_parsed;
	}

	// Get tabs HTML
	$.WS_Form.prototype.get_tabs_html = function(groups) {

		// No tabs if there is only 1 group and we are not in admin
		if(groups.length == 1 && !this.is_admin) { return ''; }

		var tabs_html = '';

		// Get current framework
		var framework_type = this.is_admin ? ws_form_settings.framework_admin : $.WS_Form.settings_plugin.framework;
		var framework = $.WS_Form.frameworks.types[framework_type];
		var framework_tabs = framework['tabs'][this.is_admin ? 'admin' : 'public'];

		// Get tab index cookie if settings require it
		var index = (this.get_object_meta_value(this.form, 'cookie_tab_index')) ? this.cookie_get('tab_index', 0) : 0;

		// Groups
		if(typeof groups === 'undefined') { return ''; }

		if((groups.length > 1) || this.is_admin) {

			for(var i=0; i<groups.length; i++) {

				var group = groups[i];

				tabs_html += this.get_tab_html(group, i, (index == i));
			}

			// Parse wrapper tabs
			var mask = framework_tabs['mask_wrapper'];
			var mask_values = {'tabs': tabs_html, 'id': this.form_id_prefix + 'tabs'};
			var tabs_html_parsed = this.comment_html(this.language('comment_group_tabs')) + this.mask_parse(mask, mask_values) + this.comment_html(this.language('comment_group_tabs'), true);
		}

		return tabs_html_parsed;
	}

	// Get tab HTML
	$.WS_Form.prototype.get_tab_html = function(group, index, is_active) {

		if(typeof index === 'undefined') { var index = 0; }
		if(typeof is_active === 'undefined') { var is_active = false; }

		// Get current framework for tabs
		var framework_type = this.is_admin ? ws_form_settings.framework_admin : $.WS_Form.settings_plugin.framework;
		var framework = $.WS_Form.frameworks.types[framework_type];
		var framework_tabs = framework['tabs'][this.is_admin ? 'admin' : 'public'];

		// Get group label
		var group_label = this.html_encode(group.label);

		// Parse and return wrapper tab
		var mask = framework_tabs['mask_single'];
		var mask_values = {'class': 'wsf-tab', 'data_id': group.id, 'href': '#' + this.form_id_prefix + 'tab-content-' + group.id, 'label': group_label};

		// Active tab
		if(is_active && (typeof framework_tabs['active'] !== 'undefined')) {

			mask_values['active'] = framework_tabs['active'];

		} else {

			mask_values['active'] = '';
		}

		return this.mask_parse(mask, mask_values);
	}

	// Get groups HTML
	$.WS_Form.prototype.get_groups_html = function(groups) {

		var group_html = '';

		// Groups
		if(typeof groups === 'undefined') { return ''; }

		// Get current framework
		var framework_type = this.is_admin ? ws_form_settings.framework_admin : $.WS_Form.settings_plugin.framework;
		var framework = $.WS_Form.frameworks.types[framework_type];
		var framework_groups = framework['groups'][this.is_admin ? 'admin' : 'public'];

		// Get tab index cookie if settings require it
		var group_index_current = (this.get_object_meta_value(this.form, 'cookie_tab_index')) ? this.cookie_get('tab_index', 0) : 0;

		// Build tabs content
		var groups_html = '';
		var use_mask = this.is_admin || (groups.length > 1);
		for(var group_index=0; group_index<groups.length; group_index++) {

			var group = groups[group_index];

			// Render group
			groups_html += this.get_group_html(group, (group_index == group_index_current), use_mask, group_index);
		}

		// Add container class
		var class_array = ['wsf-groups'];

		// Parse wrapper form
		var mask = (use_mask ? framework_groups['mask_wrapper'] : '#groups');
		var mask_values = {'class': class_array.join(' '), 'groups': groups_html, 'id': this.form_id_prefix + 'tabs'};
		var groups_html_parsed = (use_mask ? this.comment_html(this.language('comment_groups')) : '') + this.mask_parse(mask, mask_values) + (use_mask ? this.comment_html(this.language('comment_groups'), true) : '');

		return groups_html_parsed;
	}

	// Get group HTML
	$.WS_Form.prototype.get_group_html = function(group, is_active, use_mask, group_index) {

		if(typeof is_active === 'undefined') { var is_active = false; }
		if(typeof use_mask === 'undefined') { var use_mask = true; }

		// Get current framework
		var framework_type = this.is_admin ? ws_form_settings.framework_admin : $.WS_Form.settings_plugin.framework;
		var framework = $.WS_Form.frameworks.types[framework_type];
		var framework_groups = framework['groups'][this.is_admin ? 'admin' : 'public'];

		var group_id = this.html_encode(group.id);

		// Label
		var group_label = this.html_encode(group.label);
		var label_render = !this.is_admin && (this.get_object_meta_value(group, 'label_render', 'on') == 'on') ? true : false;

		if(label_render) {

			var label_mask_group = !this.is_admin ? this.get_object_meta_value(this.form, 'label_mask_group', '') : '';
			var mask = (label_mask_group != '') ? label_mask_group : (typeof framework_groups['mask_label'] !== 'undefined') ? framework_groups['mask_label'] : '';
			var mask_values = {'label': group_label};
			var label_html_parsed = this.mask_parse(mask, mask_values);

		} else {

			var label_html_parsed = '';
		}

		// HTML
		var sections_html = this.get_sections_html(group);

		// Classes
		var class_array = [];

		// Class - Base
		if(typeof framework_groups['class'] !== 'undefined') {

			class_array.push(framework_groups['class']);
		}

		// Class - Wrapper
		if(!this.is_admin) {

			// Wrapper set at form level
			var class_group_wrapper = this.get_object_meta_value(this.form, 'class_group_wrapper', '');
			if(class_group_wrapper != '') { class_array.push($.trim(class_group_wrapper)); }

			// Wrapper set at group level
			var class_group_wrapper = this.get_object_meta_value(group, 'class_group_wrapper', '');
			if(class_group_wrapper != '') { class_array.push($.trim(class_group_wrapper)); }
		}

		// Class - Active
		if(is_active && (typeof framework_groups['class_active'] !== 'undefined')) {

			class_array.push(framework_groups['class_active']);
		}

		// Parse wrapper tabs content
		var mask = (use_mask ? framework_groups['mask_single'] : '#group');
		var mask_values = {'class': class_array.join(' '), 'id': this.form_id_prefix + 'tab-content-' + group_id, 'data_id': group.id, 'data_group_index': group_index, 'group': sections_html, 'label': label_html_parsed};

		var group_html_parsed = (use_mask ? this.comment_html(this.language('comment_group') + ': ' + group_label) : '') + this.mask_parse(mask, mask_values) + (use_mask ? this.comment_html(this.language('comment_group') + ': ' + group_label, true) : '');

		return group_html_parsed;
	}

	// Get sections html
	$.WS_Form.prototype.get_sections_html = function(group) {

		var sections_html = '';

		// Get current framework
		var framework_type = this.is_admin ? ws_form_settings.framework_admin : $.WS_Form.settings_plugin.framework;
		var framework = $.WS_Form.frameworks.types[framework_type];
		var framework_sections = framework['sections'][this.is_admin ? 'admin' : 'public'];

		var group_id = group.id;
		var sections = group.sections

		// Check to see if section_repeatable data is available
		var section_repeatable = {};
		if(typeof(this.submit) === 'object') {

			if(typeof(this.submit['section_repeatable']) !== 'undefined') {

				section_repeatable = this.submit['section_repeatable'];
			}
		}

		// Sections
		if(typeof(sections) === 'undefined') { return ''; }

		for(var i=0; i<sections.length; i++) {

			var section = sections[i];

			// Check for section repeaters
			var section_id_string = 'section_' + section.id;
			var section_repeatable_array = (

				(section_repeatable !== false) &&
				(typeof(section_repeatable[section_id_string]) !== 'undefined') &&
				(typeof(section_repeatable[section_id_string]['index']) !== 'undefined')

			) ? section_repeatable[section_id_string]['index'] : [false];

			// Loop through section_repeatable_array
			for(var section_repeatable_array_index in section_repeatable_array) {

				if(!section_repeatable_array.hasOwnProperty(section_repeatable_array_index)) { continue; }

				// Get repeatable index
				var section_repeatable_index = section_repeatable_array[section_repeatable_array_index];

				// Render section
				sections_html += this.get_section_html(section, section_repeatable_index);
			}
		}

		// Parse wrapper section
		var mask = framework_sections['mask_wrapper'];
		var mask_values = {'class': 'wsf-sections', 'id': this.form_id_prefix + 'sections-' + group.id, 'data_id': group.id, 'sections': sections_html};
		var sections_html_parsed = this.comment_html(this.language('comment_sections')) + this.mask_parse(mask, mask_values) + this.comment_html(this.language('comment_sections'), true);

		return sections_html_parsed;
	}

	// Get section html
	$.WS_Form.prototype.get_section_html = function(section, section_repeatable_index) {

		if(typeof(section_repeatable_index) === 'undefined') { var section_repeatable_index = false; }

		// Is section repeatable?
		var section_repeatable = false;
		if(!this.is_admin) {

			var section_repeatable = (this.get_object_meta_value(section, 'section_repeatable', '') == 'on') ? true : false;
			if(section_repeatable) {

				if(section_repeatable_index === false) {

					// Find next available section_repeatable_index
					section_repeatable_index = 0;

					do {

						section_repeatable_index++;

					} while($('#' + this.form_id_prefix + 'section-' + section.id + '-repeat-' + section_repeatable_index).length);
				}
			}
		}

		// Attributes
		var attributes = [];

		// Get current framework
		var framework_type = this.is_admin ? ws_form_settings.framework_admin : $.WS_Form.settings_plugin.framework;
		var framework = $.WS_Form.frameworks.types[framework_type];
		var framework_sections = framework['sections'][this.is_admin ? 'admin' : 'public'];

		// Get column class array
		var class_array = this.column_class_array(section);

		// Is section repeatable?
		if(section_repeatable && !this.is_admin) {

			attributes.push('data-repeatable');
			attributes.push('data-repeatable-index="' + section_repeatable_index + '"');
		}

		// Add any base classes
		if(typeof framework_sections['class_single'] !== 'undefined') { class_array = class_array.concat(framework_sections['class_single']); }

		// Public
		if(!this.is_admin) {

			// Wrapper set at form level
			var class_section_wrapper = this.get_object_meta_value(this.form, 'class_section_wrapper', '');
			if(class_section_wrapper != '') { class_array.push($.trim(class_section_wrapper)); }

			// Wrapper set at section level
			var class_section_wrapper = this.get_object_meta_value(section, 'class_section_wrapper', '');
			if(class_section_wrapper != '') { class_array.push($.trim(class_section_wrapper)); }
		}

		// Legend
		var section_label = this.html_encode(section.label)
		var label_render = this.is_admin || ((this.get_object_meta_value(section, 'label_render', 'on') == 'on') ? true : false);

		if(label_render) {

			var label_mask_section = !this.is_admin ? this.get_object_meta_value(this.form, 'label_mask_section', '') : '';
			var mask = (label_mask_section != '') ? label_mask_section : ((typeof framework_sections['mask_label'] !== 'undefined') ? framework_sections['mask_label'] : '');
			var mask_values = {'label': section_label};
			var label_html_parsed = this.mask_parse(mask, mask_values);

		} else {

			var label_html_parsed = '';
		}

		// Hidden
		var hidden = (!this.is_admin && this.get_object_meta_value(section, 'hidden_section', '') == 'on') ? ' style="display:none;"' : '';

		// HTML
		if(section.child_count == 0) {

			// Render fields
			var section_single_html = this.get_fields_html(section, section_repeatable_index);

		} else {

			// Render child section(s)
			var section_single_html = this.get_sections_html(section.children);
		}

		// Parse wrapper section
		var mask = framework_sections['mask_single'];
		var mask_values = {

			'attributes': ((attributes.length > 0) ? ' ' : '') + attributes.join(' '),
			'hidden': hidden,
			'class': class_array.join(' '),
			'id': this.form_id_prefix + 'section-' + section.id + (section_repeatable_index ? ('-repeat-' + section_repeatable_index) : ''),
			'data_id': section.id,
			'section': section_single_html,
			'label': label_html_parsed,
			'section_id': (($.WS_Form.settings_plugin.helper_section_id) ? ('<span class="wsf-section-id">' + this.language('id') + ': ' + section.id + '</span>') : '')
		};

		var section_html_parsed = this.comment_html(this.language('comment_section') + ': ' + section_label) + this.mask_parse(mask, mask_values) + this.comment_html(this.language('comment_section') + ': ' + section_label, true);

		return section_html_parsed;
	}

	// Get fields html
	$.WS_Form.prototype.get_fields_html = function(section, section_repeatable_index) {

		// Is section repeatable?
		var section_repeatable = (this.get_object_meta_value(section, 'section_repeatable', '') == 'on') ? true : false;
		if(typeof(section_repeatable_index) === 'undefined') { var section_repeatable_index = (section_repeatable ? 0 : false); }

		var fields_html = '';

		// Attributes
		var attributes = [];

		// Get current framework for tabs
		var framework_type = this.is_admin ? ws_form_settings.framework_admin : $.WS_Form.settings_plugin.framework;
		var framework = $.WS_Form.frameworks.types[framework_type];
		var framework_fields = framework['fields'][this.is_admin ? 'admin' : 'public'];

		var section_id = section.id;
		var fields = section.fields;

		// Disabled
		if(!this.is_admin) {

			var disabled_section = this.get_object_meta_value(section, 'disabled_section', '');
			if(disabled_section == 'on') { attributes.push('disabled'); }
		}

		// Legend
		var section_label = this.html_encode(section.label)
		var label_render = !this.is_admin && (this.get_object_meta_value(section, 'label_render', 'on') == 'on') ? true : false;

		if(label_render) {

			var label_mask_section = !this.is_admin ? this.get_object_meta_value(this.form, 'label_mask_section', '') : '';
			var mask = (label_mask_section != '') ? label_mask_section : ((typeof framework_fields['mask_wrapper_label'] !== 'undefined') ? framework_fields['mask_wrapper_label'] : '');
			var mask_values = {'label': section_label};
			var label_html_parsed = this.mask_parse(mask, mask_values);

		} else {

			var label_html_parsed = '';
		}

		// Fields
		if(typeof(fields) === 'undefined') { return ''; }

		// Fields
		for(var field_index=0; field_index<fields.length; field_index++) {

			var field = fields[field_index];

			// Render field
			fields_html += this.get_field_html(field, section_repeatable_index);
		}

		// Parse wrapper section
		var mask = framework_fields['mask_wrapper'];
		var mask_values = {'attributes': ((attributes.length > 0) ? ' ' : '') + attributes.join(' '), 'id': this.form_id_prefix + 'fields-' + section.id, 'data_id': section.id, 'fields': fields_html, 'label': label_html_parsed};
		var fields_html_parsed = this.comment_html(this.language('comment_fields')) + this.mask_parse(mask, mask_values) + this.comment_html(this.language('comment_fields'), true);

		return fields_html_parsed;
	}

	// Get field html
	$.WS_Form.prototype.get_field_html = function(field, section_repeatable_index) {

		if(typeof(section_repeatable_index) === 'undefined') { var section_repeatable_index = false; }

		// Attributes
		var attributes = [];

		// Repeatable
		if(section_repeatable_index !== false) { attributes.push('data-repeatable-index="' + section_repeatable_index + '"'); }

		// Get current framework for tabs
		var framework_type = this.is_admin ? ws_form_settings.framework_admin : $.WS_Form.settings_plugin.framework;
		var framework = $.WS_Form.frameworks.types[framework_type];
		var framework_fields = framework['fields'][this.is_admin ? 'admin' : 'public'];

		// Hidden
		if(!this.is_admin) {

			var hidden = (this.get_object_meta_value(field, 'hidden', '') == 'on');
			if(hidden) { attributes.push('style="display:none;"'); }
		}

		// Get column class array
		var class_array = this.column_class_array(field);

		// Add any base classes
		var class_array_config = this.get_field_value_fallback(field.type, false, 'class_single', false, framework_fields);
		if(class_array_config !== false) { class_array = class_array.concat(class_array_config); }

		// Add container class
		if(!this.is_admin) {

			// Wrapper set at form level
			var class_field_wrapper = this.get_object_meta_value(this.form, 'class_field_wrapper', '');
			if(class_field_wrapper != '') { class_array.push($.trim(class_field_wrapper)); }

			// Wrapper set at field level
			var class_field_wrapper = this.get_object_meta_value(field, 'class_field_wrapper', '');
			if(class_field_wrapper != '') { class_array.push($.trim(class_field_wrapper)); }

			// Vertical alignment
			var class_single_vertical_align = this.get_object_meta_value(field, 'class_single_vertical_align', '');
			if(class_single_vertical_align) {

				var class_single_vertical_align_config = this.get_field_value_fallback(field.type, false, 'class_single_vertical_align', false);

				if(typeof(class_single_vertical_align_config[class_single_vertical_align]) !== 'undefined') {

					class_array.push(class_single_vertical_align_config[class_single_vertical_align]);
				}
			}
		}

		// Check to see if this field is available in the submit data
		if(typeof(this.submit) === 'object') {

			var repeatable_suffix = ((section_repeatable_index !== false) ? '_' + section_repeatable_index : '');
			if((typeof(this.submit['meta']) !== 'undefined') && (typeof(this.submit['meta'][ws_form_settings.field_prefix + field.id + repeatable_suffix]) !== 'undefined') && (typeof(this.submit['meta'][ws_form_settings.field_prefix + field.id + repeatable_suffix]['value']) !== 'undefined')) {

				var value = this.submit['meta'][ws_form_settings.field_prefix + field.id + repeatable_suffix]['value'];

				// Special processing
				switch(field.type) {

					case 'datetime' :

						value = (typeof(value.presentation_full) !== 'undefined') ? value.presentation_full : value;
						break;
				}
			}

		} else {

			// Check to see if auto populate data exists
			if(this.submit_auto_populate !== false) {

				if(typeof(this.submit_auto_populate[ws_form_settings.field_prefix + field.id]) !== 'undefined') {

					value = this.submit_auto_populate[ws_form_settings.field_prefix + field.id];
				}
			}
		}

		// Get field HTML (Admin returns blank, Public returns rendered field)
		var field_html = (this.is_admin ? '' : this.get_field_html_single(field, value, false, section_repeatable_index));

		// Field label (For comments only)
		var field_label = this.html_encode(field.label)

		// Get field type config
		if(typeof($.WS_Form.field_type_cache[field.type]) === 'undefined') { return ''; }
		var field_config = $.WS_Form.field_type_cache[field.type];

		// Check field is licensed
		if((typeof(field_config['pro_required']) !== 'undefined') && field_config['pro_required']) {

			return '';
		}

		// Check to see if mask_single should be ignored
		var mask_wrappers_drop = this.is_admin ? false : ((typeof field_config['mask_wrappers_drop'] !== 'undefined') ? field_config['mask_wrappers_drop'] : false);


		// If wrappers should be dropped, disregard them
		if(mask_wrappers_drop) { var mask_single = '#field'; } else { var mask_single = this.get_field_value_fallback(field.type, false, 'mask_single', false, framework_fields); }

		// Build parse values
		var mask_values = {'attributes': ((attributes.length > 0) ? ' ' : '') + attributes.join(' '), 'class': class_array.join(' '), 'id': this.form_id_prefix + 'field-wrapper-' + field.id + ((section_repeatable_index !== false) ? '-repeat-' + section_repeatable_index : ''), 'data_id': field.id, 'type': field.type, 'field': field_html};

		// Parse wrapper field
		var field_html_parsed = this.comment_html(this.language('comment_field') + ': ' + field_label) + this.mask_parse(mask_single, mask_values) + this.comment_html(this.language('comment_field') + ': ' + field_label, true);

		return field_html_parsed;
	}

	// Build field type cache
	$.WS_Form.prototype.field_type_cache_build = function() {

		// If public, set field_type_cache to field_types, already in corret format
		if(!this.is_admin) { $.WS_Form.field_type_cache = $.WS_Form.field_types; }

		// If already built, do not build
		if($.WS_Form.field_type_cache.length > 0) { return true; }

		// Add field types
		for (var group_key in $.WS_Form.field_types) {

	  		var group = $.WS_Form.field_types[group_key];
			var types = group.types;

			// Add field types
			for (var type in types) {

				// Store field type to cache
				$.WS_Form.field_type_cache[type] = types[type];
			}
		}
	}

	// HTML encode string
	$.WS_Form.prototype.html_encode = function(input) {

		if(typeof(input) !== 'string') { return input; }

		var return_html = input.replace_all('&', '&amp;');
		return_html = return_html.replace_all('<', '&lt;');
		return_html = return_html.replace_all('>', '&gt;');
		return_html = return_html.replace_all('"', '&quot;');

		return return_html;
	}

	// JS string encode so it can be used in single quotes
	$.WS_Form.prototype.js_string_encode = function(input) {

		if(typeof input !== 'string') { return input; }

		var return_html = input.replace_all("'", "\\'");

		return return_html;
	}

	// Loader - On
	$.WS_Form.prototype.loader_on = function() {

		$('#wsf-loader').addClass('wsf-loader-on');
	}

	// Loader - Off
	$.WS_Form.prototype.loader_off = function() {

		$('#wsf-loader').removeClass('wsf-loader-on');
	}

	// HTML encode string
	$.WS_Form.prototype.comment_html = function(string, end) {

		if(typeof(end) === 'undefined') { var end = false; }

		var comment_html = $.WS_Form.settings_plugin.comments_html ? ('<!-- ' + (end ? '/' : '') + string + " -->\n") + (end ? "\n" : '') : '';

		return comment_html;
	}

	// HTML encode string
	$.WS_Form.prototype.comment_css = function(string) {

		var comment_css = $.WS_Form.settings_plugin.comments_css ? ("\t/* " + string + " */\n") : '';

		return comment_css;
	}

	// Get object value
	$.WS_Form.prototype.get_object_value = function(object, element, default_return) {

		if(typeof default_return === 'undefined') { var default_return = false; }

		// Check object and return value if found
		if(typeof object === 'undefined') { return default_return; }
		if(typeof object[element] === 'undefined') { return default_return; }
		return object[element];
	}

	// Get object value (with fallback)
	$.WS_Form.prototype.get_field_value_fallback = function(field_type, label_position, element, default_return, framework_fields) {

		if(typeof default_return === 'undefined') { var default_return = false; }
		if(typeof framework_fields === 'undefined') { var framework_fields = this.framework_fields; }

		// Get field to check
		var object = framework_fields;
		var object_fallback = $.WS_Form.field_type_cache[field_type];

		// object[label_position] checks
		if(label_position !== false) {

			// object[label_position]['field_types'][field_type][element]
			var object_not_found = (typeof object === 'undefined') || (typeof object[label_position] === 'undefined') || (typeof object[label_position]['field_types'] === 'undefined') || (typeof object[label_position]['field_types'][field_type] === 'undefined') || (typeof object[label_position]['field_types'][field_type][element] === 'undefined');
			if(!object_not_found) { return object[label_position]['field_types'][field_type][element]; }

			// object[label_position][element]
			var object_not_found = (typeof object === 'undefined') || (typeof object[label_position] === 'undefined') || (typeof object[label_position][element] === 'undefined');
			if(!object_not_found) { return object[label_position][element]; }
		}

		// object['field_types'][field_type][element]
		var object_not_found = (typeof object === 'undefined') || (typeof object['field_types'] === 'undefined') || (typeof object['field_types'][field_type] === 'undefined') || (typeof object['field_types'][field_type][element] === 'undefined');
		if(!object_not_found) { return object['field_types'][field_type][element]; }

		// object[element]
		var object_not_found = (typeof object === 'undefined') || (typeof object[element] === 'undefined');
		if(!object_not_found) { return object[element]; }

		// object_fallback[element]
		if(typeof object_fallback === 'undefined') { return default_return; }
		if(typeof object_fallback[element] === 'undefined') { return default_return; }
		return object_fallback[element];
	}

	// Get object data
	$.WS_Form.prototype.get_object_data = function(object, object_id, use_scratch) {

		if(typeof(use_scratch) === 'undefined') { var use_scratch = false; }

		// Get object data
		switch(object) {

			case 'form' :

				return use_scratch ? this.object_data_scratch : this.form;

			case 'group' :

				return use_scratch ? this.object_data_scratch : this.group_data_cache[object_id];

			case 'section' :

				return use_scratch ? this.object_data_scratch : this.section_data_cache[object_id];

			case 'field' :

				return use_scratch ? this.object_data_scratch : this.field_data_cache[object_id];


			case 'action' :

				return this.action;
		}

		return false;
	}

	// Get object meta
	$.WS_Form.prototype.get_object_meta = function(object, object_id) {

		switch(object) {

			case 'form' :

				var object_meta = $.WS_Form.settings_form.sidebars.form.meta;
				break;


			case 'group' :

				var object_meta = $.WS_Form.settings_form.sidebars.group.meta;
				break;

			case 'section' :

				var object_meta = $.WS_Form.settings_form.sidebars.section.meta;
				break;

			case 'field' :

				var object_data = this.field_data_cache[object_id];
				var object_meta = $.WS_Form.field_type_cache[object_data.type];
				break;
		}

		return object_meta;
	}

	// Get object meta value
	$.WS_Form.prototype.get_object_meta_value = function (object, key, default_return, create, parse_variables_process) {

		if(typeof default_return === 'undefined') { var default_return = false; }

		if(typeof create === 'undefined') { var create = false; }

		if(typeof parse_variables_process === 'undefined') { var parse_variables_process = false; }

		if(typeof object === 'undefined') { return default_return; }

		if(typeof object.meta === 'undefined') { return default_return; }

		if(typeof object.meta[key] === 'undefined') {

			if(create) {

				this.set_object_meta_value(object, key, default_return);

			} else {

				return default_return;
			}
		}

		return parse_variables_process ? this.parse_variables_process(object.meta[key]) : object.meta[key];	
	}

	// Get object meta value
	$.WS_Form.prototype.has_object_meta_key = function (object, key) {

		return (

			(typeof object !== 'undefined') &&
			(typeof object.meta !== 'undefined') &&
			(typeof object.meta[key] !== 'undefined') &&
			(object.meta[key] != '')
		);
	}

	// Parse WS Form variables
	$.WS_Form.prototype.parse_variables_process = function(parse_string, section_repeatable_index, depth) {

		// Checks parse_string
		if(typeof(parse_string) !== 'string') { return parse_string; }
		if(parse_string.indexOf('#') == -1) { return parse_string; }

		// Check section_repeatable_index
		if(typeof(section_repeatable_index) === 'undefined') { section_repeatable_index = false; }

		// Check for too many iterations
		if(typeof(depth) === 'undefined') { depth = 1; }

		// Initialize variables
		var variables = {};
		var variables_single_parse = {};

		// Parse type
		var lookups_contain_singles = false;

		// Check for too many iterations
		if(depth > 100) {

			this.error('error_parse_variable_syntax_error_depth', '', 'parse_variables');
			return this.language('error_parse_variable_syntax_error_depth');
		}

		// Process each parse variable key
		for(var parse_variables_key in $.WS_Form.parse_variables) {

			if(!$.WS_Form.parse_variables.hasOwnProperty(parse_variables_key)) { continue; }

			if(parse_string.indexOf('#' + parse_variables_key) == -1) { continue; }

			// Process each parse variable
			var parse_variables = $.WS_Form.parse_variables[parse_variables_key];

			for(var parse_variable in parse_variables['variables']) {

				if(!parse_variables['variables'].hasOwnProperty(parse_variable)) { continue; }

				if(parse_string.indexOf('#' + parse_variable) == -1) { continue; }

				var parse_variable_config = parse_variables['variables'][parse_variable];

				// Assign value
				var parse_variable_value = (typeof(parse_variable_config['value']) !== 'undefined') ? parse_variable_config['value'] : false;
				var parse_variable_attributes = (typeof(parse_variable_config['attributes']) === 'object') ? parse_variable_config['attributes'] : false;

				// Single parse? (Used if different value returned each parse, e.g. random_number)
				var parse_variable_single_parse = (typeof(parse_variable_config['single_parse']) !== 'undefined') ? parse_variable_config['single_parse'] : false;

				// If no attributes specified, then just set the value
				if((parse_variable_attributes === false) && (parse_variable_value !== false)) { variables[parse_variable] = parse_variable_value; continue; }

				// Get number of attributes required
				var variable_attribute_count = (typeof(parse_variable_config['attributes']) === 'object') ? parse_variable_config['attributes'].length : 0;

				if(variable_attribute_count > 0) {

					// Do until no more found
					var variable_index_start = 0;
					do {

						// Find position of variable and brackets
						var variable_index_of = parse_string.indexOf('#' + parse_variable, variable_index_start);

						// No more instances of variable found
						if(variable_index_of === -1) { continue; }

						// Find bracket positions
						var variable_index_of_bracket_start = -1;
						var variable_index_of_bracket_finish = -1;
						var parse_string_function = parse_string.substring(variable_index_of + parse_variable.length + 1);

						// Bracket should immediately follow the variable name
						if(parse_string_function.substring(0, 1) == '(') {

							variable_index_of_bracket_start = variable_index_of + parse_variable.length + 1;
							variable_index_of_bracket_finish = parse_string.indexOf(')', variable_index_of_bracket_start);
						}

						// Check brackets found
						if(	(variable_index_of_bracket_start === -1) ||
							(variable_index_of_bracket_finish === -1) ) {

							// Shift index to look for next instance
							variable_index_start += parse_variable.length + 1;

							// Get full string to parse
							parse_variable_full = '#' + parse_variable;

							// No brackets found so set attributes as blank
							var variable_attribute_array = [];

						} else {

							// Shift index to look for next instance
							variable_index_start = variable_index_of_bracket_finish;

							// Get attribute string
							var variable_attribute_string = parse_string.substr(variable_index_of_bracket_start + 1, (variable_index_of_bracket_finish - 1) - variable_index_of_bracket_start);

							// Replace non standard double quotes
							variable_attribute_string.replace('“', '"');
							variable_attribute_string.replace('”', '"');

							// Get full string to parse
							var parse_variable_full = parse_string.substr(variable_index_of, (variable_index_of_bracket_finish + 1) - variable_index_of);

							// Get attribute array
							var variable_attribute_array_raw = variable_attribute_string.match(/(".*?"|[^",\s]+)(?=\s*,|\s*$)/g);

							if(variable_attribute_array_raw !== null) {

								// Trim and strip double quotes
								var variable_attribute_array = variable_attribute_array_raw.map(function(e) { 

									e = $.trim(e);
									e = e.replace(/^"(.+(?="$))"$/, '$1'); 
									return e;
								});

							} else {

								var variable_attribute_array = [];
							}
						}

						// Check each attribute
						for(var parse_variable_attributes_index in parse_variable_attributes) {

							if(!parse_variable_attributes.hasOwnProperty(parse_variable_attributes_index)) { continue; }

							var parse_variable_attribute = parse_variable_attributes[parse_variable_attributes_index];

							var parse_variable_attribute_id = parse_variable_attribute['id'];

							// Was attribute provided for this index?
							var parse_variable_attribute_supplied = (typeof(variable_attribute_array[parse_variable_attributes_index]) !== 'undefined');

							// Check required
							var parse_variable_attribute_required = (typeof(parse_variable_attribute['required'] !== 'undefined') ? parse_variable_attribute['required'] : true);
							if(parse_variable_attribute_required && !parse_variable_attribute_supplied) {

								// Syntax error - Attribute count
								this.error('error_parse_variable_syntax_error_attribute', '#' + parse_variable + ' (Expected ' + parse_variable_attribute_id + ')', 'parse-variables');
								return this.language('error_parse_variable_syntax_error_attribute', '#' + parse_variable + ' (Expected ' + parse_variable_attribute_id + ')');
							}

							// Check default
							var parse_variable_attribute_default = typeof(parse_variable_attribute['default'] !== 'undefined') ? parse_variable_attribute['default'] : false;
							if((parse_variable_attribute_default !== false) && !parse_variable_attribute_supplied) {

								variable_attribute_array[parse_variable_attributes_index] = parse_variable_attribute_default;
							}

							// Check valid
							var parse_variable_attribute_valid = (typeof(parse_variable_attribute['valid']) !== 'undefined') ? parse_variable_attribute['valid'] : false;
							if(parse_variable_attribute_valid !== false) {

								if(!parse_variable_attribute_valid.includes(variable_attribute_array[parse_variable_attributes_index])) {

									// Syntax error - Invalid attribute value
									this.error('error_parse_variable_syntax_error_attribute_invalid', '#' + parse_variable + ' (Expected ' + parse_variable_attribute_valid.join(', ') + ')', 'parse-variables');
									return this.language('error_parse_variable_syntax_error_attribute_invalid', '#' + parse_variable + ' (Expected ' + parse_variable_attribute_valid.join(', ') + ')');
								}
							}
						}

						// Process variable
						var parsed_variable = '';
						switch(parse_variable) {

							case 'query_var' :

								parsed_variable = this.get_query_var(variable_attribute_array[0]);
								break;

							case 'field' :
							case 'ecommerce_field_price' :

								if(isNaN(variable_attribute_array[0])) {

									this.error('error_parse_variable_syntax_error_field_id', variable_attribute_array[0], 'parse-variables');
									return this.language('error_parse_variable_syntax_error_field_id', variable_attribute_array[0]);
								}

								var field_id = variable_attribute_array[0];

								// Use default value
								if(typeof(this.field_data_cache[field_id]) === 'undefined') {

									this.error('error_parse_variable_syntax_error_field_id', variable_attribute_array[0], 'parse-variables');
									return this.language('error_parse_variable_syntax_error_field_id', variable_attribute_array[0]);
								}

								var field_config = this.field_data_cache[field_id];
								var field_type_config = $.WS_Form.field_type_cache[field_config.type];
								var field_name = ws_form_settings.field_prefix + parseInt(field_id) + ((section_repeatable_index) ? '[' + section_repeatable_index + ']' : '');

								// Check if submitted as array
								var field_type_submit_array = (typeof field_type_config['submit_array'] !== 'undefined') ? field_type_config['submit_array'] : false;
								field_name += field_type_submit_array ? '[]' : '';

								// Build field selector
								var field_selector = '[name="' + field_name + '"]';

								// Adjustments by field type
								switch(field_config.type) {

									// Radio
									case 'radio' :

										field_selector += ':checked';
										break;
								}

								var field_obj = $(field_selector, this.form_canvas_obj);

								if(field_obj.length) {

									// Use live value
									parsed_variable = field_obj.val();

								} else {

									parsed_variable = this.get_object_meta_value(this.field_data_cache[field_id], 'default_value', '');
									parsed_variable = this.parse_variables_process(parsed_variable, section_repeatable_inde, depth + 1);
								}

								if(parse_variable == 'ecommerce_field_price') {

									var math_round = parseInt($.WS_Form.settings_plugin.price_decimals);
									parsed_variable = parseFloat(this.get_number(parsed_variable)).toFixed(math_round)
								}

								break;

							case 'select_option_text' :

								if(isNaN(variable_attribute_array[0])) {

									this.error('error_parse_variable_syntax_error_field_id', variable_attribute_array[0], 'parse-variables');
									return this.language('error_parse_variable_syntax_error_field_id', variable_attribute_array[0]);
								}

								var field_id = variable_attribute_array[0];

								var field_name = ws_form_settings.field_prefix + parseInt(field_id) + ((section_repeatable_index) ? '[' + section_repeatable_index + ']' : '');

								var field_obj = $(field_name + '[] option:selected', this.form_canvas_obj);
								if(field_obj.length) {

									// Use live value
									parsed_variable = field_obj.text();
								}

								break;

							case 'post_date_custom' :
							case 'server_date_custom' :
							case 'blog_date_custom' :

								var parsed_variable_date = new Date(parse_variable_value);
								parsed_variable = parsed_variable_date.format(variable_attribute_array[0]);
								break;

							case 'client_date_custom' :

								var parsed_variable_date = new Date();
								parsed_variable = parsed_variable_date.format(variable_attribute_array[0]);
								break;

							case 'random_number' :

								var random_number_min = parseInt(variable_attribute_array[0]);
								var random_number_max = parseInt(variable_attribute_array[1]);
								parsed_variable = Math.floor(Math.random() * (random_number_max - random_number_min + 1)) + random_number_min;
								break;

							case 'random_string' :

								var random_string_length = parseInt(variable_attribute_array[0]);
								var random_string_characters = variable_attribute_array[1];
								var random_string_character_length = random_string_characters.length;
								parsed_variable = '';
								for (var random_string_index = 0; random_string_index < random_string_length; random_string_index++) { parsed_variable += random_string_characters[Math.floor(Math.random() * random_string_character_length)]; } 
								break;
						}

						// Assign value
						if(parse_variable_single_parse) {
	
							variables_single_parse[parse_variable_full.substring(1)] = this.html_encode(parsed_variable);

						} else {

							variables[parse_variable_full.substring(1)] = this.html_encode(parsed_variable);
						}

					} while (variable_index_of !== -1);
				}
			}
		}

		// Form
		if(parse_string.indexOf('form') != -1) {

			variables['form_id'] = this.form_id;
			variables['form_instance_id'] = this.form_instance_id;
			variables['form_obj_id'] = this.form_obj_id;
			variables['form_label'] = this.form.label;
			variables['form_checksum'] = this.form.published_checksum;
			variables['form_framework'] = this.framework.name;
		}

		// Submit
		if(parse_string.indexOf('submit') != -1) {

			variables['submit_hash'] = this.hash;
		}

		// Client
		if(parse_string.indexOf('client') != -1) {

			var client_date_time = new Date();

			variables['client_time'] = client_date_time.format(ws_form_settings.time_format);
			variables['client_date'] = client_date_time.format(ws_form_settings.date_format);
		}


		// Sort variables descending by key
		var variables_sorted = [];
		var keys = Object.keys(variables);
		keys.sort();
		keys.reverse();
		for(var i = 0; i < keys.length; i++) { variables_sorted[keys[i]] = variables[keys[i]]; }
		variables = variables_sorted;

		// Parse until no more changes made
		var parse_string_before = parse_string;
		parse_string = this.mask_parse(parse_string, variables);
		parse_string = this.mask_parse(parse_string, variables_single_parse, true);
		if(
			(parse_string !== parse_string_before) &&
			(parse_string.indexOf('#') !== -1)
		) {

			parse_string = this.parse_variables_process(parse_string, section_repeatable_index, depth + 1);
		}

		return parse_string;
	}

	// Get query variable
	$.WS_Form.prototype.get_query_var = function(query_var) {

		var url = window.location.href;
		query_var = query_var.replace(/[\[\]]/g, "\\$&");
		var regex = new RegExp("[?&]" + query_var + "(=([^&#]*)|&|#|$)"),
			results = regex.exec(url);
		if (!results) return '';
		if (!results[2]) return '';
		return decodeURIComponent(results[2].replace(/\+/g, " "));
	}

	// Set object meta
	$.WS_Form.prototype.set_object_meta_value = function (object, key, value) {

		if(typeof object === 'undefined') { return value; }

		if(typeof object.meta === 'undefined') { return value; }
	
		// Set value
		object.meta[key] = value;
	}

	// Get column class array
	$.WS_Form.prototype.column_class_array = function(object) {

		var column_class_array = [];

		// Get current framework breakpoints
		var framework_breakpoints = this.framework.breakpoints;

		// Get class masks
		var column_class = this.framework.columns.column_class;
		var offset_class = this.framework.columns.offset_class;

		var column_size_value_old = 0;
		var offset_value_old = 0;

		for(var breakpoint in framework_breakpoints) {

			if(!framework_breakpoints.hasOwnProperty(breakpoint)) { continue; }

			var column_framework = framework_breakpoints[breakpoint];

			var column_size_value = this.get_object_meta_value(object, 'breakpoint_size_' + breakpoint, '');
			if(column_size_value == '') { column_size_value = '0'; }
			column_size_value = parseInt(column_size_value);

			// If a framework breakpoint size is not found, but column_size_default is set, then use configured or specified value as size (Used for Bootstrap 4 that does not fallback to full column width)
			if(column_size_value == 0) {

				if(typeof column_framework.column_size_default !== 'undefined') {

					switch(column_framework.column_size_default) {

						case 'column_count' :

							column_size_value = parseInt($.WS_Form.settings_plugin.framework_column_count);
							break;

						default :

							column_size_value = parseInt(column_framework.column_size_default);
					}
				}

			} else {

				column_size_value = parseInt(column_size_value);
			}

			// Process breakpoint (only if it differs from the previous breakpoint size, otherwise it just inheris the size from the previous breakpoint)
			if((column_size_value > 0) && (column_size_value != column_size_value_old)) {

				// Get ID for parsing
				var id = column_framework.id;

				// Build mask values for parser
				var mask_values = {

					'id': id,
					'size_word': (typeof this.number_to_word[column_size_value] === 'undefined') ? column_size_value : this.number_to_word[column_size_value],
					'size': column_size_value
				};

				// Check for breakpoint specific column class mask
				if(typeof column_framework.column_class !== 'undefined') {

					var column_class_single = column_framework.column_class;

				} else {

					var column_class_single = column_class;
				}

				// Get single class
				var class_single = this.mask_parse(column_class_single, mask_values);

				// Push to class array
				column_class_array.push(class_single);

				// Remember framework size
				column_size_value_old = column_size_value;
			}

			// Offset
			var offset_value = this.get_object_meta_value(object, 'breakpoint_offset_' + breakpoint, '');

			// Process breakpoint (only if it differs from the previous breakpoint offset, otherwise it just inheris the offset from the previous breakpoint)
			var offset_found = false;
			if((offset_value !== '') && (offset_value != offset_value_old)) {

				// Get ID for parsing
				var id = column_framework.id;

				// Build mask values for parser
				var mask_values = {

					'id': id,
					'offset_word': (typeof this.number_to_word[offset_value] === 'undefined') ? offset_value : this.number_to_word[offset_value],
					'offset': offset_value
				};

				// Check for breakpoint specific column class mask
				if(typeof column_framework.offset_class !== 'undefined') {

					var offset_class_single = column_framework.offset_class;

				} else {

					var offset_class_single = offset_class;
				}

				// Get single class
				var class_single = this.mask_parse(offset_class_single, mask_values);

				// Push to class array
				column_class_array.push(class_single);

				// Remember framework size
				offset_value_old = offset_value;

				offset_found = true;
			}

			if(offset_found) { column_class_array.push('wsf-has-offset'); }
		}

		return column_class_array;
	}

	// Render field classes (add/remove)
	$.WS_Form.prototype.column_classes_render = function(obj, column, add) {

		if(typeof add === 'undefined') { var add = true; }

		// Get column class array before change
		var class_array = this.column_class_array(column);

		// Add/Remove old classes
		for(var i=0; i < class_array.length; i++) {
			
			if(add) {

				obj.addClass(class_array[i]);

			} else {

				obj.removeClass(class_array[i]);
			}
		}
	}

	// Mask parse
	$.WS_Form.prototype.mask_parse = function(mask, lookups, single_parse) {

		if(typeof(mask) !== 'string') { return ''; }
		if(typeof(single_parse) === 'undefined') { var single_parse = false; }

		// Reverse order of lookup values
		var mask_lookups = [];
		for(var key in lookups) {

			if(!lookups.hasOwnProperty(key)) { continue; }

			var value = lookups[key];

			mask_lookups.push({'key': key, 'value': value})
		}

		// Sort keys
		mask_lookups.sort( function ( a, b ) { return (a['key'] < b['key']) ? 1 : -1; } );

		// Process mask_lookups array
		for(var lookups_index in mask_lookups) {

			if(!mask_lookups.hasOwnProperty(lookups_index)) { continue; }

			var lookup = mask_lookups[lookups_index];
			var key = lookup['key'];
			var value = lookup['value'];

			if(single_parse) {

				mask = mask.replace('#' + key, value);

			} else {

				mask = mask.replace_all('#' + key, value);
			}
		}

		return mask;
	}

	// Get field label
	$.WS_Form.prototype.get_field_label = function(field_id) {

		var field_data = this.field_data_cache[field_id];
		return field_data.label;
	}

	// Get section name
	$.WS_Form.prototype.get_section_label = function(section_id) {

		var section_data = this.section_data_cache[section_id];
		return section_data.label;
	}

	// Get localized language string
	$.WS_Form.prototype.language = function(id, value, html_encode) {

		if(typeof value === 'undefined') { var value = false; }
		if(typeof html_encode === 'undefined') { var html_encode = true; }

		var language_string = '';
		var return_string = '';

		if(id == 'error_language') {

			language_string = 'Language reference not found: %s';

		} else {

			if($.WS_Form.settings_form !== null) {

				if(typeof $.WS_Form.settings_form.language !== 'undefined') {

					if(typeof $.WS_Form.settings_form.language[id] !== 'undefined') {

						var language_string = $.WS_Form.settings_form.language[id];
					}
				}
			}
		}

		if(language_string != '') {

			if(value !== false) { language_string = language_string.replace_all('%s', value); }

			return_string = html_encode ? this.html_encode(language_string) : language_string;
		}

		if(return_string == '') {

			return_string = (value == '') ? '[LANGUAGE NOT FOUND: ' + id + ']' : value;

			if($.WS_Form.settings_form !== null) {

				this.error('error_language', id);
			}
		}

		return return_string;
	}

	// Set cookie
	$.WS_Form.prototype.cookie_set = function(cookie_name, cookie_value, cookie_expiry, bind_to_form_id) {

		if(typeof cookie_expiry === 'undefined') { var cookie_expiry = true; }
		if(typeof bind_to_form_id === 'undefined') { var bind_to_form_id = true; }

		// Read cookie prefix
		var cookie_prefix = this.get_object_value($.WS_Form.settings_plugin, 'cookie_prefix');
		if(!cookie_prefix) { return false; }

		// Read cookie timeout
		if(cookie_expiry) {

			// Get cookie timeout value
			var cookie_timeout = this.get_object_value($.WS_Form.settings_plugin, 'cookie_timeout');
			if(!cookie_timeout) { return false; }

			// Build expiry
			var d = new Date();
			d.setTime(d.getTime() + (cookie_timeout * 1000));
			var expires = 'expires='+ d.toUTCString() + ';';

		} else {

			var expires = '';
		}

		// Set cookie
		var cookie_string = cookie_prefix + '_' + (bind_to_form_id ? (this.form_id + '_') : '') + cookie_name + "=" + cookie_value + ";" + expires + "path=/";
		document.cookie = cookie_string;

		return true;
	}

	// Get cookie
	$.WS_Form.prototype.cookie_get = function(cookie_name, default_value, bind_to_form_id) {

		if(typeof bind_to_form_id === 'undefined') { var bind_to_form_id = true; }

		// Read cookie configurtion
		var cookie_prefix = this.get_object_value($.WS_Form.settings_plugin, 'cookie_prefix');
		if(!cookie_prefix) { return default_value; }

		// Build name
		var name = cookie_prefix + '_' + (bind_to_form_id ? (this.form_id + '_') : '') + cookie_name + "=";

		// Find cookie
		var decodedCookie = decodeURIComponent(document.cookie);
		var ca = decodedCookie.split(';');
		for(var i = 0; i < ca.length; i++) {

			var c = ca[i];

			while(c.charAt(0) == ' ') {
				c = c.substring(1);
			}

			if(c.indexOf(name) == 0) {
				return c.substring(name.length, c.length);
			}
		}

		return (typeof default_value !== 'undefined') ? default_value : '';
	}

	// Clear cookie
	$.WS_Form.prototype.cookie_clear = function(cookie_name, bind_to_form_id) {

		if(typeof bind_to_form_id === 'undefined') { var bind_to_form_id = true; }

		// Read cookie prefix
		var cookie_prefix = this.get_object_value($.WS_Form.settings_plugin, 'cookie_prefix');
		if(!cookie_prefix) { return false; }

		// Build expiry
		var d = new Date();
		d.setTime(d.getTime() - (3600 * 1000));
		var expires = "expires="+ d.toUTCString();

		// Clear cookie (because of negative expiry date)
		var cookie_string = cookie_prefix + '_' + (bind_to_form_id ? (this.form_id + '_') : '') + cookie_name + "='';" + expires + ";path=/";
		document.cookie = cookie_string;

		return true;
	}

	// Set caret at end
	$.WS_Form.prototype.set_caret_at_end = function(obj) {

		var objLen = obj.value.length;

		// For IE Only
		if (document.selection) {

			// Set focus
			obj.focus();

			// Use IE Ranges
			var oSel = document.selection.createRange();

			// Reset position to 0 & then set at end
			oSel.moveStart('character', -objLen);
			oSel.moveStart('character', objLen);
			oSel.moveEnd('character', 0);
			oSel.select();

		} else if (obj.selectionStart || obj.selectionStart == '0') {

			// Other browsers
			obj.selectionStart = objLen;
			obj.selectionEnd = objLen;
			obj.focus();
		}
	}

	// Tabs
	$.WS_Form.prototype.tabs = function(obj, atts) {

		if(typeof atts === 'undefined') { var atts = {}; };
		var tab_selector = (typeof atts.selector !== 'undefined') ? atts.selector : 'li';
		var tab_active_index = (typeof atts.active !== 'undefined') ? atts.active : 0;
		var tab_activate = (typeof atts.activate !== 'undefined') ? atts.activate : false;

		var ws_this = this;
		var tab_index = 0;

		obj.addClass('wsf-tabs');

		$(tab_selector, obj).each(function() {

			var tab_obj_outer = $(this);

			tab_obj_outer.find('a[href*="#"]:not([href="#"])').each(function() {

				// Add tab index data attribute
				$(this).attr('data-tab-index', tab_index);

				// Click event
				$(this).off('click').on('click', function(e) {

					e.preventDefault();
					e.stopPropagation();
					e.stopImmediatePropagation();
					ws_this.tab_show($(this), tab_obj_outer, tab_activate);
				});

				// Initialize tab
				if(tab_index == tab_active_index) {
					
					ws_this.tab_show($(this), tab_obj_outer);
				}
			});

			tab_index++;
		});
	}

	// Tabs - Destroy
	$.WS_Form.prototype.tabs_destroy = function(obj, atts) {

		if(typeof atts === 'undefined') { var atts = {}; };
		var tab_selector = (typeof atts.selector !== 'undefined') ? atts.selector : 'li';

		$(tab_selector, obj).each(function() {

			var tab_obj_outer = $(this);

			tab_obj_outer.find('a').each(function() {

				// Remove tab index data attribute
				$(this).removeAttr('data-tab-index');

				// Remove click event
				$(this).off('click');
			});
		});

		obj.removeClass('wsf-tabs');
	}

	// Tabs - Show
	$.WS_Form.prototype.tab_show = function(tab_obj, tab_obj_outer, tab_activate) {

		// Hide siblings
		var ws_this = this;
		tab_obj_outer.siblings().each(function() {

			var tab_obj_sibling = $(this).find('a').first();
			ws_this.tab_hide(tab_obj_sibling, $(this));
		});

		// Tab
		tab_obj_outer.addClass('wsf-tab-active');

		// Tab content
		var tab_hash = tab_obj.attr('href');
		$(tab_hash).show();

		// Tab activate function
		if(typeof(tab_activate) === 'function') {

			var tab_index = tab_obj.attr('data-tab-index');
			tab_activate(tab_index);
		}

		// Fire event
		tab_obj.trigger('tab_show');
	}

	// Tabs - Hide
	$.WS_Form.prototype.tab_hide = function(tab_obj, tab_obj_outer) {

		// Tab
		tab_obj_outer.removeClass('wsf-tab-active');

		// Tab content
		var tab_hash = tab_obj.attr('href');
		$(tab_hash).hide();
	}

	// Build group_data_cache, section_data_cache, field_data_cache and action_data_cache
	$.WS_Form.prototype.data_cache_build = function() {

		// Check we can build the caches
		if(typeof this.form === 'undefined') { return false; }
		if(typeof this.form.groups === 'undefined') { return false; }

		// Clear data caches
		this.group_data_cache = [];
		this.section_data_cache = [];
		this.field_data_cache = [];
		this.action_data_cache = [];

		// Build group, section and field data caches
		for(var group_index=0; group_index<this.form.groups.length; group_index++) {

			var group = this.form.groups[group_index];

			// Process group
			this.data_cache_build_group(group);
		}

		// Build action data cache
		var action = this.get_object_meta_value(this.form, 'action', false);
		if(!(
			(action === false) ||
			(typeof(action.groups) === 'undefined') ||
			(typeof(action.groups[0]) === 'undefined') ||
			(typeof(action.groups[0].rows) !== 'object') ||
			(action.groups[0].rows.length == 0)
		)) {

			var rows = action.groups[0].rows;
			for(var row_index in rows) {

				if(!rows.hasOwnProperty(row_index)) { continue; }

				var row = rows[row_index];

				if(typeof(row.data) === 'undefined') { continue; }
				if(row.data.length == 0) { continue; }

				this.action_data_cache[row.id] = {'label': row.data[0]};
			}
		}	

		return true;
	}

	// Build group_data_cache
	$.WS_Form.prototype.data_cache_build_group = function(group) {

		// Store to group_data_cache array
		this.group_data_cache[group.id] = group;

		for(var i=0; i<group.sections.length; i++) {

			var section = group.sections[i];

			// Process section
			this.data_cache_build_section(section);
		}

		return true;
	}

	// Build section_data_cache and field_data_cache
	$.WS_Form.prototype.data_cache_build_section = function(section) {

		// Store to section_data_cache array
		this.section_data_cache[section.id] = section;

		var section_repeatable = (

			(typeof(section.meta) !== 'undefined') &&
			(typeof(section.meta.section_repeatable) !== 'undefined') &&
			(section.meta.section_repeatable == 'on')
		);

		// HTML
		if(section.child_count == 0) {

			// Process fields
			for(var field_index=0; field_index<section.fields.length; field_index++) {

				var field = section.fields[field_index];

				// Repeatable?
				field.in_section_repeatable = section_repeatable;

				// Skip fields that are unlicensed (Required for published data)
				if(typeof($.WS_Form.field_type_cache[field.type]) === 'undefined') { continue; }

				// Store to field_data_cache array
				this.field_data_cache[field.id] = field;
			}

		} else {

			// Process child section(s)
			this.data_cache_build_section(section.children);
		}

		return true;
	}

	// Randomize array
	$.WS_Form.prototype.array_randomize = function(array_to_randomize) {

		for (var i = array_to_randomize.length - 1; i > 0; i--) {

			var j = Math.floor(Math.random() * (i + 1));
			var temp = array_to_randomize[i];
			array_to_randomize[i] = array_to_randomize[j];
			array_to_randomize[j] = temp;
		}					

		return array_to_randomize;
	}

	// Get nice duration
	$.WS_Form.prototype.get_nice_duration = function(duration) {

		if(duration == 0) { return '-'; }

		var duration_hours = ~~(duration / 3600);
		var duration_minutes = ~~((duration % 3600) / 60);
		var duration_seconds = duration % 60;

		var return_string = '';

		if(duration_hours > 0) { return_string += '' + duration_hours + ' ' + this.language('submit_duration_hours') + ' '; }
		if(duration_minutes > 0) { return_string += '' + duration_minutes + ' ' + this.language('submit_duration_minutes') + ' '; }

		return_string += '' + duration_seconds + ' ' + this.language('submit_duration_seconds') + '';

		return return_string;
	}

	// Get field html
	$.WS_Form.prototype.get_field_html_single = function (field, value, is_submit, section_repeatable_index) {

		if(typeof(is_submit) === 'undefined') { var is_submit = false; }
		if(typeof(section_repeatable_index) === 'undefined') { var section_repeatable_index = false; }

		var field_html = '';
		var attributes_values_field = [];
		var has_value = (typeof(value) !== 'undefined');

		// Determine field name
		var repeatable_suffix = ((section_repeatable_index !== false) ? '-repeat-' + section_repeatable_index : '');
		var field_name = this.field_name_prefix + field.id + ((section_repeatable_index !== false) ? (is_submit ? ('_' + section_repeatable_index) : ('[' + section_repeatable_index + ']')) : '');

		// Submit only config
		var submit_attributes_field = ['default', 'class', 'input_type_datetime', 'multiple', 'min', 'max', 'step'];
		var submit_attributes_field_label = ['class'];

		if(typeof($.WS_Form.field_type_cache[field.type]) === 'undefined') { return ''; }

		// Get field type
		var field_type = $.WS_Form.field_type_cache[field.type];

		// Check to see if this field can be used in the current edition
		var pro_required = field_type['pro_required'];
		if(pro_required) { return this.language('error_pro_required'); }

		// Should label be rendered?
		if(is_submit) {

			var label_render = true;

		} else {

			var label_render = this.get_object_meta_value(field, 'label_render', true);
		}

		// Check for label disable override
		var label_disabled = this.get_field_value_fallback(field.type, label_position, 'label_disabled', false);
		if(label_disabled) { label_render = false; }

		// Should field name be suffixed with []?
		var submit_array = (typeof field_type['submit_array'] !== 'undefined') ? field_type['submit_array'] : false;
		if(submit_array) { field_name += '[]'; }

		// Determine label_position (If we are not rendering the label, then set to top so no position specific framework masks are used)
		if(label_render && !is_submit) {

			// Get label parameters
			var label_position = this.get_object_meta_value(field, 'label_position', 'default');
			label_position = this.get_field_value_fallback(field.type, false, 'label_position_force', label_position);

			// Field is using default position, so read default label position of form
			if(label_position == 'default') {

				label_position = this.get_object_meta_value(this.form, 'label_position_form', 'top');
			}

		} else {

			var label_position = 'top';
		}

		// Inline rendering
		var class_inline = this.get_object_meta_value(field, 'class_inline', false);

		// Check to see if wrappers should be ignored
		var mask_wrappers_drop = (typeof field_type['mask_wrappers_drop'] !== 'undefined') ? field_type['mask_wrappers_drop'] : false;


		// Load masks
		var mask = mask_wrappers_drop ? '#field' : this.get_field_value_fallback(field.type, label_position, 'mask', '#field');
		var mask_field = this.get_field_value_fallback(field.type, label_position, 'mask_field', '');
		var mask_field_label = label_render ? this.get_field_value_fallback(field.type, label_position, 'mask_field_label', '') : '';
		var mask_field_label_hide_group = this.get_field_value_fallback(field.type, label_position, 'mask_field_label_hide_group', false);
		var mask_help = this.get_field_value_fallback(field.type, label_position, 'mask_help', '');
		var mask_help_append = this.get_field_value_fallback(field.type, label_position, 'mask_help_append', '');
		var mask_help_append_separator = this.get_field_value_fallback(field.type, label_position, 'mask_help_append_separator', '');
		var mask_invalid_feedback = this.get_field_value_fallback(field.type, label_position, 'mask_invalid_feedback', '');

		// Get default value
		var default_value = this.get_object_meta_value(field, 'default_value', '', false, true);
		var text_editor = this.get_object_meta_value(field, 'text_editor', '', false, true);
		var html_editor = this.get_object_meta_value(field, 'html_editor', '', false, true);

		// Get default value
		if(!has_value) {

			var value = '';
			if(default_value != '') { value = this.html_encode(default_value); }
			if(text_editor != '') { value = text_editor; }
			if(html_editor != '') { value = html_editor; }
		}

		// Classes
		if(!is_submit) {

			var class_field_form = this.get_object_meta_value(this.form, 'class_field', '', false, true);
			var class_field = this.get_object_meta_value(field, 'class_field', '', false, true);
			if(class_field_form != '') { class_field += ' ' + class_field_form; }

			// Full width class for buttons
			var class_field_full_button_remove = this.get_object_meta_value(field, 'class_field_full_button_remove', '');
			if(!class_field_full_button_remove) {

				var class_field_full_button = this.get_field_value_fallback(field.type, label_position, 'class_field_full_button', '');
				if(typeof(class_field_full_button) === 'object') {
					class_field += ' ' + class_field_full_button.join(' ');
				}
			}

			// Type class for buttons
			var class_field_button_type = this.get_object_meta_value(field, 'class_field_button_type', false);
			if(!class_field_button_type) {

				var class_field_button_type = this.get_field_value_fallback(field.type, label_position, 'class_field_button_type_fallback', false);
			}
			if(class_field_button_type) {

				var class_field_button_type_config = this.get_field_value_fallback(field.type, label_position, 'class_field_button_type', '');
				if(typeof(class_field_button_type_config[class_field_button_type]) !== 'undefined') {

					class_field += (class_field_button_type_config[class_field_button_type] !== '') ? (' ' + class_field_button_type_config[class_field_button_type]) : '';
				}
			}
		}

		// Label / field column widths (For left/right label positioning)
		var framework_column_count = parseInt($.WS_Form.settings_plugin.framework_column_count);

		var column_width_label_form = parseInt(this.get_object_meta_value(this.form, 'label_column_width_form', 3));
		var column_width_label = this.get_object_meta_value(field, 'label_column_width', 'default');

		switch(column_width_label) {

			case 'default' :
			case '' :

				column_width_label = column_width_label_form;
				break;

			default :

				column_width_label = parseInt(column_width_label);
		}
		if(column_width_label >= framework_column_count) { column_width_label = (framework_column_count - 1); }

		var column_width_field = framework_column_count - column_width_label;

		// Field - Mask values
		var mask_values_field = {

			'id': 					this.form_id_prefix + 'field-' + field.id + repeatable_suffix,
			'form_id_prefix':  		this.form_id_prefix,
			'form_id':  			this.form_id,
			'form_instance_id':  	this.form_instance_id,
			'field_id': 			field.id,

			'name': 				field_name,
			'label': 				this.parse_variables_process(field.label),
			'value': 				value,
			'required': 			(this.get_object_meta_value(this.form, 'label_required')) ? '<span class="wsf-required-wrapper"></span>' : '',

			'column_width_label': 	column_width_label,
			'column_width_field': 	column_width_field,

			'max_upload_size': 		ws_form_settings.max_upload_size,
			'locale': 				ws_form_settings.locale,
			'currency':  			$.WS_Form.settings_plugin.currency
		};

		// Date field
		if(field.type == 'datetime') {

			if(

				// Use date/time picker
				($.WS_Form.settings_plugin.ui_datepicker == 'on') ||

				// If browser does not support native date/time picker, use jQuery component
				(
					($.WS_Form.settings_plugin.ui_datepicker == 'native') &&
					!this.native_date
				)

			) {

				mask_values_field.datetime_type = 'text';

			} else {

				mask_values_field.datetime_type = this.get_object_meta_value(field, 'input_type_datetime', 'date');
			}
		}

		// Color field
		if(field.type == 'color') {

			if(

				// Use color picker
				($.WS_Form.settings_plugin.ui_color == 'on') ||

				// If browser does not support native color picker, use jQuery component
				(
					($.WS_Form.settings_plugin.ui_color == 'native') &&
					!this.native_color
				)

			) {

				mask_values_field.color_type = 'text';

			} else {

				mask_values_field.color_type = 'color';
			}
		}

		// Field - Mask values - Meta data
		var meta_key_parse_variables = this.get_field_value_fallback(field.type, label_position, 'meta_key_parse_variables', []);
		for(var meta_key_parse_variables_index in meta_key_parse_variables) {

			if(!meta_key_parse_variables.hasOwnProperty(meta_key_parse_variables_index)) { continue; }

			// Get meta key
			var meta_key = meta_key_parse_variables[meta_key_parse_variables_index];

			// Get default value
			var meta_key_config = (typeof($.WS_Form.meta_keys[meta_key]) === 'undefined') ? false : $.WS_Form.meta_keys[meta_key];
			var meta_key_value_default = (meta_key_config !== false) ? ((typeof(meta_key_config['d']) === 'undefined') ? '' : meta_key_config['d']) : '';

			// Get meta value
			var meta_value = this.get_object_meta_value(field, meta_key, meta_key_value_default);

			// If value is an array, turn it into a JSON string
			if(typeof(meta_value) === 'object') { meta_value = JSON.stringify(meta_value); }

			// Encode single quotes for JS purposes
			meta_value = meta_value.replace_all("'", '&#39;');

			mask_values_field[meta_key] = meta_value;
		}

		// Field label - Mask values
		var mask_values_field_label = ($.extend(true, {}, mask_values_field));
		mask_values_field_label['label_id'] = this.form_id_prefix + 'label-' + field.id + repeatable_suffix;

		// Help
		var mask_values_help = [];
		var help_id = this.form_id_prefix + 'help-' + field.id + repeatable_suffix;
		var help = !is_submit ? this.get_object_meta_value(field, 'help', '', false, true) : '';

		// Help - When editing a submission, change help by field type
		if(is_submit) {

			switch(field.type) {

				case 'range' :

					help = '#value';
					break;
			}
		}

		// Help classes
		var class_help_array = this.get_field_value_fallback(field.type, label_position, 'class_help', []);

		// Help mask values
		mask_values_help['help_id'] = help_id;
		mask_values_help['help_class'] = class_help_array.join(' ');
		mask_values_help['help'] = help;

		mask_values_field['help_class'] = class_help_array.join(' ');

		// Get invalid_feedback parameters
		var invalid_feedback_render = (is_submit ? false : this.get_object_meta_value(field, 'invalid_feedback_render', false, false, true));

		// Invalid feedback
		var invalid_feedback_last_row = false;

		if(invalid_feedback_render) {

			var mask_values_invalid_feedback = ($.extend(true, {}, mask_values_field));

			// Help ID
			var invalid_feedback_id = this.form_id_prefix + 'invalid-feedback-' + field.id + repeatable_suffix;

			// Invalid feedback classes
			var class_invalid_feedback_array = this.get_field_value_fallback(field.type, label_position, 'class_invalid_feedback', []);
			var invalid_feedback_last_row = this.get_field_value_fallback(field.type, label_position, 'invalid_feedback_last_row', false);

			// Get invalid feedback string
			var invalid_feedback = this.get_object_meta_value(field, 'invalid_feedback', '', false, true);

			if(invalid_feedback == '' && this.invalid_feedback_mask_placeholder != '') {

 				var invalid_feedback_label = field.label;

				// Parse invalid_feedback_mask_placeholder
				var invalid_feedback = this.invalid_feedback_mask_placeholder.replace_all('#label_lowercase', invalid_feedback_label.toLowerCase());
				invalid_feedback = invalid_feedback.replace_all('#label', invalid_feedback_label);
			}

			// Help mask values
			mask_values_invalid_feedback['invalid_feedback_id'] = invalid_feedback_id;
			mask_values_invalid_feedback['invalid_feedback_class'] = class_invalid_feedback_array.join(' ');
			mask_values_invalid_feedback['invalid_feedback'] = invalid_feedback;

			var invalid_feedback_parsed = this.mask_parse(mask_invalid_feedback, mask_values_invalid_feedback);

		} else {

			var invalid_feedback_id = false;
			var invalid_feedback_parsed = '';
		}

		mask_values_field['invalid_feedback'] = invalid_feedback_parsed;
		mask_values_field_label['invalid_feedback'] = invalid_feedback_parsed;

		// Field - Attributes
		mask_values_field['attributes'] = '';
		var mask_field_attributes = ($.extend(true, [], this.get_field_value_fallback(field.type, label_position, 'mask_field_attributes', [])));

		if(is_submit) {

			var mask_field_attributes = submit_attributes_field.filter(function(val) {

				return mask_field_attributes.indexOf(val) != -1;
			});
		}

		if(mask_field_attributes.length > 0) {
 			var get_attributes_return = this.get_attributes(field, mask_field_attributes);
			mask_values_field['attributes'] += ' '  + get_attributes_return.attributes;
			mask_field_attributes = get_attributes_return.mask_attributes;
			attributes_values_field = get_attributes_return.attribute_values;
		}

		// Field - Attributes - Custom
		var mask_field_attributes_custom = this.get_object_meta_value(field, 'custom_attributes', false);
		if(
			(mask_field_attributes_custom !== false) &&
			(typeof(mask_field_attributes_custom) === 'object') &&
			(mask_field_attributes_custom.length > 0)
		) {

			// Run through each custom attribute
			for(var mask_field_attributes_custom_index in mask_field_attributes_custom) {

				if(!mask_field_attributes_custom.hasOwnProperty(mask_field_attributes_custom_index)) { continue; }

				// Get custom attribute name/value pair
				var mask_field_attribute_custom = mask_field_attributes_custom[mask_field_attributes_custom_index];

				// Check attribute name exists
				if(mask_field_attribute_custom.custom_attribute_name == '') { continue; }

				// Build attribute (Only add value if one is specified)
				var attribute_custom = mask_field_attribute_custom.custom_attribute_name;
				if(mask_field_attribute_custom.custom_attribute_value != '') { attribute_custom += '="' + mask_field_attribute_custom.custom_attribute_value + '"'; }

				// Add to attributes array
				mask_values_field['attributes'] += ' ' + attribute_custom;
			}
		}

		// Field label - Attributes
		mask_values_field_label['attributes'] = '';
		var mask_field_label_attributes = ($.extend(true, [], this.get_field_value_fallback(field.type, label_position, 'mask_field_label_attributes', [])));

		if(is_submit) {

			var mask_field_label_attributes = submit_attributes_field_label.filter(function(val) {

				return mask_field_label_attributes.indexOf(val) != -1;
			});
		}

		if(mask_field_label_attributes.length > 0) {
 			var get_attributes_return = this.get_attributes(field, mask_field_label_attributes);
			mask_values_field_label['attributes'] += get_attributes_return.attributes;
			mask_field_label_attributes = get_attributes_return.mask_attributes;
		}

		// Mask values - Data
		var data = '';
		var data_source = this.get_object_value(field_type, 'data_source', false);
		var data_row_count = 0;

		var data_source_process = (data_source !== false);

		if(data_source_process) {

			// Get data source type
			if(typeof data_source.type === 'undefined') { data_source_process = false; } else { var data_source_type = data_source.type; }
			
			// Get data source ID
			if(typeof data_source.id === 'undefined') { data_source_process = false; } else {  var data_source_id = data_source.id; }
		}

		if(data_source_process) {

			// Get array of data
			switch(data_source_type) {

				case 'data_grid' :

					var data_source_object_data = this.get_object_meta_value(field, data_source_id, false);
					break;
			}
			if(data_source_object_data === false) { data_source_process = false; }

			// Columns
			if(typeof data_source_object_data.columns === 'undefined') { data_source_process = false; } else { var data_columns = data_source_object_data.columns; }
		}

		if(data_source_process) {

			// Data masks
			var mask_group 				=	this.get_field_value_fallback(field.type, label_position, 'mask_group', '');
			var mask_group_label 		=	this.get_field_value_fallback(field.type, label_position, 'mask_group_label', '');
			var mask_group_always		=	this.get_field_value_fallback(field.type, label_position, 'mask_group_always', false);

			var mask_row 				=	this.get_field_value_fallback(field.type, label_position, 'mask_row', '');
			var mask_row_placeholder	=	this.get_field_value_fallback(field.type, label_position, 'mask_row_placeholder', '');
			var mask_row_field			=	this.get_field_value_fallback(field.type, label_position, 'mask_row_field', '');

			// Mask row label can be defined at a framework level for field types to support inline and wrapping labels
			var mask_row_label 			=	this.get_field_value_fallback(field.type, label_position, 'mask_row_label', '');

			var mask_row_lookups 		=	this.get_field_value_fallback(field.type, label_position, 'mask_row_lookups', []);
			var datagrid_column_value 	=	this.get_field_value_fallback(field.type, label_position, 'datagrid_column_value', false);

			var mask_row_default 		= 	this.get_field_value_fallback(field.type, label_position, 'mask_row_default', '');
			var mask_row_required 		= 	this.get_field_value_fallback(field.type, label_position, 'mask_row_required', ' required data-required');
			var mask_row_disabled 		= 	this.get_field_value_fallback(field.type, label_position, 'mask_row_disabled', ' disabled');
			var mask_row_visible 		= 	this.get_field_value_fallback(field.type, label_position, 'mask_row_visible', ' visible');

			// Randomize rows
			var rows_randomize = this.get_object_meta_value(field, 'data_grid_rows_randomize', '', false, true);

			// Placeholder row (e.g. Adds Select... as the first row)
			var placeholder_row = this.get_object_meta_value(field, 'placeholder_row', '', false, true);
			if(placeholder_row != '') {

				// Inject placeholder row
				var mask_values_row_placeholder = $.extend(true, {}, mask_values_field);
				mask_values_row_placeholder['value'] = placeholder_row;
				data += this.mask_parse(mask_row_placeholder, mask_values_row_placeholder);
			}

			// Value should be an array
			if(has_value && (typeof(value) !== 'object')) { value = [value]; }

			// Build mask lookup cache
			var mask_row_lookup_array = [];

			// Run through each data mask field
			for(var mask_row_lookup_key in mask_row_lookups) {

				if(!mask_row_lookups.hasOwnProperty(mask_row_lookup_key)) { continue; }

				// Read data mask field value (this will be the ID for that data grid column)
				var mask_row_lookup 		= mask_row_lookups[mask_row_lookup_key];
				var mask_row_lookup_value 	= this.get_object_meta_value(field, mask_row_lookup, false, false, true);

				// If not found, skip this data mask field
				if(mask_row_lookup_value === false) { continue; }

				// Run through the data grid columns until we find the ID
				var data_column_index = false;
				for(var data_columns_index in data_columns) {

					if(!data_columns.hasOwnProperty(data_columns_index)) { continue; }

					var data_column = data_columns[data_columns_index];
					var data_column_id = data_column.id;

					// Match found, store in cache
					if(data_column_id == mask_row_lookup_value) { data_column_index = data_columns_index; break; }
				}

				if(data_column_index) { mask_row_lookup_array[mask_row_lookup] = data_column_index; }
			}
			// Read groups
			if(typeof data_source_object_data.groups === 'undefined') { this.error('error_data_source_groups'); return ''; }
			var data_groups = data_source_object_data.groups;
			var data_groups_count = data_groups.length;

			// Randomize data groups?
			if(rows_randomize) { data_groups = this.array_randomize(data_groups); }

			// Cycle through groups
			for(var data_group_index in data_groups) {

				if(!data_groups.hasOwnProperty(data_group_index)) { continue; }

				// Mask values
				var mask_values_group = $.extend(true, {}, mask_values_field);
				mask_values_group['group_id'] = this.form_id_prefix + 'datagrid-' + field.id + '-group-' + data_group_index + repeatable_suffix;

				// Get group
				var data_group = data_groups[data_group_index];

				// Get group label
				if(typeof(data_group.label) === 'undefined') { this.error('error_data_group_label'); return ''; }
				var mask_values_group_label_render = (typeof(data_group.label_render) === 'undefined') ? true : data_group.label_render;

				// Group label mask values
				if(mask_values_group_label_render) {

					var mask_values_group_label = $.extend(true, {}, mask_values_field);
					mask_values_group_label['group_label'] = this.html_encode(data_group.label);
					mask_values_group_label['label_row_id'] = this.form_id_prefix + 'label-' + field.id + '-group-' + data_group_index + repeatable_suffix;

					// Parse group label mask to build group_label value
					mask_values_group['group_label'] = this.mask_parse(mask_group_label, mask_values_group_label);

				} else {

					mask_values_group['group_label'] = '';
				}

				// Get group disabled (optional)
				mask_values_group['disabled'] = (typeof(data_group.disabled) !== 'undefined') ? (data_group.disabled == 'on' ? ' disabled' : '') : '';

				// Should group data mask be used?
				var mask_group_use = ((typeof(data_group.mask_group) !== 'undefined') ? (data_group.mask_group == 'on') : false) || mask_group_always;

				// Should field label be hidden if groups are in use
				if(mask_group_use && mask_field_label_hide_group) { mask_field_label = ''; }

				// Get group rows (If there are no rows, data_group.rows = undefined)
				var group = '';
				if(typeof(data_group.rows) !== 'undefined') {

					// Clone data group rows
					var data_rows = JSON.parse(JSON.stringify(data_group.rows));

					// Add 'Select All' row
					var select_all_row = this.get_object_meta_value(field, 'select_all', '');
					if(((field.type == 'checkbox') || (field.type == 'price_checkbox')) && (select_all_row == 'on')) {

						// Mask values - Data mask fields
						var select_all_value_index = false;
						var select_all_label_index = false;
						for(var mask_row_lookup in mask_row_lookup_array) {

							if(!mask_row_lookup_array.hasOwnProperty(mask_row_lookup)) { continue; }

							var select_all_index = mask_row_lookup_array[mask_row_lookup];

							if(mask_row_lookup == 'checkbox_field_value') { var select_all_value_index = select_all_index; }
							if(mask_row_lookup == 'checkbox_field_label') { var select_all_label_index = select_all_index; }
							if(mask_row_lookup == 'checkbox_price_field_value') { var select_all_value_index = select_all_index; }
							if(mask_row_lookup == 'checkbox_price_field_label') { var select_all_label_index = select_all_index; }
						}

						// Inject new row
						var select_all_row = {

							id: 0,
							default: '',
							required: '',
							hidden: '',
							disabled: '',
							select_all: true,
							data: []
						}
						var select_all_label = this.get_object_meta_value(field, 'select_all_label', '');
						if(select_all_label == '') { select_all_label = this.language('select_all_label'); }
						select_all_row.data[select_all_value_index] = select_all_label;
						select_all_row.data[select_all_label_index] = select_all_label;
						data_rows.unshift(select_all_row);
					}

					// Randomize data rows?
					if(rows_randomize) { data_rows = this.array_randomize(data_rows); }

					// Cycle through rows
					for(var data_row_index in data_rows) {

						if(!data_rows.hasOwnProperty(data_row_index)) { continue; }

						// Get row of data from data grid
						var data_row = data_rows[data_row_index];

						// Is this the last row?
						var last_row = !invalid_feedback_last_row || (data_row_index == (data_rows.length - 1));

						// Mask values
						var mask_values_row = $.extend(true, {}, mask_values_field);

						// Mask values - Data mask fields
						for(var mask_row_lookup in mask_row_lookup_array) {

							if(!mask_row_lookup_array.hasOwnProperty(mask_row_lookup)) { continue; }

							var data_column_index = mask_row_lookup_array[mask_row_lookup];
							var mask_row_lookup_value = this.parse_variables_process(data_row['data'][data_column_index]);

							// HTML version of lookup (This is used for encoding labels in values, e.g. price_select option values)
							mask_values_row[mask_row_lookup + '_html'] = this.html_encode(mask_row_lookup_value);

							// Check for HTML encoding
							var price = false;
							if(typeof($.WS_Form.meta_keys[mask_row_lookup]) !== 'undefined') {

								var mask_row_lookup_config = $.WS_Form.meta_keys[mask_row_lookup];

								var html_encode = (typeof(mask_row_lookup_config['h']) !== 'undefined') ? mask_row_lookup_config['h'] : false;
								if(html_encode) { mask_row_lookup_value = this.html_encode(mask_row_lookup_value); }

								var price = (typeof(mask_row_lookup_config['pr']) !== 'undefined') ? mask_row_lookup_config['pr'] : false;
							}

							if(price) {

								var mask_row_lookup_value_number = this.get_number(mask_row_lookup_value);
								mask_values_row[mask_row_lookup + '_currency'] = this.get_price(mask_row_lookup_value_number);
								mask_values_row[mask_row_lookup] = mask_row_lookup_value_number;

							} else {

								mask_values_row[mask_row_lookup] = mask_row_lookup_value;
							}
						}

						// Check for row value mask (Used by price_select, price_radio and price_checkbox)
						if(typeof(field_type['mask_row_value']) !== 'undefined') {

							mask_values_row['row_value'] = this.mask_parse(field_type['mask_row_value'], mask_values_row);
						}

						// Mask values row
						mask_values_row['row_id'] = this.form_id_prefix + 'field-' + field.id + '-row-' + data_row['id'] + repeatable_suffix;
						mask_values_row['data_id'] = data_row['id'];

						// Copy to row label and field mask values
						var mask_values_row_field = $.extend(true, {}, mask_values_row);
						var mask_values_row_label = $.extend(true, {}, mask_values_row);
						mask_values_row_label['label_id'] = this.form_id_prefix + 'label-' + field.id + repeatable_suffix;
						mask_values_row_label['label_row_id'] = this.form_id_prefix + 'label-' + field.id + '-row-' + data_row['id'] + repeatable_suffix;

						// Build default extra values
						var extra_values_default = [];
						if(
							(!has_value && data_row['default']) ||
							(has_value && (datagrid_column_value !== false) && (value.indexOf(mask_values_row[datagrid_column_value]) > -1)) ||
							(has_value && (typeof(mask_values_row['row_value']) !== 'undefined') && (value.indexOf(mask_values_row['row_value']) > -1))
						) { extra_values_default['default'] = mask_row_default; }
						if(data_row['disabled']) { extra_values_default['disabled'] = mask_row_disabled; }
						if(data_row['required']) { extra_values_default['required'] = mask_row_required; }

						mask_values_row['attributes'] = '';
						mask_values_row_label['attributes'] = '';
						mask_values_row_field['attributes'] = '';

						// Row - Attributes
						var extra_values = $.extend(true, [], extra_values_default);

						if(!is_submit) {

							// class (Inline)
							var class_inline_array = class_inline ? this.get_field_value_fallback(field.type, label_position, 'class_inline', false) : false;
							if(class_inline_array !== false) { extra_values['class'] = class_inline_array.join(' '); }

							// class (Row)
							var class_row_array = this.get_field_value_fallback(field.type, label_position, 'class_row', false)
							if(class_row_array !== false) { extra_values['class'] = (class_inline_array !== false) ? (extra_values['class'] + ' ' + class_row_array.join(' ')) : class_row_array.join(' '); }

							// class if disabled
							if(data_row['disabled']) {
								var class_row_disabled_array = this.get_field_value_fallback(field.type, label_position, 'class_row_disabled', false);
								if(class_row_disabled_array !== false) { extra_values['class'] += ' ' + class_row_disabled_array.join(' '); }
							}
						}

						var mask_row_attributes = ($.extend(true, [], this.get_field_value_fallback(field.type, label_position, 'mask_row_attributes', [])));
						if(mask_row_attributes.length > 0) {
				 			var get_attributes_return = this.get_attributes(field, mask_row_attributes, extra_values);
							mask_values_row['attributes'] += ' ' + get_attributes_return.attributes;
						}

						// Skip hidden rows
						if((typeof data_row['hidden'] !== 'undefined') && data_row['hidden'] && !is_submit) {

							switch(field.type) {

								// Select
								case 'select' :

									continue;

								// Checkboxes and radios
								default :

									mask_values_row['attributes'] += ' style="display:none;"';
							}
						}

						// Row - Label - Attributes
						var extra_values = $.extend(true, [], extra_values_default);

						// Class
						var class_row_field_label_array = this.get_field_value_fallback(field.type, label_position, 'class_row_field_label', false);
						if(class_row_field_label_array !== false) { extra_values['class'] = class_row_field_label_array.join(' '); }

						var mask_row_label_attributes = ($.extend(true, [], this.get_field_value_fallback(field.type, label_position, 'mask_row_label_attributes', [])));

						if(is_submit) {

							var mask_row_label_attributes = submit_attributes_field_label.filter(function(val) {

								return mask_row_label_attributes.indexOf(val) != -1;
							});
						}

						if(mask_row_label_attributes.length > 0) {
				 			var get_attributes_return = this.get_attributes(field, mask_row_label_attributes, extra_values);
							mask_values_row_label['attributes'] += ' ' + get_attributes_return.attributes;
						}

						// Row - Field - Attributes
						var extra_values = $.extend(true, [], extra_values_default);

						// class
						var class_row_field_array = this.get_field_value_fallback(field.type, label_position, 'class_row_field', false);
						if(class_row_field_array !== false) { extra_values['class'] = class_row_field_array.join(' '); }

						// class (Field setting)
						if(!is_submit && (class_field != '')) { extra_values['class'] += ' '  + $.trim(class_field); }

						// aria-labelledby
						extra_values['aria_labelledby'] = mask_values_row_label['label_row_id'];

						// Row attributes
						if(
							(!has_value && data_row['default']) ||
							(has_value && (datagrid_column_value !== false) && (value.indexOf(mask_values_row[datagrid_column_value]) > -1))
						) { extra_values['default'] = mask_row_default; }

						if(data_row['disabled']) { extra_values['disabled'] = mask_row_disabled; }
						if(data_row['required']) { extra_values['required'] = mask_row_required; }

						// Copy field level attributes to row
						extra_values['required_row'] = attributes_values_field['required'];

						// Build row field attributes
						var mask_row_field_attributes = ($.extend(true, [], this.get_field_value_fallback(field.type, label_position, 'mask_row_field_attributes', [])));

						if(is_submit) {

							var mask_row_field_attributes = submit_attributes_field.filter(function(val) {

								return mask_row_field_attributes.indexOf(val) != -1;
							});
						}

						if(mask_row_field_attributes.length > 0) {
				 			var get_attributes_return = this.get_attributes(field, mask_row_field_attributes, extra_values);
							mask_values_row_field['attributes'] += ' ' + get_attributes_return.attributes;
						}
						if(mask_values_row_field['attributes'] != '') { mask_values_row_field['attributes'] = ' ' + mask_values_row_field['attributes']; }

						if(typeof(data_row.select_all) !== 'undefined') {

							mask_values_row_field['attributes'] += ' data-select-all';
						}

						// Parse invalid feedback for rows
						if(invalid_feedback_render && !invalid_feedback_last_row) {

							invalid_feedback_id = this.form_id_prefix + 'invalid-feedback-' + field.id + '-row-' + data_row['id'] + repeatable_suffix;
							mask_values_invalid_feedback['invalid_feedback_id'] = invalid_feedback_id;
							if(invalid_feedback_render) {

								var invalid_feedback_parsed = this.mask_parse(mask_invalid_feedback, mask_values_invalid_feedback);

							} else {

								var invalid_feedback_parsed = '';
							}
						}

						// Invalid feedback
						mask_values_row_field['invalid_feedback'] = (last_row ? invalid_feedback_parsed : '');
						mask_values_row_label['invalid_feedback'] = (last_row ? invalid_feedback_parsed : '');

						// Parse field
						var row_field_html = this.mask_parse(mask_row_field, mask_values_row_field);
						mask_values_row_label['row_field'] = row_field_html;
						mask_values_row['row_field'] = row_field_html;

						// Parse label
						var row_field_label = this.mask_parse(mask_row_label, mask_values_row_label);
						mask_values_row['row_label'] = row_field_label;

						// Parse row
						group += this.mask_parse(mask_row, mask_values_row);

						// Increment data row count
						data_row_count++;
					}
				}

				if((mask_group !== false) && mask_group_use) {

					// Parse mask_group
					mask_values_group['group'] = group;
					data += this.mask_parse(mask_group, mask_values_group);

				} else {

					// Ignore group mask, there is only one group
					data += group;
				}
			}
		}

		// Add to mask array
		if(data_row_count > 0) {

			mask_values_field['data'] = data;

		} else {

			mask_values_field['data'] = '';
		}

		// Field - Attributes
		if(mask_field_attributes.length > 0) {

			var extra_values = [];

			// list
			if(typeof(mask_values_group) !== 'undefined') {
				if(
					(typeof(mask_values_group['group_id']) !== 'undefined') &&
					data_row_count > 0

				) { extra_values['list'] = mask_values_group['group_id']; }
			}

			// aria_labelledby (Used if aria_label is blank)
			var aria_label = this.get_object_meta_value(field, 'aria_label', false, false, true);
			if(aria_label === '') {

				if(
					label_render &&
					(mask_field_label.indexOf('#attributes') !== -1)	// Without attributes, we cannot reference the ID
				) {

					// Use aria_labelledby instead of aria_label
					extra_values['aria_labelledby'] = this.form_id_prefix + 'label-' + field.id + repeatable_suffix;

				} else {

					// Set to label
					extra_values['aria_label'] = field.label;
				}
			}

			// aria_describedby
			if(help !== '') { extra_values['aria_describedby'] = help_id; }

			// class (Config)
			var class_field_array = this.get_field_value_fallback(field.type, label_position, 'class_field', false);
			if(class_field_array !== false) { extra_values['class'] = class_field_array.join(' '); }

			// class (Field setting)
			if(class_field != '') { extra_values['class'] += ' '  + $.trim(class_field); }

			// Process attributes
 			var get_attributes_return = this.get_attributes(field, mask_field_attributes, extra_values);

 			// Store as mask value
 			if(get_attributes_return.attributes != '') { mask_values_field['attributes'] += ' ' + get_attributes_return.attributes; }
		}

		// Field Label - Attributes
		if(mask_field_label_attributes.length > 0) {

			var extra_values = [];

			// class
			var class_field_label_array = this.get_field_value_fallback(field.type, label_position, 'class_field_label', false);
			if(class_field_label_array !== false) { extra_values['class'] = class_field_label_array.join(' '); }

			// Process attributes
 			var get_attributes_return = this.get_attributes(field, mask_field_label_attributes, extra_values);

 			// Store as mask value
 			if(get_attributes_return.attributes != '') { mask_values_field_label['attributes'] += ' ' + get_attributes_return.attributes; }

		}

		// Parse help mask append
		mask_values_help['help_append_separator'] = (help != '') ? this.mask_parse(mask_help_append_separator, mask_values_help) : '';
		if(mask_help_append != '') {

			var help_append_parsed = this.mask_parse(mask_help_append, mask_values_help);

		} else {

			var help_append_parsed = '';
		}
		mask_values_help['help_append'] = help_append_parsed;

		// Parse help mask
		var help_parsed = (help != '' || help_append_parsed != '') ? this.mask_parse(mask_help, mask_values_help) : '';
		mask_values_field['help'] = help_parsed;
		mask_values_field_label['help'] = help_parsed;

		// Parse field
		var field_parsed = this.mask_parse(mask_field, mask_values_field);
		var label_parsed = this.mask_parse(mask_field_label, mask_values_field_label);

		// Check to see if #field is in the label
		var field_in_label = (mask_field_label.indexOf('#field') !== -1);
		if(field_in_label) {

			// Render field in label
			mask_values_field_label['field'] = field_parsed;	// Make the field available in the label mask values
			label_parsed = this.mask_parse(mask_field_label, mask_values_field_label);

			// Finished with field
			field_parsed = '';
			mask_values_field_label['field'] = '';
		}

		// Check to see if the #label is in the field
		var label_in_field = (mask_field.indexOf('#label') !== -1);
		if(label_in_field) {

			// Render label in field
			mask_values_field['label'] = label_parsed;	// Make the label available in the field mask values
			field_parsed = this.mask_parse(mask_field, mask_values_field);

			// Finished with label
			label_parsed = '';
			mask_values_field['label'] = '';
		}

		// Parse field wrapper
		if(field_parsed != '' && !mask_wrappers_drop) {

			var mask_field_wrapper = this.get_field_value_fallback(field.type, label_position, 'mask_field_wrapper', false);
			if(mask_field_wrapper !== false) {
				var mask_field_wrapper_values = {'field': field_parsed};
				field_parsed = this.mask_parse(mask_field_wrapper, mask_field_wrapper_values);
			}
		}

		// Parse label wrapper
		if(label_parsed != '' && !mask_wrappers_drop) {

			var mask_field_label_wrapper = this.get_field_value_fallback(field.type, label_position, 'mask_field_label_wrapper', false);
			if(mask_field_label_wrapper !== false) {
				var mask_field_label_wrapper_values = {'label': label_parsed};
				label_parsed = this.mask_parse(mask_field_label_wrapper, mask_field_label_wrapper_values);
			}
		}

		// Build field mask based upon label position
		field = '';

		switch(label_position) {

			// Bottom / Right
			case 'bottom' :
			case 'right' :

				field += field_parsed + label_parsed;
				break;

			// Top / Left / None
			default :

				field += label_parsed + field_parsed;
				break;
		}

		// Final parse
		mask_values_field['field'] = field;
		field_html = this.mask_parse(mask, mask_values_field);

		return field_html;
	}

	// Get meta keys associated with a field type
	$.WS_Form.prototype.field_type_meta_keys = function(field_type, config_filter) {

		if(typeof(config_filter) === 'undefined') { var config_filter = false; }

		var field_type_meta = [];

		// Get fieldsets
		var fieldsets = field_type.fieldsets;

		for(var key in fieldsets) {

			if(!fieldsets.hasOwnProperty(key)) { continue; }

			var fieldset = fieldsets[key];

			// Render fieldset variables
			if(typeof fieldset.meta_keys !== 'undefined') {

				for(var key in fieldset.meta_keys) {

					if(!fieldset.meta_keys.hasOwnProperty(key)) { continue; }

					var meta_key = fieldset.meta_keys[key];

					if(config_filter) {

						// Get meta key config
						if(typeof($.WS_Form.meta_keys[meta_key]) === 'undefined') { continue; }
						var meta_key_config = $.WS_Form.meta_keys[meta_key];

						var config_filter_true = (typeof(meta_key_config[config_filter]) !== 'undefined') ? meta_key_config[config_filter] : false;
						if(config_filter_true) { field_type_meta.push(meta_key); }	

					} else {

						field_type_meta.push(meta_key);
					}
				}
			}

			// Render child fieldset
			if(typeof fieldset.fieldsets !== 'undefined') {

				var field_type_meta_keys_return = this.field_type_meta_keys(fieldset, config_filter);
				if(field_type_meta_keys_return.length > 0) { field_type_meta = field_type_meta.concat(field_type_meta_keys_return); }
			}
		}

		return field_type_meta;
	}

	$.WS_Form.prototype.set_invalid_feedback = function(obj, invalid_feedback_obj, value, object_id, object_row_id) {

		if(typeof(object_row_id) === 'undefined') { var object_row_id = 0; }

		// HTML 5 custom validity
		if(obj.length) { obj[0].setCustomValidity(value); }

		// Invalid feedback text
		if(invalid_feedback_obj.length) {

			if(typeof(this.invalid_feedback_cache[object_id]) == 'undefined') {

				this.invalid_feedback_cache[object_id] = [];
			}

			if(value == '') {

				// Recall old invalid feedback if it exists						
				if(typeof(this.invalid_feedback_cache[object_id][object_row_id]) !== 'undefined') {

					invalid_feedback_obj.html(this.invalid_feedback_cache[object_id]);
				}

			} else {

				// Remember old invalid feedback
				if(typeof(this.invalid_feedback_cache[object_id][object_row_id]) === 'undefined') {

					this.invalid_feedback_cache[object_id][object_row_id] = invalid_feedback_obj.html();
				}

				// Set invalid feedback
				invalid_feedback_obj.html(value);
			}
		}
	}

	$.WS_Form.prototype.get_attributes = function(object, mask_attributes, extra_values) {

		if(typeof(extra_values) !== 'object') { var extra_values = false; }

		// Build attributes array
		var attributes = [];
		var attribute_values = [];

		if(mask_attributes !== false) {

			for(var mask_attributes_key in mask_attributes) {

				if(!mask_attributes.hasOwnProperty(mask_attributes_key)) { continue; }

				var mask_attribute_meta_key = mask_attributes[mask_attributes_key];

				// Skip unknown meta_keys
				if(typeof $.WS_Form.meta_keys[mask_attribute_meta_key] === 'undefined') { continue; }

				var meta_key = $.WS_Form.meta_keys[mask_attribute_meta_key];

				// Read meta key mask data
				if(this.is_admin) {
	
					var meta_key_mask = (typeof meta_key.mask !== 'undefined') ? meta_key.mask : '';
					var meta_key_mask_disregard_on_empty = (typeof meta_key.mask_disregard_on_empty !== 'undefined') ? meta_key.mask_disregard_on_empty : false;
					var meta_key_mask_disregard_on_zero = (typeof meta_key.mask_disregard_on_zero !== 'undefined') ? meta_key.mask_disregard_on_zero : false;

				} else {

					var meta_key_mask = (typeof meta_key.m !== 'undefined') ? meta_key.m : '';
					var meta_key_mask_disregard_on_empty = (typeof meta_key.e !== 'undefined') ? meta_key.e : false;
					var meta_key_mask_disregard_on_zero = (typeof meta_key.z !== 'undefined') ? meta_key.z : false;

				}

				if(extra_values !== false) {

					// Use extra values
					if(typeof extra_values[mask_attribute_meta_key] !== 'undefined') {

						var meta_value = extra_values[mask_attribute_meta_key];

					} else {

						var meta_value = '';
					}

				} else {

					// If meta_key key parameter is set, use that to get the object meta value
					if(this.is_admin) {

						var get_object_meta_value_key = (typeof meta_key.key !== 'undefined') ? meta_key.key : mask_attribute_meta_key;

					} else {

						var get_object_meta_value_key = (typeof meta_key.k !== 'undefined') ? meta_key.k : mask_attribute_meta_key;
					}

					// Get value
					var meta_value = this.get_object_meta_value(object, get_object_meta_value_key, '', false, true);

					// Remember value
					attribute_values[get_object_meta_value_key] = meta_value;
				}

				// HTML encode value
				meta_value = this.html_encode(meta_value);

				// Parse mask
				var attribute_meta_value = this.mask_parse(meta_key_mask, {'value': meta_value});

				// Push attribute key value pair to attributes array
				if(
					((meta_key_mask_disregard_on_empty && (meta_value != '')) || !meta_key_mask_disregard_on_empty)
					&&
					((meta_key_mask_disregard_on_zero && (parseInt(meta_value) != 0)) || !meta_key_mask_disregard_on_zero)

				) {

					// Push field to attribute array
					attributes.push(attribute_meta_value);

					// Remove this key from the array (so that it is not re-processed further down)
					delete mask_attributes[mask_attributes_key];
				}
			}
		}

		// Remove empty elements
		mask_attributes = $.grep(mask_attributes,function(n){ return n == 0 || n });

		// Return field attributes string
		return {

			'attributes': 		attributes.join(' '),
			'mask_attributes': 	mask_attributes,
			'attribute_values': attribute_values
		};
	}

	$.WS_Form.prototype.get_tel = function(tel_input) {
	
		return tel_input.replace(/[^+\d]+/g, "");
	}

	$.WS_Form.prototype.get_number = function(number_input, default_value) {

		if(typeof(default_value) === 'undefined') { default_value = 0; }

		number_input = $.trim(number_input);
		number_input = $("<div/>").html(number_input).text();
		number_input = number_input.replace(/[^0-9-.]/g, '');

		return ($.trim(number_input) == '') ? default_value : (isNaN(number_input)) ? default_value : parseFloat(number_input);
	}

	$.WS_Form.prototype.get_currency = function() {

		var return_obj = {};

		var price_decimals = parseInt($.WS_Form.settings_plugin.price_decimals);

		return_obj.prefix = ws_form_settings.currency_symbol;
		return_obj.suffix = '';

		var currency_position = $.WS_Form.settings_plugin.currency_position;
		switch(currency_position) {

			case 'right' :

				return_obj.prefix = '';
				return_obj.suffix = ws_form_settings.currency_symbol;
				break;

			case 'left_space' :

				return_obj.prefix = ws_form_settings.currency_symbol + ' ';
				break;

			case 'right_space' :

				return_obj.prefix = '';
				return_obj.suffix = ' ' + ws_form_settings.currency_symbol;
				break;
		}

		// Price decimals
		return_obj.decimals = (price_decimals < 0) ? 0 : price_decimals;

		// Separators
		return_obj.decimal_separator = $.WS_Form.settings_plugin.price_decimal_separator;
		return_obj.thousand_separator = $.WS_Form.settings_plugin.price_thousand_separator;

		return return_obj;
	}

	$.WS_Form.prototype.get_price = function(price, currency, currency_symbol_render) {

		if(typeof(currency) === 'undefined') { var currency = this.get_currency(); }
		if(typeof(currency_symbol_render) === 'undefined') { var currency_symbol_render = true; }

		return (currency_symbol_render ? currency.prefix : '') + parseFloat(price).toFixed(currency.decimals).replace(/\B(?=(\d{3})+(?!\d))/g, currency.thousand_separator).replace('.', currency.decimal_separator) + (currency_symbol_render ? currency.suffix : '');
	}

	// Insert text into an input
	$.WS_Form.prototype.input_insert_text = function(input, text) {

		// Get meta_key
		var meta_key = input.attr('data-meta-key-type');

		switch(meta_key) {

			case 'text_editor' :

				tinymce.activeEditor.execCommand('mceInsertContent', false, text);

   				break;

			case 'html_editor' :

				var code_editor = $('.CodeMirror')[0].CodeMirror;
				var code_editor_doc = code_editor.getDoc();
				var code_editor_cursor = code_editor_doc.getCursor();
				code_editor_doc.replaceRange(text, code_editor_cursor);

   				break;
		}

		// Inject text (Do this regardless in case CodeMirror or TinyMCE did not initiate)
		var caret_position = input[0].selectionStart;
		var input_val = input.val();
		input.val(input_val.substring(0, caret_position) + text + input_val.substring(caret_position) );

		// Focus back on input
		input.focus();

		// Set new caret position
		var new_caret_position = caret_position + text.length;
		if(input.prop('selectionStart') !== null) { input.prop('selectionStart', new_caret_position); }
		if(input.prop('selectionEnd') !== null) { input.prop('selectionEnd', new_caret_position); }
	}

	// Highlight menu
	$.WS_Form.prototype.menu_highlight = function(page) {

		if(typeof(page) === 'undefined') { var page = 'ws-form'; }

		// Highlight menu
		$('#toplevel_page_' + page).removeClass('wp-not-current-submenu').addClass('wp-has-current-submenu current').addClass('selected');
		$('[href="admin.php?page=' + page + '"]', $('#toplevel_page_' + page)).closest('li').addClass('wp-menu-open current');
	}

	// Get website URL
	$.WS_Form.prototype.get_plugin_website_url = function(path, medium) {

		if(typeof(path) === 'undefined') { var path = ''; }
		var medium_parameter = (typeof(medium) == 'undefined') ? '' : '&utm_medium=' + medium;
		return 'https://wsform.com' + path + '?utm_source=ws_form' + medium_parameter;
	}

	// Add hidden field to canvas
	$.WS_Form.prototype.form_add_hidden_input = function(name, value, id, attributes, single_quote) {

		// Do not add if it already exists
		var obj = $('input[name="' + name + '"]', this.form_canvas_obj);
		if(obj.length && (name.indexOf('[]') === -1)) {

			// Just set value if already exists and it is not an array
			obj.val(value);
			return;
		}

		// Check function attributes
		if(typeof(value) === 'undefined') { var value = ''; }
		if(typeof(id) === 'undefined') { var id = false; }
		if(typeof(attributes) === 'undefined') { var attributes = false; }
		if(typeof(single_quote) === 'undefined') { var single_quote = false; }

		// Append to form
		this.form_canvas_obj.append('<input type="hidden" name="' + name + '" value=' + (single_quote ? "'" : '"') + value + (single_quote ? "'" : '"') + ((id !== false) ? (' id="' + id + '"') : '') + ((attributes !== false) ? (' ' + attributes) : '') + ' />');
	}

	// mod_security fix
	$.WS_Form.prototype.mod_security_fix = function(input_string) {

		var output_string = input_string.replace_all('#', '~%23~');

		return output_string;
	}

})(jQuery);

// String - Replace all function
if(typeof String.prototype.replace_all !== 'function') {

	String.prototype.replace_all = function (search, replace) {

		if (replace === undefined) {
			return this.toString();
		}
		return this.split(search).join(replace);
	}
}

// String - Score
if(typeof String.prototype.score !== 'function') {

	String.prototype.score = function (word, fuzziness) {

		'use strict';

		// If the string is equal to the word, perfect match.
		if (this === word) { return 1; }

		//if it's not a perfect match and is empty return 0
		if (word === "") { return 0; }

		var runningScore = 0,
		charScore,
		finalScore,
		string = this,
		lString = string.toLowerCase(),
		strLength = string.length,
		lWord = word.toLowerCase(),
		wordLength = word.length,
		idxOf,
		startAt = 0,
		fuzzies = 1,
		fuzzyFactor,
		i;

		// Cache fuzzyFactor for speed increase
		if (fuzziness) { fuzzyFactor = 1 - fuzziness; }

		// Walk through word and add up scores.
		// Code duplication occurs to prevent checking fuzziness inside for loop
		if (fuzziness) {
			for (i = 0; i < wordLength; i+=1) {

				// Find next first case-insensitive match of a character.
				idxOf = lString.indexOf(lWord[i], startAt);

				if (idxOf === -1) {

					fuzzies += fuzzyFactor;

				} else {

					if (startAt === idxOf) {

						// Consecutive letter & start-of-string Bonus
						charScore = 0.7;

					} else {

						charScore = 0.1;

						// Acronym Bonus
						// Weighing Logic: Typing the first character of an acronym is as if you
						// preceded it with two perfect character matches.
						if (string[idxOf - 1] === ' ') { charScore += 0.8; }
					}

					// Same case bonus.
					if (string[idxOf] === word[i]) { charScore += 0.1; }

					// Update scores and startAt position for next round of indexOf
					runningScore += charScore;
					startAt = idxOf + 1;
				}
			}

		} else {

			for (i = 0; i < wordLength; i+=1) {

				idxOf = lString.indexOf(lWord[i], startAt);

				if (-1 === idxOf) { return 0; }

				if (startAt === idxOf) {
					charScore = 0.7;
				} else {
					charScore = 0.1;
					if (string[idxOf - 1] === ' ') { charScore += 0.8; }
				}

				if (string[idxOf] === word[i]) { charScore += 0.1; }

				runningScore += charScore;
				startAt = idxOf + 1;
			}
		}

		// Reduce penalty for longer strings.
		finalScore = 0.5 * (runningScore / strLength    + runningScore / wordLength) / fuzzies;

		if ((lWord[0] === lString[0]) && (finalScore < 0.85)) {
			finalScore += 0.15;
		}

		return finalScore;
	};
}

// Date - Format
if(typeof Date.prototype.format !== 'function') {

  // Defining locale
  Date.shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
  Date.longMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
  Date.shortDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
  Date.longDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']

  // Defining patterns
  var replaceChars = {

    // Day
    d: function () { var d = this.getDate(); return (d < 10 ? '0' : '') + d },
    D: function () { return Date.shortDays[this.getDay()] },
    j: function () { return this.getDate() },
    l: function () { return Date.longDays[this.getDay()] },
    N: function () { var N = this.getDay(); return (N === 0 ? 7 : N) },
    S: function () { var S = this.getDate(); return (S % 10 === 1 && S !== 11 ? 'st' : (S % 10 === 2 && S !== 12 ? 'nd' : (S % 10 === 3 && S !== 13 ? 'rd' : 'th'))) },
    w: function () { return this.getDay() },
    z: function () { var d = new Date(this.getFullYear(), 0, 1); return Math.ceil((this - d) / 86400000) },

    // Week
    W: function () {
      var target = new Date(this.valueOf())
      var dayNr = (this.getDay() + 6) % 7
      target.setDate(target.getDate() - dayNr + 3)
      var firstThursday = target.valueOf()
      target.setMonth(0, 1)
      if (target.getDay() !== 4) {
        target.setMonth(0, 1 + ((4 - target.getDay()) + 7) % 7)
      }
      var retVal = 1 + Math.ceil((firstThursday - target) / 604800000)

      return (retVal < 10 ? '0' + retVal : retVal)
    },

    // Month
    F: function () { return Date.longMonths[this.getMonth()] },
    m: function () { var m = this.getMonth(); return (m < 9 ? '0' : '') + (m + 1) },
    M: function () { return Date.shortMonths[this.getMonth()] },
    n: function () { return this.getMonth() + 1 },
    t: function () {
      var year = this.getFullYear()
      var nextMonth = this.getMonth() + 1
      if (nextMonth === 12) {
        year = year++
        nextMonth = 0
      }
      return new Date(year, nextMonth, 0).getDate()
    },

    // Year
    L: function () { var L = this.getFullYear(); return (L % 400 === 0 || (L % 100 !== 0 && L % 4 === 0)) },
    o: function () { var d = new Date(this.valueOf()); d.setDate(d.getDate() - ((this.getDay() + 6) % 7) + 3); return d.getFullYear() },
    Y: function () { return this.getFullYear() },
    y: function () { return ('' + this.getFullYear()).substr(2) },

    // Time
    a: function () { return this.getHours() < 12 ? 'am' : 'pm' },
    A: function () { return this.getHours() < 12 ? 'AM' : 'PM' },
    B: function () { return Math.floor((((this.getUTCHours() + 1) % 24) + this.getUTCMinutes() / 60 + this.getUTCSeconds() / 3600) * 1000 / 24) },
    g: function () { return this.getHours() % 12 || 12 },
    G: function () { return this.getHours() },
    h: function () { var h = this.getHours(); return ((h % 12 || 12) < 10 ? '0' : '') + (h % 12 || 12) },
    H: function () { var H = this.getHours(); return (H < 10 ? '0' : '') + H },
    i: function () { var i = this.getMinutes(); return (i < 10 ? '0' : '') + i },
    s: function () { var s = this.getSeconds(); return (s < 10 ? '0' : '') + s },
    v: function () { var v = this.getMilliseconds(); return (v < 10 ? '00' : (v < 100 ? '0' : '')) + v },

    // Timezone
    e: function () { return Intl.DateTimeFormat().resolvedOptions().timeZone },
    I: function () {
      var DST = null
      for (var i = 0; i < 12; ++i) {
        var d = new Date(this.getFullYear(), i, 1)
        var offset = d.getTimezoneOffset()

        if (DST === null) DST = offset
        else if (offset < DST) { DST = offset; break } else if (offset > DST) break
      }
      return (this.getTimezoneOffset() === DST) | 0
    },
    O: function () { var O = this.getTimezoneOffset(); return (-O < 0 ? '-' : '+') + (Math.abs(O / 60) < 10 ? '0' : '') + Math.floor(Math.abs(O / 60)) + (Math.abs(O % 60) === 0 ? '00' : ((Math.abs(O % 60) < 10 ? '0' : '')) + (Math.abs(O % 60))) },
    P: function () { var P = this.getTimezoneOffset(); return (-P < 0 ? '-' : '+') + (Math.abs(P / 60) < 10 ? '0' : '') + Math.floor(Math.abs(P / 60)) + ':' + (Math.abs(P % 60) === 0 ? '00' : ((Math.abs(P % 60) < 10 ? '0' : '')) + (Math.abs(P % 60))) },
    T: function () { var tz = this.toLocaleTimeString(navigator.language, {timeZoneName: 'short'}).split(' '); return tz[tz.length - 1] },
    Z: function () { return -this.getTimezoneOffset() * 60 },

    // Full Date/Time
    c: function () { return this.format('Y-m-d\\TH:i:sP') },
    r: function () { return this.toString() },
    U: function () { return Math.floor(this.getTime() / 1000) }
  }

  // Simulates PHP's date function
  Date.prototype.format = function (format) {

    var date = this
    return format.replace(/(\\?)(.)/g, function (_, esc, chr) {
      return (esc === '' && replaceChars[chr]) ? replaceChars[chr].call(date) : chr
    })
  }
}
