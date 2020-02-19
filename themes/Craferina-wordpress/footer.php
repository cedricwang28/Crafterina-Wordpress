<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package E-commerce_Theme
 */

?>

	</div><!-- #content -->

	<footer id="colophon" class="site-footer">
	<nav id="social-navigation" class="main-navigation cell large-12 medium-12 grid-x align-middle">
					<!-- <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false"><?php esc_html_e( 'Primary Menu', 'ecommerce-theme' ); ?></button> -->
					<?php
					wp_nav_menu( array(
						'theme_location' => 'social',
						'menu_id'        => 'social-menu',
					) );
					?>
			</nav><!-- #social-links-navigation -->
			<nav id="footer-navigation" class="main-navigation cell large-12 medium-12 grid-x align-middle">
					<!-- <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false"><?php esc_html_e( 'Primary Menu', 'ecommerce-theme' ); ?></button> -->
					<?php
					wp_nav_menu( array(
						'theme_location' => 'footer',
						'menu_id'        => 'footer-menu',
					) );
					?>
			</nav>

	</footer><!-- #colophon -->
</div><!-- #page -->


<?php wp_footer(); ?>

</body>
</html>
