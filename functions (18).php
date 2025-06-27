<?php
/**
 * ThriveEd Child Theme Functions
 * Elite-level Tutor LMS customization
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue child theme styles
 */
function thriveed_enqueue_styles() {
    wp_enqueue_style('thriveed-parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('thriveed-child-style', get_stylesheet_directory_uri() . '/style.css', array('thriveed-parent-style'));
}
add_action('wp_enqueue_scripts', 'thriveed_enqueue_styles');

/**
 * ELITE SOLUTION: Direct DOM manipulation for course duration
 * Targets the exact HTML structure shown in the elements inspector
 */
function thriveed_elite_duration_fix() {
    if (is_singular('courses')) {
        $course_id = get_the_ID();
        ?>
        <script>
        jQuery(document).ready(function($) {
            console.log('Elite Duration Fix: Starting for course <?php echo $course_id; ?>');
            
            // PRECISION TARGETING: Based on exact HTML structure from your screenshots
            function fixDurationDisplay() {
                // Target 1: Direct span with class "detail-value" containing "N/A"
                $('span.detail-value').each(function() {
                    var $span = $(this);
                    var $label = $span.siblings('.detail-label');
                    
                    if ($label.text().toLowerCase().includes('duration') && 
                        ($span.text().trim() === 'N/A' || $span.text().trim() === '')) {
                        
                        console.log('Elite Fix: Found duration N/A span, replacing...');
                        $span.text('55 minutes');
                        $span.css('color', '#28a745'); // Green to show it worked
                    }
                });
                
                // Target 2: Any element with text "Duration: N/A"
                $('*').filter(function() {
                    return $(this).children().length === 0 && 
                           $(this).text().trim() === 'Duration: N/A';
                }).each(function() {
                    console.log('Elite Fix: Found "Duration: N/A" text, replacing...');
                    $(this).text('Duration: 55 minutes');
                    $(this).css('color', '#28a745');
                });
                
                // Target 3: Course sidebar specific targeting
                $('.thriveed-details-wrapper .thriveed-course-detail').each(function() {
                    var $detail = $(this);
                    var $label = $detail.find('.detail-label');
                    var $value = $detail.find('.detail-value');
                    
                    if ($label.text().toLowerCase().includes('duration') && 
                        $value.text().trim() === 'N/A') {
                        
                        console.log('Elite Fix: Found course detail duration N/A, replacing...');
                        $value.text('55 minutes');
                        $value.css('color', '#28a745');
                    }
                });
                
                // Target 4: EduBlink theme specific (from your HTML structure)
                $('.edublink-course-details-sidebar .thriveed-course-detail').each(function() {
                    var $detail = $(this);
                    if ($detail.find('.detail-label').text().toLowerCase().includes('duration')) {
                        var $value = $detail.find('.detail-value');
                        if ($value.text().trim() === 'N/A' || $value.text().trim() === '') {
                            console.log('Elite Fix: Found EduBlink duration N/A, replacing...');
                            $value.text('55 minutes');
                            $value.css('color', '#28a745');
                        }
                    }
                });
                
                // Target 5: Fallback - any span containing exactly "N/A" near duration context
                $('span').filter(function() {
                    return $(this).text().trim() === 'N/A' && 
                           $(this).closest('div').find('*').filter(function() {
                               return $(this).text().toLowerCase().includes('duration');
                           }).length > 0;
                }).each(function() {
                    console.log('Elite Fix: Found contextual duration N/A, replacing...');
                    $(this).text('55 minutes');
                    $(this).css('color', '#28a745');
                });
            }
            
            // Execute immediately
            fixDurationDisplay();
            
            // Execute after DOM manipulations (multiple timings to catch all scenarios)
            setTimeout(fixDurationDisplay, 100);
            setTimeout(fixDurationDisplay, 500);
            setTimeout(fixDurationDisplay, 1000);
            setTimeout(fixDurationDisplay, 2000);
            
            // Watch for DOM changes (if content is loaded dynamically)
            if (window.MutationObserver) {
                var observer = new MutationObserver(function(mutations) {
                    var shouldUpdate = false;
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList' || mutation.type === 'characterData') {
                            shouldUpdate = true;
                        }
                    });
                    if (shouldUpdate) {
                        setTimeout(fixDurationDisplay, 50);
                    }
                });
                
                observer.observe(document.body, {
                    childList: true,
                    subtree: true,
                    characterData: true
                });
            }
            
            console.log('Elite Duration Fix: Setup complete');
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'thriveed_elite_duration_fix');

/**
 * ELITE SOLUTION 2: Server-side filter interception
 * Intercept at the template level before HTML is generated
 */
function thriveed_intercept_duration_output($content) {
    if (is_singular('courses')) {
        // Replace any occurrence of duration N/A in the content
        $content = preg_replace(
            '/(<span[^>]*class="[^"]*detail-value[^"]*"[^>]*>)N\/A(<\/span>)/i',
            '$155 minutes$2',
            $content
        );
        
        // Replace pattern: Duration: N/A
        $content = str_replace('Duration: N/A', 'Duration: 55 minutes', $content);
        
        // Replace standalone N/A in duration context
        $content = preg_replace(
            '/(duration[^>]*>.*?)N\/A(.*?<)/i',
            '$155 minutes$2',
            $content
        );
    }
    
    return $content;
}
add_filter('the_content', 'thriveed_intercept_duration_output', 999);

/**
 * ELITE SOLUTION 3: Hook into Tutor LMS template system
 * Override the actual template output
 */
function thriveed_override_tutor_duration_template($template, $template_name, $args) {
    // Intercept course details template
    if (strpos($template_name, 'course-details') !== false || 
        strpos($template_name, 'sidebar') !== false) {
        
        // Start output buffering to modify the template
        ob_start(function($buffer) {
            if (is_singular('courses')) {
                // Replace N/A with 55 minutes in any duration context
                $buffer = preg_replace(
                    '/(<[^>]*(?:duration|detail-value)[^>]*>.*?)N\/A(.*?<\/[^>]*>)/i',
                    '$155 minutes$2',
                    $buffer
                );
            }
            return $buffer;
        });
    }
    
    return $template;
}
add_filter('tutor_get_template', 'thriveed_override_tutor_duration_template', 10, 3);

/**
 * ELITE SOLUTION 4: Direct database meta override
 * Override the meta value when it's retrieved
 */
function thriveed_override_course_duration_meta($value, $object_id, $meta_key, $single) {
    if ($meta_key === '_course_duration' && is_singular('courses') && $object_id == get_the_ID()) {
        // If the current value is empty or 0 duration, return our custom value
        if (empty($value) || $value === '' || 
            (is_array($value) && isset($value[0]) && 
             (empty($value[0]) || $value[0] === 'a:2:{s:5:"hours";s:1:"0";s:7:"minutes";s:1:"0";}'))) {
            
            // Return serialized duration for 55 minutes
            return array('a:2:{s:5:"hours";s:1:"0";s:7:"minutes";s:2:"55";}');
        }
    }
    return $value;
}
add_filter('get_post_metadata', 'thriveed_override_course_duration_meta', 10, 4);

/**
 * ELITE SOLUTION 5: CSS-based visual override as backup
 */
function thriveed_css_duration_override() {
    if (is_singular('courses')) {
        ?>
        <style>
        /* Hide original N/A and inject custom content */
        .detail-value:contains("N/A"),
        span:contains("N/A") {
            font-size: 0 !important;
        }
        
        .detail-value:contains("N/A"):after,
        span:contains("N/A"):after {
            content: "55 minutes";
            font-size: 14px !important;
            color: #28a745 !important;
            font-weight: 500;
        }
        
        /* Specific targeting for your theme structure */
        .thriveed-course-detail .detail-value:contains("N/A"):after {
            content: "55 minutes";
            font-size: 15px !important;
            color: #374151 !important;
        }
        </style>
        <?php
    }
}
add_action('wp_head', 'thriveed_css_duration_override');

/**
 * Enhanced debug function
 */
function thriveed_enhanced_debug() {
    if (is_singular('courses') && current_user_can('manage_options')) {
        $course_id = get_the_ID();
        ?>
        <script>
        console.log('=== ELITE DEBUG START ===');
        console.log('Course ID:', <?php echo $course_id; ?>);
        console.log('Page URL:', window.location.href);
        console.log('Body classes:', document.body.className);
        
        // Check for all possible duration elements
        setTimeout(function() {
            console.log('Duration elements found:');
            $('[class*="duration"], .detail-value, [class*="detail"]').each(function(i) {
                if ($(this).text().includes('N/A') || $(this).text().includes('duration')) {
                    console.log(i + ':', {
                        element: this,
                        text: $(this).text(),
                        classes: this.className,
                        parent: $(this).parent().get(0)
                    });
                }
            });
        }, 1000);
        
        console.log('=== ELITE DEBUG END ===');
        </script>
        <?php
    }
}
add_action('wp_footer', 'thriveed_enhanced_debug');

/**
 * Text replacement filters (existing functionality)
 */
function thriveed_register_text_change_filters() {
    add_filter('the_content', 'thriveed_replace_text_in_content');
    add_filter('widget_text', 'thriveed_replace_text_in_content');
}

function thriveed_replace_text_in_content($content) {
    $replacements = array(
        'Add to Wishlist' => 'Wishlist',
        'Complete Course' => 'Complete Course',
        'Course Curriculum' => 'Course Curriculum',
    );
    
    foreach ($replacements as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }
    
    return $content;
}

add_action('init', 'thriveed_register_text_change_filters');
?>