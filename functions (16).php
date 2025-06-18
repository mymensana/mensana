<?php
/**
 * ThriveEd Child Theme Functions
 * Clean, organized, and optimized for Tutor LMS integration
 */

// =============================================================================
// BASIC CHILD THEME SETUP
// =============================================================================

function edublink_child_enqueue_styles() {
    wp_enqueue_style('edublink-parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('edublink-child-style', get_stylesheet_directory_uri() . '/style.css', array('edublink-parent-style'));
}
add_action('wp_enqueue_scripts', 'edublink_child_enqueue_styles');

function load_poppins_font() {
    wp_enqueue_style('poppins-font', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
}
add_action('wp_enqueue_scripts', 'load_poppins_font');

// =============================================================================
// CONDITIONAL ASSET LOADING FOR PERFORMANCE
// =============================================================================

function thriveed_conditional_assets() {
    // Course archive pages
    if (is_post_type_archive('courses') || is_tax(get_object_taxonomies('courses'))) {
        wp_enqueue_style(
            'thriveed-course-archive-style',
            get_stylesheet_directory_uri() . '/assets/css/course-archive.css',
            array('edublink-child-style'),
            '1.0.9'
        );
        wp_enqueue_script(
            'thriveed-course-archive-script',
            get_stylesheet_directory_uri() . '/assets/js/course-archive.js',
            array('jquery'),
            '1.0.9',
            true
        );
    }

    // Single course pages
    if (is_singular('courses')) {
        wp_enqueue_style(
            'thriveed-course-single-style',
            get_stylesheet_directory_uri() . '/assets/css/course-single.css',
            array('edublink-child-style'),
            '1.0.9'
        );
    }
}
add_action('wp_enqueue_scripts', 'thriveed_conditional_assets', 20);

// =============================================================================
// COURSE DATA FUNCTIONS
// =============================================================================

if (!function_exists('get_complete_course_data')) {
    function get_complete_course_data($course_id) {
        $course_data = [
            'instructor_name' => 'N/A',
            'duration'        => 'N/A',
            'weeks'           => 'N/A',
            'target_audience' => 'N/A',
        ];

        // Get instructor(s)
        if (function_exists('tutor_utils')) {
            $instructors = tutor_utils()->get_instructors_by_course($course_id);
            if (!empty($instructors)) {
                $instructor_names = array_map(function($instructor) {
                    return $instructor->display_name;
                }, $instructors);
                $course_data['instructor_name'] = implode(', ', $instructor_names);
            }
        }

        // Get duration from serialized array
        $duration_meta = get_post_meta($course_id, '_course_duration', true);
        if (!empty($duration_meta)) {
            $duration_array = maybe_unserialize($duration_meta);
            if (is_array($duration_array)) {
                $hours = (int) ($duration_array['hours'] ?? 0);
                $minutes = (int) ($duration_array['minutes'] ?? 0);
                
                $duration_parts = [];
                if ($hours > 0) $duration_parts[] = $hours . ' hour' . ($hours > 1 ? 's' : '');
                if ($minutes > 0) $duration_parts[] = $minutes . ' minute' . ($minutes > 1 ? 's' : '');
                
                if (!empty($duration_parts)) {
                    $course_data['duration'] = implode(' ', $duration_parts);
                } else {
                    $course_data['duration'] = '1 hour';
                }
            }
        }

        // Get target audience (Ages)
        $audience_meta = get_post_meta($course_id, '_tutor_course_target_audience', true);
        if (!empty($audience_meta)) {
            $course_data['target_audience'] = esc_html($audience_meta);
        }

        // Get weeks (Course Materials)
        $weeks_meta = get_post_meta($course_id, '_tutor_course_material_includes', true);
        if (!empty($weeks_meta)) {
            $course_data['weeks'] = esc_html($weeks_meta);
        }

        return $course_data;
    }
}

if (!function_exists('embed_course_data')) {
    function embed_course_data() {
        if (!function_exists('tutor')) {
            return;
        }

        $data_for_script = [];

        if (is_singular('courses') || is_post_type_archive('courses') || is_tax(get_object_taxonomies('courses'))) {
            if (is_singular('courses')) {
                $course_id = get_the_ID();
                $post_data = get_complete_course_data($course_id);
                $post_data['debug'] = ['post_date' => get_the_date('c', $course_id)];
                $data_for_script[$course_id] = $post_data;
            } else {
                global $wp_query;
                if ($wp_query->have_posts()) {
                    while ($wp_query->have_posts()) {
                        $wp_query->the_post();
                        $course_id = get_the_ID();
                        $post_data = get_complete_course_data($course_id);
                        $post_data['debug'] = ['post_date' => get_the_date('c')];
                        $data_for_script[$course_id] = $post_data;
                    }
                    wp_reset_postdata();
                }
            }

            if (!empty($data_for_script)) {
                echo '<script id="thrive-course-data">window.thriveEdCourseData = ' . wp_json_encode($data_for_script) . ';</script>';
            }
        }
    }
}
add_action('wp_footer', 'embed_course_data', 5);

// =============================================================================
// COURSE SCHEDULE FUNCTIONS
// =============================================================================

function find_course_schedule($course_id) {
    global $wpdb;
    
    // Check for Google Meet schedules
    $google_meets = $wpdb->get_results($wpdb->prepare(
        "SELECT p.ID, p.post_title FROM {$wpdb->posts} p 
         WHERE p.post_type = 'tutor-google-meet' 
         AND p.post_status = 'publish' 
         AND (p.post_parent = %d OR p.post_parent IN 
             (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'topics' AND post_parent = %d))", 
        $course_id, $course_id
    ));

    if (!empty($google_meets)) {
        foreach ($google_meets as $meet) {
            $meet_meta = get_post_meta($meet->ID);
            foreach ($meet_meta as $key => $value) {
                if (preg_match('/(date|time|start|end|schedule)/i', $key) && !empty($value[0])) {
                    $timestamp = strtotime($value[0]);
                    if ($timestamp && $timestamp > time()) {
                        return [
                            'source' => 'google_meet',
                            'timestamp' => $timestamp,
                            'display' => format_schedule_date($timestamp)
                        ];
                    }
                }
            }
        }
    }

    // Check course post date
    $course_post = get_post($course_id);
    if ($course_post && $course_post->post_status === 'publish') {
        $course_timestamp = strtotime($course_post->post_date);
        if ($course_timestamp > time()) {
            return [
                'source' => 'course_post_date',
                'timestamp' => $course_timestamp,
                'display' => format_schedule_date($course_timestamp)
            ];
        }
    }

    // Check meta fields for dates
    $all_meta = get_post_meta($course_id);
    $excluded_keys = ['_edit_last', '_edit_lock', 'date_time', '_wp_old_date', '_wp_old_slug'];
    
    foreach ($all_meta as $key => $value) {
        if (in_array($key, $excluded_keys) || empty($value[0]) || is_array($value[0])) {
            continue;
        }
        
        $timestamp = strtotime($value[0]);
        if ($timestamp && $timestamp > time()) {
            return [
                'source' => 'course_meta (' . $key . ')',
                'timestamp' => $timestamp,
                'display' => format_schedule_date($timestamp)
            ];
        }
    }

    return null;
}

function format_schedule_date($timestamp) {
    $date = date(get_option('date_format', 'M j, Y'), $timestamp);
    $time = date(get_option('time_format', 'g:i A'), $timestamp);
    
    if ($time !== date(get_option('time_format', 'g:i A'), strtotime('midnight'))) {
        return $date . ' at ' . $time;
    }
    
    return $date;
}

// =============================================================================
// COURSE SIDEBAR CUSTOMIZATION
// =============================================================================

function thriveed_final_working_sidebar() {
    if (!is_singular('courses')) {
        return;
    }

    $course_id = get_the_ID();

    // Get duration from serialized array
    $duration_meta = get_post_meta($course_id, '_course_duration', true);
    $duration = 'N/A';
    
    if (!empty($duration_meta)) {
        $duration_array = maybe_unserialize($duration_meta);
        
        if (is_array($duration_array)) {
            $hours = (int) ($duration_array['hours'] ?? 0);
            $minutes = (int) ($duration_array['minutes'] ?? 0);
            
            $duration_parts = [];
            if ($hours > 0) $duration_parts[] = $hours . ' hour' . ($hours > 1 ? 's' : '');
            if ($minutes > 0) $duration_parts[] = $minutes . ' minute' . ($minutes > 1 ? 's' : '');
            
            if (!empty($duration_parts)) {
                $duration = implode(' ', $duration_parts);
            } else {
                $duration = '1 hour';
            }
        }
    }

    // Get ages - check multiple possible meta keys
    $ages = get_post_meta($course_id, '_tutor_course_target_audience', true);
    if (empty($ages)) {
        $ages = get_post_meta($course_id, 'course_target_audience', true);
    }
    if (empty($ages)) {
        $ages = 'All Ages';
    }

    // Get weeks - check multiple possible meta keys
    $weeks = get_post_meta($course_id, '_tutor_course_material_includes', true);
    if (empty($weeks)) {
        $weeks = get_post_meta($course_id, 'course_material_includes', true);
    }
    if (empty($weeks)) {
        $weeks = '8';
    }

    // Get instructor
    $instructor = get_the_author();

    // Debug output
    error_log("ThriveEd Sidebar - Course $course_id: Duration=$duration, Ages=$ages, Weeks=$weeks, Instructor=$instructor");

    ?>
    <style>
        .thriveed-details-wrapper .thriveed-course-detail { 
            display: flex !important; 
            align-items: center !important; 
            gap: 15px !important; 
            padding: 8px 0 !important; 
            font-family: 'Poppins', sans-serif !important; 
            font-size: 15px !important; 
            color: #374151 !important; 
            border-bottom: 1px solid #f0f0f0; 
        }
        .thriveed-details-wrapper .thriveed-course-detail:last-child { border-bottom: none; }
        .thriveed-course-detail .detail-icon { 
            font-size: 16px !important; 
            width: 20px !important; 
            color: #6b7280 !important; 
            text-align: center !important; 
        }
        .thriveed-course-detail .detail-label { 
            font-weight: 500 !important; 
            color: #374151 !important; 
        }
        .thriveed-course-detail .detail-value { 
            margin-left: auto !important; 
            color: #6b7280 !important; 
            font-weight: 400 !important; 
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function rebuildCourseDetails() {
            if (document.querySelector('.thriveed-details-wrapper')) {
                return;
            }

            const anchorText = 'course includes:';
            let courseIncludesText = null;

            for (const el of document.querySelectorAll('*')) {
                if (el.textContent.trim().toLowerCase() === anchorText) {
                    courseIncludesText = el;
                    break;
                }
            }

            if (!courseIncludesText) {
                return;
            }

            const detailsHTML = `
                <div class="thriveed-course-detail"><span class="detail-icon">👨‍🏫</span><span class="detail-label">Instructor:</span><span class="detail-value"><?php echo esc_js($instructor); ?></span></div>
                <div class="thriveed-course-detail"><span class="detail-icon">⏱️</span><span class="detail-label">Duration:</span><span class="detail-value"><?php echo esc_js($duration); ?></span></div>
                <div class="thriveed-course-detail"><span class="detail-icon">📚</span><span class="detail-label">Weeks:</span><span class="detail-value"><?php echo esc_js($weeks); ?></span></div>
                <div class="thriveed-course-detail"><span class="detail-icon">👥</span><span class="detail-label">Ages:</span><span class="detail-value"><?php echo esc_js($ages); ?></span></div>
                <div class="thriveed-course-detail"><span class="detail-icon">📊</span><span class="detail-label">Level:</span><span class="detail-value">Intermediate</span></div>
            `;
            
            const detailsWrapper = document.createElement('div');
            detailsWrapper.className = 'thriveed-details-wrapper';
            detailsWrapper.innerHTML = detailsHTML;
            
            courseIncludesText.after(detailsWrapper);
            
            const originalList = courseIncludesText.nextElementSibling.nextElementSibling;
            if(originalList && originalList.matches('ul, .edublink-course-meta-informations')) {
                originalList.style.display = 'none';
            }

            console.log('🎯 ThriveEd: Final sidebar rebuilt successfully!');
        }

        setTimeout(rebuildCourseDetails, 500);
        setTimeout(rebuildCourseDetails, 1500);
        setTimeout(rebuildCourseDetails, 3000);

        const observer = new MutationObserver(function(mutationsList, observer) {
            const sidebar = document.querySelector('.edublink-course-details-sidebar-inner');
            if (sidebar && !document.querySelector('.thriveed-details-wrapper')) {
                rebuildCourseDetails();
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    });
    </script>
    <?php
}
add_action('wp_footer', 'thriveed_final_working_sidebar', 20);

// =============================================================================
// TEXT FILTERS FOR TUTOR LMS LABELS
// =============================================================================

function thriveed_register_text_change_filters() {
    add_filter('gettext', function($translation, $text, $domain) {
        if ($domain === 'tutor' || $domain === 'tutor-pro') {
            if ($text === 'Materials Included') {
                return 'Number of Weeks for Offering';
            }
            if ($text === 'Target Audience') {
                return 'Ages';
            }
        }
        return $translation;
    }, 10, 3);

    add_filter('ngettext', function($translation, $single, $plural, $number, $domain) {
        if ($domain === 'tutor' || $domain === 'tutor-pro') {
            if ($single === 'Materials Included') {
                return 'Number of Weeks for Offering';
            }
            if ($single === 'Target Audience') {
                return 'Ages';
            }
        }
        return $translation;
    }, 10, 5);
}
add_action('init', 'thriveed_register_text_change_filters');