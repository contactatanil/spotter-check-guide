
/**
 * Checklist manager for observation checklist functionality
 *
 * @module     mod_observationchecklist/checklist_manager
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/templates'], function($, Ajax, Notification, Templates) {

    /**
     * Checklist manager class
     * @param {number} cmid Course module ID
     */
    function ChecklistManager(cmid) {
        this.cmid = cmid;
        this.init();
    }

    ChecklistManager.prototype.init = function() {
        this.bindEvents();
    };

    ChecklistManager.prototype.bindEvents = function() {
        var self = this;

        // Add new item
        $(document).on('click', '#add-item-btn', function() {
            var itemText = $('#new-item-text').val().trim();
            if (itemText) {
                self.addItem(itemText);
            }
        });

        // Delete item
        $(document).on('click', '.delete-item', function() {
            var itemId = $(this).data('item-id');
            if (confirm(M.util.get_string('confirmdelete', 'mod_observationchecklist'))) {
                self.deleteItem(itemId);
            }
        });

        // Assess item
        $(document).on('click', '.assess-btn', function() {
            var itemId = $(this).data('item-id');
            var userId = $(this).data('user-id');
            var status = $(this).data('status');
            var notes = $(this).closest('.assessment-row').find('.assessment-notes').val();
            
            self.assessItem(itemId, userId, status, notes);
        });
    };

    ChecklistManager.prototype.addItem = function(itemText) {
        var self = this;
        
        Ajax.call([{
            methodname: 'mod_observationchecklist_add_item',
            args: {
                cmid: this.cmid,
                itemtext: itemText
            }
        }])[0].done(function(result) {
            if (result.success) {
                Notification.addNotification({
                    message: M.util.get_string('itemadded', 'mod_observationchecklist'),
                    type: 'success'
                });
                location.reload();
            } else {
                Notification.exception(new Error(result.message));
            }
        }).fail(Notification.exception);
    };

    ChecklistManager.prototype.deleteItem = function(itemId) {
        var self = this;
        
        Ajax.call([{
            methodname: 'mod_observationchecklist_delete_item',
            args: {
                cmid: this.cmid,
                itemid: itemId
            }
        }])[0].done(function(result) {
            if (result.success) {
                Notification.addNotification({
                    message: M.util.get_string('itemdeleted', 'mod_observationchecklist'),
                    type: 'success'
                });
                $('#item-' + itemId).fadeOut();
            } else {
                Notification.exception(new Error(result.message));
            }
        }).fail(Notification.exception);
    };

    ChecklistManager.prototype.assessItem = function(itemId, userId, status, notes) {
        var self = this;
        
        Ajax.call([{
            methodname: 'mod_observationchecklist_assess_item',
            args: {
                cmid: this.cmid,
                itemid: itemId,
                userid: userId,
                status: status,
                notes: notes
            }
        }])[0].done(function(result) {
            if (result.success) {
                Notification.addNotification({
                    message: M.util.get_string('assessmentadded', 'mod_observationchecklist'),
                    type: 'success'
                });
                // Update UI to reflect assessment
                self.updateAssessmentDisplay(itemId, userId, status);
            } else {
                Notification.exception(new Error(result.message));
            }
        }).fail(Notification.exception);
    };

    ChecklistManager.prototype.updateAssessmentDisplay = function(itemId, userId, status) {
        var $row = $('.assessment-row[data-item-id="' + itemId + '"][data-user-id="' + userId + '"]');
        var statusClass = status === 'satisfactory' ? 'badge-success' : 'badge-danger';
        var statusText = status === 'satisfactory' ? 
            M.util.get_string('satisfactory', 'mod_observationchecklist') : 
            M.util.get_string('not_satisfactory', 'mod_observationchecklist');
        
        $row.find('.status-badge').removeClass('badge-secondary badge-success badge-danger')
            .addClass(statusClass).text(statusText);
        $row.find('.assess-buttons').hide();
        $row.find('.assessment-complete').show();
    };

    return {
        init: function(cmid) {
            return new ChecklistManager(cmid);
        }
    };
});
