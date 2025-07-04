
/**
 * Submission manager for observation checklist
 *
 * @module     mod_observationchecklist/submission
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {

    /**
     * Submission manager
     * @param {number} cmid Course module ID
     */
    function SubmissionManager(cmid) {
        this.cmid = cmid;
        this.init();
    }

    SubmissionManager.prototype.init = function() {
        this.bindEvents();
        this.loadStrings();
    };

    SubmissionManager.prototype.loadStrings = function() {
        var self = this;
        Str.get_strings([
            {key: 'submissioncomplete', component: 'mod_observationchecklist'},
            {key: 'submissionfailed', component: 'mod_observationchecklist'},
            {key: 'confirmsubmission', component: 'mod_observationchecklist'}
        ]).done(function(strings) {
            self.strings = {
                submissioncomplete: strings[0],
                submissionfailed: strings[1],
                confirmsubmission: strings[2]
            };
        });
    };

    SubmissionManager.prototype.bindEvents = function() {
        var self = this;

        // Submit checklist
        $(document).on('click', '#submit-checklist', function(e) {
            e.preventDefault();
            if (confirm(self.strings.confirmsubmission || 'Are you sure you want to submit this checklist?')) {
                self.submitChecklist();
            }
        });

        // Save draft
        $(document).on('click', '#save-draft', function(e) {
            e.preventDefault();
            self.saveDraft();
        });

        // Auto-save on input change
        $(document).on('change input', '.submission-field', function() {
            clearTimeout(self.autoSaveTimeout);
            self.autoSaveTimeout = setTimeout(function() {
                self.autoSave();
            }, 2000);
        });

        // Upload evidence file
        $(document).on('change', '.evidence-upload', function() {
            var itemId = $(this).data('item-id');
            var file = this.files[0];
            if (file) {
                self.uploadEvidence(itemId, file);
            }
        });
    };

    SubmissionManager.prototype.submitChecklist = function() {
        var self = this;
        var formData = this.collectFormData();
        
        Ajax.call([{
            methodname: 'mod_observationchecklist_submit_checklist',
            args: {
                cmid: this.cmid,
                submissions: formData
            }
        }])[0].done(function(result) {
            if (result.success) {
                Notification.addNotification({
                    message: self.strings.submissioncomplete || 'Checklist submitted successfully',
                    type: 'success'
                });
                // Redirect or update UI
                if (result.redirecturl) {
                    window.location.href = result.redirecturl;
                } else {
                    location.reload();
                }
            } else {
                Notification.exception(new Error(result.message || self.strings.submissionfailed));
            }
        }).fail(function(error) {
            Notification.exception(error);
        });
    };

    SubmissionManager.prototype.saveDraft = function() {
        var self = this;
        var formData = this.collectFormData();
        
        Ajax.call([{
            methodname: 'mod_observationchecklist_save_draft',
            args: {
                cmid: this.cmid,
                submissions: formData
            }
        }])[0].done(function(result) {
            if (result.success) {
                Notification.addNotification({
                    message: 'Draft saved successfully',
                    type: 'info'
                });
                // Update last saved time
                $('#last-saved-time').text('Saved at ' + new Date().toLocaleTimeString());
            } else {
                Notification.exception(new Error(result.message || 'Failed to save draft'));
            }
        }).fail(function(error) {
            Notification.exception(error);
        });
    };

    SubmissionManager.prototype.autoSave = function() {
        this.saveDraft();
    };

    SubmissionManager.prototype.collectFormData = function() {
        var submissions = [];
        
        $('.checklist-item').each(function() {
            var itemId = $(this).data('item-id');
            var responses = {};
            
            // Collect all form inputs for this item
            $(this).find('.submission-field').each(function() {
                var fieldName = $(this).attr('name');
                var fieldValue = $(this).val();
                var fieldType = $(this).attr('type') || $(this).prop('tagName').toLowerCase();
                
                if (fieldType === 'checkbox' || fieldType === 'radio') {
                    if ($(this).is(':checked')) {
                        responses[fieldName] = fieldValue;
                    }
                } else {
                    responses[fieldName] = fieldValue;
                }
            });
            
            // Collect file uploads
            var evidenceFiles = [];
            $(this).find('.evidence-upload').each(function() {
                if (this.files && this.files.length > 0) {
                    evidenceFiles.push({
                        filename: this.files[0].name,
                        filesize: this.files[0].size
                    });
                }
            });
            
            submissions.push({
                itemid: itemId,
                responses: responses,
                evidence: evidenceFiles
            });
        });
        
        return submissions;
    };

    SubmissionManager.prototype.uploadEvidence = function(itemId, file) {
        var self = this;
        var formData = new FormData();
        formData.append('file', file);
        formData.append('itemid', itemId);
        formData.append('cmid', this.cmid);
        formData.append('sesskey', M.cfg.sesskey);

        $.ajax({
            url: M.cfg.wwwroot + '/mod/observationchecklist/upload_evidence.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Notification.addNotification({
                        message: 'Evidence uploaded successfully',
                        type: 'success'
                    });
                    // Update the evidence display
                    self.updateEvidenceDisplay(itemId, response.file);
                } else {
                    Notification.exception(new Error(response.message || 'Failed to upload evidence'));
                }
            },
            error: function() {
                Notification.exception(new Error('Failed to upload evidence'));
            }
        });
    };

    SubmissionManager.prototype.updateEvidenceDisplay = function(itemId, fileInfo) {
        var $evidenceList = $('#evidence-list-' + itemId);
        var html = '<div class="evidence-item mb-2">';
        html += '<i class="fa fa-file-o mr-2"></i>';
        html += '<span>' + fileInfo.filename + '</span>';
        html += '<small class="text-muted ml-2">(' + this.formatFileSize(fileInfo.filesize) + ')</small>';
        html += '</div>';
        $evidenceList.append(html);
    };

    SubmissionManager.prototype.formatFileSize = function(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    return {
        init: function(cmid) {
            return new SubmissionManager(cmid);
        }
    };
});
