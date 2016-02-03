<?php
/**
 * The template for displaying all single posts.
 *
 * @package totomo
 */

get_header(); ?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

		<?php while ( have_posts() ) : the_post(); ?>
			<?php get_template_part( 'content', 'single' ); ?>
			<?php totomo_get_author_box(); ?>
			<?php totomo_related_post(); ?>
			
			<?php
			// Don't show navigation
			//the_post_navigation ( $args = array(
			//    'next_text'          => '',
			//    'prev_text'          => '',
			//) );
			?>

			<?php
				// If comments are open or we have at least one comment, load up the comment template
				if ( comments_open() || get_comments_number() ) :
					comments_template();
				endif;
			?>

		<?php endwhile; // end of the loop. ?>

		</main><!-- #main -->
	</div><!-- #primary -->
<?php get_footer(); ?>
