
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

        // Student selection with error handling
        $(document).on('change.observationchecklist', '.student-checkbox', function() {
            try {
                var studentId = $(this).val();
                var studentName = $(this).data('student-name');
                
                // Enhanced type checking
                if (!studentId || !studentName || typeof studentId === 'undefined' || typeof studentName === 'undefined') {
                    Notification.addNotification({
                        message: 'Invalid student data',
                        type: 'error'
                    });
                    return;
                }
                
                var studentObj = {id: studentId, name: studentName};
                
                if ($(this).is(':checked')) {
                    self.selectedStudents.add(studentObj);
                } else {
                    // Remove student by finding matching id
                    for (let student of self.selectedStudents) {
                        if (student && student.id === studentId) {
                            self.selectedStudents.delete(student);
                            break;
                        }
                    }
                }
                
                self.updateSelectedStudents();
                self.updateObservationGrid();
            } catch (e) {
                Notification.exception(e);
            }
        });

        // Assessment buttons with enhanced error handling
        $(document).on('click.observationchecklist', '.assess-satisfactory, .assess-not-satisfactory', function() {
            try {
                var $card = $(this).closest('.student-observation-card');
                var studentId = $card.data('student-id');
                var itemId = $card.data('item-id');
                var status = $(this).hasClass('assess-satisfactory') ? 'satisfactory' : 'not_satisfactory';
                var notes = $card.find('.notes-textarea').val();

                // Validate required data
                if (!studentId || !itemId) {
                    Notification.addNotification({
                        message: 'Missing required assessment data',
                        type: 'error'
                    });
                    return;
                }

                self.recordObservation(studentId, itemId, status, notes, $card);
            } catch (e) {
                Notification.exception(e);
            }
        });

        // Reset assessment
        $(document).on('click.observationchecklist', '.reset-assessment', function() {
            try {
                var $card = $(this).closest('.student-observation-card');
                var studentId = $card.data('student-id');
                var itemId = $card.data('item-id');

                if (!studentId || !itemId) {
                    Notification.addNotification({
                        message: 'Missing required data for reset',
                        type: 'error'
                    });
                    return;
                }

                self.resetObservation(studentId, itemId, $card);
            } catch (e) {
                Notification.exception(e);
            }
        });

        // Save all observations with validation
        $('#save-all-observations').on('click.observationchecklist', function() {
            try {
                if (self.observations.size === 0) {
                    Notification.addNotification({
                        message: 'No observations to save',
                        type: 'warning'
                    });
                    return;
                }
                self.saveAllObservations();
            } catch (e) {
                Notification.exception(e);
            }
        });

        // Notes change with validation
        $(document).on('input.observationchecklist', '.notes-textarea', function() {
            try {
                var $card = $(this).closest('.student-observation-card');
                var studentId = $card.data('student-id');
                var itemId = $card.data('item-id');
                var notes = $(this).val();
                
                // Validate notes length
                if (notes.length > 1000) {
                    $(this).val(notes.substring(0, 1000));
                    Notification.addNotification({
                        message: 'Notes truncated to 1000 characters maximum',
                        type: 'warning'
                    });
                    return;
                }
                
                var key = studentId + '-' + itemId;

                if (self.observations.has(key)) {
                    var obs = self.observations.get(key);
                    obs.notes = notes;
                    self.observations.set(key, obs);
                }
            } catch (e) {
                Notification.exception(e);
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
                // Enhanced validation before processing
                if (student && student.id && student.name && typeof student.name === 'string') {
                    var escapedName = $('<div>').text(student.name).html(); // XSS protection
                    $list.append('<div class="badge badge-primary mr-1 mb-1">' + escapedName + '</div>');
                }
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

            if (itemId) {
                self.selectedStudents.forEach(function(student) {
                    if (student && student.id && student.name) {
                        self.createStudentObservationCard(student, itemId, $itemGrid);
                    }
                });
            }
        });

        this.updateSaveButton();
    };

    MultiStudentObserver.prototype.createStudentObservationCard = function(student, itemId, $container) {
        try {
            var $template = $('#student-observation-template').clone();
            var $card = $template.find('.student-observation-card');
            
            if ($card.length && student && student.id && student.name && itemId) {
                $card.attr('data-student-id', student.id);
                $card.attr('data-item-id', itemId);
                
                var escapedName = $('<div>').text(student.name).html(); // XSS protection
                $card.find('.student-name').text(escapedName);

                $container.append($template.html());
            }
        } catch (e) {
            Notification.exception(e);
        }
    };

    MultiStudentObserver.prototype.recordObservation = function(studentId, itemId, status, notes, $card) {
        try {
            var key = studentId + '-' + itemId;
            var observation = {
                studentId: parseInt(studentId),
                itemId: parseInt(itemId),
                status: status,
                notes: notes || '',
                timestamp: new Date()
            };

            this.observations.set(key, observation);
            this.updateObservationDisplay($card, status);
            this.updateSaveButton();

            // Show success feedback
            var studentName = $card.find('.student-name').text();
            if (studentName) {
                Notification.addNotification({
                    message: 'Observation recorded for ' + studentName,
                    type: 'success'
                });
            }
        } catch (e) {
            Notification.exception(e);
        }
    };

    MultiStudentObserver.prototype.resetObservation = function(studentId, itemId, $card) {
        try {
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
        } catch (e) {
            Notification.exception(e);
        }
    };

    MultiStudentObserver.prototype.updateObservationDisplay = function($card, status) {
        try {
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
            } else if (status === 'not_satisfactory') {
                $alert.removeClass('alert-success').addClass('alert-danger');
                $icon.removeClass('fa-check').addClass('fa-times');
                $text.text('Not Satisfactory');
                $badge.removeClass('badge-secondary badge-success').addClass('badge-danger').text('Not Satisfactory');
            }
        } catch (e) {
            Notification.exception(e);
        }
    };

    MultiStudentObserver.prototype.updateSaveButton = function() {
        try {
            var $saveBtn = $('#save-all-observations');
            
            if (this.observations.size > 0) {
                $saveBtn.prop('disabled', false).text('Save ' + this.observations.size + ' Observations');
            } else {
                $saveBtn.prop('disabled', true).text('Save All Assessments');
            }
        } catch (e) {
            Notification.exception(e);
        }
    };

    MultiStudentObserver.prototype.saveAllObservations = function() {
        var self = this;
        
        if (this.observations.size === 0) {
            return;
        }

        // Validate observations before sending
        var observations = [];
        this.observations.forEach(function(obs) {
            if (obs && obs.studentId && obs.itemId && obs.status) {
                observations.push({
                    studentId: parseInt(obs.studentId),
                    itemId: parseInt(obs.itemId),
                    status: obs.status,
                    notes: obs.notes || ''
                });
            }
        });

        if (observations.length === 0) {
            Notification.addNotification({
                message: 'No valid observations to save',
                type: 'warning'
            });
            return;
        }

        Ajax.call([{
            methodname: 'mod_observationchecklist_save_multi_observations',
            args: {
                cmid: this.cmid,
                observations: observations
            }
        }])[0].done(function(result) {
            if (result && result.success) {
                Notification.addNotification({
                    message: result.message || 'All observations saved successfully',
                    type: 'success'
                });
                
                // Optionally redirect or reset
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            } else {
                Notification.addNotification({
                    message: result.message || 'Failed to save observations',
                    type: 'error'
                });
            }
        }).fail(function(error) {
            Notification.exception(error);
        });
    };

    // Cleanup function to remove event listeners
    MultiStudentObserver.prototype.destroy = function() {
        $(document).off('.observationchecklist');
        $('#save-all-observations').off('.observationchecklist');
    };

    return {
        init: function(cmid) {
            return new MultiStudentObserver(cmid);
        }
    };
});
