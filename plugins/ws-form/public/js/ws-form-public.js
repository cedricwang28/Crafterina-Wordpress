(function($) {

	'use strict';

	// Set is_admin
	$.WS_Form.prototype.set_is_admin = function() { return false; }

	// One time init for admin page
	$.WS_Form.prototype.init = function() {

		// Build data cache
		this.data_cache_build();

		// Set global variables once for performance
		this.set_globals();
	}

	// Continue initialization after submit data retrieved
	$.WS_Form.prototype.init_after_get_submit = function(submit_retrieved) {


		// Build form
		this.form_build();
	}

	// Set global variables once for performance
	$.WS_Form.prototype.set_globals = function() {

		// Get framework ID
		this.framework_id = $.WS_Form.settings_plugin.framework;

		// Get framework settings
		this.framework = $.WS_Form.frameworks.types[this.framework_id];

		// Get current framework
		this.framework_fields = this.framework['fields']['public'];

		// Get invalid_feedback placeholder mask
		this.invalid_feedback_mask_placeholder = '';
		if(typeof($.WS_Form.meta_keys['invalid_feedback']) !== 'undefined') {

			if(typeof $.WS_Form.meta_keys['invalid_feedback']['p'] !== 'undefined') {

				this.invalid_feedback_mask_placeholder = $.WS_Form.meta_keys['invalid_feedback']['p'];
			}
		}

		// Custom action URL
		this.form_action_custom = (this.form_obj.attr('action') != (ws_form_settings.url + 'submit'));

		// Get activated class
		var class_validated_array = (typeof this.framework.fields.public.class_form_validated !== 'undefined') ? this.framework.fields.public.class_form_validated : [];
		this.class_validated = class_validated_array.join(' ');


		// Set hash (Reset cookie with new expiry)
		this.hash_set(this.cookie_get('hash', ''), true);

		// Read submission data if hash is defined
		var ws_this = this;
		if(this.hash != '') {

			// Call AJAX request
			$.WS_Form.this.api_call('submit/hash/' + this.hash, 'GET', false, function(response) {

				// Save the submissions data
				ws_this.submit = response.data;

				ws_this.init_after_get_submit(true);

				// Finished with submit data
				ws_this.submit = false;

			}, function(response) {

				// Read auto populate data instead
				ws_this.read_json_populate();

				ws_this.init_after_get_submit(false);
			});

		} else {

			// Read auto populate data
			this.read_json_populate();

			this.init_after_get_submit(false);
		}
	}

	// Read auto populate data
	$.WS_Form.prototype.read_json_populate = function() {

		if(typeof(wsf_form_json_populate) !== 'undefined') {

			if(typeof(wsf_form_json_populate[this.form_id]) !== 'undefined') {

				this.submit_auto_populate = wsf_form_json_populate[this.form_id]['data'];
			}
		}
	}


	// Render an error message
	$.WS_Form.prototype.error = function(language_id, variable, error_class) {
		if (window.console && window.console.error) { console.error(this.language(language_id).replace(/%s/g, variable)); }
	}

	// Render any interface elements that rely on the form object
	$.WS_Form.prototype.form_render = function() {

		// Timer
		this.form_timer();


		// Initialize JS
		this.form_init_js();

		// Groups - Tabs - Initialize
		this.form_tabs();


		// Navigation
		this.form_navigation();

		// Client side form validation
		this.form_validation();

		// Select All
		this.form_select_all();

		// Required (Runs after form_conditional to avoid wsf-validate triggering too soon)
		this.form_required();

		// Input masks
		this.form_inputmask();

		// Text input and textarea character and word count
		this.form_character_word_count();

		// Spam protection
		this.form_spam_protection();


		// Form preview
		this.form_preview();

		// Form stats
		this.form_stat();

		// Trigger rendered event
		this.trigger('rendered');

		// Form validation - Real time
		this.form_validate_real_time();

	}

	$.WS_Form.prototype.form_timer = function() {

		// Timer
		this.date_start = this.cookie_get('date_start', false);
		if((this.date_start === false) || isNaN(this.date_start) || (this.date_start == '')) {

			this.date_start = new Date().getTime();
			this.cookie_set('date_start', this.date_start, false);
		}
	}

	// Trigger events
	$.WS_Form.prototype.trigger = function(slug) {

		// New method
		var action_type = 'wsf-' + slug;
		$(document).trigger(action_type, [this.form, this.form_id, this.form_instance_id, this.form_obj, this.form_canvas_obj]);

		// Legacy method - Instance
		var trigger_instance = 'wsf-' + slug + '-instance-' + this.form_instance_id;
		$(window).trigger(trigger_instance);

		// Legacy method - Form
		var trigger_form = 'wsf-' + slug + '-form-' + this.form_id;
		$(window).trigger(trigger_form);
	}

	// Initialize JS
	$.WS_Form.prototype.form_init_js = function() {

		// Check framework init_js
		if(typeof(this.framework.init_js) !== 'undefined') {

			// Framework init JS values
			var framework_init_js_values = {'form_canvas_selector': '#' + this.form_obj_id};
			var framework_init_js = this.mask_parse(this.framework.init_js, framework_init_js_values);

			$.globalEval('(function($) { $(function() {' + framework_init_js + '}) })(jQuery);');
		}
	}

	// Form - Reset
	$.WS_Form.prototype.form_reset = function(e) {

		// Trigger
		this.trigger('reset-before');

		// Unmark as validated
		this.form_obj.removeClass(this.class_validated);

		// HTML form reset
		this.form_obj[0].reset();
		// Trigger
		this.trigger('reset-complete');
	}

	// Form - Clear
	$.WS_Form.prototype.form_clear = function() {

		// Trigger
		this.trigger('clear-before');

		// Unmark as validated
		this.form_obj.removeClass(this.class_validated);

		// Clear fields
		for(var key in this.field_data_cache) {

			if(!this.field_data_cache.hasOwnProperty(key)) { continue; }

			var field = this.field_data_cache[key];

			// Only process on submit save fields
			var submit_save = this.get_field_value_fallback(field.type, false, 'submit_save', false);
			if(!submit_save) { continue; }

			var field_id = field.id;
			var field_selector = '#' + this.form_id_prefix + 'field-' + field.id;
			var field_name = this.field_name_prefix + field_id;

			// Clear value
			switch(field.type) {

				case 'checkbox' :
				case 'price_checkbox' :
				case 'radio' :
				case 'price_radio' :

					var field_trigger = ($('[name="' + field_name + '[]"]').is(':checked'));
					$('[name="' + field_name + '[]"]').prop('checked', false);
					break;

				case 'select' :
				case 'price_select' :

					var field_trigger = ($('[name="' + field_name + '[]"]').val() != '');
					$('[name="' + field_name + '[]"]').val('');
					break;

				case 'textarea' :

					var field_trigger = ($(field_selector).val() != '');
					$(field_selector).val('');
					this.textarea_set_value($(field_selector), '');
					break;

				case 'color' :

					var field_trigger = ($(field_selector).val() != '');
					$(field_selector).val('#000000');
					break;

				default:

					var field_trigger = ($(field_selector).val() != '');
					$(field_selector).val('');
			}

			if(field_trigger) {

				var field_type_config = $.WS_Form.field_type_cache[field.type];
				var trigger = (typeof(field_type_config.trigger) !== 'undefined') ? field_type_config.trigger : 'change';
				$(field_selector).trigger(trigger);
			}
		}

		// Trigger
		this.trigger('clear-complete');
	}

	// Form reload
	$.WS_Form.prototype.form_reload = function() {

		// Read submission data if hash is defined
		var ws_this = this;
		if(this.hash != '') {

			// Call AJAX request
			$.WS_Form.this.api_call('submit/hash/' + this.hash, 'GET', false, function(response) {

				// Save the submissions data
				ws_this.submit = response.data;

				ws_this.form_reload_after_get_submit(true);

				// Finished with submit data
				ws_this.submit = false;

			}, function(response) {

				ws_this.form_reload_after_get_submit(false);
			});

		} else {

			// Reset submit
			this.submit = false;

			this.form_reload_after_get_submit(false);
		}
	}

	// Form reload - After get submit
	$.WS_Form.prototype.form_reload_after_get_submit = function(submit_retrieved) {

		// Clear any messages
		$('[data-wsf-message][data-wsf-instance-id="' + this.form_instance_id + '"]').remove();

		// Show the form
		this.form_canvas_obj.show();

		// Reset form tag
		this.form_canvas_obj.removeClass(this.class_validated)

		// Clear ecommerce real time validation hooks
		this.form_validation_real_time_hooks = [];

		// Empty form object
		this.form_canvas_obj.empty();

		// Build form
		this.form_build();
	}

	// Form - Hash reset
	$.WS_Form.prototype.form_hash_clear = function() {

		// Clear hash variable
		this.hash = '';

		// Clear hash cookie
		this.cookie_clear('hash')

	}


	// Form navigation
	$.WS_Form.prototype.form_navigation = function() {

		var ws_this = this;
		var group_count = this.form.groups.length;

		// Buttons - Save
		$('[data-action="wsf-save"]:not([data-init-navigation])', this.form_canvas_obj).each(function() {

			// Flag so it only initializes once
			$(this).attr('data-init-navigation', '');

			// Click
			$(this).click(function() {

				ws_this.form_post('save');
			});
		});


		// Buttons - Next
		$('[data-action="wsf-tab_next"]:not([data-init-navigation])', this.form_canvas_obj).each(function() {

			// Flag so it only initializes once
			$(this).attr('data-init-navigation', '');

			// Get wrapper
			var button_wrapper = $(this).closest('[data-type="tab_next"]');

			// If there are no groups, remove the next button
			if(group_count == 0) { button_wrapper.remove(); }

			// Get group index
			var group_index = ws_this.get_group_index($(this));
			if(group_index === false) { return true; }

			// If the next button is on the last tab, disable it
			if(group_index == (group_count - 1)) { $(this).attr('disabled', ''); return true; }

			// Click
			$(this).click(function() {

				if(typeof($(this).attr('disabled')) === 'undefined') {

					ws_this.group_index_offset($(this), 1);
				}
			});
		});

		// Buttons - Previous
		$('[data-action="wsf-tab_previous"]:not([data-init-navigation])', this.form_canvas_obj).each(function() {

			// Flag so it only initializes once
			$(this).attr('data-init-navigation', '');

			// Get wrapper
			var button_wrapper = $(this).closest('[data-type="tab_previous"]');

			// If there are no groups, remove the previous button
			if(group_count == 0) { button_wrapper.remove(); }

			// Get group index
			var group_index = ws_this.get_group_index($(this));
			if(group_index === false) { return true; }

			// If the previous button is on the first tab, disable it
			if(group_index == 0) { $(this).attr('disabled', ''); return true; }

			// Click
			$(this).click(function() {

 				if(typeof($(this).attr('disabled')) === 'undefined') {

					ws_this.group_index_offset($(this), -1);
				}
			});
		});

		// Buttons - Reset
		$('[data-action="wsf-reset"]:not([data-init-navigation])', this.form_canvas_obj).each(function() {

			// Flag so it only initializes once
			$(this).attr('data-init-navigation', '');

			// Click
			$(this).click(function(e) {

				// Prevent default
				e.preventDefault();
				e.stopPropagation();

				ws_this.form_reset();
			});
		});

		// Buttons - Clear
		$('[data-action="wsf-clear"]:not([data-init-navigation])', this.form_canvas_obj).each(function() {

			// Flag so it only initializes once
			$(this).attr('data-init-navigation', '');

			// Click
			$(this).click(function() {

				ws_this.form_clear();
			});
		});
	}

	// Tab - Activate by offset amount
	$.WS_Form.prototype.group_index_offset = function(obj, group_index_offset) {

		// Get group index
		var group_index = this.get_group_index(obj);

		// Get next group_index
		var group_index_new = group_index + group_index_offset;

		// Activate tab
		this.group_index_set(group_index_new);

		// Get field ID
		var field_id = obj.closest('[data-id]').attr('data-id');
		var field = this.field_data_cache[field_id];
		var scroll_to_top = this.get_object_meta_value(field, 'scroll_to_top', '');
		var scroll_to_top_offset = this.get_object_meta_value(field, 'scroll_to_top_offset', '0');
		scroll_to_top_offset = (scroll_to_top_offset == '') ? 0 : parseInt(scroll_to_top_offset);
		var scroll_position = this.form_canvas_obj.offset().top - scroll_to_top_offset;

		switch(scroll_to_top) {

			// Instant
			case 'instant' :

				$('html,body').scrollTop(scroll_position);

				break;

			// Smooth
			case 'smooth' :

				var scroll_to_top_duration = this.get_object_meta_value(field, 'scroll_to_top_duration', '0');
				scroll_to_top_duration = (scroll_to_top_duration == '') ? 0 : parseInt(scroll_to_top_duration);

				$('html,body').animate({

					scrollTop: scroll_position

				}, scroll_to_top_duration);

				break;
		}
	}

	// Tab - Set
	$.WS_Form.prototype.group_index_set = function(group_index) {

		if(this.form.groups.length <= 1) { return false; }

		var framework_tabs = this.framework['tabs']['public'];

		if(typeof(framework_tabs.activate_js) !== 'undefined') {

			var activate_js = framework_tabs.activate_js;	

			if(activate_js != '') {

				// Parse activate_js
				var mask_values = {'form': '#' + this.form_obj_id, 'index': group_index};
				var activate_js_parsed = this.mask_parse(activate_js, mask_values);

				// Execute activate tab javascript
				$.globalEval('(function($) { $(function() {' + activate_js_parsed + '}); })(jQuery);');

				// Set cookie
				this.cookie_set('tab_index', group_index);
			}
		}

	}

	// Get tab index object resides in
	$.WS_Form.prototype.get_group_index = function(obj) {

		if(this.form.groups.length <= 1) { return false; }

		// Get group
		var group_single = obj.closest('[data-group-index]');
		if(group_single.length == 0) { return false; }

		// Get group index
		var group_index = group_single.first().attr('data-group-index');
		if(group_index == undefined) { return false; }

		return parseInt(group_index);
	}

	// Get section id object resides in
	$.WS_Form.prototype.get_section_id = function(obj) {

		var section_id = obj.closest('[id^="' + this.form_id_prefix + 'section-"]').attr('data-id');
		if(!section_id) { return false; }
		return parseInt(section_id);
	}

	// Get field id object resides in
	$.WS_Form.prototype.get_field_id = function(obj) {

		var field_id = obj.closest('[data-type]').attr('data-id');
		if(!field_id) { return false; }
		return parseInt(field_id);
	}


	// Form preview
	$.WS_Form.prototype.form_preview = function() {

		if(this.form_canvas_obj[0].hasAttribute("data-preview")) {

			this.form_add_hidden_input('wsf_preview', 'true');
		}
	}

	// Form spam protection
	$.WS_Form.prototype.form_spam_protection = function() {

		// Honeypot
		var honeypot = this.get_object_meta_value(this.form, 'honeypot', false);

		if(honeypot) {

			// Add honeypot field
			var honeypot_hash = (this.form.published_checksum != '') ? this.form.published_checksum : ('honeypot_unpublished_' + this.form_id);

			// Build honeypot input
			var framework_type = $.WS_Form.settings_plugin.framework;
			var framework = $.WS_Form.frameworks.types[framework_type];
			var fields = this.framework['fields']['public'];
			var honeypot_attributes = (typeof(fields.honeypot_attributes) !== 'undefined') ? ' ' + fields.honeypot_attributes.join(' ') : '';

			// Add to form
			this.form_add_hidden_input('field_' + honeypot_hash, '', false, 'autocomplete="off"' + honeypot_attributes);

			// Hide it
			var honeypot_obj = $('[name="field_' + honeypot_hash + '"]', this.form_canvas_obj);
			honeypot_obj.css({'position': 'absolute', 'left': '-9999em'});

		}
	}


	// Adds required string (if found in framework config) to all labels
	$.WS_Form.prototype.form_required = function() {

		// Get required label HTML
		var label_required = this.get_object_meta_value(this.form, 'label_required', false);
		if(!label_required) { return false; }

		var label_mask_required = this.get_object_meta_value(this.form, 'label_mask_required', '', true, true);
		if(label_mask_required == '') {

			// Use framework mask_required_label
			var framework_type = $.WS_Form.settings_plugin.framework;
			var framework = $.WS_Form.frameworks.types[framework_type];
			var fields = this.framework['fields']['public'];

			if(typeof fields.mask_required_label === 'undefined') { return false; }
			var label_mask_required = fields.mask_required_label;
			if(label_mask_required == '') { return false; }
		}

		// Get all labels in this form
		$('label:not([data-init-required])', this.form_canvas_obj).each(function() {

			// Flag so it only initializes once
			$(this).attr('data-init-required', '');

			// Get 'for' attribute of label
			var label_for = $(this).attr('for');
			if(label_for === undefined) { return; }

			// Get field related to 'for'
			var field = $('[id="' + label_for + '"]');
			if(!field.length) { return; }

			// Check if field is required
			var field_required = (field.attr('data-required') !== undefined);

			// Check if the require string should be added to the parent label (e.g. for radios)
			var label_required_id = $(this).attr('data-label-required-id');
			if((typeof(label_required_id) !== 'undefined') && (label_required_id !== false)) {

				var label_obj = $('#' + label_required_id);

			} else {

				var label_obj = $(this);
			}

			// Check if wsf-required-wrapper span exists, if not, create it (You can manually insert it in config using #required)
			var required_wrapper = $('.wsf-required-wrapper', label_obj);
			if(!required_wrapper.length && field_required) {

				var required_wrapper_html = '<span class="wsf-required-wrapper"></span>';

				// Find first child
				var first_child = label_obj.children('input').first();

				// Add at appropriate place
				if(first_child.length) {

					first_child.before(required_wrapper_html);

				} else {

					label_obj.append(required_wrapper_html);
				}

				required_wrapper = $('.wsf-required-wrapper', label_obj);
			}

			if(field_required) {

				// Add it
				required_wrapper.html(label_mask_required);

			} else {

				// Remove it
				required_wrapper.html('');
			}
		});

		// Process field required bypass
		this.form_required_bypass(false);
	}

	// Field required bypass
	$.WS_Form.prototype.form_required_bypass = function(conditional_initiated) {

		// Process visible sections
		$('[id^="' + this.form_id_prefix + 'section-"][style!="display:none;"][style!="display: none;"] [data-required-bypass]:not([type=hidden])').attr('required', '').attr('aria-required', 'true').removeAttr('data-required-bypass');
		$('[id^="' + this.form_id_prefix + 'section-"][style!="display:none;"][style!="display: none;"] [data-ecommerce-price-bypass]:not([type=hidden])').removeAttr('data-ecommerce-price-bypass');

		// Process visible fields
		$('[id^="' + this.form_id_prefix + 'field-wrapper-"][style!="display:none;"][style!="display: none;"] [data-required-bypass]:not([type=hidden])').attr('required', '').attr('aria-required', 'true').removeAttr('data-required-bypass');
		$('[id^="' + this.form_id_prefix + 'field-wrapper-"][style!="display:none;"][style!="display: none;"] [data-ecommerce-price-bypass]:not([type=hidden])').removeAttr('data-ecommerce-price-bypass');

		// Process hidden sections
		$('[id^="' + this.form_id_prefix + 'section-"][style="display:none;"] [required]:not([type=hidden])').removeAttr('required').removeAttr('aria-required').attr('data-required-bypass', '');
		$('[id^="' + this.form_id_prefix + 'section-"][style="display:none;"] [data-ecommerce-price]:not([type=hidden])').attr('data-ecommerce-price-bypass', '');
		$('[id^="' + this.form_id_prefix + 'section-"][style="display: none;"] [required]:not([type=hidden])').removeAttr('required').removeAttr('aria-required').attr('data-required-bypass', '');
		$('[id^="' + this.form_id_prefix + 'section-"][style="display: none;"] [data-ecommerce-price]:not([type=hidden])').attr('data-ecommerce-price-bypass', '');

		// Process hidden fields
		$('[id^="' + this.form_id_prefix + 'field-wrapper-"][style="display:none;"] [required]:not([type=hidden])').removeAttr('required').removeAttr('aria-required').attr('data-required-bypass', '');
		$('[id^="' + this.form_id_prefix + 'field-wrapper-"][style="display:none;"] [data-ecommerce-price]:not([type=hidden])').attr('data-ecommerce-price-bypass', '');		
		$('[id^="' + this.form_id_prefix + 'field-wrapper-"][style="display: none;"] [required]:not([type=hidden])').removeAttr('required').removeAttr('aria-required').attr('data-required-bypass', '');
		$('[id^="' + this.form_id_prefix + 'field-wrapper-"][style="display: none;"] [data-ecommerce-price]:not([type=hidden])').attr('data-ecommerce-price-bypass', '');

	}

	// Select all
	$.WS_Form.prototype.form_select_all = function() {

		$('[data-select-all]:not([data-init-select-all])', this.form_canvas_obj).each(function() {

			// Flag so it only initializes once
			$(this).attr('data-init-select-all', '');

			// Get select all name
			var select_all_name = $(this).attr('name');
			$(this).removeAttr('name').removeAttr('value').attr('data-select-all', select_all_name);

			// Click event
			$(this).click(function() {

				var select_all = $(this).is(':checked');
				var select_all_name = $(this).attr('data-select-all');
				$('[name="' + select_all_name + '"]:enabled').prop('checked', select_all);
			})
		});
	}

	// Form - Input Mask
	$.WS_Form.prototype.form_inputmask = function() {

		$('[data-inputmask]', this.form_canvas_obj).each(function () {

			if(typeof($(this).inputmask) !== 'undefined') {

				$(this).inputmask();
			}
		});
	}

	// Form - Client side validation
	$.WS_Form.prototype.form_validation = function() {

		// WS Form forms are set with novalidate attribute so we can manage that ourselves
		var ws_this = this;

		// Remove any existing on events
		if(this.form_post_run === true) { this.form_obj.off(); }

		// Disable submit on enter
		if(!this.get_object_meta_value(this.form, 'submit_on_enter', false)) {

			this.form_obj.on('keydown', ':input:not(textarea)', function(e) {

				if(e.keyCode == 13) {

					e.preventDefault();
					return false;
				}
			});
		}

		// On submit
		this.form_obj.on('submit', function(e) {

			// Trigger
			ws_this.trigger('submit-before');

			// Do not submit form
			e.preventDefault();
			e.stopPropagation();

			// If form post is locked, return
			if(ws_this.form_post_locked) { return; }

			// Recalculate e-commerce
			if(ws_this.has_ecommerce) { ws_this.form_ecommerce_calculate(); }

			// Mark as validated
			$(this).addClass(ws_this.class_validated);

			// Check validity of form
			if(ws_this.form_validate($(this))) {

				// Trigger
				ws_this.trigger('submit-validate-success');

					// Submit form
					ws_this.form_post('submit');
			} else {

				// Trigger
				ws_this.trigger('submit-validate-fail');
			}
		});
	}

	// Form - Validate (WS Form validation functions)
	$.WS_Form.prototype.form_validate = function(form) {

		if(typeof(form) === 'undefined') { var form = this.form_obj; }

		// Trigger rendered event
		this.trigger('validate-before');

		// Tab focussing
		var group_index_focus_enabled = (this.form.groups.length > 0);
		var group_index_focus = false;
		var object_focus = false;

		// Get form as element
		var form_el = form[0];

		// Execute browser validation
		var form_validated = form_el.checkValidity();

		if(!form_validated) {

			// Get all invalid fields
			var fields_invalid = $(':invalid', form).not('fieldset');

			if(fields_invalid) {

				// Get first invalid field
				object_focus = fields_invalid.first();

				// Get group index
				group_index_focus = this.get_group_index(object_focus);
			}
		}

		// Focus
		if(!form_validated) {

			if(object_focus !== false) {

				// Focus object
	 			if(this.get_object_meta_value(this.form, 'invalid_field_focus', true)) {

					if(group_index_focus !== false) { 

	 					this.object_focus = object_focus;

	 				} else {

	 					object_focus.focus();
	 				}
				}
			}

			// Focus tab
			if(group_index_focus !== false) { this.group_index_set(group_index_focus); }
		}

		// Trigger rendered event
		this.trigger('validate-after');

		return form_validated;
	}

	// Form - Validate - Real time
	$.WS_Form.prototype.form_validate_real_time = function(form) {

		var ws_this = this;

		// Set up form validation events
		for(var field_index in this.field_data_cache) {

			if(!this.field_data_cache.hasOwnProperty(field_index)) { continue; }

			var field_type = this.field_data_cache[field_index].type;
			var field_type_config = $.WS_Form.field_type_cache[field_type];

			// Get events
			if(typeof(field_type_config.events) === 'undefined') { continue; }
			var form_validate_event = field_type_config.events.event;

			// Get field ID
			var field_id = this.field_data_cache[field_index].id;

			// Check to see if this field is submitted as an array
			var submit_array = (typeof(field_type_config.submit_array) !== 'undefined') ? field_type_config.submit_array : false;

			// Check to see if field is in a repeatable section
			var field_wrapper = $('[data-type][data-id="' + field_id + '"]');

			// Run through each wrapper found (there might be repeatables)
			field_wrapper.each(function() {

				var field_repeatable_index = $(this).attr('data-repeatable-index');
				var repeatable_suffix = (typeof(field_repeatable_index) !== 'undefined') ? '[' + field_repeatable_index + ']' : '';

				if(submit_array) {

					var field_obj = $('[name="' + ws_form_settings.field_prefix + field_id + repeatable_suffix + '[]"]:not([data-init-validate-real-time]), [data-name="' + ws_form_settings.field_prefix + field_id + repeatable_suffix + '[]"]:not([data-init-validate-real-time])', this.form_canvas_obj);

				} else {

					var field_obj = $('[name="' + ws_form_settings.field_prefix + field_id + repeatable_suffix + '"]:not([data-init-validate-real-time]), [data-name="' + ws_form_settings.field_prefix + field_id + repeatable_suffix + '"]:not([data-init-validate-real-time])', this.form_canvas_obj);
				}

				if(field_obj.length) {

					// Flag so it only initializes once
					field_obj.attr('data-init-validate-real-time', '');

					// Create event (Also run on blur, this prevents the mask component from causing false validation results)
					field_obj.on(form_validate_event + ' blur', function() {

						ws_this.form_validate_real_time_process(false);
					});
				}
			});
		}

		// Initial validation fire
		this.form_validate_real_time_process(false);
	}

	$.WS_Form.prototype.form_validate_real_time_process = function(conditional_initiated) {

		this.form_valid = this.form_validate_silent(this.form_obj);

		// Run conditional logic
		if(!conditional_initiated) { this.form_canvas_obj.trigger('wsf-validate-silent'); }

		// Check for form validation changes
		if(
			(this.form_valid_old === null) ||
			(this.form_valid_old != this.form_valid)
		) {

			// Run conditional logic
			if(!conditional_initiated) { this.form_canvas_obj.trigger('wsf-validate'); }
		}

		this.form_valid_old = this.form_valid;

		// Execute hooks and pass form_valid to them
		for(var hook_index in this.form_validation_real_time_hooks) {

			if(!this.form_validation_real_time_hooks.hasOwnProperty(hook_index)) { continue; }

			var hook = this.form_validation_real_time_hooks[hook_index];

			if(typeof(hook) === 'undefined') {

				delete(this.form_validation_real_time_hooks[hook_index]);

			} else {

				hook(this.form_valid, this.form, this.form_id, this.form_instance_id, this.form_obj, this.form_canvas_obj);
			}
		}

		return this.form_valid;
	}

	$.WS_Form.prototype.form_validate_real_time_register_hook = function(hook) {

		this.form_validation_real_time_hooks.push(hook);
	}

	// Form - Validate - Silent
	$.WS_Form.prototype.form_validate_silent = function(form) {

		// Get form as element
		var form_el = form[0];

		// Execute browser validation
		var form_validated = form_el.checkValidity();
		if(!form_validated) { return false; }


		return true;
	}

	// Validate any form object
	$.WS_Form.prototype.object_validate = function(obj) {

		var radio_field_processed = [];		// This ensures correct progress numbers of radios

		if(typeof(obj) === 'undefined') { return false; }

		var ws_this = this;

		var valid = true;

		// Get required fields
		$('[data-required]:not([data-required-bypass])', obj).each(function() {

			// Get data ID
			var field_id = $(this).closest('[data-id]').attr('data-id');

			// Get progress event
			var field = ws_this.field_data_cache[field_id];
			var field_type = field.type;

			// Get repeatable suffix
			var field_wrapper = $(this).closest('[data-type]');
			var field_repeatable_index = field_wrapper.attr('data-repeatable-index');
			var repeatable_suffix = (typeof(field_repeatable_index) !== 'undefined') ? '[' + field_repeatable_index + ']' : '';

			// Build field name
			var field_name = ws_form_settings.field_prefix + field_id + repeatable_suffix;

			// Determine field validity based on field type
			var validity = false;
			switch(field_type) {

				case 'radio' :
				case 'price_radio' :

					if(typeof(radio_field_processed[field_id]) === 'undefined') { 

						validity = $(this)[0].checkValidity();

					} else {

						return;
					}
					break;

				case 'signature' :

					validity = ws_this.signature_get_response_by_name(field_name);
					break;

				case 'email' :

					var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
					validity = re.test($(this).val());
					break;

				default :

					validity = $(this)[0].checkValidity();
			}

			radio_field_processed[field_id] = true;

			if(!validity) { valid = false; }
		});

		return valid;
	}

	// Convert hex color to RGB values
	$.WS_Form.prototype.hex_to_hsl = function(color) {

		// Get RGB of hex color
		var rgb = this.hex_to_rgb(color);
		if(rgb === false) { return false; }

		// Get HSL of RGB
		var hsl = this.rgb_to_hsl(rgb);

		return hsl;
	}

	// Convert hex color to RGB values
	$.WS_Form.prototype.hex_to_rgb = function(color) {

		// If empty, return false
		if(color == '') { return false; }

		// Does color have a hash?
		var color_has_hash = (color[0] == '#');

		// Check
		if(color_has_hash && (color.length != 7)) { return false; }
		if(!color_has_hash && (color.length != 6)) { return false; }

		// Strip hash
		var color = color_has_hash ? color.substr(1) : color;

		// Get RGB values
		var r = parseInt(color.substr(0,2), 16);
		var g = parseInt(color.substr(2,2), 16);
		var b = parseInt(color.substr(4,2), 16);

		return {'r': r, 'g': g, 'b': b};
	}

	// Convert RGB to HSL
	$.WS_Form.prototype.rgb_to_hsl = function(rgb) {

		if(typeof rgb.r === 'undefined') { return false; }
		if(typeof rgb.g === 'undefined') { return false; }
		if(typeof rgb.b === 'undefined') { return false; }

		var r = rgb.r;
		var g = rgb.g;
		var b = rgb.b;

		r /= 255, g /= 255, b /= 255;

		var max = Math.max(r, g, b), min = Math.min(r, g, b);
		var h, s, l = (max + min) / 2;

		if(max == min){
	
			h = s = 0;
	
		} else {
	
			var d = max - min;
			s = l > 0.5 ? d / (2 - max - min) : d / (max + min);

			switch(max){
				case r: h = (g - b) / d + (g < b ? 6 : 0); break;
				case g: h = (b - r) / d + 2; break;
				case b: h = (r - g) / d + 4; break;
			}

			h /= 6;
		}

		return {'h': h, 's': s, 'l': l};
	}

	$.WS_Form.prototype.conditional_process_actions = function(actions, type, obj, object_repeatable_index) {

		var actions_processed = 0;
		var process_required = false;

		for(var action_index in actions) {

			if(!actions.hasOwnProperty(action_index)) { continue; }

			var action_single = actions[action_index];

			// Check integrity of action
			if(!this.conditional_action_check(action_single)) { continue; }

			// Read action data
			var object = action_single['object'];
			var object_id = action_single['object_id'];
			var object_row_id = (typeof action_single['object_row_id'] === 'undefined') ? false : action_single['object_row_id'];
			var action = action_single['action'];
			var value = (typeof action_single['value'] === 'undefined') ? false : this.parse_variables_process(action_single['value'], object_repeatable_index);
			var debug_action_value = value;
			var field_name = ws_form_settings.field_prefix + object_id;

			// Repeatable?
			var repeatable_suffix = (object_repeatable_index > 0) ? '-repeat-' + object_repeatable_index : '';

			// Build selectors

			// Object wrapper
			switch(object) {

				case 'form' :

					var obj_wrapper = this.form_obj;
					var obj = this.form_obj;
					break;

				case 'field' :

					// Is the object we are modifying in the same section as the obj that fired this action
					var object_wrapper = $('[data-type][data-id="' + object_id + '"]');
					var obj_section_id = this.get_section_id(obj);
					var object_section_id = this.get_section_id(object_wrapper.first());
					var same_section = (obj_section_id == object_section_id);

					if(same_section) {

						// Get repeatable suffix
						var object_wrapper = obj.closest('[data-type]');
						var object_repeatable_index = (typeof(object_wrapper.attr('data-repeatable-index')) ? object_wrapper.attr('data-repeatable-index') : 0);
						var object_repeatable_suffix = (object_repeatable_index > 0) ? '-repeat-' + object_repeatable_index : '';

						// Set selectors
						var object_selector_wrapper = '#' + this.form_id_prefix + object + '-wrapper-' + object_id + (object_row_id ? '-row-' + object_row_id : '') + object_repeatable_suffix;
						var object_selector = '#' + this.form_id_prefix + object + '-' + object_id + (object_row_id ? '-row-' + object_row_id : '') + object_repeatable_suffix;

					} else {

						// Set selectors
						var object_selector_wrapper = '[id^="' + this.form_id_prefix + object + '-wrapper-' + object_id + (object_row_id ? '-row-' + object_row_id : '') + '"][data-id="' + object_id + '"]';
						var object_selector = '[id^="' + this.form_id_prefix + object + '-' + object_id + (object_row_id ? '-row-' + object_row_id : '') + '"]';
					}

					var obj_wrapper = $(object_selector_wrapper);
					var obj = $(object_selector, obj_wrapper);

					break;

				case 'section' :

					// Is the object we are modifying in the same section as the obj that fired this action
					var object_wrapper = $('#' + this.form_id_prefix + 'section-' + object_id);
					var obj_section_id = this.get_section_id(obj);
					var object_section_id = this.get_section_id(object_wrapper.first());
					var same_section = (obj_section_id == object_section_id);

					if(same_section) {

						// Get repeatable suffix
						var object_repeatable_index = (typeof(object_wrapper.attr('data-repeatable-index')) ? object_wrapper.attr('data-repeatable-index') : 0);
						var object_repeatable_suffix = (object_repeatable_index > 0) ? '-repeat-' + object_repeatable_index : '';

						// Set selectors
						var object_selector_wrapper = object_selector = '#' + this.form_id_prefix + object + '-' + object_id + object_repeatable_suffix;
						var object_selector = '#' + this.form_id_prefix + object + '-' + object_id + object_repeatable_suffix;
	
					} else {

						// Set selectors
						var object_selector_wrapper = object_selector = '[id^="' + this.form_id_prefix + object + '-"][data-id="' + object_id + '"]';
					}

					var obj_wrapper = $(object_selector_wrapper);
					var obj = $(object_selector);
					break;

				default :

					var object_selector_wrapper = object_selector = '#' + this.form_id_prefix + object + '-' + object_id + repeatable_suffix;
					var obj_wrapper = $(object_selector_wrapper);
					var obj = $(object_selector);
			}

			switch(action) {

				// Set value
				case 'value' :
				case 'value_number' :
				case 'value_datetime' :
				case 'value_tel' :
				case 'value_email' :
				case 'value_textarea' :

					obj.attr('data-value-old', function() { return $(this).val(); }).val(value).filter(function() { return $(this).val() !== $(this).attr('data-value-old') }).trigger('change').removeAttr('data-value-old');
					break;

				case 'value_range' : 
				case 'value_rating' :

					obj.attr('data-value-old', function() { return $(this).val(); }).val(value).filter(function() { return $(this).val() !== $(this).attr('data-value-old') }).trigger('change').removeAttr('data-value-old');
					break;

				case 'value_color' :

					if(typeof(obj.minicolors) === 'function') {

						obj.attr('data-value-old', function() { return $(this).val(); }).minicolors('value', {color: value}).filter(function() { return $(this).val() !== $(this).attr('data-value-old') }).trigger('change').removeAttr('data-value-old');

					} else {

						obj.attr('data-value-old', function() { return $(this).val(); }).val(value).filter(function() { return $(this).val() !== $(this).attr('data-value-old') }).trigger('change').removeAttr('data-value-old');
					}
					break;

				// Set HTML
				case 'html' :
				case 'text_editor' :

					obj_wrapper.html(value);
					break;

				// Set button label
				case 'button_html' :

					obj.html(value);
					break;

				// Add class (Wrapper)
				case 'class_add_wrapper' :

					obj_wrapper.addClass(value);
					debug_action_value = this.language('debug_action_added');
					break;

				// Remove class
				case 'class_remove_wrapper' :

					obj_wrapper.removeClass(value);
					debug_action_value = this.language('debug_action_removed');
					break;

				// Add class (Wrapper)
				case 'class_add_field' :

					obj.addClass(value);
					debug_action_value = this.language('debug_action_added');
					break;

				// Remove class
				case 'class_remove_field' :

					obj.removeClass(value);
					debug_action_value = this.language('debug_action_removed');
					break;
				// Select an option
				case 'value_row_select' :

					$(object_selector + ':enabled').prop('selected',false).prop('selected',true);
					debug_action_value = this.language('debug_action_selected');
					break;

				// Deselect an option
				case 'value_row_deselect' :

					$(object_selector + ':enabled').prop('selected',true).prop('selected',false);
					debug_action_value = this.language('debug_action_deselected');
					break;

				// Unselect all options (Reset)
				case 'value_row_reset' :

					obj_wrapper.find('option:enabled').prop('selected',true).prop('selected',false);
					debug_action_value = this.language('debug_action_reset');
					break;

				// Check/uncheck a checkbox or radio
				case 'value_row_check' :
				case 'value_row_uncheck' :

					$(object_selector + ':enabled').prop('checked',(action == 'value_row_check'));
					debug_action_value = this.language('debug_action_' + ((action == 'value_row_check') ? 'checked' : 'unchecked'));
					break;

				// Set required on a checkbox or radio
				case 'value_row_required' :
				case 'value_row_not_required' :

					obj.prop('required',(action == 'value_row_required')).removeAttr('data-init-required');
					debug_action_value = this.language('debug_action_' + ((action == 'value_row_required') ? 'required' : 'not_required'));
					process_required = true;
					break;

				// Set disabled on a checkbox or radio
				case 'value_row_disabled' :
				case 'value_row_not_disabled' :

					obj.attr('disabled',(action == 'value_row_disabled'));
					debug_action_value = this.language('debug_action_' + ((action == 'value_row_disabled') ? 'disabled' : 'enabled'));
					break;

				// Set visible on a checkbox or radio
				case 'value_row_visible' :
				case 'value_row_not_visible' :

					if(action === 'value_row_not_visible') { obj.parent().hide(); } else { obj.parent().show(); }
					debug_action_value = this.language('debug_action_' + ((action == 'value_row_not_visible') ? 'hide' : 'show'));
					break;

				// Focus on a checkbox or radio
				case 'value_row_focus' :

					obj.focus();
					debug_action_value = this.language('debug_action_focussed');
					break;

				// Add class
				case 'value_row_class_add' :

					obj.addClass(value);
					debug_action_value = this.language('debug_action_added');
					break;

				// Remove class
				case 'value_row_class_remove' :

					obj.removeClass(value);
					debug_action_value = this.language('debug_action_removed');
					break;

				// Set custom validity
				case 'value_row_set_custom_validity' :

					// Custom invalid feedback text
					var invalid_feedback_obj = $('#' + this.form_id_prefix + 'invalid-feedback-' + object_id + '-row-' + object_row_id);

					// Set invalid feedback
					this.set_invalid_feedback(obj, invalid_feedback_obj, value, object_id, object_row_id);

					break;

				// Set visibility
				case 'visibility' :

					switch(object) {

						// Field / section visibility
						case 'section' :
						case 'field' :

							if(value === 'off') {

								// Hide object
								obj_wrapper.hide();

								// Process field bypassing
								this.form_required_bypass(true);

							} else {

								// Show object
								obj_wrapper.show();

								// Process field bypassing
								this.form_required_bypass(true);

								// Redraw signatures
								if(object == 'section') { this.signatures_redraw(false, object_id); }
								if(object == 'field') { this.signatures_redraw(false, false, object_id); }
							}

							debug_action_value = this.language('debug_action_' + ((value == 'off') ? 'hide' : 'show'));
							break;
					}

					break;

				// Disable
				case 'disabled' :

					switch(object) {

						case 'section' :

							// For sections, we need to look for a fieldset
							$('fieldset', obj_wrapper).first().prop('disabled', (value == 'on'));
							break;

						case 'field' :

							obj.prop('disabled', (value == 'on'));

							var class_disabled_array = this.get_field_value_fallback(obj_wrapper.attr('data-type'), false, 'class_disabled', false);

							if(value == 'on') {

								if(class_disabled_array !== false) { obj.addClass(class_disabled_array.join(' ')); }
								obj.css({'pointer-events': 'none'});

							} else {

								if(class_disabled_array !== false) { obj.removeClass(class_disabled_array.join(' ')); }
								obj.attr('style', '');
							}

							break;
					}

					debug_action_value = this.language('debug_action_' + ((value == 'on') ? 'disabled' : 'enabled'));
					break;

				// Required
				case 'required' :

					// Get field data
					var field = this.field_data_cache[object_id];
					switch(field.type) {

						case 'radio' :
						case 'price_radio' :

							$('[name="' + field_name + '[]"]').prop('required', (value == 'on')).removeAttr('data-init-required');
							break;

						default :

							obj.prop('required', (value == 'on')).removeAttr('data-init-required');
					}

					debug_action_value = this.language('debug_action_' + ((value == 'on') ? 'required' : 'not_required'));
					process_required = true;
					break;

				// Required - Signature
				case 'required_signature' :

					obj.attr('required', (value == 'on')).removeAttr('data-init-required');
					var signature = (typeof(this.signatures_by_name[field_name]) !== 'undefined') ? this.signatures_by_name[field_name] : false;
					if(signature !== false) { signature.required = (value == 'on'); }
					debug_action_value = this.language('debug_action_' + ((value == 'on') ? 'required' : 'not_required'));
					process_required = true;
					break;

				// Read only
				case 'readonly' :

					obj.prop('readonly', (value == 'on'));
					debug_action_value = this.language('debug_action_' + ((value == 'on') ? 'read_only' : 'not_read_only'));

					this.form_date();	// Destroy jQuery component if readonly

					break;

				// Set custom validity
				case 'set_custom_validity' :

					// Custom invalid feedback text
					var invalid_feedback_obj = $('#' + this.form_id_prefix + 'invalid-feedback-' + object_id);

					// Set invalid feedback
					this.set_invalid_feedback(obj, invalid_feedback_obj, value, object_id);

					break;

				// Click
				case 'click' :

					switch(object) {

						// Tab click
						case 'group' :

							var tab_selector = '[href="#' + this.form_id_prefix + 'tab-content-' + object_id + '"]';
							$(tab_selector).click();
							break;

						// Field click
						case 'field' :

							obj.click();
							break;
					}

					debug_action_value = this.language('debug_action_clicked');
					break;

				// Focus
				case 'focus' :

					obj.focus();
					debug_action_value = this.language('debug_action_focussed');

					break;

				// Action - Run
				case 'action_run' :

					if(this.conditional_actions_run_save.indexOf(object_id) !== -1) {

						this.form_post('action', object_id);
					}
					break;

				// Action - Enable on save
				case 'action_run_on_save' :

					if(this.conditional_actions_run_save.indexOf(object_id) === -1) {
						this.conditional_actions_run_save.push(object_id);
						this.conditional_actions_changed = true;
					}
					break;

				// Action - Enable on submit
				case 'action_run_on_submit' :

					if(this.conditional_actions_run_submit.indexOf(object_id) === -1) {
						this.conditional_actions_run_submit.push(object_id);
						this.conditional_actions_changed = true;
					}
					break;

				// Action - Disable on save
				case 'action_do_not_run_on_save' :

					var object_id_index = this.conditional_actions_run_save.indexOf(object_id);
					if (object_id_index !== -1) {
						this.conditional_actions_run_save.splice(object_id_index, 1);
						this.conditional_actions_changed = true;
					}
					break;

				// Action - Disable on submit
				case 'action_do_not_run_on_submit' :

					var object_id_index = this.conditional_actions_run_submit.indexOf(object_id);
					if (object_id_index !== -1) {
						this.conditional_actions_run_submit.splice(object_id_index, 1);
						this.conditional_actions_changed = true;
					}
					break;

				// Run JavaScript
				case 'javascript' :

					eval(value);
					break;

				// Form - Save
				case 'form_save' :

					this.form_post('save');
					break;

				// Form - Submit
				case 'form_submit' :

					this.form_obj.submit();
					break;
			}

			actions_processed++;

			if($.WS_Form.debug_rendered) {

				var object_single_type = false;

				// Build action description for debug
				switch(object) {

					case 'form' :

						var object_single_type = this.language('debug_action_form');
						var object_single_label = this.language('debug_action_form');
						break;

					case 'group' :

						var object_single = this.group_data_cache[object_id];
						var object_single_type = this.language('debug_action_group');
						var object_single_label = object_single.label;
						break;

					case 'section' :

						var object_single = this.section_data_cache[object_id];
						var object_single_type = this.language('debug_action_section');
						var object_single_label = object_single.label;
						break;

					case 'field' :

						if(typeof(this.field_data_cache[object_id]) !== 'undefined') {

							var object_single = this.field_data_cache[object_id];						
							var object_single_type = object_single.type;
							var object_single_label = object_single.label;
						}
						break;

					case 'action' :

						var object_single = this.action_data_cache[object_id];
						var object_single_type = this.language('debug_action_action');
						var object_single_label = object_single.label;
						break;
				}

				if(object_single_type !== false) {

					var conditional_settings = $.WS_Form.settings_form.conditional;
					var conditional_settings_objects = conditional_settings.objects;
					var conditional_settings_actions = conditional_settings_objects[object]['action'];
					var conditional_settings_action = conditional_settings_actions[action];

					var action_description = conditional_settings_action.text.toUpperCase();
					if(typeof conditional_settings_action.values !== 'undefined') {

						if(typeof conditional_settings_action.values === 'object') {

							if(typeof conditional_settings_action.values[value] !== 'undefined') {

								debug_action_value = conditional_settings_action.values[value].text;
							}
						}
					}

					var log_description = '<strong>[' + this.html_encode(object_single_label) + '] ' + action_description + (((debug_action_value !== false) && (debug_action_value != '')) ? " '" + this.html_encode(debug_action_value) + "'" : '') + '</strong> (' + this.language('debug_action_type') + ': ' + object_single_type + ' | ID: ' + object_id + (object_row_id ? ' | ' + this.language('debug_action_row') + ' ID: ' + object_row_id : '') + ')';

					this.log('log_conditional_action_' + type, log_description, 'conditional');
				}
			}
		}

		if(actions_processed == 0) {

			this.log('log_conditional_action_not_found_' + type, '', 'conditional');
		}

		if(process_required) {

			this.form_required();
		}
	}

	$.WS_Form.prototype.conditional_logic_previous = function(accumulator, value, logic_previous) {

		switch(logic_previous) {

			// OR
			case '||' :

				accumulator |= value;
				break;

			// AND
			case '&&' :

				accumulator &= value;
				break;
		}

		return accumulator;
	}

	// Check integrity of a condition
	$.WS_Form.prototype.conditional_condition_check = function(condition) {

		// Check condition structure
		if(typeof condition.id === 'undefined') { return false; }
		if(typeof condition.object === 'undefined') { return false; }
		if(typeof condition.object_id === 'undefined') { return false; }
		if(typeof condition.object_row_id === 'undefined') { return false; }
		if(typeof condition.logic === 'undefined') { return false; }
		if(typeof condition.value === 'undefined') { return false; }
		if(typeof condition.case_sensitive === 'undefined') { return false; }
		if(typeof condition.logic_previous === 'undefined') { return false; }

		// Check condition variables
		if(condition.id == '') { return false; }
		if(condition.id == 0) { return false; }
		if(condition.object == '') { return false; }
		if(condition.object_id == '') { return false; }
		if(condition.logic == '') { return false; }

		return true;
	}

	// Check integrity of an action
	$.WS_Form.prototype.conditional_action_check = function(action) {

		// Check action structure
		if(typeof action.object === 'undefined') { return false; }
		if(typeof action.object_id === 'undefined') { return false; }
		if(typeof action.action === 'undefined') { return false; }

		// Check action variables
		if(action.object == '') { return false; }
		if(action.object_id == '') { return false; }
		if(action.action == '') { return false; }

		return true;
	}

	// Group - Tabs - Init
	$.WS_Form.prototype.form_tabs = function() {

		if(this.form.groups.length <= 1) { return false; }

		var ws_this = this;

		// Get tab index cookie if settings require it
		var index = (this.get_object_meta_value(this.form, 'cookie_tab_index')) ? this.cookie_get('tab_index', 0) : 0;

		// If we are using the WS Form framework, then we need to run our own tabs script
		if($.WS_Form.settings_plugin.framework == ws_form_settings.framework_admin) {

			var tabs_obj = $('.wsf-group-tabs', this.form_canvas_obj);

			// Destroy tabs (Ensures subsequent calls work)
			if(tabs_obj.hasClass('wsf-tabs')) { this.tabs_destroy(); }

			// Init tabs
			this.tabs(tabs_obj, { active: index });

		} else {

			// Set active tab
			this.group_index_set(index);
		}

		var framework_tabs = this.framework['tabs']['public'];

		if(typeof(framework_tabs.event_js) !== 'undefined') {

			var event_js = framework_tabs.event_js;
			var event_type_js = (typeof(framework_tabs.event_type_js) !== 'undefined') ? framework_tabs.event_type_js : false;
			var event_selector_wrapper_js = (typeof(framework_tabs.event_selector_wrapper_js) !== 'undefined') ? framework_tabs.event_selector_wrapper_js : false;
			var event_selector_active_js = (typeof(framework_tabs.event_selector_active_js) !== 'undefined') ? framework_tabs.event_selector_active_js : false;

			switch(event_type_js) {

				case 'wrapper' :

					var event_selector = $(event_selector_wrapper_js, this.form_canvas_obj);
					break;

				default :

					var event_selector = $('[href^="#' + this.form_id_prefix + 'tab-content-"]');
			}

			// Set up on click event for each tab
			event_selector.on(event_js, function (event, ui) {

				switch(event_type_js) {

					case 'wrapper' :

						var event_active_selector = $(event_selector_active_js, event_selector);
						var tab_index = event_active_selector.index();
						break;

					default :

						var tab_index = $(this).parent().index();
				}

				// Save current tab index to cookie
				if(ws_this.get_object_meta_value(ws_this.form, 'cookie_tab_index')) {

					ws_this.cookie_set('tab_index', tab_index);
				}

				// Object focus
				if(ws_this.object_focus !== false) {

					ws_this.object_focus.focus();
					ws_this.object_focus = false;
				}
			});
		}

		// Tab validation
		var tab_validation = this.get_object_meta_value(this.form, 'tab_validation');
		if(tab_validation) {

			this.form_canvas_obj.on('wsf-validate-silent', function() {

				ws_this.tab_validation();
			});
			this.tab_validation();
		}
	}

	// Tab validation
	$.WS_Form.prototype.tab_validation = function() {

		var ws_this = this;

		var tab_validated_previous = true;

		// Get tabs
		var tabs = $('[href^="#' + this.form_id_prefix + 'tab-content-"]', this.form_canvas_obj);

		// Get tab count
		var tab_count = tabs.length;

		// Get tab_index_current
		var tab_index_current = 0;
		tabs.each(function(tab_index) {

			var tab_visible = $($(this).attr('href')).is(':visible');
			if(tab_visible) {

				tab_index_current = tab_index;
				return false;
			}
		});

		tabs.each(function(tab_index) {

			// Render validation for previous tab
			ws_this.tab_validation_previous($(this), tab_validated_previous);

			// Validate tab
			if(tab_index < (tab_count - 1)) {

				if(tab_validated_previous === true) {

					var tab_validated_current = ws_this.object_validate($($(this).attr('href')));

				} else {

					var tab_validated_current = false;
				}

				// Render validation for current tab
				ws_this.tab_validation_current($(this), tab_validated_current);

				tab_validated_previous = tab_validated_current;
			}

			// If we are on a tab that is beyond the current invalidated tab, change tab to first invalidated tab
			if( !tab_validated_current &&
				(tab_index_current > tab_index)
			) {

				// Activate tab
				ws_this.group_index_set(tab_index);
			}
		});
	}

	// Tab validation - Current
	$.WS_Form.prototype.tab_validation_current = function(obj, tab_validated) {

		var tab_id = obj.attr('href');
		var tab_content_obj = $(tab_id);
		var button_next_obj = $('button[data-action="wsf-tab_next"]', tab_content_obj);

		if(tab_validated) {

			button_next_obj.removeAttr('disabled');

		} else {

			button_next_obj.attr('disabled', '');
		}
	}

	// Tab validation - Previous
	$.WS_Form.prototype.tab_validation_previous = function(obj, tab_validated) {

		var framework_tabs = this.framework['tabs']['public'];

		if(typeof(framework_tabs.class_disabled) !== 'undefined') {

			if(tab_validated) {

				obj.removeClass(framework_tabs.class_disabled);

			} else {

				obj.addClass(framework_tabs.class_disabled);
			}
		}

		if(typeof(framework_tabs.class_parent_disabled) !== 'undefined') {

			if(tab_validated) {

				obj.parent().removeClass(framework_tabs.class_parent_disabled);

			} else {

				obj.parent().addClass(framework_tabs.class_parent_disabled);
			}
		}
	}

	// Form - Submit
	$.WS_Form.prototype.form_post = function(post_mode, action_id) {

		if(typeof post_mode == 'undefined') { var post_mode = 'save'; }
		if(typeof action_id == 'undefined') { var action_id = 0; }

		// Determine if this is a submit
		var submit = (post_mode == 'submit');

		// Trigger post mode event
		this.trigger(post_mode);

		var ws_this = this;

		// Lock form
		this.form_post_lock();

		// Build form data
		this.form_add_hidden_input('wsf_form_id', this.form_id);
		this.form_add_hidden_input('wsf_hash', this.hash);
		this.form_add_hidden_input('wsf_duration', Math.round((new Date().getTime() - this.date_start) / 1000));

		// Reset date start
		if(post_mode == 'submit') {

			this.date_start = false;
			this.cookie_set('date_start', false, false);
			this.form_timer();
		}

		if((typeof(ws_form_settings.post_id) !== 'undefined') && (ws_form_settings.post_id > 0)) {

			this.form_add_hidden_input('wsf_post_id', ws_form_settings.post_id);
		}

		// Work out which required fields to skip
		var form_field_bypass_array = $('[data-required-bypass]', this.form_canvas_obj).map(function() {

			// Signature canvases use data-name instead of name
			var name = ($(this).attr('data-name')) ? $(this).attr('data-name') : $(this).attr('name');

			// Strip brackets (For select, radio and checkboxes)
			name = name.replace('[]', '');

			return name;

		}).get();
		form_field_bypass_array = form_field_bypass_array.filter(function(value, index, self) { 

		    return self.indexOf(value) === index;
		});
		var form_field_bypass = form_field_bypass_array.join();
		this.form_add_hidden_input('wsf_bypass', form_field_bypass);

		// Post mode
		this.form_add_hidden_input('wsf_post_mode', post_mode);

		// Action ID
		if(action_id > 0) {

			this.form_add_hidden_input('wsf_action_id', action_id);
		}


		// Do not run AJAX
		if(
			(action_id == 0) &&
			(this.form_ajax === false)
		) {

			// We're done!
			ws_this.trigger(post_mode + '-complete');
			return;
		}

		// Trigger
		ws_this.trigger('submit-before-ajax');

		// Build form data
		var form_data = new FormData(this.form_obj[0]);

		// Call API
 		this.api_call('submit', 'POST', form_data, function(response) {

 			// Success

			// Check for validation errors
			var error_validation = (typeof(response.error_validation) !== 'undefined') && response.error_validation;

			// Check for errors
			var errors = (

				(typeof(response.data) !== 'undefined') &&
				(typeof(response.data.errors) !== 'undefined') &&
				response.data.errors.length
			);

 			// If response is invalid or form is being saved, force unlock it
 			var form_post_unlock_force = (

 				(typeof(response.data) === 'undefined') ||
 				(post_mode == 'save') ||
 				error_validation ||
 				errors
 			);

 			// Unlock form
			ws_this.form_post_unlock('progress', !form_post_unlock_force, form_post_unlock_force);

 			// Check for form reload on submit
 			if(
 				(submit && !error_validation && !errors)
 			) {

				// Clear hash
				ws_this.form_hash_clear();

				if(ws_this.get_object_meta_value(ws_this.form, 'submit_reload', true)) {

	 				// Reload
	 				ws_this.form_reload();
	 			}
	 		}

	 		// Show error messages
	 		if(errors && ws_this.get_object_meta_value(ws_this.form, 'submit_show_errors', true)) {

		 		for(var error_index in response.data.errors) {

					if(!response.data.errors.hasOwnProperty(error_index)) { continue; }

		 			var error_message = response.data.errors[error_index];
		 			ws_this.action_message(error_message, 'danger', 'after', 4000, false, false, false, false, true);
		 		}
		 	}

			ws_this.trigger(post_mode + '-complete');

	 		return !errors;

 		}, function(response) {

 			// Error
			ws_this.form_post_unlock('progress', true, true);


	 		// Show error message
	 		if(typeof(response.error_message) !== 'undefined') {

	 			ws_this.action_message(response.error_message, 'danger', 'after', 4000, false, false, false, false, true);
	 		}

			// Trigger post most complete event
			ws_this.trigger(post_mode + '-error');

 		}, (action_id > 0));
	}

	// Form lock
	$.WS_Form.prototype.form_post_lock = function(cursor, force) {

		if(typeof(cursor) === 'undefined') { var cursor = 'progress'; }
		if(typeof(force) === 'undefined') { var timeout = false; }

		if(this.form_obj.hasClass('wsf-form-post-lock')) { return; }

		if(force || this.get_object_meta_value(this.form, 'submit_lock', false)) {

			// Stop further calculations
			this.form_ecommerce_calculate_enabled = false;

			// Add locked class to form
			this.form_obj.addClass('wsf-form-post-lock' + (cursor ? ' wsf-form-post-lock-' + cursor : ''));

			// Disable submit buttons
			$('button[type="submit"].wsf-button, input[type="submit"].wsf-button, button[data-action="wsf-save"].wsf-button, button[data-ecommerce-payment].wsf-button', this.form_canvas_obj).attr('disabled', '');
			this.form_post_locked = true;

			// Trigger rendered event
			this.trigger('lock');

		}
	}

	// Form unlock
	$.WS_Form.prototype.form_post_unlock = function(cursor, timeout, force) {

		if(typeof(cursor) === 'undefined') { var cursor = 'progress'; }
		if(typeof(timeout) === 'undefined') { var timeout = true; }
		if(typeof(force) === 'undefined') { var force = false; }

		if(!this.form_obj.hasClass('wsf-form-post-lock')) { return; }

		var ws_this = this;

		var unlock_fn = function() {

			// Re-enable cart calculations
			ws_this.form_ecommerce_calculate_enabled = true;

			// Remove locked class from form
			ws_this.form_obj.removeClass('wsf-form-post-lock' + (cursor ? ' wsf-form-post-lock-' + cursor : ''));

			// Enable submit buttons
			$('button[type="submit"].wsf-button, input[type="submit"].wsf-button, button[data-action="wsf-save"].wsf-button, button[data-ecommerce-payment].wsf-button', ws_this.form_canvas_obj).removeAttr('disabled');
			ws_this.form_post_locked = false;

			// Trigger rendered event
			ws_this.trigger('unlock');

		}

		if(force || this.get_object_meta_value(this.form, 'submit_unlock', false)) {

			// Enable post buttons
			timeout ? setTimeout(function() { unlock_fn(); }, 1000) : unlock_fn();
		}
	}

	// API Call
	$.WS_Form.prototype.api_call = function(ajax_path, method, params, success_callback, error_callback, force_ajax_path) {

		if(typeof(force_ajax_path) === 'undefined') { var force_ajax_path = false; }

		var ws_this = this;


		// Make AJAX request
		var url = force_ajax_path ? (ws_form_settings.url + ajax_path) : ((ajax_path == 'submit') ? this.form_obj.attr('action') : (ws_form_settings.url + ajax_path));

		// Check for custom action URL
		if(
			!force_ajax_path &&
			this.form_action_custom &&
			((ajax_path == 'submit') || (ajax_path == 'save'))
		) {

			// Custom action submit
			this.form_obj.off('submit');
			this.form_obj.submit();
			return true;
		}

		// Defaults
		if(typeof method === 'undefined') { var method = 'POST'; }
		if(typeof params === 'undefined') { var params = false; }

		// Check for AJAX HTTP method override
		var ajax_http_method_override = (ws_form_settings.ajax_http_method_override === true);

		// Call AJAX
		var ajax_request = {

			method: ajax_http_method_override ? 'POST' : method,
			url: url,
			beforeSend: function(xhr) {

				// Nonce
				if(typeof(ws_form_settings.nonce) !== 'undefined') { xhr.setRequestHeader('X-WP-Nonce', ws_form_settings.nonce); }

				// HTTP method override
				if(ajax_http_method_override) { xhr.setRequestHeader('X-HTTP-Method-Override', method); }
			},
			contentType: false,
			processData: false,
  			statusCode: {

				// Success
				200: function(response) {

					// Handle hash response
					var hash_ok = ws_this.api_call_hash(response);

					// Call success function
					var success_callback_result = (typeof(success_callback) !== 'undefined') ? success_callback(response) : true;

					// Check for data to process
					if(
						(typeof(response.data) !== 'undefined') &&
						success_callback_result
					) {

						// Check for nonce
						if(typeof(response.data.nonce) !== 'undefined') { ws_form_settings.nonce = response.data.nonce; }

						// Check for action_js (These are returned from the action system to tell the browser to do something)
						if(typeof(response.data.js) === 'object') { ws_this.action_js_init(response.data.js); }
					}
				},

				// Bad request (Error from API)
				400: function(response) {

					var data = response.responseJSON;


					if(typeof error_callback !== 'undefined') { error_callback(data); }
				},

				// Forbidden (Error from API)
				403: function(response) {

					var data = response.responseJSON;


					if(typeof error_callback !== 'undefined') { error_callback(data); }
				},

				// Not found (Error from API)
				404: function(response) {

					var data = response.responseJSON;


					if(typeof error_callback !== 'undefined') { error_callback(data); }
				},

				// Server error
				500: function(response) {

					var data = response.responseJSON;


					if(typeof error_callback !== 'undefined') { error_callback(data); }
				}
			}
		};

		if(params !== false) { ajax_request.data = params; }

		$.ajax(ajax_request);

		return this;
	};

	// JS Actions - Init
	$.WS_Form.prototype.action_js_init = function(action_js) {

		// Trigger rendered event
		this.trigger('actions-start');

		this.action_js = action_js;

		this.action_js_process_next();
	};

	$.WS_Form.prototype.action_js_process_next = function() {

		if(this.action_js.length == 0) {

		 	// Trigger rendered event
			this.trigger('actions-finish');

			return false;
		}

		var js_action = this.action_js.shift();

		var action = this.js_action_get_parameter(js_action, 'action');
		var duration = this.js_action_get_parameter(js_action, 'duration');

		switch(action) {

			// Redirect
			case 'redirect' :

				var url = this.js_action_get_parameter(js_action, 'url');
				if(url !== false) { location.href = js_action['url']; }

				// Actions end at this point because of the redirect

				break;

			// Message
			case 'message' :

				var message = this.js_action_get_parameter(js_action, 'message');
				var type = this.js_action_get_parameter(js_action, 'type');
				var method = this.js_action_get_parameter(js_action, 'method');
				var duration = this.js_action_get_parameter(js_action, 'duration');
				var form_hide = this.js_action_get_parameter(js_action, 'form_hide');
				var clear = this.js_action_get_parameter(js_action, 'clear');
				var scroll_top = this.js_action_get_parameter(js_action, 'scroll_top');
				var scroll_top_offset = this.js_action_get_parameter(js_action, 'scroll_top_offset');
				var scroll_top_duration = this.js_action_get_parameter(js_action, 'scroll_top_duration');
				var form_show = this.js_action_get_parameter(js_action, 'form_show');
				var message_hide = this.js_action_get_parameter(js_action, 'message_hide');

				this.action_message(message, type, method, duration, form_hide, clear, scroll_top, scroll_top_offset, scroll_top_duration, form_show, message_hide);

				break;
			// Field invalid feedback
			case 'field_invalid_feedback' :

				var field_id = this.js_action_get_parameter(js_action, 'field_id');
				var invalid_feedback = this.js_action_get_parameter(js_action, 'invalid_feedback');

				// Field object
				var field_obj = $('#' + this.form_id_prefix + 'field-' + field_id);

				// Custom invalid feedback text
				var invalid_feedback_obj = $('#' + this.form_id_prefix + 'invalid-feedback-' + field_id);

				// Set invalid feedback
				this.set_invalid_feedback(field_obj, invalid_feedback_obj, invalid_feedback, field_id);

				var ws_this = this;

				// Reset if field modified
				field_obj.one('change input keyup', function() {

					var field_id = $(this).closest('[data-id]').attr('[data-id]');

					// Custom invalid feedback text
					var invalid_feedback_obj = $('#' + ws_this.form_id_prefix + 'invalid-feedback-' + field_id);

					// Reset invalid feedback
					ws_this.set_invalid_feedback($(this), invalid_feedback_obj, '', field_id);
				})

				break;

			// Unknown
			default :

				this.action_js_process_next();
		}
	}

	// JS Actions - Get js_action config parameter from AJAX return
	$.WS_Form.prototype.js_action_get_parameter = function(js_action_parameters, meta_key) {

		return typeof(js_action_parameters[meta_key]) !== 'undefined' ? js_action_parameters[meta_key] : false;
	}

	// JS Actions - Get framework config value
	$.WS_Form.prototype.get_framework_config_value = function(object, meta_key) {

		if(typeof(this.framework[object]) === 'undefined') {
			return false;
		}
		if(typeof(this.framework[object]['public']) === 'undefined') {
			return false;
		}
		if(typeof(this.framework[object]['public'][meta_key]) === 'undefined') { return false; }

		return this.framework[object]['public'][meta_key];
	}

	// JS Action - Message
	$.WS_Form.prototype.action_message = function(message, type, method, duration, form_hide, clear, scroll_top, scroll_top_offset, scroll_top_duration, form_show, message_hide) {

		if(typeof(type) === 'undefined') { var type = 'information'; }
		if(typeof(method) === 'undefined') { var method = 'before'; }
		if(typeof(duration) === 'undefined') { var duration = 0; }
		if(typeof(form_hide) === 'undefined') { var form_hide = false; }
		if(typeof(clear) === 'undefined') { var clear = true; }
		if(typeof(scroll_top) === 'undefined') { var scroll_top = false; }
		if(typeof(scroll_top_offset) === 'undefined') { var scroll_top_offset = 0; }
		scroll_top_offset = (scroll_top_offset == '') ? 0 : parseInt(scroll_top_offset);
		if(typeof(scroll_top_duration) === 'undefined') { var scroll_top_duration = 0; }
		if(typeof(form_show) === 'undefined') { var form_show = false; }
		if(typeof(message_hide) === 'undefined') { var message_hide = false; }

		var scroll_position = this.form_canvas_obj.offset().top - scroll_top_offset;

		// Parse duration
		duration = parseInt(duration);
		if(duration < 0) { duration = 0; }

		// Get config
		var mask_wrapper = this.get_framework_config_value('message', 'mask_wrapper');
		var types = this.get_framework_config_value('message', 'types');

		var type = (typeof(types[type]) !== 'undefined') ? types[type] : false;
		var mask_wrapper_class = (typeof(type['mask_wrapper_class']) !== 'undefined') ? type['mask_wrapper_class'] : false;

		// Clear other messages
		if(clear) {

			$('[data-wsf-message][data-wsf-instance-id="' + this.form_instance_id + '"]').remove();
		}

		// Scroll top
		switch(scroll_top) {

			case 'instant' :
			case 'on' : 			// Legacy

				$('html,body').scrollTop(scroll_position);

				break;

			// Smooth
			case 'smooth' :

				scroll_top_duration = (scroll_top_duration == '') ? 0 : parseInt(scroll_top_duration);

				$('html,body').animate({

					scrollTop: scroll_position

				}, scroll_top_duration);

				break;
		}

		var mask_wrapper_values = {

			'message': 				message,
			'mask_wrapper_class': 	mask_wrapper_class 
		};

		var message_div = $('<div/>', { html: this.mask_parse(mask_wrapper, mask_wrapper_values) });
		message_div.attr('data-wsf-message', '');
		message_div.attr('data-wsf-instance-id', this.form_instance_id);

		// Hide form?
		if(form_hide) { this.form_obj.hide(); }

		// Render message
		switch(method) {

			// Before
			case 'before' :

				message_div.insertBefore(this.form_obj);
				break;

			// After
			case 'after' :

				message_div.insertAfter(this.form_obj);
				break;
		}

		// Process next action
		var ws_this = this;

		setTimeout(function() {

			// Should this message be removed?
			if(message_hide) { message_div.remove(); }

			// Should the form be shown?
			if(form_show) { ws_this.form_canvas_obj.show(); }

			// Process next js_action
			ws_this.action_js_process_next();

		}, parseInt(duration));
	}
	// Text input and textarea character and word count
	$.WS_Form.prototype.form_character_word_count = function(obj) {

		var ws_this = this;
		if(typeof(obj) === 'undefined') { var obj = this.form_canvas_obj; }

		// Run through each input that accepts text
		for(var field_id in this.field_data_cache) {

			if(!this.field_data_cache.hasOwnProperty(field_id)) { continue; }

			var field = this.field_data_cache[field_id];

			// Process help?
			var help = this.get_object_meta_value(field, 'help', '', false, true);
			var process_help = (

				(help.indexOf('#character_') !== -1) ||
				(help.indexOf('#word_') !== -1)
			);

			// Process min or max?
			var process_min_max = (

				this.has_object_meta_key(field, 'min_length') ||
				this.has_object_meta_key(field, 'max_length') ||
				this.has_object_meta_key(field, 'min_length_words') ||
				this.has_object_meta_key(field, 'max_length_words')
			);

			if(process_min_max || process_help) {

				// Process count functionality on field
				var field_obj = $('#' + this.form_id_prefix + 'field-' + field_id, obj);
				if(!field_obj.length) { var field_obj = $('[id^=' + this.form_id_prefix + 'field-' + field_id + '-]:not([data-init-char-word-count]):not(iframe)', obj); }

				field_obj.each(function() {

					// Flag so it only initializes once
					$(this).attr('data-init-char-word-count', '');

					if(ws_this.form_character_word_count_process($(this))) {

						$(this).on('keyup change focus blur paste', function() { ws_this.form_character_word_count_process($(this)); });
					}
				});
			}
		}
	}

	// Text input and textarea character and word count - Process
	$.WS_Form.prototype.form_character_word_count_process = function(obj) {

		// Get help text
		var field_wrapper = obj.closest('[data-type]');
		var field_id = field_wrapper.attr('data-id');
		var field_repeatable_index = field_wrapper.attr('data-repeatable-index');
		var repeatable_suffix = (typeof(field_repeatable_index) !== 'undefined') ? '-repeat-' + field_repeatable_index : '';
		var field = this.field_data_cache[field_id];

		// Process invalid feedback

		// Get minimum and maximum character count
		var min_length = this.get_object_meta_value(field, 'min_length', '');
		min_length = (parseInt(min_length) > 0) ? parseInt(min_length) : false;

		var max_length = this.get_object_meta_value(field, 'max_length', '');
		max_length = (parseInt(max_length) > 0) ? parseInt(max_length) : false;

		// Get minimum and maximum word length
		var min_length_words = this.get_object_meta_value(field, 'min_length_words', '');
		min_length_words = (parseInt(min_length_words) > 0) ? parseInt(min_length_words) : false;

		var max_length_words = this.get_object_meta_value(field, 'max_length_words', '');
		max_length_words = (parseInt(max_length_words) > 0) ? parseInt(max_length_words) : false;

		// Calculate sizes
		var val = obj.val();

		var character_count = val.length;
		var character_remaining = (max_length !== false) ? max_length - character_count : false;
		if(character_remaining < 0) { character_remaining = 0; }

		var word_count = this.get_word_count(val);
		var word_remaining = (max_length_words !== false) ? max_length_words - word_count : false;
		if(word_remaining < 0) { word_remaining = 0; }

		// Check minimum and maximums counts
		var count_invalid = false;
		var count_invalid_message_array = [];

		if((min_length !== false) && (character_count < min_length)) {

			count_invalid_message_array.push(this.language('error_min_length', min_length));
			count_invalid = true;
		}
		if((max_length !== false) && (character_count > max_length)) {

			count_invalid_message_array.push(this.language('error_max_length', max_length));
			count_invalid = true;
		}
		if((min_length_words !== false) && (word_count < min_length_words)) {

			count_invalid_message_array.push(this.language('error_min_length_words', min_length_words));
			count_invalid = true;
		}
		if((max_length_words !== false) && (word_count > max_length_words)) {

			count_invalid_message_array.push(this.language('error_max_length_words', max_length_words));
			count_invalid = true;
		}

		// Get object ID
		var object_id = obj.closest('[data-id]').attr('data-id');

		// Custom invalid feedback text
		var invalid_feedback_obj = $('#' + this.form_id_prefix + 'invalid-feedback-' + object_id + repeatable_suffix);

		// Check if required
		if(
			(typeof(obj.attr('required')) !== 'undefined') ||
			(val.length > 0)
		) {

			// Check if count_invalid
			if(count_invalid) {

				// Set invalid feedback
				this.set_invalid_feedback(obj, invalid_feedback_obj, count_invalid_message_array.join(' / '), object_id);

			} else {

				// Reset invalid feedback
				this.set_invalid_feedback(obj, invalid_feedback_obj, '', object_id);
			}

		} else {

			// Reset invalid feedback
			this.set_invalid_feedback(obj, invalid_feedback_obj, '', object_id);
		}

		// Process help
		var help = this.get_object_meta_value(field, 'help', '', false, true);

		// If #character_ and #word_ not present, don't bother processing
		if(
			(help.indexOf('#character_') === -1) &&
			(help.indexOf('#word_') === -1)
		) {
			return true;
		}

		// Get language
		var character_singular = this.language('character_singular');
		var character_plural = this.language('character_plural');
		var word_singular = this.language('word_singular');
		var word_plural = this.language('word_plural');

		// Set mask values
		var mask_values_help = {

			// Characters
			'character_count': 				character_count,
			'character_count_label': 		(character_count == 1 ? character_singular : character_plural),
			'character_remaining': 			(character_remaining !== false) ? character_remaining : '',
			'character_remaining_label': 	(character_remaining == 1 ? character_singular : character_plural),
			'character_min': 				(min_length !== false) ? min_length : '',
			'character_min_label': 			(min_length !== false) ? (min_length == 1 ? character_singular : character_plural) : '',
			'character_max': 				(max_length !== false) ? max_length : '',
			'character_max_label': 			(max_length !== false) ? (max_length == 1 ? character_singular : character_plural) : '',

			// Words
			'word_count': 			word_count,
			'word_count_label': 	(word_count == 1 ? word_singular : word_plural),
			'word_remaining': 		(word_remaining !== false) ? word_remaining : '',
			'word_remaining_label': (word_remaining == 1 ? word_singular : word_plural),
			'word_min': 			(min_length_words !== false) ? min_length_words : '',
			'word_min_label': 		(min_length_words !== false) ? (min_length_words == 1 ? word_singular : word_plural) : '',
			'word_max': 			(max_length_words !== false) ? max_length_words : '',
			'word_max_label': 		(max_length_words !== false) ? (max_length_words == 1 ? word_singular : word_plural) : ''
		};

		// Parse help mask
		var help_parsed = this.mask_parse(help, mask_values_help);

		// Update help HTML
		var help_id = this.form_id_prefix + 'help-' + field.id + repeatable_suffix;
		$('#' + help_id).html(help_parsed);

		return true;
	}

	// Get word count of a string
	$.WS_Form.prototype.get_word_count = function(input_string) {

		// Trim input string
		input_string = input_string.trim();

		// If string is empty, return 0
		if(input_string.length == 0) { return 0; }

		// Return word count
		return input_string.trim().replace(/\s+/gi, ' ').split(' ').length;
	}

	// API Call
	$.WS_Form.prototype.api_call_hash = function(response) {

		var hash_ok = true;
		if(typeof response.hash === 'undefined') { hash_ok = false; }
		if(hash_ok && (response.hash.length != 32)) { hash_ok = false; }
		if(hash_ok) {

			// Set hash
			this.hash_set(response.hash)
		}

		return hash_ok;
	}

	// Hash - Set
	$.WS_Form.prototype.hash_set = function(hash, cookie_set) {

		if(typeof cookie_set === 'undefined') { cookie_set = false; }

		if(hash != this.hash) {

			// Set hash
			this.hash = hash;

			// Set hash cookie
			cookie_set = true;

		}

		if(cookie_set) {

			this.cookie_set('hash', this.hash);
		}
	}

	// Generate password
	$.WS_Form.prototype.generate_password = function(length) {

		var password = '';
		var characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+~`|}{[]\\:;?><,./-=';
		
		for(var i = 0; i < length; ++i) { password += characters.charAt(Math.floor(Math.random() * characters.length)); }

		return password;
	}

	// Form - Statistics
	$.WS_Form.prototype.form_stat = function() {

		// Add view
		if(ws_form_settings.stat) { this.form_stat_add_view(); }
	}

	// Add view statistic
	$.WS_Form.prototype.form_stat_add_view = function() {

		// Call AJAX
		$.ajax({ method: 'POST', url: ws_form_settings.url + 'form/' + this.form_id + '/stat/add_view/' });
	}

	// On load
	$(function() {

		window.wsf_form_instances = [];

		// Render each form
		$('.wsf-form').each(function() {

			var id = $(this).attr('id');
			var instance_id = $(this).attr('data-instance-id');
			var form_id = $(this).attr('data-id');

			var ws_form = new $.WS_Form();
			window.wsf_form_instances[instance_id] = ws_form;

			ws_form.render({

				'obj' : 		'#' + id,
				'form_id':		form_id
			});
		});
	});

})(jQuery);


