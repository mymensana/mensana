<?php
/**
 * Template for displaying single course
 *
 * @package Tutor\Templates
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.0.0
 */

$course_id     = get_the_ID();
$course_rating = tutor_utils()->get_course_rating( $course_id );
$is_enrolled   = tutor_utils()->is_enrolled( $course_id, get_current_user_id() );

// Prepare the nav items.
$course_nav_item = apply_filters( 'tutor_course/single/nav_items', tutor_utils()->course_nav_items(), $course_id );
$is_public       = \TUTOR\Course_List::is_public( $course_id );
$is_mobile       = wp_is_mobile();

$enrollment_box_position = tutor_utils()->get_option( 'enrollment_box_position_in_mobile', 'bottom' );
if ( '-1' === $enrollment_box_position ) :
	$enrollment_box_position = 'bottom';
endif;
$student_must_login_to_view_course = tutor_utils()->get_option( 'student_must_login_to_view_course' );

tutor_utils()->tutor_custom_header();

if ( ! is_user_logged_in() && ! $is_public && $student_must_login_to_view_course ) :
	tutor_load_template( 'login' );
	tutor_utils()->tutor_custom_footer();
	return;
endif;

$course_details_style = edublink_set_value( 'tl_course_details_style', 4 );
$course_details_sidebar = edublink_set_value( 'tl_course_details_sidebar_status', true );
$course_sidebar_sticky = edublink_set_value( 'tl_course_details_sidebar_sticky', true );
$course_details_column = 'edublink-col-lg-12';
$siebar_main_content = 'course-summary';
$course_sidebar_class = 'ed-course-sidebar';

if ( isset( $_GET['course_details'] ) ) :
	$course_details_style = in_array( $_GET['course_details'], array( 1, 2, 3, 4, 5, 6 ) ) ? $_GET['course_details'] : 1;
endif;

if ( $course_details_sidebar ) :
	$course_details_column = 'edublink-col-lg-8';
endif;

if ( isset( $_GET['disable_sidebar'] ) ) :
	$course_details_column = 'edublink-col-lg-12';
	$course_details_sidebar = false;
endif;

if ( isset( $_GET['sidebar_sticky'] ) ) :
	$course_sidebar_sticky = true;
endif;

if ( in_array( $course_details_style, array( 3, 4 ) ) ) :
	if ( $course_sidebar_sticky ) :
		wp_enqueue_script( 'theia-sticky-sidebar' );
		$siebar_main_content .= $siebar_main_content . ' ' . 'eb-sticky-sidebar-parallal-content';
		$course_sidebar_class = $course_sidebar_class . ' ' . 'eb-sidebar-sticky';
	endif;
endif;

edublink_tl_course_details_header( $course_details_style );

do_action( 'tutor_course/single/before/wrap' ); ?>
<div <?php tutor_post_class( 'tutor-full-width-course-top tutor-course-top-info tutor-page-wrap tutor-wrap-parent' ); ?>>
    <?php
	echo '<div class="edublink-course-details-page eb-tl-course-single-page eb-course-single-style-' . esc_attr( $course_details_style ) . '">';
		echo '<div class="edublink-container">';
			echo '<div class="edublink-row">';
				echo '<div id="eb-tl-course-details-content" class="' . esc_attr( $siebar_main_content ) . ' ' . apply_filters( 'courese_details_columnn', $course_details_column ) . '">';
					echo '<div class="eb-course-details-page-content">';
						if ( $course_details_style == '4' ) :
							tutor_load_template( 'custom.course-details.style-4' );
						endif;

						do_action( 'tutor_course/single/before/inner-wrap' );

						echo '<div class="tutor-course-details-tab">';

							if ( is_array( $course_nav_item ) && count( $course_nav_item ) > 1 ) :
								echo '<div class="tutor-is-sticky">';
									tutor_load_template( 'single.course.enrolled.nav', array( 'course_nav_item' => $course_nav_item ) );
								echo '</div>';
							endif;


							echo '<div class="eb-tl-course-tabs tutor-tab tutor-pt-24">';
								foreach ( $course_nav_item as $key => $subpage ) :
									$active = '';
									if ( 'info' == $key ) :
										$active = ' is-active';
									endif;

									echo '<div id="tutor-course-details-tab-' . esc_attr( $key ) . '" class="tutor-tab-item' . esc_attr( $active ) . '">';
										do_action( 'tutor_course/single/tab/' . $key . '/before' );
										$method = $subpage['method'];
										if ( is_string( $method ) ) :
											$method();
										else :
											$_object = $method[0];
											$_method = $method[1];
											$_object->$_method( get_the_ID() );
										endif;

										do_action( 'tutor_course/single/tab/' . $key . '/after' );
									echo '</div>';
								endforeach;
							echo '</div>';
						echo '</div>';
						do_action( 'tutor_course/single/after/inner-wrap' );
					echo '</div>';
				echo '</div>';

				if ( $course_details_sidebar ) :
					echo '<div class="' . esc_attr( $course_sidebar_class ) . ' ' . apply_filters( 'courese_details_sidebar_columnn', 'edublink-col-lg-4' ) . '">';
						edublink_tl_course_content_sidebar();
					echo '</div>';
				endif;
			echo '</div>';
		echo '</div>';
	echo '</div>';
echo '</div>';

do_action( 'tutor_course/single/after/wrap' );

edublink_tl_related_courses();

tutor_utils()->tutor_custom_footer();
