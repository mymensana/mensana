/**
 * ThriveEd Customizations for Tutor LMS Admin Area
 * Enhanced with comprehensive conflict prevention and error handling
 */
(function() {
    'use strict';

    // Prevent multiple loading
    if (window.ThriveEdTutorAdminLoaded) {
        console.log('ThriveEd: Admin script already loaded, skipping duplicate');
        return;
    }
    
    try {
        window.ThriveEdTutorAdminLoaded = true;
        console.log('ThriveEd: Initializing admin customizations...');

        const ThriveEdTutorAdmin = {
            debounceTimer: null,
            maxRetries: 15,
            retryCount: 0,

            init: function() {
                console.log('ThriveEd: Starting initialization...');
                
                // Wait for jQuery with retries
                if (typeof jQuery === 'undefined') {
                    if (this.retryCount < this.maxRetries) {
                        this.retryCount++;
                        console.log('ThriveEd: Waiting for jQuery, attempt ' + this.retryCount);
                        setTimeout(() => this.init(), 300);
                        return;
                    } else {
                        console.warn('ThriveEd: jQuery not available after maximum retries, proceeding with vanilla JS');
                    }
                } else {
                    console.log('ThriveEd: jQuery detected successfully');
                }
                
                // Setup observer for future enhancements
                this.setupMutationObserver();
                
                console.log('ThriveEd: Initialization complete');
            },

            setupMutationObserver: function() {
                try {
                    // Target the course builder specifically, or fall back to body
                    const targetNode = document.getElementById('tutor-course-builder') || document.body;

                    if (!targetNode) {
                        console.warn('ThriveEd: Target node not found, retrying observer setup...');
                        setTimeout(() => this.setupMutationObserver(), 2000);
                        return;
                    }

                    const observer = new MutationObserver((mutationsList) => {
                        // Observer setup for future customizations
                        // Currently no modifications needed since using native Tutor LMS
                        console.log('ThriveEd: DOM changes detected (observer active)');
                    });

                    observer.observe(targetNode, {
                        childList: true,
                        subtree: true,
                        attributes: true,
                        attributeFilter: ['class', 'id']
                    });
                    
                    console.log('ThriveEd: MutationObserver setup complete on', targetNode.tagName);
                    
                } catch(error) {
                    console.error('ThriveEd: Error setting up MutationObserver:', error);
                }
            }
        };

        // Initialize with multiple fallback methods
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                console.log('ThriveEd: DOMContentLoaded event fired');
                ThriveEdTutorAdmin.init();
            });
        } else {
            console.log('ThriveEd: DOM already ready, initializing immediately');
            ThriveEdTutorAdmin.init();
        }

    } catch(error) {
        console.error('ThriveEd: Critical error in admin script initialization:', error);
    }
})();