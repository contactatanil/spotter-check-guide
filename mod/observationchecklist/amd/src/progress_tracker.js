
/**
 * Progress tracking functionality for observation checklist
 *
 * @module     mod_observationchecklist/progress_tracker
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {

    /**
     * Progress tracker class
     * @param {number} cmid Course module ID
     */
    function ProgressTracker(cmid) {
        this.cmid = cmid;
        this.init();
    }

    ProgressTracker.prototype.init = function() {
        this.loadUserProgress();
        this.bindEvents();
    };

    ProgressTracker.prototype.bindEvents = function() {
        var self = this;

        // Refresh progress
        $(document).on('click', '#refresh-progress', function() {
            self.loadUserProgress();
        });

        // Generate report
        $(document).on('click', '.generate-report', function() {
            var userId = $(this).data('user-id');
            self.generateReport(userId);
        });
    };

    ProgressTracker.prototype.loadUserProgress = function() {
        var self = this;
        
        Ajax.call([{
            methodname: 'mod_observationchecklist_get_all_progress',
            args: {
                cmid: this.cmid
            }
        }])[0].done(function(result) {
            if (result.success) {
                self.updateProgressDisplay(result.data);
            } else {
                Notification.exception(new Error(result.message));
            }
        }).fail(Notification.exception);
    };

    ProgressTracker.prototype.updateProgressDisplay = function(progressData) {
        var $container = $('#progress-container');
        
        progressData.forEach(function(userProgress) {
            var $userRow = $container.find('[data-user-id="' + userProgress.userid + '"]');
            if ($userRow.length) {
                // Update progress bar
                var percentage = (userProgress.completed / userProgress.total) * 100;
                $userRow.find('.progress-bar').css('width', percentage + '%');
                $userRow.find('.progress-text').text(userProgress.completed + '/' + userProgress.total);
                
                // Update status badges
                $userRow.find('.satisfactory-count').text(userProgress.satisfactory);
                $userRow.find('.not-satisfactory-count').text(userProgress.not_satisfactory);
            }
        });
    };

    ProgressTracker.prototype.generateReport = function(userId) {
        var self = this;
        
        Ajax.call([{
            methodname: 'mod_observationchecklist_generate_report',
            args: {
                cmid: this.cmid,
                userid: userId
            }
        }])[0].done(function(result) {
            if (result.success) {
                // Open report in new window
                var reportWindow = window.open('', '_blank');
                reportWindow.document.write(result.html);
                reportWindow.document.close();
            } else {
                Notification.exception(new Error(result.message));
            }
        }).fail(Notification.exception);
    };

    return {
        init: function(cmid) {
            return new ProgressTracker(cmid);
        }
    };
});
