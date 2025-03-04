<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

	<div id="page-container">
		<div id="et-main-area" class="divi-overlays-main-single">
			<div id="main-content">
				<div class="container">
					<div id="content-area" class="clearfix">
						<?php while ( have_posts() ) : the_post(); ?>
							<article id="post-<?php the_ID(); ?>" <?php post_class( 'et_pb_post' ); ?>>

								<div class="entry-content">
								<?php the_content(); ?>
								</div> <!-- .entry-content -->
								
							</article> <!-- .et_pb_post -->

						<?php endwhile; ?>
					</div> <!-- #content-area -->
				</div> <!-- .container -->
			</div> <!-- #main-content -->
		</div> <!-- #et-main-area -->
	</div> <!-- #page-container -->

	<?php wp_footer(); ?>
	
</body>
</html>
