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

	<footer id="colophon" class="site-footer grid-x">
		<nav id="social-navigation" class="social-navigation cell large-12 medium-12 grid-x align-middle">
				<?php if(get_theme_mod('Craferina-wordpress_facebook_url') ||  get_theme_mod('Craferina-wordpress_twitter_url') ){?> 
					<ul class="social-media">
						<?php if(get_theme_mod('Craferina-wordpress_facebook_url')) {?> 
							<li class="facebook">
								<a href="<?php echo get_theme_mod('Craferina-wordpress_facebook_url')?>">
								<i class="fab fa-facebook"></i>
								</a>
							</li>
							
						<?php } ?>

						<?php if(get_theme_mod('Craferina-wordpress_twitter_url')) {?> 
							<li class="twitter">
								<a href="<?php echo get_theme_mod('Craferina-wordpress_twitter_url')?>">
									<i class="fab fa-twitter"></i>
								</a>
							</li>
						<?php } ?>

						<?php if(get_theme_mod('Craferina-wordpress_instagram_url')) {?> 
							<li class="instagram">
								<a href="<?php echo get_theme_mod('Craferina-wordpress_instagram_url')?>">
									<i class="fab fa-instagram"></i>
								</a>
							</li>
						<?php } ?>

					</ul>
				<?php } ?>
				
		</nav>
		<!-- #social-media-navigation -->

		<nav id="footer-navigation" class="main-navigation cell large-12 medium-12 grid-x align-middle">
					<?php
					wp_nav_menu( array(
						'theme_location' => 'footer',
						'menu_id'        => 'footer-menu',
					) );
					?>
			</nav><!-- #footer-menu-navigation -->

		<div class="site-info cell large-12">
			<a href="<?php echo esc_url( __( 'https://wordpress.org/', 'ecommerce-theme' ) ); ?>">
				<?php
				/* translators: %s: CMS name, i.e. WordPress. */
				printf( esc_html__( 'Proudly powered by %s', 'ecommerce-theme' ), 'WordPress' );
				?>
			</a>
			<span class="sep"> | </span>
				<?php
				/* translators: 1: Theme name, 2: Theme author. */
				printf( esc_html__( 'Theme: %1$s by %2$s.', 'ecommerce-theme' ), 'Craferina', '<a href="/">Craferina</a>' );
				?>
		</div><!-- .site-info -->
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
