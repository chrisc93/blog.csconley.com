<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package totomo
 */
?>

	</div><!-- #content -->
	<footer id="colophon" class="site-footer container" role="contentinfo">
		<div class="credit">
		<div class="row">
			<div class="col-md-1"></div>
			<div class="col-md-4">
				<span class="underline-subheadings">Latest Posts</span>
				<ul class="no-bullets">
					<?php
						$args = array( 'numberposts' => '5', 'post_status' => 'publish' );
						$recent_posts = wp_get_recent_posts( $args );
						foreach( $recent_posts as $recent ){
							echo '<li><a class="small-font" href="' . get_permalink($recent["ID"]) . '">' .   $recent["post_title"].'</a> </li> ';
						}
					?>
				</ul>
			</div>
			<div class="col-md-4">
				<span class="underline-subheadings">Archive</span>
				<ul class="no-bullets">
					<?php
						$args = array( 'limit' => '5' );
						$months = wp_get_archives( $args );
					?>
				</ul>
			</div>
			<div class="col-md-2">
				<span class="underline-subheadings">Find More</span>
				<ul class="no-bullets">
					<li><?php get_search_form(); ?></li>
				</ul>
			</div>
			<div class="col-md-1"></div>
		</div>
	</footer>
</div><!-- #page -->


</body>
</html>
