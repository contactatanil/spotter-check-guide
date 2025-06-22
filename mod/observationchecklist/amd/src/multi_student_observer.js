
/**
 * Multi-student observation functionality
 *
 * @module     mod_observationchecklist/multi_student_observer
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/templates'], function($, Ajax, Notification, Templates) {

    /**
     * Multi-student observer class
     * @param {number} cmid Course module ID
     */
    function MultiStudentObserver(cmid) {
        this.cmid = cmid;
        this.selectedStudents = new Set();
        this.observations = new Map();
        this.init();
    }

    MultiStudentObserver.prototype.init = function() {
        this.bindEvents();
        this.updateSelectedCount();
    };

    MultiStudentObserver.prototype.bindEvents = function() {
        var self = this;

        // Student selection
        $(document).on('change', '.student-checkbox', function() {
            var studentId = $(this).val();
            var studentName = $(this).data('student-name');
            
            if ($(this).is(':checked')) {
                self.selectedStudents.add({id: studentId, name: studentName});
            } else {
                self.selectedStudents.delete({id: studentId, name: studentName});
            }
            
            self.updateSelectedStudents();
            self.updateObservationGrid();
        });

        // Assessment buttons
        $(document).on('click', '.assess-satisfactory, .assess-not-satisfactory', function() {
            var $card = $(this).closest('.student-observation-card');
            var studentId = $card.data('student-id');
            var itemId = $card.data('item-id');
            var status = $(this).hasClass('assess-satisfactory') ? 'satisfactory' : 'not_satisfactory';
            var notes = $card.find('.notes-textarea').val();

            self.recordObservation(studentId, itemId, status, notes, $card);
        });

        // Reset assessment
        $(document).on('click', '.reset-assessment', function() {
            var $card = $(this).closest('.student-observation-card');
            var studentId = $card.data('student-id');
            var itemId = $card.data('item-id');

            self.resetObservation(studentId, itemId, $card);
        });

        // Save all observations
        $('#save-all-observations').on('click', function() {
            self.saveAllObservations();
        });

        // Notes change
        $(document).on('input', '.notes-textarea', function() {
            var $card = $(this).closest('.student-observation-card');
            var studentId = $card.data('student-id');
            var itemId = $card.data('item-id');
            var key = studentId + '-' + itemId;

            if (self.observations.has(key)) {
                var obs = self.observations.get(key);
                obs.notes = $(this).val();
                self.observations.set(key, obs);
            }
        });
    };

    MultiStudentObserver.prototype.updateSelectedStudents = function() {
        var self = this;
        var $list = $('#selected-students-list');
        var $count = $('#selected-count');
        
        $count.text(this.selectedStudents.size);
        $list.empty();

        if (this.selectedStudents.size > 0) {
            this.selectedStudents.forEach(function(student) {
                $list.append('<div class="badge badge-primary mr-1 mb-1">' + student.name + '</div>');
            });
        }
    };

    MultiStudentObserver.prototype.updateSelectedCount = function() {
        $('#selected-count').text(this.selectedStudents.size);
    };

    MultiStudentObserver.prototype.updateObservationGrid = function() {
        var self = this;
        var $grid = $('#observation-grid');
        var $noStudentsMsg = $('#no-students-message');

        if (this.selectedStudents.size === 0) {
            $grid.hide();
            $noStudentsMsg.show();
            return;
        }

        $noStudentsMsg.hide();
        $grid.show();

        // Clear existing grids
        $('.student-observation-grid').empty();

        // Populate grids for each item
        $('.observation-item').each(function() {
            var itemId = $(this).data('item-id');
            var $itemGrid = $(this).find('.student-observation-grid');

            self.selectedStudents.forEach(function(student) {
                self.createStudentObservationCard(student, itemId, $itemGrid);
            });
        });

        this.updateSaveButton();
    };

    MultiStudentObserver.prototype.createStudentObservationCard = function(student, itemId, $container) {
        var $template = $('#student-observation-template').clone();
        var $card = $template.find('.student-observation-card');
        
        $card.attr('data-student-id', student.id);
        $card.attr('data-item-id', itemId);
        $card.find('.student-name').text(student.name);

        $container.append($template.html());
    };

    MultiStudentObserver.prototype.recordObservation = function(studentId, itemId, status, notes, $card) {
        var key = studentId + '-' + itemId;
        var observation = {
            studentId: studentId,
            itemId: itemId,
            status: status,
            notes: notes,
            timestamp: new Date()
        };

        this.observations.set(key, observation);
        this.updateObservationDisplay($card, status);
        this.updateSaveButton();

        // Show success feedback
        var studentName = $card.find('.student-name').text();
        Notification.addNotification({
            message: 'Observation recorded for ' + studentName,
            type: 'success'
        });
    };

    MultiStudentObserver.prototype.resetObservation = function(studentId, itemId, $card) {
        var key = studentId + '-' + itemId;
        this.observations.delete(key);

        // Reset display
        $card.find('.observation-buttons').show();
        $card.find('.assessment-result').hide();
        $card.find('.observation-status .badge')
            .removeClass('badge-success badge-danger')
            .addClass('badge-secondary')
            .text('Not Observed');

        this.updateSaveButton();
    };

    MultiStudentObserver.prototype.updateObservationDisplay = function($card, status) {
        var $buttons = $card.find('.observation-buttons');
        var $result = $card.find('.assessment-result');
        var $alert = $result.find('.assessment-alert');
        var $icon = $result.find('.assessment-icon');
        var $text = $result.find('.assessment-text');
        var $badge = $card.find('.observation-status .badge');

        $buttons.hide();
        $result.show();

        if (status === 'satisfactory') {
            $alert.removeClass('alert-danger').addClass('alert-success');
            $icon.removeClass('fa-times').addClass('fa-check');
            $text.text('Satisfactory');
            $badge.removeClass('badge-secondary badge-danger').addClass('badge-success').text('Satisfactory');
        } else {
            $alert.removeClass('alert-success').addClass('alert-danger');
            $icon.removeClass('fa-check').addClass('fa-times');
            $text.text('Not Satisfactory');
            $badge.removeClass('badge-secondary badge-success').addClass('badge-danger').text('Not Satisfactory');
        }
    };

    MultiStudentObserver.prototype.updateSaveButton = function() {
        var $saveBtn = $('#save-all-observations');
        
        if (this.observations.size > 0) {
            $saveBtn.prop('disabled', false).text('Save ' + this.observations.size + ' Observations');
        } else {
            $saveBtn.prop('disabled', true).text('Save All Assessments');
        }
    };

    MultiStudentObserver.prototype.saveAllObservations = function() {
        var self = this;
        
        if (this.observations.size === 0) {
            return;
        }

        var observations = Array.from(this.observations.values());

        Ajax.call([{
            methodname: 'mod_observationchecklist_save_multi_observations',
            args: {
                cmid: this.cmid,
                observations: observations
            }
        }])[0].done(function(result) {
            if (result.success) {
                Notification.addNotification({
                    message: 'All observations saved successfully',
                    type: 'success'
                });
                
                // Optionally redirect or reset
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            } else {
                Notification.exception(new Error(result.message || 'Failed to save observations'));
            }
        }).fail(Notification.exception);
    };

    return {
        init: function(cmid) {
            return new MultiStudentObserver(cmid);
        }
    };
});
