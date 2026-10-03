/**
 * Image Uploader Component
 * Handles menu item image upload functionality
 */
define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return {
        builder: null,

        /**
         * Initialize with reference to builder
         */
        init: function(builder) {
            this.builder = builder;
            return this;
        },

        /**
         * Initialize image uploader event handlers
         */
        initialize: function () {
            var self = this;

            // Choose Image button click
            $('#item-image-upload-btn').off('click').on('click', function() {
                $('#item-image-file').click();
            });

            // File input change
            $('#item-image-file').off('change').on('change', function(e) {
                var file = e.target.files[0];
                if (file) {
                    $('#item-image-filename').text(file.name);
                    self.upload(file);
                }
            });

            // Remove image button
            $('#item-image-remove').off('click').on('click', function() {
                $('#item-image').val('');
                $('#item-image-url').val('');
                $('#item-image-preview').hide();
                $('#item-image-preview-img').attr('src', '');
                $('#item-image-filename').text('');
                $('#item-image-file').val('');
            });

            // Manual URL input
            $('#item-image-url').off('input').on('input', function() {
                var url = $(this).val();
                $('#item-image').val(url);
                if (url) {
                    $('#item-image-preview-img').attr('src', url);
                    $('#item-image-preview').show();
                } else {
                    $('#item-image-preview').hide();
                }
            });
        },

        /**
         * Upload image file
         */
        upload: function (file) {
            var self = this;
            var formData = new FormData();
            formData.append('image', file);

            // Add form key for CSRF protection
            var formKey = window.FORM_KEY || $('input[name="form_key"]').val();
            if (formKey) {
                formData.append('form_key', formKey);
            }

            // Show progress bar
            $('#item-image-progress').show();
            $('#item-image-progress-bar').css('width', '0%');

            $.ajax({
                url: self.builder.uploadUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            var percentComplete = (e.loaded / e.total) * 100;
                            $('#item-image-progress-bar').css('width', percentComplete + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    $('#item-image-progress').hide();

                    if (response.url) {
                        // Set the uploaded image URL
                        $('#item-image').val(response.url);
                        $('#item-image-url').val(response.url);
                        $('#item-image-preview-img').attr('src', response.url);
                        $('#item-image-preview').show();

                        if (self.builder && self.builder.showNotification) {
                            self.builder.showNotification('success', $t('Image uploaded successfully'));
                        }
                    } else if (response.error) {
                        if (self.builder && self.builder.showNotification) {
                            self.builder.showNotification('error', $t('Upload failed: ') + response.error);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    $('#item-image-progress').hide();
                    if (self.builder && self.builder.showNotification) {
                        self.builder.showNotification('error', $t('Upload failed: ') + error);
                    }
                }
            });
        }
    };
});
