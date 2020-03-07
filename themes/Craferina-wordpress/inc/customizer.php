<?php
/**
 * Craferina Theme Customizer
 *
 * @package Craferina
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function craferina_customize_register( $wp_customize ) {
	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
	$wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

	if ( isset( $wp_customize->selective_refresh ) ) {
		$wp_customize->selective_refresh->add_partial( 'blogname', array(
			'selector'        => '.site-title a',
			'render_callback' => 'craferina_customize_partial_blogname',
		) );
		$wp_customize->selective_refresh->add_partial( 'blogdescription', array(
			'selector'        => '.site-description',
			'render_callback' => 'craferina_customize_partial_blogdescription',
		) );
	}

		/**
	 * PANELS
	 */
	$wp_customize->add_panel('Craferina-wordpress_social_media_panel', array(
		'title' => esc_html__('Social Media', 'Craferina-wordpress'),
		'capability' => 'edit_theme_options',
	));
	/**
	 * SECTIONS
	 */
	$wp_customize->add_section('Craferina-wordpress_facebook_section', array(
		'title' => esc_html__('Facebook', 'Craferina-wordpress'),
		'capability' => 'edit_theme_options',
		'panel' => 'Craferina-wordpress_social_media_panel',
	));

	$wp_customize->add_section('Craferina-wordpress_twitter_section', array(
		'title' => esc_html__('Twitter', 'Craferina-wordpress'),
		'capability' => 'edit_theme_options',
		'panel' => 'Craferina-wordpress_social_media_panel',
	));


	$wp_customize->add_section('Craferina-wordpress_instagram_section', array(
		'title' => esc_html__('Instagram', 'Craferina-wordpress'),
		'capability' => 'edit_theme_options',
		'panel' => 'Craferina-wordpress_social_media_panel',
	));


	/**
	 * SETTINGS
	 */
	$wp_customize->add_setting('Craferina-wordpress_facebook_url', array(
		'transport' => 'refresh', 
		'default' => '',
		'sanitize_callback' =>  'esc_url_raw',
	));

	$wp_customize->add_setting('Craferina-wordpress_twitter_url', array(
		'transport' => 'refresh', 
		'default' => '',
		'sanitize_callback' =>  'esc_url_raw',
	));

	$wp_customize->add_setting('Craferina-wordpress_instagram_url', array(
		'transport' => 'refresh', 
		'default' => '',
		'sanitize_callback' =>  'esc_url_raw',
	));


	/**
	 * CONTROLS (has the same name as setting)
	 */
	$wp_customize->add_control('Craferina-wordpress_facebook_url', array(
		'label' => esc_html__('URL', 'Craferina-wordpress'),
		'description' => esc_html__('Add URL to display Facebook icon/link','Craferina-wordpress' ),
		'section' => 'Craferina-wordpress_facebook_section',
		'type' => 'input',
		'input_attrs' => array(
			'placeholder' => esc_html__('http://facebook.com','Craferina-wordpress' ),
		),
	));

	$wp_customize->add_control('Craferina-wordpress_twitter_url', array(
		'label' => esc_html__('URL', 'Craferina-wordpress'),
		'description' => esc_html__('Add URL to display Twitter icon/link','Craferina-wordpress' ),
		'section' => 'Craferina-wordpress_twitter_section',
		'type' => 'input',
		'input_attrs' => array(
			'placeholder' => esc_html__('http://twitter.com','Craferina-wordpress' ),
		),
	));

	$wp_customize->add_control('Craferina-wordpress_instagram_url', array(
		'label' => esc_html__('URL', 'Craferina-wordpress'),
		'description' => esc_html__('Add URL to display Instagram icon/link','Craferina-wordpress' ),
		'section' => 'Craferina-wordpress_instagram_section',
		'type' => 'input',
		'input_attrs' => array(
			'placeholder' => esc_html__('http://instagram.com','Craferina-wordpress' ),
		),
	));

}
add_action( 'customize_register', 'craferina_customize_register' );

/**
 * Render the site title for the selective refresh partial.
 *
 * @return void
 */
function craferina_customize_partial_blogname() {
	bloginfo( 'name' );
}

/**
 * Render the site tagline for the selective refresh partial.
 *
 * @return void
 */
function craferina_customize_partial_blogdescription() {
	bloginfo( 'description' );
}

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function craferina_customize_preview_js() {
	wp_enqueue_script( 'craferina-customizer', get_template_directory_uri() . '/js/customizer.js', array( 'customize-preview' ), '20151215', true );
}
add_action( 'customize_preview_init', 'craferina_customize_preview_js' );
