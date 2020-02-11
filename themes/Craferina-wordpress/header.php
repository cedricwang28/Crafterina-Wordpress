<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Craferina
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<div id="page" class="site grid-x">

	<header id="masthead" class="site-header cell large-2">
		<?php the_custom_logo(); ?>

		<nav id="site-navigation" class="main-navigation cell large-7" role="navigation">
			
			<?php wp_nav_menu( array( 'theme_location' => 'primary', 'menu_id' => 'primary-menu' ) ); ?>
		</nav>

		<div class="cell large-3 medium-3">
			<?php
				if ( ! is_active_sidebar( 'sidebar-1' ) ) {
					return;
				}
			?>
			<div id="secondary" class="widget-area">
				<?php dynamic_sidebar( 'sidebar-1' ); ?>
			</div>
		</div>

	</header>

	<div id="content" class="site-content">
