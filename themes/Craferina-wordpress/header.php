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
<div id="page" class="site">

	<header id="masthead" class="site-header  grid-x">
		<div class="cell large-2 middle-3 small-3">
			<?php the_custom_logo(); ?>
		</div>
		
		<nav id="site-navigation" class="main-navigation cell large-10 middle-9 small-9" role="navigation">
			
			<?php wp_nav_menu( array( 'theme_location' => 'menu-1', 'menu_id' => 'primary-menu' ) ); ?>
			<i class="fas fa-bars"></i>
		</nav>

		<!-- <div class="cell large-4 medium-4">
			<?php
				if ( ! is_active_sidebar( 'sidebar-1' ) ) {
					return;
				}
			?>
			<div id="secondary" class="widget-area">
				<?php dynamic_sidebar( 'sidebar-1' ); ?>
			</div>
		</div> -->

	</header>

	<div id="content" class="site-content">
