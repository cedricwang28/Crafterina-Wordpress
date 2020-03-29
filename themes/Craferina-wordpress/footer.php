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

	<?php if( is_front_page() ) { ?>

		<section class="footerarticleWrapper grid-container">
			<div id="footerArticle" class="site-main grid-x">
				
				<h1 class="cell large-12">Craft Article</h1>
			
				<article class="cell large-12" id="bodyText">
					<?php
					/**
					 * Custom WP_Query
					 */
					$args = array(
						'post_type'      => 'craft_articles',
						'posts_per_page' => 3,
					);

					$articles = new WP_Query( $args );

					if ( $articles->have_posts() ) {
					?>
						<div>
							<div class="post-list height-1">
								<div class="grid-x paddingAround grid-margin-x">
									
									<?php
									while ( $articles->have_posts() ) {
										$articles->the_post();
										?>
										<div class="cell small-12 medium-6 large-4 custom post-list-wrapper grid-margin-x grid-x">
											<div id="post-<?php echo get_the_ID(); ?>">
												<!-- post-thumbnail -->
												<div class="custom post-thumbnail">
													<a href="<?php the_permalink(); ?>" target="_blank"><?php the_post_thumbnail('small-thumbnail'); ?></a>
												</div><!-- post-thumbnail -->
												<a href="<?php the_permalink(); ?>" target="_blank"><h4 class="text-center postTitle"><?php echo get_the_title(); ?></h4></a>
												<p class="postExcerpt"><?php echo get_the_excerpt(); ?></p>
												<a class="readMore" href="<?php the_permalink(); ?>" target="_blank">Read More</a>
											</div>
										</div>
									<?php } ?>
								</div>
							</div>
						</div>
					<?php
					}
					wp_reset_postdata();
					?>
				
				</article>	

			</div><!-- footer article section -->
		</section>
	<?php
	}
	?>
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
