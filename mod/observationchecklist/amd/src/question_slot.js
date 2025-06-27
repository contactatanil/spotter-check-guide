
/**
 * Question slot manager for observation checklist
 *
 * @module     mod_observationchecklist/question_slot
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {

    /**
     * Question slot manager
     * @param {number} cmid Course module ID
     */
    function QuestionSlotManager(cmid) {
        this.cmid = cmid;
        this.init();
    }

    QuestionSlotManager.prototype.init = function() {
        this.bindEvents();
    };

    QuestionSlotManager.prototype.bindEvents = function() {
        var self = this;

        // Add question slot
        $(document).on('click', '.add-question-slot', function() {
            var questionType = $(this).data('question-type');
            var itemId = $(this).data('item-id');
            self.addQuestionSlot(itemId, questionType);
        });

        // Remove question slot
        $(document).on('click', '.remove-question-slot', function() {
            var slotId = $(this).data('slot-id');
            self.removeQuestionSlot(slotId);
        });

        // Update question slot
        $(document).on('blur', '.question-slot-input', function() {
            var slotId = $(this).data('slot-id');
            var value = $(this).val();
            self.updateQuestionSlot(slotId, value);
        });
    };

    QuestionSlotManager.prototype.addQuestionSlot = function(itemId, questionType) {
        var self = this;
        
        Ajax.call([{
            methodname: 'mod_observationchecklist_add_question_slot',
            args: {
                itemid: itemId,
                questiontype: questionType
            }
        }])[0].done(function(result) {
            if (result.success) {
                // Add the new slot to the DOM
                self.renderQuestionSlot(result.slot);
                Notification.addNotification({
                    message: 'Question slot added successfully',
                    type: 'success'
                });
            } else {
                Notification.exception(new Error(result.message || 'Failed to add question slot'));
            }
        }).fail(function(error) {
            Notification.exception(error);
        });
    };

    QuestionSlotManager.prototype.removeQuestionSlot = function(slotId) {
        var self = this;
        
        Ajax.call([{
            methodname: 'mod_observationchecklist_remove_question_slot',
            args: {
                slotid: slotId
            }
        }])[0].done(function(result) {
            if (result.success) {
                $('#question-slot-' + slotId).fadeOut(300, function() {
                    $(this).remove();
                });
                Notification.addNotification({
                    message: 'Question slot removed successfully',
                    type: 'success'
                });
            } else {
                Notification.exception(new Error(result.message || 'Failed to remove question slot'));
            }
        }).fail(function(error) {
            Notification.exception(error);
        });
    };

    QuestionSlotManager.prototype.updateQuestionSlot = function(slotId, value) {
        Ajax.call([{
            methodname: 'mod_observationchecklist_update_question_slot',
            args: {
                slotid: slotId,
                value: value
            }
        }])[0].done(function(result) {
            if (!result.success) {
                Notification.exception(new Error(result.message || 'Failed to update question slot'));
            }
        }).fail(function(error) {
            Notification.exception(error);
        });
    };

    QuestionSlotManager.prototype.renderQuestionSlot = function(slot) {
        var html = '<div id="question-slot-' + slot.id + '" class="question-slot mb-2">';
        html += '<div class="input-group">';
        
        switch (slot.questiontype) {
            case 'text':
                html += '<input type="text" class="form-control question-slot-input" ';
                html += 'data-slot-id="' + slot.id + '" placeholder="Enter text question">';
                break;
            case 'textarea':
                html += '<textarea class="form-control question-slot-input" rows="3" ';
                html += 'data-slot-id="' + slot.id + '" placeholder="Enter detailed question"></textarea>';
                break;
            case 'select':
                html += '<select class="form-control question-slot-input" data-slot-id="' + slot.id + '">';
                html += '<option value="">Select option</option>';
                html += '</select>';
                break;
        }
        
        html += '<div class="input-group-append">';
        html += '<button class="btn btn-danger remove-question-slot" data-slot-id="' + slot.id + '">';
        html += '<i class="fa fa-trash"></i></button>';
        html += '</div></div></div>';
        
        $('#question-slots-' + slot.itemid).append(html);
    };

    return {
        init: function(cmid) {
            return new QuestionSlotManager(cmid);
        }
    };
});
