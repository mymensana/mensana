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
            modifications: {
                priceLabelsChanged: 0
            },

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
                
                // Run initial modifications
                this.runAllModifications();
                
                // Setup observer
                this.setupMutationObserver();
                
                // Run modifications at intervals to catch late-loading content
                setTimeout(() => this.runAllModifications(), 1000);
                setTimeout(() => this.runAllModifications(), 3000);
                setTimeout(() => this.runAllModifications(), 5000);
                setTimeout(() => this.runAllModifications(), 10000);
                
                console.log('ThriveEd: Initialization complete');
            },

            runAllModifications: function() {
                try {
                    this.changePriceLabels();
                    
                    // Log progress
                    if (this.modifications.priceLabelsChanged > 0) {
                        console.log('ThriveEd: Successfully modified ' + this.modifications.priceLabelsChanged + ' price labels');
                    }
                } catch(error) {
                    console.error('ThriveEd: Error in runAllModifications:', error);
                }
            },

            changePriceLabels: function() {
                try {
                    let labelsModified = 0;
                    
                    // Method 1: jQuery approach (if available)
                    if (typeof jQuery !== 'undefined') {
                        jQuery('#tutor-course-builder label:not([data-thriveed-processed])').each(function() {
                            const labelElement = jQuery(this);
                            const labelText = labelElement.text().trim();
                            let wasModified = false;

                            if (labelText === 'Regular Price') {
                                labelElement.text('Total Price');
                                wasModified = true;
                                labelsModified++;
                                console.log('ThriveEd: Changed "Regular Price" to "Total Price" (jQuery)');
                            } else if (labelText === 'Sale Price') {
                                labelElement.text('Per Week');
                                wasModified = true;
                                labelsModified++;
                                console.log('ThriveEd: Changed "Sale Price" to "Per Week" (jQuery)');
                            }

                            if (wasModified) {
                                labelElement.attr('data-thriveed-processed', 'true');
                            }
                        });
                    }
                    
                    // Method 2: Vanilla JS approach (always run as backup)
                    const courseBuilder = document.getElementById('tutor-course-builder');
                    if (courseBuilder) {
                        const labels = courseBuilder.querySelectorAll('label:not([data-thriveed-processed])');
                        labels.forEach(label => {
                            const labelText = label.textContent.trim();
                            let wasModified = false;

                            if (labelText === 'Regular Price') {
                                label.textContent = 'Total Price';
                                wasModified = true;
                                labelsModified++;
                                console.log('ThriveEd: Changed "Regular Price" to "Total Price" (vanilla JS)');
                            } else if (labelText === 'Sale Price') {
                                label.textContent = 'Per Week';
                                wasModified = true;
                                labelsModified++;
                                console.log('ThriveEd: Changed "Sale Price" to "Per Week" (vanilla JS)');
                            }

                            if (wasModified) {
                                label.setAttribute('data-thriveed-processed', 'true');
                            }
                        });
                    }
                    
                    // Method 3: Global search for any missed labels
                    const allLabels = document.querySelectorAll('label:not([data-thriveed-processed])');
                    allLabels.forEach(label => {
                        const labelText = label.textContent.trim();
                        let wasModified = false;

                        if (labelText === 'Regular Price') {
                            label.textContent = 'Total Price';
                            wasModified = true;
                            labelsModified++;
                            console.log('ThriveEd: Changed "Regular Price" to "Total Price" (global search)');
                        } else if (labelText === 'Sale Price') {
                            label.textContent = 'Per Week';
                            wasModified = true;
                            labelsModified++;
                            console.log('ThriveEd: Changed "Sale Price" to "Per Week" (global search)');
                        }

                        if (wasModified) {
                            label.setAttribute('data-thriveed-processed', 'true');
                        }
                    });
                    
                    // Update our running total
                    this.modifications.priceLabelsChanged += labelsModified;
                    
                } catch(error) {
                    console.error('ThriveEd: Error in changePriceLabels:', error);
                }
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
                        let shouldRunModifications = false;
                        
                        for (const mutation of mutationsList) {
                            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                                for (const node of mutation.addedNodes) {
                                    if (node.nodeType === 1) { // Element node
                                        // Check if the added node contains labels we need to modify
                                        if (node.tagName === 'LABEL' || node.querySelector('label')) {
                                            shouldRunModifications = true;
                                            break;
                                        }
                                    }
                                }
                            }
                            if (shouldRunModifications) break;
                        }
                        
                        if (shouldRunModifications) {
                            clearTimeout(this.debounceTimer);
                            this.debounceTimer = setTimeout(() => {
                                console.log('ThriveEd: DOM changes detected, running modifications...');
                                this.runAllModifications();
                            }, 200);
                        }
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
        
        // Additional initialization attempts for React-heavy environments
        setTimeout(() => {
            console.log('ThriveEd: 1-second delayed initialization');
            ThriveEdTutorAdmin.init();
        }, 1000);
        
        setTimeout(() => {
            console.log('ThriveEd: 3-second delayed initialization');
            ThriveEdTutorAdmin.init();
        }, 3000);

    } catch(error) {
        console.error('ThriveEd: Critical error in admin script initialization:', error);
    }
})();