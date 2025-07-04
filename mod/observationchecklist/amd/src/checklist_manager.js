
/**
 * Checklist manager for observation checklist functionality
 *
 * @module     mod_observationchecklist/checklist_manager
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {

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
        this.loadStrings();
    };

    ChecklistManager.prototype.loadStrings = function() {
        this.strings = {};
        var self = this;
        
        Str.get_strings([
            {key: 'confirmdelete', component: 'mod_observationchecklist'},
            {key: 'itemadded', component: 'mod_observationchecklist'},
            {key: 'itemdeleted', component: 'mod_observationchecklist'},
            {key: 'assessmentadded', component: 'mod_observationchecklist'},
            {key: 'satisfactory', component: 'mod_observationchecklist'},
            {key: 'not_satisfactory', component: 'mod_observationchecklist'}
        ]).done(function(strings) {
            self.strings.confirmdelete = strings[0];
            self.strings.itemadded = strings[1];
            self.strings.itemdeleted = strings[2];
            self.strings.assessmentadded = strings[3];
            self.strings.satisfactory = strings[4];
            self.strings.not_satisfactory = strings[5];
        });
    };

    ChecklistManager.prototype.bindEvents = function() {
        var self = this;

        // Add new item
        $(document).on('click', '#add-item-btn', function() {
            var itemText = $('#new-item-text').val().trim();
            var category = $('#new-item-category').val().trim() || 'General';
            if (itemText) {
                self.addItem(itemText, category);
            }
        });

        // Delete item
        $(document).on('click', '.delete-item', function() {
            var itemId = $(this).data('item-id');
            if (confirm(self.strings.confirmdelete || 'Are you sure you want to delete this item?')) {
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

        // Submit evidence
        $(document).on('click', '.submit-evidence-btn', function() {
            var itemId = $(this).data('item-id');
            var fileInput = $(this).closest('.evidence-section').find('.evidence-file')[0];
            var notes = $(this).closest('.evidence-section').find('.evidence-notes').val();
            
            if (fileInput.files.length > 0) {
                self.submitEvidence(itemId, fileInput.files[0], notes);
            }
        });
    };

    ChecklistManager.prototype.addItem = function(itemText, category) {
        var self = this;
        
        Ajax.call([{
            methodname: 'mod_observationchecklist_add_item',
            args: {
                cmid: this.cmid,
                itemtext: itemText,
                category: category || 'General'
            }
        }])[0].done(function(result) {
            if (result.success) {
                Notification.addNotification({
                    message: self.strings.itemadded || 'Item added successfully',
                    type: 'success'
                });
                location.reload();
            } else {
                Notification.exception(new Error(result.message || 'Failed to add item'));
            }
        }).fail(function(error) {
            Notification.exception(error);
        });
    };

    ChecklistManager.prototype.deleteItem = function(itemId) {
        var self = this;
        
        Ajax.call([{
            methodname: 'mod_observationchecklist_delete_item',
            args: {
                itemid: itemId
            }
        }])[0].done(function(result) {
            if (result.success) {
                Notification.addNotification({
                    message: self.strings.itemdeleted || 'Item deleted successfully',
                    type: 'success'
                });
                $('#item-' + itemId).fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                Notification.exception(new Error(result.message || 'Failed to delete item'));
            }
        }).fail(function(error) {
            Notification.exception(error);
        });
    };

    ChecklistManager.prototype.assessItem = function(itemId, userId, status, notes) {
        var self = this;
        
        Ajax.call([{
            methodname: 'mod_observationchecklist_assess_item',
            args: {
                itemid: itemId,
                userid: userId,
                status: status,
                notes: notes || ''
            }
        }])[0].done(function(result) {
            if (result.success) {
                Notification.addNotification({
                    message: self.strings.assessmentadded || 'Assessment added successfully',
                    type: 'success'
                });
                self.updateAssessmentDisplay(itemId, userId, status);
            } else {
                Notification.exception(new Error(result.message || 'Failed to assess item'));
            }
        }).fail(function(error) {
            Notification.exception(error);
        });
    };

    ChecklistManager.prototype.submitEvidence = function(itemId, file, notes) {
        var self = this;
        var formData = new FormData();
        formData.append('file', file);
        formData.append('itemid', itemId);
        formData.append('notes', notes || '');
        formData.append('cmid', this.cmid);

        // For file uploads, we need to use a different approach
        $.ajax({
            url: M.cfg.wwwroot + '/mod/observationchecklist/submit_evidence.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Notification.addNotification({
                        message: 'Evidence submitted successfully',
                        type: 'success'
                    });
                    location.reload();
                } else {
                    Notification.exception(new Error(response.message || 'Failed to submit evidence'));
                }
            },
            error: function() {
                Notification.exception(new Error('Failed to submit evidence'));
            }
        });
    };

    ChecklistManager.prototype.updateAssessmentDisplay = function(itemId, userId, status) {
        var $row = $('.assessment-row[data-item-id="' + itemId + '"][data-user-id="' + userId + '"]');
        var statusClass = status === 'satisfactory' ? 'badge-success' : 'badge-danger';
        var statusText = status === 'satisfactory' ? 
            (this.strings.satisfactory || 'Satisfactory') : 
            (this.strings.not_satisfactory || 'Not Satisfactory');
        
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
