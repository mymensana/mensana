// Customize course sidebar - USING CENTRALIZED DATA FUNCTION
function thriveed_customize_course_sidebar_final() {
    if (!is_singular('courses')) {
        return;
    }
    
    global $post;
    $course_id = $post->ID;
    
    // GET DATE USING THE NEW CENTRALIZED FUNCTION
    $schedule_data = thriveed_get_custom_course_schedule_data($course_id);
    $start_date = $schedule_data ? $schedule_data['full_string'] : 'Coming Soon';
    
    // Get the course data using your existing function
    $course_data = get_complete_course_data($course_id);
    
    // Extract the data
    $weeks = $course_data['number_of_weeks'] ?? '8';
    $ages = $course_data['target_audience'] ?? 'All Ages';
    $instructor = $course_data['instructor'] ?? 'ThriveEd';
    
    // Fix duration formatting (keep your existing logic)
    $duration_raw = $course_data['duration'] ?? '1 hour';
    $duration = '';
    
    if (is_array($duration_raw)) {
        if (isset($duration_raw['hours']) || isset($duration_raw['minutes'])) {
            $hours = intval($duration_raw['hours'] ?? 0);
            $minutes = intval($duration_raw['minutes'] ?? 0);
            
            if ($hours > 0 && $minutes > 0) {
                $duration = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '');
            } elseif ($hours > 0) {
                $duration = $hours . ' hour' . ($hours > 1 ? 's' : '');
            } elseif ($minutes > 0) {
                $duration = $minutes . ' minute' . ($minutes > 1 ? 's' : '');
            } else {
                $duration = '1 hour';
            }
        } else {
            $duration = '1 hour';
        }
    } elseif (is_string($duration_raw) && !empty($duration_raw)) {
        $duration = $duration_raw;
    } else {
        $duration = '1 hour';
    }
    
    // DEBUG: Log what we got
    error_log("Course ID: $course_id | Schedule Data: " . print_r($schedule_data, true) | " Final Date: $start_date");
    ?>
    
    <style>
    /* Keep your existing styles */
    .thriveed-course-banner {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        padding: 12px 16px !important;
        border-radius: 8px !important;
        margin-bottom: 20px !important;
        text-align: center !important;
        box-shadow: 0 2px 10px rgba(102, 126, 234, 0.2) !important;
        font-family: 'Poppins', sans-serif !important;
    }
    
    .thriveed-course-banner .banner-label {
        font-size: 12px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        opacity: 0.9 !important;
        margin-bottom: 4px !important;
        font-weight: 600 !important;
    }
    
    .thriveed-course-banner .banner-date {
        font-size: 16px !important;
        font-weight: 600 !important;
        margin: 0 !important;
    }
    
    /* Keep all your other existing styles... */
    .thriveed-course-detail {
        display: flex !important;
        align-items: center !important;
        gap: 15px !important;
        padding: 8px 0 !important;
        font-family: 'Poppins', sans-serif !important;
        font-size: 15px !important;
        color: #374151 !important;
    }
    
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
    
    .thriveed-price-section {
        margin-top: 15px !important;
        padding-top: 15px !important;
        border-top: 1px solid #f0f0f0 !important;
    }
    </style>
    
    <script>
    // Keep your existing JavaScript but now $start_date has the correct value from PHP
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            rebuildCourseDetailsFinal();
        }, 1000);
        
        setTimeout(function() {
            rebuildCourseDetailsFinal();
        }, 3000);
    });
    
    function rebuildCourseDetailsFinal() {
        if (document.querySelector('.thriveed-final-rebuild')) {
            return;
        }
        
        var courseIncludesText = null;
        var allElements = document.querySelectorAll('*');
        
        for (var i = 0; i < allElements.length; i++) {
            if (allElements[i].textContent.trim() === 'Course Includes:') {
                courseIncludesText = allElements[i];
                break;
            }
        }
        
        if (!courseIncludesText) {
            return;
        }
        
        var container = courseIncludesText.parentNode;
        
        // Add start date banner with the CORRECT date from our centralized function
        if (!courseIncludesText.previousElementSibling || 
            !courseIncludesText.previousElementSibling.classList.contains('thriveed-course-banner')) {
            
            var banner = document.createElement('div');
            banner.className = 'thriveed-course-banner';
            banner.innerHTML = '<div class="banner-label">Course Starts</div><div class="banner-date"><?php echo esc_js($start_date); ?></div>';
            container.insertBefore(banner, courseIncludesText);
        }
        
        // [Keep the rest of your existing JavaScript code for the course details...]
        
        container.classList.add('thriveed-final-rebuild');
    }
    </script>
    
    <?php
}
add_action('wp_footer', 'thriveed_customize_course_sidebar_final');