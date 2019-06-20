/* jshint ignore:start */
define(['jquery', 'core/log'], function($, log) {
    "use strict"; // ...jshint ;_; !!!

    log.debug('Adaptable AMD Show sidebar');

    return {
        init: function() {
            $(document).ready(function($) {
                log.debug('Adaptable AMD Show sidebar init');

                var sidePostClosed = true;
                var sidePost = $('#block-region-side-post');
                var showSideBar = $('#showsidebaricon');
                var showSideBarIcon = $('#showsidebaricon i.fa');
                if (typeof sidePost != 'undefined') {
                    showSideBar.click(function() {
                        if (sidePostClosed === true) {
                            sidePost.addClass('sidebarshown');
                            showSideBar.addClass('sidebarshown');
                            showSideBarIcon.removeClass('fa-chevron-left');
                            showSideBarIcon.addClass('fa-chevron-right');
                            sidePostClosed = false;
                        } else {
                            sidePost.removeClass('sidebarshown');
                            showSideBar.removeClass('sidebarshown');
                            showSideBarIcon.removeClass('fa-chevron-right');
                            showSideBarIcon.addClass('fa-chevron-left');
                            sidePostClosed = true;
                        }
                    });
                }
            });
        }
    };
});
/* jshint ignore:end */
