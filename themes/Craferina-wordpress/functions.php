<?php
/**
 * Craferina functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Craferina
 */

if ( ! function_exists( 'craferina_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function craferina_setup() {
		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on Craferina, use a find and replace
		 * to change 'craferina' to the name of your theme in all the template files.
		 */
		load_theme_textdomain( 'craferina', get_template_directory() . '/languages' );

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		// This theme uses wp_nav_menu() in one location.
		register_nav_menus( array(
			'menu-1' => esc_html__( 'Primary', 'craferina' ),
		) );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		) );

		add_theme_support(
			'editor-font-sizes',
			array(
				array(
					'name' => esc_html__( 'Regular', 'sample-theme' ),
					'size' => 16,
					'slug' => 'regular'
				),
			)
		);

		// Set up the WordPress core custom background feature.
		add_theme_support( 'custom-background', apply_filters( 'craferina_custom_background_args', array(
			'default-color' => '',
			'default-image' => '',
		) ) );

		// Add theme support for selective refresh for widgets.
		add_theme_support( 'customize-selective-refresh-widgets' );

		/**
		 * Add support for default core block styles.
		 */
		// add_theme_support( 'wp-block-styles' );

		/**
		 * Add support for full width block alignment
		 */
		add_theme_support( 'align-wide' );

		/**
		 * Add support for custom color palette
		 */
		add_theme_support(
			'editor-color-palette',
			array(
				array(
					'name'  => esc_html__( 'Blue', 'sample-theme' ),
					'slug'  => 'pink',
					'color' => '#F7DDD9',
				),
				array(
					'name'  => esc_html__( 'Green', 'sample-theme' ),
					'slug'  => 'dark pink',
					'color' => '#E9B8AC',
				),
				array(
					'name'  => esc_html__( 'Orange', 'sample-theme' ),
					'slug'  => 'pink3',
					'color' => '#C599C5',
				),
				array(
					'name'  => esc_html__( 'Red', 'sample-theme' ),
					'slug'  => 'pink4',
					'color' => '#965B96',
				),
				array(
					'name'  => esc_html__( 'White', 'sample-theme' ),
					'slug'  => 'white',
					'color' => '#FFF',
				),
				array(
					'name'  => esc_html__( 'Black', 'sample-theme' ),
					'slug'  => 'black',
					'color' => '#000',
				),
			)
		);

		/**
		 * Add support for disabling custom colors
		 */
		add_theme_support( 'disable-custom-colors' );

		/**
		 * Add support for font sizes
		 */
		add_theme_support(
			'editor-font-sizes',
			array(
				array(
					'name' => esc_html__( 'Regular', 'sample-theme' ),
					'size' => 16,
					'slug' => 'regular'
				),
			)
		);

		/**
		 * Add support for disabling custom font sizes
		 */
		add_theme_support( 'disable-custom-font-sizes' );

		/**
		 * Add support for core custom logo.
		 *
		 * @link https://codex.wordpress.org/Theme_Logo
		 */
		add_theme_support( 'custom-logo', array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		) );
	}
endif;
add_action( 'after_setup_theme', 'craferina_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function craferina_content_width() {
	// This variable is intended to be overruled from themes.
	// Open WPCS issue: {@link https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/issues/1043}.
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$GLOBALS['content_width'] = apply_filters( 'craferina_content_width', 640 );
}
add_action( 'after_setup_theme', 'craferina_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function craferina_widgets_init() {
	register_sidebar( array(
		'name'          => esc_html__( 'Sidebar', 'craferina' ),
		'id'            => 'sidebar-1',
		'description'   => esc_html__( 'Add widgets here.', 'craferina' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );
}
add_action( 'widgets_init', 'craferina_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function craferina_scripts() {
	wp_enqueue_style( 'craferina-style', get_stylesheet_uri() );

	wp_enqueue_script( 'craferina-navigation', get_template_directory_uri() . '/js/navigation.js', array(), '20151215', true );

	wp_enqueue_script( 'craferina-skip-link-focus-fix', get_template_directory_uri() . '/js/skip-link-focus-fix.js', array(), '20151215', true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'craferina_scripts' );


/**
 * Enqueue fontawesome
 */
wp_register_script( 'FontAwesome', 'https://use.fontawesome.com/releases/v5.8.2/js/all.js', null, null, true );
wp_enqueue_script('FontAwesome');

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-hooks.php';

/**
 * custom block editor 
 */
require get_template_directory() . '/inc/block-editor.php';

/**
 * custom post types
 */
require get_template_directory() . '/inc/post-types.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}

/**
 * Load WooCommerce compatibility file.
 */
if ( class_exists( 'WooCommerce' ) ) {
	require get_template_directory() . '/inc/woocommerce.php';
}


/**
 * Enqueue scripts and styles.
 */
function sample_theme_scripts() {
	wp_enqueue_style(
		'sample-theme-style',
		get_stylesheet_uri()
	);

	wp_enqueue_style(
		'sample-theme-foundation',
		get_template_directory_uri() . '/assets/css/foundation.min.css',
		null,
		'6.5.1'
	);

	wp_enqueue_style(
		'custom-css',
		get_template_directory_uri() . '/custom.css',
		null
		
	);

	wp_enqueue_style(
		'singleProduct-css',
		get_template_directory_uri() . '/assets/css/single-product.css',
		null
		
	);

	wp_enqueue_script(
		'sample-theme-what-input',
		get_template_directory_uri() . '/assets/js/vendor/what-input.js',
		array( 'jquery' ),
		'6.5.1',
		true
	);

	wp_enqueue_script(
		'sample-theme-foundation',
		get_template_directory_uri() . '/assets/js/vendor/foundation.min.js',
		array( 'jquery', 'sample-theme-what-input' ),
		'6.5.1',
		true
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'sample_theme_scripts' );