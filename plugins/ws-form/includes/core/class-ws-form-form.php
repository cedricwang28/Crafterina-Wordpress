<?php

	class WS_Form_Form extends WS_Form_Core {

		public $id;
		public $checksum;
		public $new_lookup;

		public $table_name;

		const DB_INSERT = 'label,user_id,date_added,date_updated,version';
		const DB_UPDATE = 'label,user_id,date_updated';
		const DB_SELECT = 'label,status,checksum,published_checksum,count_stat_view,count_stat_save,count_stat_submit,count_submit,count_submit_unread,id';

 		const FILE_ACCEPTED_MIME_TYPES = 'application/json';

		public function __construct() {

			global $wpdb;

			$this->id = 0;
			$this->table_name = $wpdb->prefix . WS_FORM_DB_TABLE_PREFIX . 'form';
			$this->checksum = '';
			$this->new_lookup = array();
			$this->new_lookup['form'] = array();
			$this->new_lookup['group'] = array();
			$this->new_lookup['section'] = array();
			$this->new_lookup['field'] = array();
		}

		// Create form
		public function db_create() {

			// User capability check
			if(!WS_Form_Common::can_user('create_form')) { return false; }

			global $wpdb;

			// Add form
			$sql = sprintf("INSERT INTO %s (%s) VALUES ('%s', %u, '%s', '%s', '%s');", $this->table_name, self::DB_INSERT, esc_sql(WS_FORM_DEFAULT_FORM_NAME), WS_Form_Common::get_user_id(), WS_Form_Common::get_mysql_date(), WS_Form_Common::get_mysql_date(), WS_FORM_VERSION);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error adding form', 'ws-form')); }

			// Get inserted ID
			$this->id = $wpdb->insert_id;

			// Build meta data array
			$settings_form_admin = WS_Form_Config::get_settings_form_admin();
			$meta_data = $settings_form_admin['sidebars']['form']['meta'];
			$meta_keys = WS_Form_Config::get_meta_keys();
			$meta_keys = apply_filters('wsf_form_create_meta_keys', $meta_keys);
			$meta_data_array = self::build_meta_data($meta_data, $meta_keys);

			// Build meta data
			$form_meta = New WS_Form_Meta();
			$form_meta->object = 'form';
			$form_meta->parent_id = $this->id;
			$form_meta->db_update_from_array($meta_data_array);

			// Build first group
			$ws_form_group = New WS_Form_Group();
			$ws_form_group->form_id = $this->id;
			$ws_form_group->db_create();

			// Run action
			do_action('wsf_form_create', $this);

			return $this->id;
		}

		public function db_create_from_wizard($id) {

			if(empty($id)) { return false; }

			// Create new form
			self::db_create();

			// Load wizard form data
			$ws_form_wizard = New WS_Form_Wizard();
			$ws_form_wizard->id = $id;
			$ws_form_wizard->read();
			$form = $ws_form_wizard->form;

			// Ensure form attributes are reset
			$form['status'] = 'draft';
			$form['count_submit'] = 0;
			$form['count_submit_unread'] = 0;

			// Create form
			self::db_update_from_object($form, true, true);

			// Fix data - Conditional ID's
			self::db_conditional_repair();

			// Fix data - Action ID's
			self::db_action_repair();

			// Fix data - Meta ID's
			self::db_meta_repair();

			// Set checksum
			self::db_checksum();

			return $this->id;
		}

		public function db_create_from_action($action_id, $list_id, $list_sub_id = false) {

			// Create new form
			self::db_create();

			if($this->id > 0) {

				// Modify form so it matches action list
				WS_Form_Action::update_form($this->id, $action_id, $list_id, $list_sub_id);

				return $this->id;

			} else {

				return false;
			}
		}

		// Read record to array
		public function db_read($get_meta = true, $get_groups = false, $fields = false, $checksum = false) {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			global $wpdb;

			self::db_check_id();

			// Read form
			$sql = sprintf("SELECT %s FROM %s WHERE id = %s LIMIT 1;", self::DB_SELECT, $this->table_name, $this->id);
			$return_array = $wpdb->get_row($sql, 'ARRAY_A');

			if($return_array === null) { parent::db_throw_error(__('Unable to read form', 'ws-form')); }

			// Process groups (Done first in case we are requesting only fields)
			if($get_groups || $fields) {

				// Read sections
				$ws_form_group = New WS_Form_Group();
				$ws_form_group->form_id = $this->id;
				$ws_form_group_return = $ws_form_group->db_read_all($get_meta, $fields, $checksum);
				if($fields) { return $ws_form_group_return; }

				$return_array['groups'] = $ws_form_group_return;
			}

			// Set class variables
			foreach($return_array as $key => $value) {

				$this->{$key} = $value;
			}

			// Process meta data
			if($get_meta) {

				// Read meta
				$ws_form_meta = New WS_Form_Meta();
				$ws_form_meta->object = 'form';
				$ws_form_meta->parent_id = $this->id;
				$metas = $ws_form_meta->db_read_all();
				$return_array['meta'] = $this->meta = $metas;
			}

			// Return array
			return $return_array;
		}

		// Read - Published data
		public function db_read_published() {

			// No capabilities required, this is a public method

			global $wpdb;

			// Get contents of published field
			$sql = sprintf("SELECT checksum, published FROM %s WHERE id = %s LIMIT 1;", $this->table_name, $this->id);
			$published_row = $wpdb->get_row($sql);

			if($published_row === null) { parent::db_throw_error(__('Unable to read published form data', 'ws-form')); }

			// Read published JSON string
			$published_string = $published_row->published;

			// Empty published field (Never published)
			if($published_string == '') { return false; }

			// Inject latest checksum
			$published_object = json_decode($published_string);
			$published_array = (array)$published_object;
			$published_array['checksum'] = $published_row->checksum;

			return $published_array;
		}

		// Set - Published
		public function db_publish() {

			// User capability check
			if(!WS_Form_Common::can_user('publish_form')) { return false; }

			global $wpdb;

			// Set form as published
			$sql = sprintf("UPDATE %s SET status = 'publish', date_publish = '%s', date_updated = '%s' WHERE id = %u LIMIT 1;", $this->table_name, WS_Form_Common::get_mysql_date(), WS_Form_Common::get_mysql_date(), $this->id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error publishing form', 'ws-form')); }

			// Read full form
			$form = self::db_read(true, true);

			// Update checksum
			self::db_checksum();

			// Set checksums
			$form['checksum'] = $this->checksum;
			$form['published_checksum'] = $this->checksum;

			// Apply filters
			apply_filters('wsf_form_publish', $form);

			// JSON encode
			$form_json = json_encode($form);

			// Publish form
			$sql = sprintf("UPDATE %s SET published = '%s', published_checksum = '%s' WHERE id = %u LIMIT 1;", $this->table_name, esc_sql($form_json), esc_sql($this->checksum), $this->id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error publishing form', 'ws-form')); }

			// Do action
			do_action('wsf_form_publish', $form);
		}

		// Set - Published
		public function db_draft() {

			// User capability check
			if(!WS_Form_Common::can_user('publish_form')) { return false; }

			global $wpdb;

			// Set form as published
			$sql = sprintf("UPDATE %s SET status = 'draft', date_publish = '', date_updated = '%s', published = '', published_checksum = '' WHERE id = %u LIMIT 1;", $this->table_name, WS_Form_Common::get_mysql_date(), $this->id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error drafting form', 'ws-form')); }

			// Read full form
			$form = self::db_read(true, true);

			// Update checksum
			self::db_checksum();
		}

		// Import reset
		public function db_import_reset() {

			// User capability check
			if(!WS_Form_Common::can_user('publish_form')) { return false; }

			global $wpdb;

			// Delete meta
			$ws_form_meta = New WS_Form_Meta();
			$ws_form_meta->object = 'form';
			$ws_form_meta->parent_id = $this->id;
			$ws_form_meta->db_delete_by_object();

			// Delete form groups
			$ws_form_group = New WS_Form_Group();
			$ws_form_group->form_id = $this->id;
			$ws_form_group->db_delete_by_form(false);

			// Set form as published
			$sql = sprintf("UPDATE %s SET status = 'draft', date_publish = NULL, date_updated = '%s', published = '', published_checksum = NULL WHERE id = %u LIMIT 1;", $this->table_name, WS_Form_Common::get_mysql_date(), $this->id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error resetting form', 'ws-form')); }
		}

		// Read - All
		public function db_read_all($join = '', $where = '', $order_by = '', $limit = '', $offset = '', $count_submit_update_all = true, $bypass_user_capability_check = false, $select = '') {

			// User capability check
			if(!$bypass_user_capability_check && !WS_Form_Common::can_user('read_form')) { return false; }

			global $wpdb;

			// Update count submit on all forms
			if($count_submit_update_all) { self::db_count_update_all(); }

			// Get form data
			if($select == '') { $select = self::DB_SELECT; }
			
			if($join != '') {

				$select_array = explode(',', $select);
				foreach($select_array as $key => $select) {

					$select_array[$key] = $this->table_name . '.' . $select;
				}
				$select = implode(',', $select_array);
			}

			$sql = sprintf("SELECT %s FROM %s", $select, $this->table_name);

			if($join != '') { $sql .= sprintf(" %s", $join); }
			if($where != '') { $sql .= sprintf(" WHERE %s", $where); }
			if($order_by != '') { $sql .= sprintf(" ORDER BY %s", $order_by); }
			if($limit != '') { $sql .= sprintf(" LIMIT %s", $limit); }
			if($offset != '') { $sql .= sprintf(" OFFSET %s", $offset); }

			return $wpdb->get_results($sql, 'ARRAY_A');
		}

		// Delete
		public function db_delete() {

			// User capability check
			if(!WS_Form_Common::can_user('delete_form')) { return false; }

			global $wpdb;

			self::db_check_id();

			// Read the form status
			self::db_read(false, false);

			// If status is trashed, do a permanent delete of the data
			if($this->status == 'trash') {

				// Delete meta
				$ws_form_meta = New WS_Form_Meta();
				$ws_form_meta->object = 'form';
				$ws_form_meta->parent_id = $this->id;
				$ws_form_meta->db_delete_by_object();

				// Delete form groups
				$ws_form_group = New WS_Form_Group();
				$ws_form_group->form_id = $this->id;
				$ws_form_group->db_delete_by_form();

				// Delete form stats
				$ws_form_form_stat = New WS_Form_Form_Stat();
				$ws_form_form_stat->form_id = $this->id;
				$ws_form_form_stat->db_delete();

				// Delete form
				$sql = sprintf("DELETE FROM %s WHERE id = %u;", $this->table_name, $this->id);
				if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error deleting form', 'ws-form')); }

				// Do action
				do_action('wsf_form_delete', $this->id);

			} else {

				// Set status to 'trash'
				self::db_set_status('trash');

				// Do action
				do_action('wsf_form_trash', $this->id);
			}

			return true;
		}

		// Delete trashed forms
		public function db_trash_delete() {

			// Get all trashed forms
			$forms = self::db_read_all('', "status='trash'");

			foreach($forms as $form) {

				$this->id = $form['id'];
				self::db_delete();
			}

			return true;
		}

		// Clone
		public function db_clone() {

			// User capability check
			if(!WS_Form_Common::can_user('create_form')) { return false; }

			global $wpdb;

			// Read form data
			$form = self::db_read(true, true);

			// Convert to arrays
			$form = json_decode(json_encode($form), true);

			// Clone form
			$sql = sprintf("INSERT INTO %s (%s) VALUES ('%s', %u, '%s', '%s', '%s');", $this->table_name, self::DB_INSERT, esc_sql(sprintf(__('%s (Copy)', 'ws-form'), $this->label)), WS_Form_Common::get_mysql_date(), WS_Form_Common::get_mysql_date(), WS_Form_Common::get_user_id(), WS_FORM_VERSION);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error cloning form', 'ws-form')); }

			// Get new form ID
			$this->id = $wpdb->insert_id;

			// Build form (As new)
			self::db_update_from_object($form, true, true);

			// Fix data - Conditional ID's
			self::db_conditional_repair();

			// Fix data - Action ID's
			self::db_action_repair();

			// Fix data - Meta ID's
			self::db_meta_repair();

			// Update checksum
			self::db_checksum();

			// Update form label
			$sql = sprintf("UPDATE %s SET label =  '%s' WHERE id = %u;", $this->table_name, esc_sql(sprintf(__('%s (Copy)', 'ws-form'), $this->label)), $this->id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error update form label', 'ws-form')); }

			return $this->id;
		}

		// Restore
		public function db_restore() {

			// User capability check
			if(!WS_Form_Common::can_user('delete_form')) { return false; }

			self::db_set_status('draft');

			// Do action
			do_action('wsf_form_restore', $this->id);
		}

		// Set status of form
		public function db_set_status($status) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			global $wpdb;

			self::db_check_id();

			// Ensure provided form status is valid
			self::db_check_status($status);

			// Update form record
			$sql = sprintf("UPDATE %s SET status = '%s' WHERE id = %u LIMIT 1;", $this->table_name, esc_sql($status), $this->id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error setting form status', 'ws-form')); }

			return true;
		}

		// Check form status
		public function db_check_status($status) {

			// Check status is valid
			$valid_statuses = explode(',', WS_FORM_STATUS_FORM);
			if(!in_array($status, $valid_statuses)) { parent::db_throw_error(__('Invalid form status: ' . $status, 'ws-form')); }

			return true;
		}

		// Get form status name
		public function db_get_status_name($status) {

			switch($status) {

				case 'draft' : 		return __('Draft', 'ws-form'); break;
				case 'publish' : 	return __('Published', 'ws-form'); break;
				case 'trash' : 		return __('Trash', 'ws-form'); break;
				default :			return $status;
			}
		}

		// Update all count_submit values
		public function db_count_update_all() {

			// Update form submit count
			global $wpdb;

			// Get all forms
			$sql = sprintf("SELECT id, count_stat_view,count_stat_save,count_stat_submit,count_submit,count_submit_unread FROM %s", $this->table_name);
			$forms = $wpdb->get_results($sql, 'ARRAY_A');

			foreach($forms as $form) {

				$this->id = $form['id'];

				// Update
				self::db_count_update($form);
			}
		}

		// Set count_submit
		public function db_count_update($form = false) {

			global $wpdb;

			self::db_check_id();

			// Get form stat totals
			$ws_form_form_stat = New WS_Form_Form_Stat();
			$ws_form_form_stat->form_id = $this->id;
			$count_array = $ws_form_form_stat->db_get_counts();

			// Get form submit total
			$ws_form_submit = New WS_Form_Submit();
			$ws_form_submit->form_id = $this->id;
			$count_submit = $ws_form_submit->db_get_count_submit();
			$count_submit_unread = $ws_form_submit->db_get_count_submit_unread();

			// Check if new values are different from existing values
			$data_same = (

				($form) &&
				(absint($count_array['count_view']) == $form['count_stat_view']) &&
				(absint($count_array['count_save']) == $form['count_stat_save']) &&
				(absint($count_array['count_submit']) == $form['count_stat_submit']) &&
				(absint($count_submit) == $form['count_submit']) &&
				(absint($count_submit_unread) == $form['count_submit_unread'])
			);

			if(!$data_same) {

				// Update form record
				$sql = sprintf("UPDATE %s SET count_stat_view = %u, count_stat_save = %u, count_stat_submit = %u, count_submit = %u, count_submit_unread = %u WHERE id = %u LIMIT 1;", $this->table_name, absint($count_array['count_view']), absint($count_array['count_save']), absint($count_array['count_submit']), absint($count_submit), absint($count_submit_unread), $this->id);
				if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error updating counts', 'ws-form')); }
			}
		}

		// Set count_submit
		public function db_update_count_submit_unread($bypass_user_capability_check = false) {

			global $wpdb;

			self::db_check_id();

			// Get form submit total
			$ws_form_submit = New WS_Form_Submit();
			$ws_form_submit->form_id = $this->id;
			$count_submit_unread = $ws_form_submit->db_get_count_submit_unread($bypass_user_capability_check);

			// Update form record
			$sql = sprintf("UPDATE %s SET count_submit_unread = %u WHERE id = %u LIMIT 1;", $this->table_name, absint($count_submit_unread), $this->id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error updating submit unread count', 'ws-form')); }
		}

		// Get total submissions unread
		public function db_get_count_submit_unread_total() {

			global $wpdb;

			$sql = sprintf("SELECT SUM(count_submit_unread) AS count_submit_unread FROM %s WHERE status IN ('publish', 'draft');", $this->table_name);
			$count_submit_unread = $wpdb->get_var($sql);
			return empty($count_submit_unread) ? 0 : absint($count_submit_unread);
		}

		// Get checksum of current form and store it to database
		public function db_checksum() {

			global $wpdb;

			self::db_check_id();

			// Get form data
			$form_array = self::db_read(true, true, false, true);

			// Remove any variables that change each time checksum calculated or don't affect the public form
			unset($form_array['checksum']);
			unset($form_array['published_checksum']);
			unset($form_array['meta']->tab_index);
			unset($form_array['meta']->breakpoint);

			// Serialize
			$form_serialized = serialize($form_array);

			// MD5
			$this->checksum = md5($form_serialized);

			// SQL escape
			$this->checksum = str_replace("'", "''", $this->checksum);

			// Update form record
			$sql = sprintf("UPDATE %s SET checksum = '%s' WHERE id = %u LIMIT 1;", $this->table_name, esc_sql($this->checksum), $this->id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error setting checksum', 'ws-form')); }

			return $this->checksum;
		}

		// Get all form fields
		public function db_get_fields($get_meta = true) {

			self::db_check_id();

			// Read form and return fields
			$fields = self::db_read($get_meta, true, true);

			return $fields;
		}

		// Get form count by status
		public function db_get_count_by_status($status = '') {

			global $wpdb;

			if(!WS_Form_Common::check_form_status($status, false)) { $status = ''; }

			$sql = sprintf("SELECT COUNT(id) FROM %s WHERE", $this->table_name);
			if($status == '') { $sql .= " NOT(status = 'trash')"; } else { $sql .= " status = '" . esc_sql($status) . "'"; }

			$form_count = $wpdb->get_var($sql);
			if(is_null($form_count)) { $form_count = 0; }

			return $form_count; 
		}

		// Push form from array (if full, include all groups, sections, fields)
		public function db_update_from_object($form, $full = true, $new = false) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			// Store old form ID
			$form_id_old = isset($form['id']) ? $form['id'] : false;

			// Check for form ID in $form
			if(isset($form['id']) && !$new) { $this->id = absint($form['id']); }

			if(!$new) { self::db_check_id(); }

			// Update / Insert
			$this->id = parent::db_update_insert($this->table_name, self::DB_UPDATE, self::DB_INSERT, $form, 'form', $this->id, false);

			// Add to lookups
			if($form_id_old !== false) {

				$this->new_lookup['form'][$form_id_old] = $this->id;
			}

			// Base meta for new records
			if(!isset($form['meta']) || !is_array($form['meta'])) { $form['meta'] = array(); }
			if($new) {

				$settings_form_admin = WS_Form_Config::get_settings_form_admin();
				$meta_data = $settings_form_admin['sidebars']['form']['meta'];
				$meta_keys = WS_Form_Config::get_meta_keys();
				$meta_keys = apply_filters('wsf_form_create_meta_keys', $meta_keys);
				$meta_data_array = self::build_meta_data($meta_data, $meta_keys);
				$form['meta'] = array_merge($meta_data_array, $form['meta']);
			}

			// Update meta
			$ws_form_meta = New WS_Form_Meta();
			$ws_form_meta->object = 'form';
			$ws_form_meta->parent_id = $this->id;
			$ws_form_meta->db_update_from_array($form['meta']);

			// Full update?
			if($full) {

				// Update groups
				$ws_form_group = New WS_Form_Group();
				$ws_form_group->form_id = $this->id;
				$ws_form_group->db_update_from_array($form['groups'], $new);

				if($new) {
					$this->new_lookup['group'] = $this->new_lookup['group'] + $ws_form_group->new_lookup['group'];
					$this->new_lookup['section'] = $this->new_lookup['section'] + $ws_form_group->new_lookup['section'];
					$this->new_lookup['field'] = $this->new_lookup['field'] + $ws_form_group->new_lookup['field'];
				}
			}

			return true;
		}

		// Conditional repair (Repairs a duplicated conditional and replaces ID's with new_lookup values)
		public function db_conditional_repair() {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			// Check form ID
			self::db_check_id();

			// Read conditional
			$ws_form_meta = New WS_Form_Meta();
			$ws_form_meta->object = 'form';
			$ws_form_meta->parent_id = $this->id;
			$conditional = $ws_form_meta->db_get_object_meta('conditional');

			// Data integrity check
			if(!isset($conditional['groups'])) { return true; }
			if(!isset($conditional['groups'][0])) { return true; }
			if(!isset($conditional['groups'][0]['rows'])) { return true; }

			// Run through each conditional (data grid rows)
			$rows = $conditional['groups'][0]['rows'];

			foreach($rows as $row_index => $row) {

				// Data integrity check
				if(!isset($row['data'])) { continue; }
				if(!isset($row['data'][1])) { continue; }

				$data = $row['data'][1];

				// Data integrity check
				if(gettype($data) !== 'string') { continue; }
				if($data == '') { continue; }

				// Converts conditional JSON string to object
				$conditional_json_decode = json_decode($data);
				if(is_null($conditional_json_decode)) { continue; }

				// Process IF conditions
				$if = $conditional_json_decode->if;

				// Run through each group in $if
				foreach($if as $key_if => $group) {

					$conditions = $group->conditions;

					// Run through each condition
					foreach($conditions as $key_condition => $condition) {

						if(isset($condition->object) && isset($this->new_lookup[$condition->object]) && isset($this->new_lookup[$condition->object][$condition->object_id])) {
							$condition->object_id = $this->new_lookup[$condition->object][$condition->object_id];
						}

						// String replace
						foreach($this->new_lookup['field'] as $field_id_old => $field_id_new) {

							if(isset($condition->value)) {

								$condition->value = str_replace('#field(' . $field_id_old . ')', ($field_id_new != '') ? '#field(' . $field_id_new . ')' : '', $condition->value);
								$condition->value = str_replace('#ecommerce_field_price(' . $field_id_old . ')', ($field_id_new != '') ? '#ecommerce_field_price(' . $field_id_new . ')' : '', $condition->value);
							}
						}
					}
				}

				// Process THEN actions
				$then = $conditional_json_decode->then;

				// Run through each group in $then
				foreach($then as $key_then => $then_single) {

					if(isset($then_single->object) && isset($this->new_lookup[$then_single->object]) && isset($this->new_lookup[$then_single->object][$then_single->object_id])) {
						$then_single->object_id = $this->new_lookup[$then_single->object][$then_single->object_id];
					}

					// String replace
					foreach($this->new_lookup['field'] as $field_id_old => $field_id_new) {

						if(isset($then_single->value)) {

							$then_single->value = str_replace('#field(' . $field_id_old . ')', ($field_id_new != '') ? '#field(' . $field_id_new . ')' : '', $then_single->value);
							$then_single->value = str_replace('#ecommerce_field_price(' . $field_id_old . ')', ($field_id_new != '') ? '#ecommerce_field_price(' . $field_id_new . ')' : '', $then_single->value);
						}
					}
				}

				// Process ELSE actions
				$else = $conditional_json_decode->else;

				// Run through each group in $else
				foreach($else as $key_else => $else_single) {

					if(isset($else_single->object) && isset($this->new_lookup[$else_single->object]) && isset($this->new_lookup[$else_single->object][$else_single->object_id])) {
						$else_single->object_id = $this->new_lookup[$else_single->object][$else_single->object_id];
					}

					// String replace
					foreach($this->new_lookup['field'] as $field_id_old => $field_id_new) {

						if(isset($else_single->value)) {

							$else_single->value = str_replace('#field(' . $field_id_old . ')', ($field_id_new != '') ? '#field(' . $field_id_new . ')' : '', $else_single->value);
							$else_single->value = str_replace('#ecommerce_field_price(' . $field_id_old . ')', ($field_id_new != '') ? '#ecommerce_field_price(' . $field_id_new . ')' : '', $else_single->value);
						}
					}
				}

				// Write conditional
				$conditional_json_encode = json_encode($conditional_json_decode);
				$conditional['groups'][0]['rows'][$row_index]['data'][1] = $conditional_json_encode;
				$meta_data_array = array('conditional' => $conditional);
				$ws_form_meta->db_update_from_array($meta_data_array);
			}
		}

		// Action repair (Repairs a duplicated action and replaces ID's with new_lookup values)
		public function db_action_repair() {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			// Check form ID
			self::db_check_id();

			// Read action
			$ws_form_meta = New WS_Form_Meta();
			$ws_form_meta->object = 'form';
			$ws_form_meta->parent_id = $this->id;
			$action = $ws_form_meta->db_get_object_meta('action');

			// Data integrity check
			if(!isset($action['groups'])) { return true; }
			if(!isset($action['groups'][0])) { return true; }
			if(!isset($action['groups'][0]['rows'])) { return true; }

			// Run through each action (data grid rows)
			$rows = $action['groups'][0]['rows'];

			foreach($rows as $row_index => $row) {

				// Data integrity check
				if(!isset($row['data'])) { continue; }
				if(!isset($row['data'][1])) { continue; }

				$data = $row['data'][1];

				// Data integrity check
				if(gettype($data) !== 'string') { continue; }
				if($data == '') { continue; }

				// Converts action JSON string to object
				$action_json_decode = json_decode($data);
				if(is_null($action_json_decode)) { continue; }

				$action_id = $action_json_decode->id;

				// Skip actions that are not installed
				if(!isset(WS_Form_Action::$actions[$action_id])) { continue; }

				// Process metas
				$metas = $action_json_decode->meta;

				// Run through each meta
				foreach($metas as $meta_key => $meta_value) {

					if(is_array($meta_value)) {

						foreach($meta_value as $repeater_key => $repeater_row) {

							if(isset($repeater_row->ws_form_field)) {

								$ws_form_field = $repeater_row->ws_form_field;

								if(isset($this->new_lookup['field']) && isset($this->new_lookup['field'][$ws_form_field])) {

									$metas->{$meta_key}[$repeater_key]->ws_form_field = $this->new_lookup['field'][$ws_form_field];
								}
							}

							foreach($repeater_row as $key => $value) {

								// String replace
								foreach($this->new_lookup['field'] as $field_id_old => $field_id_new) {

									$metas->{$meta_key}[$repeater_key]->{$key} = str_replace('#field(' . $field_id_old . ')', ($field_id_new != '') ? '#field(' . $field_id_new . ')' : '',$metas->{$meta_key}[$repeater_key]->{$key});
									$metas->{$meta_key}[$repeater_key]->{$key} = str_replace('#ecommerce_field_price(' . $field_id_old . ')', ($field_id_new != '') ? '#ecommerce_field_price(' . $field_id_new . ')' : '', $metas->{$meta_key}[$repeater_key]->{$key});
								}
							}
						}

					} else {

						if(isset($this->new_lookup['field']) && isset($this->new_lookup['field'][$meta_value])) {
							$metas->{$meta_key} = $this->new_lookup['field'][$meta_value];
						}

						// String replace
						foreach($this->new_lookup['field'] as $field_id_old => $field_id_new) {

							$metas->{$meta_key} = str_replace('#field(' . $field_id_old . ')', ($field_id_new != '') ? '#field(' . $field_id_new . ')' : '', $metas->{$meta_key});
							$metas->{$meta_key} = str_replace('#ecommerce_field_price(' . $field_id_old . ')', ($field_id_new != '') ? '#ecommerce_field_price(' . $field_id_new . ')' : '', $metas->{$meta_key});
						}
					}
				}

				// Write action
				$action_json_encode = json_encode($action_json_decode);
				$action['groups'][0]['rows'][$row_index]['data'][1] = $action_json_encode;
				$meta_data_array = array('action' => $action);
				$ws_form_meta->db_update_from_array($meta_data_array);
			}
		}

		// Meta repair - Update any field references in meta data
		public function db_meta_repair() {

			// Get field data
			$fields = self::db_get_fields();
			if(count($fields) == 0) { return; }

			// Get field meta
			$meta_keys = WS_Form_Config::get_meta_keys();

			// Look for field meta that uses fields for option lists, and also repeater fields
			$meta_key_check = array();
			foreach($meta_keys as $meta_key => $meta_key_config) {

				// Check for meta_keys that contain #section_id
				if(isset($meta_key_config['default']) && ($meta_key_config['default'] === '#section_id')) {

					$meta_key_check[$meta_key] = array('repeater' => false, 'section_id' => true, 'meta_key' => $meta_key);
					continue;
				}

				// Check for meta_keys that use field for options
				if(isset($meta_key_config['options']) && ($meta_key_config['options'] === 'fields')) {

					$meta_key_check[$meta_key] = array('repeater' => false, 'section_id' => false, 'meta_key' => $meta_key);
					continue;
				}

				// Check for meta_keys that use fields for repeater fields
				if(isset($meta_key_config['type']) && ($meta_key_config['type'] === 'repeater')) {

					if(!isset($meta_key_config['meta_keys'])) { continue; }

					foreach($meta_key_config['meta_keys'] as $meta_key_repeater) {

						if(!isset($meta_keys[$meta_key_repeater])) { continue; }

						$meta_key_repeater_config = $meta_keys[$meta_key_repeater];

						if(isset($meta_key_repeater_config['options']) && ($meta_key_repeater_config['options'] === 'fields')) {

							$meta_key_check[$meta_key] = array('repeater' => true, 'section_id' => false, 'meta_key' => $meta_key_repeater);
							continue;
						}
					}
				}
			}

			// Run through each field and look for these meta keys
			foreach($fields as $field) {

				// Get field meta as array
				$field_meta = (array) $field['meta'];
				if(count($field_meta) == 0) { continue; }

				$field_meta_update = false;

				// Find meta keys that contain only field numbers to make sure we don't update other numeric values
				$keys_to_process = array_intersect_key($field_meta, $meta_key_check);
				foreach($keys_to_process as $meta_key => $meta_value) {

					// Check for repeater
					$repeater = $meta_key_check[$meta_key]['repeater'];
					if($repeater) {

						$repeater_meta_key = $meta_key_check[$meta_key]['meta_key'];

						foreach($field_meta[$meta_key] as $repeater_index => $repeater_row) {

							$meta_value = intval($field_meta[$meta_key][$repeater_index][$repeater_meta_key]);

							if(isset($this->new_lookup['field']) && isset($this->new_lookup['field'][$meta_value])) {

								$field_meta[$meta_key][$repeater_index][$repeater_meta_key] = $this->new_lookup['field'][$meta_value];
								$field_meta_update = true;
							}
						}
					}

					// Check for section_id
					$section_id = $meta_key_check[$meta_key]['section_id'];
					if($section_id) {

						$section_id_meta_key = $meta_key_check[$meta_key]['meta_key'];
						$section_id_old = $field_meta[$section_id_meta_key];
						if(isset($this->new_lookup['section']) && isset($this->new_lookup['section'][$section_id_old])) {

							$field_meta[$section_id_meta_key] = $this->new_lookup['section'][$section_id_old];
							$field_meta_update = true;
						}
					}

					$meta_value = intval($field_meta[$meta_key]);

					if(isset($this->new_lookup['field']) && isset($this->new_lookup['field'][$meta_value])) {

						$field_meta[$meta_key] = $this->new_lookup['field'][$meta_value];
						$field_meta_update = true;
					}
				}

				// Variable replace
				foreach($this->new_lookup['field'] as $field_id_old => $field_id_new) {

					$field_meta = str_replace('#field(' . $field_id_old . ')', ($field_id_new != '') ? '#field(' . $field_id_new . ')' : '', $field_meta, $counter);
					if($counter > 0) { $field_meta_update = true; }

					$field_meta = str_replace('#ecommerce_field_price(' . $field_id_old . ')', ($field_id_new != '') ? '#ecommerce_field_price(' . $field_id_new . ')' : '', $field_meta, $counter);
					if($counter > 0) { $field_meta_update = true; }
				}

				// Update meta data
				if($field_meta_update) {

					// Update meta data
					$ws_form_meta = new WS_Form_Meta();
					$ws_form_meta->object = 'field';
					$ws_form_meta->parent_id = $field['id'];
					$ws_form_meta->db_update_from_array($field_meta);
				}
			}
		}

		// Get form to preview
		public function db_get_preview_form_id() {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			global $wpdb;

			// Get contents of published field
			$sql = sprintf("SELECT id FROM %s WHERE status = 'publish' ORDER BY date_updated DESC LIMIT 1;", $this->table_name);
			$form_id = $wpdb->get_Var($sql);

			if(is_null($form_id)) { return 0; } else { return $form_id; }
		}

		// Get form label
		public function db_get_label() {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			return parent::db_object_get_label($this->table_name, $this->id);
		}

		// Check id
		public function db_check_id() {

			if($this->id <= 0) { parent::db_throw_error(__('Invalid form ID', 'ws-form')); }
			return true;
		}

		// API - POST - Download - JSON
		public function db_download_json() {

			// User capability check
			if(!WS_Form_Common::can_user('export_form')) { parent::api_access_denied(); }

			// Check form ID
			self::db_check_id();

			// Get form
			$form = self::db_read(true, true);

			// Convert to object
			$form = json_decode(json_encode($form));

			// Clean form
			unset($form->checksum);
			unset($form->published_checksum);

			// Stamp form data
			$form->identifier = WS_FORM_IDENTIFIER;
			$form->version = WS_FORM_VERSION;
			$form->time = time();
			$form->status = 'draft';
			$form->count_submit = 0;

			// Add checksum
			$form->checksum = md5(json_encode($form));

			// Build filename
			$filename = 'ws-form-' . strtolower($this->label) . '.json';

			// HTTP headers
			WS_Form_Common::file_download_headers($filename, 'application/octet-stream');

			// Output JSON
			echo json_encode($form);
			
			exit;
		}

		// Find pages a form is embedded on
		public function db_get_locations() {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { parent::api_access_denied(); }

			// Return array
			$form_to_post_array = array();

			// Get post types
			$post_types_exclude = array('attachment');
			$post_types = get_post_types(array('show_in_menu' => true), 'objects', 'or');
			$args_post_types = array();

			foreach($post_types as $post_type) {

				$post_type_name = $post_type->name;

				if(in_array($post_type_name, $post_types_exclude)) { continue; }

				$args_post_types[] = $post_type_name;
			}

			// Post types
			$args = array(

				'post_type' 		=> $args_post_types,
				'posts_per_page' 	=> -1
			);

			// Apply filter
			$args = apply_filters('wsf_get_locations_args', $args);

			// Get posts
			$posts = get_posts($args);

			// Run through each post
			foreach($posts as $post) {

				// Look for forms in the post content
				$form_id_array = self::find_shortcode_in_string($post->post_content);

				// Run filter
				$form_id_array = apply_filters('wsf_get_locations_post', $form_id_array, $post, $this->id);

				if(count($form_id_array) > 0) {

					foreach($form_id_array as $form_id) {

						if(
							($this->id > 0) &&
							($this->id != $form_id)
						) {

							continue;
						}

						// Get post type
						$post_type = get_post_type_object($post->post_type);

						// If found, register in the return array
						if(!isset($form_to_post_array[$form_id])) { $form_to_post_array[$form_id] = array(); }
						if(!isset($form_to_post_array[$form_id][$post->post_type . '-' . $post->ID])) {

							$form_to_post_array[$form_id][$post->post_type . '-' . $post->ID] = array(

								'id'		=> $post->ID,
								'type'		=> $post->post_type,
								'type_name'	=> $post_type->labels->singular_name,
								'title'		=> (empty($post->post_title) ? $post->ID : $post->post_title)
							);
						}
					}
				}
			}

			// Get registered sidebars
			global $wp_registered_sidebars;

			// Get current widgets
			$sidebars_widgets = get_option('sidebars_widgets');
			$wsform_widgets = get_option('widget_' . WS_FORM_WIDGET);

			if($sidebars_widgets !== false) {

				// Run through each widget
				foreach($sidebars_widgets as $sidebars_widget_id => $sidebars_widget) {

					if(!is_array($sidebars_widget)) { continue; }

					// Check if the sidebar exists
					if(!isset($wp_registered_sidebars[$sidebars_widget_id])) { continue; }
					if(!isset($wp_registered_sidebars[$sidebars_widget_id]['name'])) { continue; }

					foreach($sidebars_widget as $setting) {

						// Is this a WS Form widget?
						if(strpos($setting, WS_FORM_WIDGET) !== 0) { continue; }

						// Get widget instance
						$setting_array = explode('-', $setting);
						if(!isset($setting_array[1])) { continue; }
						$widget_instance = intval($setting_array[1]);

						// Check if that widget instance is valid
						if(!isset($wsform_widgets[$widget_instance])) { continue; }
						if(!isset($wsform_widgets[$widget_instance]['form_id'])) { continue; }

						// Get form ID used by widget ID
						$form_id = intval($wsform_widgets[$widget_instance]['form_id']);
						if($form_id === 0) { continue; }

						if(
							($this->id > 0) &&
							($this->id !== $form_id)
						) {

							continue;
						}

						// If found, register in the return array
						if(!isset($form_to_post_array[$form_id])) { $form_to_post_array[$form_id] = array(); }
						if(!isset($form_to_post_array[$form_id]['widget-' . $sidebars_widget_id])) {

							$form_to_post_array[$form_id]['widget-' . $sidebars_widget_id] = array(

								'id'		=> $sidebars_widget_id,
								'type'		=> 'widget',
								'type_name'	=> __('Widget', 'ws-form'),
								'title'		=> $wp_registered_sidebars[$sidebars_widget_id]['name']
							);
						}
					}
				}
			}

			return $form_to_post_array;
		}

		// Find WS Form shortcodes or Gutenberg blocks in a string
		public function find_shortcode_in_string($input) {

			$form_id_array = array();

			// Gutenberg block search
			if(function_exists('parse_blocks')) {

				$parse_blocks = parse_blocks($input);
				foreach($parse_blocks as $parse_block) {

					if(!isset($parse_block['blockName'])) { continue; }
					if(!isset($parse_block['attrs'])) { continue; }
					if(!isset($parse_block['attrs']['form_id'])) { continue; }

					$block_name = $parse_block['blockName'];

					if(strpos($block_name, 'wsf-block/') === 0) {

						$form_id_array[] = intval($parse_block['attrs']['form_id']);
					}
				}
			}

			// Shortcode search
			$has_shortcode = has_shortcode($input, WS_FORM_SHORTCODE);

			$pattern = get_shortcode_regex();
			if(
				preg_match_all('/'. $pattern .'/s', $input, $matches) &&
				array_key_exists(2, $matches) &&
				in_array(WS_FORM_SHORTCODE, $matches[2])
			) {

				foreach( $matches[0] as $key => $value) {

					$get = str_replace(" ", "&" , $matches[3][$key] );
			        parse_str($get, $output);

			        if(isset($output['id'])) {

			        	$form_id_array[] = (int) filter_var($output['id'], FILTER_SANITIZE_NUMBER_INT);
					}
				}
			}

			return $form_id_array;
		}

		public function get_svg($type = 'published') {

			if($type == 'published') {

				// Published
				$form = self::db_read_published();

			} else {

				// Draft
				$form = self::db_read(true, true);
			}

			// Convert form to array
			$form = json_decode(json_encode($form), true);

			// Get form$w SVG
			$ws_form_wizard = new WS_Form_Wizard();
			return $ws_form_wizard->svg($form);
		}
	}