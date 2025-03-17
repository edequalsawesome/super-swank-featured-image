/* global jQuery, wp, ssfiAdmin */
jQuery(document).ready(function($) {
    'use strict';

    let frame;
    let cropFrame;
    const $imagePreview = $('.ssfi-image-preview');
    const $defaultImageInput = $('#ssfi_default_image');
    const $cropSettingsInput = $('#ssfi_default_image_crop');
    const $removeButton = $('#ssfi-remove-image');
    const $cropButton = $('#ssfi-crop-image');
    const $cropDialog = $('#ssfi-crop-dialog');
    
    // Initialize the crop dialog
    $cropDialog.dialog({
        autoOpen: false,
        modal: true,
        width: Math.min($(window).width() - 60, 800),
        height: Math.min($(window).height() - 60, 600),
        title: ssfiAdmin.cropTitle,
        buttons: [
            {
                text: ssfiAdmin.cropButton,
                class: 'button-primary',
                click: function() {
                    const $img = $('#ssfi-crop-image-element');
                    const cropData = $img.data('cropData');
                    if (cropData) {
                        saveCropData(cropData);
                    }
                    $(this).dialog('close');
                }
            },
            {
                text: ssfiAdmin.cancelButton,
                class: 'button-secondary',
                click: function() {
                    $(this).dialog('close');
                }
            }
        ],
        create: function() {
            $(this).closest('.ui-dialog').addClass('wp-dialog');
        },
        open: function() {
            $('.ui-widget-overlay').addClass('wp-dialog-overlay');
        },
        close: function() {
            $('.ui-widget-overlay').removeClass('wp-dialog-overlay');
        }
    });

    // Handle image selection
    $('#ssfi-select-image').on('click', function(e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: ssfiAdmin.cropTitle,
            button: {
                text: ssfiAdmin.cropButton
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });

        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            updateImage(attachment);
        });

        frame.open();
    });

    // Handle image removal
    $removeButton.on('click', function(e) {
        e.preventDefault();
        removeImage();
    });

    // Handle image cropping
    $cropButton.on('click', function(e) {
        e.preventDefault();
        const $img = $('.ssfi-preview-image');
        const fullSrc = $img.data('full-src');
        
        if (fullSrc) {
            openCropDialog(fullSrc);
        }
    });

    /**
     * Update the image preview and form values
     * @param {Object} attachment The attachment object from the media library
     */
    function updateImage(attachment) {
        const previewHtml = `
            <img src="${attachment.sizes.thumbnail.url}" 
                 alt="" 
                 class="ssfi-preview-image"
                 data-full-src="${attachment.url}">
        `;
        
        $imagePreview.html(previewHtml);
        $defaultImageInput.val(attachment.id);
        $removeButton.show();
        $cropButton.show();
        $cropSettingsInput.val('{}');
    }

    /**
     * Remove the current image
     */
    function removeImage() {
        $imagePreview.html('<div class="ssfi-no-image">No image selected</div>');
        $defaultImageInput.val('');
        $cropSettingsInput.val('{}');
        $removeButton.hide();
        $cropButton.hide();
    }

    /**
     * Open the crop dialog
     * @param {string} imageSrc The full size image URL
     */
    function openCropDialog(imageSrc) {
        const $cropImage = $('#ssfi-crop-image-element');
        $cropImage.attr('src', imageSrc);

        // Initialize cropping when image is loaded
        $cropImage.on('load', function() {
            initCropping($(this));
        }).each(function() {
            if (this.complete) {
                $(this).trigger('load');
            }
        });

        $cropDialog.dialog('open');
    }

    /**
     * Initialize the cropping functionality
     * @param {jQuery} $img The image jQuery element
     */
    function initCropping($img) {
        const currentCrop = JSON.parse($cropSettingsInput.val() || '{}');
        
        $img.cropbox({
            width: ssfiAdmin.minWidth,
            height: ssfiAdmin.minHeight,
            zoom: true,
            controls: true,
            showControls: 'always',
            aspectRatio: ssfiAdmin.aspectRatio,
            ...currentCrop
        }).on('cropbox', function(e, data) {
            $img.data('cropData', data);
        });
    }

    /**
     * Save the crop data
     * @param {Object} cropData The crop data from the cropbox plugin
     */
    function saveCropData(cropData) {
        $cropSettingsInput.val(JSON.stringify(cropData));
        
        // Update preview with cropped version
        const $previewImg = $('.ssfi-preview-image');
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const img = new Image();
        
        img.onload = function() {
            canvas.width = cropData.width;
            canvas.height = cropData.height;
            ctx.drawImage(
                img,
                cropData.x,
                cropData.y,
                cropData.width,
                cropData.height,
                0,
                0,
                cropData.width,
                cropData.height
            );
            $previewImg.attr('src', canvas.toDataURL());
        };
        
        img.src = $previewImg.data('full-src');
    }
}); 