<?php
// Basic child theme setup
function edublink_child_enqueue_styles() {
    wp_enqueue_style('edublink-parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('edublink-child-style', get_stylesheet_directory_uri() . '/style.css', array('edublink-parent-style'));
}
add_action('wp_enqueue_scripts', 'edublink_child_enqueue_styles');

// Load Poppins font
function load_poppins_font() {
    wp_enqueue_style('poppins-font', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
}
add_action('wp_enqueue_scripts', 'load_poppins_font');

// NEW: Enqueue separated and conditional CSS/JS files for performance
function thriveed_conditional_assets() {
    // Enqueue for course archive pages
    if ( is_post_type_archive('courses') || is_tax(get_object_taxonomies('courses')) ) {
        wp_enqueue_style(
            'thriveed-course-archive-style',
            get_stylesheet_directory_uri() . '/assets/css/course-archive.css',
            array('edublink-child-style'),
            '1.0.8'
        );
        wp_enqueue_script(
            'thriveed-course-archive-script',
            get_stylesheet_directory_uri() . '/assets/js/course-archive.js',
            array('jquery'),
            '1.0.8',
            true
        );
    }

    // Enqueue for single course pages
    if ( is_singular('courses') ) {
        wp_enqueue_style(
            'thriveed-course-single-style',
            get_stylesheet_directory_uri() . '/assets/css/course-single.css',
            array('edublink-child-style'),
            '1.0.8'
        );
    }
}
add_action('wp_enqueue_scripts', 'thriveed_conditional_assets', 20);

/**
 * =====================================================================
 * FRONT-END JAVASCRIPT MODIFICATIONS (Unchanged)
 * =====================================================================
 */
function thriveed_modify_course_elements_globally() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {

        const processElements = (container) => {
            // Add 'weekly' to price amounts that haven't been processed
            const priceAmounts = container.querySelectorAll('.tutor-item-price span:not([data-processed-price]), .list-item-price span:not([data-processed-price]), [class*="price"] span:not([data-processed-price])');
            priceAmounts.forEach(function(element) {
                if (element.textContent.includes('$') && !element.textContent.includes('weekly')) {
                    element.textContent = element.textContent.trim() + ' weekly';
                }
                element.dataset.processedPrice = 'true';
            });

            if (!window.thriveEdCourseData) {
                return;
            }

            // Modify course cards that haven't been processed
            const courseCards = container.querySelectorAll('.edublink-single-course:not([data-processed-meta])');
            courseCards.forEach(function(card) {
                const courseIdMatch = card.closest('[id*="post-"]');
                const courseId = courseIdMatch ? courseIdMatch.id.replace('post-', '') : null;

                let instructorName = 'ThriveEd Team';
                let targetAudience = 'All Ages';
                let scheduleInfo = null;

                if (courseId && window.thriveEdCourseData[courseId]) {
                    const data = window.thriveEdCourseData[courseId];
                    instructorName = data.instructor || 'ThriveEd Team';
                    targetAudience = data.target_audience || 'All Ages';
                    scheduleInfo = data.schedule_info;
                }

                const courseLevelElements = card.querySelectorAll('.course-level');
                courseLevelElements.forEach(function(element) {
                    if (scheduleInfo && scheduleInfo.display) {
                        element.textContent = scheduleInfo.display;
                    } else {
                        element.textContent = 'Flexible Schedule';
                    }
                });

                const existingItems = card.querySelectorAll('ul.course-meta li:not(.custom-meta-weeks):not(.custom-meta-duration)');
                existingItems.forEach(function(item) {
                    const lessonIcon = item.querySelector('i.icon-24');
                    const studentIcon = item.querySelector('i.icon-25');

                    if (lessonIcon && (item.textContent.includes('Lesson') || item.textContent.includes('lesson'))) {
                        item.innerHTML = '\uD83D\uDC68\u200D\uD83C\uDFEB ' + instructorName;
                    }

                    if (studentIcon && (item.textContent.includes('Student') || item.textContent.includes('student'))) {
                       item.innerHTML = '\uD83D\uDC65 ' + targetAudience;
                    }
                });

                // Mark the card as processed to avoid running this logic again
                card.dataset.processedMeta = 'true';
            });
        };

        // Run once for the initial page load
        processElements(document.body);

        // Create an observer to watch for new elements being added to the page
        const observer = new MutationObserver((mutationsList) => {
            for (const mutation of mutationsList) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1) {
                            if (node.matches('.edublink-single-course, .tutor-item-price') || node.querySelector('.edublink-single-course, .tutor-item-price')) {
                                processElements(node);
                            }
                        }
                    });
                }
            }
        });

        // Start observing the entire document for changes
        observer.observe(document.body, { childList: true, subtree: true });

    });
    </script>
    <?php
}
add_action('wp_footer', 'thriveed_modify_course_elements_globally', 20);

/**
 * =====================================================================
 * COURSE DATA & SCHEDULE FUNCTIONS (Unchanged)
 * =====================================================================
 */
if ( ! function_exists( 'get_complete_course_data' ) ) {
    function get_complete_course_data( $course_id ) {
        $course_data = [
            'instructor_name'   => 'N/A',
            'duration'          => 'N/A',
            'weeks'             => 'N/A',
            'target_audience'   => 'N/A',
        ];
        if ( function_exists('tutor_utils') ) {
            $instructors = tutor_utils()->get_instructors_by_course( $course_id );
            if ( ! empty( $instructors ) ) {
                $instructor_names = array_map( function( $instructor ) {
                    return $instructor->display_name;
                }, $instructors );
                $course_data['instructor_name'] = implode( ', ', $instructor_names );
            }
        }
        $duration_meta = get_post_meta( $course_id, '_tutor_course_duration', true );
        if ( ! empty( $duration_meta ) ) { $course_data['duration'] = esc_html( $duration_meta ); }
        $audience_meta = get_post_meta( $course_id, '_tutor_course_target_audience', true );
        if ( ! empty( $audience_meta ) ) { $course_data['target_audience'] = esc_html( $audience_meta ); }
        $weeks_meta = get_post_meta( $course_id, '_tutor_course_material_includes', true );
        if ( ! empty( $weeks_meta ) ) { $course_data['weeks'] = esc_html( $weeks_meta ); }
        return $course_data;
    }
}
if ( ! function_exists( 'embed_course_data' ) ) {
    function embed_course_data() {
        if ( ! function_exists( 'tutor' ) ) { return; }
        $data_for_script = [];
        if ( is_singular( 'courses' ) || is_post_type_archive('courses') || is_tax(get_object_taxonomies('courses')) ) {
            if ( is_singular( 'courses' ) ) {
                $course_id = get_the_ID();
                $post_data = get_complete_course_data( $course_id );
                $post_data['debug'] = ['post_date' => get_the_date('c', $course_id)];
                $data_for_script[$course_id] = $post_data;
            } else {
                global $wp_query;
                if ( $wp_query->have_posts() ) {
                    while ( $wp_query->have_posts() ) {
                        $wp_query->the_post();
                        $course_id = get_the_ID();
                        $post_data = get_complete_course_data( $course_id );
                        $post_data['debug'] = ['post_date' => get_the_date('c')];
                        $data_for_script[$course_id] = $post_data;
                    }
                    wp_reset_postdata();
                }
            }
            if ( ! empty( $data_for_script ) ) {
                echo '<script id="thrive-course-data">window.thriveEdCourseData = ' . wp_json_encode( $data_for_script ) . ';</script>';
            }
        }
    }
}
add_action( 'wp_footer', 'embed_course_data', 5 );

function find_course_schedule($course_id) {
    global $wpdb;
    $google_meets = $wpdb->get_results($wpdb->prepare(
        "SELECT p.ID, p.post_title FROM {$wpdb->posts} p WHERE p.post_type = 'tutor-google-meet' AND p.post_status = 'publish' AND (p.post_parent = %d OR p.post_parent IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'topics' AND post_parent = %d))", $course_id, $course_id
    ));
    if (!empty($google_meets)) {
        foreach ($google_meets as $meet) {
            $meet_meta = get_post_meta($meet->ID);
            foreach ($meet_meta as $key => $value) {
                if (preg_match('/(date|time|start|end|schedule)/i', $key) && !empty($value[0])) {
                    $timestamp = strtotime($value[0]);
                    if ($timestamp && $timestamp > time()) { return ['source' => 'google_meet', 'timestamp' => $timestamp, 'display' => format_schedule_date($timestamp)]; }
                }
            }
        }
    }
    $course_post = get_post($course_id);
    if ($course_post && $course_post->post_status === 'publish') {
        $course_timestamp = strtotime($course_post->post_date);
        if ($course_timestamp > time()) { return ['source' => 'course_post_date', 'timestamp' => $course_timestamp, 'display' => format_schedule_date($course_timestamp)]; }
    }
    $all_meta = get_post_meta($course_id);
    $excluded_keys = ['_edit_last', '_edit_lock', 'date_time', '_wp_old_date', '_wp_old_slug'];
    foreach ($all_meta as $key => $value) {
        if (in_array($key, $excluded_keys) || empty($value[0]) || is_array($value[0])) continue;
        $timestamp = strtotime($value[0]);
        if ($timestamp && $timestamp > time()) { return ['source' => 'course_meta (' . $key . ')', 'timestamp' => $timestamp, 'display' => format_schedule_date($timestamp)]; }
    }
    return null;
}

function format_schedule_date($timestamp) {
    $date = date(get_option('date_format', 'M j, Y'), $timestamp);
    $time = date(get_option('time_format', 'g:i A'), $timestamp);
    if ($time !== date(get_option('time_format', 'g:i A'), strtotime('midnight'))) { return $date . ' at ' . $time; }
    return $date;
}

/**
 * =====================================================================
 * NUCLEAR OPTION: SCRIPT DEQUEUING & REPLACEMENT APPROACH
 * =====================================================================
 */

// Step 1: Aggressively dequeue ALL conflicting scripts on Tutor pages
function thriveed_nuclear_script_dequeue() {
    $is_tutor_admin_page = false;
    if (is_admin()) {
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'courses' && in_array($screen->base, ['post', 'post-new'])) { 
            $is_tutor_admin_page = true; 
        }
        if (isset($_GET['page']) && ( 
            strpos($_GET['page'], 'tutor') !== false || 
            strpos($_GET['page'], 'create-course') !== false || 
            strpos($_GET['page'], 'course-builder') !== false 
        )) { 
            $is_tutor_admin_page = true; 
        }
    }

    if ($is_tutor_admin_page) {
        // Remove ALL Tutor course builder related scripts
        wp_dequeue_script('tutor-course-builder');
        wp_dequeue_script('tutor-course-builder-curriculum');
        wp_dequeue_script('tutor-course-builder-announcement');
        wp_dequeue_script('tutor-course-builder-assignment');
        wp_dequeue_script('tutor-course-builder-quiz');
        wp_dequeue_script('tutor-course-builder-lesson');
        wp_dequeue_script('tutor-course-builder-topic');
        wp_dequeue_script('tutor-admin');
        wp_dequeue_script('tutor-admin-common');
        
        // Also remove Elementor scripts that might conflict
        wp_dequeue_script('elementor-frontend');
        wp_dequeue_script('elementor-common');
        wp_dequeue_script('elementor-pro-frontend');
        wp_dequeue_script('backbone-marionette');
        
        // Log what we're doing
        error_log('ThriveEd: Dequeued conflicting scripts on Tutor admin page');
    }
}
add_action('admin_enqueue_scripts', 'thriveed_nuclear_script_dequeue', 1);

// Step 2: Use the ORIGINAL working method but with proper file structure
function thriveed_original_working_method() {
    $screen = get_current_screen();
    $is_tutor_admin_page = false;

    if ($screen) {
        if ($screen->post_type === 'courses' && in_array($screen->base, ['post', 'post-new'])) {
            $is_tutor_admin_page = true;
        }
        if (isset($_GET['page']) && (
            strpos($_GET['page'], 'tutor') !== false ||
            strpos($_GET['page'], 'create-course') !== false ||
            strpos($_GET['page'], 'course-builder') !== false
        )) {
            $is_tutor_admin_page = true;
        }
    }

    if ($is_tutor_admin_page) {
        ?>
        <script>
        // This is the EXACT method that worked before - inline admin_footer approach
        // but with only the price label functionality extracted from your original code
        (function() {
            'use strict';
            
            console.log('ThriveEd: Starting original working method for price labels');
            
            function changePriceLabelsOnly() {
                // Use the approach that definitely worked before
                var allLabels = document.querySelectorAll('label, .tutor-form-label, .tutor-fs-6, .tutor-fw-medium, .tutor-color-black, .form-label, .tutor-mb-12, .tutor-form-group label, h3, h4, h5, h6, [class*="label"], [class*="title"], [class*="heading"]');
                
                var changesCount = 0;
                allLabels.forEach(function(label) {
                    if (!label.getAttribute('data-thriveed-processed')) {
                        var originalText = label.textContent.trim();
                        var wasChanged = false;
                        
                        if (originalText === 'Regular Price') {
                            label.textContent = 'Total Price';
                            wasChanged = true;
                            changesCount++;
                            console.log('ThriveEd: Changed "Regular Price" to "Total Price"');
                        } else if (originalText === 'Sale Price') {
                            label.textContent = 'Per Week';
                            wasChanged = true;
                            changesCount++;
                            console.log('ThriveEd: Changed "Sale Price" to "Per Week"');
                        }
                        
                        if (wasChanged) {
                            label.setAttribute('data-thriveed-processed', 'true');
                        }
                    }
                });
                
                if (changesCount > 0) {
                    console.log('ThriveEd: Successfully changed ' + changesCount + ' price labels');
                }
            }

            function initPriceLabelChanger() {
                changePriceLabelsOnly();
                
                // Run multiple times to catch late-loading content
                setTimeout(changePriceLabelsOnly, 1000);
                setTimeout(changePriceLabelsOnly, 3000);
                setTimeout(changePriceLabelsOnly, 5000);
                setTimeout(changePriceLabelsOnly, 10000);

                // Set up observer for new content
                var observer = new MutationObserver(function(mutations) {
                    var shouldRun = false;
                    mutations.forEach(function(mutation) {
                        if (mutation.addedNodes.length > 0) {
                            mutation.addedNodes.forEach(function(node) {
                                if (node.nodeType === 1) {
                                    shouldRun = true;
                                }
                            });
                        }
                    });
                    if (shouldRun) {
                        setTimeout(changePriceLabelsOnly, 100);
                    }
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true,
                    characterData: true
                });
                
                // Also listen for focus events which often trigger UI updates
                document.addEventListener('focus', function(e) {
                    if (e.target.matches('input, textarea, select')) {
                        setTimeout(changePriceLabelsOnly, 100);
                    }
                }, true);
                
                console.log('ThriveEd: Price label changer initialized successfully');
            }

            // Initialize using the same method that worked before
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initPriceLabelChanger);
            } else {
                initPriceLabelChanger();
            }
            
            // Also run on window load as backup
            window.addEventListener('load', function() {
                setTimeout(changePriceLabelsOnly, 500);
            });
            
        })();
        </script>
        <?php
    }
}
add_action('admin_footer', 'thriveed_original_working_method', 999);

/**
 * =====================================================================
 * PHP-BASED TEXT FILTERS (Unchanged)
 * =====================================================================
 */
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
    add_filter( 'gettext', function( $translated_text, $text, $domain ) {
        if ($domain === 'tutor' || $domain === 'tutor-pro') {
            if ( is_singular('courses') ) {
                if ( 'Subscription' === $text || 'Subscriptions' === $text ) {
                     return 'Pay Weekly';
                }
                if ( 'Start from' === $text || 'Starts from' === $text ) {
                     return '';
                }
            }
        }
        return $translated_text;
    }, 20, 3 );
}
add_action( 'init', 'thriveed_register_text_change_filters' );

/**
 * =====================================================================
 * OTHER FUNCTIONS (Unchanged)
 * =====================================================================
 */
$child_tutor_functions = get_stylesheet_directory() . '/tutor/custom/functions.php';
if (file_exists($child_tutor_functions)) {
    require_once $child_tutor_functions;
}

add_action('wp_enqueue_scripts', function() {
    wp_dequeue_script('tutor-push-notification');
    wp_deregister_script('tutor-push-notification');
}, 999);