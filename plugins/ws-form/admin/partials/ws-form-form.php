<?php

	// Form - Admin Page

	// Loader
	WS_Form_Common::loader();
?>
<div id="wsf-wrapper" class="<?php WS_Form_Common::wrapper_classes(); ?>">

<!-- Header -->
<div class="wsf-heading">
<h1 class="wp-heading-inline"><?php _e('Forms', 'ws-form'); ?></h1>
<?php

	if(WS_Form_Common::can_user('create_form')) {
?>
<a class="wsf-button wsf-button-small wsf-button-information" href="<?php echo WS_Form_Common::get_admin_url('ws-form-add'); ?>" title="<?php _e('Add New', 'ws-form'); ?>"><?php WS_Form_Common::render_icon_16_svg('plus'); ?> <?php _e('Add New', 'ws-form'); ?></a>
<?php
	}

	if(WS_Form_Common::can_user('import_form')) {
?>
<button class="wsf-button wsf-button-small" data-action-button="wsf-form-upload"><?php WS_Form_Common::render_icon_16_svg('upload'); ?> <?php _e('Import', 'ws-form'); ?></button>
<?php
	}
?>
</div>
<hr class="wp-header-end">
<!-- /Header -->
<?php

	if(WS_Form_Common::can_user('import_form')) {
?>
<input type="file" class="wsf-file-upload" id="wsf-form-upload-file" accept=".json"/>
<?php
	}

	// Prepare
	$this->ws_form_wp_list_table_form_obj->prepare_items();

	$this->ws_form_wp_list_table_form_obj->views();
?>
<!-- Form Table -->
<form id="wsf-form-list-table" method="get">
<input type="hidden" name="page" value="ws-form">
<?php

	// Display
	$this->ws_form_wp_list_table_form_obj->display();
?>
</form>
<!-- /Form Table -->

<!-- Form Actions -->
<form action="<?php echo WS_Form_Common::get_admin_url(); ?>" id="wsf-action-do" method="post">
<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('wp_rest'); ?>">
<input type="hidden" name="page" value="ws-form">
<input type="hidden" id="wsf-action" name="action" value="">
<input type="hidden" id="wsf-id" name="id" value="">
<input type="hidden" name="paged" value="<?php echo WS_Form_Common::get_query_var('paged'); ?>">
<input type="hidden" name="ws-form-status" value="<?php echo WS_Form_Common::get_query_var('ws-form-status'); ?>">
</form>
<!-- /Form Actions -->

<script>

	(function($) {

		'use strict';

		// On load
		$(function() {

			// Manually inject language strings (Avoids having to call the full config)
			$.WS_Form.settings_form = [];
			$.WS_Form.settings_form.language = [];
			$.WS_Form.settings_form.language['draft'] = '<?php _e('Draft', 'ws-form'); ?>';
			$.WS_Form.settings_form.language['publish'] = '<?php _e('Published', 'ws-form'); ?>';
			$.WS_Form.settings_form.language['form_location_not_found'] = '<?php _e('Form not found in content', 'ws-form'); ?>';
			$.WS_Form.settings_form.language['form_location_found'] = '<?php _e('Form found in %s', 'ws-form'); ?>';
			$.WS_Form.settings_form.language['shortcode_copied'] = '<?php _e('Shortcode copied', 'ws-form'); ?>';

			// Initialize WS Form
			var wsf_obj = new $.WS_Form();
			wsf_obj.wp_list_table_form();
			$('#wsf-form-table h1').html('<?php echo __('Drop file to upload', 'ws-form'); ?>');
		});

	})(jQuery);

</script>

</div>
