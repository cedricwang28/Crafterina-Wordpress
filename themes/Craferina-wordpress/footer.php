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
		<div class="site-info-01">
			<a href="<?php echo esc_url( __( '', 'ecommerce-theme' ) ); ?>">
			<h3> Crafterina </h3>
				<?php
				/* translators: %s: CMS name, i.e. WordPress. */
				printf( esc_html__( 'About %s'), '' );
				?><br/>
				<?php
				printf( esc_html__( 'Contact Us %s'), '' );
				?><br/>
				<?php
				printf( esc_html__( 'Carrers %s'), '' );
				?>
			</a>
			<span class="sep">  </span>
				
		</div><!-- .site-info -->


		<div class="site-info-02">
			<a href="<?php echo esc_url( __( '', 'ecommerce-theme' ) ); ?>">
			<h3> Deals </h3>
				<?php
				/* translators: %s: CMS name, i.e. WordPress. */
				printf( esc_html__( 'Coupons %s'), '' );
				?><br/>
				<?php
				printf( esc_html__( 'Products to discover %s'), '' );
				?><br/>
				<?php
				printf( esc_html__( 'Occasional deals %s'), '' );
				?>
			</a>
			<span class="sep">  </span>
				
		</div><!-- .site-info -->
		
		<div class="site-info-03">
			<a href="<?php echo esc_url( __( '', 'ecommerce-theme' ) ); ?>">
			<h3> Policies </h3>
				<?php
				/* translators: %s: CMS name, i.e. WordPress. */
				printf( esc_html__( 'Return %s'), '' );
				?><br/>
				<?php
				printf( esc_html__( 'Exchange %s'), '' );
				?><br/>
				<?php
				printf( esc_html__( 'Terms of use %s'), '' );
				?><br/>
				<?php
				printf( esc_html__( 'Security %s'), '' );
				?><br/>
				<?php
				printf( esc_html__( 'Privacy %s'), '' );
				?>
			</a>
			<span class="sep">  </span>
				
		</div><!-- .site-info -->
		
		<div class="site-info-04">
			<a href="<?php echo esc_url( __( '', 'ecommerce-theme' ) ); ?>">
			<h3> Contact Info </h3>
				<?php
				/* translators: %s: CMS name, i.e. WordPress. */
				printf( esc_html__( '2000 Simcoe street, Oshawa, Ontario %s'), '' );
				?><br/>
				<?php
				printf( esc_html__( '9876543210 %s'), '' );
				?><br/>
				<?php
				printf( esc_html__( 'jewellery@craferina.com %s'), '' );
				?>
			</a>
			<span class="sep">  </span>
				
		</div>

		<!-- .site-info -->
	</footer><!-- #colophon -->
</div><!-- #page -->

</div><!-- #content -->

	<footer id="colophon" class="site-footer">
	<div class="site-info">
            <a href="<?php echo esc_url( __( '', '' ) ); ?>">
                <?php
                /* translators: %s: CMS name, i.e. WordPress. */
                printf( esc_html__( ' Copyright &copy %s 2020', '' ), '' );
                ?>
            </a>
            <span class="sep"> | </span>
                <?php
                /* translators: 1: Theme name, 2: Theme author. */
                printf( esc_html__( ' All Rights Reserved' ), 'Craferina', '<a href="/">Craferina</a>' );
                ?>
        </div>

	</footer><!-- #colophon -->
</div><!-- #page -->


<?php wp_footer(); ?>

</body>
</html>
