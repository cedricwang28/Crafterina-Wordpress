<?php

	class WS_Form_Field extends WS_Form_Core {

		public $id;
		public $form_id;
		public $section_id;
		public $section_id_from;
		public $new_lookup;
		public $type;

		public $table_name;

		const DB_INSERT = 'label,type,user_id,date_added,date_updated,sort_index,section_id';
		const DB_UPDATE = 'label,user_id,date_updated';
		const DB_SELECT = 'label,type,date_updated,sort_index,id';

		public function __construct() {

			global $wpdb;

			$this->form_id = 0;
			$this->section_id = 0;
			$this->id = 0;
			$this->new_lookup = array();
			$this->new_lookup['field'] = array();
			$this->type = '';

			$this->table_name = $wpdb->prefix . WS_FORM_DB_TABLE_PREFIX . 'field';
		}

		// Create field
		public function db_create($next_sibling_id = 0) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			// Check section ID
			self::db_check_section_id();

			// Check field type is licensed
			if(!self::db_check_licensed($this->type)) { return false; }

			// Get sort_index
			$sort_index = self::db_object_sort_index_get($this->table_name, 'section_id', $this->section_id, $next_sibling_id);

			// Build field label
			$meta_data = self::db_field_type_config();
			if(isset($meta_data['label_default'])) {

				// Use label configured in config
				$field_label = $meta_data['label_default'];

			} else {

				// Use fallback label (in case label_default is not specified in the config data)
				$field_label = WS_FORM_DEFAULT_FIELD_NAME;
			}

			global $wpdb;

			// Add field
			$sql = sprintf("INSERT INTO %s (%s) VALUES ('%s', '%s', %u, '%s', '%s', %u, %u);", $this->table_name, self::DB_INSERT, esc_sql($field_label), esc_sql($this->type), WS_Form_Common::get_user_id(), WS_Form_Common::get_mysql_date(), WS_Form_Common::get_mysql_date(), $sort_index, $this->section_id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error adding field', 'ws-form')); }

			// Get inserted ID
			$this->id = $wpdb->insert_id;

			// Build meta data array
			$meta_keys = WS_Form_Config::get_meta_keys();
			$meta_values = array(

				'section_id' => $this->section_id
			);
			$meta_data_array = self::build_meta_data($meta_data, $meta_keys, $meta_values);

			// Check for section_repeatable_section_id
			if(array_key_exists('section_repeatable_section_id', $meta_data_array)) {

				// Read section
				$ws_form_section = new WS_Form_Section();
				$ws_form_section->id = $this->section_id;
				$section = $ws_form_section->db_read();

				$section_repeatable = WS_Form_Common::get_array_meta_value($section, 'section_repeatable', false);

				// If it is not enabled, we should not assign this field to that section
				if(!$section_repeatable) {

					$meta_data_array['section_repeatable_section_id'] = '';
				}
			}

			// Build meta data
			$field_meta = New WS_Form_Meta();
			$field_meta->object = 'field';
			$field_meta->parent_id = $this->id;
			$field_meta->db_update_from_array($meta_data_array);

			return $this->id;
		}

		// Read record to array
		public function db_read($get_meta = true, $bypass_user_capability_check = false) {

			// User capability check
			if(!$bypass_user_capability_check && !WS_Form_Common::can_user('read_form')) { return false; }

			self::db_check_id();

			global $wpdb;

			// Get field types
			$field_types = WS_Form_Config::get_field_types_flat();

			// Add fields
			$sql = sprintf("SELECT %s FROM %s WHERE id = %u LIMIT 1;", self::DB_SELECT, $this->table_name, $this->id);
			$return_array = $wpdb->get_row($sql, 'ARRAY_A');

			if($return_array === null) { return false; }

			// Skip unlicensed field types
			if(!isset($field_types[$return_array['type']])) { return false; }

			foreach($return_array as $key => $value) {

				$this->{$key} = $value;
			}

			if($get_meta) {

				// Read meta
				$field_meta = New WS_Form_Meta();
				$field_meta->object = 'field';
				$field_meta->parent_id = $this->id;
				$metas = $field_meta->db_read_all();
				$return_array['meta'] = $metas;
				$this->meta = $metas;

				// wpautop processing
				if(isset($field_types[$return_array['type']]['meta_wpautop'])) {

					$meta_wpautop = $field_types[$return_array['type']]['meta_wpautop'];

					if(!is_array($meta_wpautop)) { $meta_wpautop = array($meta_wpautop); }

					foreach($meta_wpautop as $meta_wpautop_meta_key) {

						if(!isset($this->meta->{$meta_wpautop_meta_key})) { continue; }

						$this->meta->{$meta_wpautop_meta_key} = wpautop($this->meta->{$meta_wpautop_meta_key});
					}
				}
			}

			// Return array
			return $return_array;
		}

		// Check if record exists
		public function db_check() {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			global $wpdb;

			$sql = sprintf("SELECT id FROM %s WHERE id = %u LIMIT 1;", $this->table_name, $this->id);
			$return_array = $wpdb->get_row($sql, 'ARRAY_A');

			if($return_array === null) { return false; } else { return true; }
		}

		// Read record and all children to array
		public function db_read_all($get_meta = true, $checksum = false) {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			self::db_check_section_id();

			global $wpdb;

			// Get field types
			$field_types = WS_Form_Config::get_field_types_flat();

			$sql = sprintf("SELECT %s FROM %s WHERE section_id = %u ORDER BY sort_index", self::DB_SELECT, $this->table_name, $this->section_id);
			$fields = $wpdb->get_results($sql, 'ARRAY_A');

			if($fields) {

				foreach($fields as $key => $field) {

					// Skip unlicensed field types
					if(!isset($field_types[$field['type']])) { unset($fields[$key]); continue; }

					// Get meta data for each field
					if($get_meta) {

						$field_meta = New WS_Form_Meta();
						$field_meta->object = 'field';
						$field_meta->parent_id = $field['id'];
						$metas = $field_meta->db_read_all();
						$fields[$key]['meta'] = $metas;

						// Checksum
						if($checksum && isset($fields[$key]['date_updated'])) {

							unset($fields[$key]['date_updated']);
						}

						// wpautop processing
						if(isset($field_types[$field['type']]['meta_wpautop'])) {

							$meta_wpautop = $field_types[$field['type']]['meta_wpautop'];

							if(!is_array($meta_wpautop)) { $meta_wpautop = array($meta_wpautop); }

							foreach($meta_wpautop as $meta_wpautop_meta_key) {

								if(!isset($fields[$key]['meta']->{$meta_wpautop_meta_key})) { continue; }

								$fields[$key]['meta']->{$meta_wpautop_meta_key} = wpautop($fields[$key]['meta']->{$meta_wpautop_meta_key});
							}
						}
					}
				}

				// Reset keys in case one was removed because a field was not licensed
				$fields = array_values($fields);

				return $fields;

			} else {

				return [];
			}
		}

		// Delete
		public function db_delete($repair = true) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			self::db_check_id();

			global $wpdb;

			// Delete field
			$sql = sprintf("DELETE FROM %s WHERE id = %u;", $this->table_name, $this->id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error deleting field', 'ws-form')); }

			// Delete meta
			$ws_form_meta = New WS_Form_Meta();
			$ws_form_meta->object = 'field';
			$ws_form_meta->parent_id = $this->id;
			$ws_form_meta->db_delete_by_object();

			// Repair conditional, actions and meta data to remove references to this deleted field
			if($repair) {

				$ws_form_form = New WS_Form_Form();
				$ws_form_form->id = $this->form_id;
				$ws_form_form->new_lookup['field'][$this->id] = '';
				$ws_form_form->db_conditional_repair();
				$ws_form_form->db_action_repair();
				$ws_form_form->db_meta_repair();
			}

			return true;
		}

		// Delete all fields in section
		public function db_delete_by_section($repair = true) {
			
			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			self::db_check_section_id();

			global $wpdb;

			if($repair) {

				$ws_form_form = New WS_Form_Form();
				$ws_form_form->id = $this->form_id;
			}

			$sql = sprintf("SELECT %s FROM %s WHERE section_id = %u", self::DB_SELECT, $this->table_name, $this->section_id);
			$fields = $wpdb->get_results($sql, 'ARRAY_A');

			if($fields) {

				foreach($fields as $key => $field) {

					// Delete field
					$this->id = $field['id'];
					self::db_delete(false);

					$ws_form_form->new_lookup['field'][$this->id] = '';
				}
			}

			// Repair conditional, actions and meta data to remove references to these deleted fields
			if($repair) {

				$ws_form_form->db_conditional_repair();
				$ws_form_form->db_action_repair();
				$ws_form_form->db_meta_repair();
			}

			return true;
		}

		// Clone - All
		public function db_clone_all($section_id_copy_to) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			global $wpdb;

			// Get field types
			$field_types = WS_Form_Config::get_field_types_flat();

			$sql = sprintf("SELECT %s FROM %s WHERE section_id = %u ORDER BY sort_index", self::DB_SELECT, $this->table_name, $this->section_id);
			$fields = $wpdb->get_results($sql, 'ARRAY_A');

			if($fields) {

				foreach($fields as $key => $field) {

					// Read data required for copying
					$this->id = $field['id'];
					$this->label = $field['label'];
					$this->type = $field['type'];
					$this->sort_index = $field['sort_index'];
					$this->section_id = $section_id_copy_to;

					// Check for multiple = false field types
					if(!isset($field_types[$this->type])) { continue; }
					$multiple = (isset($field_types[$this->type]['multiple'])) ? $field_types[$this->type]['multiple'] : true;
					if(!$multiple) { continue; }

					self::db_clone();
				}
			}
		}

		// Clone
		public function db_clone() {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			global $wpdb;

			// Clone field
			$sql = sprintf("INSERT INTO %s (%s) VALUES ('%s', '%s', %u, '%s', '%s', %u, %u);", $this->table_name, self::DB_INSERT, esc_sql($this->label), esc_sql($this->type), WS_Form_Common::get_user_id(), WS_Form_Common::get_mysql_date(), WS_Form_Common::get_mysql_date(), $this->sort_index, $this->section_id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error cloning field', 'ws-form')); }

			// Get new field ID
			$field_id_new = $wpdb->insert_id;

			// Clone meta data
			$ws_form_meta = New WS_Form_Meta();
			$ws_form_meta->object = 'field';
			$ws_form_meta->parent_id = $this->id;
			$ws_form_meta->db_clone_all($field_id_new);

			return $field_id_new;
		}

		// Get checksum of current form and store it to database
		public function db_checksum() {

			// Get form ID
			self::db_check_form_id();

			// Calculate new form checksum
			$ws_form_form = New WS_Form_Form();
			$ws_form_form->id = $this->form_id;
			$checksum = $ws_form_form->db_checksum();

			return $checksum;
		}

		// Push field from array
		public function db_update_from_object($field, $new = false) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			// Check field type is licensed
			if(!isset($field['type'])) { return false; }
			if(!self::db_check_licensed($field['type'])) { return false; }

			// Check for field ID in $field
			if(isset($field['id']) && !$new) { $this->id = absint($field['id']); }
			if($new) {

				$this->id = 0;
				$field_id_old = (isset($field['id'])) ? $field['id'] : 0;		// Do not convert to in, some imported forms require strings for keys (e.g. '1.3')
				if(isset($field['id'])) { unset($field['id']); }
			}

			// Check for label
			if(!isset($field['label'])) {

				self::db_read();
				$field['label'] = $this->label;
			}
			if($field['label'] == '') { parent::db_throw_error(__('Blank label', 'ws-form')); }

			// Update / Insert
			$this->id = parent::db_update_insert($this->table_name, self::DB_UPDATE, self::DB_INSERT, $field, 'field', $this->id);
			if($new) {

				if($field_id_old) { $this->new_lookup['field'][$field_id_old] = $this->id; }
				if(
					isset($field['meta']) &&
					isset($field['meta']['parent_id']) &&
					$field['meta']['parent_id'] &&
					!isset($this->new_lookup['field'][$field['meta']['parent_id']])
				) {

					$this->new_lookup['field'][$field['meta']['parent_id']] = $this->id;
				}
			}

			// Base meta for new records
			if(!isset($field['meta']) || !is_array($field['meta'])) { $field['meta'] = array(); }
			if($new) {

				$this->type = $field['type'];
				$meta_data = self::db_field_type_config();
				$meta_keys = WS_Form_Config::get_meta_keys();
				$meta_data_array = self::build_meta_data($meta_data, $meta_keys);
				$field['meta'] = array_merge($meta_data_array, $field['meta']);
			}

			// Update meta
			if(isset($field['meta'])) {

				$ws_form_meta = New WS_Form_Meta();
				$ws_form_meta->object = 'field';
				$ws_form_meta->parent_id = $this->id;
				$ws_form_meta->db_update_from_array($field['meta'], $this->new_lookup['field']);
			}

			$this->previous_id = $this->id;
		}

		// Push all fields from array
		public function db_update_from_array($fields, $new) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			self::db_check_section_id();

			global $wpdb;

			// Change date_updated to null for all records
			$wpdb->update($this->table_name, array('date_updated' => null), array('section_id' => $this->section_id));

			foreach($fields as $field) {

				self::db_update_from_object($field, $new);
			}

			// Delete any fields that were not updated
			$wpdb->delete($this->table_name, array('date_updated' => null, 'section_id' => $this->section_id));

			return true;
		}

		// Get section ID
		public function db_get_section_id() {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			if($this->id == 0) { parent::db_throw_error(__('Field ID is zero, cannot get section ID', 'ws-form')); }

			global $wpdb;

			$sql = sprintf("SELECT section_id FROM %s WHERE id = %u LIMIT 1;", $this->table_name, $this->id);
			$section_id = $wpdb->get_var($sql);
			if($section_id === false) { parent::db_throw_error(__('Error getting section ID', 'ws-form')); }

			return $section_id;
		}

		// Check form_id
		public function db_check_form_id() {

			if($this->form_id <= 0) { parent::db_throw_error(__('Invalid form ID', 'ws-form')); }
			return true;
		}

		// Check section_id
		public function db_check_section_id() {

			if($this->section_id <= 0) { parent::db_throw_error(__('Invalid section ID', 'ws-form')); }
			return true;
		}

		// Check section_id from
		public function db_check_section_id_from() {

			if($this->section_id_from <= 0) { parent::db_throw_error(__('Invalid section ID (From)', 'ws-form')); }
			return true;
		}

		// Check id
		public function db_check_id() {

			if($this->id <= 0) { parent::db_throw_error(__('Invalid field ID', 'ws-form')); }
			return true;
		}

		// Check type
		public function db_field_type_config() {

			$field_types = WS_Form_Config::get_field_types();
			foreach($field_types as $field_group => $types) {

				if(isset($types['types'][$this->type])) { return $types['types'][$this->type]; }
			}
			return false;
		}

		// Check licensed
		public function db_check_licensed($type) {

			$field_types = WS_Form_Config::get_field_types();
			foreach($field_types as $section => $types) {

				if(isset($types['types'][$type])) {

					if(
						isset($types['types'][$type]['pro_required']) &&
						$types['types'][$type]['pro_required']
					) {
						return false;
					} else {
						return true;
					}
				}
			}
			return false;
		}

		// Get field label
		public function db_get_label() {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			return parent::db_object_get_label($this->table_name, $this->id);
		}
	}
