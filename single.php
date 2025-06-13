<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package EduBlink
 */

if ( 'sfwd-courses' === get_post_type() ) :
	get_template_part( 'template-parts/single/content', 'sfwd-courses' );
	return;
// elseif( 'course' === get_post_type() && class_exists( 'Sensei_Main' ) ) :
// 		get_template_part( 'sensei/single', 'course' );
// 		return;
elseif ( 'course' === get_post_type() && edublink_is_lifter_lms_activated() ) :
	llms_get_template( 'single-course-content.php' );
	return;
elseif( is_singular( edublink_header_footer_blank_single_post_array() ) ) :
	get_template_part( 'template-parts/single', 'header-blank' ); 
	return;
endif;

get_header();

$single_layout = apply_filters( 'edublink_single_sidebar_layout', edublink_set_value( 'blog_single_layout', 'right-sidebar' ) );
echo '<div class="site-content-inner' . esc_attr( apply_filters( 'edublink_container_class', ' edublink-container' ) ) . '">';
	do_action( 'edublink_before_content' );

	echo '<div id="primary" class="content-area ' . esc_attr( apply_filters( 'edublink_content_area_class', 'edublink-col-lg-8' ) ) . '">';
		echo '<main id="main" class="site-main eb-post-details-page">';
			if ( 'simple_team' === get_post_type() ) :
				get_template_part( 'template-parts/single/content', 'simple_team' );
			else :
				get_template_part( 'template-parts/single', 'post' );
			endif;
		echo '</main>';
	echo '</div>';
	if ( 'no-sidebar' !== $single_layout && 'simple_team' !== get_post_type() ) :
		get_sidebar();
	endif;
	
	do_action( 'edublink_after_content' );
echo '</div>';

get_footer();