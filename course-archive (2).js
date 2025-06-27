document.addEventListener('DOMContentLoaded', function() {
    if (!document.body.classList.contains('post-type-archive-courses') && !document.body.classList.contains('tax-course-category') && !document.body.classList.contains('tax-course-tag')) {
        return;
    }

    var bannerRemoved = false;
    var hasRunCleanup = false;
    var metaItemsAdded = false;

    function removeBreadcrumbBanner() {
        if (bannerRemoved) return;

        var elementsToRemove = [];
        var walker = document.createTreeWalker(
            document.body,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );

        var textNode;
        while (textNode = walker.nextNode()) {
            if (textNode.textContent.includes('Archives:')) {
                var element = textNode.parentElement;
                while (element && element !== document.body) {
                    if (element.tagName === 'SECTION' ||
                        element.tagName === 'DIV' ||
                        element.tagName === 'HEADER' ||
                        element.className.includes('banner') ||
                        element.className.includes('breadcrumb') ||
                        element.className.includes('page-title')) {
                        elementsToRemove.push(element);
                        break;
                    }
                    element = element.parentElement;
                }
            }
        }

        elementsToRemove.forEach(function(element) {
            element.style.display = 'none';
        });

        bannerRemoved = true;
    }

    function cleanupHoverContent() {
        if (hasRunCleanup) return;

        var hoverContents = document.querySelectorAll('.edublink-single-course .course-hover-content');

        hoverContents.forEach(function(hoverContent) {
            var elementsWithWhiteBg = hoverContent.querySelectorAll('*');

            for (var i = 0; i < elementsWithWhiteBg.length; i++) {
                var child = elementsWithWhiteBg[i];

                if (child.matches('.edu-btn, .btn, button, input, select, [class*="btn"], [class*="button"]')) {
                    child.style.cssText = 'background: #EE4A62 !important; color: #ffffff !important; text-align: center !important; display: flex !important; align-items: center !important; justify-content: center !important;';
                } else {
                    var computedStyle = window.getComputedStyle(child);
                    if (computedStyle.backgroundColor === 'rgb(255, 255, 255)' ||
                        computedStyle.backgroundColor === 'white') {
                        child.style.cssText = 'background: transparent !important; color: #ffffff !important;';
                    }
                }
            }
        });

        hasRunCleanup = true;
    }

    var bannersAdded = false;
    function addSmartNewBanners() {
        if (bannersAdded || !window.thriveEdCourseData) return;

        var courseCards = document.querySelectorAll('.edublink-single-course');

        courseCards.forEach(function(card) {
            var courseIdMatch = card.closest('[id*="post-"]');
            var courseId = courseIdMatch ? courseIdMatch.id.replace('post-', '') : null;

            if (courseId && window.thriveEdCourseData[courseId]) {
                var data = window.thriveEdCourseData[courseId];

                if (data.debug && data.debug.post_date) {
                    var courseDate = new Date(data.debug.post_date);
                    var currentDate = new Date();
                    var daysDifference = Math.ceil((currentDate - courseDate) / (1000 * 60 * 60 * 24));

                    if (daysDifference <= 30 && daysDifference >= 0) {
                        var thumbnail = card.querySelector('.thumbnail');
                        if (thumbnail && !thumbnail.querySelector('.smart-new-banner')) {
                            var banner = document.createElement('div');
                            banner.className = 'smart-new-banner';
                            banner.textContent = 'New';
                            thumbnail.appendChild(banner);
                        }
                    }
                }
            }
        });

        bannersAdded = true;
    }

    var durationBannersRemoved = false;
    function removeDurationBanners() {
        if (durationBannersRemoved) return;

        var thumbnails = document.querySelectorAll('.edublink-single-course .thumbnail');

        thumbnails.forEach(function(thumbnail) {
            var elements = thumbnail.querySelectorAll('*:not(.smart-new-banner)');

            elements.forEach(function(element) {
                var text = element.textContent.toLowerCase();
                if (/\d+\s*(min|minute|hour|hr|m\b|h\b)/.test(text) || /\d+:\d+/.test(text)) {
                    element.style.display = 'none';
                }
            });
        });

        durationBannersRemoved = true;
    }

    function smartDurationConvert(value) {
        if (value === null || value === undefined) {
            return '1 hour';
        }

        if (typeof value === 'string') {
            return value;
        }

        if (typeof value === 'object') {
            var hours = 0;
            var minutes = 0;

            if (value.hours !== undefined) {
                hours = parseInt(value.hours) || 0;
            }
            if (value.minutes !== undefined) {
                minutes = parseInt(value.minutes) || 0;
            }

            if (minutes > 0 && hours === 0) {
                if (minutes >= 60) {
                    hours = Math.floor(minutes / 60);
                    minutes = minutes % 60;
                }
            }

            var parts = [];

            if (hours > 0) {
                parts.push(hours + (hours === 1 ? ' hour' : ' hours'));
            }

            if (minutes > 0) {
                parts.push(minutes + (minutes === 1 ? ' minute' : ' minutes'));
            }

            if (parts.length > 0) {
                return parts.join(' ');
            }

            if (hours === 0 && minutes === 0) {
                return '1 hour';
            }

            if (value.duration) return smartDurationConvert(value.duration);
            if (value.time) return smartDurationConvert(value.time);
            if (value.value) return smartDurationConvert(value.value);
            if (value.text) return smartDurationConvert(value.text);

            return '1 hour';
        }

        if (typeof value === 'number') {
            if (value === 0) return '1 hour';
            if (value === 1) return '1 hour';
            return value + ' hours';
        }

        try {
            var stringValue = value.toString();
            if (stringValue && stringValue !== '[object Object]') {
                return stringValue;
            }
        } catch (e) {
            // Silent fail
        }

        return '1 hour';
    }

    function addCourseMetaItems() {
        if (metaItemsAdded) return;

        var courseCards = document.querySelectorAll('.edublink-single-course');
        var addedCount = 0;

        courseCards.forEach(function(card) {
            var courseIdMatch = card.closest('[id*="post-"]');
            var courseId = courseIdMatch ? courseIdMatch.id.replace('post-', '') : null;

            var courseMeta = card.querySelector('.content .course-meta') ||
                            card.querySelector('.course-meta') ||
                            card.querySelector('.content ul') ||
                            card.querySelector('.content');

            if (courseMeta && !courseMeta.querySelector('.custom-meta-container')) {
                var numberOfWeeks = '8';
                var duration = '1 hour';

                if (courseId && window.thriveEdCourseData && window.thriveEdCourseData[courseId]) {
                    var data = window.thriveEdCourseData[courseId];
                    
                    if(data.weeks && data.weeks !== 'N/A') {
                        numberOfWeeks = String(data.weeks);
                    }
                    
                    if(data.duration && data.duration !== 'N/A') {
                        duration = smartDurationConvert(data.duration);
                    }
                }

                var newMetaContainer = document.createElement('div');
                newMetaContainer.className = 'custom-meta-container';
                newMetaContainer.style.cssText = 'display: flex; flex-direction: column; gap: 6px; margin-top: 6px;';

                var weeksElement = document.createElement('div');
                weeksElement.className = 'custom-meta-weeks';
                weeksElement.style.cssText = 'display: flex; align-items: center; gap: 8px; font-size: 15px; color: #6b7280; line-height: 1.4;';
                weeksElement.innerHTML = '\uD83D\uDCD6 ' + numberOfWeeks + ' Weeks';

                var durationElement = document.createElement('div');
                durationElement.className = 'custom-meta-duration';
                durationElement.style.cssText = 'display: flex; align-items: center; gap: 8px; font-size: 15px; color: #6b7280; line-height: 1.4;';
                durationElement.innerHTML = '\u23F0 ' + duration;

                newMetaContainer.appendChild(weeksElement);
                newMetaContainer.appendChild(durationElement);

                if (courseMeta.tagName === 'UL') {
                    var weeksLi = document.createElement('li');
                    weeksLi.style.cssText = 'display: flex; align-items: center; gap: 8px; font-size: 15px; color: #6b7280; margin: 0; padding: 0; line-height: 1.4;';
                    weeksLi.innerHTML = '\uD83D\uDCD6 ' + numberOfWeeks + ' Weeks';

                    var durationLi = document.createElement('li');
                    durationLi.style.cssText = 'display: flex; align-items: center; gap: 8px; font-size: 15px; color: #6b7280; margin: 0; padding: 0; line-height: 1.4;';
                    durationLi.innerHTML = '\u23F0 ' + duration;

                    courseMeta.appendChild(weeksLi);
                    courseMeta.appendChild(durationLi);
                } else {
                    courseMeta.appendChild(newMetaContainer);
                }

                addedCount++;
            }
        });

        if (addedCount > 0) {
            metaItemsAdded = true;
        }
    }
    
    function runAllModifications() {
        removeBreadcrumbBanner();
        cleanupHoverContent();
        addSmartNewBanners();
        removeDurationBanners();
        addCourseMetaItems();
    }

    // Initial run
    runAllModifications();
    setTimeout(runAllModifications, 500);
    setTimeout(runAllModifications, 1500);

    window.addEventListener('load', function() {
        setTimeout(runAllModifications, 1000);
    });

    var observer = new MutationObserver(function(mutations) {
        var shouldRun = false;
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && node.querySelector && node.querySelector('.edublink-single-course')) {
                        shouldRun = true;
                    }
                });
            }
        });

        if (shouldRun) {
            setTimeout(runAllModifications, 200);
        }
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});