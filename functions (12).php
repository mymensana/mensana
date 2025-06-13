<?php

require_once get_template_directory() . '/tutor/custom/helper.php';
use \EduBlink\Tutor\Helper;
use \EduBlink\Filter;

/**
 * Course Search Post Type
 */
add_filter( 'edublink_course_search_post_type', 'edublink_tl_course_search_post_type' );
if ( ! function_exists( 'edublink_tl_course_search_post_type' ) ) :
	function edublink_tl_course_search_post_type() {
		return 'courses';
	}
endif;

/**
 * Header Course Category Slug
 */
add_filter( 'edublink_header_course_lms_cat_slug', 'edublink_header_course_tl_cat_slug' );
if ( ! function_exists( 'edublink_header_course_tl_cat_slug' ) ) :
	function edublink_header_course_tl_cat_slug() {
		return 'course-category';
	}
endif;

/**
 * Tutor LMS specific scripts & stylesheets.
 *
 * @return void
 * 
 * @since 1.0.0
 */
if ( ! function_exists( 'edublink_tutor_scripts' ) ) :
	function edublink_tutor_scripts() {
		$dependency = array();
		$dependency = apply_filters( 'edublink_tutor_css_dependency', $dependency );
		$handler = apply_filters( 'edublink_tutor_css_handler', 'edublink-tutor-style' );
		wp_enqueue_style( $handler, get_template_directory_uri() . '/assets/css/tutor-lms.css', $dependency, EDUBLINK_THEME_VERSION );
	}
endif;
add_action( 'wp_enqueue_scripts', 'edublink_tutor_scripts' );

/**
 * Generate wishlist icon
 * 
 * @since 1.0.0
 */
if ( ! function_exists( 'edublink_tutor_wishlist_icon' ) ) :
	function edublink_tutor_wishlist_icon( $course_id ) {
		$course_id      = get_the_ID();
		$is_wish_listed = tutor_utils()->is_wishlisted( $course_id );

		$login_url_attr = '';
		$action_class   = '';

		if ( is_user_logged_in() ) :
			$action_class = apply_filters( 'tutor_wishlist_btn_class', 'tutor-course-wishlist-btn' );
		else :
			$action_class = apply_filters( 'tutor_popup_login_class', 'tutor-open-login-modal' );

			if ( ! tutor_utils()->get_option( 'enable_tutor_native_login', null, true, true ) ) :
				$login_url_attr = 'data-login_url="' . esc_url( wp_login_url() ) . '"';
			endif;
		endif;
			//phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- $login_url_attr contain safe data
			echo '<a href="javascript:;" ' . $login_url_attr . ' class="' . esc_attr( $action_class ) . ' save-bookmark-btn" data-course-id="' . esc_attr( $course_id ) . '">
				<i class="edublink-wishlist-wrapper ' . ( $is_wish_listed ? 'tutor-icon-bookmark-bold' : 'tutor-icon-bookmark-line' ) . '"></i>
			</a>';
	}
endif;

/**
 * Sale tag for promotional courses
 * 
 * @since 1.0.0
 */
if ( ! function_exists( 'edublink_tutor_course_sale_tag' ) ) :
	function edublink_tutor_course_sale_tag() {
		$course_price  = tutor_utils()->get_raw_course_price( get_the_ID() );
		$regular_price = $course_price->regular_price;
		$sale_price    = $course_price->sale_price;
		if ( empty( $sale_price ) ) :
			return;
		endif;
		if ( $regular_price != $sale_price ) :
			printf( '<span class="label">%s</span>', apply_filters( 'edublink_course_sale_tag_text', __( 'Sale', 'edublink' ) ) );
		endif;
	}
endif;

/**
 * post_class extends for tutor courses
 * 
 * @since 1.0.0
 */
if ( ! function_exists( 'edublink_tutor_course_class' ) ) :
    function edublink_tutor_course_class( $default = array() ) {
		$terms      = get_the_terms( get_the_ID(), 'course-category' );
		$terms_html = array();
		if ( $terms ) :
			foreach ( $terms as $term ) :
				$terms_html[] = $term->slug;
			endforeach;
		endif;
		$all_classes = array_merge( $terms_html, $default );
		$classes     = apply_filters( 'edublink_tutor_course_class', $all_classes );
        post_class( $classes );
    }
endif;


/**
 * Tutor Course Details Header
 *
 */
if( ! function_exists( 'edublink_tl_course_details_header' ) ) :
	function edublink_tl_course_details_header( $style ) {
		switch ( $style ):
			case 1:
				edublink_tl_course_details_header_default_style();
				break;
			case 2:
				edublink_tl_course_details_header_default_style( 'dark-version' );
				break;
			case 3:
				edublink_tl_course_details_header_default_style();
				break;
			case 4:
				edublink_tl_course_details_header_style_2();
				break;
			case 5:
				edublink_tl_course_details_header_default_style( 'style-5' );
				break;
			case 6:
				edublink_tl_course_details_header_default_style( 'style-6' );
				break;
			default:
			edublink_tl_course_details_header_default_style();
		endswitch;
	}
endif;

/**
 * Tutor Course Details Header Default Style
 *
 */
if( ! function_exists( 'edublink_tl_course_details_header_default_style' ) ) :
	function edublink_tl_course_details_header_default_style( $style = null ) {
		$style = $style ? ' ' . esc_attr( $style ) : '';
		echo '<div class="edublink-course-page-header' . esc_attr( $style ) . '">';
			echo '<div class="eb-course-header-breadcrumb">';
				echo '<div class="' . esc_attr( apply_filters( 'edublink_breadcrumb_container_class', 'edublink-container' ) ) . '">';
					do_action( 'edublink_breadcrumb' );
				echo '</div>';
			echo '</div>';

			echo '<div class="eb-course-header-breadcrumb-content">';
				echo '<div class="' . esc_attr( apply_filters( 'edublink_breadcrumb_container_class', 'edublink-container' ) ) . '">';
					echo '<div class="edublink-course-breadcrumb-inner">';
						echo '<div class="edublink-course-title">';
							echo '<h1 class="entry-title">';
								the_title(); 
							echo '</h1>';
						echo '</div>';
						
						echo '<div class="edublink-course-header-meta">';
							edublink_breadcrumb_tl_course_meta();
						echo '</div>';
					echo '</div>';
				echo '</div>';
				if ( ' style-6' === $style  ) :
					edublink_course_breadcrumb_header_6_shapes();
				endif;
			echo '</div>';
			
			if ( ' style-6' !== $style ) :
				edublink_breadcrumb_shapes();
			endif;
		echo '</div>';
	}
endif;

/**
 * Tutor Course Details Header Style 2
 *
 */
if( ! function_exists( 'edublink_tl_course_details_header_style_2' ) ) :
	function edublink_tl_course_details_header_style_2() {
		$has_bg_image = '';
		$breadcrumb_img   = edublink_set_value( 'tl_course_breadcrumb_image' );
		$title = get_the_title();
		$style = array();
		
		if ( isset( $breadcrumb_img['url'] ) && ! empty( $breadcrumb_img['url'] ) ) :
			$style[] = 'background-image:url(\'' . esc_url( $breadcrumb_img['url'] ) . '\' )';
			$has_bg_image = 'edublink-breadcrumb-has-bg course-header-4';
		else :
			$has_bg_image = 'edublink-breadcrumb-empty-bg course-header-4';
		endif;

		$extra_style = ! empty( $style ) ? ' style="' . implode( "; ", $style ) . '"' : "";

		edublink_breadcrumb_style_1( $title, $has_bg_image, $extra_style );
	}
endif;

/**
 * Tutor Course Breaecrumb Meta
 *
 */
if( ! function_exists( 'edublink_breadcrumb_tl_course_meta' ) ) :
	function edublink_breadcrumb_tl_course_meta() {
		$category = edublink_category_by_id( get_the_ID(), 'course-category' );
		echo '<ul class="eb-course-header-meta-items">';
			echo '<li class="instructor">';
				echo '<i class="icon-58"></i>';
				_e( 'By', 'edublink' );
				echo ' ';
				the_author();
			echo '</li>';

			if ( $category ) :
				echo '<li class="category"><i class="icon-59"></i>' . wp_kses_post( $category ) . '</li>';
			endif;

			echo '<li class="rating">';
				Helper::rating(2);
			echo '</li>';
		echo '</ul>';
	}
endif;

/**
 * Tutor Course Details Page Instructor Tab
 *
 */
if( ! function_exists( 'edublink_tutor_course_single_instructor_tab' ) ) :
	function edublink_tutor_course_single_instructor_tab( $tabs, $course_id ) {
		$tab_array = [];
		if ( edublink_set_value( 'tl_course_instructor_tab_status', true ) ) :
			foreach ( $tabs as $key => $tab) :
				if ( 'info' === $key ) :
					$tab_array['info'] = $tab;
					$tab_array['instructor'] = array(
						'title'  => __( 'Instructor', 'edublink' ),
						'method' => 'tutor_course_instructors_html'
					);
				else :
					$tab_array[$key] = $tab;
				endif;
			endforeach;
			return $tab_array;
		else :
			return $tabs;
		endif;
	}
endif;
add_filter( 'tutor_course/single/nav_items', 'edublink_tutor_course_single_instructor_tab', 10, 2 );

/**
 * Tutor Course Details Page Info Tab Content
 *
 */
add_filter( 'tutor_course/single/nav_items', 'edublink_tutor_course_detials_info_tab_content', 10, 2 );
if( ! function_exists( 'edublink_tutor_course_detials_info_tab_content' ) ) :
	function edublink_tutor_course_detials_info_tab_content( $info_tab_items, $id ) {
		if ( ! empty( $info_tab_items ) ) :
			foreach ($info_tab_items as $key => &$item) :
				if ( 'info' === $key ) :
					$item['method'] = 'edublink_tutor_course_single_info_tab_content';
				endif;
			endforeach;
		endif;
		return $info_tab_items;
	}
endif;

/**
 * Tutor Course Details Nav Items Custom Text
 *
 */
add_filter( 'tutor_course/single/nav_items', 'edublink_tutor_course_detials_nav_item_text', 10, 2 );
if( ! function_exists( 'edublink_tutor_course_detials_nav_item_text' ) ) :
	function edublink_tutor_course_detials_nav_item_text( $info_tab_items, $id ) {
		if ( ! empty( $info_tab_items ) ) :
			foreach ( $info_tab_items as $key => &$item ) :
				if ( ( 'info' === $key ) && ! empty( edublink_set_value( 'tl_course_info_tab_title' ) ) ) :
					$item['title']  = edublink_set_value( 'tl_course_info_tab_title' );
				elseif ( ( 'reviews' === $key ) && ! empty( edublink_set_value( 'tl_course_reviews_tab_title' ) ) ) :
					$item['title']  = edublink_set_value( 'tl_course_reviews_tab_title' );
				elseif ( ( 'questions' === $key ) && ! empty( edublink_set_value( 'tl_course_q_and_a_tab_title' ) ) ) :
					$item['title']  = edublink_set_value( 'tl_course_q_and_a_tab_title' );
				elseif ( ( 'announcements' === $key ) && ! empty( edublink_set_value( 'tl_course_announcements_tab_title' ) ) ) :
					$item['title']  = edublink_set_value( 'tl_course_announcements_tab_title' );
				elseif ( ( 'instructor' === $key ) && ! empty( edublink_set_value( 'tl_course_instructor_tab_title' ) ) ) :
					$item['title']  = edublink_set_value( 'tl_course_instructor_tab_title' );
				endif;
			endforeach;
		endif;
		return $info_tab_items;
	}
endif;

if( ! function_exists( 'edublink_tutor_course_single_info_tab_content' ) ) :
	function edublink_tutor_course_single_info_tab_content() {
		tutor_course_content();
		tutor_course_benefits_html();
		tutor_course_requirements_html();
		tutor_course_target_audience_html();
		tutor_course_topics();
		tutor_course_tags_html(); 
	}
endif;

/**
 * Right Side Content
 */
if ( ! function_exists( 'edublink_tl_course_content_sidebar' ) ) :
	function edublink_tl_course_content_sidebar() {
		$style = edublink_set_value( 'tl_course_details_style', '1' );
		$preview_thumb = edublink_set_value( 'tl_course_preview_thumb', true );
		$button_status = edublink_set_value( 'tl_course_sidebar_button', true );
		$social_share_status = edublink_set_value( 'tl_course_sidebar_social_share', true );
		$extra_class = $preview_thumb ? 'enable' : 'disable';

		if ( isset( $_GET['course_details'] ) ) :
			$style = in_array( $_GET['course_details'], array( 1, 2, 3, 4, 5, 6 ) ) ? $_GET['course_details'] : 1;
		endif;

		echo '<div class="edublink-course-details-sidebar eb-course-single-' . esc_attr( $style ) . ' sidebar-' . esc_attr( $extra_class ) . '">';
			echo '<div class="edublink-course-details-sidebar-inner">';

				do_action( 'tutor_course/single/before/sidebar' );

				if ( $preview_thumb && '4' != $style ) :
					edublink_tl_course_preview();
				endif;

				echo '<div class="edublink-course-details-sidebar-content">';

					do_action( 'edublink_tl_course_sidebar_after_meta' );

					edublink_tl_course_meta_data();

					do_action( 'edublink_tl_course_sidebar_after_button' );

					do_action( 'tutor_course/single/after/sidebar' );

					tutor_load_template( 'single.course.course-entry-box' );
					
					if ( $social_share_status ) :
						$social_heading = edublink_set_value( 'tl_course_sidebar_social_share_heading', __( 'Share On:', 'edublink') );
						echo '<div class="edublink-single-event-social-share">';
							echo '<h4 class="share-title">' . esc_html( $social_heading ) . '</h4>';
							get_template_part( 'template-parts/social', 'share' );
						echo '</div>';
					endif;

					do_action( 'edublink_tl_course_sidebar_after_social_share' );
				echo '</div>';
			echo '</div>';
		echo '</div>';
	}
endif;

/**
 * Right Side Course Preview
 */
if ( ! function_exists( 'edublink_tl_course_preview' ) ) :
	function edublink_tl_course_preview() {
		if ( edublink_set_value( 'tl_course_preview', 'default' ) ) :
			tutor_utils()->has_video_in_single() ? tutor_course_video() : get_tutor_course_thumbnail();
		endif;
	}
endif;

/**
 * Right Side Meta Data
 */
if ( ! function_exists( 'edublink_tl_course_meta_data' ) ) :
	function edublink_tl_course_meta_data() {
		$data = Filter::TL_Data();
		$heading_status = edublink_set_value( 'tl_course_sidebar_heading_status', true );
		$heading = edublink_set_value( 'tl_course_sidebar_heading_text', __( 'Course Includes:', 'edublink') );
		if ( $heading_status && $heading ) :
			echo '<h4 class="widget-title">' . esc_html( $heading ). '</h4>';
		endif;

		echo '<ul class="edublink-course-meta-informations">';
			do_action( 'edublink_tl_course_meta_before' );

			if ( edublink_set_value( 'tl_course_sidebar_price_status', true ) ) :
				$price_label = edublink_set_value( 'tl_course_sidebar_price_label' ) ? edublink_set_value( 'tl_course_sidebar_price_label' ) : __( 'Price:', 'edublink' );
				echo '<li class="edublink-course-details-features-item course-price">';
					echo '<span class="edublink-course-feature-item-label">';
						echo '<i class="icon-60"></i>';
						echo esc_html( $price_label );
					echo '</span>';

					echo '<span class="edublink-course-feature-item-value">';
						echo wp_kses_post( $data['price'] );
					echo '</span>';
				echo '</li>';
			endif;

			if ( edublink_set_value( 'tl_course_instructor', true ) ) :
				$instructor_label = edublink_set_value( 'tl_course_instructor_label' ) ? edublink_set_value( 'tl_course_instructor_label' ) : __( 'Instructor:', 'edublink' );
				echo '<li class="edublink-course-details-features-item course-instructor">';
					echo '<span class="edublink-course-feature-item-label">';
						echo '<i class="icon-62"></i>';
						echo esc_html( $instructor_label );
					echo '</span>';

					echo '<span class="edublink-course-feature-item-value">';
						echo wp_kses_post( get_the_author() );
					echo '</span>';
				echo '</li>';
			endif;

			if ( ! empty( $data['duration'] ) && edublink_set_value( 'tl_course_duration', true ) ) :
				$duration_label = edublink_set_value( 'tl_course_duration_label' ) ? edublink_set_value( 'tl_course_duration_label' ) : __( 'Duration:', 'edublink' );
				echo '<li class="edublink-course-details-features-item course-duration">';
					echo '<span class="edublink-course-feature-item-label">';
						echo '<i class="icon-61"></i>';
						echo esc_html( $duration_label );
					echo '</span>';

					echo '<span class="edublink-course-feature-item-value">';
						echo wp_kses_post( $data['duration'] );
					echo '</span>';
				echo '</li>';
			endif;

			if ( edublink_set_value( 'tl_course_lessons', true ) ) :
				$lessons_label = edublink_set_value( 'tl_course_lessons_label' ) ? edublink_set_value( 'tl_course_lessons_label' ) : __( 'Lessons:', 'edublink' );
				echo '<li class="edublink-course-details-features-item course-lesson">';
					echo '<span class="edublink-course-feature-item-label">';
						echo '<img src="' . esc_url( get_template_directory_uri() . '/assets/images/icons/books.svg' ) . '" class="edublink-course-sidebar-img-icon">';
						echo esc_html( $lessons_label );
					echo '</span>';

					echo '<span class="edublink-course-feature-item-value">';
						echo esc_html( $data['lessons'] );
					echo '</span>';
				echo '</li>';
			endif;

			if ( edublink_set_value( 'tl_course_students', true ) ) :
				$students_label = edublink_set_value( 'tl_course_students_label' ) ? edublink_set_value( 'tl_course_students_label' ) : __( 'Students:', 'edublink' );
				echo '<li class="edublink-course-details-features-item course-student">';
					echo '<span class="edublink-course-feature-item-label">';
						echo '<i class="icon-63"></i>';
						echo esc_html( $students_label );
					echo '</span>';

					echo '<span class="edublink-course-feature-item-value">';
						echo esc_html( $data['enrolled'] );
					echo '</span>';
				echo '</li>';
			endif;

			// if ( ! empty( $data['language'] ) && edublink_set_value( 'tl_course_language', true ) ) :
			// 	$language_label = edublink_set_value( 'tl_course_language_label' ) ? edublink_set_value( 'tl_course_language_label' ) : __( 'Language:', 'edublink' );
			// 	echo '<li class="edublink-course-details-features-item course-language">';
			// 		echo '<span class="edublink-course-feature-item-label">';
			// 			echo '<i class="icon-59"></i>';
			// 			echo esc_html( $language_label );
			// 		echo '</span>';

			// 		echo '<span class="edublink-course-feature-item-value">';
			// 			echo esc_html( $data['language'] );
			// 		echo '</span>';
			// 	echo '</li>';
			// endif;

			if ( ! empty( $data['level'] ) && edublink_set_value( 'tl_course_level', true ) ) :
				$level_label = edublink_set_value( 'tl_course_level_label' ) ? edublink_set_value( 'tl_course_level_label' ) : __( 'Level:', 'edublink' );
				echo '<li class="edublink-course-details-features-item course-level">';
					echo '<span class="edublink-course-feature-item-label">';
						echo '<i class="tutor-icon-level"></i>';
						echo esc_html( $level_label );
					echo '</span>';

					echo '<span class="edublink-course-feature-item-value">';
						echo esc_html( $data['level'] );
					echo '</span>';
				echo '</li>';
			endif;

			if ( ! empty( $data['last_updated'] ) && edublink_set_value( 'tl_course_last_updated', true ) ) :
				$last_updated_label = edublink_set_value( 'tl_course_last_updated_label' ) ? edublink_set_value( 'tl_course_last_updated_label' ) : __( 'Updated:', 'edublink' );
				echo '<li class="edublink-course-details-features-item course-level">';
					echo '<span class="edublink-course-feature-item-label">';
						echo '<i class="tutor-icon-refresh-o"></i>';
						echo esc_html( $last_updated_label );
					echo '</span>';

					echo '<span class="edublink-course-feature-item-value">';
						echo esc_html( $data['last_updated'] );
					echo '</span>';
				echo '</li>';
			endif;

			// if ( ! empty( $data['certificate'] ) && edublink_set_value( 'tl_course_certificate', true ) ) :
			// 	$certificate_label = edublink_set_value( 'tl_course_certificate_label' ) ? edublink_set_value( 'tl_course_certificate_label' ) : __( 'Certifications:', 'edublink' );
			// 	echo '<li class="edublink-course-details-features-item course-certificate">';
			// 		echo '<span class="edublink-course-feature-item-label">';
			// 			echo '<i class="icon-64"></i>';
			// 			echo esc_html( $certificate_label );
			// 		echo '</span>';

			// 		echo '<span class="edublink-course-feature-item-value">';
			// 			echo esc_html( $data['certificate'] );
			// 		echo '</span>';
			// 	echo '</li>';
			// endif;

			// if ( isset( $data['extra_meta'] ) && is_array( $data['extra_meta'] ) ) :
			// 	foreach ( $data['extra_meta'] as $key => $meta ) :
			// 		if ( $meta['label'] ) :
			// 			$wrapper_class = '';
			// 			if ( isset( $meta['wrapper_class'] ) && ! empty( $meta['wrapper_class'] ) ) :
			// 				$wrapper_class = ' ' . $meta['wrapper_class'];
			// 			endif;
			// 			echo '<li class="edublink-course-details-features-item' . esc_attr( $wrapper_class ) . '">';
			// 				echo '<span class="edublink-course-feature-item-label">';
			// 					if (  isset( $meta['icon_class'] ) && ! empty( $meta['icon_class'] ) ) :
			// 						echo '<i class="' . esc_attr( $meta['icon_class'] ) . '"></i>';
			// 					else :
			// 						echo '<i class="ri-check-fill"></i>';
			// 					endif;
			// 					echo esc_html( $meta['label'] );
			// 				echo '</span>';

			// 				if ( ! empty( $meta['value'] ) ) :
			// 					echo '<span class="edublink-course-feature-item-value">' . esc_html( $meta['value'] ) . '</span>';
			// 				endif;
			// 			echo '</li>';
			// 		endif;
			// 	endforeach;
			// endif;

			do_action( 'edublink_tl_course_meta_after' );
		echo '</ul>';
	}
endif;

/**
 * Related Courses
 */
if ( ! function_exists( 'edublink_tl_related_courses' ) ) :
	function edublink_tl_related_courses() {
		$related_courses = edublink_set_value( 'tl_related_courses', true );
		if ( isset( $_GET['disable_related_courses'] ) ) :
			$related_courses = false;
		endif;
		
		if ( $related_courses ) :
			tutor_load_template( 'custom.courses-related' );
		endif;
	}
endif;

/**
 * Breadcrumb Remove For Quiz Details Page
 */
if ( ! function_exists( 'edublink_tl_breadcrumb_remove_at_quiz_details' ) ) :
	function edublink_tl_breadcrumb_remove_at_quiz_details() {
		if ( is_singular( 'tutor_quiz' ) ) :
			return true;
		endif;
		return false;
	}
endif;
add_filter( 'edublink_page_without_breadcrumb', 'edublink_tl_breadcrumb_remove_at_quiz_details' );