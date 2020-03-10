(function($) {

	'use strict';

	// Set is_admin
	$.WS_Form.prototype.set_is_admin = function() { return true; }

	// One time init for admin page
	$.WS_Form.prototype.init = function() {

		// Set globals
		this.set_globals();

		// Build form
		this.form_build();

		// Size calculations
		this.admin_size();

		// Sidebar reset
		this.sidebar_reset();

		// Window resizing
		this.window_resize_init();

		// Key down events
		this.keydown_events_init();

		// Intro
		this.intro();
	};

	// Key down events
	$.WS_Form.prototype.keydown_events_init = function() {

		// Key down events
		$(document).keydown(function(e) {

			// Command (Mac) / Ctrl (PC)
			var ctrl = ((typeof e.metaKey !== 'undefined') ? e.metaKey : false) || ((typeof e.ctrlKey !== 'undefined') ? e.ctrlKey : false);

			if(typeof $.WS_Form.this.keydown[e.keyCode] === 'object') {

				var keydown = $.WS_Form.this.keydown[e.keyCode];

				if((keydown.ctrl_key && ctrl) || (!keydown.ctrl_key)) {

					e.preventDefault();
					keydown.function();
				}
			}
		});
	}

	// Window - Resize - Init
	$.WS_Form.prototype.window_resize_init = function(sidebar_obj) {

		$(window).on('resize', function() { 

			// Toolbox sidebar reopened if it was closed and screen goes beyond mobile cut-off
			if(	(($('#wsf-sidebars').attr('data-current') == 'toolbox') || ($('#wsf-sidebars').attr('data-current') == undefined)) &&
				$('#wsf-sidebar-toolbox').hasClass('wsf-sidebar-closed') &&
				window.matchMedia('(min-width: 851px)').matches
			) {

				// Sidebar - Toolbox - Open
				$.WS_Form.this.sidebar_open('toolbox');
			}

			// Sidebar expanded resizing
			if($.WS_Form.this.sidebar_expanded_obj !== false) {

				$.WS_Form.this.sidebar_resize(sidebar_obj);
			}
		});
	}

	// Set global variables once for performance
	$.WS_Form.prototype.set_globals = function(framework_override, admin_public) {

		// Get framework ID
		this.framework_id = (typeof(framework_override) !== 'undefined' ? framework_override : $.WS_Form.settings_plugin.framework);

		// Get framework settings
		this.framework = $.WS_Form.frameworks.types[this.framework_id];

		// Get current framework for tabs
		this.framework_fields = this.framework['fields'][typeof(admin_public) !== 'undefined' ? admin_public : 'admin'];

		// Get invalid_feedback placeholder mask
		this.invalid_feedback_mask_placeholder = '';
		if(typeof $.WS_Form.meta_keys['invalid_feedback'] !== 'undefined') {

			if(typeof $.WS_Form.meta_keys['invalid_feedback']['mask_placeholder'] !== 'undefined') {
				
				this.invalid_feedback_mask_placeholder = $.WS_Form.meta_keys['invalid_feedback']['mask_placeholder'];
			}
		}
	}

	// Intro
	$.WS_Form.prototype.intro = function() {

		// Intro
		if(typeof(introJs) !== 'function') { return; }

		var ws_this = this;

		// Request intro
		$.WS_Form.this.api_call('helper/intro/', 'GET', false, function(hint_steps_config) {

			// Loader off
			$.WS_Form.this.loader_off();

			if(typeof(hint_steps_config) !== 'object') { return; }

			// Build hint steps
			var hints = [];
			var hints_sidebar_open = [];
			var hints_button_url = [];
			for(var hint_config_index in hint_steps_config) {

				var hint_step_config = hint_steps_config[hint_config_index];
				var hint_step = {}

				if(typeof(hint_step_config.hint) !== 'undefined') { hint_step.hint = hint_step_config.hint; }
				if(typeof(hint_step_config.position) !== 'undefined') { hint_step.hintPosition = hint_step_config.position; }
				if(typeof(hint_step_config.element) !== 'undefined') { hint_step.element = $(hint_step_config.element)[0]; }
				if(typeof(hint_step_config.sidebar_open) !== 'undefined') { hints_sidebar_open[hint_config_index] = hint_step_config.sidebar_open; }
				if(typeof(hint_step_config.button_url) !== 'undefined') { hints_button_url[hint_config_index] = hint_step_config.button_url; }

				hints.push(hint_step);
			}

			var intro = introJs();

			intro.setOptions({

				hints: hints
			});

			// On hint click
			intro.onhintclick(function(hint_element, item, step_id) {

				if(typeof(hints_sidebar_open[step_id]) !== 'undefined') {

					var id = hints_sidebar_open[step_id];

					// Open
					var meta_key_open_function = 'sidebar_' + id + '_open';
					if(typeof(window[meta_key_open_function]) === 'function') {

						// Get dom objects
						var obj_outer = $('#wsf-sidebar-' + id);
						var obj_inner = $('.wsf-sidebar-inner', obj_outer);

						window[meta_key_open_function]($.WS_Form.this, obj_inner, $(this));

					} else {

						// Open
						$.WS_Form.this.sidebar_open(id);
					}
				}

				if(typeof(hints_button_url[step_id]) !== 'undefined') {

					var url = hints_button_url[step_id];

					setTimeout(function() {

						$('.introjs-tooltiptext').append('&nbsp;<a href="' + url + '" class="introjs-button" role="button" target="_blank">' + ws_this.language('intro_learn_more') + '</a>');
						$('.introjs-tooltiptext').append('<div data-action="wsf-intro-skip" class="wsf-intro-skip">' + ws_this.language('intro_skip') + '</div>');

						$('[data-action="wsf-intro-skip"]', $('.introjs-tooltiptext')).click(function() {

							introJs().hideHints();
						});

					}, 50);
				}

			});

			intro.addHints();
		});
	}

	// Render any interface elements that rely on the form object (Also called on a form push)
	$.WS_Form.prototype.form_render = function() {

		// Form name
		$('[data-action="wsf-form-label"]').val(this.form.label);

		if(!this.form_interface) {

			// Sidebars
			this.sidebars_render();

			// Render the breakpoints
			this.breakpoints();

			// Form - Label - Change
			$('[data-action="wsf-form-label"]').change(function(event) {

				// If change occurred as a result of someone changing the field (and not a JQuery val update)
				if(event.originalEvent) {

					// Get label
					var label = $(this).val();
					if(label == '') { label = $.WS_Form.this.get_label_default('form'); $(this).val(label);}

					// Change its value
					$.WS_Form.this.form.label = label;

					// Push the change to the API
					$.WS_Form.this.form_put();
				}
			});

			// Form - Label - Keyup
			$('[data-action="wsf-form-label"]').keyup(function(event) {

				$('#wsf-sidebar-form [name="label"]').val($(this).val());
			});

			// Publish
			$('[data-action="wsf-publish"]').click(function() { $.WS_Form.this.form_publish(); });

			// Upload
			$('[data-action="wsf-form-upload"]').click(function() {

				// Click file input
				$('input[id="wsf-form-upload-file"]').click();
			});
			$('input[id="wsf-form-upload-file"]').change(function() {

				var files = $('input[id="wsf-form-upload-file"]').prop("files");

				if(files.length > 0) {

					var form_upload_window = $('.wsf-form-upload-json-window', $.WS_Form.this.form_obj);
					form_upload_window.fadeIn(200);
					$.WS_Form.this.form_upload_json(files, form_upload_window, function(response) {

						// Redraw form
						$.WS_Form.this.form_build();
					});
				}
			});

			// Download
			$('[data-action="wsf-form-download"]').click(function() { $.WS_Form.this.form_download(); });

			// Undo
			$('[data-action="wsf-undo"]').click(function() { $.WS_Form.this.undo(); });

			// Redo
			$('[data-action="wsf-redo"]').click(function() { $.WS_Form.this.redo(); });

			// Event - Mouse up
			this.mouseup_mode = false;
			$(document).mouseup(function() {

				switch($.WS_Form.this.mouseup_mode) {

					case 'column_size' :

						$.WS_Form.this.column_size_change_release();
						break;

					case 'offset' :

						$.WS_Form.this.offset_change_release();
						break;
				}
			});

			this.form_interface = true;
		}

		// Groups - Tabs - Add
		var add_group_tab = '<li class="wsf-group-add wsf-ui-cancel" title="' + this.language('add_group') + '"><button tabindex="-1">' + this.svg('group') + this.svg('plus') + '</button></li>';

		$('.wsf-group-tabs').append(add_group_tab);
		$('.wsf-group-add button').click(function() { $.WS_Form.this.group_post($(this)); });

		// Groups - Tabs - Initialize
		this.group_tabs_init();

		// Update undo/redo update button
		this.undo_redo_update();

		// Build groups
		$('.wsf-group').each(function() {

			$.WS_Form.this.group_render($(this));
		});


		// Initialize draggable
		this.init_ui();

		// Form upload
		this.form_obj.append('<div class="wsf-form-upload-json-window"><div class="wsf-form-upload-json-window-content"><h1>' + this.language('form_upload_json') + '</h1><div class="wsf-uploads"></div></div></div>');

		// Drag enter
		this.form_obj.on('dragenter', function (e) {

			e.stopPropagation();
			e.preventDefault();
			$('.wsf-form-upload-json-window', $(this)).fadeIn(200);
		});

		// Drag over
		$('.wsf-form-upload-json-window', this.form_obj).on('dragover', function (e) {

			e.stopPropagation();
			e.preventDefault();
		});

		// Drop
		$('.wsf-form-upload-json-window', this.form_obj).on('drop', function (e) {

			e.preventDefault();

			if(confirm($.WS_Form.this.language('form_import_confirm'))) {

				var files = e.originalEvent.dataTransfer.files;
				$.WS_Form.this.form_upload_json(files, $(this), function(response) {

					// Redraw form
					$.WS_Form.this.form_build();
				});
				
			} else {

				$('.wsf-form-upload-json-window', $.WS_Form.this.form_obj).fadeOut(200);
			}
		});

		// Drag leave
		$('.wsf-form-upload-json-window', this.form_obj).on('dragleave', function (e) {

			$('.wsf-form-upload-json-window', $.WS_Form.this.form_obj).fadeOut(200);
		});

		// Check multiple field
		this.field_check_multiple();
	}

	// API - Form - PUT
	$.WS_Form.prototype.form_put = function(full, form_build, history_suppress, complete) {

		if(typeof full === 'undefined') { var full = false; }
		if(typeof form_build === 'undefined') { var form_build = true; }
		if(typeof history_suppress === 'undefined') { var history_suppress = false; }

		var form = $.extend(true, {}, this.form); // Deep clone

		// If not doing a full push, we'll remove the groups key to provide a smaller cut down form
		if(!full) { delete form.groups; }

		// Render interface
		if(form_build) { $.WS_Form.this.form_build(); }

		// Suppress history?
		if(history_suppress) { form.history_suppress = 'on'; }

		// Call AJAX request
		this.api_call('form/' + this.form_id + (full ? '/full' : ''), 'PUT', {'form': form}, function(response) {

			if(typeof complete !== 'undefined') { complete(); }

			// Loader off
			$.WS_Form.this.loader_off();
		});
	}

	// Form - Publish
	$.WS_Form.prototype.form_publish = function() {

		// Loader on
		this.loader_on();

		// Call AJAX request
		this.api_call('form/' + this.form_id + '/publish/', 'PUT', false, function(response) {

			// Loader off
			$.WS_Form.this.loader_off();
		});
	}

	// Form - Uploader
	$.WS_Form.prototype.form_upload_json = function(files, obj, success_callback) {

		// Hide H1
		$('h1', obj).hide();

		if(files.length == 0) { return false; }

		// Create form data
		var form_data = new FormData();
		form_data.append('form_id', this.form_id);
		form_data.append('file', files[0]);

		// Reset sidebar
		this.sidebar_reset();

		// Create status bar for this file
		var status_bar = new this.upload_status_bar(obj)

		// Populate status_bar
		status_bar.populate(files[0].name, files[0].size);

		// Send file to the server using AJAX
		this.form_upload_ajax(form_data, status_bar, obj, success_callback);
	}

	// Form - Uploaded CSV - AJAX request
	$.WS_Form.prototype.form_upload_ajax = function(form_data, status_bar, obj, success_callback) {

		var jqXHR=$.ajax({

			beforeSend: function(xhr) {

				xhr.setRequestHeader('X-WP-Nonce', ws_form_settings.nonce);
			},

			xhr: function() {

				// Upload progress
				var xhrobj = $.ajaxSettings.xhr();
				if (xhrobj.upload) {

					xhrobj.upload.addEventListener('progress', function(event) {

						var percent = 0;
						var position = event.loaded || event.position;
						var total = event.total;
						if (event.lengthComputable) {
							percent = Math.ceil(position / total * 100);
						}

						status_bar.set_progress(percent);

					}, false);
				}

				return xhrobj;
			},

			url: ws_form_settings.url + 'form/' + this.form_id + '/upload/json',
			type: 'POST',
			contentType: false,
			processData: false,
			cache: false,
			data: form_data,

			statusCode: {

				// Success
				200: function(response) {

					// Set progress bar to 100%
					status_bar.set_progress(100);

					if(typeof(response.form) !== 'undefined') {

						// If full form returned by API, load it
						if((typeof(response.form_full) !== 'undefined') && response.form_full) {

							$.WS_Form.this.form = response.form;

							// Build data cache
							$.WS_Form.this.data_cache_build();

							// Look for checksum
							if(typeof(response.form.checksum) !== 'undefined' && typeof(response.form.published_checksum) !== 'undefined') {

								// Save published checksum
								$.WS_Form.this.published_checksum = response.form.published_checksum;

								// Render publish button
								$.WS_Form.this.publish_render(response.form.checksum);
							}
						}
					}

					// Save if we are using undo function (Called after success_callback to ensure response returned is in caches)
					if(typeof(response.history) !== 'undefined') {

						// Push to history stack
						$.WS_Form.this.history_push(response);
					}

					// Call success script
					if(typeof(success_callback) === 'function') { success_callback(response); }
				},

				// Bad request (Error from API)
				400: function(response) {

					var data = response.responseJSON;

					if(data.error) {

						// Show error
						$.WS_Form.this.error('error_bad_request', data.error_message);

						// Hide drag and drop zone
						obj.fadeOut(200);
					}
				},

				// Forbidden (Error from API)
				403: function(response) {

					// Show error
					$.WS_Form.this.error('error_forbidden', url);

					// Hide drag and drop zone
					obj.fadeOut(200);
				},

				// Not found (Error from API)
				404: function(response) {

					// Show error
					$.WS_Form.this.error('error_not_found', url);

					// Hide drag and drop zone
					obj.fadeOut(200);
				},

				// Server error
				500: function(response) {

					// Show error
					$.WS_Form.this.error('error_server', url);

					// Hide drag and drop zone
					obj.fadeOut(200);
				}
			}
		});

		status_bar.set_abort(jqXHR);
	}

	// Form - Publish
	$.WS_Form.prototype.form_download = function() {

		// Build downloader
		var downloader_html = '<form id="wsf-data-grid-downloader" action="' + ws_form_settings.url + 'form/' + this.form_id + '/download/json" method="post">';

		downloader_html += '<input type="hidden" name="form_id" value="' + this.form_id + '" />';
		downloader_html += '<input type="hidden" name="_wpnonce" value="' + ws_form_settings.nonce + '" />';

		downloader_html += '</form>';

		// Inject into body
		var downloader = $('body').append(downloader_html);

		// Submit
		$('#wsf-data-grid-downloader').submit();

		// Remove
		$('#wsf-data-grid-downloader').remove();
	}

	// Group init
	$.WS_Form.prototype.group_render = function(obj) {

		// Get group ID
		var group_id = obj.attr('data-id');

		// Add column helper class
		if($.WS_Form.settings_plugin.helper_columns == 'on') { obj.addClass('wsf-column-helper'); }

		// Get group data
		var this_group_data = this.group_data_cache[group_id];

		// Add settings to each group tab
		var tab_obj = $('.wsf-group-tab[data-id="' + group_id + '"]');
		if(tab_obj.length) {

			if(!tab_obj.find('.wsf-settings').length) {

				// Settings
				var group_tab_html = this.settings_html('group', group_id, true);

				// Add group HTML
				tab_obj.append(group_tab_html);

				// Settings - Events
				this.settings_events(tab_obj, 'group');
			}
		}

		// Drag sections here
		var section_blank_html = '<li class="wsf-section-blank"><div>' + this.language('blank_section') + '</div></li>';
		obj.find('.wsf-sections').prepend(section_blank_html);

		// Create section add button
		if(!obj.find('.wsf-section-add').length) {

			// Section interface HTML
			var section_interface_html = '<div class="wsf-section-add" data-group-id="' + group_id + '" title="' + this.language('add_section') + '"><button>' + this.svg('section') + this.svg('plus') + '</button></div>';

			// Add section interface
			obj.append(section_interface_html);
			$('.wsf-section-add[data-group-id="' + group_id + '"] button').click(function() {

				$.WS_Form.this.section_post($(this));
			});
		}

		// Initialize label
		this.label_init(tab_obj);

		// Build sections
		obj.find(".wsf-section").each(function() {

			$.WS_Form.this.section_render($(this), false);
		})
	}

	// Group - Tabs - Init
	$.WS_Form.prototype.group_tabs_init = function(index) {

		if(typeof index === 'undefined') {

			// If index not specified, use the form tab_index meta value, or use value of 0 and create meta
			var index = this.get_object_meta_value(this.form, 'tab_index', 0, true);

		} else {

			// Save tab index
			$.WS_Form.this.group_tab_index_save(index);
		}

		// Modifying jQuery tabs to disable keydown events
		$.widget('ui.tabs', $.ui.tabs, {

			options: { keyboard: true },

			_tabKeydown: function(e) {

				if(this.options.keyboard) {

					this._super('_tabKeydown');

				} else {

					return false;
				}
			}
		});

		// Destroy tabs (Ensures subsequent calls work)
		if($('#' + this.form_obj_id).hasClass('ui-tabs')) {

			$('#' + this.form_obj_id).tabs('destroy');
		}
		$('#' + this.form_obj_id).tabs({

			active: index,
			activate: function(event, ui) {

				// Loader on
				$.WS_Form.this.loader_on();

				// Save tab index
				$.WS_Form.this.group_tab_index_save(ui.newTab.index());
			}
		});
	}

	// Group - Tabs - Index save
	$.WS_Form.prototype.group_tab_index_save = function(index) {

		// Store tab index to form meta data
		this.set_object_meta_value(this.form, 'tab_index', index);

		// Push tab_index to API (Suppress history)
		this.form_put(false, false, true);
	}

	// API - Group - POST
	$.WS_Form.prototype.group_post = function(obj) {

		// Loader on
		this.loader_on();

		// Pre-save current tab index to form meta (it will have changed because of the add)
		var tab_obj_index = obj.index();	// Tab index of '+', this index would be replaced with new group tab
		this.set_object_meta_value(this.form, 'tab_index', tab_obj_index);

		// Call AJAX request
		var call_obj = obj;
		$.WS_Form.this.api_call('group', 'POST', false, function(response) {

			// Get new group ID
			var group = $.extend(true, {}, response.data);
			var group_id = group.id;

			// Get HTML for group (This also adds the group to the group cache)
			var group_html = $.WS_Form.this.get_group_html(group);

			// Add HTML to form
			$('#wsf-form').append(group_html);

			var group_obj = $('.wsf-group[data-id="' + group_id + '"]');

			// Add group to tabs
			var tab_html = $.WS_Form.this.get_tab_html(group);
			$('.wsf-group-tabs li.wsf-group-add').before(tab_html);

			// Insert new group tab HTML after tab for current object
			var new_tab_obj = $('.wsf-group-tab[data-id="' + group_id + '"]');

			// Initialize tabs
			$.WS_Form.this.group_tabs_init(new_tab_obj.index());

			// Render group
			$.WS_Form.this.group_render(group_obj);

			// Trigger label edit
			$('input', new_tab_obj).first().trigger('dblclick');

			// Initialize UI
			$.WS_Form.this.init_ui();

			// Loader
			$.WS_Form.this.loader_off();
		});
	}

	// API - Group - PUT - SORT INDEX
	$.WS_Form.prototype.group_put_sort_index = function(obj) {

		// Get next sibling ID (0 = Last or only element in form)
		var next_sibling_id = (typeof obj.next().attr('data-id') !== 'undefined') ? obj.next().attr('data-id') : 0;
		if(this.next_sibling_id_old == next_sibling_id) { return false; }

		// Loader on
		this.loader_on();

		// Get group ID
		var group_id = obj.attr('data-id');

		// Build request parameters
		var params = {

			'next_sibling_id':	next_sibling_id
		}

		// Save current tab index to form meta (it will have changed because of the sorting)
		this.set_object_meta_value(this.form, 'tab_index', obj.index());

		// Call AJAX request
		var call_obj = obj;
		this.api_call('group/' + group_id + '/sort_index', 'PUT', params, function(response) {

			// Loader off
			$.WS_Form.this.loader_off();
		});
	}

	// API - Section - PUT - CLONE
	$.WS_Form.prototype.group_put_clone = function(obj) {

		// Loader on
		this.loader_on();

		// Read data attributes
		var group_id = obj.attr('data-id');

		// Get tab index
		var tab_obj_index = obj.index();

		// Pre-save current tab index to form meta (it will have changed because of the add)
		this.set_object_meta_value(this.form, 'tab_index', tab_obj_index + 1);

		// Get next sibling ID (0 = Last or only element in group)
		var next_sibling_id = (typeof obj.next().attr('data-id') !== 'undefined') ? obj.next().attr('data-id') : 0;

		// Build request parameters
		var params = {

			'next_sibling_id':	next_sibling_id
		}

		// Call AJAX request
		var call_obj = obj;
		this.api_call('group/' + group_id + '/clone', 'PUT', params, function(response) {

			// Get group ID
			var group_id_new = response.data.id;

			// Get group tab HTML
			var group_tab_html = $.WS_Form.this.get_tab_html(response.data);

			// Insert new group tab HTML after tab for current object
			var new_tab_obj = $(group_tab_html).insertAfter($('.wsf-group-tab[data-id="' + group_id + '"]'));

			// Get group HTML (Stores it to cache)
			var group_html = $.WS_Form.this.get_group_html(response.data);

			// Insert new group HTML after obj
			$(group_html).insertAfter(call_obj);

			// Get new group object
			var new_obj = $('.wsf-group[data-id="' + group_id_new + '"]');

			// Build group
			$.WS_Form.this.group_render(new_obj);

			// Groups - Tabs - Initialize
			$.WS_Form.this.group_tabs_init(new_tab_obj.index());

			// Update blank fields
			$.WS_Form.this.object_blank_update(new_obj);

			// Trigger label edit
			$('input', new_tab_obj).first().trigger('dblclick');

			// Initialize draggable elements to work with new section
			$.WS_Form.this.init_ui();

			// Loader off
			$.WS_Form.this.loader_off();
		});
	}

	// Section - Render
	$.WS_Form.prototype.section_render = function(obj) {

		// Get section ID
		var section_id = obj.attr('data-id');

		// Get whether disabled or not
		var section = this.section_data_cache[section_id];

		// Read section settings
		var section_hidden = this.get_object_meta_value(section, 'hidden_section', false);
		var section_disabled = this.get_object_meta_value(section, 'disabled_section', false);
		var section_repeatable = this.get_object_meta_value(section, 'section_repeatable', false);

		// Icon count
		var section_icon_count = 0;
		if(section_hidden) { section_icon_count++; }
		if(section_disabled) { section_icon_count++; }
		if(section_repeatable) { section_icon_count++; }

		// Apply icon count class
		var section_label = $('.wsf-section-label', obj);
		for(var section_icon_count_index = 0; section_icon_count_index < 4; section_icon_count_index++) {

			section_label.removeClass('wsf-section-icon-count-' + section_icon_count_index);
		}
		section_label.addClass('wsf-section-icon-count-' + section_icon_count);

		// Hidden
		if(section_hidden) { $('.wsf-section-hidden', obj).show(); } else { $('.wsf-section-hidden', obj).hide(); }

		// Disabled
		if(section_disabled) { $('.wsf-section-disabled', obj).show(); } else { $('.wsf-section-disabled', obj).hide(); }

		// Repeatable
		if(section_repeatable) { $('.wsf-section-repeatable', obj).show(); } else { $('.wsf-section-repeatable', obj).hide(); }

		// Add column helper class
		if($.WS_Form.settings_plugin.helper_columns == 'on') { obj.addClass('wsf-column-helper'); }

		// Settings
		if(obj.find('.wsf-settings-section').length == 0) {

			// Settings
			var section_html = this.settings_html('section', section_id, true);

			// Resize
			section_html += this.column_size_change_html();

			// Offset
			section_html += this.offset_change_html();

			// Add section HTML
			obj.append(section_html);

			// Settings - Events
			this.settings_events(obj, 'section');

			// Drag fields here
			var field_blank_html = '<li class="wsf-field-blank"><div>' + this.language('blank_field') + '</div></li>';
			obj.find('.wsf-fields').prepend(field_blank_html);

			// Column size - Change
			this.column_size_change_init(obj);

			// Offset - Change
			this.offset_change_init(obj);

			// Initialize label
			this.label_init(obj);
		}

		// Build fields
		obj.find('.wsf-field-wrapper').each(function() {

			$.WS_Form.this.field_render($(this));
		})
	}

	// API - Section - POST
	$.WS_Form.prototype.section_post = function(obj) {

		// Loader on
		this.loader_on();

		// Get group ID
		var group_id = obj.closest('.wsf-group').attr('data-id');

		// Build request parameters
		var params = {

			'group_id':	group_id,
		};

		// Call AJAX request
		var call_obj = obj;
		$.WS_Form.this.api_call('section', 'POST', params, function(response) {

			// Get new section ID
			var section = $.extend(true, {}, response.data);
			var section_id = section.id;

			// Get HTML for section (This also adds the section to the section cache)
			var section_html = $.WS_Form.this.get_section_html(section);

			// Add HTML to section UL
			$('.wsf-sections[data-id="' + group_id + '"]').append(section_html);

			var section_obj = $('.wsf-section[data-id="' + section_id + '"]');

			// Render section
			$.WS_Form.this.section_render(section_obj);

			// Trigger label edit
			$('input', section_obj).first().trigger('dblclick');

			// Initialize draggable elements to work with new section
			$.WS_Form.this.init_ui();

			// Loader
			$.WS_Form.this.loader_off();
		});
	}

	// API - Section - PUT - SORT INDEX
	$.WS_Form.prototype.section_put_sort_index = function(obj) {

		// Get next sibling ID (0 = Last or only element in form)
		var next_sibling_id = (typeof obj.next().attr('data-id') !== 'undefined') ? obj.next().attr('data-id') : 0;

		// Get group_id section has been dragged to
		var group_id = obj.closest('.wsf-group').attr('data-id');

		// Check if it moved
		if((this.next_sibling_id_old == next_sibling_id) && (this.group_id_old == group_id)) { return false; }

		// Loader on
		this.loader_on();

		// Get section ID
		var section_id = obj.attr('data-id');

		// Build request parameters
		var params = {

			'group_id':		group_id,
			'next_sibling_id':	next_sibling_id
		}

		// Call AJAX request
		var call_obj = obj;
		$.WS_Form.this.api_call('section/' + section_id + '/sort_index', 'PUT', params, function(response) {

			// Loader off
			$.WS_Form.this.loader_off();
		});
	}

	// API - Section - PUT - CLONE
	$.WS_Form.prototype.section_put_clone = function(obj) {

		// Loader on
		this.loader_on();

		// Read data attributes
		var section_id = obj.attr('data-id');

		// Get next sibling ID (0 = Last or only element in section)
		var next_sibling_id = (typeof obj.next().attr('data-id') !== 'undefined') ? obj.next().attr('data-id') : 0;

		// Build request parameters
		var params = {

			'next_sibling_id':	next_sibling_id
		}

		// Call AJAX request
		var call_obj = obj;
		this.api_call('section/' + section_id + '/clone', 'PUT', params, function(response) {

			// Get section ID
			var section_id = response.data.id;

			// Get section HTML (Stores it to cache)
			var section_html = $.WS_Form.this.get_section_html(response.data);

			// Insert new section HTML after obj
			$(section_html).insertAfter(call_obj);

			// Get new section object
			var new_obj = $('.wsf-section[data-id="' + section_id + '"]');

			// Build section
			$.WS_Form.this.section_render(new_obj);

			// Trigger label edit
			$('input', new_obj).first().trigger('dblclick');

			// Update blank fields
			$.WS_Form.this.object_blank_update(new_obj);

			// Initialize draggable elements to work with new section
			$.WS_Form.this.init_ui();

			// Loader off
			$.WS_Form.this.loader_off();
		});
	}

	// Build field
	$.WS_Form.prototype.field_render = function(obj, field_data) {

		if(typeof(field_data) === 'undefined') {

			var field_id = obj.attr('data-id');
			var field_data = this.field_data_cache[field_id];

		} else {

			var field_id = field_data.id;
		}

		// Set field ID as title
		if($.WS_Form.settings_plugin.helper_field_id) {

			obj.attr('title', '#field(' + field_id + ')');
		}

		// Get column class array
		var class_array = this.column_class_array(field_data);
		var class_string = 'wsf-field-wrapper' + (class_array.length ? ' ' + class_array.join(' ') : '');
		obj.attr('class', class_string);

		// Get field data
		var field_type = $.WS_Form.field_type_cache[field_data.type];

		// Variables for rendering field
		var this_field_label = field_data.label;

		if(typeof field_type !== 'undefined') {

			var field_type_label = field_type.label;
			var field_type_icon = field_type.icon;
			var field_type_has_label = (typeof field_type.mask_field_label !== 'undefined');
			var field_type_has_mask_preview = (typeof field_type.mask_preview !== 'undefined');
			var field_type_multiple = (typeof field_type.multiple !== 'undefined') ? field_type.multiple : true;

		} else {

			var field_type_label = this.language('error_field_type_unknown');
			var field_type_icon = this.svg('default');
			var field_type_has_label = true;
			var field_type_has_mask_preview = false;
		}

		var field_required = this.get_object_meta_value(field_data, 'required', false);
		var field_hidden = this.get_object_meta_value(field_data, 'hidden', false);
		var field_disabled = this.get_object_meta_value(field_data, 'disabled', false);
		var field_readonly = this.get_object_meta_value(field_data, 'readonly', false);

		// Determine if there are required settings and field setting errors
		var field_setting_error = this.field_setting_error(field_data);

		if(field_setting_error.has_required_setting) { obj.attr('data-required-setting', ''); }

		// Icons
		var field_icon_array = [];
		if(field_required) { field_icon_array.push('<span class="wsf-required"></span>'); }
		if(field_hidden) { field_icon_array.push(this.svg('hidden')); }
		if(field_disabled) { field_icon_array.push(this.svg('disabled')); }
		if(field_readonly) { field_icon_array.push(this.svg('readonly')); }
		if(field_setting_error.field_setting_error !== false) { field_icon_array.push('<span class="wsf-required-setting">' + this.svg('warning') + '</span>'); }
		var field_icon_count = field_icon_array.length;

		// Label
		var field_html = '<div class="wsf-field-inner">';
		field_html += '<div class="wsf-field-label' + ((field_icon_count > 0) ? ' wsf-field-icon-count-' + field_icon_count : '') + '">' + field_type_icon;
		field_html += field_icon_array.join('');
		field_html += '<input type="text" value="' + this.html_encode(this_field_label) + '" data-label="' + field_id + '" readonly maxlength="190"></div>';
		field_html += '<div class="wsf-field-type">' + this.html_encode(field_type_label);
		if($.WS_Form.settings_plugin.helper_field_id) { field_html += '<span class="wsf-field-id">' + this.language('id') + ': ' + field_id + '</span>'; }
		field_html += '</div>';

		if(field_type_has_mask_preview) {

			// Parse field admin field_preview
			var text_editor = this.get_object_meta_value(field_data, 'text_editor', '', true);
			var html_editor = this.get_object_meta_value(field_data, 'html_editor', '', true);
			var mask_values = {'text_editor': text_editor, 'html_editor': html_editor};
			var field_preview = this.mask_parse(field_type.mask_preview, mask_values);

			// Fix any open tags
			var div = document.createElement('div');
			div.innerHTML = field_preview;
			field_preview = div.innerHTML;

			field_html += '<div class="wsf-field-preview">' + field_preview + '</div>';
		}

		field_html += '</div>';

		// Check to see if resize should be ignored
		var mask_wrappers_drop = (typeof field_type['mask_wrappers_drop'] !== 'undefined') ? field_type['mask_wrappers_drop'] : false;

		// reCAPTCHA override
		if((field_data.type == 'recaptcha') && (this.get_object_meta_value(field_data, 'recaptcha_recaptcha_type', 'default') == 'invisible')) { mask_wrappers_drop = true; }

		// Settings
		field_html += this.settings_html('field', field_id, field_type_multiple);

		if(!mask_wrappers_drop) {

			// Column size icon
			field_html += this.column_size_change_html();

			// Offset icon
			field_html += this.offset_change_html();
		}

		// Inject HTML
		obj.html(field_html);

		// Settings - Events
		this.settings_events(obj, 'field');

		// Initialize label
		this.label_init(obj);

		if(!mask_wrappers_drop) {

			// Make column size changeable
			this.column_size_change_init(obj);

			// Make offset changeable
			this.offset_change_init(obj);
		}
	}

	// Fields settings error checking
	$.WS_Form.prototype.fields_setting_error = function() {

		$('[data-required-setting]', this.form_obj).each(function() {

			$.WS_Form.this.field_render($(this));
		})
	}

	// Field settings error check
	$.WS_Form.prototype.field_setting_error = function(field) {

		var field_setting_error = [];

		if(typeof(field.meta) === 'undefined') { return false; }

		if(
			(typeof($.WS_Form.meta_keys_required_setting[field.type]) === 'undefined') &&
			(typeof($.WS_Form.field_type_cache[field.type]) !== 'undefined')
		) {

			var field_type = $.WS_Form.field_type_cache[field.type];

			var meta_keys = this.field_type_meta_keys(field_type, 'required_setting');

			$.WS_Form.meta_keys_required_setting[field.type] = meta_keys;
		}

		var has_required_setting = ($.WS_Form.meta_keys_required_setting[field.type].length > 0);

		if(has_required_setting) {

			for(var meta_key_required_index in $.WS_Form.meta_keys_required_setting[field.type]) {

				var meta_key = $.WS_Form.meta_keys_required_setting[field.type][meta_key_required_index];

				// Check for blank
				if(
					(typeof(field.meta[meta_key]) === 'undefined') ||
					(field.meta[meta_key] == '')

				) { field_setting_error.push(meta_key); }

				// Check value if this is a select for choosing a field
				if(typeof($.WS_Form.meta_keys[meta_key]) === 'undefined') { continue; }
				var meta_key_config = $.WS_Form.meta_keys[meta_key];

				if(typeof(meta_key_config['type']) === 'undefined') { continue; }
				if(meta_key_config['type'] == 'select') {

					if(typeof(meta_key_config['options']) === 'undefined') { continue; }
					if(meta_key_config['options'] == 'fields') {

						var field_id = field.meta[meta_key];

						if(typeof(this.field_data_cache[field_id]) === 'undefined') {

							field_setting_error.push(meta_key);
						}
					}

					if(meta_key_config['options'] == 'sections') {

						var section_id = field.meta[meta_key];

						if(typeof(this.section_data_cache[section_id]) === 'undefined') {

							field_setting_error.push(meta_key);
						}
					}
				}
			}
		}

		return {'has_required_setting': has_required_setting, 'field_setting_error': ((field_setting_error.length > 0) ? field_setting_error : false) };
	}

	// Blank section / field update
	$.WS_Form.prototype.object_blank_update = function(obj) {

		if(typeof obj === 'undefined') { var obj = $('.wsf-group'); }

		// Sections
		$('.wsf-sections', obj).each(function() {

			var section_wrapper_count = $('.wsf-section:not(.ui-sortable-helper)', $(this)).length;
			var helper_count = $('.wsf-section-wrapper.ui-sortable-helper', $(this)).length;
			var placeholder_count = $('.wsf-section-placeholder', $(this)).length;
			var blank_count = $('.wsf-section-blank:visible', $(this)).length;
			var total_count = section_wrapper_count + placeholder_count;

			var blank_section_obj = $('.wsf-section-blank', $(this));
			if(total_count == 0) {

				blank_section_obj.show();

			} else {

				blank_section_obj.hide();
			}
		});

		// Fields
		$('.wsf-fields', obj).each(function() {

			var field_wrapper_count = $('.wsf-field-wrapper:not(.ui-sortable-helper)', $(this)).length;
			var helper_count = $('.wsf-field-wrapper.ui-sortable-helper', $(this)).length;
			var placeholder_count = $('.wsf-field-placeholder', $(this)).length;
			var blank_count = $('.wsf-field-blank:visible', $(this)).length;
			var total_count = field_wrapper_count + placeholder_count;

			// If dragging a new field, this ensure the out even fires correctly
			if($.WS_Form.this.dragged_field) {

				if((field_wrapper_count == 0) && (helper_count == 1) && (placeholder_count == 1) && (blank_count == 0)) { total_count = 0; }
			}

			var blank_field_obj = $('.wsf-field-blank', $(this));
			if(total_count == 0) {

				blank_field_obj.show();

			} else {

				blank_field_obj.hide();
			}
		});
	}

	// API - Field - POST
	$.WS_Form.prototype.field_post = function(obj) {

		// Loader on
		this.loader_on();

		// Blur any labels that are being edited to save them
		$('.wsf-field-wrapper input:not([readonly])').blur();

		// Get section ID
		var section_id = obj.parent().attr('data-id');

		// Get field type
		var type = obj.attr('data-type');

		// Get field width (factor)
		var width_factor = obj.attr('data-width-factor');

		// Get next sibling ID (0 = Last or only element in section)
		var next_sibling_id = (typeof obj.next().attr('data-id') !== 'undefined') ? obj.next().attr('data-id') : 0;

		// Build request parameters
		var params = {

			'section_id':		section_id,
			'type':				type,
			'next_sibling_id':	next_sibling_id
		};

		if(width_factor) {

			params.width_factor = width_factor;
		}

		// Call AJAX request
		var call_obj = obj;
		$.WS_Form.this.api_call('field', 'POST', params, function(response) {

			// Set data attribute
			call_obj.attr('data-id', response.data.id);

			// Store data to field_data_cache array
			$.WS_Form.this.field_data_cache[response.data.id] = $.extend(true, {}, response.data);

			// Build field
			$.WS_Form.this.field_render(call_obj);

			// Trigger label edit
			$('input', obj).first().trigger('dblclick');

			// Update blank fields
			$.WS_Form.this.object_blank_update($('.wsf-section[data-id="' + section_id + '"]'));

			// Check multiple field
			$.WS_Form.this.field_check_multiple();

			// Loader off
			$.WS_Form.this.loader_off();
		});
	}

	// field_type_click_section_id set
	$.WS_Form.prototype.field_type_click_section_id_set = function() {

		var group_index = this.get_object_meta_value(this.form, 'tab_index', 0, true);

		if(typeof(this.form.groups) === 'undefined') { return false; }
		if(typeof(this.form.groups[group_index]) === 'undefined') { return false; }

		var group = this.form.groups[group_index];

		if(typeof(group.sections) === 'undefined') { return false; }

		var section_id = false;
		var field_date_updated_max = false;

		// Find section with newest fields
		for(var section_index in group.sections) {

			var section = group.sections[section_index];
			if(section_id === false) { section_id = section.id; }

			if(typeof(section.fields) === 'undefined') { return false; }

			// If there are no fields in section, use it.
			if(section.fields.length == 0) { section_id = section.id; break; }

			for(var field_index in section.fields)  {

				var field = section.fields[field_index];

				if(
					(field_date_updated_max === false) ||
					(field_date_updated_max < field.date_updated)
				) {

					field_date_updated_max = field.date_updated;

					section_id = section.id;
				}
			}
		}

		return section_id;
	}

	// Check for fields that can only be added to the form once
	$.WS_Form.prototype.field_check_multiple = function() {

		var field_type_disabled = [];

		// Reset
		for(var field_type_id in $.WS_Form.field_type_cache) {

			field_type_disabled[field_type_id] = false;
		}

		// Run through each field on the page
		for(var field_id in this.field_data_cache) {

			// Get field
			if(typeof(this.field_data_cache[field_id]) === 'undefined') { continue; }
			var field = this.field_data_cache[field_id];
			if(typeof(field['type']) === 'undefined') { continue; }
			var field_type_id = field['type'];

			// Get field type data
			var field_type = $.WS_Form.field_type_cache[field_type_id];

			// Check to see if multiple attribute is set
			if(typeof(field_type['multiple']) === 'undefined') { continue; }

			var multiple = field_type['multiple'];

			if(!multiple) {

				// Mark as disabled if it only be added once
				field_type_disabled[field_type_id] = true;
			}
		}

		// Set
		for(var field_type_id in $.WS_Form.field_type_cache) {

			var toolbar_field_obj = $('#wsf-form-field-selector [data-type="' + field_type_id + '"]');
			
			if(field_type_disabled[field_type_id]) {

				toolbar_field_obj.addClass('wsf-field-disabled');

			} else {

				toolbar_field_obj.removeClass('wsf-field-disabled');
			}
		}
	}

	// API - Field - PUT - SORT INDEX
	$.WS_Form.prototype.field_put_sort_index = function(obj) {

		// Get next sibling ID (0 = Last or only element in section)
		var next_sibling_id = (typeof obj.next().attr('data-id') !== 'undefined') ? obj.next().attr('data-id') : 0;

		// Get section_id section has been dragged to
		var section_id = obj.closest('.wsf-section').attr('data-id');

		// Check if it moved
		if((this.next_sibling_id_old == next_sibling_id) && (this.section_id_old == section_id)) { return false; }

		// Loader on
		this.loader_on();

		// Get field ID
		var field_id = obj.attr('data-id');

		// Build request parameters
		var params = {

			'next_sibling_id':	next_sibling_id,
			'section_id':		section_id
		}

		// Call AJAX request
		var call_obj = obj;
		$.WS_Form.this.api_call('field/' + field_id + '/sort_index', 'PUT', params, function(response) {

			// Loader off
			$.WS_Form.this.loader_off();
		});
	}

	// API - PUT - CLONE
	$.WS_Form.prototype.field_put_clone = function(obj) {

		// Loader on
		this.loader_on();

		// Read data attributes
		var field_id = obj.attr('data-id');

		// Get next sibling ID (0 = Last or only element in section)
		var next_sibling_id = (typeof obj.next().attr('data-id') !== 'undefined') ? obj.next().attr('data-id') : 0;

		// Build request parameters
		var params = {

			'next_sibling_id':	next_sibling_id
		}

		// Call AJAX request
		var call_obj = obj;
		this.api_call('field/' + field_id + '/clone', 'PUT', params, function(response) {

			// Get field ID
			var field_id = response.data.id;

			// Get field HTML (Stores it to cache)
			var field_html = $.WS_Form.this.get_field_html(response.data);

			// Insert new field after obj
			$(field_html).insertAfter(call_obj);

			// Get new field object
			var new_obj = $('.wsf-field-wrapper[data-id="' + field_id + '"]');

			// Hide
			new_obj.hide();

			// Build field
			$.WS_Form.this.field_render(new_obj);

			// Show
			new_obj.show();

			// Trigger label edit
			$('input', new_obj).first().trigger('dblclick');

			// Loader off
			$.WS_Form.this.loader_off();
		});
	}

	// Label - Init
	$.WS_Form.prototype.label_init = function(object_wrapper) {

		// Get label object
		var label_obj = object_wrapper.find('input').first();

		// Get ID and object type
		var object_id = object_wrapper.attr('data-id');
		var object = $.WS_Form.this.get_object_type(object_wrapper);

		// On key up, save label to meta data
		label_obj.on('keyup', function(e) {

			var object_wrapper = $(this).closest('[data-id]');
			var object_id = object_wrapper.attr('data-id');
			var object = $.WS_Form.this.get_object_type(object_wrapper);

			var code = e.which;

			switch(code) {

				case 13: // Enter

					e.preventDefault();
					$(this).blur();
					break;

				default:

					// Set data attribute
					var object_label = $(this).val();

					// Update
					$.WS_Form.this.label_update(object, object_id, object_label);
			}
		});

		// Blur - Save label
		label_obj.focusout(function() {

			var object_wrapper = $(this).closest('[data-id]');
			var object_id = object_wrapper.attr('data-id');
			var object = $.WS_Form.this.get_object_type(object_wrapper);

			$.WS_Form.this.label_save($(this), object, object_id);
		});

		// Double click / Focus - Enable editing
		label_obj.on('dblclick focus', function() {

			// Switch off readonly and select it
			$(this).prop('readonly', false).select();

			// Store old label
			$.WS_Form.this.label_old = label_obj.val();
		});
	}

	// Label - Update
	$.WS_Form.prototype.label_update = function(object, object_id, object_label) {

		// Store to appropriate object cache
		switch(object) {

			case 'group':

				$.WS_Form.this.group_data_cache[object_id].label = object_label;
				break;

			case 'section':

				$.WS_Form.this.section_data_cache[object_id].label = object_label;
				break;

			case 'field':

				$.WS_Form.this.field_data_cache[object_id].label = object_label;
				break;
		}

		// Update sidebar label
		var sidebar_label = $('#wsf-sidebar-' + object + '[data-id="' + object_id + '"] [name="label"]');
		if(sidebar_label.length) {

			// Check for blank label
			if($.trim(object_label) == '') {

				object_label = this.get_label_default(object);
			}

			sidebar_label.val(object_label);
		}
	}

	// Label - Save
	$.WS_Form.prototype.label_save = function(label_obj, object, object_id) {

		// Switch on readonly
		document.getSelection().removeAllRanges();
		label_obj.prop('readonly', true);

		var object_label = $.trim(label_obj.val());

		// Check to see if label has changed. If it hasn't don't bother saving it.
		if(object_label == this.label_old) { return true; }

		// Check for blank label
		if(object_label == '') {

			object_label = this.get_label_default(object);
			$('input[data-label="' + object_id + '"]').val(object_label);
		}

		// Update to ensure no AJAX requests have effected last update
		this.label_update(object, object_id, object_label);

		// Loader on
		$.WS_Form.this.loader_on();

		// Build parameters
		var params = {};

		// Form ID
		params['form_id'] = this.form_id;

		// Object data
		switch(object) {

			case 'group':

				params[object] = $.WS_Form.this.group_data_cache[object_id];
				break;

			case 'section':

				params[object] = $.WS_Form.this.section_data_cache[object_id];
				break;

			case 'field':

				params[object] = $.WS_Form.this.field_data_cache[object_id];
				break;
		}


		// Call AJAX request
		this.api_call(object + '/' + object_id, 'PUT', params, function(response) {

			// Loader off
			$.WS_Form.this.loader_off();
		});
	}

	// Get object type
	$.WS_Form.prototype.get_object_type = function(obj) {

		if(obj.hasClass('wsf-form')) { return('form'); }
		if(obj.hasClass('wsf-group-tab')) { return('group'); }
		if(obj.hasClass('wsf-group')) { return('group'); }
		if(obj.hasClass('wsf-section')) { return('section'); }
		if(obj.hasClass('wsf-field-wrapper')) { return('field'); }
		return false;
	}

	// Get default label
	$.WS_Form.prototype.get_label_default = function(object) {

		var label_default = '';

		switch(object) {

			case 'form' :

				label_default = this.language('default_label_form');
				break;

			case 'group' :

				label_default = this.language('default_label_group');
				break;

			case 'section' :

				label_default = this.language('default_label_section');
				break;

			case 'field' :

				label_default = this.language('default_label_field');
				break;
		}

		return label_default;
	}

	// Edit (Used for editing sections and fields)
	$.WS_Form.prototype.object_edit = function(obj, reload) {

		// Reset any existing meta_keys ID's to avoid conflicts between sidebars
		$(".wsf-sidebar:not(#wsf-sidebar-support) [data-meta-key]").removeAttr('id data-meta-key data-meta-key-type');

		// Get object type
		var object = this.get_object_type(obj);

		// Read data attributes
		var object_id = obj.attr('data-id');

		// Get sidebar dom object
		var obj_sidebar_outer = $('#wsf-sidebar-' + object);

		// Check to see if object is already being edited
		if(obj.hasClass('wsf-editing')) {

			// Process as cancel
			this.object_cancel(obj);

			// Reset sidebar
			this.sidebar_reset();

			return false;
		}

		// Remove editing class on any other objects that are currently being edited
		var obj_editing = $('.wsf-editing', $('#wsf-form'));
		if(obj_editing.length && !reload) {

			// Process as cancel
			$.WS_Form.this.object_cancel(obj_editing);
		}

		// Add editing class to object
		obj.addClass('wsf-editing');

		// Object specific functions
		switch(object) {

			case 'group' :

				// Add editing class to tab
				$('.wsf-group-tab[data-id="' + object_id + '"]').addClass('wsf-editing');

				// Show this group (Select tab)
				var tab_index = $('.wsf-group-tab[data-id="' + object_id + '"] a').click();

				break;
		}

		// Get object data
		var object_data = this.get_object_data(object, object_id);

		// Get object meta data
		var object_meta = this.get_object_meta(object, object_id);

		// Create new object data that edits will be saved to
		if(!reload) {

			this.object_data_scratch = $.extend(true, {}, object_data); // Deep clone
		}

		// Destroy tabs (Ensures subsequent calls work)
		if(obj_sidebar_outer.hasClass('ui-tabs')) { obj_sidebar_outer.tabs('destroy'); }

		// Data object and ID
		obj_sidebar_outer.attr('data-object', object).attr('data-id', object_id);

		// Build sidebar
		var sidebar_html_tabs = '';
		var sidebar_html = '';
		var sidebar_html_buttons = '';
		var sidebar_inits = [];
		if(typeof object_meta.fieldsets !== 'undefined') {

			// Clear sidebar caches
			this.sidebar_cache_clear();

			var buttons = [
				{'class': 'wsf-button-primary', 'action': 'wsf-sidebar-save', 'label': this.language('save')},
				{'class': '', 'action': 'wsf-sidebar-cancel', 'label': this.language('cancel')},
			];

			switch(object) {

				case 'group' :
				case 'section' :
				case 'field' :

					buttons.push({'class': '', 'action': 'wsf-sidebar-clone', 'right': true, 'label': this.language('clone')});
					buttons.push({'class': 'wsf-button-danger', 'action': 'wsf-sidebar-delete', 'label': this.language('delete')});
			}

			var sidebar_return = this.sidebar_html(object, object_id, this.object_data_scratch, object_meta, false, true, true, buttons);
			sidebar_html_tabs = sidebar_return.html_tabs;
			sidebar_html = sidebar_return.html;
			sidebar_html_buttons = sidebar_return.html_buttons;
			sidebar_inits = sidebar_return.inits;
		}

		// Initialize title for objects
		this.sidebar_title_init(object, object_id, object_meta, obj_sidebar_outer);

		// Tabs
		obj_sidebar_outer.append(sidebar_html_tabs);

		// Inner
		obj_sidebar_outer.append('<div class="wsf-sidebar-inner">' + sidebar_html + '</div>');

		var obj_sidebar_inner = $('.wsf-sidebar-inner', obj_sidebar_outer);

		// Buttons
		obj_sidebar_outer.append(sidebar_html_buttons);

		// Initialize sidebar
		this.sidebar_inits(sidebar_inits, obj_sidebar_outer, obj_sidebar_inner, this.object_data_scratch);

		// Initialize label for objects
		this.sidebar_label_init(object_meta, obj, object, obj_sidebar_outer, obj_sidebar_inner);

		// Initialize buttons for objects
		this.sidebar_buttons_init(obj, obj_sidebar_outer);

		// Initialize change events for objects
		this.sidebar_change_event_init(obj, object, obj_sidebar_inner);

		// Open sidebar
		this.sidebar_open(object);
	}

	// Edit - Button - Save
	$.WS_Form.prototype.object_button_save = function(obj) {

		// Save object
		this.object_save(obj);

		// Reset sidebar
//		this.sidebar_reset();
	}

	// Edit - Button - Cancel
	$.WS_Form.prototype.object_button_cancel = function(obj) {

		// Cancel object
		this.object_cancel(obj);

		// Reset sidebar
		this.sidebar_reset();
	}

	// Edit - Button - Clone
	$.WS_Form.prototype.object_button_clone = function(obj) {

		// Cancel object
//		this.object_cancel(obj);

		// Reset sidebar
//		this.sidebar_reset();

		// Get object type
		var object = this.get_object_type(obj);

		switch(object) {

			case 'group' :

				wsf_group_clone(this, obj);
				break;

			case 'section' :

				wsf_section_clone(this, obj);
				break;

			case 'field' :

				wsf_field_clone(this, obj);
				break;
		}
	}

	// Edit - Button - Delete
	$.WS_Form.prototype.object_button_delete = function(obj) {

		// Cancel object
		this.object_cancel(obj);

		// Reset sidebar
		this.sidebar_reset();

		// Get object type
		var object = this.get_object_type(obj);

		switch(object) {

			case 'group' :

				wsf_group_delete(this, obj);
				break;

			case 'section' :

				wsf_section_delete(this, obj);
				break;

			case 'field' :

				wsf_field_delete(this, obj);
				break;
		}
	}

	// Save notification
	$.WS_Form.prototype.save_notification = function(obj) {

		var ws_this = this;

		$('#wsf-sidebars .wsf-sidebar').addClass('wsf-saved');
	
		var obj_group = false;
		if(obj) {

			obj.addClass('wsf-saved');

			// Get object type
			var object = this.get_object_type(obj);

			// Read data attributes
			var object_id = obj.attr('data-id');

			// Object specific functions
			switch(object) {

				case 'group' :

					// Add editing class to tab
					obj_group = $('.wsf-group-tab[data-id="' + object_id + '"]');

					break;
			}
		}
		if(obj_group) { obj_group.addClass('wsf-saved'); }

		setTimeout(function() {

			$('#wsf-sidebars .wsf-sidebar').removeClass('wsf-saved');
			if(obj) { obj.removeClass('wsf-saved'); }
			if(obj_group) { obj_group.removeClass('wsf-saved'); }

		}, 2000);
	}	

	// Object - Save
	$.WS_Form.prototype.object_save = function(obj, save) {

		if(typeof save === 'undefined') { var save = true; }

		// Get object type
		var object = this.get_object_type(obj);

		// Read object ID
		var object_id = obj.attr('data-id');

		// Get sidebar dom object
		var obj_sidebar_outer = $('#wsf-sidebar-' + object);
		var obj_sidebar_inner = $('.wsf-sidebar-inner', obj_sidebar_outer);

		// Loader on
		if(save) {

			this.loader_on();
		}

		// Run through each of the field meta and set it
		for(var key in this.object_meta_cache) {

			// Get meta_key
			var meta_key = this.object_meta_cache[key]['meta_key'];

			// Update object data
			this.object_data_update_by_meta_key(object, this.object_data_scratch, meta_key);
		}

		// Object label
		switch(object) {

			case 'field' :

				var field_data = this.field_data_cache[object_id];
				var field_type = $.WS_Form.field_type_cache[field_data.type];
				var label_default = field_type.label_default;
				break;

			default :

			var label_default = this.get_label_default(object);
		}
		var object_label = $('[name="label"]', obj_sidebar_inner).val();
		if(typeof object_label !== 'undefined') {

			this.object_data_scratch['label'] = (object_label == '') ? label_default : object_label;
		}

		if(save) {

			// Move new object data to appropriate object
			switch(object) {

				case 'form' :

					this.form.label = this.object_data_scratch.label;
					this.form.meta = this.object_data_scratch.meta;
					break;

				case 'group' :

					// Remove editing class on tab
//					$('.wsf-group-tab[data-id="' + object_id + '"]').removeClass('wsf-editing');

					this.group_data_cache[object_id].label = this.object_data_scratch.label;
					this.group_data_cache[object_id].meta = this.object_data_scratch.meta;

					break;

				case 'section' :

					this.section_data_cache[object_id].label = this.object_data_scratch.label;
					this.section_data_cache[object_id].meta = this.object_data_scratch.meta;
					break;

				case 'field' :

					this.field_data_cache[object_id].label = this.object_data_scratch.label;
					this.field_data_cache[object_id].meta = this.object_data_scratch.meta;

					break;
			}

			// Remove editing class
//			obj.removeClass('wsf-editing');

			// Build parameters
			var params = {};

			// Form ID
			params['form_id'] = this.form_id;

			// Object data
			params[object] = this.object_data_scratch;

			// Call AJAX request
			this.api_call(object + '/' + object_id, 'PUT', params, function(response) {

				switch(object) {

					case 'field' :

						// If there is a meta preview, re-render the field. We run this after the API call finishes in case there is any server side formatting of content.
						if((typeof(field_type.mask_preview) !== 'undefined') && field_type.mask_preview) {

							$.WS_Form.this.field_render(obj, $.WS_Form.this.field_data_cache[object_id]);
						}

						break;

					case 'section' :

						// Initialize fields toggle for objects
						$.WS_Form.this.sidebar_fields_toggle_init(obj, object, obj_sidebar_inner);

						break;
				}

				// Save notification
				$.WS_Form.this.save_notification(obj);

				// Loader off
				$.WS_Form.this.loader_off();
			});
		}
	}

	// Object - Cancel
	$.WS_Form.prototype.object_cancel = function(obj) {

		// Get object type
		var object = this.get_object_type(obj);

		// Get ID of field
		var object_id = obj.attr('data-id');

		// Render
		switch(object) {

			case 'form' :

				// Reset form title
				$('[data-action="wsf-form-label"]').val($.WS_Form.this.html_encode($.WS_Form.this.form['label']));

				// Remove editing class on form edit button
				$('[data-action="wsf-form-settings"]').removeClass('wsf-editing');

				break;

			case 'group' :

				// Render group
				this.group_render(obj);

				// Change tab label
				var object_data = this.get_object_data(object, object_id);
				$('.wsf-group-tab[data-id="' + object_id + '"] a input').val($.WS_Form.this.html_encode(object_data['label']));

				// Remove editing class on tab
				$('.wsf-group-tab[data-id="' + object_id + '"]').removeClass('wsf-editing');

				break;

			case 'section' :

				// Render section
				var object_data = this.get_object_data(object, object_id);
				$('.wsf-section[data-id="' + object_id + '"] .wsf-section-label input').val($.WS_Form.this.html_encode(object_data['label']));
				$.WS_Form.this.section_render(obj);
				break;

			case 'field' :

				// Render field
				this.field_render(obj);
				break;
		}

		// If we were using scratch, lets reset the classes on the field
		if(this.object_data_scratch !== false) {

			// Get framework
			var object_data = false;

			// Get object data
			switch(object) {

				case 'section' :

					object_data = this.section_data_cache[object_id];
					break;

				case 'field' :

					object_data = this.field_data_cache[object_id];
					break;
			}

			// Add classes
			if(object_data !== false) {

				this.column_classes_render(obj, this.object_data_scratch, false);
				this.column_classes_render(obj, object_data);
			}
		}

		// Remove editing class
		obj.removeClass('wsf-editing');

		// Clear object_data_scratch
		this.object_data_scratch = false;

		// Clear any breakpoint objects
		$('.wsf-breakpoint-sizes.wsf-breakpoint-sizes-initialized').remove();

		// Clear keyup functions
		$.WS_Form.this.keydown = [];
	}

	// Object - Delete
	$.WS_Form.prototype.object_delete = function(obj) {

		// Loader on
		this.loader_on();

		// Get object type
		var object = this.get_object_type(obj);

		// Get object ID
		var object_id = obj.attr('data-id');

		// Remove from cache
		switch(object) {

			case 'group' :

			delete this.group_data_cache[object_id];
			break;

			case 'section' :

			delete this.section_data_cache[object_id];
			break;

			case 'field' :

			delete this.field_data_cache[object_id];
			break;
		}

		// For groups
		if(object == 'group') {

			// Get tab and remember its index
			var tab_obj = $('.wsf-group-tab[data-id="' + object_id + '"]');
			var tab_obj_index = tab_obj.index();

			// Select next closest tab
			tab_obj_index--;
			if(tab_obj_index < 0) { tab_obj_index = 0; }

			// Pre-save current tab index to form meta (it will have changed because of the delete)
			this.set_object_meta_value(this.form, 'tab_index', tab_obj_index);
		}

		// Call AJAX request
		var call_obj = obj;
		this.api_call(object + '/' + object_id, 'DELETE', false, function(response) {

			switch(object) {

				case 'group' :

					// Remove object
					call_obj.remove();

					// Remove tab
					tab_obj.remove();

					// Select new tab
					$('.wsf-group-tabs li.wsf-group-tab:eq(' + tab_obj_index + ') a').click();

					break;

				case 'section' :

					// Remove object
					call_obj.remove();

					// Update blank fields
					$.WS_Form.this.object_blank_update();

					break;

				case 'field' :

					// Remove object
					call_obj.remove();

					// Update blank fields
					$.WS_Form.this.object_blank_update();

					break;
			}

			// Check multiple field
			$.WS_Form.this.field_check_multiple();

			// Check fields that have required settings
			$.WS_Form.this.fields_setting_error();

			// Loader off
			$.WS_Form.this.loader_off();
		});
	}

	// Update object from field sidebar
	$.WS_Form.prototype.object_data_update_by_meta_key = function(object, object_data, meta_key) {

		// Read meta key config
		var meta_key_config = $.WS_Form.meta_keys[meta_key];

		// Get type to determine how to render it
		var meta_key_type = meta_key_config['type'];

		// Check for key change
		if(typeof($.WS_Form.meta_keys[meta_key]['key']) !== 'undefined') { meta_key = $.WS_Form.meta_keys[meta_key]['key']; }

		// Read meta_value from form elements
		var field_obj = $('#wsf-sidebar-' + object + ' [data-meta-key="' + meta_key + '"]');

		// Get meta_value
		var meta_value = this.get_meta_value_by_obj(field_obj, meta_key_type);

		// Set object meta
		if(meta_value !== false) {

			this.set_object_meta_value(object_data, meta_key, meta_value);
		}
	}

	// Get meta value by obj
	$.WS_Form.prototype.get_meta_value_by_obj = function(obj, meta_key_type) {

		var meta_value = false;

		switch(meta_key_type) {

			// Skip data grids
			case 'data_grid' :

				break;

			// Convert checkbox meta value to boolean
			case 'checkbox' :

				meta_value = obj.is(':checked') ? obj.val() : '';
				break;

			// Repeater
			case 'repeater' :

				// Get data
				var repeater = this.sidebar_repeater_get(obj);
				var meta_key = obj.attr('data-meta-key');

				meta_value = [];

				// Get column data
				for(var meta_keys_index in repeater.meta_keys) {

					var meta_keys_single = repeater.meta_keys[meta_keys_index];

					// Ensure meta key is configured
					if(typeof($.WS_Form.meta_keys[meta_keys_single]) === 'undefined') { continue; }

					// Check for key change
					if(typeof($.WS_Form.meta_keys[meta_keys_single]['key']) !== 'undefined') { meta_keys_single = $.WS_Form.meta_keys[meta_keys_single]['key']; }

					var repeater_row_index = 0;

					var cell_obj = $('[name="' + meta_key + '_' + meta_keys_single + '[]"]').each(function() {

						if(typeof(meta_value[repeater_row_index]) === 'undefined') { meta_value[repeater_row_index] = {}; }
						var meta_value_cell = $.WS_Form.this.get_meta_value_by_obj($(this), $(this).attr('data-meta-key-type'));
						if(meta_value_cell !== false) { meta_value[repeater_row_index][meta_keys_single] = meta_value_cell; }
						repeater_row_index++;
					});
				}

				break;

			default :

				meta_value = obj.val();
		}

		// Check for null values (e.g. from unpopulated selects)
		if(meta_value == null) { meta_value = ''; }

		return meta_value;
	}

	// Sidebar - Get HTML
	$.WS_Form.prototype.sidebar_html = function(object, object_id, object_data, object_meta, repeater_meta_key, render_wrappers, render_label, buttons, depth, inits, datetime_type) {

		if(typeof object_meta.fieldsets === 'undefined') { return ''; }
		if(typeof repeater_meta_key === 'undefined') { var repeater_meta_key = false; }
		if(typeof render_wrappers === 'undefined') { var render_wrappers = true; }
		if(typeof render_label === 'undefined') { var render_label = true; }
		if(typeof buttons === 'undefined') { var buttons = true; }
		if(typeof label === 'undefined') { var label = true; }
		if(typeof depth === 'undefined') { var depth = 1; }
		if(typeof inits === 'undefined') { var inits = []; }
		if(typeof datetime_type === 'undefined') { var datetime_type = 'datetime-local'; }

		var repeater = (repeater_meta_key !== false);

		if(repeater) {

			render_wrappers = false;
			render_label = false;
			buttons = false;
			label = false;
		}

		// Get fieldsets
		var fieldsets = object_meta.fieldsets;

		// Tabs HTML
		var tab_count = 0;
		var sidebar_html_tabs = '';
		if((depth == 1) && !repeater) {

			sidebar_html_tabs = '<ul class="wsf-sidebar-tabs">';

			// Build tab UL
			for(var key in fieldsets) {

				var fieldset = fieldsets[key];

				sidebar_html_tabs += '<li><a href="#wsf-' + object + '-' + key + '" data-wsf-tab-key="' + key + '">' + fieldset['label'] + '</a></li>';

				tab_count++;
			}

			sidebar_html_tabs += "</ul>\n\n";

			if(tab_count > 1) {

				inits.push('tabs');

			} else {

				sidebar_html_tabs = '';
			}
		}

		// Sidebar HTML
		var sidebar_html = '';

		// Build tab content
		var fieldset_index = 0;

		for(var key in fieldsets) {

			var fieldset = fieldsets[key];

			if((depth == 1) && !repeater) {

				sidebar_html += this.comment_html(this.language('group') + ' - ' + fieldset['label']);
				sidebar_html += '<div id="wsf-' + object + '-' + key + '"' + ((tab_count > 1) ? ' class="wsf-sidebar-tabs-panel"' : '') + '>';
			}

			/* Sidebar classes */
			var sidebar_classes = ['wsf-fieldset'];
			if(typeof fieldset.class !== 'undefined') {

				sidebar_classes = sidebar_classes.concat(fieldset.class);
			}

			sidebar_html += render_wrappers ? '<fieldset class="' + sidebar_classes.join(' ') + '">' : '';

			// Render child fieldset
			if(depth > 1) {

				sidebar_html += '<legend>' + fieldset.label + '</legend>';
			}

			// Render field type and label
			if((fieldset_index == 0) && (depth == 1) && render_label) {

				var object_label = this.html_encode(object_data.label);
				sidebar_html += '<div class="wsf-field-wrapper"><label class="wsf-label" for="wsf-label">Label</label><input name="label" class="wsf-field" type="text" value="' + object_label + '" maxlength="190" /></div>';
				inits.push('label');
			}

			// Render fieldset variables
			if(typeof(fieldset.meta_keys) !== 'undefined') {

				for(var key in fieldset.meta_keys) {

					var sidebar_html_options = '';

					var meta_key = fieldset.meta_keys[key];

					// Check to see if meta_key is defined
					if(typeof $.WS_Form.meta_keys[meta_key] === 'undefined') {

						this.error('error_meta_key', meta_key);
						continue;
					}

					// Read meta key config
					var meta_key_config = $.WS_Form.meta_keys[meta_key];

					// meta_key override
					if(typeof meta_key_config['key'] !== 'undefined') { meta_key = meta_key_config['key']; }

					// Condition
					if(typeof meta_key_config['condition'] !== 'undefined') {

						for(var condition_index in meta_key_config['condition']) {

							var condition = meta_key_config['condition'][condition_index];

							// Push condition to sidebar conditions
							this.sidebar_conditions.push({'logic': condition.logic, 'meta_key': condition.meta_key, 'meta_value': condition.meta_value, 'show': meta_key, 'logic_previous': typeof(condition.logic_previous) ? condition.logic_previous : '&&'});

							// Initialize
							inits.push('conditions');
						}
					}

					// Get required
					var meta_key_required = (typeof(meta_key_config['required']) !== 'undefined') ? meta_key_config['required'] : false;

					// Get label
					var meta_key_label = meta_key_config['label'];

					// Get type to determine how to render it
					var meta_key_type = meta_key_config['type'];

					// meta_value override
					if(typeof(meta_key_config['default_static']) !== 'undefined') {

						var meta_value = this.parse_variables_process(meta_key_config['default_static']);

					} else {

						// Get meta value
						var meta_value = this.get_object_meta_value(object_data, meta_key, '', true);

						// If meta value not found
						if(meta_value === '') {

							// Check for default value
							if(typeof(meta_key_config['fallback']) !== 'undefined') {

								meta_value = meta_key_config['fallback'];
							}
						}
					}

					// Datetime types
					if(meta_key == 'input_type_datetime') { datetime_type = meta_value; }

					// Build help HTML
					if((typeof meta_key_config['help'] !== 'undefined') && ($.WS_Form.settings_plugin.helper_field_help || (typeof meta_key_config['help_force'] !== 'undefined' ? meta_key_config['help_force'] : false)) && !repeater) {

						var sidebar_html_help = '<div class="wsf-helper">' + meta_key_config['help'] + '</div>';

					} else {

						var sidebar_html_help = '';
					}

					// Build label HTML
					var sidebar_html_label = (repeater || (typeof(meta_key_label) === 'undefined')) ? '' : '<label class="wsf-label' + (meta_key_required ? ' wsf-required' : '') + '" for="wsf_' + meta_key + '">' + meta_key_label + '</label>';

					// Field attributes - Standard
					var field_attributes = ' ' + (repeater ? ' name="' + repeater_meta_key + '_' + meta_key + '[]"' : 'id="wsf_' + meta_key + '"') + ' class="wsf-field' + (repeater ? ' wsf-field-small' : '') + '" data-meta-key="' + meta_key + '" data-meta-key-type="' + meta_key_type + '"';

					// Field options
					var meta_key_options = [];

					// Field options - Required setting
					if((typeof(meta_key_config['required_setting']) !== 'undefined') && meta_key_config['required_setting']) {

						meta_key_options.push('<li class="wsf-required-setting">' + this.svg('warning') + '</li>');

						field_attributes += ' data-required-setting';

						inits.push('required_setting');
					}

					// Field options - Compatibility
					if((typeof(meta_key_config['compatibility_url']) !== 'undefined') && $.WS_Form.settings_plugin.helper_compatibility && !repeater) {

						meta_key_options.push('<li><a class="wsf-compatibility" href="' + meta_key_config['compatibility_url'] + '" target="_blank" title="' + this.language('attribute_compatibility') + '" tabindex="-1">' + this.svg('markup-circle') + '</a></li>');
					}

					// Field options - Select list (Falls back to variables)
					if(typeof(meta_key_config['select_list']) !== 'undefined') {

						meta_key_options.push('<li><span data-action="wsf-select-list" data-option-meta-key="' + meta_key + '" title="' + this.language('select_list') + '">' + this.svg('menu') + '</span></li>');

						inits.push('select_list');
					}

					// Field options - Auto map
					if((typeof meta_key_config['auto_map'] !== 'undefined') && !repeater) {

						meta_key_options.push('<li><span data-action="wsf-auto-map" data-option-meta-key="' + meta_key + '" data-object="' + object + '" data-object-id="' + object_id + '" title="' + this.language('auto_map') + '">' + this.svg('exchange') + '</span></li>');

						inits.push('auto_map');
					}

					// Field options - API reload
					if((typeof meta_key_config['reload'] !== 'undefined')) {

						var reload_config = meta_key_config['reload'];

						// Reload attributes
						var reload_attributes = '';
						if(typeof reload_config['action_id'] !== 'undefined') { reload_attributes += ' data-action-id="' + reload_config['action_id'] + '"'; }
						if(typeof reload_config['action_id_meta_key'] !== 'undefined') { reload_attributes += ' data-action-id-meta-key="' + reload_config['action_id_meta_key'] + '"'; }
						if(typeof reload_config['list_id_meta_key'] !== 'undefined') { reload_attributes += ' data-list-id-meta-key="' + reload_config['list_id_meta_key'] + '"'; }
						if(typeof reload_config['list_sub_id_meta_key'] !== 'undefined') { reload_attributes += ' data-list-sub-id-meta-key="' + reload_config['list_sub_id_meta_key'] + '"'; }
						reload_attributes += ' data-method="' + reload_config['method'] + '"';
						if(repeater) { reload_attributes += ' data-repeater-meta-key="' + repeater_meta_key + '"'; }

						meta_key_options.push('<li><span data-action="wsf-api-reload"' + reload_attributes + ' data-meta-key-for="' + meta_key + '" title="' + this.language('action_api_reload') + '">' + this.svg('reload') + '</span></li>');

						inits.push('options-action-reload');
					}

					// Field options - Build
					var meta_key_options_html = '';
					if(meta_key_options.length > 0) {

						meta_key_options_html = '<ul class="wsf-meta-key-options">' + meta_key_options.join('') + '</ul>';
					}

					// Default option (Use for inheriting parent values)
					if(typeof(meta_key_config['options_default']) !== 'undefined') {

						// Get default options
						var options_default_label = false;
						var options_default_meta_key = meta_key_config['options_default'];
						var options_default_meta_value = this.get_object_meta_value(this.form, options_default_meta_key, undefined);
						if(options_default_meta_value !== undefined) {

							var options_default_meta_key = $.WS_Form.meta_keys[options_default_meta_key];

							switch(options_default_meta_key['type']) {

								case 'select' :

									if(typeof(options_default_meta_key['options']) !== 'undefined') {

										var options_default_options = options_default_meta_key['options'];

										for(var options_default_options_index in options_default_options) {

											var options_default_value = options_default_options[options_default_options_index]['value'];
											if(options_default_value != options_default_meta_value) { continue; }

											options_default_label = options_default_options[options_default_options_index]['text'];
										}
									}
									break;

								default :

									options_default_label = options_default_meta_value;
							}
						}

						// Determine if it should be selected
						var option_selected = (meta_value == 'default') ? ' selected' : '';

						// Build option
						sidebar_html_options += '<option value="default"' + option_selected + '>' + this.language('default') + ((options_default_label !== false) ? ' (' + options_default_label + ')' : '') + "</option>\n";
					}

					// Build options HTML
					if(typeof(meta_key_config['options']) !== 'undefined') {

						// Get options
						var meta_key_options = meta_key_config['options'];

						// Option filtering by framework (e.g. to only show label positions available for the current framework)
						var meta_key_options_filter = false;
						if(typeof meta_key_config['options_framework_filter'] !== 'undefined') {

							// Get options filter from framework
							if(typeof this.framework[meta_key_config['options_framework_filter']] !== 'undefined') {

								meta_key_options_filter = this.framework[meta_key_config['options_framework_filter']];
							}
						}

						// Pre-defined options
						switch(meta_key_options) {

							case 'sections' :

								// Check cache
								if(typeof(this.meta_key_options_cache['sections']) !== 'undefined') {

									// Cached version found, so set meta_key_options to copy of that cached data
									var meta_key_options = $.extend(true, [], this.meta_key_options_cache['sections']);
									break;
								}

								// Clear options
								var meta_key_options = [];

								// Filter
								var section_filter_attribute = (typeof(meta_key_config['section_filter_attribute']) !== 'undefined') ? meta_key_config['section_filter_attribute'] : false;

								// Build options
								for(var section_index in this.section_data_cache) {

									var section = this.section_data_cache[section_index];
									
									switch(section_filter_attribute) {

										case 'section_repeatable' :

											var section_repeatable = this.get_object_meta_value(section, 'section_repeatable', false);
											if(!section_repeatable) { continue; }
											break;
									}

									var text = section.label;

									meta_key_options.push({'value': section.id, 'text': text + ' (' + this.language('id') + ': ' + section.id + ')', 'type': section.type});
								}

								// Sort options alphabetically
								meta_key_options.sort(function(a, b) {

									if(a.text < b.text) { return -1; }
									if(a.text > b.text) { return 1; }
									return 0;
								});

								// Store to cache
								this.meta_key_options_cache['sections'] = $.extend(true, [], meta_key_options);

								break;

							case 'fields' :

								// Check cache
								if(typeof(this.meta_key_options_cache['fields']) !== 'undefined') {

									// Cached version found, so set meta_key_options to copy of that cached data
									var meta_key_options = $.extend(true, [], this.meta_key_options_cache['fields']);
									break;
								}

								// Clear options
								var meta_key_options = [];

								// Build options
								for(var field_index in this.field_data_cache) {

									var field = this.field_data_cache[field_index];
									var text = field.label;
									var field_required = this.get_object_meta_value(field, 'required', false);
									if(field_required) { text += ' *'; }

									meta_key_options.push({'value': field.id, 'text': text + ' (' + this.language('id') + ': ' + field.id + ')', 'type': field.type});
								}

								// Sort options alphabetically
								meta_key_options.sort(function(a, b) {

									if(a.text < b.text) { return -1; }
									if(a.text > b.text) { return 1; }
									return 0;
								});

								// Store to cache
								this.meta_key_options_cache['fields'] = $.extend(true, [], meta_key_options);

								break;

							case 'action_api_populate' :

								var meta_key_options = [];

								break;

							default :

								var meta_key_options = $.extend(true, [], meta_key_options);
						}

						// Insert blank option
						if(typeof(meta_key_config['options_blank']) !== 'undefined') {

							meta_key_options.unshift({'value': '', 'text': meta_key_config['options_blank']});
						}

						// Filters
						var fields_filter_type = (typeof(meta_key_config['fields_filter_type']) !== 'undefined') ? meta_key_config['fields_filter_type'] : false;
						var fields_filter_attribute = (typeof(meta_key_config['fields_filter_attribute']) !== 'undefined') ? meta_key_config['fields_filter_attribute'] : false;

						// Build options
						for(var meta_key_option_index in meta_key_options) {

							// Option single
							var meta_key_option = meta_key_options[meta_key_option_index];

							// Filter - By Field Type
							if(fields_filter_type !== false) {

								// Filter by field type
								if(typeof(meta_key_option.type) !== 'undefined') {

									// Skip option if it does not match filter requirements
									if(fields_filter_type.indexOf(meta_key_option.type) === -1) { continue; }

									// Skip same field
									if(meta_key_option.value == object_id) { continue; }
								}
							}

							// Filter - By Field Type Attribute
							if(fields_filter_attribute !== false) {

								// Read field
								if(
									(typeof(meta_key_option.type) !== 'undefined') &&
									(typeof($.WS_Form.field_type_cache[meta_key_option.type]) !== 'undefined')
								) {

									// Get field config
									var field_config = $.WS_Form.field_type_cache[meta_key_option.type];

									// Skip option if it does not match filter requirements
									var attribute_found = fields_filter_attribute.find(function(attribute) {

										return (

											(typeof(field_config[attribute]) !== 'undefined') &&
											field_config[attribute]
										);
									});
									if(!attribute_found) { continue; }
								}
							}

							// Option value
							var option_value = meta_key_option.value;

							// Filter array
							if((meta_key_options_filter !== false) && (meta_key_options_filter.indexOf(option_value) == -1)) { continue; }

							// Option text
							var option_text = meta_key_option.text;

							// Determine if it should be selected
							var option_selected = (meta_value == option_value) ? ' selected' : '';

							// Determine if it should be disabled
							var meta_key_option_disabled = (typeof(meta_key_option.disabled) !== 'undefined') ? meta_key_option.disabled : false;
							var option_disabled = meta_key_option_disabled ? ' disabled' : '';

							// Build option
							sidebar_html_options += '<option value="' + this.html_encode(option_value) + '"' + option_selected + option_disabled + '>' + this.html_encode(option_text) + "</option>\n";
						}
					}

					// Build select AJAX
					if(meta_key_type == 'select_ajax') {

						var select_ajax_method_search = (typeof(meta_key_config['select_ajax_method_search']) !== 'undefined') ? meta_key_config['select_ajax_method_search'] : false;
						var select_ajax_method_cache = (typeof(meta_key_config['select_ajax_method_cache']) !== 'undefined') ? meta_key_config['select_ajax_method_cache'] : false;
						var select_ajax_placeholder = (typeof(meta_key_config['select_ajax_placeholder']) !== 'undefined') ? meta_key_config['select_ajax_placeholder'] : false;

						if(select_ajax_method_search) {

							// Add field attribute
							field_attributes += ' data-select-ajax-method-search="' + select_ajax_method_search + '"';
							field_attributes += ' data-select-ajax-method-cache="' + select_ajax_method_cache + '"';
							field_attributes += select_ajax_placeholder ? ' data-select-ajax-placeholder="' + select_ajax_placeholder + '"' : '';
							if(meta_value) { field_attributes += ' data-select-ajax-id="'  + this.html_encode(meta_value) + '"'; }

							// Initialize
							inits.push('select-ajax');
						}
					}

					// Build select number options
					if(meta_key_type == 'select_number') {

						// Get minimum and maximum values
						var minimum = (typeof(meta_key_config['minimum']) !== 'undefined') ? meta_key_config['minimum'] : 1;
						var maximum = (typeof(meta_key_config['maximum']) !== 'undefined') ? meta_key_config['maximum'] : 100;
						if(maximum == 'framework_column_count') { maximum = ($.WS_Form.settings_plugin.framework_column_count - 1); }

						for(var option_value = minimum; option_value <= maximum; option_value++) {

							// Determine if it should be selected
							var option_selected = (meta_value == option_value) ? ' selected' : '';

							// Build option
							sidebar_html_options += '<option value="' + option_value + '"' + option_selected + '>' + option_value + "</option>\n";
						}

						// Change to normal select
						meta_key_type = 'select';
					}

					// Field attributes - Placeholder
					if(typeof(meta_key_config.mask_placeholder) !== 'undefined') {

						// Add placeholder to field attributes
						field_attributes += ' data-placeholder="' + this.html_encode(meta_key_config.mask_placeholder) + '"';

						// Initialize
						inits.push('placeholders');
					}

					// Field attributes - Placeholder
					if(typeof(meta_key_config.placeholder) !== 'undefined') {

						// Add placeholder to field attributes
						field_attributes += ' placeholder="' + meta_key_config.placeholder + '"';
					}

					// Field attributes - Data change
					if((typeof(meta_key_config.data_change) !== 'undefined')) {

						// Add reload on change to field attributes
						field_attributes += ' data-change-event="' + meta_key_config.data_change.event + '"';
						field_attributes += ' data-change-action="' + meta_key_config.data_change.action + '"';
					}

					// Field attributes - Fields toggle
					if((typeof(meta_key_config.fields_toggle) !== 'undefined')) {

						// Add fields toggle to field attributes
						field_attributes += ' data-fields-toggle';
					}

					// Field attributes - Min, Max, Step
					if(typeof(meta_key_config.min) !== 'undefined') { field_attributes += ' min="' + parseInt(meta_key_config.min) + '"'; }
					if(typeof(meta_key_config.max) !== 'undefined') { field_attributes += ' max="' + parseInt(meta_key_config.max) + '"'; }
					if(typeof(meta_key_config.step) !== 'undefined') { field_attributes += ' step="' + parseInt(meta_key_config.step) + '"'; }

					// Field attributes - Options - Action
					if(((typeof(meta_key_config.options_action_id) !== 'undefined') || (typeof(meta_key_config.options_action_id_meta_key) !== 'undefined')) && (typeof meta_key_config.options_action_api_populate !== 'undefined')) {

						if(typeof(meta_key_config.options_action_id) !== 'undefined') { field_attributes += ' data-options-action-id="' + meta_key_config.options_action_id + '"'; }
						if(typeof(meta_key_config.options_action_id_meta_key) !== 'undefined') { field_attributes += ' data-options-action-id-meta-key="' + meta_key_config.options_action_id_meta_key + '"'; }
						if(typeof(meta_key_config.options_list_id_meta_key) !== 'undefined') { field_attributes += ' data-options-list-id-meta-key="' + meta_key_config.options_list_id_meta_key + '"'; }
						if(typeof(meta_key_config.options_list_sub_id_meta_key) !== 'undefined') { field_attributes += ' data-options-list-sub-id-meta-key="' + meta_key_config.options_list_sub_id_meta_key + '"'; }
						field_attributes += ' data-options-action-api-populate="' + meta_key_config.options_action_api_populate + '"';
						field_attributes += ' data-object="' + object + '"';
						field_attributes += ' data-object-id="' + object_id + '"';
						if(repeater) { field_attributes += ' data-repeater-meta-key="' + repeater_meta_key + '"'; }

						inits.push('options-action');
					}

					// Field attributes - Required
					if(meta_key_required) { field_attributes += ' required'; }

					// Field attributes - Additional
					if(typeof(meta_key_config.attributes) !== 'undefined') {

						for(var attribute in meta_key_config.attributes) {

							var attribute_value = meta_key_config.attributes[attribute];
							field_attributes += ' ' + attribute + '="' + attribute_value + '"';
						}
					}

					// Add meta key to cache
					if(!repeater) {

						this.object_meta_cache.push({'meta_key': meta_key, 'meta_key_type': meta_key_type, 'meta_value': meta_value});
					}

					// Change type to text if mode is advanced so that parse variables can be used
					if(meta_key == 'default_value') {

						var mode = $.WS_Form.settings_plugin.mode;
						var type_advanced = (typeof(meta_key_config['type_advanced']) !== 'undefined') ? meta_key_config['type_advanced'] : false;

						if(
							(mode == 'advanced') &&
							(type_advanced !== false)

						) { meta_key_type = type_advanced; }
					}

					// Process by meta key type
					switch(meta_key_type) {

						// Checkbox
						case 'checkbox' :

							var meta_key_checked = (meta_value == 'on') ? ' checked' : '';
							var sidebar_html_field = '<input type="checkbox"' + field_attributes + meta_key_checked + ' />' + sidebar_html_label + meta_key_options_html + sidebar_html_help;
							break;

						// Select
						// RegEx Filter
						case 'select' :
						case 'select_ajax' :
						case 'regex_filter' :
						case 'data_grid_field' :

							var sidebar_html_field = sidebar_html_label + meta_key_options_html + '<select' + field_attributes + '>' + sidebar_html_options + '</select>' + sidebar_html_help;
							break;

						// Text Editor
						case 'text_editor' :

							field_attributes += (typeof(meta_key_config.css) !== 'undefined') ? ' data-helper-css="' + meta_key_config.css + '"' : '';
							var sidebar_html_field = sidebar_html_label + meta_key_options_html + '<textarea data-text-editor="true"' + field_attributes + '>' + this.html_encode(meta_value) + '</textarea>' + sidebar_html_help;
							inits.push('text-editor');
							break;

						// HTML Editor
						case 'html_editor' :

							var sidebar_html_field = sidebar_html_label + meta_key_options_html + '<textarea data-html-editor="true"' + field_attributes + '>' + this.html_encode(meta_value) + '</textarea>' + sidebar_html_help;
							inits.push('html-editor');
							break;

						// Textarea
						case 'textarea' :

							var sidebar_html_field = sidebar_html_label + meta_key_options_html + '<textarea' + field_attributes + '>' + this.html_encode(meta_value) + '</textarea>' + sidebar_html_help;
							break;

						// Number
						case 'number' :

							var sidebar_html_field = sidebar_html_label + meta_key_options_html + '<input type="number"' + field_attributes + ' value="' + this.html_encode(meta_value) + '"' + ' />' + sidebar_html_help;
							inits.push('number');
							break;

						// Range
						case 'range' :

							var sidebar_html_field = sidebar_html_label + meta_key_options_html + '<input type="range"' + field_attributes + ' value="' + this.html_encode(meta_value) + '"' + ' /><small id="wsf_' + meta_key + '_range_value"></small>' + sidebar_html_help;
							inits.push('range');
							break;

						// Password
						case 'password' :

							var sidebar_html_field = sidebar_html_label + meta_key_options_html + '<input type="password"' + field_attributes + ' value="' + this.html_encode(meta_value) + '"' + ' autocomplete="new-password" />' + sidebar_html_help;
							break;

						// Email
						case 'email' :

							var sidebar_html_field = sidebar_html_label + meta_key_options_html + '<input type="email"' + field_attributes + ' value="' + this.html_encode(meta_value) + '"' + ' />' + sidebar_html_help;
							break;

						// Tel
						case 'tel' :

							var sidebar_html_field = sidebar_html_label + meta_key_options_html + '<input type="tel"' + field_attributes + ' value="' + this.html_encode(meta_value) + '"' + ' />' + sidebar_html_help;
							break;

						// URL
						case 'url' :

							var sidebar_html_field = sidebar_html_label + meta_key_options_html + '<input type="url"' + field_attributes + ' value="' + this.html_encode(meta_value) + '"' + ' />' + sidebar_html_help;
							break;

						// Date/Time
						case 'datetime' :

							var sidebar_html_field = sidebar_html_label + meta_key_options_html + '<input type="' + datetime_type + '"' + field_attributes + ' value="' + this.html_encode(meta_value) + '"' + ' />' + sidebar_html_help;
							inits.push('datetime');
							break;

						// Color
						case 'color' :

							var sidebar_html_field = sidebar_html_label + meta_key_options_html + '<input type="color"' + field_attributes + ' value="' + this.html_encode(meta_value) + '"' + ' />' + sidebar_html_help;
							break;


						// Data grid
						case 'data_grid' :

							var sidebar_html_field = '<div class="wsf-data-grid" data-object="' + object + '" data-id="' + object_id + '" data-meta-key="' + meta_key + '"></div>';
							inits.push('data-grid');
							break;

						// Field select
						case 'field_select' :

							var sidebar_html_field = '<div class="wsf-field-select"></div>';
							inits.push('field-select');
							break;

						// Form history
						case 'form_history' :

							var sidebar_html_field = '';
							inits.push('form-history');
							break;

						// Knowledge Base
						case 'knowledgebase' :

							var sidebar_html_field = '';
							break;

						// Contact
						case 'contact' :

							var sidebar_html_field = '';
							inits.push('contact');
							break;

						// Repeater
						case 'repeater' :

							var sidebar_html_field = sidebar_html_label + meta_key_options_html + '<div id="wsf_' + meta_key + '" class="wsf-repeater" data-object="' + object + '" data-id="' + object_id + '" data-meta-key="' + meta_key + '" data-meta-key-type="' + meta_key_type + '"></div>' + sidebar_html_help;
							inits.push('repeater');
							break;

						// Button
						case 'button' :

							var sidebar_html_field = '<button class="wsf-button wsf-button-primary wsf-button-full" id="wsf_' + meta_key + '" data-object="' + object + '" data-id="' + object_id + '" data-meta-key="' + meta_key + '" data-meta-key-type="' + meta_key_type + '">' + this.html_encode(meta_key_label) + '</button>';
							break;

						// Image
						case 'image' :

							var sidebar_html_field = sidebar_html_label + meta_key_options_html + '<div class="wsf-field-inline"><input type="text"' + field_attributes + ' value="' + this.html_encode(meta_value) + '"' + ' />';
							sidebar_html_field += '<button class="wsf-button wsf-button-primary" data-for="wsf_' + meta_key + '">' + this.language('sidebar_button_image') + '</button></div>' + sidebar_html_help;
							inits.push('image');
							break;

						// HTML
						case 'html' :

							var html = (typeof(meta_key_config['html']) !== 'undefined') ? meta_key_config['html'] : '';
							var sidebar_html_field = '<div class="wsf-sidebar-html"' + field_attributes + '>' + html + '</div>';
							break;

						// Default (Text)
						default :

							var sidebar_html_field = sidebar_html_label + meta_key_options_html + '<input type="text"' + field_attributes + ' value="' + this.html_encode(meta_value) + '"' + ' />' + sidebar_html_help;
					}

					// Indent HTML
					var indent_html = (typeof(meta_key_config['indent']) !== 'undefined') ? ' wsf-field-indent' : '';

					// Add fieldset field HTML
					if(render_wrappers) {

						sidebar_html += '<div class="wsf-field-wrapper' + indent_html + '">' + sidebar_html_field + "</div>\n";

					} else {

						sidebar_html += sidebar_html_field + "\n";
					}
				}
			}

			// Render child fieldset
			if(typeof fieldset.fieldsets !== 'undefined') {

				var sidebar_return = this.sidebar_html(object, object_id, object_data, fieldset, repeater, render_wrappers, render_label, buttons, (depth + 1), inits, datetime_type);
				sidebar_html += sidebar_return.html;
				inits = inits.concat(sidebar_return.inits);
			}

			sidebar_html += render_wrappers ? '</fieldset>' : '';

			if((depth == 1) && !repeater) {

				sidebar_html += '</div>';

				sidebar_html += this.comment_html(this.language('group') + ' - ' + fieldset['label'], true);
			}

			fieldset_index++;
		}

		var sidebar_html_buttons = (buttons !== false) ? this.sidebar_buttons_html(buttons) : '';

		return {'html_tabs': sidebar_html_tabs, 'html': sidebar_html, 'html_buttons': sidebar_html_buttons, 'inits': inits};
	}

	// Sidebar - Clear caches (Done each time a sidebar is rendered)
	$.WS_Form.prototype.sidebar_cache_clear = function() {

		// Reset object meta cache (Used later to recall the meta keys that need saving)
		this.object_meta_cache = [];

		// Reset sidebar_conditions
		this.sidebar_conditions = [];
		this.sidebar_conditions_events_added = false;

		// Clear options cache
		this.meta_key_options_cache = [];
	}

	// Sidebar - Set up input that need to match datetime type selected
	$.WS_Form.prototype.sidebar_buttons_html = function(buttons) {

		if((typeof buttons === 'undefined') || (buttons === true)) { var buttons = [

				{'class': 'wsf-button-primary', 'action': 'wsf-sidebar-save', 'label': this.language('save')},
				{'class': '', 'action': 'wsf-sidebar-cancel', 'label': this.language('cancel')},
			];
		}

		if(buttons === false) { return ''; }

		var return_html = '<div class="wsf-sidebar-footer">';

		return_html += '<div class="wsf-sidebar-footer-saved">Saved</div>';

		return_html += '<ul class="wsf-list-inline">';

		for(var button_index in buttons) {

			var button = buttons[button_index];

			if(typeof(button.class) === 'undefined') { button.class = ''; }
			if(typeof(button.action) === 'undefined') { continue; }
			if(typeof(button.label) === 'undefined') { continue; }
			if(typeof(button.disabled) === 'undefined') { button.disabled = false; }
			if(typeof(button.right) === 'undefined') { button.right = false; }

			return_html += '<li' + (button.right ? ' class="wsf-button-right"' : '') + '><button class="wsf-button wsf-button-small' + ((button.class != '') ? ' ' + button.class : '') + '" data-action="' + button.action + '"' + ((button.id !== undefined) ? ' data-id="' + button.id + '"' : '') + (button.disabled ? ' disabled' : '') + '>' + this.html_encode(button.label) + '</button></li>';
		}

		return_html += '</ul>';
		return_html += '</div>';

		return return_html;
	}

	// Sidebar - Init
	$.WS_Form.prototype.sidebar_inits = function(inits, obj_sidebar_outer, obj_sidebar_inner, object_data) {

		if(typeof(inits) !== 'object') { var inits = []; }
		if(!inits.length) { return; }
		if(typeof(obj_sidebar_inner) === 'undefined') { var obj_sidebar_inner = obj_sidebar_outer; }
		if(typeof(object_data) === 'undefined') { var object_data = []; }


		var mode = $.WS_Form.settings_plugin.mode;
		var mode_basic = (mode == 'basic');

		// Initialize tabs
		if(inits.indexOf('tabs') != -1) {

			this.sidebar_tabs_init(obj_sidebar_outer, obj_sidebar_inner);
		}

		// Tabs class
		if($('.wsf-sidebar-tabs li', obj_sidebar_outer).length) {

			obj_sidebar_outer.addClass('wsf-sidebar-has-tabs');

		} else {

			obj_sidebar_outer.removeClass('wsf-sidebar-has-tabs');
		}

		// Initialize TinyMCE
		if(inits.indexOf('text-editor') != -1) {

			this.sidebar_tinymce_init(obj_sidebar_inner);
		}

		// Initialize HTML editors
		if(inits.indexOf('html-editor') != -1) {

			this.sidebar_html_editor_init(obj_sidebar_inner);
		}

		// Initialize repeaters
		if(inits.indexOf('repeater') != -1) {

			this.sidebar_repeater_init(obj_sidebar_inner);
		}

		// Initialize data grids
		if(inits.indexOf('data-grid') != -1) {

			this.sidebar_data_grids_init(obj_sidebar_outer);
		}

		// Initialize field selector
		if(inits.indexOf('field-select') != -1) {

			this.sidebar_field_select_init(obj_sidebar_outer);
		}

		// Initialize form history
		if(inits.indexOf('form-history') != -1) {

			this.sidebar_form_history_init();
		}
		// Initialize default value range slider
		if((inits.indexOf('range') != -1)) {

			this.sidebar_range_init(obj_sidebar_inner);
		}

		// Initialize default value number
		if((inits.indexOf('number') != -1) && mode_basic) {

			this.sidebar_number_init(obj_sidebar_inner);
		}

		// Initialize sidebar placeholders
		if(inits.indexOf('placeholders') != -1) {

			this.sidebar_placeholders_init(obj_sidebar_outer);
		}

		// Initialize sidebar select lists
		if(inits.indexOf('select_list') != -1) {

			this.sidebar_select_list(obj_sidebar_outer);
		}


		// Initialize sidebar knowledgebase
		if(inits.indexOf('knowledgebase') != -1) {

			this.sidebar_knowledgebase(obj_sidebar_outer, obj_sidebar_inner);
		}

		// Initialize required setting event handler
		if(inits.indexOf('required_setting') != -1) {

			this.sidebar_required_setting(object_data, obj_sidebar_outer, obj_sidebar_inner);
		}

		// Initialize image selector
		if(inits.indexOf('image') != -1) {

			this.sidebar_image(obj_sidebar_inner);
		}

		// Initialize options populated by action methods
		if(inits.indexOf('options-action') != -1) {

			this.sidebar_option_action_init(obj_sidebar_inner, function() {

				// Only run these inits after API requests have all finished

				// Initialize options populated by action methods
				if(inits.indexOf('options-action-reload') != -1) {

					$.WS_Form.this.sidebar_api_reload_init(obj_sidebar_inner);
				}

				// Initialize sidebar conditions
				if(inits.indexOf('conditions') != -1) {

					$.WS_Form.this.sidebar_conditions_init(obj_sidebar_outer);
				}
			});

		} else {

			// Initialize sidebar conditions
			if(inits.indexOf('conditions') != -1) {

				$.WS_Form.this.sidebar_conditions_init(obj_sidebar_outer);
			}
		}
	}

	// Sidebar - Title - Init
	$.WS_Form.prototype.sidebar_title_init = function(object, object_id, object_meta, obj_outer) {

		switch(object) {

			case 'form' :

				var sidebar_label = this.language('sidebar_title_' + object);
				var sidebar_icon = this.svg(object);
				var sidebar_compatibility_html = '';

				// Knowledge base URL
				var kb_url = this.get_plugin_website_url('/knowledgebase/form-settings/', 'sidebar');
				var sidebar_kb_html = '<a class="wsf-kb-url" href="' + kb_url + '" target="_blank" title="' + this.language('field_kb_url') + '" tabindex="-1">' + this.svg('question-circle') + '</a>';

				// Build ID html
				var sidebar_field_id_html = ($.WS_Form.settings_plugin.helper_field_id) ? '<span>' + this.language('id') + ': ' + this.form_id + '</span>' : '';

				break;

			case 'group' :

				var sidebar_label = this.language('sidebar_title_' + object);
				var sidebar_icon = this.svg(object);
				var sidebar_compatibility_html = '';

				// Knowledge base URL
				var kb_url = this.get_plugin_website_url('/knowledgebase/tabs/', 'sidebar');
				var sidebar_kb_html = '<a class="wsf-kb-url" href="' + kb_url + '" target="_blank" title="' + this.language('field_kb_url') + '" tabindex="-1">' + this.svg('question-circle') + '</a>';

				// Build ID html
				var sidebar_field_id_html = ($.WS_Form.settings_plugin.helper_field_id) ? '<span>' + this.language('id') + ': ' + object_id + '</span>' : '';

				break;

			case 'section' :

				var sidebar_label = this.language('sidebar_title_' + object);
				var sidebar_icon = this.svg(object);
				var sidebar_compatibility_html = '';

				// Knowledge base URL
				var kb_url = this.get_plugin_website_url('/knowledgebase/sections/', 'sidebar');
				var sidebar_kb_html = '<a class="wsf-kb-url" href="' + kb_url + '" target="_blank" title="' + this.language('field_kb_url') + '" tabindex="-1">' + this.svg('question-circle') + '</a>';

				// Build ID html
				var sidebar_field_id_html = ($.WS_Form.settings_plugin.helper_field_id) ? '<span>' + this.language('id') + ': ' + object_id + '</span>' : '';

				break;

			case 'field' :

				var sidebar_label = this.html_encode(object_meta.label);
				var sidebar_icon = (typeof object_meta.icon !== 'undefined' ? (object_meta.icon) : '');

				// Build knowledge base HTML
				if((typeof(object_meta['kb_url']) !== 'undefined')) {

					var kb_url = this.get_plugin_website_url(object_meta['kb_url'], 'sidebar');
					var sidebar_kb_html = '<a class="wsf-kb-url" href="' + kb_url + '" target="_blank" title="' + this.language('field_kb_url') + '" tabindex="-1">' + this.svg('question-circle') + '</a>';
				}

				// Build compatibility icon HTML
				if((typeof(object_meta['compatibility_url']) !== 'undefined') && $.WS_Form.settings_plugin.helper_compatibility) {

					var sidebar_compatibility_html = '<a class="wsf-compatibility" href="' + object_meta['compatibility_url'] + '" target="_blank" title="' + this.language('field_compatibility') + '" tabindex="-1">' + this.svg('markup-circle') + '</a>';
				}

				// Build ID html
				var sidebar_field_id_html = ($.WS_Form.settings_plugin.helper_field_id) ? '<span>' + this.language('id') + ': ' + object_id + '</span>' : '';

				break;

			default :

				var sidebar_label = this.language('sidebar_title_' + object);
				var sidebar_icon = this.svg(object);
				var sidebar_compatibility_html = '';
				var sidebar_field_id_html = '';
		}

		obj_outer.html(this.sidebar_title(sidebar_icon, sidebar_label, sidebar_compatibility_html, sidebar_kb_html, sidebar_field_id_html, true));

		this.sidebar_expand_contract_init();
	}

	// Sidebar - Label - Init
	$.WS_Form.prototype.sidebar_label_init = function(object_meta, obj, object, obj_outer, obj_inner) {

		// Label keyup event
		$('[name="label"]', obj_inner).keyup(function() {

			// Check scratch data exists
			if($.WS_Form.this.object_data_scratch === false) { return false; }

			// Get field_label value
			var object_label = $.trim($(this).val());
			switch(object) {

				case 'field' :

					var label_default = object_meta.label_default;
					break;

				default :

					var label_default = $.WS_Form.this.get_label_default(object);
			}
			$.WS_Form.this.object_data_scratch['label'] = ((object_label == '') ? label_default : object_label);

			switch(object) {

				case 'form' :

					// Change form label
					$('[data-action="wsf-form-label"]').val($.WS_Form.this.html_encode($.WS_Form.this.object_data_scratch['label']));
					break;

				case 'group' :

					// Change tab label
					var group_id = obj.attr('data-id');
					$('.wsf-group-tab[data-id="' + group_id + '"] a input').val($.WS_Form.this.html_encode($.WS_Form.this.object_data_scratch['label']));

					break;

				case 'section' :

					// Render section (Simulate using new object data)
					var section_id = obj.attr('data-id');
					$('.wsf-section[data-id="' + section_id + '"] .wsf-section-label input').val($.WS_Form.this.html_encode($.WS_Form.this.object_data_scratch['label']));
					break;

				case 'field' :

					// Render field (Simulate using new object data)
					$.WS_Form.this.field_render(obj, $.WS_Form.this.object_data_scratch);

					// Render placeholders
					$.WS_Form.this.sidebar_placeholders_init(obj_outer);

					break;
			}
		});
	}


	// Sidebar - Change Event - Init
	$.WS_Form.prototype.sidebar_change_event_init = function(obj, object, obj_inner) {

		$('[data-change-event]', obj_inner).each(function() {

			var change_event = $(this).attr('data-change-event');

			$(this).on(change_event, function() {

				var change_action = $(this).attr('data-change-action');

				// Update field data
				var object_id = obj.attr('data-id');
				var meta_key = $(this).attr('data-meta-key');

				$.WS_Form.this.object_data_update_by_meta_key(object, $.WS_Form.this.object_data_scratch, meta_key);

				// Meta key specific
				switch(meta_key) {

					case 'recaptcha_recaptcha_type' :

						// If recaptcha is set to invisible, reset breakpoints to full width
						if($(this).val() == 'invisible') {

							// Remove old classes
							$.WS_Form.this.column_classes_render(obj, $.WS_Form.this.object_data_scratch, false);

							// Reset
							$.WS_Form.this.breakpoint_reset_process($.WS_Form.this.object_data_scratch);

							// Add new classes
							$.WS_Form.this.column_classes_render(obj, $.WS_Form.this.object_data_scratch);
						}
						break;
				}

				// Object specific
				switch(object) {

					case 'section' :

						// Render section
						var object_data_old = $.extend(true, {}, $.WS_Form.this.section_data_cache[object_id]); // Deep clone
						$.WS_Form.this.section_data_cache[object_id] = $.WS_Form.this.object_data_scratch;
						$.WS_Form.this.section_render(obj);
						obj.addClass('wsf-editing');
						$.WS_Form.this.section_data_cache[object_id] = object_data_old;
						break;

					case 'field' :

						// Render field
						var object_data_old = $.extend(true, {}, $.WS_Form.this.field_data_cache[object_id]); // Deep clone
						$.WS_Form.this.field_data_cache[object_id] = $.WS_Form.this.object_data_scratch;
						$.WS_Form.this.field_render(obj);
						obj.addClass('wsf-editing');
						$.WS_Form.this.field_data_cache[object_id] = object_data_old;
						break;
				}

				// Reload sidebar on change
				if(change_action == 'reload') {

					$.WS_Form.this.object_save(obj, false);
					obj.removeClass('wsf-editing')
					$.WS_Form.this.object_edit(obj, true);
				}
			});
		});
	}

	// Sidebar - Image
	$.WS_Form.prototype.sidebar_image = function(obj_sidebar_inner) {

		var file_frame;

		$('input[data-meta-key-type="image"]', obj_sidebar_inner).each(function() {

			var field_id = $(this).attr('id');

			// Find button
			var field_button = $('button[data-for="' + field_id + '"]');

			field_button.click(function() {

				var for_id = $(this).attr('data-for');

				// If the media frame already exists, reopen it.
				if(file_frame) {

					// Open frame
					file_frame.open();
					return;
				}

				// Create the media frame.
				file_frame = wp.media.frames.file_frame = wp.media({

					title: 'Select image',
					button: {
						text: 'Use this image',
					},
					multiple: false
				});

				// When an image is selected, run a callback.
				file_frame.on('select', function() {

					// We set multiple to false so only get one image from the uploader
					var attachment = file_frame.state().get('selection').first().toJSON();

					$('#' + for_id).val(attachment.url);
				});

				// Finally, open the modal
				file_frame.open();
			});
		});
	}

	// Sidebar - Title - Init
	$.WS_Form.prototype.sidebar_required_setting = function(object_data, obj_sidebar_inner) {

		$('[data-required-setting]', obj_sidebar_inner).on('change keyup input', function() {

			$.WS_Form.this.sidebar_required_setting_process(object_data, obj_sidebar_inner);
		});

		// Initial run
		$.WS_Form.this.sidebar_required_setting_process(object_data, obj_sidebar_inner);
	}

	$.WS_Form.prototype.sidebar_required_setting_process = function(object_data, obj_sidebar_outer, obj_sidebar_inner) {

		$('[data-required-setting]', obj_sidebar_inner).each(function() {

			var require_setting_icon = $('.wsf-required-setting', $(this).closest('.wsf-field-wrapper'));

			if($(this).val() == '') {

				$(this).addClass('wsf-error');
				require_setting_icon.show();

			} else {

				$(this).removeClass('wsf-error');
				require_setting_icon.hide();
			}
		})
	}

	// Sidebar - Knowledge Base
	$.WS_Form.prototype.sidebar_knowledgebase = function(obj_sidebar_outer, obj_sidebar_inner) {

		var knowledgebase_obj = $('#wsf-form-knowledgebase .wsf-fieldset', obj_sidebar_inner);

		// If data is already loaded, no need to make further requests to server
		if(knowledgebase_obj.attr('data-loaded')) { return true; }

		// Search
		var sidebar_knowledgebase_html = '<div class="wsf-field-wrapper">';

		sidebar_knowledgebase_html += '<label class="wsf-label" for="wsf-kb-search">' + this.language('knowledgebase_search_label') + '</label>';

		sidebar_knowledgebase_html += '<div class="wsf-field-inline">';
		sidebar_knowledgebase_html += '<input type="text" id="wsf-kb-search-keyword" class="wsf-field" value="" placeholder="' + this.language('knowledgebase_search_placeholder') + '" />';
		sidebar_knowledgebase_html += '<button class="wsf-button wsf-button-primary" data-action="wsf-kb-search">' + this.language('knowledgebase_search_button') + '</button>';
		sidebar_knowledgebase_html += '</div>';

		sidebar_knowledgebase_html += '</div>';

		// Content
		sidebar_knowledgebase_html += '<div class="wsf-field-wrapper">';
		sidebar_knowledgebase_html += '<div id="wsf-kb-content"></div>';
		sidebar_knowledgebase_html += '</div>';

		knowledgebase_obj.html(sidebar_knowledgebase_html); 

		// Request knowledgebase content and pass across WS Form variables only just so we know what knowledgebase content to provide
		$.get(

			'https://wsform.com/plugin-support/knowledgebase_content.php?l=' + encodeURIComponent(ws_form_settings.locale) + '&e=' + encodeURIComponent(ws_form_settings.edition) + '&v=' + encodeURIComponent(ws_form_settings.version),
			function( data ) {

				$('#wsf-kb-content').html(data);
				knowledgebase_obj.attr('data-loaded', 'true');
			}
		);

		// Search button
		$('[data-action="wsf-kb-search"]').click(function() {

			$.WS_Form.this.sidebar_knowledgebase_search();
		});

		// Enter on keyword
		$('#wsf-kb-search-keyword').keypress(function(e) {

			if(e.which == 13) {

				$.WS_Form.this.sidebar_knowledgebase_search();
			}
		});

		// Wrap contact form in form tag
		var contact_html = $('#wsf-form-contact').html();
		var contact_html = '<form id="wsf-contact-form">' + contact_html + '</form>';
		$('#wsf-form-contact').html(contact_html);

		$('#wsf-contact-form').on('submit', function(e) {

			$('#wsf_contact_submit').attr('disabled', 'disabled');

			// Check GDPR checkbox
			var contact_gdpr = $('#wsf_contact_gdpr').is(':checked');
			if(!contact_gdpr) { return false; }

			// Build form data
			var params = {

				'contact_first_name': $('#wsf_contact_first_name').val(),
				'contact_last_name': $('#wsf_contact_last_name').val(),
				'contact_email': $('#wsf_contact_email').val(),
				'contact_inquiry': $('#wsf_contact_inquiry').val(),
				'contact_push_form': $('#wsf_contact_push_form').is(':checked'),
				'contact_push_system': $('#wsf_contact_push_system').is(':checked')
			};

			// Fallback to variables
			$.WS_Form.this.api_call('helper/support_contact_submit/', 'POST', params, function(data) {

				if(data.error) {

					$.WS_Form.this.error(this.language(support_contact_error, response.error_message));

				} else {

					$('#wsf-form-contact form fieldset').html('<div class="wsf-field-wrapper">' + data.response + '</div>');
				}

				$.WS_Form.this.loader_off();
			});

			return false;
		});
	}

	// Sidebar - Knowledge Base - Search
	$.WS_Form.prototype.sidebar_knowledgebase_search = function() {

		var keyword = $('#wsf-kb-search-keyword').val();
		if(keyword == '') { return; }

		// Tidy up keyword
		keyword = $.trim(keyword);

		$.get(

			'https://wsform.com/plugin-support/knowledgebase_search.php?l=' + encodeURIComponent(ws_form_settings.locale) + '&e=' + encodeURIComponent(ws_form_settings.edition) + '&v=' + encodeURIComponent(ws_form_settings.version) + '&k=' + encodeURIComponent(keyword),
			function( data ) {

				$('#wsf-kb-content').html(data);
			}
		);
	}

	// Sidebar - Auto Map Fields
	$.WS_Form.prototype.sidebar_auto_map = function(obj) {

		$('[data-action="wsf-auto-map"]').click(function() {

			// Start Auto Map annimation
			var api_auto_map_obj = $(this);
			api_auto_map_obj.addClass('wsf-api-method-calling');

			// Get meta key
			var option_meta_key = $(this).attr('data-option-meta-key');
			var object = $(this).attr('data-object');
			var object_id = $(this).attr('data-object-id');

			// Get meta key config
			if(typeof($.WS_Form.meta_keys[option_meta_key]) === 'undefined') { return false; }
			var meta_key_config = $.WS_Form.meta_keys[option_meta_key];

			// Check type
			if(typeof(meta_key_config.type) === 'undefined') { return false; }
			if(meta_key_config.type !== 'repeater') { return false; }

			// Get meta keys to map
			if(typeof(meta_key_config.meta_keys) === 'undefined') { return false; }
			var meta_keys = meta_key_config.meta_keys;
			if(meta_keys.length !== 2) { return false; }

			var index_fields = false;
			var key_field = false;
			var key_api_field = false;
			var index_api_fields = false;
			var options_action_id = false;
			var options_list_id_meta_key = false;
			var options_list_sub_id_meta_key = false;
			var options_action_api_populate = 'list_fields_fetch';

			// Get lists of data
			for(var meta_key_index in meta_keys) {

				var meta_key = meta_keys[meta_key_index];

				// Get meta key config
				if(typeof($.WS_Form.meta_keys[meta_key]) === 'undefined') { return false; }
				var meta_key_config = $.WS_Form.meta_keys[meta_key];

				// Check type
				if(typeof(meta_key_config.type) === 'undefined') { return false; }				
				var meta_key_type = meta_key_config.type;
				if(meta_key_type != 'select') { return false; }

				// Get options
				if(typeof(meta_key_config.options) === 'undefined') { return false; }
				var meta_key_options = meta_key_config.options;

				switch(meta_key_options) {

					case 'fields' :

						key_field = (typeof(meta_key_config.key) !== 'undefined') ? meta_key_config.key : meta_key;
						index_fields = meta_key_index;
						break;

					case 'action_api_populate' :

						index_api_fields = meta_key_index;
						key_api_field = meta_key;
						options_list_id_meta_key = meta_key_config.options_list_id_meta_key;
						options_list_sub_id_meta_key = meta_key_config.options_list_sub_id_meta_key;

						if(typeof(meta_key_config.options_action_id_meta_key) !== 'undefined') {

							options_action_id = $('[data-meta-key="' + meta_key_config.options_action_id_meta_key + '"]').val();

						} else {

							options_action_id = meta_key_config.options_action_id;
						}

						options_action_api_populate = (typeof(meta_key_config.options_action_api_populate) !== 'undefined') ? meta_key_config.options_action_api_populate : 'list_fields_fetch';

						break;
				}
			}

			if(index_fields === false) { return false; }
			if(index_api_fields === false) { return false; }

			// Get list ID
			var options_action_list_id = $('[data-meta-key="' + options_list_id_meta_key + '"]').val();

			// Get list sub ID
			var options_action_list_sub_id = $('[data-meta-key="' + options_list_sub_id_meta_key + '"]').val();

			// Get API call path
			var api_call_path = $.WS_Form.this.action_api_method_path(options_action_id, options_action_api_populate, options_action_list_id, options_action_list_sub_id);

			$.WS_Form.this.options_action_cache_clear(api_call_path);

			// Make API call
			var fields_api = [];
			$.WS_Form.this.api_call(api_call_path, 'GET', false, function(response) {

				if(typeof(response.data) === 'undefined') { return false; }

				for(var api_field_index in response.data) {

					var api_field = response.data[api_field_index];

					fields_api.push({'id': api_field.id, 'label': api_field.label});
				}

				if(fields_api.length == 0) { $.WS_Form.error('error_auto_map_api_fields'); return false; }

				// Mapping
				var action_value = [];
				for(var fields_api_index in fields_api) {

					var field_api = fields_api[fields_api_index];

					var field_api_label = field_api.label;

					var field_id_best = false;
					var field_score_best = 0;
					for(var fields_index in $.WS_Form.this.field_data_cache) {

						var field = $.WS_Form.this.field_data_cache[fields_index];

						var field_label = field.label;

						var score = String(field_label).score(String(field_api_label));
						score += String(field_api_label).score(String(field_label));

						if(score > field_score_best) {

							field_score_best = score;
							field_id_best = fields_index;
						}
					}

					if(field_score_best > 0) {

						var action_value_single = {};
						action_value_single[key_field] = field_id_best;
						action_value_single[key_api_field] = field_api.id;
						action_value.push(action_value_single);
					}
				}

				// Set meta_key value
				if(action_value.length > 0) {

					// Get object and object ID
					var object_data = $.WS_Form.this.get_object_data(object, object_id, true);

					object_data.meta[option_meta_key] = action_value;

					// Initialize sidebar
					var inits = ['options-action', 'repeater'];
					var obj_sidebar_inner = obj.closest('.wsf-sidebar-inner');
					if(!obj_sidebar_inner.length) { obj_sidebar_inner = $('.wsf-sidebar-inner', obj); }
					$.WS_Form.this.sidebar_inits(inits, obj_sidebar_inner);
				}

				// Remove class
				api_auto_map_obj.removeClass('wsf-api-method-calling');

			}, function() {}, true);	// Bypass loader
		});
	}

	// Sidebar - Variables - Init
	$.WS_Form.prototype.sidebar_select_list = function(obj) {

		$('[data-action="wsf-select-list"]').click(function() {

			var field_wrapper = $(this).closest('.wsf-field-wrapper');
			var meta_key = $(this).attr('data-option-meta-key');
			var already_open = $(this).hasClass('wsf-select-list-open');

			$('.wsf-select-list', $('#wsf-sidebars')).remove();
			$('.wsf-select-list-open').removeClass('wsf-select-list-open');

			if(!already_open) {

				$(this).addClass('wsf-select-list-open');

				if(typeof($.WS_Form.meta_keys[meta_key]['select_list']) === 'object') {

					var select_list_heading = (typeof($.WS_Form.meta_keys[meta_key]['select_list_heading']) !== 'undefined') ? $.WS_Form.meta_keys[meta_key]['select_list_heading'] : false;

					// List specified at meta key level
					$.WS_Form.this.sidebar_select_list_process(field_wrapper, $.WS_Form.meta_keys[meta_key]['select_list'], meta_key, select_list_heading);

				} else {

					// Fallback to variables
					$.WS_Form.this.api_call('helper/variables/', 'GET', false, function(list) {

						$.WS_Form.this.sidebar_select_list_process(field_wrapper, list, meta_key);
					});
				}
			}
		});
	}

	$.WS_Form.prototype.sidebar_select_list_process = function(obj, list, meta_key, heading) {

		if(typeof(heading) === 'undefined') { var heading = false; }

		// Build list
		var list_html = '<div class="wsf-select-list"><table>';

		if(heading !== false) { list_html += '<thead><tr><th>' + heading + '</th></tr></thead>'; }

		list_html += '<tbody>';

		for(var list_index in list) {

			var list_item = list[list_index];

			list_html += '<tr data-action="wsf-list-item-insert" data-action-html="' + $.WS_Form.this.html_encode(list_item.value) + '"><td>' + list_item.text + '</td></tr>';
		}

		list_html += '</tbody></table></div>';

		// Append list
		var wsf_field_wrapper = $('[data-meta-key="' + meta_key + '"]').closest('.wsf-field-wrapper');
		var wsf_helper = $('.wsf-helper', wsf_field_wrapper);

		if(wsf_helper.length) {

			$(list_html).insertBefore(wsf_helper);

		} else {
	
			$(list_html).insertAfter($('[data-meta-key="' + meta_key + '"]'));
		}

		// Turn off loader
		$.WS_Form.this.loader_off();

		// Set up events
		$('[data-action="wsf-list-item-insert"]').click(function(e) {

			var list_html = $(this).attr('data-action-html');

			$.WS_Form.this.input_insert_text($('[data-meta-key="' + meta_key + '"]'), list_html);
		})
	}

	// Sidebar - Option - Action - Reload - Init
	$.WS_Form.prototype.sidebar_api_reload_init = function(obj) {

		// Action API method events
		this.api_reload_init(obj, function(obj, action_id, action_api_method) {

			// Initialize sidebar
			var inits = ['options-action', 'repeater'];
			var obj_sidebar_inner = obj.closest('.wsf-sidebar-inner');
			$.WS_Form.this.sidebar_inits(inits, obj_sidebar_inner);

		}, null, true);
	}

	// Sidebar - Option - Action - Init
	$.WS_Form.prototype.sidebar_option_action_init = function(obj, complete) {

		// Get select objects
		this.options_action_objects = $('[data-options-action-api-populate]', obj).toArray();

		// Start processing them
		this.sidebar_option_action_process(complete);
	}

	// Sidebar - Option - Action - Init
	$.WS_Form.prototype.sidebar_option_action_process = function(complete) {

		// If there are no more actions to process, then return true
		if(this.options_action_objects.length == 0) { 

			// Run complete function
			complete();

			return true;
		}

		// Get next select to populate
		var options_action_obj = $(this.options_action_objects.shift());

		// Get action ID and API method
		var action_id = options_action_obj.attr('data-options-action-id');
		if(action_id == undefined) {

			var action_id_meta_key = options_action_obj.attr('data-options-action-id-meta-key');
			if(action_id_meta_key != undefined) {

				action_id = $('[data-meta-key="' + action_id_meta_key + '"]', $('#wsf-sidebars')).val();
			}
		}
		var action_api_method = options_action_obj.attr('data-options-action-api-populate');

		// Get list ID
		var list_id_meta_key = options_action_obj.attr('data-options-list-id-meta-key');
		var list_id = (list_id_meta_key != undefined) ? $('[data-meta-key="' + list_id_meta_key + '"]', $('#wsf-sidebars')).val() : false;

		// Get list sub ID
		var list_sub_id_meta_key = options_action_obj.attr('data-options-list-sub-id-meta-key');
		var list_sub_id = (list_sub_id_meta_key != undefined) ? $('[data-meta-key="' + list_sub_id_meta_key + '"]', $('#wsf-sidebars')).val() : false;

		// Get API call path
		var api_call_path = $.WS_Form.this.action_api_method_path(action_id, action_api_method, list_id, list_sub_id);

		// Check action_id
		if(api_call_path === false) {

			// Process next
			return this.sidebar_option_action_process(complete);
		}

		// Get object and object ID
		var object = options_action_obj.attr('data-object');
		var object_id = options_action_obj.attr('data-object-id');
		var object_data = $.WS_Form.this.get_object_data(object, object_id, true);

		// Get meta key and meta value
		var meta_key = options_action_obj.attr('data-meta-key');

		// Get meta key config
		var meta_key_config = (typeof($.WS_Form.meta_keys[meta_key]) !== 'undefined') ? $.WS_Form.meta_keys[meta_key] : false;
		if(meta_key_config === false) {

			// Process next
			return this.sidebar_option_action_process(complete);
		}

		// Check for repeater
		var repeater_meta_key = options_action_obj.attr('data-repeater-meta-key');

		// Get meta_value
		if(repeater_meta_key === undefined) {

			var meta_value = $.WS_Form.this.get_object_meta_value(object_data, meta_key, [], true);

		} else {

			// Get row index
			var row = options_action_obj.closest('tr');
			var row_index = row.index();

			// Get repeater data
			var repeater_meta_value_array = $.WS_Form.this.get_object_meta_value(object_data, repeater_meta_key, [], true);

			// Get row data
			var repeater_row_array = (typeof(repeater_meta_value_array[row_index]) !== 'undefined') ? repeater_meta_value_array[row_index] : [];

			// Get column (meta_value)
			var meta_value = (typeof(repeater_row_array[meta_key]) !== 'undefined') ? repeater_row_array[meta_key] : '';
		}

		// Check if options exist in cache
		if(typeof(this.options_action_cache[api_call_path]) === 'undefined') {

			// Make API call
			$.WS_Form.this.api_call(api_call_path, 'GET', false, function(response) {

				// Build options for action
				var options_action = [];

				if(typeof(response.data) !== 'undefined') {

					var rows = response.data;
					for(var row_index in rows) {

						var row = rows[row_index];
						var text = row.label;
						if((typeof(row.required) !== 'undefined') && row.required) { text += ' *'; }

						options_action.push({'value': row.id, 'text': text});
					}
				}

				// Add to cache
				$.WS_Form.this.options_action_cache[api_call_path] = options_action;

				// Render
				return $.WS_Form.this.sidebar_option_action_process_render(options_action_obj, options_action, meta_value, complete);

			}, function() {

				// Failed, so attempt to process next
				return $.WS_Form.this.sidebar_option_action_process(complete);

			}, true);	// Bypass loader

		} else {

			// Pull from cache
			var options_action = this.options_action_cache[api_call_path];

			// Render
			return this.sidebar_option_action_process_render(options_action_obj, options_action, meta_value, complete);
		}
	}

	// Sidebar - Option - Action - Init
	$.WS_Form.prototype.sidebar_option_action_process_render = function(options_action_obj, options_action, meta_value, complete) {

		var options_html = '';

		// Read meta key config
		var meta_key = options_action_obj.attr('data-meta-key');
		var meta_key_config = $.WS_Form.meta_keys[meta_key];

		// Insert blank option
		if(typeof(meta_key_config['options_blank']) !== 'undefined') {

			options_html += '<option value="">' + this.html_encode(meta_key_config['options_blank']) + '</option>';
		}

		// Render options
		for(var options_action_index in options_action) {

			var option_action_single = options_action[options_action_index];

			options_html += '<option value="' + this.html_encode(option_action_single.value) + '"' + ((meta_value == option_action_single.value) ? ' selected="selected"' : '') + '>' + this.html_encode(option_action_single.text) + '</option>';
		}

		// Populate
		options_action_obj.html(options_html);

		// Process next
		return this.sidebar_option_action_process(complete);
	}

	// Sidebar - Repeater - Init
	$.WS_Form.prototype.sidebar_repeater_init = function(obj_inner) {

		$('.wsf-repeater', obj_inner).each(function() {

			// Render
			$.WS_Form.this.sidebar_repeater_render($(this));
		});
	}

	// Sidebar - Repeater - Row - New
	$.WS_Form.prototype.sidebar_repeater_render = function(obj) {

		// Get data
		var repeater = this.sidebar_repeater_get(obj);

		// Get repeater HTML
		var sidebar_repeater_html_return = this.sidebar_repeater_html(repeater);

		// Render
		obj.html(sidebar_repeater_html_return.html);

		// Init
		this.sidebar_inits(sidebar_repeater_html_return.inits, obj);

		// Event - Add Row
		$('[data-action="wsf-repeater-row-add"] div', obj).click(function() {

			// Get data
			var repeater_obj = $(this).closest('.wsf-repeater');

			var object = repeater_obj.attr('data-object');
			var object_id = repeater_obj.attr('data-id');
			var object_data = $.WS_Form.this.get_object_data(object, object_id, true);

			var meta_key = repeater_obj.attr('data-meta-key');

			// Save repeater data
			$.WS_Form.this.object_data_update_by_meta_key(object, object_data, meta_key);

			// Get repeater data
			var repeater = $.WS_Form.this.sidebar_repeater_get(repeater_obj);

			// Get empty row
			var repeater_data_row = $.WS_Form.this.sidebar_repeater_row_new(repeater.meta_keys);

			// Add to data
			repeater.data.push(repeater_data_row);
			$.WS_Form.this.set_object_meta_value(repeater.object_data, repeater.meta_key, repeater.data);

			// Re-render
			$.WS_Form.this.sidebar_repeater_render(repeater_obj);

			// Initialize
			var inits = ['options-action', 'repeater'];
			$.WS_Form.this.sidebar_inits(inits, repeater_obj);	
		});

		// Event - Delete Row
		$('[data-action="wsf-repeater-row-delete"]', obj).click(function() {

			// Get data
			var repeater_obj = $(this).closest('.wsf-repeater');

			var object = repeater_obj.attr('data-object');
			var object_id = repeater_obj.attr('data-id');
			var object_data = $.WS_Form.this.get_object_data(object, object_id, true);

			var meta_key = repeater_obj.attr('data-meta-key');

			// Get repeater data
			var repeater = $.WS_Form.this.sidebar_repeater_get(repeater_obj);

			// Get row data
			var row = $(this).closest('tr');
			var row_index = row.index();

			// Delete row from DOM
			row.remove();

			// Save repeater data
			$.WS_Form.this.object_data_update_by_meta_key(object, object_data, meta_key);

			// Re-render
			$.WS_Form.this.sidebar_repeater_render(repeater_obj);

			// Initialize
			var inits = ['options-action', 'repeater'];
			$.WS_Form.this.sidebar_inits(inits, repeater_obj);	
		});
	}

	// Sidebar - Repeater - Row - New
	$.WS_Form.prototype.sidebar_repeater_row_new = function(meta_keys) {

		var repeater_row = {};

		for(var meta_keys_index in meta_keys) {

			var meta_key = meta_keys[meta_keys_index];

			// Ensure meta key is configured
			if(typeof($.WS_Form.meta_keys[meta_key]) === 'undefined') { return false; }

			var meta_value = (typeof($.WS_Form.meta_keys[meta_key].default) !== 'undefined') ? $.WS_Form.meta_keys[meta_key].default : '';

			if(typeof($.WS_Form.meta_keys[meta_key]['key']) !== 'undefined') { meta_key = $.WS_Form.meta_keys[meta_key]['key']; }

			repeater_row[meta_key] = meta_value;
		}

		return repeater_row;
	}

	// Sidebar - Repeater - Get data
	$.WS_Form.prototype.sidebar_repeater_get = function(obj) {

		// Get meta key
		var meta_key = obj.attr('data-meta-key');

		// Read meta key config
		if(typeof($.WS_Form.meta_keys[meta_key]) === 'undefined') { return false; }
		var meta_key_config = $.WS_Form.meta_keys[meta_key];

		// Read meta keys for repeater
		if(typeof(meta_key_config['meta_keys']) !== 'object') { return false; }
		var meta_keys = meta_key_config['meta_keys'];

		// Get object
		var object = obj.attr('data-object');

		// Get object ID
		var object_id = obj.attr('data-id');

		// Get data
		var object_data = $.WS_Form.this.get_object_data(object, object_id, true);

		// Get repeater data
		var data = $.WS_Form.this.get_object_meta_value(object_data, meta_key, [], true);
		if(typeof(data) !== 'object') { data = []; }

		return {'object': object, 'object_id': object_id, 'meta_key': meta_key, 'meta_keys': meta_keys, 'object_data': object_data, 'data': data};
	}

	// Sidebar - Repeater - HTML
	$.WS_Form.prototype.sidebar_repeater_html = function(repeater) {

		// Build repeater
		var repeater_html = '<table><thead><tr>';

		var repeater_inits = [];

		// Header columns
		for(var meta_keys_index in repeater.meta_keys) {

			var meta_keys_single = repeater.meta_keys[meta_keys_index];

			// Ensure meta key is configured
			if(typeof($.WS_Form.meta_keys[meta_keys_single]) === 'undefined') { return false; }

			var meta_key_single = $.WS_Form.meta_keys[meta_keys_single];

			repeater_html += '<th>' + this.html_encode(meta_key_single.label) + '</th>';
		}

		// Blank column for delete icons
		repeater_html += '<th data-icon></th>';

		repeater_html += '</tr></thead><tbody>';

		// Rows
		for(var repeater_data_index in repeater.data) {

			var repeater_data_row = repeater.data[repeater_data_index];

			var sidebar_html_return = this.sidebar_repeater_row_html(repeater, repeater_data_row, repeater.meta_key);

			repeater_html += sidebar_html_return.html;

			repeater_inits = repeater_inits.concat(sidebar_html_return.inits);
		}

		repeater_html += '</tbody></table>';

		// Add button
		repeater_html += '<div data-action="wsf-repeater-row-add" title="' + this.language('repeater_row_add') + '"><div>' + this.svg('plus-circle') + '</div></div>';

		return {'html': repeater_html, 'inits': repeater_inits};
	}

	// Sidebar - Repeater - Row - HTML
	$.WS_Form.prototype.sidebar_repeater_row_html = function(repeater, repeater_data_row, repeater_meta_key) {

		var repeater_row_html = '<tr>';
		var repeater_row_inits = [];

		// Build object data (sidebar_html then uses this to extract the meta_value)
		var object_data = [];
		object_data['meta'] = repeater_data_row;

		for(var meta_keys_index in repeater.meta_keys) {

			var meta_keys_single = repeater.meta_keys[meta_keys_index];

			var repeater_fieldset = {'fieldsets': {}};
			repeater_fieldset.fieldsets[repeater.object_id] = {'meta_keys': [meta_keys_single]};

			var sidebar_html_return = this.sidebar_html(repeater.object, repeater.object_id, object_data, repeater_fieldset, repeater_meta_key);

			repeater_row_html += '<td>' + sidebar_html_return.html + '</td>';

			repeater_row_inits = repeater_row_inits.concat(sidebar_html_return.inits);
		}

		repeater_row_html += '<td data-icon><div data-action="wsf-repeater-row-delete" title="' + this.language('repeater_row_delete') + '">' + this.svg('minus-circle') + '</div></td>';

		repeater_row_html += '</tr>';

		return {'html': repeater_row_html, 'inits': repeater_row_inits};
	}

	// Sidebar - Range - Init
	$.WS_Form.prototype.sidebar_range_init = function(obj_inner) {

		// Configure all range sliders
		var obj = $('input[type="range"]', obj_inner).first();
		if(!obj.length) { return false; }

		// Value changes
		obj.on('input', function() { $.WS_Form.this.sidebar_range_update(obj_inner, obj); });
		$.WS_Form.this.sidebar_range_update(obj_inner, obj);

		// Don't process min, max and step if in advanced mode
		var mode = $.WS_Form.settings_plugin.mode;
		var mode_advanced = (mode == 'advanced');
		if(mode_advanced) { return true; }

		// Configure default values that are range sliders
		var obj_default = $('input[type="range"][data-meta-key="default_value"]', obj_inner).first();
		if(!obj_default.length) { return false; }

		// Min, max, step changes
		$('[data-meta-key="min"],[data-meta-key="max"],[data-meta-key="step"]', obj_inner).change(function() { $.WS_Form.this.sidebar_default_value_attributes(obj_inner, obj, 'range'); this.sidebar_range_update(obj_inner, obj); });
		$.WS_Form.this.sidebar_default_value_attributes(obj_inner, obj, 'range');
	}

	// Sidebar - Range - Process
	$.WS_Form.prototype.sidebar_range_update = function(obj_inner, obj) {

		var value = obj.val();
		var meta_key = obj.attr('data-meta-key');
		$('#wsf_' + meta_key + '_range_value', obj_inner).html(value);
	}

	// Sidebar - Number - Init
	$.WS_Form.prototype.sidebar_number_init = function(obj_inner) {

		// Configure default values that are number inputs
		var obj = $('input[type="number"][data-meta-key="default_value"]', obj_inner).first();
		if(!obj.length) { return false; }

		// Look for minimum value
		$('[data-meta-key="min"],[data-meta-key="max"],[data-meta-key="step"]', obj_inner).change(function() { $.WS_Form.this.sidebar_default_value_attributes(obj_inner, obj, 'number'); });
		$.WS_Form.this.sidebar_default_value_attributes(obj_inner, obj, 'number');
	}

	// Sidebar - Date/Time - Init
	$.WS_Form.prototype.sidebar_datetime_init = function(obj_outer) {

		// Configure default values that are number inputs
		var obj = $('input[data-meta-key="default_value"]', obj_outer).first();

		if(!obj.length) { return false; }

		// Look for minimum value
		$('[data-meta-key="min_date"],[data-meta-key="max_date"],[data-meta-key="step"]', obj_outer).change(function() { $.WS_Form.this.sidebar_default_value_attributes(obj_outer, obj, 'datetime'); });
		$.WS_Form.this.sidebar_default_value_attributes(obj_outer, obj, 'datetime');
	}

	// Sidebar - Update default value attributes
	$.WS_Form.prototype.sidebar_default_value_attributes = function(obj_outer, obj, type) {

		// Get obj value
		var value = obj.val();

		// Get step object
		switch(type) {

			case 'datetime' :

				var meta_key_min = 'min_date';
				var meta_key_max = 'max_date';
				break;

			default :

				var meta_key_min = 'min';
				var meta_key_max = 'max';
				break;
		}
		var obj_min = $('[data-meta-key="' + meta_key_min + '"]', obj_outer).first();
		var obj_max = $('[data-meta-key="' + meta_key_max + '"]', obj_outer).first();
		var obj_step = $('[data-meta-key="step"]', obj_outer).first();

		// Get values according to type
		switch(type) {

			case 'range' :

				var min = this.get_number(obj_min.val(), 0);
				var max = this.get_number(obj_max.val(), 100);
				var step = this.get_number(obj_step.val(), 1);

				if((obj_max.val() != '') && (min > max)) { min = max; obj_min.val(min); }

				if(min == 0) { obj.removeAttr('min'); } else { obj.attr('min', min); }
				if(max == 100) { obj.removeAttr('max'); } else { obj.attr('max', max); }
				if(step == 1) { obj.removeAttr('step'); } else { obj.attr('step', step); }

				// Check value
				obj.val(value > max ? max : value < min ? min : value);

				break;

			case 'number' :

				var min = (obj_min.val() != '') ? this.get_number(obj_min.val()) : false;
				var max = (obj_min.val() != '') ? this.get_number(obj_max.val()) : false;
				var step = (obj_min.val() != '') ? this.get_number(obj_step.val()) : false;

				if((min !== false) && (max !== false) && (min > max)) { min = max; obj_min.val(min); }

				if(min === false) { obj.removeAttr('min'); } else { obj.attr('min', min); }
				if(max === false) { obj.removeAttr('max'); } else { obj.attr('max', max); }
				if(step === false) { obj.removeAttr('step'); } else { obj.attr('step', step); }

				// Check value
				if((max !== false) && (value > max)) { obj.val(max); }
				if((min !== false) && (value < min)) { obj.val(min); }

			case 'datetime' :

				var min = (obj_min.val() != '') ? obj_min.val() : false;
				var max = (obj_min.val() != '') ? obj_max.val() : false;
				var step = (obj_min.val() != '') ? obj_step.val() : false;

				if(min === false) { obj.removeAttr('min'); } else { obj.attr('min', min); }
				if(max === false) { obj.removeAttr('max'); } else { obj.attr('max', max); }
				if(step === false) { obj.removeAttr('step'); } else { obj.attr('step', step); }

				break;
		}
	}

	// Sidebar - Tabs - Init
	$.WS_Form.prototype.sidebar_tabs_init = function(obj_outer, obj_inner) {

		// Init tabs
		obj_outer.tabs({

			activate: function() {

				// Reset scrolling
				obj_inner.scrollTop(0);

				// Initialize TinyMCE
				$.WS_Form.this.sidebar_tinymce_init(obj_inner);

				// Initialize HTML
				$.WS_Form.this.sidebar_html_editor_init(obj_inner);
			}
		});
	}

	// Sidebar - Field Select - Init
	$.WS_Form.prototype.sidebar_field_select_init = function() {

		var field_select_html = '<div class="wsf-field-select"><ul>';

		// Add field types
		for (var group_key in $.WS_Form.field_types) {

			var group = $.WS_Form.field_types[group_key];
			var label = group.label;
			var types = group.types;

			// Skip empty groups
			if(types.length == 0) { continue; }

			field_select_html += '<li class="wsf-field-selector-group wsf-fields-group-' + group_key + '"><h3>' + label + '</h3><ul>';

			// Add field types
			for (var type in types) {

				var field_type = types[type];

				// Is pro required? (i.e. edition is not pro)
				var pro_required = field_type['pro_required'];
				// Build knowledge base HTML
				if((typeof(field_type.kb_url) !== 'undefined')) {

					var kb_url = this.get_plugin_website_url(field_type.kb_url, 'field_select');
					field_select_html += '<li class="wsf-field-wrapper' + (pro_required ? ' wsf-pro-required' : '') + '" data-type="' + type + '">' + (pro_required ? '<a href="' + kb_url + '" target="_blank">' : '') + '<div class="wsf-field-inner"><div class="wsf-field-label">' + field_type.icon + '</div><div class="wsf-field-type">' + field_type.label + '</div></div>' + (pro_required ? '</a>' : '') + '</li>';

				} else {

					field_select_html += '<li class="wsf-field-wrapper' + (pro_required ? ' wsf-pro-required' : '') + '" data-type="' + type + '">' + '<div class="wsf-field-inner"><div class="wsf-field-label">' + field_type.icon + '</div><div class="wsf-field-type">' + field_type.label + '</div></div></li>';
				}
			}

			field_select_html += '</ul></li>';
		}

		field_select_html += '</ul></div>';

		// Add field types
		$('#wsf-form-field-selector').html(field_select_html);

		// Make field types draggable
		$('.wsf-field-selector-group > ul > li:not(.wsf-pro-required)').draggable({

			connectToSortable: '.wsf-fields',
			helper: 'clone',
			revert: 'invalid',
			zIndex: 100001,
			cancel: '.wsf-field-disabled, .wsf-pro-required',
			appendTo: '#wsf-field-draggable ul',
			distance: 3,
			start: function(event, ui) {

				// Set correct width
				ui.helper.width($(this).width());

				// Store the field object that was cloned
				$.WS_Form.this.dragged_field = $(ui.helper);

				// Reset sidebar
				$.WS_Form.this.sidebar_reset();
			},
			drag: function(event, ui) {

				$.WS_Form.this.field_type_click_drag_check = true;
			}

		}).disableSelection();

		// Make field type clickable
		$('.wsf-field-selector-group > ul > li:not(.wsf-pro-required)').bind('mousedown', function() {

			$.WS_Form.this.field_type_click_drag_check = false;
		});
		$('.wsf-field-selector-group > ul > li:not(.wsf-pro-required)').bind('mouseup', function() {

			if($.WS_Form.this.field_type_click_drag_check === false) {

				var section_id = $.WS_Form.this.field_type_click_section_id_set();
				if(section_id === false) { return; }

				var field_type = $(this).attr('data-type');

				var field_html = '<li class="wsf-field-wrapper" data-type="' + field_type + '">' + $(this).html() + '</li>';
				$('#wsf-fields-' + section_id).append(field_html);

				var field_obj = $('#wsf-fields-' + section_id + ' li').last();

				// Push new field to AJAX
				$.WS_Form.this.field_post(field_obj);

				// Init UI
				$.WS_Form.this.init_ui();

				// Reset sidebar
				$.WS_Form.this.sidebar_reset();
			}
		});

		// Button - Cancel
		$('[data-action="wsf-sidebar-cancel"]', $('#wsf-sidebar-toolbox')).click(function() {

			$.WS_Form.this.sidebar_reset();
		});
	}

	// Sidebar - Form History - Init
	$.WS_Form.prototype.sidebar_form_history_init = function() {

		// Inject HTML
		var form_history_html = '<div class="wsf-form-history"><h3>' + this.language('sidebar_title_history') + '</h3><ul></ul></div>';
		$('#wsf-form-form-history').html(form_history_html);

		// Event - History - Mouse - Leave
		if(!this.touch_device) {

			$('.wsf-form-history ul').mouseleave(function() {

				var history_index = $.WS_Form.this.history_index;
				$.WS_Form.this.history_pull(history_index);

				// Update history classes
				$.WS_Form.this.sidebar_form_history_classes();
			});
		}
	}

	// Sidebar - TinyMCE - Init
	$.WS_Form.prototype.sidebar_tinymce_init = function(obj_inner) {

		if(typeof(wp) === 'undefined') { return false; }
		if(typeof(wp.editor) === 'undefined') { return false; }
		if(typeof(wp.editor.remove) === 'undefined') { return false; }
		if(typeof(wp.editor.initialize) === 'undefined') { return false; }

		$('.wsf-sidebar-tabs-panel:visible [data-text-editor="true"], [id^=wsf-action] [data-text-editor="true"], .wsf-text-editor [data-text-editor="true"]', obj_inner).each(function() {

			var id = $(this).attr('id');

			var init = { 

				tinymce: { 

					wpautop: 		true,
					plugins: 		'charmap colorpicker compat3x directionality fullscreen hr image lists media paste tabfocus textcolor wordpress wpautoresize wpdialogs wpeditimage wpemoji wpgallery wplink wptextpattern wpview', 
					toolbar1: 		'formatselect bold italic | bullist numlist | blockquote | alignleft aligncenter alignright | link unlink | wp_more | spellchecker | fullscreen | wp_adv',
					toolbar2: 		'strikethrough hr forecolor pastetext removeformat charmap outdent indent undo redo',
					height: 		'200px',

					init_instance_callback: function (editor) {

						editor.on('keyup change', function (e) {

							$('#' + editor.id).val(wp.editor.getContent(editor.id)).trigger('keyup').trigger('input');
						});
					}
				},

				quicktags: true,
				mediaButtons: true
			};

			// CSS
			var css = $(this).attr('data-helper-css');
			if(css) { init.tinymce.content_css = ws_form_settings.url + 'helper/' + css + '/'; }

			wp.editor.remove(id); 
			wp.editor.initialize(id, init)
		})
	}

	// Edit - Button - Save
	$.WS_Form.prototype.sidebar_buttons_init = function(obj, obj_outer) {

		// Init save events
		$('button[data-action="wsf-sidebar-save"]', obj_outer).click(function() {

			// Save
			$.WS_Form.this.object_button_save(obj);
		});

		// Init cancel events
		$('button[data-action="wsf-sidebar-cancel"]', obj_outer).click(function() {

			// Cancel
			$.WS_Form.this.object_button_cancel(obj);
		});

		// Init clone events
		$('button[data-action="wsf-sidebar-clone"]', obj_outer).click(function() {

			// Clone
			$.WS_Form.this.object_button_clone(obj);
		});

		// Init delete events
		$('button[data-action="wsf-sidebar-delete"]', obj_outer).click(function() {

			// Clone
			$.WS_Form.this.object_button_delete(obj);
		});

		// Set up key shortcuts
		$.WS_Form.this.keydown[27] = {'function': function() { $.WS_Form.this.object_button_cancel(obj); }, 'ctrl_key': false};
		$.WS_Form.this.keydown[83] = {'function': function() { $.WS_Form.this.object_button_save(obj); }, 'ctrl_key': true};
	}

	// Sidebar - Init conditions
	$.WS_Form.prototype.sidebar_conditions_init = function(obj_sidebar_outer) {

		if(this.sidebar_conditions.length == 0) { return true; }

		var ws_this = this;

		// Sidebar condition events
		var sidebar_condition_added = [];
		if(!this.sidebar_conditions_events_added) {

			for(var sidebar_conditions_index in this.sidebar_conditions) {

				// Get sidebar condition meta key
				var sidebar_condition = this.sidebar_conditions[sidebar_conditions_index];

				var sidebar_condition_meta_key = sidebar_condition.meta_key;

				// Check meta key exists
				var data_meta_key = $('[data-meta-key="' + sidebar_condition_meta_key + '"]', obj_sidebar_outer);
				if(data_meta_key.length == 0) { continue; }

				// Ensure only one change event is added per meta key
				if(typeof(sidebar_condition_added[sidebar_condition_meta_key]) !== 'undefined') { continue; }
				sidebar_condition_added[sidebar_condition_meta_key] = true;

				// Create on change event
				data_meta_key.change(function() { ws_this.sidebar_condition_process(obj_sidebar_outer, $(this), false); });
			}

			this.sidebar_conditions_events_added = true;
		}

		// Initial run
		this.sidebar_condition_process(obj_sidebar_outer, obj_sidebar_outer, true);
	}

	// Sidebar - Condition process
	$.WS_Form.prototype.sidebar_condition_process = function(obj_sidebar_outer, obj, initial_run) {

		if(this.sidebar_conditions.length == 0) { return true; }

		var condition_result_array = [];

		// Run all conditions
		for(var sidebar_conditions_index in this.sidebar_conditions) {

			var sidebar_condition = this.sidebar_conditions[sidebar_conditions_index];

			var sidebar_condition_meta_key = sidebar_condition.meta_key;
			var sidebar_condition_meta_key_obj = $('[data-meta-key="' + sidebar_condition_meta_key + '"]', obj_sidebar_outer);

			// Check meta key exists
			if(!sidebar_condition_meta_key_obj.length) { continue; }

			var sidebar_condition_meta_key_type = sidebar_condition_meta_key_obj.attr('type');

			var sidebar_condition_logic = sidebar_condition.logic;
			var sidebar_condition_meta_value = sidebar_condition.meta_value;

			var sidebar_condition_show = sidebar_condition.show;
			var sidebar_condition_show_obj = $('[data-meta-key="' + sidebar_condition_show + '"]', obj_sidebar_outer);

			var sidebar_condition_logic_previous = sidebar_condition.logic_previous;

			// Check meta key exists
			if(!sidebar_condition_show_obj.length) { continue; }

			// Get current result
			var result = true;
			var meta_value = '';

			// Process condition
			switch(sidebar_condition_meta_key_type) {

				case 'checkbox' :

					meta_value = sidebar_condition_meta_key_obj.is(':checked');

					switch(sidebar_condition_logic) {

						case '==' :

							result = meta_value;
							break;	

						case '!=' :

							result = !meta_value;
							break;	
					}

					break;

				default :

					meta_value = sidebar_condition_meta_key_obj.val();
					if(meta_value === null) { meta_value = ''; }

					// Check for options_default
					if(meta_value === 'default') {

						var meta_key_config = $.WS_Form.meta_keys[sidebar_condition_meta_key];
						if(typeof(meta_key_config['options_default']) !== 'undefined') {

							meta_value = this.get_object_meta_value(this.form, meta_key_config['options_default'], '');
						}
					}

					switch(sidebar_condition_logic) {

						case '==' :

							result = (meta_value == sidebar_condition_meta_value);
							break;	

						case '!=' :

							result = (meta_value != sidebar_condition_meta_value);
							break;	
					}
			}

			// Assign to result
			if(typeof(condition_result_array[sidebar_condition_show]) === 'undefined') {

				condition_result_array[sidebar_condition_show] = result;

			} else {

				switch(sidebar_condition_logic_previous) {

					case '||' :

						condition_result_array[sidebar_condition_show] = (condition_result_array[sidebar_condition_show] || result);
						break;

					default :

						condition_result_array[sidebar_condition_show] = (condition_result_array[sidebar_condition_show] && result);
						break;
				}
			}
		}

		// Process results
		for(sidebar_condition_show in condition_result_array) {

			var condition_result = condition_result_array[sidebar_condition_show];

			// Show / hide
			var show_obj = $('[data-meta-key="' + sidebar_condition_show + '"]', obj_sidebar_outer).closest('.wsf-field-wrapper');

			// Check fieldset to see if this is the only meta_key. If it is the sole meta_key, hide the entire fieldset.
			var show_obj_fieldset = show_obj.closest('fieldset.wsf-fieldset', obj_sidebar_outer);
			if(show_obj_fieldset.length) {

				var fieldset_meta_key_count = $('.wsf-field-wrapper', show_obj_fieldset).length;
				if(fieldset_meta_key_count == 1) { show_obj = show_obj_fieldset; }
			}

			// Show / hide object
			if(condition_result) {

				show_obj.show().removeClass('wsf-field-hidden');

			} else {

				show_obj.hide().addClass('wsf-field-hidden');
			}

			// Sidebar fieldset toggles
			this.sidebar_fieldset_toggle(show_obj);
		}

		if(!initial_run) {

			// Check if this is an element within a datagrid
			var data_grid_obj = obj.closest('.wsf-data-grid');
			if(data_grid_obj.length) {

				var data_grid_meta_key = data_grid_obj.attr('data-meta-key');

				switch(data_grid_meta_key) {

					case 'conditional' :

						$.WS_Form.this.conditional_save();
						break;

					case 'action' :

						$.WS_Form.this.action_save();
						break;
				}

			} else {

				// Get object data
				var object_identifier = obj.closest('[data-object]');
				var object = object_identifier.attr('data-object');
				var object_id = object_identifier.attr('data-id');
				var object_data = $.WS_Form.this.get_object_data(object, object_id, true);

				// Save sidebar
				for(var key in $.WS_Form.this.object_meta_cache) {

					// Get meta_key
					var meta_key = $.WS_Form.this.object_meta_cache[key]['meta_key'];

					// Update object data
					$.WS_Form.this.object_data_update_by_meta_key(object, object_data, meta_key);
				}
			}

			// Init
			var inits = ['options-action', 'repeater', 'text-editor', 'html-editor'];
			$.WS_Form.this.sidebar_inits(inits, obj_sidebar_outer);
		}
	}

	// Sidebar - Show/hide fieldsets
	$.WS_Form.prototype.sidebar_fieldset_toggle = function(obj) {

		var fieldset_obj = obj.closest('.wsf-fieldset');
		var fields_count = $('.wsf-field-wrapper', fieldset_obj).length;
		var fields_hidden = $('.wsf-field-hidden', fieldset_obj).length;

		if(fields_count == fields_hidden) {

			fieldset_obj.hide();

		} else {

			fieldset_obj.show();
		}
	}

	// Sidebar - Update placeholders
	$.WS_Form.prototype.sidebar_placeholders_init = function(obj) {

		$('[data-placeholder]', obj).each(function() {

			// Get label
			var label_obj = $('[name="label"]', obj);
			if(label_obj.length) {

				// Get label value
				var label = label_obj.val();

				// Get placeholder mask
				var mask_placeholder = $(this).attr('data-placeholder');

				// Parse mask_placeholder
				var placeholder = mask_placeholder.replace_all('#label_lowercase', label.toLowerCase());
				placeholder = placeholder.replace_all('#label', label);

			} else {

				var placeholder = '';
			}

			// Set placeholder
			$(this).attr('placeholder', $.WS_Form.this.html_encode(placeholder));
		});
	}

	// Footer - Breakpoint slider
	$.WS_Form.prototype.breakpoints = function() {

		// Build breakpoints
		var obj_breakpoints = $('#wsf-breakpoints');

		var framework = $.WS_Form.settings_plugin.framework;

		// Get current framework
		var framework = $.WS_Form.frameworks.types[framework];

		// Get current framework breakpoints
		var framework_breakpoints = framework.breakpoints;

		// Get icons for use with breakpoint key (0, 25, 50, 75, 100, 125, 150)
		var framework_icons = $.WS_Form.frameworks.icons;

		// Get current breakpoint
		var breakpoint = this.get_object_meta_value(this.form, 'breakpoint');

		// Reset global breakpoints array
		$.WS_Form.breakpoints = [];

		// Ensure breakpoint exists in current framework, otherwise set it to closest match to current
		var breakpoint_found = false;
		var breakpoint_diff_min = 0;
		var breakpoint_closest = 0;
		var breakpoint_slider_max = 0;
		var breakpoint_slider_value = 1;

		for(var breakpoint_key in framework_breakpoints) {

			breakpoint_slider_max++;

			if((breakpoint_key == breakpoint) && !breakpoint_found) {

				breakpoint_found = true;
				breakpoint_slider_value = breakpoint_slider_max;

			} else {

				var breakpoint_diff = Math.abs(breakpoint_key - breakpoint);
				if((breakpoint_diff_min == 0) || (breakpoint_diff < breakpoint_diff_min)) {

					breakpoint_diff_min = breakpoint_diff;
					breakpoint_closest = breakpoint_key;
				}
			}

			// Store breakpoint
			$.WS_Form.breakpoints.push(breakpoint_key);
		}

		if(!breakpoint_found) {

			// Could not find breakpoint, so we need to set breakpoint to closet found
			breakpoint = breakpoint_closest;

			// Set object meta
			this.set_object_meta_value(this.form, 'breakpoint', breakpoint);

			// Push form
			$.WS_Form.this.form_put(false, false, true);
		}

		// Set on form
		var form_breakpoint_class = framework_breakpoints[breakpoint]['id'];
		$('#' + this.form_obj_id).attr('data-breakpoint', form_breakpoint_class);

	}

	// Footer - Breakpoint slider - Optimize
	$.WS_Form.prototype.breakpoint_optimize = function() {

		var framework = $.WS_Form.settings_plugin.framework;

		// Get current group
		var group_obj = $($('.wsf-group-tab.ui-tabs-active a').attr('href'));

		// Sections
		for(var object_id in this.section_data_cache) {

			var object = this.section_data_cache[object_id];

			// Optimize
			$.WS_Form.this.breakpoint_optimize_process(object);
		};

		// Fields
		for(var object_id in this.field_data_cache) {

			var object = this.field_data_cache[object_id];

			// Optimize
			$.WS_Form.this.breakpoint_optimize_process(object);
		};

		// Loader on
		$.WS_Form.this.loader_on();

		// Call AJAX request
		$.WS_Form.this.api_call('form/' + $.WS_Form.this.form_id + '/full', 'PUT', {'form': $.WS_Form.this.form, 'history_method': 'put_optimize'}, function(response) {

			// Form build
			$.WS_Form.this.form_build();

			// Loader off
			$.WS_Form.this.loader_off();
		});
	}

	// Footer - Breakpoint slider - Reset
	$.WS_Form.prototype.breakpoint_reset = function() {

		var framework = $.WS_Form.settings_plugin.framework;

		// Get current group
		var group_obj = $($('.wsf-group-tab.ui-tabs-active a').attr('href'));

		// Sections
		for(var object_id in this.section_data_cache) {

			var object = this.section_data_cache[object_id];

			// Optimize
			$.WS_Form.this.breakpoint_reset_process(object);
		};

		// Fields
		for(var object_id in this.field_data_cache) {

			var object = this.field_data_cache[object_id];

			// Optimize
			$.WS_Form.this.breakpoint_reset_process(object);
		};

		// Form build
		$.WS_Form.this.form_build();

		// Loader on
		$.WS_Form.this.loader_on();

		// Call AJAX request
		$.WS_Form.this.api_call('form/' + $.WS_Form.this.form_id + '/full', 'PUT', {'form': $.WS_Form.this.form, 'history_method': 'put_reset'}, function(response) {

			// Loader off
			$.WS_Form.this.loader_off();
		});
	}

	// Footer - Breakpoint slider - Set
	$.WS_Form.prototype.breakpoint_set = function(key) {

		// Get framework
		var framework = $.WS_Form.settings_plugin.framework;

		// Get breakpoint ID
		var form_data_breakpoint = $.WS_Form.frameworks.types[framework]['breakpoints'][key]['id'];

		// Set new breakpoint
		$.WS_Form.this.set_object_meta_value($.WS_Form.this.form, 'breakpoint', key);

		// Set breakpoint ID in form data-breakpoint attribute
		$('#' + $.WS_Form.this.form_obj_id).attr('data-breakpoint', form_data_breakpoint);
	};

	// Footer - Breakpoint slider - Button rendering
	$.WS_Form.prototype.breakpoint_buttons = function() {

		var can_optimize = false;
		var can_reset = false;

		// Get current group
		var group_obj = $($('.wsf-group-tab.ui-tabs-active a').attr('href'));

		// Sections
		for(var object_id in this.section_data_cache) {

			var object = this.section_data_cache[object_id];

			var optimize_result = $.WS_Form.this.breakpoint_optimize_process(object, true);

			can_optimize = can_optimize || optimize_result.can_optimize;
			can_reset = can_reset || optimize_result.can_reset;
		};

		// Fields
		for(var object_id in this.field_data_cache) {

			var object = this.field_data_cache[object_id];

			var optimize_result = $.WS_Form.this.breakpoint_optimize_process(object, true);

			can_optimize = can_optimize || optimize_result.can_optimize;
			can_reset = can_reset || optimize_result.can_reset;
		};

		// Set buttons
		$('#wsf-breakpoints [data-action="wsf-optimize"]').attr('disabled', !can_optimize);
		$('#wsf-breakpoints [data-action="wsf-reset"]').attr('disabled', !can_reset);
	}


	// Data grid
	$.WS_Form.prototype.data_grid_html = function(meta_key, meta_value) {

		// Check data grid object
		if(typeof meta_value !== 'object') { return ''; }
		if(typeof meta_value.columns === 'undefined') { this.error('error_data_grid_columns'); }
		if(typeof meta_value.groups === 'undefined') { this.error('error_data_grid_groups'); }
		if(typeof meta_value.rows_per_page === 'undefined') { this.error('error_data_grid_rows_per_page'); }

		// Get columns and rows
		var columns = meta_value.columns;
		var groups = meta_value.groups;
		var rows_per_page = meta_value.rows_per_page;

		// Get meta key config
		var meta_key_config = $.WS_Form.meta_keys[meta_key];

		// Are groups enabled?
		var groups_group = meta_key_config.groups_group;

		// Conditional?
		var conditional = ((typeof meta_key_config.conditional !== 'undefined') && meta_key_config.conditional);

		// Upload / download?
		var upload_download = (typeof meta_key_config.upload_download !== 'undefined') && meta_key_config.upload_download;

		// Overrides
		if(typeof rows_per_page_override !== 'undefined') { rows_per_page = rows_per_page_override; }

		// Get counts
		var column_count = columns.length;
		var group_count = groups.length;

		var return_html = '<div class="wsf-data-grid-groups">';

		// Build group tabs
		return_html += '<div class="wsf-data-grid-group-tabs-wrapper">';

		return_html += '<ul class="wsf-data-grid-group-tabs">';

		var group_count = groups.length;
		for(var group_index in groups) {

			var group = groups[group_index];
			var group_label = this.html_encode(group.label);

			// <input type="text" value="' + this.html_encode(group_label) + '" />
			return_html += '<li class="wsf-data-grid-group-tab' + ((group_count == 1) ? ' ui-state-active' : '') + '">';
			return_html += '<a href="#wsf-data-grid-group-' + group_index + '">';
			return_html += group_label;
			return_html += '</a>';

			if(group_count > 1) {
				return_html += '<div data-action="wsf-data-grid-group-delete" title="' + this.language('data_grid_group_delete') + '">' + this.svg('close-circle') + '</div>';
			}

			return_html += '</li>';
		}

		if(groups_group) {

			// Add group
			return_html += '<li class="wsf-ui-cancel" data-action="wsf-data-grid-group-add" title="' + this.language('data_grid_group_add') + '"><div>' + this.svg('plus-circle') + '</div></li>';
		}

		return_html += "</ul>\n\n";

		// Compatibility
		if((typeof meta_key_config.compatibility_url !== 'undefined') && $.WS_Form.settings_plugin.helper_compatibility) {

			return_html += '<div class="wsf-data-grid-compatibility"><a class="wsf-compatibility" href="' + meta_key_config.compatibility_url + '" target="_blank" title="' + this.language('field_compatibility') + '" tabindex="-1">' + this.svg('markup-circle') + '</a></div>';
		}

		// Upload/download?
		if(upload_download) {

			// Upload CSV
			return_html += '<div data-action="wsf-data-grid-upload" title="' + this.language('data_grid_group_upload_csv') + '">' + this.svg('upload') + '</div>';
			return_html += '<input type="file" class="wsf-file-upload" id="wsf-data-grid-upload-file" accept=".csv"/>';

			// Download CSV
			return_html += '<div data-action="wsf-data-grid-download" title="' + this.language('data_grid_group_download_csv') + '">' + this.svg('download') + '</div>';
		}

		return_html += "</div>\n\n";

		// Build each group
		for(var group_index in groups) {

			var group = groups[group_index];
			return_html += this.data_grid_html_group(group, group_index, columns, rows_per_page, meta_key);

			if(!groups_group) { break; }
		}

		if(upload_download) {

			// Data upload-csv
			return_html += '<div class="wsf-data-grid-upload-csv-window"><div class="wsf-data-grid-upload-csv-window-content"><h1>' + this.language('data_grid_upload_csv') + '</h1><div class="wsf-uploads"></div></div></div>';
		}

		return_html += '</div>';

		return return_html;
	}

	// Data grid - Group
	$.WS_Form.prototype.data_grid_html_group = function(group, group_index, columns, rows_per_page, meta_key) {

		// Group - Options
		var meta_key_config = $.WS_Form.meta_keys[meta_key];

		// Get current page
		var page = parseInt(group.page);

		// Get row count
		if(typeof group.rows === 'undefined') { group.rows = []; }
		var rows = group.rows;
		var row_count = rows.length;

		// Support attributes
		var row_default = (typeof meta_key_config.row_default !== 'undefined') ? meta_key_config.row_default : false;
		var row_disabled = (typeof meta_key_config.row_disabled !== 'undefined') ? meta_key_config.row_disabled : false;
		var row_required = (typeof meta_key_config.row_required !== 'undefined') ? meta_key_config.row_required : false;
		var row_hidden = (typeof meta_key_config.row_hidden !== 'undefined') ? meta_key_config.row_hidden : false;

		// Group settings
		var groups_label = (typeof meta_key_config.groups_label !== 'undefined') ? meta_key_config.groups_label : true;
		var groups_label_label = (typeof meta_key_config.groups_label_label !== 'undefined') ? this.html_encode(meta_key_config.groups_label_label) : this.language('data_grid_groups_label');

		var groups_label_render = (typeof meta_key_config.groups_label_render !== 'undefined') ? meta_key_config.groups_label_render : true;
		var groups_label_render_label = (typeof meta_key_config.groups_label_render_label !== 'undefined') ? this.html_encode(meta_key_config.groups_label_render_label) : this.language('data_grid_groups_label_render');

		var groups_group = (typeof meta_key_config.groups_group !== 'undefined') ? meta_key_config.groups_group : true;
		var groups_group_label = (typeof meta_key_config.groups_group_label !== 'undefined') ? this.html_encode(meta_key_config.groups_group_label) : this.language('data_grid_groups_group');

		var groups_disabled = (typeof meta_key_config.groups_disabled !== 'undefined') ? meta_key_config.groups_disabled : true;
		var groups_auto_group = (typeof meta_key_config.groups_auto_group !== 'undefined') ? meta_key_config.groups_auto_group : false;
		var group_settings_show = groups_label || groups_auto_group || groups_group || groups_disabled;

		var rows_randomize = (typeof meta_key_config.rows_randomize !== 'undefined') ? meta_key_config.rows_randomize : false;

		// Build table
		var return_html = '<div id="wsf-data-grid-group-' + group_index + '" class="wsf-data-grid-group" data-group-index="' + group_index + "\">\n\n";

		// Table
		return_html += "<div class=\"wsf-data-grid-table-outer\"><div class=\"wsf-data-grid-table-inner\"><table><thead>\n\n";

		return_html += this.data_grid_html_row_header(columns, group_index, row_default, row_disabled, row_required, row_hidden, meta_key);

		return_html += "</thead>\n<tbody>";

		// Build each row
		for(var row_index = (page * rows_per_page); ((row_index < row_count) && ((rows_per_page == 0) || (row_index < ((page + 1) * rows_per_page)))); row_index++) {

			return_html += this.data_grid_html_row(rows[row_index], group_index, row_index, row_default, row_disabled, row_required, row_hidden, meta_key);
		}

		return_html += "\n</tbody>\n</table>";

		// Row - Add
		return_html += '</div><div data-action="wsf-data-grid-row-add" title="' + this.language('data_grid_row_add') + '"><div>' + this.svg('plus-circle') + '</div></div>';

		// Table - Outer/Inner
		return_html += "</div>\n\n";

		// Group - Pagination
		return_html += '<ul class="wsf-data-grid-pagination"></ul>';

		// Footer
		return_html += '<ul class="wsf-data-grid-footer wsf-list-inline">';
		return_html += '<li>';

		return_html += '<label class="wsf-label wsf-label-small">' + this.language('data_grid_row_bulk_actions') + '</label>';

		return_html += '<div class="wsf-field-inline">';
		return_html += "<select class=\"wsf-field wsf-field-small\" disabled>\n";
		return_html += '<option value="">' + this.language('data_grid_row_bulk_actions_select') + "</option>\n";

		// Group - Bulk action - Default
		if(row_default) {
			return_html += '<option value="default">' + this.language('data_grid_row_bulk_actions_default') + "</option>\n";
			return_html += '<option value="default_off">' + this.language('data_grid_row_bulk_actions_default_off') + "</option>\n";
		}

		// Group - Bulk action - Required
		if(row_required) {
			return_html += '<option value="required">' + this.language('data_grid_row_bulk_actions_required') + "</option>\n";
			return_html += '<option value="required_off">' + this.language('data_grid_row_bulk_actions_required_off') + "</option>\n";
		}

		// Group - Bulk action - Disabled
		if(row_disabled) {
			return_html += '<option value="disabled">' + this.language('data_grid_row_bulk_actions_disabled') + "</option>\n";
			return_html += '<option value="disabled_off">' + this.language('data_grid_row_bulk_actions_disabled_off') + "</option>\n";
		}

		// Group - Bulk action - Hidden
		if(row_hidden) {
			return_html += '<option value="hidden">' + this.language('data_grid_row_bulk_actions_hidden') + "</option>\n";
			return_html += '<option value="hidden_off">' + this.language('data_grid_row_bulk_actions_hidden_off') + "</option>\n";
		}

		// Group - Bulk action - Delete
		return_html += '<option value="delete">' + this.language('data_grid_row_bulk_actions_delete') + "</option>\n";

		return_html += '</select>';
		return_html += '<button class="wsf-button wsf-button-primary wsf-button-small" data-action="wsf-data-grid-bulk-action" disabled>' + this.language('data_grid_row_bulk_actions_apply') + "</button>\n\n";
		return_html += '</div>'

		return_html += '</li>'

		// Data grid - Rows per page
		return_html += '<li>';
		return_html += '<label class="wsf-label wsf-label-small" for="wsf-data-grid-rows-per-page-' + group_index + '">' + this.language('data_grid_rows_per_page') + '</label>';
		return_html += '<div class="wsf-field-inline">';
		return_html += "<select class=\"wsf-field wsf-field-small\">\n";
		return_html += "<option value=\"0\">" + this.language('data_grid_rows_per_page_0') + "</option>\n";

		// Render rows per page options
		var rows_per_page_options = $.WS_Form.settings_form.data_grid.rows_per_page_options;

		for(var key in rows_per_page_options) {

			return_html += "<option value=\"" + key + "\"" + (key == rows_per_page ? ' selected' : '') + ">" + this.html_encode(rows_per_page_options[key]) + "</option>\n";
		}

		return_html += "</select>";
		return_html += '<button class=\"wsf-button wsf-button-primary wsf-button-small\" data-action="wsf-data-grid-rows-per-page" disabled>' + this.language('data_grid_rows_per_page_apply') + "</button>\n\n";
		return_html += '</div>'

		return_html += '</li>'

		if(group_settings_show) {

			// Group - Settings - Open
			return_html += '<li><div data-action="wsf-data-grid-settings" title="' + this.language('data_grid_settings') + '">' + this.svg('settings') + '</div></li>';
		}

		return_html += '</ul>'

		if(group_settings_show) {

			return_html += '<div class="wsf-data-grid-settings-wrapper">';

			return_html += '<div class="wsf-data-grid-settings">';

			// Group - Settings - Label
			if(groups_label) {

				return_html += '<label for="wsf-data-grid-group-label-' + group_index + '" class="wsf-label">' + groups_label_label + "</label>\n";
				return_html += '<input type="text" id="wsf-field wsf-data-grid-group-label-' + group_index + '" class="wsf-field" data-text="label" value="' + this.html_encode(group.label) + "\" maxlength=\"190\" />\n";
			}

			// Group - Settings - Group (optgroup / fieldset)
			if(groups_group) {

				return_html += '<input type="checkbox" id="wsf-data-grid-mask_group-' + group_index + '" class="wsf-field" data-children="wsf-data-grid-groups-group-' + group_index + '" data-checkbox="mask_group"' + (group.mask_group ? ' checked' : '') + " />\n";
				return_html += '<label for="wsf-data-grid-mask_group-' + group_index + '" class="wsf-label">' + groups_group_label + "</label>\n";
			}

			return_html += '<div id="wsf-data-grid-groups-group-' + group_index + '" class="wsf-field-indent">';

			// Group - Settings - Label - Render
			if(groups_label && groups_label_render) {

				return_html += '<input type="checkbox" id="wsf-data-grid-group-label-render-' + group_index + '" class="wsf-field" data-checkbox="label_render"' + (group.label_render ? ' checked' : '') + " />\n";
				return_html += '<label for="wsf-data-grid-group-label-render-' + group_index + '" class="wsf-label">' + groups_label_render_label + "</label>\n";
			}

			// Group - Settings - Disabled
			if(groups_disabled) {

				return_html += '<input type="checkbox" id="wsf-data-grid-disabled-' + group_index + '" class="wsf-field" data-checkbox="disabled"' + (group.disabled ? ' checked' : '') + " />\n";
				return_html += '<label for="wsf-data-grid-disabled-' + group_index + '" class="wsf-label">' + this.language('data_grid_group_disabled') + "</label>\n";
			}

			return_html += '</div>';

			// Group - Settings - Auto Group
			if(groups_auto_group) {

				return_html += '<label for="wsf-data-grid-auto-group-' + group_index + '" class="wsf-label">' + this.language('data_grid_group_auto_group') + "</label>\n";
				return_html += "<select class=\"wsf-field\" data-action=\"wsf-data-grid-auto-group\"></select>\n";
			}

			// /Settings
			return_html += '</div>';

			// /Settings wrapper
			return_html += '</div>';
		}

		// /Group
		return_html += "</div>\n\n";

		return return_html;
	}

	// Data grid - Header row
	$.WS_Form.prototype.data_grid_html_row_header = function(columns, group_index, row_default, row_disabled, row_required, row_hidden, meta_key) {

		// Get meta key config
		var meta_key_config = $.WS_Form.meta_keys[meta_key];

		// Config
		var max_columns = ((typeof meta_key_config.max_columns !== 'undefined') ? meta_key_config.max_columns : 0);
		var type_sub = (typeof meta_key_config.type_sub !== 'undefined') ? meta_key_config.type_sub : false;

		// Build data grid header row HTML
		var return_html = '<tr>'

		// Row ID
		var row_id = 'wsf-data-grid-bulk-' + meta_key + '-' + group_index;

		// Spacer - Sort
		return_html += '<th data-fixed-sort class="wsf-data-grid-icon"></th>';

		// Spacer - Bulk action select all
		return_html += '<th data-fixed-select class="wsf-data-grid-checkbox"><input id="' + row_id + '" class="wsf-field" data-action="wsf-data-grid-row-select-all" type="checkbox" /><label for="' + row_id + '" class="wsf-label"></label></th>'

		var column_count = 0;
		for(var key in columns) {

			var column = columns[key];
			var column_id = column.id;
			var column_name = column.label;

			return_html += '<th><input type="text" class="wsf-field wsf-field-small" data-id="' + column_id + '" data-column="' + key + '" data-action="wsf-data-grid-column-label" value="' + this.html_encode(column_name) + '" />';

			if((columns.length > 0) && ((max_columns > 1) || (max_columns == 0))) {

				return_html += '<div data-action="wsf-data-grid-column-delete" data-id="' + column_id + '" title="' + this.language('data_grid_column_delete') + '">' + this.svg('close-circle') + '</div>';
			}

			return_html += '</th>';

			// Max columns
			column_count++;
			if((max_columns > 0) && (column_count == max_columns)) { break; }
		}

		// Supported attributes
		if(type_sub !== false) { return_html += '<th data-fixed-icon></th>'; }
		if(row_default) { return_html += '<th data-fixed-icon></th>'; }
		if(row_required) { return_html += '<th data-fixed-icon></th>'; }
		if(row_disabled) { return_html += '<th data-fixed-icon></th>'; }
		if(row_hidden) { return_html += '<th data-fixed-icon></th>'; }

		// Sub type edit
		if(type_sub !== false) {

			return_html += '<th data-fixed-icon></th>';
		}

		if((max_columns == 0) || (columns.length < max_columns)) {

			// Spacer - Add column
			return_html += '<th data-fixed-icon><div data-action="wsf-data-grid-column-add" title="' + this.language('data_grid_column_add') + '">' + this.svg('plus-circle') + '</div></th>';
		}

		return_html += "</tr>\n\n";

		return return_html;
	}

	// Data grid - Row
	$.WS_Form.prototype.data_grid_html_row = function(row, group_index, row_index, row_default, row_disabled, row_required, row_hidden, meta_key) {

		// Get meta key config
		var meta_key_config = $.WS_Form.meta_keys[meta_key];

		// Config
		var max_columns = ((typeof meta_key_config.max_columns !== 'undefined') ? meta_key_config.max_columns : 0);
		var type_sub = (typeof meta_key_config.type_sub !== 'undefined') ? meta_key_config.type_sub : false;

		var conditional = ((typeof meta_key_config.conditional !== 'undefined') && meta_key_config.conditional);

		// Read row data
		if(typeof(row.data) === 'undefined') { return ''; }
		var data = row.data;

		// Get column count
		if(typeof(data.length) === 'undefined') { return ''; }
		var column_count = data.length;

		// Build data grid row HTML
		var return_html = '<tr>';

		var row_id = 'wsf-data-grid-bulk-' + meta_key + '-' + group_index + '-' + row_index;

		// Sort
		return_html += '<td data-fixed-sort><div data-action="wsf-data-grid-row-sort" title="' + this.language('data_grid_row_sort') + '">' + this.svg('sort') + '</div></td>'

		// Selector
		return_html += '<td data-fixed-select><input data-action="wsf-data-grid-row-select" id="' + row_id + '" class="wsf-field" type="checkbox" tabindex="-1" /><label for="' + row_id + '" class="wsf-label"></label></td>'

		// Build each column
		for(var column_index = 0; column_index < column_count; column_index++) {

			var column_value = data[column_index];
			return_html += '<td><input class="wsf-field wsf-field-small" type="text" data-column="' + column_index + '" value="' + this.html_encode(column_value) + '" /></td>';

			// Max columns
			if((max_columns > 0) && (column_index == (max_columns - 1))) { break; }
		}

		// Sub type edit
		if(type_sub !== false) {

			return_html += '<td data-fixed-icon><div data-action="wsf-data-grid-' + type_sub + '-edit" title="' + this.language('data_grid_' + type_sub + '_edit') + '">' + this.svg('edit') + '</div></td>';
		}

		// Supported attributes
		if(row_default) { return_html += '<td data-fixed-icon><div data-attribute="default" data-status="' + (row.default ? 'on': '') + '" title="' + this.language('data_grid_row_default') + '">' + this.svg('check') + '</div></td>'; }
		if(row_required) { return_html += '<td data-fixed-icon><div data-attribute="required" data-status="' + (row.required ? 'on': '') + '" title="' + this.language('data_grid_row_required') + '">' + this.svg('asterisk') + '</div></td>'; }
		if(row_disabled) { return_html += '<td data-fixed-icon><div data-attribute="disabled" data-status="' + (row.disabled ? 'on': '') + '" title="' + this.language('data_grid_row_disabled') + '">' + this.svg('disabled') + '</div></td>'; }
		if(row_hidden) { return_html += '<td data-fixed-icon><div data-attribute="hidden" data-status="' + (row.hidden ? 'on': '') + '" title="' + this.language('data_grid_row_hidden') + '">' + this.svg('visible') + this.svg('hidden') + '</div></td>'; }

		// Delete
		return_html += '<td data-fixed-icon><div data-action="wsf-data-grid-row-delete" title="' + this.language('data_grid_row_delete') + '">' + this.svg('minus-circle') + '</div></td>';

		return_html += "</tr>\n";

		return return_html;
	}

	// Data grids - Init
	$.WS_Form.prototype.sidebar_data_grids_init = function(obj) {

		// Init data grids
		$('.wsf-data-grid:not(.wsf-data-grid-initialized)', obj).each(function(i, e) {

			$.WS_Form.this.data_grid_init($(this), e);
		});
	}

	// Data grid - Settings - Children
	$.WS_Form.prototype.data_grid_settings_children = function(data_grid_obj, obj) {

		// Meta value
		var this_meta_value = obj.is(':checked');

		// Children
		var children = obj.attr('data-children');
		if(children) {

			var children_array = children.split(',');

			for(var child_index in children_array) {

				var child = children_array[child_index];

				if(this_meta_value) {

					$('#' + child, data_grid_obj).show();

				} else {

					$('#' + child, data_grid_obj).hide();
				}
			}
		}
	}

	// Data grid - Init
	$.WS_Form.prototype.data_grid_init = function(obj, element) {

		element.render = function() {

			// Get data grid attributes
			var object = obj.attr('data-object');
			var object_id = obj.attr('data-id');
			var meta_key = obj.attr('data-meta-key');
			var meta_key_config = $.WS_Form.meta_keys[meta_key];
			var meta_key_type_sub = (typeof(meta_key_config['type_sub']) !== 'undefined') ? meta_key_config['type_sub'] : false;

			// Get object data
			var object_data = $.WS_Form.this.get_object_data(object, object_id, true);
			if(object_data === false) { $.WS_Form.this.error('error_object_data'); }

			// Get latest current data grid data
			var meta_value = $.WS_Form.this.get_object_meta_value(object_data, meta_key, '', true);

			// If data is empty, we'll create empty data
			if(meta_value === '') {

				var meta_value = meta_key_config.default;
				$.WS_Form.this.set_object_meta_value(object_data, meta_key, meta_value);
			}

			// Render HTML
			obj.html($.WS_Form.this.data_grid_html(meta_key, meta_value));

			var render_redo = false;

			// Auto group
			$('[data-action="wsf-data-grid-auto-group"]').change(function() {

				var auto_group_index = $(this).val();

				// Get group_index
				var group_index = $(this).closest('.wsf-data-grid-group').attr('data-group-index');

				// Get meta data (Deep clone)
				var group = ($.extend(true, {}, meta_value.groups[group_index]));

				// Get rows
				var rows = group.rows;

				// Create new groups data
				var groups_new = [];
				for(var row_index in rows) {

					// Get label
					var group_label = rows[row_index]['data'][auto_group_index];
					if(typeof group_label == 'undefined') { group_label = ''; }

					// See if this exists, if not create key
					if(typeof groups_new[group_label] === 'undefined') {

						groups_new[group_label] = $.extend(true, {}, group);
						groups_new[group_label].label = (group_label == '') ? $.WS_Form.this.language('data_grid_group_label_default') : group_label;
						groups_new[group_label].rows = [];
						groups_new[group_label].mask_group = 'on';
						groups_new[group_label].page = '0';
						groups_new[group_label].disabled = '';
					}

					// Add row to group
					groups_new[group_label].rows.push(rows[row_index]);
				}

				// Move to meta_value
				meta_value.groups = [];
				for(var group_key in groups_new) {

					meta_value.groups.push(groups_new[group_key]);
				}

				// Sort each group by label
				meta_value.groups.sort(function(a, b) {

					return (a.label === b.label) ? true : ((a.label < b.label) ? -1 : 1);
				});

				// Reset group index
				meta_value.group_index = 0;

				// Render
				element.render();
			});

			// Calculate width of icon and delete columns combined and set that as padding right on table
			$('table', obj).each(function() {

				var th_icon_icons_width = 0;
				var th_icons = $('th[data-fixed-icon]', $(this));
				for(var th_icon_index = 0; th_icon_index < th_icons.length; th_icon_index++) {

					th_icon_icons_width += $(th_icons[th_icon_index]).outerWidth();
				}

				var table_padding_right = th_icon_icons_width;

				$(this).css({'padding-right': table_padding_right + 'px'});
			});

			// Drag enter
			$('.wsf-data-grid-group', obj).on('dragenter', function (e) {

				e.stopPropagation();
				e.preventDefault();
				$('.wsf-data-grid-upload-csv-window', obj).fadeIn(200);
			});

			// Drag over
			$('.wsf-data-grid-upload-csv-window', obj).on('dragover', function (e) {

				e.stopPropagation();
				e.preventDefault();
			});

			// Drop
			$('.wsf-data-grid-upload-csv-window', obj).on('drop', function (e) {

				e.preventDefault();

				var files = e.originalEvent.dataTransfer.files;
				$.WS_Form.this.data_grid_upload_csv(object, object_id, meta_key, files, $(this));
			});

			// Drag leave
			$('.wsf-data-grid-upload-csv-window', obj).on('dragleave', function (e) {

				$('.wsf-data-grid-upload-csv-window', obj).fadeOut(200);
			});

			// Upload
			$('[data-action="wsf-data-grid-upload"]', obj).click(function() {

				// Click file input
				$('input[id="wsf-data-grid-upload-file"]', obj).click();
			});
			$('input[id="wsf-data-grid-upload-file"]', obj).change(function() {

				var files = $('input[id="wsf-data-grid-upload-file"]', obj).prop("files");

				if(files.length > 0) {

					var data_grid_upload_csv_window = $('.wsf-data-grid-upload-csv-window', obj);
					data_grid_upload_csv_window.fadeIn(200);
					$.WS_Form.this.data_grid_upload_csv(object, object_id, meta_key, files, data_grid_upload_csv_window);
				}
			});

			// Download
			$('[data-action="wsf-data-grid-download"]', obj).click(function() {

				// Get current group object
				var group_obj = $($('.wsf-data-grid-group-tabs .ui-tabs-active').find('a').attr('href'), obj);

				// Get group index
				var group_index = group_obj.attr('data-group-index');

				// Initiate file download
				$.WS_Form.this.data_grid_download_csv(object, object_id, meta_key, group_index);
			});

			// Condition - Edit
			$('[data-action="wsf-data-grid-conditional-edit"]', obj).click(function() {

				$.WS_Form.this.data_grid_row_open($(this), 'conditional', meta_value);
			});

			// Action - Edit
			$('[data-action="wsf-data-grid-action-edit"]', obj).click(function() {

				$.WS_Form.this.data_grid_row_open($(this), 'action', meta_value);
			});

			// Attributes
			$('[data-attribute]', obj).click(function() {

				// Read attribute data
				var attribute = $(this).attr('data-attribute');

				// Get group
				var group_index = $(this).closest('.wsf-data-grid-group').attr('data-group-index');
				var group = meta_value.groups[group_index];

				var rows = group.rows;

				var attribute_on = ($(this).attr('data-status') == 'on');

				// Determine whether you can multi-select default values
				var multiple = (obj.closest('.wsf-sidebar').find('[data-meta-key="multiple"]:checked').length == 1);
				switch(attribute) {

					case 'default' :

						var row_default_multiple = (typeof meta_key_config.row_default_multiple !== 'undefined') ? meta_key_config.row_default_multiple : false;
						multiple = (multiple || row_default_multiple);
						break;

					case 'required' :

						var row_required_multiple = (typeof meta_key_config.row_required_multiple !== 'undefined') ? meta_key_config.row_required_multiple : false;
						multiple = (multiple || row_required_multiple);
						break;

					case 'disabled' :
					case 'hidden' :

						multiple = true;
						break;
				}

				// If this is a default or required attribute and you cannot select multiple, then clear current
				if(!multiple) {

					// CSS change
					$('.wsf-data-grid-group [data-attribute="' + attribute + '"]', $(this).closest('.wsf-data-grid-groups')).attr('data-status', '');

					// Data change
					for(var group_all_index in meta_value.groups) {

						var group_all = meta_value.groups[group_all_index];
						var rows_all = group_all.rows;

						// Data change
						for(var row_all_index in rows_all) {

							rows_all[row_all_index][attribute] = '';
						}
					}
				}

				// CSS change
				if(attribute_on) {

					$(this).attr('data-status', '');
					var attribute_value = '';

				} else {

					$(this).attr('data-status', 'on');
					var attribute_value = 'on';
				}

				// Get row offset
				var page = group.page;
				var rows_per_page = meta_value.rows_per_page;
				var row_offset = (page * rows_per_page);

				// Get data position
				var row_index = row_offset + ($(this).closest('tr').index());

				// Data change
				rows[row_index][attribute] = attribute_value;
			});

			// Options
			$('[data-action="wsf-data-grid-settings"]', obj).click(function() {

				// Get options div
				var options_wrapper = $('.wsf-data-grid-settings-wrapper', obj);
				var options = $('.wsf-data-grid-settings', obj);

				// Determine if options is visible or not
				var visible = options.is(':visible');

				// Animate
				if(visible) {

					// Hide
					$(this).removeClass('wsf-editing');
					options_wrapper.removeClass('wsf-data-grid-settings-open');
					options.slideUp();

				} else {

					// Show
					$(this).addClass('wsf-editing');
					options_wrapper.addClass('wsf-data-grid-settings-open');
					options.slideDown();
				}
			});

			// Options - Checkboxes - Init
			$('input[data-checkbox][data-children]', obj).each(function() {

				$.WS_Form.this.data_grid_settings_children(obj, $(this));
			});

			// Options - Checkboxes
			$('input[data-checkbox]', obj).change(function() {

				// Get group_index
				var group_index = $(this).closest('.wsf-data-grid-group').attr('data-group-index');

				// Get meta data
				var group = meta_value.groups[group_index];

				// Meta key
				var this_meta_key = $(this).attr('data-checkbox');

				// Meta value
				var this_meta_value = $(this).is(':checked');

				// Set value
				group[this_meta_key] = this_meta_value ? 'on' : '';

				// Children
				$.WS_Form.this.data_grid_settings_children(obj, $(this));
			});

			// Options - Text
			$('input[data-text]', obj).keyup(function() {

				// Get group_index
				var group_index = $(this).closest('.wsf-data-grid-group').attr('data-group-index');

				// Get meta data
				var group = meta_value.groups[group_index];

				// Meta key
				var this_meta_key = $(this).attr('data-text');

				// Meta value
				var this_meta_value = ($(this).val() != '') ? $(this).val() : $.WS_Form.this.language('data_grid_group_label_default');

				// Set value
				group[this_meta_key] = this_meta_value;

				// Update tab copy
				$('.wsf-data-grid-group-tab a[href="#wsf-data-grid-group-' + group_index + '"], .wsf-data-grid-group-tab span', obj).html($.WS_Form.this.html_encode(this_meta_value));
			});

			// Options - Rows per page
			$('[data-action="wsf-data-grid-rows-per-page"]', obj).click(function() {

				// Get rows per page
				var rows_per_page = parseInt($(this).siblings('select').first().val());

				// Save rows per page
				$.WS_Form.this.data_grid_group_rows_per_page_set(meta_value, rows_per_page, object, object_id, meta_key, function() {

					// Refresh data grid
					element.render();
				});
			});
			$('[data-action="wsf-data-grid-rows-per-page"]', obj).each(function() {

				$(this).siblings('select').first().change(function() {

					$('[data-action="wsf-data-grid-rows-per-page"]', obj).prop('disabled', false);
				});
			});

			// Pagination
			$('.wsf-data-grid-pagination', obj).each(function() {

				// Get group_index
				var group_index = $(this).closest('.wsf-data-grid-group').attr('data-group-index');

				// Get meta data
				var group = meta_value.groups[group_index];
				var rows_per_page = meta_value.rows_per_page;
				var page = (rows_per_page == 0) ? 0 : parseInt(group.page);
				var rows = group.rows;
				var row_offset = (page * rows_per_page);
				var row_count = rows.length;
				var pagination_reach = 3;

				// Total pages
				var pages = (rows_per_page == 0) ? 1 : Math.ceil(row_count / rows_per_page);

				// If current page is beyond last page, set page to last page
				if(((page * rows_per_page) >= row_count) && (row_count > 0)) {

					// Go to the last page
					page = (pages - 1);

					// Save new page
					$.WS_Form.this.data_grid_group_page_set(group, group_index, page, object, object_id, meta_key, function() {

						// Refresh data grid
						render_redo = true;
					});
				}

				if(pages > 1) {

					// Calculate page positions for navigation elements
					var page_first = 0;
					var page_last = (pages - 1);
					var page_previous = (page > 0) ? (page - 1) : page;
					var page_next = (page < page_last) ? (page + 1) : page;
					var page_previous_disabled = ((page == 0) ? ' class="disabled"' : '');
					var page_next_disabled = ((page == page_last) ? ' class="disabled"' : '');

					// Previous
					var pagination_html = '<li data-page="' + page_first + '"' + page_previous_disabled + '><div>' + $.WS_Form.this.svg('first') + '</div></li>';
					pagination_html += '<li data-page="' + page_previous + '"' + page_previous_disabled + '><div>' + $.WS_Form.this.svg('previous') + '</div></li>';

					// Pagination start and end (Goes as far as pagination reach)
					var page_index_start = page - pagination_reach;
					if(page_index_start < 0) { page_index_start = 0; }
					var page_index_end = page + pagination_reach;
					if(page_index_end > page_last) { page_index_end = page_last; }

					// Pagination
					for(var page_index = page_index_start; page_index < (page_index_end + 1); page_index++) {

						pagination_html += '<li' + ((page_index == page) ? ' class="active"' : '') + ' data-page="' + page_index + '"><div>' + (page_index + 1) + '</div></li>';
					}

					// Next
					pagination_html += '<li data-page="' + page_next + '"' + page_next_disabled + '><div>' + $.WS_Form.this.svg('next') + '</div></li>';
					pagination_html += '<li data-page="' + page_last + '"' + page_next_disabled + '><div>' + $.WS_Form.this.svg('last') + '</div></li>';

					// Inject pagination
					$(this).prepend(pagination_html);

					// Pagination events
					$('li[data-page]', $(this)).click(function() {

						// Get page
						var page_new = $(this).attr('data-page');
						if(page != page_new) {

							// Save new page
							$.WS_Form.this.data_grid_group_page_set(group, group_index, page_new, object, object_id, meta_key, function() {

								// Refresh data grid
								element.render();
							});
						}
					});

				} else {

					// Remove page selector, only 1 page
					$(this).remove();
				}
			});

			// Redo render because pages changed
			if(render_redo) { element.render(); return false; }

			// Group tabs
			var meta_key_config = $.WS_Form.meta_keys[meta_key];
			var groups_group = meta_key_config.groups_group;

			if(groups_group) {

				var group_index = meta_value.group_index;
				$('.wsf-data-grid-groups', obj).tabs({

					active: group_index,

					activate: function(event, ui) {

						// Refresh sortable positions (to ensure li helpers vertical positioning is correct)
						$('table', obj).sortable('refreshPositions');

						// Get new group index
						var group_index_new = ui.newTab.index()

						// Save new group index to scratch
						meta_value.group_index = group_index_new;

						// Get object data of original field
						var object_data_old = $.WS_Form.this.get_object_data(object, object_id);
						if(object_data_old === false) { $.WS_Form.this.error('error_object_data'); }

						// Get data grid data of original field
						var meta_value_old = $.WS_Form.this.get_object_meta_value(object_data_old, meta_key, false);
						if(meta_value_old === false) { $.WS_Form.this.error('error_object_meta_value'); }

						if(typeof meta_value_old.groups[group_index_new] !== 'undefined') {

							// Loader on
							$.WS_Form.this.loader_on();

							// Save new group index to original field
							meta_value_old.group_index = group_index_new;

							// Build parameters
							var params = {};

							// Form ID
							params['form_id'] = $.WS_Form.this.form_id;

							// Object data
							params[object] = object_data_old;
							params[object]['history_suppress'] = 'on';

							// Call AJAX request
							$.WS_Form.this.api_call(object + '/' + object_id, 'PUT', params, function(response) {

								// Loader off
								$.WS_Form.this.loader_off();
							});
						}
					}
				});

				// Group tabs - Sortable
				$('.wsf-data-grid-group-tabs', obj).sortable({

					cursor:				'move',
					tolerance:			'pointer',
					containment: 		'parent',
					scroll: 			false,
					forceHelperSize:	true,
					revert:				200,
					placeholder:		'wsf-data-grid-group-tab-placeholder',
					cancel:				'.wsf-ui-cancel',
					items:				'li:not(.wsf-ui-cancel)',

					start: function(event, ui) {

						// Get index being dragged
						$.WS_Form.data_grid_group_tab_index_dragged_from = ui.helper.index();

						var height = ui.helper.height();
						var width = ui.helper.width();
						var styles = [
						'height: ' + height + 'px',
						'width: ' + width + 'px'
						].join(';');

						ui.placeholder.attr('style', styles);
					},

					stop: function(event, ui) {

						// Get meta value
						var meta_value = $.WS_Form.this.get_object_meta_value(object_data, meta_key);
						if(meta_value === false) { $.WS_Form.this.error('error_object_meta_value'); }

						// Get groups
						var groups = meta_value.groups;

						// Get index dragged to
						var group_index_old = $.WS_Form.data_grid_group_tab_index_dragged_from;
						var group_index_new = ui.item.index();

						// Move meta data index
						if (group_index_new >= groups.length) {

							var k = group_index_new - groups.length;
							while ((k--) + 1) {
								groups.push(undefined);
							}
						}
						groups.splice(group_index_new, 0, groups.splice(group_index_old, 1)[0]);
					}
				});

				// Group tabs - Add
				$('[data-action="wsf-data-grid-group-add"]', obj).click(function() {

					if(typeof meta_value.groups[0] !== 'undefined') {

						// Build new group (Deep clone)
						var group_new = $.extend(true, {}, meta_value.groups[0]);

						// No rows for a new group
						group_new.rows = [];

						// Default label
						group_new.label = $.WS_Form.this.language('data_grid_group_label_default');

						// Add group
						meta_value.groups.push(group_new);

						// Get group count and set group_index to last
						meta_value.group_index = meta_value.groups.length - 1;

						// Refresh data grid
						element.render();

					} else {

						$.WS_Form.this.error('error_data_grid_default_group');
					}
				});

				// Group tabs - Delete
				$('[data-action="wsf-data-grid-group-delete"]', obj).click(function() {

					var group_obj = $(this).closest('li');
					var group_index = group_obj.index();

					// Get groups
					var groups = meta_value.groups;

					// Buttons
					var buttons = [

					{label:$.WS_Form.this.language('cancel'), action:'wsf-cancel'},
					{label:$.WS_Form.this.language('delete'), action:'wsf-confirm', class:'wsf-button-danger'}
					];

					// Call popover
					$.WS_Form.this.popover($.WS_Form.this.language('data_grid_group_delete_confirm'), buttons, group_obj, function() {

						// Delete row
						delete groups[group_index];

						// Remove empty elements after delete
						groups = $.grep(groups,function(n){ return n == 0 || n });

						// Reset indexes
						var groups_temp = [];
						for (var group_key in groups) {

							groups_temp.push(groups[group_key]);
						}
						meta_value.groups = groups_temp;	// Write back to meta_value

						// Refresh data grid
						element.render();
					});
				});
			}

			// Columns - Add
			$('[data-action="wsf-data-grid-column-add"]', obj).click(function() {

				// Get columns
				var columns = meta_value.columns;

				// Get highest column id
				var id_new = 0;
				for(var key in columns) { if(columns[key].id > id_new) { id_new = columns[key].id; } }
					id_new++;

				// Build new column
				var column_new = {

					'id':		id_new,
					'label':	$.WS_Form.this.language('data_grid_column_label_default')
				}

				// Add column
				meta_value.columns.push(column_new);

				// Add column to rows
				var groups = meta_value.groups;

				for(var group_key in groups) {

					var group = groups[group_key];

					var rows = group.rows;

					for(var row_key in rows) {

						var row = rows[row_key];

						row.data.push('');
					}
				}

				// Update data mask fields
				$.WS_Form.this.data_grid_update_mask_row_lookups(object, object_id, meta_key);

				// Render
				element.render();
			});

			// Columns - Delete
			$('[data-action="wsf-data-grid-column-delete"]', obj).click(function() {

				// Get column ID to delete
				var column_obj = $(this).closest('th');
				var column_id = $(this).attr('data-id');

				// Buttons
				var buttons = [

				{label:$.WS_Form.this.language('cancel'), action:'wsf-cancel'},
				{label:$.WS_Form.this.language('delete'), action:'wsf-confirm', class:'wsf-button-danger'}
				];

				// Call popover
				$.WS_Form.this.popover($.WS_Form.this.language('data_grid_column_delete_confirm'), buttons, column_obj, function() {

					// Get columns
					var columns = meta_value.columns;

					// Find key to delete
					for(var column_key in columns) {

						if(parseInt(columns[column_key].id) == parseInt(column_id)) {

							delete columns[column_key];

							// Remove empty elements after delete
							meta_value.columns = $.grep(meta_value.columns,function(n){ return n == 0 || n });

							break;
						}
					}

					// Delete column from groups and rows
					if(column_key !== false) {

						var groups = meta_value.groups;

						for(var group_key in groups) {

							var group = groups[group_key];

							var rows = group.rows;

							for(var row_key in rows) {

								var row = rows[row_key];

								delete row.data[column_key];

								// Remove empty elements after delete
								row.data = $.grep(row.data,function(n){ return n == 0 || n });
							}
						}
					}

					// Update data mask fields
					$.WS_Form.this.data_grid_update_mask_row_lookups(object, object_id, meta_key);

					// Render
					element.render();
				});
			});

			// Column - Name
			$('[data-action="wsf-data-grid-column-label"]', obj).keyup(function(e) {

				// Get column index
				var column_index = $(this).attr('data-column');
				var column = meta_value.columns[column_index];

				// Meta value
				var label = ($(this).val() != '') ? $(this).val() : $.WS_Form.this.language('data_grid_column_label_default');

				// Update column label
				meta_value.columns[column_index].label = label;

				// Update data mask fields
				$.WS_Form.this.data_grid_update_mask_row_lookups(object, object_id, meta_key);
			});

			// Rows - Input changes
			$('td input[type="text"]', obj).keyup(function(e) {

				// Enter key
				if (e.keyCode == 13) {

					var inputs = $('input[type="text"]', obj);
					var idx = inputs.index(this);
					if(typeof(inputs[idx + 1]) !== 'undefined') { inputs[idx + 1].focus(); }
					return false;
				}

				// Get group
				var group_index = $(this).closest('.wsf-data-grid-group').attr('data-group-index');
				var group = meta_value.groups[group_index];

				// Get row offset
				var page = group.page;
				var rows_per_page = meta_value.rows_per_page;
				var row_offset = (page * rows_per_page);

				// Get data position
				var row_index = row_offset + ($(this).closest('tr').index());
				var column_index = $(this).attr('data-column');

				// Update row data
				var rows = group.rows;
				rows[row_index]['data'][column_index] = $(this).val();
			});

			// Rows - Sortable
			$('table', obj).sortable({

				items: 'tbody tr',
				containment: 'parent',
				cursor: 'move',
				tolerance: 'pointer',
				handle: '[data-action="wsf-data-grid-row-sort"]',
				axis: 'y',
				cancel: '.wsf-ui-cancel, input[type=text]:not([readonly])',

				start: function(event, ui) {


					// Refresh sortable positions (to ensure li helpers vertical positioning is correct)
					$('table', obj).sortable('refreshPositions');

					// Get index being dragged
					$.WS_Form.data_grid_index_dragged_from = (ui.helper.index());
				},

				stop: function(event, ui) {

					// Get meta value
					var meta_value = $.WS_Form.this.get_object_meta_value(object_data, meta_key);
					if(meta_value === false) { $.WS_Form.this.error('error_object_meta_value'); }

					// Get group
					var group_index = $(this).closest('.wsf-data-grid-group').attr('data-group-index');
					var group = meta_value.groups[group_index];

					// Get row offset
					var page = group.page;
					var rows_per_page = meta_value.rows_per_page;
					var row_offset = (page * rows_per_page);

					// Get index dragged to
					var row_index_old = row_offset + $.WS_Form.data_grid_index_dragged_from;
					var row_index_new = row_offset + (ui.item.index());

					// Move meta data index
					var rows = group.rows;
					if (row_index_new >= rows.length) {

						var k = row_index_new - rows.length;

						while ((k--) + 1) {
							rows.push(undefined);
						}
					}
					rows.splice(row_index_new, 0, rows.splice(row_index_old, 1)[0]);
				}
			});

			// Rows - Add
			$('[data-action="wsf-data-grid-row-add"] div', obj).click(function() {

				// Get group
				var group_index = $(this).closest('.wsf-data-grid-group').attr('data-group-index');
				var group = meta_value.groups[group_index];
				var rows_per_page = meta_value.rows_per_page;
				var page = (rows_per_page == 0) ? 0 : group.page;

				// Get columns
				var columns = meta_value.columns;

				// Get number of columns
				var column_count = columns.length;

				// Create blank row
				var rows = group['rows']

				// New data
				switch(meta_key_type_sub) {

					case 'conditional' :

						var data = [$.WS_Form.this.language('conditional_label_default'), JSON.stringify($.WS_Form.this.conditional_new())];
						break;

					case 'action' :

						var data = [$.WS_Form.this.language('action_label_default'), JSON.stringify($.WS_Form.this.action_new())];
						break;

					default :

						var data = new Array(column_count).join('.').split('.');
				}

				var row = {

					'id':		$.WS_Form.this.data_grid_row_next_id(meta_value),
					'default':	'',
					'disabled': '',
					'required': '',
					'hidden':	'',
					'data':	data
				}

				// Push to data
				rows.push(row);
				var row_index = rows.length;

				// Get last page index
				var pages = (rows_per_page == 0) ? 1 : Math.ceil(row_index / rows_per_page);
				var page_last = (pages - 1);

				// If last page is not the current page, go to the last page
				if(page != page_last) {

					// Save new page
					$.WS_Form.this.data_grid_group_page_set(group, group_index, page_last, object, object_id, meta_key, function() {

						// Refresh data grid
						element.render();
					});

				} else {

					// Refresh data grid
					element.render();
				}

				// Open row
				if(meta_key_type_sub !== false) {

					// Get the newly added edit icon
					var obj = $('[data-action="wsf-data-grid-' + meta_key_type_sub + '-edit"]', obj).last();

					// Open edit row
					$.WS_Form.this.data_grid_row_open(obj, meta_key_type_sub, meta_value);
				}
			});

			// Rows - Delete
			$('[data-action="wsf-data-grid-row-delete"]', obj).click(function() {

				// Get row object
				var row_obj = $(this).closest('tr');

				// Get group
				var group_index = row_obj.closest('.wsf-data-grid-group').attr('data-group-index');
				var group = meta_value.groups[group_index];

				// Get row offset
				var page = (rows_per_page == 0) ? 0 : group.page;
				var rows_per_page = meta_value.rows_per_page;
				var row_offset = (page * rows_per_page);
				var row_index = row_offset + row_obj.index();

				// Delete row
				delete group.rows[row_index];

				// Remove empty elements after delete
				group.rows = $.grep(group.rows,function(n){ return n == 0 || n });

				// Reset indexes
				var rows_temp = [];
				for (var row_key in group.rows) {

					rows_temp.push(group.rows[row_key]);
				}
				group.rows = rows_temp;	// Write back to meta_value

				// Refresh data grid
				element.render();
			});

			// Bulk action
			$('[data-action="wsf-data-grid-bulk-action"]', obj).click(function() {

				// Get bulk action
				var bulk_action = $(this).siblings('select').first().val();

				// Get bulk action rows
				var bulk_action_rows = $(this).closest('.wsf-data-grid-group').find('input[data-action="wsf-data-grid-row-select"]:checked', obj).closest('tr');

				// Get group
				var group_index = $(this).closest('.wsf-data-grid-group').attr('data-group-index');
				var group = meta_value.groups[group_index];

				// Get row offset
				var page = group.page;
				var rows_per_page = meta_value.rows_per_page;
				var row_offset = (page * rows_per_page);

				switch(bulk_action) {

					case 'delete' :

						// Delete array indexes
						bulk_action_rows.each(function() {

							var row_index = row_offset + $(this).index();
							delete group.rows[row_index];
						});

						// Remove empty elements after delete
						group.rows = $.grep(group.rows,function(n){ return n == 0 || n });

						break;

					case 'default' :
					case 'disabled' :
					case 'required' :
					case 'hidden' :

						// Reset values
						for(var key in group.rows) { group.rows[key][bulk_action] = ''; }

						// Set values
						var default_array = [];
						bulk_action_rows.each(function() {

							var row_index = row_offset + $(this).index();
							group.rows[row_index][bulk_action] = 'on';
						});

						break;

					case 'default_off' :
					case 'disabled_off' :
					case 'required_off' :
					case 'hidden_off' :

						bulk_action = bulk_action.replace('_off', '');

						// Reset values
						for(var key in group.rows) { group.rows[key][bulk_action] = ''; }

						// Set values
						var default_array = [];
						bulk_action_rows.each(function() {

							var row_index = row_offset + $(this).index();
							group.rows[row_index][bulk_action] = '';
						});

						break;
				}

				// Refresh data grid
				element.render();
			});

			// Bulk action - Select all / no rows
			$('[data-action="wsf-data-grid-row-select-all"]', obj).change(function() {

				var table = $(this).closest('table');
				var checked = $(this).is(':checked');

				if(checked) {
					$('input[data-action="wsf-data-grid-row-select"]', table).attr('checked', 'checked');	// JQuery < 1.6 compatible method
				} else {
					$('input[data-action="wsf-data-grid-row-select"]', table).removeAttr('checked');
				}

				$.WS_Form.this.data_grid_bulk_action_button(obj, object, object_id, meta_key);
			});

			// Bulk action - Select rows
			$('[data-action="wsf-data-grid-row-select"]', obj).change(function() {

				$.WS_Form.this.data_grid_bulk_action_button(obj, object, object_id, meta_key);
			});

			// Multiple
			obj.closest('.wsf-sidebar').find('[data-meta-key="multiple"]').change(function() {

				$.WS_Form.this.data_grid_bulk_action_button(obj, object, object_id, meta_key, true, element);
			});

			// Update data mask fields
			$.WS_Form.this.data_grid_update_mask_row_lookups(object, object_id, meta_key);

			// Mark as initialized
			obj.addClass('wsf-data-grid-initialized');
		}

		// Render
		element.render();
	}

	// Data grid - Open row
	$.WS_Form.prototype.data_grid_row_open = function(obj, row_type, meta_value) {

		// Delete row if it exists
		if($('.wsf-hidden-table-row').length) {

			// Enable row sorting
			$('[data-action="wsf-data-grid-row-sort"]').removeClass('wsf-ui-cancel');

			// Save
			switch(row_type) {

				case 'action' :

					$.WS_Form.this.action_save();
					break;

				case 'conditional' :

					$.WS_Form.this.conditional_save();
					break;
			}

			// Remove old row
			$('.wsf-hidden-table-row').remove();

			// If user is click on same row we just closed, then return
			var same_row_type = obj.hasClass('wsf-data-grid-row-open');

			// Remove open class
			$('.wsf-data-grid-row-open').removeClass('wsf-data-grid-row-open');

			if(same_row_type) { return; }
		}

		// Disable row sorting
		$('[data-action="wsf-data-grid-row-sort"]').addClass('wsf-ui-cancel');

		obj.addClass('wsf-data-grid-row-open');

		// Get group
		var group_index = obj.closest('.wsf-data-grid-group').attr('data-group-index');
		var group = meta_value.groups[group_index];

		var rows = group.rows;

		// Get row offset
		var page = group.page;
		var rows_per_page = meta_value.rows_per_page;
		var row_offset = (page * rows_per_page);

		// Get data position
		var row_index = row_offset + (obj.closest('tr').index());

		var row = rows[row_index];

		// Create row
		var closest_tr = obj.closest('tr');
		var td_count = $('td', closest_tr).length;
		var row_html = '<tr id="wsf-' + row_type + '-tr" class="wsf-hidden-table-row"><td></td><td></td><td class="wsf-hidden-table-cell"><div></div></td><td></td><td></td><td></td></tr>';
		$(row_html).insertAfter(closest_tr).show();

		// Get data
		switch(row_type) {

			case 'action' :

				$.WS_Form.this.action_data = row.data;
				$.WS_Form.this.action_render_from_data();
				break;

			case 'conditional' :

				$.WS_Form.this.conditional_data = row.data;
				$.WS_Form.this.conditional_render_from_data();
				break;
		}
	}

	// Data grid - Expand
	$.WS_Form.prototype.data_grid_row_next_id = function(meta_value) {

		var row_id = 0;
		var groups = meta_value['groups'];

		for(var group_index in groups) {

			var group = groups[group_index];

			var rows = group['rows'];

			for(var row_index in rows) {

				var row = rows[row_index];

				// Error checking
				if(typeof row['id'] == 'undefined') { this.error('error_data_grid_row_id'); }

				// Get row ID
				var id = parseInt(row['id']);

				// If higher row ID found, set row_id
				if(id > row_id) { row_id = id; }
			}
		}

		// Increment row ID
		row_id++;

		return row_id;
	}

	// Data grid - Uploader
	$.WS_Form.prototype.data_grid_upload_csv = function(object, object_id, meta_key, files, obj) {

		// Hide H1
		$('h1', obj).hide();

		if(files.length == 0) { return false; }

		// Create form data
		var form_data = new FormData();
		form_data.append('id', this.form_id);
		form_data.append('file', files[0]);
		form_data.append('meta_key', meta_key);

		// Create status bar for this file
		var status_bar = new this.upload_status_bar(obj)

		// Populate status_bar
		status_bar.populate(files[0].name, files[0].size);

		// Send file to the server using AJAX
		this.data_grid_upload_csv_ajax(object, object_id, form_data, status_bar, obj, function(response) {

			// If successful, run complete
			$.WS_Form.this.data_grid_upload_csv_complete(object, object_id, meta_key, response.data, obj);
		});
	}

	// Date grid - Uploader complete
	$.WS_Form.prototype.data_grid_upload_csv_complete = function(object, object_id, meta_key, meta_value, obj) {

		// Write to meta data
		$.WS_Form.this.object_data_scratch.meta[meta_key] = meta_value;

		// Update data mask fields (With reset - Resets the meta_values to 0 on the data_grid_fields)
		$.WS_Form.this.data_grid_update_mask_row_lookups(object, object_id, meta_key, true);

		// Render data grid
		obj.closest('.wsf-data-grid')[0].render();
	}

	// Data grid - Download CSV
	$.WS_Form.prototype.data_grid_download_csv = function(object, object_id, meta_key, group_index, use_scratch) {

		if(typeof use_scratch === 'undefined') { var use_scratch = true; }

		// Should we use the scratch data? If so we need to send it to the API request
		if(use_scratch) {

			// Build meta_value_json
			var object_data = $.WS_Form.this.get_object_data(object, object_id, true);
			if(object_data === false) { $.WS_Form.this.error('error_object_data'); }

			// Get latest current data grid data
			var meta_value = $.WS_Form.this.get_object_meta_value(object_data, meta_key, false);
			if(meta_value === false) { $.WS_Form.this.error('error_object_meta_value'); }
			var meta_value_json = encodeURIComponent(JSON.stringify(meta_value));
		}

		// Build downloader
		var downloader_html = '<form id="wsf-data-grid-downloader" action="' + ws_form_settings.url + object + '/' + object_id + '/download/csv" method="post">';

		downloader_html += '<input type="hidden" name="form_id" value="' + $.WS_Form.this.form_id + '" />';
		downloader_html += '<input type="hidden" name="meta_key" value="' + meta_key + '" />';
		downloader_html += '<input type="hidden" name="group_index" value="' + group_index + '" />';
		downloader_html += '<input type="hidden" name="_wpnonce" value="' + ws_form_settings.nonce + '" />';

		if(use_scratch) {

			downloader_html += '<input type="hidden" name="meta_value" value="' + meta_value_json + '" />';
		}

		downloader_html += '</form>';

		// Inject into body
		var downloader = $('body').append(downloader_html);

		// Submit
		$('#wsf-data-grid-downloader').submit();

		// Remove
		$('#wsf-data-grid-downloader').remove();
	}

	// Convert an object to a CSV encoded string
	$.WS_Form.prototype.object_to_csv = function(object) {

		var data_array = new Array;

		for (var o in object) {

			var cell = object[o];

			if(cell.indexOf(',') != -1) {

				cell = cell.replace_all(/"/g, '""');
				cell = '"' + cell + '"';
			}

			data_array.push(cell);
		}

		return data_array.join(',') + '\r\n';
	}

	// Data grid - Uploaded CSV - AJAX request
	$.WS_Form.prototype.data_grid_upload_csv_ajax = function(object, object_id, form_data, status_bar, obj, success_callback) {

		var jqXHR=$.ajax({

			beforeSend: function(xhr) {

				xhr.setRequestHeader('X-WP-Nonce', ws_form_settings.nonce);
			},

			xhr: function() {

				// Upload progress
				var xhrobj = $.ajaxSettings.xhr();
				if (xhrobj.upload) {

					xhrobj.upload.addEventListener('progress', function(event) {

						var percent = 0;
						var position = event.loaded || event.position;
						var total = event.total;
						if (event.lengthComputable) {
							percent = Math.ceil(position / total * 100);
						}

						status_bar.set_progress(percent);

					}, false);
				}

				return xhrobj;
			},

			url: ws_form_settings.url + object + '/' + object_id + '/upload/csv',
			type: 'POST',
			contentType: false,
			processData: false,
			cache: false,
			data: form_data,

			statusCode: {

				// Success
				200: function(data) {

					// Set progress bar to 100%
					status_bar.set_progress(100);

					// Call success script
					if(typeof success_callback === 'function') { success_callback(data); }
				},

				// Bad request (Error from API)
				400: function(response) {

					var data = response.responseJSON;

					if(data.error) {

						// Show error
						$.WS_Form.this.error('error_bad_request', data.error_message);

						// Hide drag and drop zone
						obj.fadeOut(200);
					}
				},

				// Forbidden (Error from API)
				403: function(response) {

					// Show error
					$.WS_Form.this.error('error_forbidden', url);

					// Hide drag and drop zone
					obj.fadeOut(200);
				},

				// Not found (Error from API)
				404: function(response) {

					// Show error
					$.WS_Form.this.error('error_not_found', url);

					// Hide drag and drop zone
					obj.fadeOut(200);
				},

				// Server error
				500: function(response) {

					// Show error
					$.WS_Form.this.error('error_server', url);

					// Hide drag and drop zone
					obj.fadeOut(200);
				}
			}
		});

		status_bar.set_abort(jqXHR);
	}

	// Data grid - Uploader status bar
	$.WS_Form.prototype.upload_status_bar = function(obj) {

		// Build status bar
		this.status_bar = $('<div class="wsf-upload-status-bar"></div>');
		this.progress_bar = $('<div class="wsf-upload-status-bar-progress"><div></div></div>').appendTo(this.status_bar);
		this.file_name = $('<div class="wsf-upload-status-bar-file-name"></div>').appendTo(this.status_bar);
		this.file_size = $('<div class="wsf-upload-status-bar-file-size"></div>').appendTo(this.status_bar);
		this.abort = $('<div class="wsf-upload-status-bar-abort">Abort</div>').appendTo(this.status_bar);
		$('.wsf-uploads', obj).append(this.status_bar);

		// Methods
		this.set_progress = function(progress) {

			var progress_bar_width = (progress * this.progress_bar.width()) / 100;
			this.progress_bar.find('div').animate({width:progress_bar_width}, 10).html('<span>' + progress + '%</span>');

			if(parseInt(progress) >= 100) {

				this.abort.hide();
			}
		}

		// Populate file information
		this.populate = function(file_name, file_size) {

			var size_string ='';
			var size_kb = file_size / 1024;

			if(parseInt(size_kb) > 1024) {

				var size_mb = size_kb / 1024;
				size_string = size_mb.toFixed(2) + ' MB';

			} else {

				size_string = size_kb.toFixed(2) + ' KB';
			}

			this.file_name.html(file_name);
			this.file_size.html(size_string);
		}

		this.set_abort = function(jqxhr) {

			var sb = this.status_bar;
			this.abort.click(function() {

				jqxhr.abort();
				sb.hide();
			});
		}
	}

	// Data grid - Update data mask fields (Updates the select pull does with the column headings)
	$.WS_Form.prototype.data_grid_update_mask_row_lookups = function(object, object_id, meta_key, meta_value_reset) {

		// Only run this on fields
		if(object !== 'field') { return false; }

		// Check for meta_value_reset
		if(typeof meta_value_reset === 'undefined') { var reset = false; }

		// Get object data of scratch field
		var object_data = $.WS_Form.this.get_object_data(object, object_id, true);
		if(object_data === false) { $.WS_Form.this.error('error_object_data'); }

		// Get data grid data of original old
		var meta_value = $.WS_Form.this.get_object_meta_value(object_data, meta_key, false);
		if(meta_value === false) { $.WS_Form.this.error('error_object_meta_value'); }

		// Get columns
		var columns = meta_value.columns;

		// Get field type
		var field_type = object_data.type;

		// Get field type config
		var field_type_config = $.WS_Form.field_type_cache[field_type];

		if(typeof(field_type_config.mask_row_lookups) !== 'undefined') {

			// Read data mask fields
			var mask_row_lookups = field_type_config.mask_row_lookups;

			// Include auto group select
			mask_row_lookups.push('wsf-data-grid-auto-group');

			// Run through each data mask field
			for(var data_mask_field_key in mask_row_lookups) {

				// Get data mask field ID
				var data_mask_field = mask_row_lookups[data_mask_field_key];

				// Is this the auto group select?
				var auto_group = (data_mask_field == 'wsf-data-grid-auto-group');

				// Get current value
				var meta_value = auto_group ? '' : this.get_object_meta_value(object_data, data_mask_field, 0);

				// Get object for data mask field
				var data_mask_field_object = $('#wsf-sidebar-' + object + ' [' + (auto_group ? 'data-action' : 'data-meta-key') + '="' + data_mask_field + '"]');

				// Clear contents of data mask field
				data_mask_field_object.empty();

				// Build options array
				var options_array = [];
				var meta_value_found = false;
				for(var column_key in columns) {

					var column = columns[column_key];

					if(parseInt(column.id) == parseInt(meta_value)) { meta_value_found = true; }

					options_array.push({

						'value': column.id,
						'text': column.label
					});
				}

				// If the current meta value (column ID) is no longer found (e.g. Column deleted) reset meta_value to 0
				if(!meta_value_found || meta_value_reset) {

					this.set_object_meta_value(object_data, data_mask_field, 0);
					meta_value = 0;
				}

				// Sort array
				options_array.sort(function(a, b) {

					if(a.text < b.text) return -1;
					if(a.text > b.text) return 1;

					return 0;
				});

				// Built options HTML
				var options_html = auto_group ? '<option value="">' + this.language('data_grid_group_auto_group_select') + "</option>\n" : '';
				for(var options_array_key in options_array) {

					var option_value = options_array[options_array_key].value;
					var option_selected = auto_group ? '' : ((parseInt(option_value) == parseInt(meta_value)) ? ' selected' : '');
					var option_text = options_array[options_array_key].text;
					options_html += '<option value="' + option_value + '"' + option_selected + '>' + option_text + "</option>\n";
				}

				data_mask_field_object.html(options_html);
			}
		}
	}

	// Data grid - Bulk action button
	$.WS_Form.prototype.data_grid_bulk_action_button = function(obj, object, object_id, meta_key, default_check, element) {

		if(typeof default_check === 'undefined') { var default_check = false; }
		if(typeof element === 'undefined') { var element = false; }

		// Determine whether you can multi-select default values
		var multiple = obj.closest('.wsf-sidebar').find('[data-meta-key="multiple"]:checked').length;

		// Run through each group
		var groups_obj = $('.wsf-data-grid-group', obj).each(function() {

			// If multiple defaults are not supported, then ensure only on row is set as default
			if(!multiple && default_check) {

				// Get object data of scratch field
				var object_data = $.WS_Form.this.get_object_data(object, object_id, true);
				if(object_data === false) { $.WS_Form.this.error('error_object_data'); }

				// Get data grid data of scratch
				var meta_value = $.WS_Form.this.get_object_meta_value(object_data, meta_key, false);
				if(meta_value === false) { $.WS_Form.this.error('error_object_meta_value'); }

				// Get group
				var group_index = $(this).attr('data-group-index');
				var group = meta_value.groups[group_index];
				var rows = group.rows;

				// Check to see if number of default rows > 1
				var default_row_count = 0;
				for(var row_index in rows) {

					if(rows[row_index]['default'] != '') { default_row_count++; }
					if(default_row_count > 1) { break; }
				}

				// More than one default row
				if(default_row_count > 0) {

					var default_row_count = 0;
					for(var row_index in rows) {

						if(rows[row_index]['default'] != '') {

							default_row_count++;

							// Remove any default rows after the first default
							if(default_row_count > 1) { rows[row_index]['default'] = ''; }
						}
					}

					if(element !== false) { element.render(); }
				}
			}

			// Get all checked bulk action checkboxes
			var checked = $('[data-action="wsf-data-grid-row-select"]:checked', $(this));

			// Get bulk action button object
			var bulk_action_button = $('[data-action="wsf-data-grid-bulk-action"]', $(this));

			// Get bulk action select object
			var bulk_action_select = bulk_action_button.siblings('select');

			// Set button disabled attribute
			bulk_action_button.attr('disabled', (checked.length == 0));

			// Set select attribute
			bulk_action_select.attr('disabled', (checked.length == 0));

			// If multiple is not enabled and more than one bulk action checkbox is checked, disable the default option in bulk edit options
			$('option[value="default"]', bulk_action_select).attr('disabled', (!multiple && (checked.length > 1)));
		});
	}

	// Data grid - Group page set
	$.WS_Form.prototype.data_grid_group_page_set = function(group, group_index, page_new, object, object_id, meta_key, complete) {

		// Set new page on scratch
		group.page = page_new;

		// Get object data of original field
		var object_data_old = $.WS_Form.this.get_object_data(object, object_id);
		if(object_data_old === false) { $.WS_Form.this.error('error_object_data'); }

		// Get data grid data of original old (Will be false if no existing conditional exists)
		var meta_value_old = $.WS_Form.this.get_object_meta_value(object_data_old, meta_key, false);

		if(
			(meta_value_old !== false) &&
			(typeof(meta_value_old.groups[group_index]) !== 'undefined')
		) {

			// Loader on
			$.WS_Form.this.loader_on();

			// Save new page to original field
			meta_value_old.groups[group_index].page = page_new;

			// Build parameters
			var params = {};

			// Form ID
			params['form_id'] = $.WS_Form.this.form_id;

			// Object data
			params[object] = object_data_old;
			params[object]['history_suppress'] = 'on';

			// Run complete function
			if(typeof complete !== 'undefined') { complete(); }

			// Call AJAX request
			$.WS_Form.this.api_call(object + '/' + object_id, 'PUT', params, function(response) {

				// Loader off
				$.WS_Form.this.loader_off();
			});
		}
	}

	// Save rows per page
	$.WS_Form.prototype.data_grid_group_rows_per_page_set = function(meta_value, rows_per_page, object, object_id, meta_key, complete) {

		// Saves rows per page
		meta_value.rows_per_page = rows_per_page;

		// Get object data of original field
		var object_data_old = $.WS_Form.this.get_object_data(object, object_id);
		if(object_data_old === false) { $.WS_Form.this.error('error_object_data'); }

		// Get data grid data of original old
		var meta_value_old = $.WS_Form.this.get_object_meta_value(object_data_old, meta_key, false);
		if(meta_value_old === false) { $.WS_Form.this.error('error_object_meta_value'); }

		// Loader on
		$.WS_Form.this.loader_on();

		// Save new rows_per_page to original field
		meta_value_old.rows_per_page = rows_per_page;

		// Set all group pages to 0
		for(var group_index in meta_value.groups) { meta_value.groups[group_index].page = 0; }
		for(var group_index in meta_value_old.groups) { meta_value_old.groups[group_index].page = 0; }

		// Build parameters
		var params = {};

		// Form ID
		params['form_id'] = $.WS_Form.this.form_id;

		// Object data
		params[object] = object_data_old;
		params[object]['history_suppress'] = 'on';

		// Run complete function
		if(typeof complete !== 'undefined') { complete(); }

		// Call AJAX request
		$.WS_Form.this.api_call(object + '/' + object_id, 'PUT', params, function(response) {

			// Loader off
			$.WS_Form.this.loader_off();
		});
	}

	// HTML Editor - Init
	$.WS_Form.prototype.sidebar_html_editor_init = function(obj_sidebar_inner) {

		if(
			(typeof(wp) !== 'undefined') &&
			(typeof(wp.codeEditor) !== 'undefined')
		) {

			// Kill existing
			$('.CodeMirror', obj_sidebar_inner).remove();

			// Initialize CodeMirror
			$('.wsf-sidebar-tabs-panel:visible [data-html-editor="true"], [id^=wsf-action] [data-html-editor="true"], .wsf-html-editor [data-html-editor="true"]', obj_sidebar_inner).each(function() {

				wp.codeEditor.initialize($(this).attr('id'));
			});

			$('.CodeMirror').each(function() {

				var code_editor = $(this)[0].CodeMirror;
				code_editor.on("keyup", function (cm, event) {

					var code_editor_value = cm.getValue();
					var code_editor_textarea = cm.getTextArea();
					$(code_editor_textarea).val(code_editor_value).trigger('keyup');
				});
			});

		} else {

			// Initialize textarea (CodeMirror not supported)
			$(document).delegate('[data-html-editor="true"]', 'keydown', function(e) {

				var keyCode = e.keyCode || e.which;

				if (keyCode == 9) {

					e.preventDefault();

					var start = this.selectionStart;
					var end = this.selectionEnd;

					$(this).val($(this).val().substring(0, start) + "\t" + $(this).val().substring(end));
					this.selectionStart = this.selectionEnd = start + 1;
				}
			});
		}
	}

	// Action - Render from data
	$.WS_Form.prototype.action_render_from_data = function() {

		// Validate action_data
		try {

			this.action = JSON.parse(this.action_data[1]);

		} catch(e) {

			this.action = this.action_new();
		}

		// Render
		this.action_render();
	}

	// Action - Blank
	$.WS_Form.prototype.action_new = function() {

		// Build a new action
		var action = {

			id: '',
			meta: {},
			events: []
		};

		return action;
	}

	// Action - Render
	$.WS_Form.prototype.action_render = function() {

		var action_obj = $('#wsf-action-tr > td > div');

		var action_id = this.action.id;

		// Ensure action is still installed
		if(typeof($.WS_Form.actions[action_id]) === 'undefined') { this.action = this.action_new(); action_id = ''; }

		// Build action global fields
		var action_header_html = '<fieldset class="wsf-fieldset wsf-action-header" data-action-header="true" data-object="action" data-id="' + action_id + '">';

		// Type of action
		action_header_html += '<div class="wsf-field-wrapper"><label class="wsf-label">' + this.language('data_grid_action_action') + '</label>';
		action_header_html += '<select data-meta-key="action_id" class="wsf-field">';
		action_header_html += '<option value="">Select...</option>';

		// Get actions that can only be fired once and are already included
		var action_single_use_exhausted_array = [];
		var action_meta = this.get_object_meta_value(this.object_data_scratch, 'action');
		var actions = action_meta.groups[0].rows;
		for(var action_index in actions) {

			// Get action
			try {

				var action_single = JSON.parse(actions[action_index].data[1]);

			} catch (e) {

				continue;
			}

			// Get action_id
			if(typeof(action_single['id']) === 'undefined') { continue; }
			var action_single_id = action_single['id'];

			// Get multiple value
			if(typeof($.WS_Form.actions[action_single_id]) === 'undefined') { continue }
			if(typeof($.WS_Form.actions[action_single_id].multiple) === 'undefined') { continue }

			// If action can only be run once, hide it from the actions pull down
			if(!$.WS_Form.actions[action_single_id].multiple) { action_single_use_exhausted_array.push(action_single_id); }
		}

		// Sort actions by label_action
		var actions_sorted = [];
		for (var action_sorted_id in $.WS_Form.actions) { actions_sorted.push({id: action_sorted_id, action: $.WS_Form.actions[action_sorted_id]}); }
		actions_sorted.sort(function (a, b) { return (a.action.label_action.toLowerCase() < b.action.label_action.toLowerCase()) ? -1 : 1; } );

		for(var action_sorted_index in actions_sorted) {

			var action_single = actions_sorted[action_sorted_index];

			var action_single_id = action_single.id;

			// Skip one time use actions that have already be used
			if((action_single_use_exhausted_array.indexOf(action_single_id) !== -1) && (action_single_id != action_id)) { continue; }

			var action_single_label = action_single.action.label_action;

			action_header_html += '<option value="' + action_single_id + '"' + ((action_single_id == action_id) ? ' selected="selected"' : '') + '>' + this.html_encode(action_single_label) + '</option>';
		}

		for(var actions_index in $.WS_Form.settings_form['sidebars']['action']['actions_pro']) {

			var action_pro = $.WS_Form.settings_form['sidebars']['action']['actions_pro'][actions_index];

			action_header_html += '<option value="" disabled="disabled">' + action_pro + '</option>';
		}

		action_header_html += '</select>';
		action_header_html += '</div>';

		action_header_html += '<div class="wsf-field-wrapper">';

		if(action_id != '') {

			action_header_html += '<label class="wsf-label">' + this.language('data_grid_action_event') + '</label>';

			// When to fire action?
			var action_events = $.WS_Form.settings_form['sidebars']['action']['events'];
			for(var action_event_key in action_events) {

				var action_event = action_events[action_event_key];
				var action_selected = ($.WS_Form.this.action.events.indexOf(action_event_key) != -1);

				action_header_html += '<div><input class="wsf-field" type="checkbox" id="wsf_action_event_' + action_event_key + '" name="wsf_action_event[]"' + (action_selected ? ' checked="checked"' : '') + ' /><label class="wsf-label" for="wsf_action_event_' + action_event_key + '">' + this.html_encode(action_event['label']) + '</label></div>';
			}
		}

		if($.WS_Form.settings_plugin.helper_field_help) {

			action_header_html += '<div class="wsf-helper">' + this.language('data_grid_action_event_conditional', '', false) + '</div>';
		}

		action_header_html += '</div>';


		action_header_html += '</fieldset>';

		action_obj.html(action_header_html);

		// Get sidebar HTML and Inits
		if(action_id != '') {

			// Clear sidebar caches
			this.sidebar_cache_clear();

			var sidebar_html_return = this.sidebar_html('action', action_id, this.action, $.WS_Form.actions[action_id], false, true, false, false);

			// Add action HTML to object
			action_obj.append(sidebar_html_return.html);

			// Initialize action
			this.sidebar_inits(sidebar_html_return.inits, action_obj, action_obj, this.action);
		}

		// Action change event
		$('[data-meta-key="action_id"]', action_obj).change(function() {

			var action_id = $(this).val();

			// Get action label object
			var action_label = $('[data-column="0"]', $(this).closest('tr').prev());

			// Set action variables
			$.WS_Form.this.action_save(true);

			// Render sidebar
			$.WS_Form.this.action_render();

			// Set checkboxes and label
			if(action_id != '') {

				// Set default events
				var action = $.WS_Form.actions[action_id];
				if(typeof(action.events) !== 'undefined') {

					for(var action_event_index in action.events) {

						var action_event = action.events[action_event_index];

						$('#wsf_action_event_' + action_event).prop('checked', true);
					}
				}

				// Set action label
				action_label.val(action.label_action);

			} else {

				// Set action label
				var default_label = $.WS_Form.this.language('action_label_default');
				action_label.val(default_label);
			}

			// Trigger save of label
			action_label.trigger('keyup');

			// Set action variables
			$.WS_Form.this.action_save(true);
		});

		// Reload on change
		$('[data-change="reload"]', action_obj).change(function() {

			// Set action variables
			$.WS_Form.this.action_save();

			// Render sidebar
			$.WS_Form.this.action_render();
		});

		action_obj.show();
	}

	// Action API method events
	$.WS_Form.prototype.api_reload_init = function(obj, success_callback, error_callback, sidebar) {

		$('[data-action="wsf-api-reload"]', obj).click(function() {

			var api_reload_obj = $(this);

			// Start reloader spinning
			api_reload_obj.addClass('wsf-api-method-calling');

			// If this reload button is in a sidebar, save the sidebar to scratch before calling the API request
			if(sidebar) {

				// Get meta key this relates to
				var meta_key_for = api_reload_obj.attr('data-meta-key-for');

				// Get meta key data
				var meta_key = $('[data-meta-key="' + meta_key_for + '"]');
				var object = meta_key.attr('data-object');
				var object_id = meta_key.attr('data-object-id');
				var object_data = $.WS_Form.this.get_object_data(object, object_id, true);

				// Save sidebar
				for(var key in $.WS_Form.this.object_meta_cache) {

					// Get meta_key
					var meta_key = $.WS_Form.this.object_meta_cache[key]['meta_key'];

					// Update object data
					$.WS_Form.this.object_data_update_by_meta_key(object, object_data, meta_key);
				}
			}

			// Get action ID and method
			var reload_action_id = api_reload_obj.attr('data-action-id');
			if(reload_action_id == undefined) {

				var reload_action_id_meta_key = api_reload_obj.attr('data-action-id-meta-key');
				if(reload_action_id_meta_key != undefined) {

					reload_action_id = $('[data-meta-key="' + reload_action_id_meta_key + '"]', $('#wsf-sidebars')).val();
				}
			}
			var reload_method = api_reload_obj.attr('data-method');

			// Get list ID
			var reload_list_id_meta_key = api_reload_obj.attr('data-list-id-meta-key');
			var reload_list_id = (reload_list_id_meta_key != undefined) ? $('[data-meta-key="' + reload_list_id_meta_key + '"]', $('#wsf-sidebars')).val() : false;

			// Get list sub ID
			var reload_list_sub_id_meta_key = api_reload_obj.attr('data-list-sub-id-meta-key');
			var reload_list_sub_id = (reload_list_sub_id_meta_key != undefined) ? $('[data-meta-key="' + reload_list_sub_id_meta_key + '"]', $('#wsf-sidebars')).val() : false;

			// Get API call path
			var api_call_path = $.WS_Form.this.action_api_method_path(reload_action_id, reload_method, reload_list_id, reload_list_sub_id);

			$.WS_Form.this.options_action_cache_clear(api_call_path);

			// Make API call
			$.WS_Form.this.api_call(api_call_path, 'GET', false, function() {

				api_reload_obj.removeClass('wsf-api-method-calling');
				success_callback(api_reload_obj, reload_action_id, reload_method);

			}, function(data) {

				if(data.error) { $.WS_Form.this.error('error_bad_request_message', data.error_message); }
				api_reload_obj.removeClass('wsf-api-method-calling');

			}, true);	// Bypass loader
		});
	}

	// Clear options_action_cache
	$.WS_Form.prototype.options_action_cache_clear = function(api_call_path) {

		if(api_call_path === false) { return false; }

		if(api_call_path.endsWith('/fetch/')) {

			api_call_path = api_call_path.substring(0, api_call_path.length - 6);
		}

		$.WS_Form.this.options_action_cache[api_call_path] = undefined;
	}

	// Get API call path
	$.WS_Form.prototype.action_api_method_path = function(action_id, method, list_id, list_sub_id) {

		// Check values
		if((typeof(action_id) === 'undefined') || (action_id == '') || (action_id === null)) { return false; }
		if((typeof(method) === 'undefined') || (method == '') || (method === null)) { return false; }
		if((typeof(list_id) === 'undefined') || (list_id == '') || (list_id === null)) { var list_id = false; }
		if((typeof(list_sub_id) === 'undefined') || (list_id == '') || (list_id === null)) { var list_sub_id = false; }

		// Build API path and params
		var api_call_path = 'action/' + action_id + '/';

		// Process API method
		switch(method) {

			case 'lists' :

				api_call_path += 'lists/';
				break;

			case 'lists_fetch' :

				api_call_path += 'lists/fetch/';
				break;

			case 'list' :

				if(list_id === false) { return false; }
				api_call_path += 'list/' + encodeURIComponent(list_id) + '/';
				break;

			case 'list_fetch' :

				if(list_id === false) { return false; }
				api_call_path += 'list/' + encodeURIComponent(list_id) + '/fetch/';
				break;

			case 'list_subs' :

				if(list_id === false) { return false; }
				api_call_path += 'list/' + encodeURIComponent(list_id) + '/subs/';
				break;

			case 'list_subs_fetch' :

				if(list_id === false) { return false; }
				api_call_path += 'list/' + encodeURIComponent(list_id) + '/subs/fetch/';
				break;

			case 'list_fields' :

				if(list_id === false) { return false; }
				if(list_sub_id === false) {

					api_call_path += 'list/' + encodeURIComponent(list_id) + '/fields/';

				} else {

					api_call_path += 'list/' + encodeURIComponent(list_id) + '/subs/' + encodeURIComponent(list_sub_id) + '/fields/';
				}
				break;

			case 'list_fields_fetch' :

				if(list_id === false) { return false; }
				if(list_sub_id === false) {

					api_call_path += 'list/' + encodeURIComponent(list_id) + '/fields/fetch/';

				} else {

					api_call_path += 'list/' + encodeURIComponent(list_id) + '/subs/' + encodeURIComponent(list_sub_id) + '/fields/fetch/';
				}
				break;

			default :

				api_call_path += method + '/';
		}

		return api_call_path;
	}

	// Set the action array variables
	$.WS_Form.prototype.action_save = function(build_meta) {

		if(typeof(build_meta) === 'undefined') { var build_meta = false; }

		if(this.action === false) { return false; }
		if(!$('#wsf-action-tr').length) { return false; }

		this.action.id = $('[data-meta-key="action_id"]').val();
		this.action.events = [];
		this.action.meta = {};

		if(this.action.id != '') {

			// Set meta_values
			var meta_data = this.build_meta_data($.WS_Form.actions[this.action.id], $.WS_Form.meta_keys);
			for(var meta_key in meta_data) {

				// Ensure meta key is configured
				if(typeof($.WS_Form.meta_keys[meta_key]) === 'undefined') { continue; }

				if(build_meta) {

					var meta_value = meta_data[meta_key];
					this.action.meta[meta_key] = meta_value;

				} else {

					this.object_data_update_by_meta_key('action', this.action, meta_key);
				}
			}

			// Set events
			var action_events = $.WS_Form.settings_form['sidebars']['action']['events'];
			for(var action_event_key in action_events) {

				var action_event_selector = '#wsf_action_event_' + action_event_key;
				if($(action_event_selector).is(':checked')) {

					this.action.events.push(action_event_key);
				}
			}
		}

		// Write to row data
		this.action_data[1] = JSON.stringify(this.action);
	}

	// Build meta data for an object
	$.WS_Form.prototype.build_meta_data = function(meta_data, meta_keys, return_array) {

		if(typeof(return_array) == 'undefined') { var return_array = []; }

		for(var key in meta_data) {

			var value = meta_data[key];

			if(typeof(value) === 'object') {

				if(key === 'meta_keys') {

					for(var meta_key_index in value) {

						var meta_key = value[meta_key_index];

						// Skip unknown meta_keys
						if(typeof(meta_keys[meta_key]) === 'undefined') { continue; }

						// Skip dummy entries
						if((typeof(meta_keys[meta_key]['dummy'] !== 'undefined') && meta_keys[meta_key]['dummy'])) { continue; }

						// Get default meta value
						if(typeof(meta_keys[meta_key]['default']) !== 'undefined') {

							var meta_value = meta_keys[meta_key]['default'];

						} else {

							var meta_value = '';
						}

						// Handle boolean values
						meta_value = (typeof(meta_value) === 'boolean') ? (meta_value ? 'on' : '') : meta_value;

						// Handle key changes
						if(typeof(meta_keys[meta_key]['key']) !== 'undefined') {

							meta_key = meta_keys[meta_key]['key'];
						}

						// Add to return array
						return_array[meta_key] = meta_value;
					}

				} else {

					// Follow
					return_array = this.build_meta_data(value, meta_keys, return_array);
				}
			}
		}

		return return_array;
	}

	// Sidebar - Title
	$.WS_Form.prototype.sidebar_title = function(sidebar_icon, sidebar_label, sidebar_compatibility_html, sidebar_kb_html, sidebar_field_id_html, sidebar_expand) {

		if(typeof sidebar_compatibility_html === 'undefined') { var sidebar_compatibility_html = ''; }
		if(typeof sidebar_kb_html === 'undefined') { var sidebar_kb_html = ''; }
		if(typeof sidebar_field_id_html === 'undefined') { var sidebar_field_id_html = ''; }
		if(typeof sidebar_expand === 'undefined') { var sidebar_expand = false; }

		// Expand / Contract
		if(sidebar_expand) {

			var expand_contract = '<div data-action="wsf-sidebar-expand" title="' + this.language('sidebar_expand') + '">' + this.svg('expand') + '</div>';
			expand_contract += '<div data-action="wsf-sidebar-contract" title="' + this.language('sidebar_contract') + '">' + this.svg('contract') + '</div>';

		} else {

			var expand_contract = '';
		}

		return '<div class="wsf-sidebar-header"><h2>' + sidebar_icon + sidebar_label + sidebar_kb_html + sidebar_compatibility_html + sidebar_field_id_html + '</h2>' + expand_contract + '</div>';
	}

	// Sidebar - Open
	$.WS_Form.prototype.sidebar_open = function(id) {

		// Close the current side bar if it is different from the id being opened
		var sidebar_current = $('#wsf-sidebars').attr('data-current');

		// Reset sidebar resize
		$.WS_Form.this.sidebar_resize_reset();

		if((typeof(sidebar_current) !== 'undefined') && (sidebar_current != id)) {

			// Different sidebar requested
			var meta_key_close_function = 'sidebar_' + sidebar_current + '_close';
			if(typeof(window[meta_key_close_function]) === 'function') {

				window[meta_key_close_function]($.WS_Form.this);
			}

			this.sidebar_close(sidebar_current);
		}

		// Add editing class to sidebar button
		$('[data-action-sidebar="' + id + '"]').addClass('wsf-editing');

		// Initial tab
		if(ws_form_settings.sidebar_tab_key !== false) {

			$('[data-wsf-tab-key="' + ws_form_settings.sidebar_tab_key + '"]', $('#wsf-sidebar-' + id)).click();
			ws_form_settings.sidebar_tab_key = false;
		}

		// Sidebar - Open
		$('#wsf-sidebar-' + id).removeClass('wsf-sidebar-closed').addClass('wsf-sidebar-open');

		// Set current side bar open
		$('#wsf-sidebars').attr('data-current', id);

		// Reset scrolling
		$('.wsf-sidebar-inner').scrollTop(0);

		// Overflow hidden to improve touch scrolling in sidebar
		if(window.matchMedia('(max-width: 600px)').matches) {

			$('html').css({'overflow':'hidden'});
			$('body').css({'overflow':'auto','-webkit-overflow-scrolling':'touch'});
		}
	}

	// Sidebar - Close
	$.WS_Form.prototype.sidebar_close = function(id) {

		// Remove editing class from button
		$('[data-action-sidebar="' + id + '"]').removeClass('wsf-editing').blur();

		// Sidebar - Close all
		$('.wsf-sidebar').addClass('wsf-sidebar-closed').removeClass('wsf-sidebar-open');

		// Overflow hidden to improve touch scrolling in sidebar
		if(window.matchMedia('(max-width: 600px)').matches) {

			$('html').css({'overflow':''});
			$('body').css({'overflow':'','-webkit-overflow-scrolling':''});
		}
	}

	// Sidebar - Reset
	$.WS_Form.prototype.sidebar_reset = function() {

		// Reset
		if(window.matchMedia('(min-width: 851px)').matches) {

			// Get initial sidebar to open
			var id = ws_form_settings.sidebar_reset_id;
			ws_form_settings.sidebar_reset_id = 'toolbox';

			// Open sidebar
			var meta_key_open_function = 'sidebar_' + id + '_open';
			if(typeof(window[meta_key_open_function]) === 'function') {

				// Get dom objects
				var obj_outer = $('#wsf-sidebar-' + id);
				var obj_inner = $('.wsf-sidebar-inner', obj_outer);

				window[meta_key_open_function](this, null, null);

			} else {

				// Open
				this.sidebar_open(id);
			}

		} else {

			// Mobile
			var sidebar_current = $('#wsf-sidebars').attr('data-current');
			this.sidebar_close(sidebar_current);
		}
	}

	// Sidebars - Render
	$.WS_Form.prototype.sidebars_render = function() {

		var sidebars = $.WS_Form.settings_form['sidebars'];
		for(var sidebar_key in sidebars) {

			this.sidebar_render(sidebar_key);
		}

		this.sidebar_expand_contract_init();
	}

	// Sidebar - Expand / Contract init
	$.WS_Form.prototype.sidebar_expand_contract_init = function() {

		// Expand button event
		$('[data-action="wsf-sidebar-expand"]', $('#wsf-sidebars')).click(function() {

			$.WS_Form.this.sidebar_expanded_obj = $(this).closest('.wsf-sidebar');
			$.WS_Form.this.sidebar_expanded_obj.addClass('wsf-sidebar-expanded');
			$.WS_Form.this.sidebar_resize();
		});

		// Contract button event
		$('[data-action="wsf-sidebar-contract"]', $('#wsf-sidebars')).click(function() {

			$.WS_Form.this.sidebar_resize_reset();
		});
	}

	// Sidebar - Render
	$.WS_Form.prototype.sidebar_render = function(id) {

		var tab_count = 0;

		// Add wrapper
		this.sidebar_wrapper_add(id);

		var sidebar_config = $.WS_Form.settings_form['sidebars'][id];

		var sidebar_static = (typeof(sidebar_config.static) !== 'undefined') ? sidebar_config.static : false;
		var sidebar_buttons = (typeof(sidebar_config.buttons) !== 'undefined') ? sidebar_config.buttons : false;
		var sidebar_nav = (typeof(sidebar_config.nav) !== 'undefined') ? sidebar_config.nav : false;
		var sidebar_expand = (typeof(sidebar_config.expand) !== 'undefined') ? sidebar_config.expand : false;

		// Get dom objects
		var obj_outer = $('#wsf-sidebar-' + id);

		// Build form sidebar title
		var label = (typeof(sidebar_config.label) !== 'undefined') ? sidebar_config.label : 'Title';
		var icon = (typeof(sidebar_config.icon) !== 'undefined') ? sidebar_config.icon : 'default';

		// Build knowledge base HTML
		if((typeof(sidebar_config.kb_url) !== 'undefined')) {

			var kb_url = this.get_plugin_website_url(sidebar_config.kb_url, 'sidebar');
			var sidebar_kb_html = '<a class="wsf-kb-url" href="' + kb_url + '" target="_blank" title="' + this.language('field_kb_url') + '" tabindex="-1">' + this.svg('question-circle') + '</a>';
		}

		obj_outer.html(this.sidebar_title(this.svg(icon), label, '', sidebar_kb_html, '', sidebar_expand));


		// Create nav button
		if(sidebar_nav) {

			var sidebar_button_html = '<li data-action-sidebar="' + id + '" title="' + this.html_encode(label) + '">' + this.svg(icon) + '</li>';
			$('.wsf-heading .wsf-settings').append(sidebar_button_html);
		}

		// Render static sidebar content
		if(sidebar_static && (typeof(sidebar_config.meta) !== 'undefined')) {

			// Clear sidebar caches
			this.sidebar_cache_clear();
			var sidebar_return = this.sidebar_html('form', this.form_id, this.form, sidebar_config.meta, false, true, false, sidebar_buttons);
			var sidebar_html_tabs = sidebar_return.html_tabs;
			var sidebar_html = sidebar_return.html;
			var sidebar_html_buttons = sidebar_return.html_buttons;
			var sidebar_inits = sidebar_return.inits;

			// Tabs
			obj_outer.append(sidebar_html_tabs);

			// Inner
			obj_outer.append("<div class=\"wsf-sidebar-inner\">" + sidebar_html + '</div>');

			// Buttons
			obj_outer.append(sidebar_html_buttons);

			// Get inner
			var obj_inner = $('.wsf-sidebar-inner', obj_outer);

			// Initialize
			this.sidebar_inits(sidebar_inits, obj_outer, obj_inner);
		}

		// Open action
		$('[data-action-sidebar="' + id + '"]').click(function() {

			var id = $(this).attr('data-action-sidebar');
			var sidebar_current = $('[data-action-sidebar].wsf-editing').first().attr('data-action-sidebar');

			if(id != sidebar_current) {

				// Remove editing class on any other objects that are currently being edited
				var obj_editing = $('.wsf-editing', $('#wsf-form'));
				if(obj_editing.length) {

					// Process as cancel
					$.WS_Form.this.object_cancel(obj_editing);
				}

				// Open
				var meta_key_open_function = 'sidebar_' + id + '_open';
				if(typeof(window[meta_key_open_function]) === 'function') {

					// Get dom objects
					var obj_outer = $('#wsf-sidebar-' + id);
					var obj_inner = $('.wsf-sidebar-inner', obj_outer);

					window[meta_key_open_function]($.WS_Form.this, obj_inner, $(this));

				} else {

					// Open
					$.WS_Form.this.sidebar_open(id);
				}

			} else {

				// Toggle
				var meta_key_toggle_function = 'sidebar_' + id + '_toggle';
				if(typeof(window[meta_key_toggle_function]) === 'function') {

					// Get dom objects
					var obj_outer = $('#wsf-sidebar-' + id);
					var obj_inner = $('.wsf-sidebar-inner', obj_outer);

					window[meta_key_toggle_function]($.WS_Form.this, obj_inner, $(this));

				} else {

					// Reset sidebar
					$.WS_Form.this.sidebar_reset();
				}
			}
		});

		// Classes
		if(sidebar_buttons !== false) { obj_outer.addClass('wsf-sidebar-has-footer'); }
	}

	// Sidebar - Resize - Reset
	$.WS_Form.prototype.sidebar_resize_reset = function(sidebar_obj) {

		if($.WS_Form.this.sidebar_expanded_obj !== false) {

			$.WS_Form.this.sidebar_expanded_obj.css({'width': ''});
			$.WS_Form.this.sidebar_expanded_obj.removeClass('wsf-sidebar-expanded');
		}

		$.WS_Form.this.sidebar_expanded_obj = false;
	}

	// Sidebar - Resize
	$.WS_Form.prototype.sidebar_resize = function(sidebar_obj) {

		if($.WS_Form.this.sidebar_expanded_obj !== false) {

			var wpcontent_width = $('#wpcontent').outerWidth();
			$.WS_Form.this.sidebar_expanded_obj.width(wpcontent_width);
		}
	}

	// Sidebar - Add wrapper
	$.WS_Form.prototype.sidebar_wrapper_add = function(id) {

		var sidebar_html = '<div id="wsf-sidebar-' + id + "\" class=\"wsf-sidebar wsf-sidebar-closed\"></div>\n\n";
		$('#wsf-sidebars').append(sidebar_html);
	}

	// Sidebar - History - Init
	$.WS_Form.prototype.sidebar_form_history = function() {

		var obj = $('.wsf-form-history ul');

		// Clear history ul
		obj.empty();

		// Render each history li (last first)
		for(var i = (this.form_history.length - 1); i >= 0 ; i--) {

			var form_history_single = this.form_history[i].history;

			// Build description
			var history_description = '';
			var history_class = '';

			if(i == 0) {

				history_description = $.WS_Form.settings_form.history.initial;

			} else {

				// Get verb
				var verb = $.WS_Form.settings_form.history.method[form_history_single.method];

				// Get object name
				var direct_object = '';

				if(typeof form_history_single.id !== 'undefined') {

					var object_id = form_history_single.id;

					// Add label
					if(typeof form_history_single.label !== 'undefined') {

						direct_object = ' <span class="wsf-history-highlight">' + form_history_single.label + '</span>';
					}
				}

				history_description = verb + direct_object;
			}

			// Determine class
			if(this.history_index == i) { history_class = ' class="wsf-history-current"'; }

			// Build history HTML (form_history_single.date + '<br />' +)
			var history_html = '<li data-id="' + i + '"' + history_class + '><div class="date">' + form_history_single.time + '</div><div class="description">' + history_description + '</div></li>';

			// Add to obj
			obj.append(history_html);

			// Get newly added li
			var newLI = obj.find('li[data-id="' + i + '"]').first();

			// Mouse - Enter
			if(!this.touch_device) {

				newLI.mouseenter(function() {

					var history_index = $(this).attr('data-id');

					// Pull the history
					$.WS_Form.this.history_pull(history_index);

					// Update history classes
					$.WS_Form.this.sidebar_form_history_classes(history_index);
				});
			}

			// Click
			newLI.click(function() {

				$.WS_Form.this.history_index = parseInt($(this).attr('data-id'));

					// Pull the history
				$.WS_Form.this.history_pull($.WS_Form.this.history_index, true);

				// Update history classes
				$.WS_Form.this.sidebar_form_history_classes($.WS_Form.this.history_index);
			});
		}

		// Update history classes
		this.sidebar_form_history_classes();
	}

	// History - Update classes
	$.WS_Form.prototype.sidebar_form_history_classes = function(index) {

		if(typeof index === 'undefined') { var index = this.history_index; }

		$('.wsf-form-history ul li').each(function() {

			// Reset
			$(this).removeClass('wsf-history-current').removeClass('wsf-history-undo').removeClass('wsf-history-redo');

			var history_id = parseInt($(this).attr('data-id'));
			if(history_id == index) { $(this).addClass('wsf-history-current'); }
			if(history_id > index) { $(this).addClass('wsf-history-redo'); }
			if(history_id < index) { $(this).addClass('wsf-history-undo'); }
		});
	}

	// History - Push
	$.WS_Form.prototype.history_push = function(data) {

		// Clear history after current history index
		this.form_history = this.form_history.slice(0, (this.history_index + 1));

		var data = $.extend(true, {}, data); // Deep clone

		// Add data to form_history
		if(typeof data.history !== 'undefined') {

			if(data.history !== false) {

				// New history object
				var history_object = {

					'form':	data.form,
					'history':	data.history
				};

				// Push form to history
				this.form_history.push(history_object);
			}
		}

		// Calculate number of history steps
		this.history_index = (this.form_history.length - 1);

		// Re-render history
		this.sidebar_form_history();

		// Update undo / redo buttons
		this.undo_redo_update();

	}

	// History - Pull
	$.WS_Form.prototype.history_pull = function(index, push_to_api) {

		if(typeof push_to_api === 'undefined') { var push_to_api = false; }

		// Loader on
		this.loader_on();

		// Reset sidebar
		this.sidebar_reset();

		// Get previous state
		this.form = $.extend(true, {}, this.form_history[index]['form']);

		// Build data cache
		this.data_cache_build();

		if(push_to_api) {

			// Push form
			this.form_put(true, true, true);	// true = Full push / Does form_build as part of form_put

		} else {

			// Render form
			this.form_build();

			// Loader off
			this.loader_off();
		}
	}

	// Button - Undo (Reduces history_index by one)
	$.WS_Form.prototype.undo = function() {

		// Decrement history index
		if(this.history_index == 0) { return false; }

		// Make sure we aren't at the beginning of the history
		var history_length = this.form_history.length;

		// If there is 1 or less length, we cannot undo. Index 0 / Length 1 = original form.
		if(history_length <= 1) { return false; }

		// Reduce history index by 1
		this.history_index--;

		// Process the history at index
		this.history_pull(this.history_index, true);

		// Re-render history
		this.sidebar_form_history();

		// Re-render undo redo buttons
		this.undo_redo_update();
	}

	// Button - Redo (Increases history_index by one)
	$.WS_Form.prototype.redo = function() {

		// Calculate number of history steps
		var history_length = this.form_history.length;

		// Make sure we aren't already at end of history
		if(this.history_index == (history_length - 1)) { return false; }

		// Increase history index by 1
		this.history_index++;

		// Process the history at index
		this.history_pull(this.history_index, true);

		// Re-render history
		this.sidebar_form_history();

		// Re-render undo redo buttons
		this.undo_redo_update();
	}

	$.WS_Form.prototype.undo_redo_update = function() {

		// Calculate number of history steps
		var history_length = this.form_history.length;

		// Render undo button
		if(this.history_index > 0) {

			// Hide number if > 99
			if(this.history_index > 99) { var history_index = ''; } else { var history_index = this.history_index; }

			// Show undo button count
			$('[data-action="wsf-undo"]').removeClass('wsf-undo-inactive').find('.count').html(history_index);

		} else {

			// Hide undo button count
			$('[data-action="wsf-undo"]').addClass('wsf-undo-inactive').find('.count').html('');
		}

		// Render redo button
		if(this.history_index < (history_length - 1)) {

			// Hide number if > 99
			if(this.history_index > 99) { var history_index = ''; } else { var history_index = (history_length - 1) - this.history_index; }

			// Show redo button count
			$('[data-action="wsf-redo"]').removeClass('wsf-redo-inactive').find('.count').html(history_index);

		} else {

			// Hide redo button cont
			$('[data-action="wsf-redo"]').addClass('wsf-redo-inactive').find('.count').html('');
		}
	}

	// Popover - Render
	$.WS_Form.prototype.popover = function(message, buttons, obj, confirm_function) {

		var ws_this = this;

		// Reset popovers
		$('.wsf-popover').hide();
		$('.wsf-popover').html('');
		$('.wsf-group.wsf-ui-cancel').removeClass('wsf-ui-cancel');
		$('.wsf-section.wsf-ui-cancel').removeClass('wsf-ui-cancel');
		$('.wsf-field-wrapper.wsf-ui-cancel').removeClass('wsf-ui-cancel');
		$('.wsf-data-grid-group-tab.wsf-ui-cancel').removeClass('wsf-ui-cancel');

		var popover_obj = $('#wsf-popover');

		var popover_html = '<p>' + message + '</p>';

		// Add class
		obj.addClass('wsf-ui-cancel');

		// Get object type
		var object = this.get_object_type(obj);

		// Read data attributes
		var object_id = obj.attr('data-id');

		// Object specific functions
		switch(object) {

			case 'group' :

				// Add editing class to tab
				$('.wsf-group-tab[data-id="' + object_id + '"]').addClass('wsf-ui-cancel');

				break;
		}

		for(var key in buttons) {

			var button = buttons[key];

			var button_label = button.label;
			var button_action = button.action;
			var button_class = '';

			if(typeof button.class !== 'undefined') { button_class = button.class; }

			popover_html += '<button class="wsf-button wsf-button-small';
			if(button_class != '') { popover_html += ' ' + button_class; }
			popover_html += '" data-action="' + button_action + '">' + button_label + '</button>';
		}

		// Render popover
		popover_obj.html(popover_html);

		// Show popover
		popover_obj.css({ opacity: 0 });
		popover_obj.show();		// Need to show here prior to calculating width and height

		// Position popover
		var position = obj.offset();
		var popover_width = popover_obj.innerWidth();
		var popover_height = popover_obj.innerHeight();
		popover_height += 6;
		var object_width = obj.width();
		var window_width = $(window).width();

		// Calculate position of popover
		var position_left = position.left + (object_width / 2) - (popover_width / 2);
		var position_top = position.top - popover_height;

		// Ensure popover kept within boundaries
		var wpcontent_offset = $('#wpcontent').offset();
		if(position_left < wpcontent_offset.left) { position_left = wpcontent_offset.left; }
		if(position_top < wpcontent_offset.top) { position_top = wpcontent_offset.top; }
		if((position_left + popover_width) > window_width) { position_left = (window_width - popover_width); }

		// Position
		popover_obj.offset({ left: position_left, top: position_top });

		// Button event handles
		popover_obj.find('button[data-action]').each(function() {

			$(this).click(function(e) {

				e.preventDefault();

				var action = $(this).attr('data-action');

				switch(action) {

					case 'wsf-confirm' :

					if(typeof confirm_function === 'function') {

						confirm_function();
					}
					break;
				}

				// Hide popover
				popover_obj.hide();
				popover_obj.html('');

				obj.removeClass('wsf-ui-cancel');

				// Get object type
				var object = ws_this.get_object_type(obj);

				// Read data attributes
				var object_id = obj.attr('data-id');

				// Object specific functions
				switch(object) {

					case 'group' :

						// Add editing class to tab
						$('.wsf-group-tab[data-id="' + object_id + '"]').removeClass('wsf-ui-cancel');

						break;
				}
			});

		});

		popover_obj.css({ opacity: 1 });
	}

	// Settings - HTML
	$.WS_Form.prototype.settings_html = function(object, object_id, multiple) {

		var settings_html = '<ul class="wsf-settings wsf-settings-' + object + ' wsf-ui-cancel">';

		var buttons = $.WS_Form.settings_form[object].buttons;

		for(var key in buttons) {

			var button = buttons[key];

			var button_name = button['name'];
			var button_icon = button['icon'];

			var button_method = button['method'];
			if(!multiple && (button_method == 'clone')) { continue; }

			settings_html += '<li data-id="' + object_id + '" data-action="' + button_method + '" title="' + this.html_encode(button_name) + '">' + button_icon + '</li>';
		}

		settings_html += '</ul>';

		return settings_html;
	}

	// Settings - Events
	$.WS_Form.prototype.settings_events = function(obj, object) {

		obj.find('.wsf-settings-' + object + ' li').each(function() {

			$(this).click(function() {

				var method_function = 'wsf_' + object + '_' + $(this).attr('data-action');

				if(typeof window[method_function], 'function' !== 'undefined') {

					var singleObj = $('.wsf-' + object + (object == 'field' ? '-wrapper':'') + '[data-id="' + $(this).attr('data-id') + '"]').first();

					window[method_function]($.WS_Form.this, singleObj, $(this));
				}
			});
		});
	}

	// Column size - Change - HTML
	$.WS_Form.prototype.column_size_change_html = function() {

		return '<div class="wsf-column-size wsf-ui-cancel" title="' + this.language('column_size_change') + '"></div>';
	}

	// Column size - Change - Initialize
	$.WS_Form.prototype.column_size_change_init = function(obj) {

		// Mouse down event
		obj.find('.wsf-column-size').last().mousedown(function() {

			// Set mouseup mode
			$.WS_Form.this.mouseup_mode = 'column_size';

			// Add class to body
			$('body').addClass('wsf-column-size-change-body');

			// Add class to field
			obj.addClass('wsf-column-size-change');

			// Remember current object being resized
			$.WS_Form.this.column_size_change_obj = obj;

			// Get width of parent
			var ul_width = obj.parent().innerWidth();

			// Find meta for field
			var object_id = obj.attr('data-id');

			// Section
			if(obj.hasClass('wsf-section')) {

				// Add column helper class
				if($.WS_Form.settings_plugin.helper_columns == 'resize') { obj.closest('.wsf-group').addClass('wsf-column-helper'); }

				var object = $.WS_Form.this.section_data_cache[object_id];
			}

			// Field
			if(obj.hasClass('wsf-field-wrapper')) {

				// Add column helper class
				if($.WS_Form.settings_plugin.helper_columns == 'resize') { obj.closest('.wsf-section').addClass('wsf-column-helper'); }

				var object = $.WS_Form.this.field_data_cache[object_id];
			}

			// If using scratch, temporarily use scratch data
			if($.WS_Form.this.object_data_scratch !== false) {

				if($.WS_Form.this.object_data_scratch.id == object_id) {

					object = $.WS_Form.this.object_data_scratch;
				}
			}

			// Get frameworks
			var framework = $.WS_Form.settings_plugin.framework;

			// Get framework
			var framework = $.WS_Form.frameworks.types[framework];

			// Get number of columns
			var framework_column_count = framework['columns']['column_count'];

			// Get current framework breakpoints
			var framework_breakpoints = framework.breakpoints;

			// Get current breakpoint
			var breakpoint_current = $.WS_Form.this.get_object_meta_value($.WS_Form.this.form, 'breakpoint');

			// Fallback size
			$.WS_Form.this.column_size = framework_column_count;

			// Run through breakpoints and get size of closest sibling
			var offset = 0;
			for(var breakpoint_key in framework_breakpoints) {

				var breakpoint = framework_breakpoints[breakpoint_key];

				// Column size

				// Get meta key
				var meta_key = 'breakpoint_size_' + breakpoint_key;

				// Get meta value
				var meta_value = $.WS_Form.this.get_object_meta_value(object, meta_key, '', false);
				if(meta_value != '') {

					$.WS_Form.this.column_size = parseInt(meta_value);
				}

				// Offset

				// Get meta key
				var meta_key = 'breakpoint_offset_' + breakpoint_key;

				// Get meta value
				var meta_value = $.WS_Form.this.get_object_meta_value(object, meta_key, '', false);
				if(meta_value != '') {

					offset = parseInt(meta_value);
				}

				// If we are at the current breakpoint, break out of loop
				if(breakpoint_key == breakpoint_current) { break; }
			}

			// Reset old breakpoint size
			$.WS_Form.this.column_size_old = $.WS_Form.this.column_size;

			// Get object left position
			var obj_offset = obj.offset();
			var obj_left = obj_offset.left;

			// Calculate max size
			var column_size_max = framework_column_count - offset;

			// Resize
			$(document).bind('mousemove',function(e) {

				e.preventDefault();

				$.WS_Form.this.column_size_change(e, obj, obj_left, ul_width, object, column_size_max);
			});
		});
	}

	// Column size - Change (Called on mousemove)
	$.WS_Form.prototype.column_size_change = function(e, obj, obj_left, ul_width, object, column_size_max) {

		if(this.column_size_change_obj !== false) {

			// Get relative x position
			var mouse_offset_x = e.pageX - obj_left;
			if(mouse_offset_x < 0) { mouse_offset_x = 0; }

			// Get number of columns
			var framework_column_count = $.WS_Form.settings_plugin.framework_column_count;

			// Calculate new size
			var column_size_new = Math.round(((mouse_offset_x / ul_width) * framework_column_count) + 0.5);
			if(column_size_new < 1) { column_size_new = 1; }
			if(column_size_new > column_size_max) { column_size_new = column_size_max; }

			// If we have calculated a new size, write it to the form object and write the field class
			if(column_size_new != this.column_size) {

				// Remove old classes
				this.column_classes_render(obj, object, false);

				// Get current breakpoint
				var breakpoint = this.get_object_meta_value(this.form, 'breakpoint');

				// Update object
				this.column_size_set(object, breakpoint, column_size_new);

				// Add new classes
				this.column_classes_render(obj, object);

				var object_id = obj.attr('data-id');
				var object_type = this.get_object_type(obj);

				// Render framework sizes in sidebar?
				if(
					($.WS_Form.this.object_data_scratch !== false) &&
					$('#wsf-sidebar-' + object_type + ' .wsf-breakpoint-sizes').length
				) {

					if($.WS_Form.this.object_data_scratch.id == object_id) {

						// Render framework sizes in sidebar
						$('#wsf-sidebar-' + object_type + ' .wsf-breakpoint-sizes').get(0).render();
					}
				}

				// Remember column size
				this.column_size = column_size_new;
			}
		}
	}

	// Column size - Size - Release
	$.WS_Form.prototype.column_size_change_release = function() {

		var obj = this.column_size_change_obj;

		// Only runs if field resize in progress
		if(this.column_size_change_obj !== false) {

			// If field size has chanegd
			if(this.column_size != this.column_size_old) {

				// Loader on
				this.loader_on();

				// Get object ID
				var api_object_id = obj.attr('data-id');

				// Determine object being resized
				var api_object_type = false;
				var api_params = {};

				// Get current breakpoint
				var breakpoint = this.get_object_meta_value(this.form, 'breakpoint');

				// Section
				if(obj.hasClass('wsf-section')) {

					// Update object (To ensure we overwrite any updates from the API)
					this.column_size_set(this.section_data_cache[api_object_id], breakpoint, this.column_size);

					api_object_type = 'section';
					api_params = {'section': this.section_data_cache[api_object_id]};
				}

				// Field
				if(obj.hasClass('wsf-field-wrapper')) {

					// Update object (To ensure we overwrite any updates from the API)
					this.column_size_set(this.field_data_cache[api_object_id], breakpoint, this.column_size);

					api_object_type = 'field';
					api_params = {'field': this.field_data_cache[api_object_id]};
				}

				if(api_object_type === false) { return false; }

				// Add history method
				api_params['history_method'] = 'put_resize';

				// Call AJAX request
				this.api_call(api_object_type + '/' + api_object_id, 'PUT', api_params, function(response) {

					// Loader off
					$.WS_Form.this.loader_off();
				});
			}

			// Remove class from field
			obj.removeClass('wsf-column-size-change');

			// Remove column helper class
			if($.WS_Form.settings_plugin.helper_columns == 'resize') { $('.wsf-column-helper').removeClass('wsf-column-helper'); }

			// Remove class from body
			$('body').removeClass('wsf-column-size-change-body');

			// Reset
			this.column_size_change_obj = false;
			this.column_size = 0;
			this.mouseup_mode = false;

			// Unbind mousemove event
			$(document).unbind('mousemove');
		}
	}

	// Column size - Set
	$.WS_Form.prototype.column_size_set = function(object, breakpoint, size) {

		// Set breakpoint size meta in object
		this.set_object_meta_value(object, 'breakpoint_size_' + breakpoint, size);
	}

	// Column size - Change - HTML
	$.WS_Form.prototype.offset_change_html = function() {

		return '<div class="wsf-offset wsf-ui-cancel" title="' + this.language('offset_change') + '"></div>';
	}

	// Column size - Change - Initialize
	$.WS_Form.prototype.offset_change_init = function(obj) {

		// Mouse down event
		obj.find('.wsf-offset').last().mousedown(function() {

			// Set mouseup mode
			$.WS_Form.this.mouseup_mode = 'offset';

			// Add class to body
			$('body').addClass('wsf-offset-change-body');

			// Add class to field
			obj.addClass('wsf-offset-change');

			// Remember current object being resized
			$.WS_Form.this.offset_change_obj = obj;

			// Get width of parent
			var ul_width = obj.parent().innerWidth();

			// Find meta for field
			var object_id = parseInt(obj.attr('data-id'));

			// Section
			if(obj.hasClass('wsf-section')) {

				// Add column helper class
				if($.WS_Form.settings_plugin.helper_columns == 'resize') { obj.closest('.wsf-group').addClass('wsf-column-helper'); }

				var object = $.WS_Form.this.section_data_cache[object_id];
			}

			// Field
			if(obj.hasClass('wsf-field-wrapper')) {

				// Add column helper class
				if($.WS_Form.settings_plugin.helper_columns == 'resize') { obj.closest('.wsf-section').addClass('wsf-column-helper'); }

				var object = $.WS_Form.this.field_data_cache[object_id];
			}

			// If using scratch, temporarily use scratch data
			if($.WS_Form.this.object_data_scratch !== false) {

				if($.WS_Form.this.object_data_scratch.id == object_id) {

					object = $.WS_Form.this.object_data_scratch;
				}
			}

			// Get frameworks
			var framework = $.WS_Form.settings_plugin.framework;

			// Get framework
			var framework = $.WS_Form.frameworks.types[framework];

			// Get number of columns
			var framework_column_count = parseInt(framework['columns']['column_count']);

			// Get current framework breakpoints
			var framework_breakpoints = framework.breakpoints;

			// Get current breakpoint
			var breakpoint_current = $.WS_Form.this.get_object_meta_value($.WS_Form.this.form, 'breakpoint');

			// Fallback size
			$.WS_Form.this.offset = 0;

			// Run through breakpoints and get size of closest sibling
			var breakpoint_index = 0;
			for(var breakpoint_key in framework_breakpoints) {

				var breakpoint = framework_breakpoints[breakpoint_key];

				if(breakpoint_index == 0) {

					var column_size = (typeof breakpoint.column_size_default !== 'undefined') ? (breakpoint.column_size_default == 'column_count' ? framework_column_count : breakpoint.column_size_default) : column_size_default;
				}

				// Offset

				// Get meta key
				var meta_key = 'breakpoint_offset_' + breakpoint_key;

				// Get meta value
				var meta_value = $.WS_Form.this.get_object_meta_value(object, meta_key, '', false);
				if(meta_value != '') {

					$.WS_Form.this.offset = parseInt(meta_value);
				}

				// Column size

				// Get meta key
				var meta_key = 'breakpoint_size_' + breakpoint_key;

				// Get meta value
				var meta_value = $.WS_Form.this.get_object_meta_value(object, meta_key, '', false);
				if(meta_value != '') {

					column_size = parseInt(meta_value);
				}

				// If we are at the current breakpoint, break out of loop
				if(breakpoint_key == breakpoint_current) { break; }

				breakpoint_index++;
			}

			// Reset old breakpoint size
			$.WS_Form.this.offset_old = $.WS_Form.this.offset;

			// Get object left position
			var obj_offset = obj.offset();
			var obj_left = obj_offset.left;

			// Calculate max size
			var offset_max = framework_column_count - column_size;

			// Resize
			$(document).bind('mousemove',function(e) {

				e.preventDefault();

				$.WS_Form.this.offset_change(e, obj, obj_left, ul_width, object, offset_max);
			});
		});
	}

	// Column size - Change (Called on mousemove)
	$.WS_Form.prototype.offset_change = function(e, obj, obj_left, ul_width, object, offset_max) {

		if(this.offset_change_obj !== false) {

			// Get relative x position
			var mouse_offset_x = e.pageX - obj_left;

			// Get number of columns
			var framework_column_count = $.WS_Form.settings_plugin.framework_column_count;

			// Calculate new size
			var offset_new = $.WS_Form.this.offset_old + Math.round(((mouse_offset_x / ul_width) * framework_column_count) + 0.5) - 1;

			if(offset_new < 0) { offset_new = 0; }
			if(offset_new > offset_max) { offset_new = offset_max; }

			// If we have calculated a new size, write it to the form object and write the field class
			if(offset_new != this.offset) {

				// Remove old classes
				this.column_classes_render(obj, object, false);

				// Get current breakpoint
				var breakpoint = this.get_object_meta_value(this.form, 'breakpoint');

				// Update object
				this.offset_set(object, breakpoint, offset_new);

				// Add new classes
				this.column_classes_render(obj, object);

				var object_id = parseInt(obj.attr('data-id'));
				var object_type = this.get_object_type(obj);

				// Render framework sizes in sidebar?
				if(
					($.WS_Form.this.object_data_scratch !== false) &&
					$('#wsf-sidebar-' + object_type + ' .wsf-breakpoint-sizes').length
				) {

					if($.WS_Form.this.object_data_scratch.id == object_id) {

						// Render framework sizes in sidebar
						$('#wsf-sidebar-' + object_type + ' .wsf-breakpoint-sizes').get(0).render();
					}
				}

				// Remember breakpoint
				this.offset = offset_new;
			}
		}
	}

	// Column size - Size - Release
	$.WS_Form.prototype.offset_change_release = function() {

		var obj = this.offset_change_obj;

		// Only runs if field resize in progress
		if(this.offset_change_obj !== false) {

			// If field size has chanegd
			if(this.offset != this.offset_old) {

				// Loader on
				this.loader_on();

				// Get object ID
				var api_object_id = parseInt(obj.attr('data-id'));

				// Determine object being resized
				var api_object_type = false;
				var api_params = {};

				// Get current breakpoint
				var breakpoint = this.get_object_meta_value(this.form, 'breakpoint');

				// Section
				if(obj.hasClass('wsf-section')) {

					// Update object (To ensure we overwrite any updates from the API)
					this.offset_set(this.section_data_cache[api_object_id], breakpoint, this.offset);

					api_object_type = 'section';
					api_params = {'section': this.section_data_cache[api_object_id]};
				}

				// Field
				if(obj.hasClass('wsf-field-wrapper')) {

					// Update object (To ensure we overwrite any updates from the API)
					this.offset_set(this.field_data_cache[api_object_id], breakpoint, this.offset);

					api_object_type = 'field';
					api_params = {'field': this.field_data_cache[api_object_id]};
				}

				if(api_object_type === false) { return false; }

				// Add history method
				api_params['history_method'] = 'put_offset';

				// Call AJAX request
				this.api_call(api_object_type + '/' + api_object_id, 'PUT', api_params, function(response) {

					// Loader off
					$.WS_Form.this.loader_off();
				});
			}

			// Remove class from field
			obj.removeClass('wsf-offset-change');

			// Remove column helper class
			if($.WS_Form.settings_plugin.helper_columns == 'resize') { $('.wsf-column-helper').removeClass('wsf-column-helper'); }

			// Remove class from body
			$('body').removeClass('wsf-offset-change-body');

			// Reset
			this.offset_change_obj = false;
			this.offset = 0;
			this.mouseup_mode = false;

			// Unbind mousemove event
			$(document).unbind('mousemove');
		}
	}

	// Column size - Set
	$.WS_Form.prototype.offset_set = function(object, breakpoint, size) {

		// Set breakpoint size meta in object
		this.set_object_meta_value(object, 'breakpoint_offset_' + breakpoint, size.toString());
	}

	// Initialize jQuery UI
	$.WS_Form.prototype.init_ui = function() {

		// JQuery UI Sortable

		// Groups (tabs) sortable
		this.groups_sortable();

		// Sections sortable
		this.sections_sortable();

		// Fields sortable
		this.fields_sortable();


		// JQuery UI Droppable

		// Group tabs droppable
		this.group_tabs_droppable();


		// Blank fields
		this.object_blank_update();
	}

	// Make form groups sortable
	$.WS_Form.prototype.groups_sortable = function() {

		// Get wsf-group-tabs
		var obj_group_tabs = $('.wsf-group-tabs');

		// Section - Sortable (Make fields within section sortable)
		obj_group_tabs.sortable({

			tolerance:			'pointer',
			containment: 		'parent',
			scroll: 			false,
			forceHelperSize:	true,
			revert:				200,
			placeholder:		'wsf-group-tab-placeholder',
			cancel:				'.wsf-ui-cancel, input[type=text]:not([readonly])',
			items:				'> li:not(.wsf-ui-cancel)',

			start: function (event, ui) {

				// Get object being sorted
				var obj = ui.placeholder;
				var height = ui.helper.height();
				var width = ui.helper.width();
				var styles = [
				'height: ' + height + 'px',
				'width: ' + width + 'px'
				].join(';');

				// Apply styles
				obj.attr('style', styles);

				// Get next sibling ID (0 = Last or only element in group)
				$.WS_Form.this.next_sibling_id_old = (typeof obj.next().attr('data-id') !== 'undefined') ? obj.next().attr('data-id') : 0;
			},

			stop: function(event, ui) {

				// Push field sort index to AJAX
				$.WS_Form.this.group_put_sort_index(ui.item);
			}

		}).disableSelection();
	}

	// Make group sections sortable
	$.WS_Form.prototype.sections_sortable = function() {

		// Group - Sortable (Make sections within group sortable)
		$('.wsf-sections').sortable({

			tolerance: 			'pointer',
			forceHelperSize: 	true,
			revert: 			200,
			placeholder: 		'wsf-section-placeholder',
			items: 				'> li',
			cancel: 			'.wsf-section-blank, .wsf-ui-cancel, input[type=text]:not([readonly])',
			connectWith: 		'.wsf-sections',

			start: function (event, ui) {

				// Get object being sorted
				var obj = ui.placeholder;

				// Build placeholder style
				var height = ui.helper.height();
				var margin_left = ui.helper.css('background-size');
				var width = ui.helper.css('max-width');
				var style = $.WS_Form.settings_plugin.flex_admin ? ['height: ' + height + 'px', '-webkit-box-flex: 0', '-ms-flex: 0 0 ' + width, 'flex: 0 0 ' + width, 'margin-left: ' + margin_left, 'max-width: ' + width].join(';') : ['height: ' + height + 'px', 'margin-left: ' + margin_left, 'width: ' + width].join(';');

				// Apply style
				obj.attr('style', style);

				// Get next sibling ID (0 = Last or only element in group)
				$.WS_Form.this.next_sibling_id_old = (typeof obj.next().attr('data-id') !== 'undefined') ? obj.next().attr('data-id') : 0;
				$.WS_Form.this.group_id_old = obj.closest('.wsf-group').attr('data-id');

				// Set field being dragged
				$.WS_Form.this.section_field_being_dragged = true;
			},

			over: function(event, ui) {

				$.WS_Form.this.object_blank_update();

				ui.helper.css('height', 'auto');
				var height = ui.helper.height();
				ui.placeholder.height(height);

			},

			stop: function(event, ui) {

				$.WS_Form.this.object_blank_update();

				// Push field sort index to AJAX
				$.WS_Form.this.section_put_sort_index(ui.item);

				$.WS_Form.this.section_field_being_dragged = false;
			}

		}).disableSelection();
	}

	// Make section fields sortable
	$.WS_Form.prototype.fields_sortable = function() {

		// Section - Sortable (Make fields within section sortable)
		$('.wsf-fields').sortable({

			tolerance: 			'pointer',
			forceHelperSize: 	true,
			revert: 			200,
			placeholder: 		'wsf-field-placeholder',
			items: 				'> li',
			cancel: 			'.wsf-field-blank, .wsf-ui-cancel, input[type=text]:not([readonly])',
			connectWith: 		'.wsf-fields',

			start: function (event, ui) {

				if(!$.WS_Form.this.dragged_field) {

					// Get object being sorted
					var obj = ui.placeholder;

					// Build placeholder style
					var height = ui.helper.height();
					var margin_left = ui.helper.css('background-size');
					var width = ui.helper.css('max-width');
					var style = $.WS_Form.settings_plugin.flex_admin ? ['height: ' + height + 'px', '-webkit-box-flex: 0', '-ms-flex: 0 0 ' + width, 'flex: 0 0 ' + width, 'margin-left: ' + margin_left, 'max-width: ' + width].join(';') : ['height: ' + height + 'px', 'margin-left: ' + margin_left, 'width: ' + width].join(';');

					// Apply style
					obj.attr('style', style);

					// Get next sibling ID (0 = Last or only element in group)
					$.WS_Form.this.next_sibling_id_old = (typeof obj.next().attr('data-id') !== 'undefined') ? obj.next().attr('data-id') : 0;
					$.WS_Form.this.section_id_old = obj.closest('.wsf-section').attr('data-id');

					$.WS_Form.this.section_field_being_dragged = true;
				}
			},

			over: function(event, ui) {

				$.WS_Form.this.object_blank_update();

				ui.helper.css('height', 'auto');
				var height = ui.helper.height();
				ui.placeholder.height(height);

				if($.WS_Form.this.dragged_field) {

					$.WS_Form.this.dragged_field.width($(ui.placeholder).width());

				} else {

					ui.helper.width($(ui.placeholder).width());
				}
			},

			out: function(event, ui) {

				$.WS_Form.this.object_blank_update();
			},

			stop: function(event, ui) {

				$.WS_Form.this.object_blank_update();

				if($.WS_Form.this.dragged_field) {

					// Remove style so li inherits fields styling
					$.WS_Form.this.dragged_field.removeAttr('style');

					// Push new field to AJAX
					$.WS_Form.this.field_post($.WS_Form.this.dragged_field);

					// Init UI
					$.WS_Form.this.init_ui();

				} else {

					// Push field sort index to AJAX
					$.WS_Form.this.field_put_sort_index(ui.item);

					$.WS_Form.this.section_field_being_dragged = false;
				}

				// Reset dragged_field
				$.WS_Form.this.dragged_field = null;
			}

		}).disableSelection();
	}


	// Make the group tabs droppable so that sections and fields can be dropped into them
	$.WS_Form.prototype.group_tabs_droppable = function() {

		$('.wsf-group-tab').droppable({

			accept: '.wsf-section, .wsf-fields .wsf-field-wrapper',
			hoverClass: 'wsf-group-tab-hover',
			tolerance: 'pointer',
			over: function(event, ui) {

				// Show tab
				$('a', event.target).click();

				// Move helper to current group
				if(ui.helper.hasClass('wsf-section')) { var ul_dummy_selector = '.wsf-sections'; }
				if(ui.helper.hasClass('wsf-field-wrapper')) { var ul_dummy_selector = '.wsf-fields'; }

				if(typeof ul_dummy_selector !== 'undefined') {

					// Append to appropriate dummy UL container
					var ul_dummy = $($(this).find('a').attr('href')).find(ul_dummy_selector).first();
					ui.draggable.appendTo(ul_dummy).show();

					// Refresh positions
					$('.wsf-sections').sortable('refreshPositions');
					$('.wsf-fields').sortable('refreshPositions');
				}
			}
		});
	}

	// Get SVG
	$.WS_Form.prototype.svg = function(id) {

		return (typeof $.WS_Form.settings_form.icons[id] !== 'undefined') ? $.WS_Form.settings_form.icons[id] : $.WS_Form.settings_form.icons.default;
	}

	// Test API
	$.WS_Form.prototype.api_test = function(success_callback, error_callback) {

		this.api_call('helper/test', 'GET', false, function(response) {

			if(
				(typeof(response.error) !== 'undefined') &&
				!response.error
			) {

				success_callback();

			} else {

				error_callback();
			}

		}, error_callback);
	}

	// Detect framework
	$.WS_Form.prototype.framework_detect = function(success_callback, error_callback) {

		this.api_call('helper/framework_detect', 'POST', false, function(response) {

			var framework = {'type': response.data.type, 'name': response.data.framework.name};
			success_callback(framework);

		}, error_callback);
	}

	// Push setup
	$.WS_Form.prototype.setup_push = function(params, success_callback, error_callback) {

		this.api_call('helper/setup_push', 'POST', params, success_callback, error_callback);
	}

	// Wizard
	$.WS_Form.prototype.wizard = function() {

		// Tabs (Run initially to avoid jolt in tabs)
		$('#wsf-form-add').tabs({

			activate: function(event, ui) {

				var action_populated = ui.newPanel.attr('data-populated');
				if(action_populated === 'true') { return; }

				var action_id = ui.newPanel.attr('data-action-id');
				if(action_id === undefined) { return; }

				// Populate wizards
				$.WS_Form.this.wizards_populate(action_id);
			}
		});

		// Click event - Add blank
		$('[data-action="wsf-add-blank"]').click(function() {

			// Scroll to top of page
			$(window).scrollTop();

			// Loader on
			$.WS_Form.this.loader_on();

			// Show loading message (Avoids double clicks)
			$.WS_Form.this.wizards_loading_on();

			$('#ws-form-action').val('wsf-add-blank');
			$('#ws-form-action-do').submit();
		});

		// Click event - Add from template
		$('li:not(.wsf-pro-required) [data-action="wsf-add-wizard"]').click(function() {

			// Scroll to top of page
			$(window).scrollTop();

			// Loader on
			$.WS_Form.this.loader_on();

			// Show loading message (Avoids double clicks)
			$.WS_Form.this.wizards_loading_on();

			var id = $(this).attr('data-id');

			$('#ws-form-action').val('wsf-add-wizard');
			$('#ws-form-id').val(id);
			$('#ws-form-action-do').submit();
		});

		// Modal - Close
		$('[data-action="wsf-close"]', $('#wsf-list-sub-modal')).click(function() {

			// Close modal
			$('#wsf-list-sub-modal').hide();
			$('#wsf-list-sub-modal-backdrop').hide();
			$(document).keydown = null;
		});

		// Click event - Add from action
		$('[data-action="wsf-add-wizard-action-modal"]', $('#wsf-list-sub-modal')).click(function() {

			// Scroll to top of page
			$(window).scrollTop();

			var action_id = $(this).attr('data-action-id');
			var list_id = $(this).attr('data-list-id');
			var list_sub_id = $('#wsf-list-sub-id').val();
			if(list_sub_id == '') { return; }

			// Loader on
			$.WS_Form.this.loader_on();

			// Close modal
			$('#wsf-list-sub-modal').hide();
			$('#wsf-list-sub-modal-backdrop').hide();
			$(document).keydown = null;

			// Show loading message (Avoids double clicks)
			$.WS_Form.this.wizards_loading_on();

			$('#ws-form-action').val('wsf-add-action');
			$('#ws-form-action-id').val(action_id);
			$('#ws-form-list-id').val(list_id);
			$('#ws-form-list-sub-id').val(list_sub_id);
			$('#ws-form-action-do').submit();
		});

		$('#wsf-list-sub-id').change(function() {

			$('#wsf-modal-buttons-list-sub button').attr('disabled', ($(this).val() == '') ? '' : false);
		});

		// Open modal
		$(document).on('click', '[data-action="wsf-list-sub-action"]', function(e) {

			e.preventDefault();

			// Get list sub label
			var list_sub_modal_label_div = $(this).closest('[data-action-list-sub-modal-label]');
			var list_sub_modal_label = list_sub_modal_label_div ? list_sub_modal_label_div.attr('data-action-list-sub-modal-label') : 'List';

			// Set modal title
			$('#wsf-list-sub-modal .wsf-modal-title').html(list_sub_modal_label);

			// Setup create button
			var action_id = $(this).attr('data-action-id');
			var list_id = $(this).attr('data-list-id');
			$('#wsf-modal-buttons-list-sub button').attr('data-action-id', action_id);
			$('#wsf-modal-buttons-list-sub button').attr('data-list-id', list_id);

			// Disable create button
			$('#wsf-modal-buttons-list-sub button').attr('disabled', '');

			// Show modal
			$('#wsf-list-sub-modal-backdrop').show();
			$('#wsf-list-sub-modal').show();

			// Escape key
			$(document).keydown(function(e) {

				if(e.keyCode == 27) { 

					// Close modal
					wsf_add_form_modal_close();
				}
			});

			// Get action and list ID
			var action_id = $(this).attr('data-action-id');
			var list_id = $(this).attr('data-list-id');

			// Get API call path
			var api_call_path = $.WS_Form.this.action_api_method_path(action_id, 'list_subs_fetch', list_id);

			// Clear select
			$('#wsf-list-sub-id').empty().append($("<option />").val('').text($.WS_Form.this.language('list_subs_call')));;

			// Make API call
			$.WS_Form.this.api_call(api_call_path, 'GET', false, function(response) {

				if(response.error) {

					$.WS_Form.this.loader_off();

					// Hide modal
					$('#wsf-list-sub-modal-backdrop').hide();
					$('#wsf-list-sub-modal').hide();

					// Throw error
					$.WS_Form.this.error('error_action_list_sub_get');

					return;
				}

				// Populate select
				$('#wsf-list-sub-id').empty().append($("<option />").val('').text($.WS_Form.this.language('list_subs_select')));;

				var list_subs = response.data;

				for(var list_sub_index in list_subs) {

					var list_sub = list_subs[list_sub_index];

					$('#wsf-list-sub-id').append($("<option />").val(list_sub.id).text(list_sub.label));
				}

				$.WS_Form.this.loader_off();
			});
		});

		// Click modal backdrop
		$(document).on('click', '#wsf-list-sub-modal-backdrop', function(e) {

			// Close modal
			wsf_add_form_modal_close();
		});

		// Action API method events
		var action_obj = $('#wsf-form-add');
		this.api_reload_init(action_obj, function(obj, action_id, action_api_method) {

			switch(action_api_method) {

				case 'lists_fetch' :

					// Populate wizards
					$.WS_Form.this.wizards_populate(action_id);
					break;
			}

		}, null, false)

		// Get configuration
		this.get_configuration(function() {

			$.WS_Form.this.loader_off();
		});
	}

	// Wizard - Show loading div
	$.WS_Form.prototype.wizards_loading_on = function(action_id) {

		$('#wsf-form-add').hide();
		$('#wsf-form-add-loading').show();
	}

	// Wizards - Populate
	$.WS_Form.prototype.wizards_populate = function(action_id) {

		// Loader on
		$.WS_Form.this.loader_on();

		// Populate wizards
		var wizard_templates_obj = $('#wsf_wizard_category_' + action_id + ' ul.wsf-templates');

		// Get wizards
		$.WS_Form.this.api_call('wizard/action/' + action_id, 'GET', [], function(response) {

			if(response.error) {

				// Loader off
				$.WS_Form.this.loader_off();
				return;
			}

			var wizards = response.data;

			var wizard_content = '';
			var wizard_count = 0;

			for(var wizard_index in wizards) {

				var wizard = wizards[wizard_index];

				var wizard_id = wizard.id;
				var wizard_label = wizard.label;
				var wizard_svg = wizard.svg;
				var wizard_list_sub = (typeof(wizard.list_sub) !== 'undefined') ? wizard.list_sub : false;
				var wizard_data_action = wizard_list_sub ? 'wsf-list-sub-action' : 'wsf-add-wizard-action';

				wizard_content += '<li><div class="wsf-template" data-action="' + wizard_data_action + '" data-action-id="' + $.WS_Form.this.html_encode(action_id) + '" data-list-id="' + $.WS_Form.this.html_encode(wizard_id) + '" title="' + $.WS_Form.this.html_encode(wizard_label) + '">';
				wizard_content += wizard_svg;
				wizard_content += '</div>';
				wizard_content += '<button class="wsf-button wsf-button-primary wsf-button-full" data-action="' + wizard_data_action + '" data-action-id="' + $.WS_Form.this.html_encode(action_id) + '" data-list-id="' + $.WS_Form.this.html_encode(wizard_id) + '">' + ws_form_settings_language_form_add_create + '</button>';
				wizard_content += '</li>';

				wizard_count++;
			}

			if(wizard_count > 0) {

				// Delete wizards (2nd li onwards)				
				$('li:not(:first-child)', wizard_templates_obj).remove();

				// Populate wizards
				wizard_templates_obj.append(wizard_content);
			}

			// Mark as populated so AJAX is not called again
			$('#wsf_wizard_category_' + action_id).attr('data-populated', 'true');

			// Click event - Add from action
			$('[data-action="wsf-add-wizard-action"]', wizard_templates_obj).click(function() {

				// Loader on
				$.WS_Form.this.loader_on();

				// Show loading message (Avoids double clicks)
				$.WS_Form.this.wizards_loading_on();

				var action_id = $(this).attr('data-action-id');
				var list_id = $(this).attr('data-list-id');

				$('#ws-form-action').val('wsf-add-action');
				$('#ws-form-action-id').val(action_id);
				$('#ws-form-list-id').val(list_id);
				$('#ws-form-action-do').submit();
			})

			// Loader off
			$.WS_Form.this.loader_off();
		});
	}

	// WP List Table - Form
	$.WS_Form.prototype.wp_list_table_form = function() {

		this.form_id = 0;

		// Form actions
		$('[data-action]', $('#wsf-form-list-table')).click(function() {

			$('#wsf-action').val($(this).attr('data-action'));
			$('#wsf-id').val($(this).attr('data-id'));
			$('#wsf-action-do').submit();
		});

		// Form upload
		var form_list_table_obj = $('#wsf-form-list-table');
		$('table.wp-list-table', form_list_table_obj).wrap('<div id="wsf-form-table"></div>');
		var form_table_obj = $('#wsf-form-table');
		form_table_obj.append('<div class="wsf-form-upload-json-window"><div class="wsf-form-upload-json-window-content"><h1></h1><div class="wsf-uploads"></div></div></div>');

		// Drag enter
		form_table_obj.on('dragenter', function (e) {

			e.stopPropagation();
			e.preventDefault();
			$('.wsf-form-upload-json-window', $(this)).fadeIn(200);
		});

		// Drag over
		$('.wsf-form-upload-json-window', form_table_obj).on('dragover', function (e) {

			e.stopPropagation();
			e.preventDefault();
		});

		// Drop
		$('.wsf-form-upload-json-window', form_table_obj).on('drop', function (e) {

			e.preventDefault();

			var files = e.originalEvent.dataTransfer.files;
			$.WS_Form.this.form_upload_json(files, $(this), function() {

				location.reload();
			});
		});

		// Drag leave
		$('.wsf-form-upload-json-window', form_table_obj).on('dragleave', function (e) {

			$('.wsf-form-upload-json-window', form_table_obj).fadeOut(200);
		});

		// Upload
		$('[data-action-button="wsf-form-upload"]').click(function(e) {

			// Click file input
			$('input[id="wsf-form-upload-file"]').click();
		});
		$('input[id="wsf-form-upload-file"]').change(function() {

			var files = $('input[id="wsf-form-upload-file"]').prop("files");

			if(files.length > 0) {

				var form_upload_window = $('.wsf-form-upload-json-window', form_table_obj);
				form_upload_window.fadeIn(200);
				$.WS_Form.this.form_upload_json(files, form_upload_window, function() {

					location.reload();
				});
			}
		});

		// Toggle status
		$('[data-action-ajax="wsf-form-status"]').click(function() {

			var form_id = $(this).attr('data-id');
			var status = $(this).is(':checked');

			// Loader on
			$.WS_Form.this.loader_on();

			var status_label_obj = $('#wsf-status-' + form_id + '-label');

			if(status) {

				// Set title
				status_label_obj.attr('title', $.WS_Form.this.language('publish'))

				// Publish
				$.WS_Form.this.api_call('form/' + form_id + '/publish/', 'PUT', false, function(response) {

					// Loader off
					$.WS_Form.this.loader_off();
				});

			} else {

				// Set title
				status_label_obj.attr('title', $.WS_Form.this.language('draft'));

				// Draft
				$.WS_Form.this.api_call('form/' + form_id + '/draft/', 'PUT', false, function(response) {

					// Loader off
					$.WS_Form.this.loader_off();
				});
			}
		});

		// Get form locations
		$('[data-action-ajax="wsf-form-locate"]').click(function(e) {

			e.preventDefault();

			// Remember button object
			var location_button_obj = $(this);

			// Blur link so hover does not get stuck
			location_button_obj.blur();

			// Get form ID
			var form_id = $(this).attr('data-id');

			// Loader on
			$.WS_Form.this.loader_on();

			// Get locations
			$.WS_Form.this.api_call('form/' + form_id + '/locations/', 'GET', false, function(response) {

				// Loader off
				$.WS_Form.this.loader_off();

				var form_location_array = [];

				if(typeof(response[form_id]) !== 'undefined') {

					// Render each location the form was found
					for(var form_location_id in response[form_id]) {

						var form_location = response[form_id][form_location_id];
						var form_location_id = form_location.id;
						var form_location_type = form_location.type;
						var form_location_type_name = $.WS_Form.this.html_encode(form_location.type_name);
						var form_location_title = $.WS_Form.this.html_encode(form_location.title);

						switch(form_location.type) {

							case 'widget' :

								form_location_array.push(form_location_type_name + ': ' + '<a href="widgets.php">' + form_location_title + '</a>');
								break;

							default :

								form_location_array.push(form_location_type_name + ': ' + '<a href="post.php?post=' + form_location_id + '&post_type=' + form_location_type + '&action=edit">' + form_location_title + '</a>');
								break;
						}
					}
				}

				if(form_location_array.length == 0) {

					var location_html = '<div class="wsf-helper">' + $.WS_Form.this.language('form_location_not_found') + '</div>';

				} else {

					var location_html = '<div class="wsf-helper">' + $.WS_Form.this.language('form_location_found', '<span class="ws-form-location">' + form_location_array.join(', </span><span class="wsf-form-location">'), false) + '</div>';
				}

				var row_actions_obj = location_button_obj.closest('.row-actions');
				var td_obj = location_button_obj.closest('td');
				var form_locations_obj = $('.wsf-form-locations', td_obj);

				if(form_locations_obj.length) {

					form_locations_obj.html(location_html);

				} else {

					row_actions_obj.before('<div class="wsf-form-locations">' + location_html + '</div>');
				}
			});
		});

		// Copy shortcode to clipboard
		$('[data-action-copy]', $('#wsf-form-list-table')).click(function(e) {

			e.preventDefault();

			// Get text to copy
			var copy_text = $(this).html();
			if(copy_text === undefined) { return; }

			// Copy text to clipboard
			var copy_to_obj = $('<input>');
			$('body').append(copy_to_obj);
			copy_to_obj.val(copy_text).select();
			document.execCommand('copy');
			copy_to_obj.remove();

			// Show copied message
			var shortcode_td = $(this).closest('td');
			shortcode_td.append('<div class="wsf-helper">' + $.WS_Form.this.language('shortcode_copied') + '</div>');
			setTimeout(function() { $('.wsf-helper', shortcode_td).remove(); }, 2000);
		});
	}

	// WP List Table - Submit
	$.WS_Form.prototype.wp_list_table_submit = function(form_id) {

		// Hide ID column hide option
		$('#id-hide').parent().remove();

		// Change form
		$('#wsf_filter_id').change(function() {

			$.WS_Form.this.wp_list_table_filter_do()
		});

		// Set form ID
		this.form_id = form_id;
		if(this.form_id == 0) { return; }

		// Hide column
		$(document).on('click', '.hide-column-tog', function() {

			var params = {'hidden': columns.hidden()};

			$.WS_Form.this.api_call('helper/user_meta_hidden_column', 'POST', params, null, null, false, true, true);
		});

		// Toggle status
		$('[data-action-ajax="wsf-submit-starred"]').click(function() {

			var submit_id = $(this).attr('data-id');
			var starred = $(this).hasClass('wsf-starred-on');
			var starred_obj = $(this);

			// Loader on
			$.WS_Form.this.loader_on();

			if(starred) {

				// Remove class
				$(this).removeClass('wsf-starred-on');

				// Set title
				starred_obj.attr('title', $.WS_Form.this.language('starred_off'))

				// Starred - On
				$.WS_Form.this.api_call('submit/' + submit_id + '/starred/off/', 'PUT', false, function(response) {

					// Loader off
					$.WS_Form.this.loader_off();
				});

			} else {

				// Add class
				$(this).addClass('wsf-starred-on');

				// Set title
				starred_obj.attr('title', $.WS_Form.this.language('starred_on'));

				// Starred - Off
				$.WS_Form.this.api_call('submit/' + submit_id + '/starred/on/', 'PUT', false, function(response) {

					// Loader off
					$.WS_Form.this.loader_off();
				});
			}
		});

		// Toggle viewed
		$('[data-action-ajax="wsf-submit-viewed"]').click(function() {

			var submit_id = $(this).attr('data-id');
			var viewed = !$(this).closest('tr').hasClass('wsf-submit-not-viewed');
			var viewed_obj = $(this);

			// Loader on
			$.WS_Form.this.loader_on();

			if(viewed) {

				// Remove class
				viewed_obj.closest('tr').addClass('wsf-submit-not-viewed');

				// Set link text
				viewed_obj.html($.WS_Form.this.language('viewed_off'));

				// Mark as unread
				$.WS_Form.this.api_call('submit/' + submit_id + '/viewed/off/', 'PUT', false, function(response) {

					// Update submission count in admin menu
					window.wsf_admin_wp_count_submit_unread_ajax($.WS_Form.this.form_id);

					// Loader off
					$.WS_Form.this.loader_off();
				});

			} else {

				// Add class
				viewed_obj.closest('tr').removeClass('wsf-submit-not-viewed');

				// Set link text
				viewed_obj.html($.WS_Form.this.language('viewed_on'));

				// Mark as read
				$.WS_Form.this.api_call('submit/' + submit_id + '/viewed/on/', 'PUT', false, function(response) {

					// Update submission count in admin menu
					window.wsf_admin_wp_count_submit_unread_ajax($.WS_Form.this.form_id);

					// Loader off
					$.WS_Form.this.loader_off();
				});
			}
		});

		// Get configuration
		this.get_configuration(function() {

			// Get published form
			$.WS_Form.this.api_call('form/' + $.WS_Form.this.form_id + '/published', 'GET', false, function(response) {

				// Store form data
				$.WS_Form.this.form = response.form;

				// Initialize the submit page
				$.WS_Form.this.wp_list_table_submit_init();

				// Initialize key down events
				$.WS_Form.this.keydown_events_init();

				// Loader off
				$.WS_Form.this.loader_off()

			}, null, false, false, true);
		});
	}

	// WP List Table - Submit - Init
	$.WS_Form.prototype.wp_list_table_submit_init = function() {

		// Set as sidebar closed
		$('#wpcontent').addClass('wsf-sidebar-closed');

		// Set globals
		this.set_globals('ws-form', 'public');

		// Build data cache
		this.data_cache_build();

		// Build field type cache
		this.field_type_cache_build();

		// Check for a hash ID
		if(window.location.hash) {

			var id = Number(window.location.hash.substring(1));
			if(!isNaN(id)) {

				// View # record
				$.WS_Form.this.submit_action('wsf-view', id);
			}
		}

		// Action
		$('[data-action]').click(function() {

			var id = $(this).attr('data-id');
			var action = $(this).attr('data-action');

			switch(action) {

				case 'wsf-view' :
				case 'wsf-edit' :

					$.WS_Form.this.submit_action(action, id);
					break;

				case 'wsf-export-all' :

					$('#ws-form-action').val('wsf-export');
					$('#ws-form-submit-id').val('');
					$('#ws-form-action-do').submit();
					break;

				default :

					$('#ws-form-action').val(action);
					$('#ws-form-submit-id').val(id);
					$('#ws-form-action-do').submit();
			}
		});

		// Filter
		$('#wsf_filter_do').click(function(e) {

			$.WS_Form.this.wp_list_table_filter_do()
		})

		// Reset
		$('#wsf_filter_reset').click(function(e) {

			$('#wsf_filter_date_from').val('');
			$('#wsf_filter_date_to').val('');

			$.WS_Form.this.wp_list_table_filter_do()
		})

		// Date fields
		$('#wsf_filter_date_from,#wsf_filter_date_to').datepicker();

		// Window - Resize
		$(window).on('resize', function() { 

			// Admin resize - Sidebar
			$.WS_Form.this.admin_size_sidebar();
		});

		// Admin resize - Sidebar
		$.WS_Form.this.admin_size_sidebar();
	}

	// Remove hidden form elements we don't need
	$.WS_Form.prototype.wp_list_table_filter_do = function() {

		$('#wsf-submissions form [name="_wp_http_referer"]').remove();
		$('#wsf-submissions form [name="_wpnonce"]').remove();
		$('#wsf-submissions form [name="action"]').attr('disabled', 'disabled');
		$('#wsf-submissions form [name="action2"]').attr('disabled', 'disabled');
		$('#current-page-selector').val('1');

		$('#wsf-submissions form').submit();
	}

	// Submit actions
	$.WS_Form.prototype.submit_action = function(action, id) {

		switch(action) {

			case 'wsf-view' :

				this.submit_render(id, true);
				break;

			case 'wsf-edit' :

				this.submit_render(id, false);
				break;
		}
	}

	// Request a submit record
	$.WS_Form.prototype.submit_render = function(id, view) {

		// Mark as read in interface
		var viewed_obj = $('[data-action-ajax="wsf-submit-viewed"][data-id="' + id + '"]');
		viewed_obj.closest('tr').removeClass('wsf-submit-not-viewed');
		viewed_obj.html($.WS_Form.this.language('viewed_on'));

		// Get sidebar outer object
		var sidebar_outer_obj = $('#wsf-sidebar-submit');

		// Clear submit ID
		$('h2 span', sidebar_outer_obj).html('');

		// Make API call to get the submit record
		this.api_call('submit/' + id, 'GET', false, function(response) {

			if(typeof(response.data) === 'undefined') { return; }

			// Get submit data
			var submit = response.data;

			// Preview?
			var preview = (typeof(submit.preview) !== 'undefined') ? submit.preview : false;

			if(!preview && $.WS_Form.this.form.published_checksum) {

				// Render submit using published form data
				$.WS_Form.this.submit_render_do(submit, $.WS_Form.this.form, view);

			} else {

				// Check if we have draft form data already
				if($.WS_Form.this.form_draft === false) {

					// Get draft form data
					$.WS_Form.this.api_call('form/' + $.WS_Form.this.form_id + '/full', 'GET', false, function(response) {

						// Store form data
						$.WS_Form.this.form_draft = response.form;

						// Render submit using draft form data
						$.WS_Form.this.submit_render_do(submit, $.WS_Form.this.form_draft, view);
					});

				} else {

					// Render submit using draft form data
					$.WS_Form.this.submit_render_do(submit, $.WS_Form.this.form_draft, view);
				}
			}

			// Update submission count in admin menu
			window.wsf_admin_wp_count_submit_unread_ajax($.WS_Form.this.form_id);
		});
	}

	$.WS_Form.prototype.submit_render_do = function(submit, form, view) {

		var submit_scratch = $.extend(true, {}, submit); // Deep clone
		var sidebar_outer_obj = $('#wsf-sidebar-submit');

		// Checks
		if(
				(typeof(form) === 'undefined') ||
				(typeof(form.groups) === 'undefined') ||
				(parseInt(submit.form_id) !== parseInt(this.form_id))
			) {

			// Turn off loader
			this.loader_off();

			return;
		}

		// Are there groups?
		var has_groups = (form.groups.length > 1);

		// Is there meta data?
		var has_meta = (typeof(submit['meta']) !== 'undefined');

		// Encrypted?
		var encrypted_html = (submit.encrypted) ? '<div class="wsf-encrypted" title="' + this.language('submit_encrypted') + '">' + this.svg('readonly') + '</div>' : '';

		// Section repeatable
		var section_repeatable = (typeof(submit['section_repeatable']) !== 'undefined') ? submit['section_repeatable'] : false;

		// Title
		var sidebar_html_title = '<div class="wsf-sidebar-header"><h2>' + this.svg('table') + this.language('submission') + encrypted_html + '<span></span></h2></div>';

		// Info
		var sidebar_html_info = '<table id="wsf-sidebar-info">';

		sidebar_html_info += this.submit_row_render(submit, 'status_full', this.language('submit_status'), 'status');
		sidebar_html_info += this.submit_row_render(submit, 'date_added_wp', this.language('submit_date_added'));
		sidebar_html_info += this.submit_row_render(submit, 'date_updated_wp', this.language('submit_date_updated'));
		sidebar_html_info += this.submit_row_render(submit, 'user_id', this.language('submit_user'), 'user');
		sidebar_html_info += this.submit_row_render(submit, 'duration', this.language('submit_duration'), 'duration');

		sidebar_html_info += '</table>';

		// Inner
		var sidebar_html = '<form class="wsf-sidebar-inner" id="ws-form-submit" data-id="' + submit.id + '">';

		// No meta data
		if(!has_meta) { return sidebar_html; }

		// Run through each group
		for(var group_index in form.groups) {

			var group = form.groups[group_index];

			// Check for sections
			if((typeof(group.sections) === 'undefined') || (group.sections.length == 0)) { continue; }

			// Run through each section
			var group_label_rendered = false;
			for(var section_index in group.sections) {

				// Get section
				var section = group.sections[section_index];

				// Get section ID
				var section_id = section.id;

				// Build section ID string
				var section_id_string = 'section_' + section_id;
				var section_repeatable_array = (

					(section_repeatable !== false) &&
					(typeof(section_repeatable[section_id_string]) !== 'undefined') &&
					(typeof(section_repeatable[section_id_string]['index']) !== 'undefined')

				) ? section_repeatable[section_id_string]['index'] : [false];

				// Run through each field
				for(var field_index in section.fields) {

					var field = section.fields[field_index];

					if(typeof $.WS_Form.field_type_cache[field.type] === 'undefined') { continue; }

					// Get field type
					var field_type = $.WS_Form.field_type_cache[field.type];

					// Determine if meta data would exist for this field type
					var submit_save = (typeof(field_type.submit_save) !== 'undefined') ? field_type.submit_save : false;

					// If not, delete it
					if(!submit_save) { delete(section.fields[field_index]); }
				}

				// Remove empty elements after delete
				section.fields = $.grep(section.fields,function(n){ return n == 0 || n });

				// Check for fields
				if((typeof(section.fields) === 'undefined') || (section.fields.length == 0)) { continue; }

				sidebar_html += '<fieldset class="wsf-fieldset">';

				// Group label
				if(has_groups && !group_label_rendered) { sidebar_html += '<h3>' + this.html_encode(group.label) + "</h3>\n"; group_label_rendered = true; }

				if(section.meta.label_render) { sidebar_html += '<legend>' + this.html_encode(section.label) + "</legend>\n"; }

				// Loop through section_repeatable_array
				for(var section_repeatable_array_index in section_repeatable_array) {

					// Check if repeatable
					var section_repeatable_index = section_repeatable_array[section_repeatable_array_index];
					var section_repeatable_suffix = '';

					// Repeatable, so render fieldset and set field_name suffix
					if(section_repeatable_index !== false) {

						// Repeatable section found
						section_repeatable_index = parseInt(section_repeatable_index);
						if(section_repeatable_index <= 0) { continue; }

						// Render fieldset
						sidebar_html += '<fieldset class="wsf-fieldset wsf-fieldset-repeatable" data-repeatable data-repeatable-index="' + section_repeatable_index + '" data-id="' + section_id + '"><legend>#' + (parseInt(section_repeatable_array_index) + 1)  + '</legend>';

						// Set field_name suffix
						var section_repeatable_suffix = '_' + section_repeatable_index;
					}

					// Run through each field
					for(var field_index in section.fields) {

						var field = section.fields[field_index];
						var field_name = this.field_name_prefix + field.id + section_repeatable_suffix;
						var value = (typeof(submit['meta'][field_name]) !== 'undefined') ? submit['meta'][field_name]['value'] : '';


						sidebar_html += '<div class="wsf-field-wrapper" data-id="' + field.id + '" data-type="' + field.type + '">';

						// Get field type
						if(typeof($.WS_Form.field_type_cache[field.type]) === 'undefined') { continue; }
						var field_type = $.WS_Form.field_type_cache[field.type];

						// Get whether field can be edited or not
						var submit_edit = (typeof(field_type.submit_edit) !== 'undefined') ? field_type.submit_edit : false;

						// Get field type ID
						var field_type_id = (typeof(field_type.submit_edit_type) !== 'undefined') ? field_type.submit_edit_type : field.type;
						if(field_type_id !== field.type) { field.type = field_type_id; }

						// If editing, show fields in view mode if config says they cannot be edited
						var field_view = !view ? !submit_edit : view;

						if(submit_edit) {

							// Create blank scratch data if not found in original submit
							if((typeof(submit['meta'][field_name]) === 'undefined')) {

								submit_scratch['meta'][field_name] = {

									'id' :			field.id,
									'value' :		'', 
									'type' :		field.type
								};
							}

						} else {

							// Delete from scratch if it cannot be edited to that it is not included in the AJAX PUT request
							delete(submit_scratch['meta'][field_name])
						}

						// Render field by field type
						if(field_view) {

							var field_mask = '<div><strong>' + this.html_encode(field.label) + '</strong><br />#field</div>';

						} else {

							var field_mask = '#field';
						}

						var field_html = false;

						// Field HTML by field type
						if(field_view && (field_html === false) && (value != '')) {

							switch(field_type_id) {

								case 'email' :

									field_html = '<a href="mailto:' + this.html_encode(value) + '">' + this.html_encode(value) + '</a>';
									break;

								case 'tel' :

									field_html = '<a href="tel:' + this.html_encode(this.get_tel(value)) + '">' + this.html_encode(value) + '</a>';
									break;

								case 'url' :

									field_html = '<a href="' + this.html_encode(value) + '" target="_blank">' + this.html_encode(value) + '</a>';
									break;
							}
						}

						if(!field_view) {

							// Edit
							field_mask = '#field';
							field_html = this.get_field_html_single(field, value, true, section_repeatable_index);

						} else {

							// Format datagrid items
							if(typeof(value) === 'object') { value = value.join('<br />'); }

							// Blank values
							if(field_html === false) { field_html = (value == '') ? '-' : value; }
						}

						// Parse and add field
						var field_mask_values = {'field': field_html};

						sidebar_html += this.mask_parse(field_mask, field_mask_values);

						sidebar_html += '</div>'

						// Mark submit meta as processed
						if(typeof(submit['meta'][field_name]) !== 'undefined') {

							submit['meta'][field_name]['processed'] = true;
						}

						// Revert field type
						if(typeof(field_type_old) !== 'undefined') { field.type = field_type_old; }
					}

					// End of repeatable section
					if(section_repeatable_suffix !== false) {

						sidebar_html += '</fieldset>';
					}
				}

				sidebar_html += '</fieldset>';
			}
		}

		if(view) {
			// Actions
			var sidebar_actions_html = this.sidebar_render_actions(submit);
			if(sidebar_actions_html !== false) { sidebar_html += sidebar_actions_html; }

		}

		sidebar_html += '</form>';

		// Next / Previous ID's
		var tbody = $('#wsf-submissions .wp-list-table tbody');

		var row_current = $('[data-id="' + submit.id + '"]', tbody).first().closest('tr');

		var row_previous = row_current.prev();
		var row_id_previous = (row_previous.length) ? $('[data-action="wsf-view"]', row_previous).attr('data-id') : undefined;

		var row_next = row_current.next();
		var row_id_next = (row_next.length) ? $('[data-action="wsf-view"]', row_next).attr('data-id') : undefined;

		// Build buttons - View
		var buttons_view = [

			{'class': 'wsf-button-primary', 'action': 'wsf-sidebar-edit', 'label': this.language('edit')},
			{'action': 'wsf-sidebar-print', 'label': this.language('print')},
			{'action': 'wsf-sidebar-close', 'label': this.language('close')},
			{'action': 'wsf-sidebar-view', 'right': true, 'id': row_id_previous, 'label': this.language('previous'), 'disabled': (row_id_previous === undefined)},
			{'action': 'wsf-sidebar-view', 'id': row_id_next, 'label': this.language('next'), 'disabled': (row_id_next === undefined)}
		];

		// Build buttons - Edit
		var buttons_edit = [

			{'class': 'wsf-button-primary', 'action': 'wsf-sidebar-save', 'label': this.language('save')},
			{'class': '', 'action': 'wsf-sidebar-cancel', 'label': this.language('cancel')},
			{'class': 'wsf-button-danger', 'right': true, 'action': 'wsf-sidebar-delete', 'label': this.language('trash')},
		];

		// Footer
		var sidebar_html_buttons = this.sidebar_buttons_html(view ? buttons_view : buttons_edit);

		// Push HTML to sidebar inner
		sidebar_outer_obj.html(sidebar_html_title);
		sidebar_outer_obj.append(sidebar_html_info);
		sidebar_outer_obj.append(sidebar_html);
		sidebar_outer_obj.append(sidebar_html_buttons);

		// Populate submit ID
		$('#wsf-sidebar-submit .wsf-sidebar-header h2 span').html(this.language('id') + ': ' + submit.id);

		var sidebar_inner_obj = $('.wsf-sidebar-inner', sidebar_outer_obj);

		// Button events
		if(view) {

			// Previous / Next
			$('[data-action="wsf-sidebar-view"]', sidebar_outer_obj).click(function() {

				var id = $(this).attr('data-id');
				if(typeof(id) !== 'undefined') { $.WS_Form.this.submit_render(id, true); }
			});

			// Edit
			$('[data-action="wsf-sidebar-edit"]', sidebar_outer_obj).click(function() {

				$.WS_Form.this.submit_render(submit.id, false);
			});

			// Print
			$('[data-action="wsf-sidebar-print"]', sidebar_outer_obj).click(function() {

				window.print();
			});

			// Close
			$('[data-action="wsf-sidebar-close"]', sidebar_outer_obj).click(function() {

				$('#wsf-sidebar-submit').removeClass('wsf-sidebar-open').addClass('wsf-sidebar-closed');
				$('#wpcontent').removeClass('wsf-sidebar-open').addClass('wsf-sidebar-closed');

				// Overflow hidden to improve touch scrolling in sidebar
				if(window.matchMedia('(max-width: 600px)').matches) {

					$('html').css({'overflow':''});
					$('body').css({'overflow':'','-webkit-overflow-scrolling':''});
				}
			});

			// Repost
			$('[data-action="wsf-submit-action-repost"]', sidebar_outer_obj).click(function() {

				var ws_this = $(this);

				var obj_action = $(this).closest('tr');

				var buttons = [

					{label:$.WS_Form.this.language('cancel'), action:'wsf-cancel'},
					{label:$.WS_Form.this.language('repost'), action:'wsf-confirm', class:'wsf-button-danger'}
				];

				$.WS_Form.this.popover($.WS_Form.this.language('confirm_action_repost'), buttons, obj_action, function() {

					// Animate
					ws_this.addClass('wsf-api-method-calling');

					// Params
					var params = {

						'id': $.WS_Form.this.form_id,
						'action_index': ws_this.attr('data-submit-action-index')
					};

					// Make API call
					$.WS_Form.this.api_call('submit/' + submit.id + '/action', 'POST', params, function(response) {

						// Success
						ws_this.removeClass('wsf-api-method-calling');

						if(typeof(response.data) === 'undefined') { return false; }

						// Process logs
						if((typeof(response.data.logs) !== 'undefined') && response.data.logs.length) {

							var log_message = response.data.logs.join('<br />');
							$.WS_Form.this.message(log_message, true, 'notice-success'); 
						}

						// Process errors
						if((typeof(response.data.errors) !== 'undefined') && response.data.errors.length) {

							var error_message = response.data.errors.join('<br />');
							$.WS_Form.this.message(error_message, true, 'notice-error'); 
						}

					}, function() {

						// Error
						ws_this.removeClass('wsf-api-method-calling');

					}, false, true);	// Bypass loader
				});
			});

			// Toggle submit action row
			$('[data-toggle]', sidebar_outer_obj).click(function() {

				$(this).attr('data-status', $(this).attr('data-status') == 'on' ? '' : 'on');
				var toggle_id = $(this).attr('data-toggle');
				$('#' + toggle_id).toggle();
			});

		} else {

			// Save
			$('[data-action="wsf-sidebar-save"]', sidebar_outer_obj).click(function() {

				$.WS_Form.this.submit_button_save(submit_scratch);
			});

			// Trash
			$('[data-action="wsf-sidebar-delete"]', sidebar_outer_obj).click(function() {

				$('#ws-form-action').val('wsf-delete');
				$('#ws-form-submit-id').val(submit_scratch.id);
				$('#ws-form-action-do').submit();
			});

			// Cancel
			$('[data-action="wsf-sidebar-cancel"]', sidebar_outer_obj).click(function() {

				$.WS_Form.this.submit_button_cancel(submit_scratch);
			});

			// Set up key shortcuts
			$.WS_Form.this.keydown[27] = {'function': function() { $.WS_Form.this.submit_button_cancel(submit_scratch); }, 'ctrl_key': false};
			$.WS_Form.this.keydown[83] = {'function': function() { $.WS_Form.this.submit_button_save(submit_scratch); }, 'ctrl_key': true};

		}

		// Turn off loader
		this.loader_off();

		// Show sidebar
		$('#wsf-sidebar-submit').removeClass('wsf-sidebar-closed').addClass('wsf-sidebar-open');
		$('#wpcontent').removeClass('wsf-sidebar-closed').addClass('wsf-sidebar-open');

		// Overflow hidden to improve touch scrolling in sidebar
		if(window.matchMedia('(max-width: 600px)').matches) {

			$('html').css({'overflow':'hidden'});
			$('body').css({'overflow':'auto','-webkit-overflow-scrolling':'touch'});
		}
	}

	// Submit - Save
	$.WS_Form.prototype.submit_button_save = function(submit_scratch) {

		// Build form data
		var form_submit_obj = $('#ws-form-submit');
		var form_data = new FormData(form_submit_obj[0]);

		// Update submit meta data
		for(var meta_key in submit_scratch.meta) {

			// Only process field meta
			if(meta_key.indexOf(this.field_name_prefix) == -1) { continue; }

			// Get value from form data and assign to submit meta value
			var meta_value = (form_data.get(meta_key) !== null) ? form_data.get(meta_key) : (form_data.getAll(meta_key + '[]') ? form_data.getAll(meta_key + '[]') : '');
			submit_scratch.meta[meta_key]['value'] = meta_value;

			// Removed processed references
			delete(submit_scratch.meta[meta_key]['processed']);
		}

		// Push to API
		var params = {

			'id' :			this.form_id,
			'submit_id' : 	submit_scratch.id,
			'submit' : 		submit_scratch
		};

		// Call AJAX request
		this.api_call('submit/' + submit_scratch.id, 'PUT', params, function(response) {

			// Reload
			location.reload();
		});
	}

	// Submit - Cancel
	$.WS_Form.prototype.submit_button_cancel = function(submit_scratch) {

		// Clear keyup functions
		$.WS_Form.this.keydown = [];

		// View submit
		$.WS_Form.this.submit_render(submit_scratch.id, true);
	}

	// Submit - Actions HTML
	$.WS_Form.prototype.sidebar_render_actions = function(submit) {

		// Get actions
		if(typeof(submit.actions) === 'undefined') { return false; }
		var submit_actions = submit.actions;

		if(submit_actions === false) { return false; }
		if(submit_actions.length == 0) { return false; }

		// Build actions HTML
		var sidebar_actions_html = "<fieldset id=\"wsf-submit-sctions\" class=\"wsf-fieldset\">\n";

		sidebar_actions_html += '<h3>' + this.language('submit_actions') + "</h3>\n";
		sidebar_actions_html += '<div class="wsf-field-wrapper">';
		sidebar_actions_html += '<div class="wsf-table-outer">';
		sidebar_actions_html += "<table>\n";

		// Header row
		sidebar_actions_html += '<thead><tr>';
		sidebar_actions_html += '<th>' + this.language('submit_actions_column_index') + '</th>';
		sidebar_actions_html += '<th>' + this.language('submit_actions_column_action') + '</th>';
		sidebar_actions_html += '<th></th>';
		sidebar_actions_html += '<th></th>';
		sidebar_actions_html += '<th></th>';
		sidebar_actions_html += '<th></th>';
		sidebar_actions_html += "</tr></thead>\n<tbody>\n";

		// Run through each action
		for(var submit_action_index in submit_actions) {

			// Save logged action data
			var submit_action_index = parseInt(submit_action_index);
			var submit_action = submit_actions[submit_action_index];
			var submit_action_id = submit_action.id;
			var submit_action_label = submit_action.label;
			var submit_action_meta = submit_action.meta;

			// Check to see if the action is installed so we can extract more data
			var action_installed = (typeof($.WS_Form.actions[submit_action_id]) !== 'undefined');
			var action = (action_installed) ? $.WS_Form.actions[submit_action_id] : false;

			// Can report action?
			var action_can_repost = (typeof(action.can_repost) !== 'undefined') ? action.can_repost : false;

			// Get action meta HTML
			var sidebar_render_action_meta_html = this.sidebar_render_action_meta_html(action, submit_action);

			// Get action log HTML
			var sidebar_render_action_logs_html = this.sidebar_render_action_logs_html(submit_action, 'logs');

			// Get action error HTML
			var sidebar_render_action_errors_html = this.sidebar_render_action_logs_html(submit_action, 'errors');

			// Action row
			sidebar_actions_html += '<tr>';

			// Action column - Index
			sidebar_actions_html += '<td>' + (submit_action_index + 1) + '</td>';

			// Action column - Label 
			sidebar_actions_html += '<td>' + this.html_encode(submit_action_label) + '</td>';

			// Action column - Meta
			sidebar_actions_html += '<td data-icon>';

			if(sidebar_render_action_meta_html !== false) {

				sidebar_actions_html += '<div data-toggle="wsf-action-meta-' + submit_action_index + '" title="' + this.language('submit_actions_meta') + '">' + this.svg('edit') + '</div>';
			}

			sidebar_actions_html += '</td><td data-icon>';

			// Action column - Logs
			if(sidebar_render_action_logs_html !== false) {

				sidebar_actions_html += '<div data-toggle="wsf-action-logs-' + submit_action_index + '" title="' + this.language('submit_actions_logs') + '">' + this.svg('info-circle') + '</div>';
			}

			sidebar_actions_html += '</td><td data-icon>';

			// Action column - Errors
			if(sidebar_render_action_errors_html !== false) {

				sidebar_actions_html += '<div data-toggle="wsf-action-errors-' + submit_action_index + '" title="' + this.language('submit_actions_errors') + '">' + this.svg('warning') + '</div>';
			}

			sidebar_actions_html += '</td><td data-icon>';

			// Action column - Repost
			if(action_can_repost) {

				sidebar_actions_html += '<div data-action="wsf-submit-action-repost" data-submit-action-index="' + submit_action_index + '" title="' + this.language('submit_actions_repost') + '">' + this.svg('redo') + '</div>';
			}

			sidebar_actions_html += "</td></tr>\n";

			// Action meta
			if(sidebar_render_action_meta_html !== false) {

				sidebar_actions_html += '<tr id="wsf-action-meta-' + submit_action_index + '" class="wsf-hidden-table-row"><td colspan="6" class="wsf-hidden-table-cell"><div>';
				sidebar_actions_html += sidebar_render_action_meta_html;
				sidebar_actions_html += '</div></td></tr>';
			}

			// Action logs
			if(sidebar_render_action_logs_html !== false) {

				sidebar_actions_html += '<tr id="wsf-action-logs-' + submit_action_index + '" class="wsf-hidden-table-row"><td colspan="6" class="wsf-hidden-table-cell"><div>';
				sidebar_actions_html += sidebar_render_action_logs_html;
				sidebar_actions_html += '</div></td></tr>';
			}

			// Action errors
			if(sidebar_render_action_errors_html !== false) {

				sidebar_actions_html += '<tr id="wsf-action-errors-' + submit_action_index + '" class="wsf-hidden-table-row"><td colspan="6" class="wsf-hidden-table-cell"><div>';
				sidebar_actions_html += sidebar_render_action_errors_html;
				sidebar_actions_html += '</div></td></tr>';
			}
		}

		sidebar_actions_html += "</tbody>\n</table>\n</div>\n</div>";

		sidebar_actions_html += '</fieldset>';

		return sidebar_actions_html;
	}

	// Submit - Action meta HTML
	$.WS_Form.prototype.sidebar_render_action_meta_html = function(action, submit_action) {

		var submit_action_id = submit_action.id;

		// Actions have a single level fieldset configuration
		var action_meta_key_cache = this.get_action_meta_key_cache(action, submit_action_id);

		// Run through submit_action meta
		if(typeof(submit_action.meta) === 'undefined') { return false; }
		var submit_action_meta = submit_action.meta;

		if(submit_action_meta.length == 0) { return false; }

		var sidebar_action_meta_html = '<table>';

		// Header row
		sidebar_action_meta_html += '<thead><tr>';
		sidebar_action_meta_html += '<th>' + this.language('submit_actions_column_meta_label') + '</th>';
		sidebar_action_meta_html += '<th>' + this.language('submit_actions_column_meta_value') + '</th>';
		sidebar_action_meta_html += "</tr></thead>\n<tbody>\n";

		// Run through each submit_action_meta row
		for(var meta_key in submit_action_meta) {

			// Get label (Use meta_key if not found)
			var meta_label = (typeof(action_meta_key_cache[meta_key]) !== 'undefined') ? action_meta_key_cache[meta_key].label : meta_key;

			// Process meta_value
			var meta_value = submit_action_meta[meta_key];

			// If it is an object, its likely a repeater field so render that as a rable
			if(typeof(meta_value) === 'object') {

				if(typeof($.WS_Form.meta_keys[meta_key]) === 'undefined') { continue; }
				var meta_config = $.WS_Form.meta_keys[meta_key];

				if(typeof(meta_config.type) === 'undefined') { continue; }
				var meta_type = meta_config.type;

				switch(meta_type) {

					case 'repeater' :

						var meta_value_repeater = '<table>';

						for(var meta_value_index in meta_value) {

							var meta_value_row = meta_value[meta_value_index];

							meta_value_repeater += '<tr>';

							for(var meta_value_row_index in meta_value_row) {

								var meta_value_column = meta_value_row[meta_value_row_index];

								meta_value_repeater += '<td>' + this.html_encode(meta_value_column) + '</td>';
							}

							meta_value_repeater += '</tr>';

						}
						meta_value_repeater += '</table>';

						meta_value = meta_value_repeater;

						break;

					default :

						// We don't have a case for processing this meta_type so output as JSON so it can be viewed
						meta_value = JSON.stringify(meta_value);
				}

			} else {

				meta_value = this.html_encode(meta_value);
			}

			sidebar_action_meta_html += '<tr><td>' + this.html_encode(meta_label) + '</td><td>' + ((meta_value != '') ? meta_value : '-') + '</td></tr>';
		} 

		sidebar_action_meta_html += "</tbody>\n</table>";

		return sidebar_action_meta_html;
	}

	// Submit - Action meta HTML
	$.WS_Form.prototype.sidebar_render_action_logs_html = function(submit_action, key) {

		// Run through submit_action log or error
		if(typeof(submit_action[key]) === 'undefined') { return false; }
		var submit_action_logs = submit_action[key];

		if(submit_action_logs.length == 0) { return false; }

		var sidebar_action_logs_html = '<table>';

		// Header row
		sidebar_action_logs_html += '<thead><tr>';
		sidebar_action_logs_html += '<th>' + this.language('submit_actions_column_' + key) + '</th>';
		sidebar_action_logs_html += "</tr></thead>\n<tbody>\n";

		// Run through each submit_action_meta row
		for(var meta_key in submit_action_logs) {

			// Get message
			var message = submit_action_logs[meta_key];

			sidebar_action_logs_html += '<tr><td>' + this.html_encode(message) + '</td></tr>';
		} 

		sidebar_action_logs_html += "</tbody>\n</table>";

		return sidebar_action_logs_html;
	}

	// Submit - Action meta HTML
	$.WS_Form.prototype.get_action_meta_key_cache = function(action, action_id) {

		var action_meta_key_cache = [];

		if(typeof(action.fieldsets) === 'undefined') { return []; }
		if(typeof(action.fieldsets[action_id]) === 'undefined') { return []; }
		if(typeof(action.fieldsets[action_id].meta_keys) === 'undefined') { return []; }

		for(var meta_key_index in action.fieldsets[action_id].meta_keys) {

			var meta_key = action.fieldsets[action_id].meta_keys[meta_key_index];

			if(typeof $.WS_Form.meta_keys[meta_key] === 'undefined') { continue; }
			var meta_key_config = $.WS_Form.meta_keys[meta_key];

			if(typeof meta_key_config.label === 'undefined') { continue; }

			// Add to action_meta_key_cache
			action_meta_key_cache[meta_key] = {'label': $.WS_Form.meta_keys[meta_key].label};
		}

		return action_meta_key_cache;
	}

	// Render submit row
	$.WS_Form.prototype.file_download_url = function(hash, id, section_repeatable_index, index, download) {

		if(typeof(download) === 'undefined') { var download = false; }

		var path = 'helper/file_download';

		var query_string = 'hash=' + hash + '&field_id=' + id + '&section_repeatable_index=' + ((section_repeatable_index !== false) ? section_repeatable_index : 0) + '&file_index=' + index + '&_wpnonce=' + ws_form_settings.nonce + (download ? '&download=1' : '');

		return ws_form_settings.url + (ws_form_settings.permalink_custom ? path + '?' + query_string : encodeURIComponent(path) + '&' + query_string);
	}

	// Render submit row
	$.WS_Form.prototype.submit_row_render = function(submit, field, label, type) {

		if(typeof(submit[field]) === 'undefined') { return; }
		if(typeof(type) === 'undefined') { var type = 'default'; }

		// Preview?
		var preview = (typeof(submit.preview) !== 'undefined') ? submit.preview : false;

		var return_html = '<tr><th>' + label + '</th><td>';

		switch(type) {

			// Status
			case 'status' :

				return_html += submit[field] + (preview ? (' (' + this.language('submit_preview') + ')') : '');
				break;

			// Duration in days, hours, minutes
			case 'duration' :

				return_html += this.get_nice_duration(submit[field]);
				break;

			// User
			case 'user' :

				var user_id = submit[field];

				if((user_id === 0) || (typeof(submit['user']) === 'undefined')) {

					return_html += '-';

				} else {

					var user_label = submit['user']['display_name'];

					return_html += '<a href="user-edit.php?user_id=' + user_id + '">' + user_label + '</a>';
				}

				break;

			default :

				return_html += submit[field];
		}

		return_html += '</td></tr>';

		return return_html;
	}

	// Generate random key
	$.WS_Form.prototype.key_generate = function() {

		if(typeof length === 'undefined') { var length = 64; }

		var characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+-=[]\\{}|;\':",./<>?';
		var key = '';

		for (var i = 0; i < 64; i++) {

			var rand = Math.round(Math.random() * characters.length);
			if(rand < 0) { rand = 0; }
			if(rand > (characters.length -1)) { rand = characters.length - 1; }
			key += characters[rand];
		}

		return key;
	}

	// API Call
	$.WS_Form.prototype.api_call = function(ajax_path, method, params, success_callback, error_callback, checksum_request, bypass_loader, bypass_form_processing) {

		this.api_call_queue.push({

			'ajax_path':				ajax_path,
			'method':					method,
			'params':					params,
			'success_callback':			success_callback,
			'error_callback':			error_callback,
			'checksum_request':			checksum_request,
			'bypass_loader':			bypass_loader,
			'bypass_form_processing':	bypass_form_processing
		});

		// Start API queue
		if(!this.api_call_queue_running) { this.api_call_process_next(); }
	}

	// API Call - Process
	$.WS_Form.prototype.api_call_process_next = function() {

		if(this.api_call_queue.length == 0) { this.api_call_queue_running = false; return false; }

		this.api_call_queue_running = true;

		var api_call = this.api_call_queue.shift();

		this.api_call_process(api_call.ajax_path, api_call.method, api_call.params, api_call.success_callback, api_call.error_callback, api_call.checksum_request, api_call.bypass_loader, api_call.bypass_form_processing);
	}

	// API Call - Process
	$.WS_Form.prototype.api_call_process = function(ajax_path, method, params, success_callback, error_callback, checksum_request, bypass_loader, bypass_form_processing) {

		// Defaults
		if(typeof method === 'undefined') { var method = 'POST'; }
		if(typeof params === 'undefined') { var params = false; }
		if(typeof success_callback === 'undefined') { var success_callback = false; }
		if(typeof error_callback === 'undefined') { var error_callback = false; }
		if(typeof checksum_request === 'undefined') { var checksum_request = false; }
		if(typeof bypass_loader === 'undefined') { var bypass_loader = false; }
		if(typeof bypass_form_processing === 'undefined') { var bypass_form_processing = false; }

		// Show loader
		if(!checksum_request && !bypass_loader) { this.loader_on(); }

		// Set form_id
		var data = {};
		if(this.form_id > 0) {

			if(params !== false) {

				data.data = JSON.stringify(params);
				data.data = this.mod_security_fix(data.data);
			}
			data.id = this.form_id;
			data.form_tab_index = this.get_object_meta_value(this.form, 'tab_index', 0);

		} else {

			if(params !== false) { var data = params; }
		}

		// Set is admin
		data.form_is_admin = true;

		// Make AJAX request
		var ajax_url = ws_form_settings.url + ajax_path;

		var ajax_request = {

			method: ws_form_settings.ajax_http_method_override ? 'POST' : method,
			url: ajax_url,
			beforeSend: function(xhr) {

				xhr.setRequestHeader('X-WP-Nonce', ws_form_settings.nonce);

				if(ws_form_settings.ajax_http_method_override) {

					xhr.setRequestHeader('X-HTTP-Method-Override', method);
				}
			},
			statusCode: {

				// Success
				200: function(response) {

					// Reset checksum checking (Prevents form refreshing due to this API call)
					if(!checksum_request) { $.WS_Form.checksum = false; }

					if(!bypass_form_processing && (typeof response.form !== 'undefined')) {

						// Look for checksum
						if(typeof response.form.checksum !== 'undefined' && typeof response.form.published_checksum !== 'undefined') {

							// Save published checksum
							$.WS_Form.this.published_checksum = response.form.published_checksum;

							// Render publish button
							$.WS_Form.this.publish_render(response.form.checksum);
						}
					}

					// Build data cache if queue is now empty
					if($.WS_Form.this.api_call_queue.length == 0) {

						// If full form returned by API, load it
						if((typeof response.form_full !== 'undefined') && response.form_full) {

							$.WS_Form.this.form = response.form;
						}

						// Build data cache
						$.WS_Form.this.data_cache_build();
					}

					// Call success function
					if(typeof success_callback === 'function') { success_callback(response); } else { $.WS_Form.this.loader_off(); }

					// Save if we are using undo function (Called after success_callback to ensure response returned is in caches)
					if(!bypass_form_processing && (typeof response.history !== 'undefined')) {

						// Push to history stack
						$.WS_Form.this.history_push(response);
					}

					// Process next API call
					$.WS_Form.this.api_call_process_next();
				},

				// Bad request (Error from API)
				400: function(response) {

					$.WS_Form.this.loader_off();

					if(typeof(response.responseJSON) === 'undefined') {

						$.WS_Form.this.error('error_bad_request');
					}

					var data = response.responseJSON;

					if(typeof(error_callback) === 'function') {

						error_callback(data);

					} else {

						// Trap an error
						if(data.error) { $.WS_Form.this.error('error_bad_request'); }
					}

					// Process next API call
					$.WS_Form.this.api_call_process_next();
				},

				// Forbidden (Error from API)
				403: function(response) {

					$.WS_Form.this.loader_off();

					if(typeof(response.responseJSON) === 'undefined') {

						$.WS_Form.this.error('error_forbidden');
					}

					var data = response.responseJSON;

					// Handle REST nonce errors (i.e. session expired)
					if(
						(typeof(data.code) !== 'undefined') &&
						(data.code == 'rest_cookie_invalid_nonce')
					) {

						location.reload();
					}

					if(typeof(error_callback) === 'function') {

						error_callback(data);

					} else {

						// Trap an error
						if(data.error) { $.WS_Form.this.error('error_forbidden'); }
					}

					// Process next API call
					$.WS_Form.this.api_call_process_next();
				},

				// Not found (Error from API)
				404: function(response) {

					$.WS_Form.this.loader_off();

					if(typeof(response.responseJSON) === 'undefined') {

						$.WS_Form.this.error('error_not_found');
					}

					var data = response.responseJSON;

					if(typeof(error_callback) === 'function') {

						error_callback(data);

					} else {

						// Trap an error
						if(data.error) { $.WS_Form.this.error('error_not_found'); }
					}

					// Process next API call
					$.WS_Form.this.api_call_process_next();
				},

				// Server error
				500: function(response) {

					$.WS_Form.this.loader_off();

					if(typeof(response.responseJSON) === 'undefined') {

						$.WS_Form.this.error('error_server');
					}

					var data = response.responseJSON;

					if(typeof(error_callback) === 'function') {

						error_callback(data);

					} else {

						// Trap an error
						if(data.error) { $.WS_Form.this.error('error_server'); }
					}

					// Process next API call
					$.WS_Form.this.api_call_process_next();
				}
			}
		};

		if(data !== false) { ajax_request.data = data; }

		$.ajax(ajax_request);

		return this;
	};

	// If current checksum does not match published check sum
	$.WS_Form.prototype.publish_render = function(checksum) {

		if(checksum == '') { return false; }

		if(checksum != this.published_checksum) {

			// Add class to publish button
			$('[data-action="wsf-publish"]').prop('disabled', false);

		} else {

			// Remove class from publish button
			$('[data-action="wsf-publish"]').prop('disabled', true);
		}
	}

	// Handle a log message (Disregard in admin)
	$.WS_Form.prototype.log = function(log_message) {}

	// Render an error message
	$.WS_Form.prototype.error = function(language_id, variable) {

		// Check for variable
		if(typeof variable == 'undefined') { var variable = ''; }

		var message = this.language(language_id, variable, false).replace(/%s/g, variable);

		this.message(message, true, 'notice-error');
	}

	$.WS_Form.prototype.message = function(message, dismissable, type, insert_after_header_end) {

		if(typeof variable == 'undefined') { var variable = ''; }
		if(typeof dismissable == 'undefined') { var dismissable = true; }
		if(typeof type == 'undefined') { var type = 'notice-success'; }
		if(typeof insert_after_header_end == 'undefined') { var insert_after_header_end = true; }

		// Build notice
		var notice = '<div class="notice ' + type + '"><p>' + message + '</p>' + (dismissable ? '<button type="button" class="notice-dismiss"><span class="screen-reader-text">' + this.language('dismiss') + '</span></button>' : '') + '</div>';
		notice.replace("\n", "<br />\n");

		// Append message to notice div
		if(insert_after_header_end) {

			var notice_obj = $(notice).insertAfter($('.wp-header-end'));

			// Button click event
			if(dismissable) {

				$('button', notice_obj).click(function() { $(this).closest('div').remove(); });
			}

		} else {

			return notice;
		}
	}

	// Sidebar - Form - Open
	window.sidebar_form_open = function(obj, obj_form, obj_button) {

		// Edit the form settings (This function opens the object sidebar)
		obj.object_edit($('#wsf-form'));
	}

	// Sidebar - Form - Toggle
	window.sidebar_form_toggle = function(obj, obj_form, obj_button) {

		// Remove editing class
		$('#wsf-form').removeClass('wsf-editing');

		obj.sidebar_reset();
	}

	// Sidebar - Form - Close
	window.sidebar_form_close = function(obj, obj_form, obj_button) {

		// Remove editing class
		$('.wsf-form').removeClass('wsf-editing');
	}

	// Sidebar - Conditional - Open
	window.sidebar_conditional_open = function(obj, obj_form, obj_button) {

		var obj_outer = $('#wsf-sidebar-conditional');

		// Title
		var sidebar_config = $.WS_Form.settings_form['sidebars']['conditional'];

		// Build knowledge base HTML
		if((typeof(sidebar_config.kb_url) !== 'undefined')) {

			var kb_url = obj.get_plugin_website_url(sidebar_config.kb_url, 'sidebar');
			var sidebar_kb_html = '<a class="wsf-kb-url" href="' + kb_url + '" target="_blank" title="' + obj.language('field_kb_url') + '" tabindex="-1">' + obj.svg('question-circle') + '</a>';
		}

		obj_outer.html(obj.sidebar_title(obj.svg(sidebar_config.icon), sidebar_config.label, '', sidebar_kb_html, '', true));

		// Inner
		obj_outer.append('<div class="wsf-sidebar-inner"><fieldset class="wsf-fieldset"><div class="wsf-field-wrapper"><div class="wsf-data-grid" data-object="form" data-id="' + obj.form_id + '" data-meta-key="conditional"></div></div></fieldset></div>');

		// Buttons
		obj_outer.append(obj.sidebar_buttons_html());

		// Get object data
		var object_data = obj.get_object_data('form', obj.form_id);

		// Create new object data that edits will be saved to
		obj.object_data_scratch = $.extend(true, {}, object_data); // Deep clone

		// Initialize conditional datagrid
		obj.sidebar_data_grids_init($('#wsf-sidebar-conditional'));

		// Button - Save
		$('[data-action="wsf-sidebar-save"]', obj_outer).click(function() {

			obj.sidebar_conditional_save();
		});

		// Button - Cancel
		$('[data-action="wsf-sidebar-cancel"]', obj_outer).click(function() {

			obj.sidebar_reset();
		});

		// Set up key shortcuts
		$.WS_Form.this.keydown[27] = {'function': function() { obj.sidebar_reset(); }, 'ctrl_key': false};
		$.WS_Form.this.keydown[83] = {'function': function() { obj.sidebar_conditional_save(); }, 'ctrl_key': true};

		// Expand / contract
		obj.sidebar_expand_contract_init();

		// Open conditional sidebar
		obj.sidebar_open('conditional');
	}

	$.WS_Form.prototype.sidebar_conditional_save = function() {

		// Open toolbox
//		this.sidebar_reset();

		// Build parameters
		var params = {};

		// Form ID
		params['form_id'] = this.form_id;

		// Object data
		params['form'] = this.object_data_scratch;

		// Call AJAX request
		this.api_call('form/' + this.form_id, 'PUT', params, function(response) {

			// Save notification
			$.WS_Form.this.save_notification();

			// Loader off
			$.WS_Form.this.loader_off();
		});
	}

	// Sidebar - Action - Open
	window.sidebar_action_open = function(obj, obj_form, obj_button) {

		var obj_outer = $('#wsf-sidebar-action');

		// Title
		var sidebar_config = $.WS_Form.settings_form['sidebars']['action'];

		// Build knowledge base HTML
		if((typeof(sidebar_config.kb_url) !== 'undefined')) {

			var kb_url = obj.get_plugin_website_url(sidebar_config.kb_url, 'sidebar');
			var sidebar_kb_html = '<a class="wsf-kb-url" href="' + kb_url + '" target="_blank" title="' + obj.language('field_kb_url') + '" tabindex="-1">' + obj.svg('question-circle') + '</a>';
		}

		obj_outer.html(obj.sidebar_title(obj.svg(sidebar_config.icon), sidebar_config.label, '', sidebar_kb_html, '', true));

		// Inner
		obj_outer.append('<div class="wsf-sidebar-inner"><fieldset class="wsf-fieldset"><div class="wsf-field-wrapper"><div class="wsf-data-grid" data-object="form" data-id="' + obj.form_id + '" data-meta-key="action"></div></div></fieldset></div>');

		// Buttons
		obj_outer.append(obj.sidebar_buttons_html());

		// Get object data
		var object_data = obj.get_object_data('form', obj.form_id);

		// Create new object data that edits will be saved to
		obj.object_data_scratch = $.extend(true, {}, object_data); // Deep clone

		// Initialize conditional datagrid
		obj.sidebar_data_grids_init($('#wsf-sidebar-action'));

		// Button - Save
		$('[data-action="wsf-sidebar-save"]', obj_outer).click(function() {

			obj.sidebar_action_save();
		});

		// Button - Cancel
		$('[data-action="wsf-sidebar-cancel"]', obj_outer).click(function() {

			obj.sidebar_reset();
		});

		// Set up key shortcuts
		$.WS_Form.this.keydown[27] = {'function': function() { obj.sidebar_reset(); }, 'ctrl_key': false};
		$.WS_Form.this.keydown[83] = {'function': function() { obj.sidebar_action_save(); }, 'ctrl_key': true};

		// Expand / contract
		obj.sidebar_expand_contract_init();

		// Open actions sidebar
		obj.sidebar_open('action');
	}

	$.WS_Form.prototype.sidebar_action_save = function() {

		// Save
		this.action_save();

		// Open toolbox
//		this.sidebar_reset();

		// Build parameters
		var params = {};

		// Form ID
		params['form_id'] = this.form_id;

		// Object data
		params['form'] = this.object_data_scratch;

		// Call AJAX request
		this.api_call('form/' + this.form_id, 'PUT', params, function(response) {

			// Save notification
			$.WS_Form.this.save_notification();

			// Loader off
			$.WS_Form.this.loader_off();
		});
	}

	// Sidebar - Support - Open
	window.sidebar_support_open = function(obj, obj_form, obj_button) {

		var obj_outer = $('#wsf-sidebar-support');

		// Button - Cancel
		$('[data-action="wsf-sidebar-cancel"]', obj_outer).click(function() {

			obj.sidebar_reset();
		});

		// Set up key shortcuts
		$.WS_Form.this.keydown[27] = {'function': function() { obj.sidebar_reset(); }, 'ctrl_key': false};

		// Initialize
		var inits = ['knowledgebase'];
		obj.sidebar_inits(inits, obj_outer);

		// Open conditional sidebar
		obj.sidebar_open('support');
	}

	// Method: Group - Edit
	window.wsf_group_edit = function(obj, obj_group, obj_button) {

		obj.object_edit(obj_group);
	}

	// Method: Group - Clone
	window.wsf_group_clone = function(obj, obj_group, obj_button) {

		// Process form sidebar as a cancel event
		obj.object_cancel(obj_group);

		// Clone object
		obj.group_put_clone(obj_group);

		// Open toolbox sidebar
		obj.sidebar_reset();
	}

	// Method: Group - Delete
	window.wsf_group_delete = function(obj, obj_group, obj_button) {

		var buttons = [

			{label:obj.language('cancel'), action:'wsf-cancel'},
			{label:obj.language('delete'), action:'wsf-confirm', class:'wsf-button-danger'}
		];

		obj.popover(obj.language('confirm_group_delete'), buttons, obj_group, function() {

			// Process form sidebar as a cancel event
			obj.object_cancel(obj_group);

			obj.object_delete(obj_group);

			// Open toolbox sidebar
			obj.sidebar_reset();
		});
	}

	// Method: Section - Edit
	window.wsf_section_edit = function(obj, obj_section, obj_button) {

		obj.object_edit(obj_section);
	}

	// Method: Section - Clone
	window.wsf_section_clone = function(obj, obj_section, obj_button) {

		// Process form sidebar as a cancel event
		obj.object_cancel(obj_section);

		// Clone objecty
		obj.section_put_clone(obj_section);

		// Open toolbox sidebar
		obj.sidebar_reset();
	}

	// Method: Section - Delete
	window.wsf_section_delete = function(obj, obj_section, obj_button) {

		var buttons = [

			{label:obj.language('cancel'), action:'wsf-cancel'},
			{label:obj.language('delete'), action:'wsf-confirm', class:'wsf-button-danger'}
		];

		obj.popover(obj.language('confirm_section_delete'), buttons, obj_section, function() {

			// Process form sidebar as a cancel event
			obj.object_cancel(obj_section);

			obj.object_delete(obj_section);

			// Open toolbox sidebar
			obj.sidebar_reset();
		});
	}

	// Method: Field - Edit
	window.wsf_field_edit = function(obj, obj_field, obj_button) {

		obj.object_edit(obj_field);
	}

	// Method: Field - Clone
	window.wsf_field_clone = function(obj, obj_field, obj_button) {

		// Process form sidebar as a cancel event
		obj.object_cancel(obj_field);

		// Clone object
		obj.field_put_clone(obj_field);

		// Open toolbox sidebar
		obj.sidebar_reset();
	}

	// Method: Field - Delete
	window.wsf_field_delete = function(obj, obj_field, obj_button) {

		var buttons = [

			{label:obj.language('cancel'), action:'wsf-cancel'},
			{label:obj.language('delete'), action:'wsf-confirm', class:'wsf-button-danger'}
		];

		obj.popover(obj.language('confirm_field_delete'), buttons, obj_field, function() {

			// Process form sidebar as a cancel event
			obj.object_cancel(obj_field);

			// Delete object
			obj.object_delete(obj_field);

			// Open toolbox sidebar
			obj.sidebar_reset();
		});
	}

})(jQuery);
