<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 */
final class WS_Form {

	// Loader
	protected $loader;

	// Plugin name
	protected $plugin_name;

	// Version
	protected $version;


	// Plugin Public
	public $plugin_public;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 */
	public function __construct() {

		$this->plugin_name = WS_FORM_NAME;
		$this->version = WS_FORM_VERSION;
		$this->woocommerce_active = is_plugin_active('woocommerce/woocommerce.php');

		$plugin_path = plugin_dir_path(dirname(__FILE__));

		// The class responsible for all common functions
		require_once $plugin_path . 'includes/class-ws-form-common.php';


		$this->load_dependencies();

		$this->plugin_public = new WS_Form_Public($this->get_plugin_name(), $this->get_version());

		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_public_shortcodes();
		$this->define_api_hooks();

		$this->wizard = false;
	}

	// Load the required dependencies for this plugin.
	private function load_dependencies() {

		// Configuration (Options, field types, field variables)
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/class-ws-form-config.php';

		// The class responsible for orchestrating the actions and filters of the core plugin.
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/class-ws-form-loader.php';

		// The class responsible for defining internationalization functionality of the plugin.
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/class-ws-form-i18n.php';

		// The class responsible for customizing
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/class-ws-form-customize.php';

		// The classes responsible for populating WP List Tables
		if(is_admin()) {

			require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/wp/class-wp-list-table-ws-form.php';
			require_once WS_FORM_PLUGIN_DIR_PATH . 'admin/class-ws-form-wp-list-table-form.php';
			require_once WS_FORM_PLUGIN_DIR_PATH . 'admin/class-ws-form-wp-list-table-submit.php';
		}

		// The class responsible for defining all actions that occur in the admin area.
		require_once WS_FORM_PLUGIN_DIR_PATH . 'admin/class-ws-form-admin.php';

		// The class responsible for defining all actions that occur in the public-facing side of the site.
		require_once WS_FORM_PLUGIN_DIR_PATH . 'public/class-ws-form-public.php';

		// The class responsible for managing form previews.
		require_once WS_FORM_PLUGIN_DIR_PATH . 'public/class-ws-form-preview.php';

		// The class responsible for the widget
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/class-ws-form-widget.php';

		// Core
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-core.php';

		// Object classes
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-meta.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-form.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-group.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-section.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-field.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-submit-meta.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-submit.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-wizard.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-css.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-form-stat.php';

		// Object classes - Actions
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-action.php';

		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-akismet.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-database.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-message.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-redirect.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-email.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-data-erasure-request.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-data-export-request.php';

		// API core
		require_once WS_FORM_PLUGIN_DIR_PATH . 'api/class-ws-form-api.php';

		// API
		require_once WS_FORM_PLUGIN_DIR_PATH . 'api/class-ws-form-api-helper.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'api/class-ws-form-api-config.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'api/class-ws-form-api-form.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'api/class-ws-form-api-group.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'api/class-ws-form-api-section.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'api/class-ws-form-api-field.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'api/class-ws-form-api-submit.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'api/class-ws-form-api-wizard.php';

		$this->loader = new WS_Form_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the WS_Form_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 */
	private function set_locale() {

		$plugin_i18n = new WS_Form_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 */
	private function define_admin_hooks() {

		$plugin_admin = new WS_Form_Admin($this->get_plugin_name(), $this->get_version());

		// Actions
		$this->loader->add_action('init', $plugin_admin, 'register_block');
		$this->loader->add_action('enqueue_block_editor_assets', $plugin_admin, 'enqueue_block_editor_assets');
		$this->loader->add_action('wp_loaded', $plugin_admin, 'wp_loaded');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles', 9999);	// Make sure we're overriding other styles
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
		$this->loader->add_action('admin_menu', $plugin_admin, 'admin_menu');
		$this->loader->add_action('admin_notices', 'WS_Form_Common', 'admin_messages_render');
		$this->loader->add_action('customize_register', $plugin_admin, 'customize_register');
		$this->loader->add_action('switch_theme', $plugin_admin, 'switch_theme');
		$this->loader->add_filter('plugin_action_links_' . WS_FORM_PLUGIN_BASENAME, $plugin_admin, 'plugin_action_links');
		$this->loader->add_filter('dashboard_glance_items', $plugin_admin, 'dashboard_glance_items');

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 */
	private function define_public_hooks() {

		$this->loader->add_action('init', $this->plugin_public, 'init');
		$this->loader->add_action('wp', $this->plugin_public, 'wp');
		$this->loader->add_action('wp_enqueue_scripts', $this->plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $this->plugin_public, 'enqueue_scripts');
		$this->loader->add_action('wp_footer', $this->plugin_public, 'wp_footer', 1000);
	}

	/**
	 * Register all of the shortcodes related to the public-facing functionality
	 * of the plugin.
	 */
	private function define_public_shortcodes() {

		$this->loader->add_shortcode('ws_form', $this->plugin_public, 'shortcode_ws_form');
	}

	/**
	 * Register all of the hooks related to the API
	 */
	private function define_api_hooks() {

		$plugin_api = new WS_Form_API();

		// Initialize API
		$this->loader->add_action('rest_api_init', $plugin_api, 'api_rest_api_init');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
