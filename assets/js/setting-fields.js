/**
 * Setting Fields JavaScript
 *
 * @package ArrayPress\WP\Register\SettingFields
 */

(function($) {
    'use strict';

    const SettingFields = {

        /**
         * Initialize all functionality
         */
        init: function() {
            this.initConditionalLogic();
            this.initSelect2();
            this.initColorPicker();
            this.initCodeEditor();
            this.initRangeSlider();
            this.initImageFields();
            this.initFileFields();
            this.initGalleryFields();
            this.initRepeater();
            this.initButtonGroup();
            this.initDimensions();
        },

        /**
         * Conditional Logic
         */
        initConditionalLogic: function() {
            const self = this;
            const $rows = $('tr[data-conditions]');

            if (!$rows.length) return;

            // Initial check
            $rows.each(function() {
                self.evaluateConditions($(this));
            });

            // Listen for changes
            $('.setting-fields-form').on('change', 'input, select, textarea', function() {
                $rows.each(function() {
                    self.evaluateConditions($(this));
                });
            });
        },

        evaluateConditions: function($row) {
            const conditions = $row.data('conditions');
            if (!conditions || !conditions.length) return;

            let allMet = true;

            conditions.forEach(function(condition) {
                const $field = $('[name*="[' + condition.field + ']"]');
                if (!$field.length) return;

                let currentValue = self.getFieldValue($field);
                let conditionMet = self.checkCondition(currentValue, condition.value, condition.operator);

                if (!conditionMet) {
                    allMet = false;
                }
            });

            const self = this;

            if (allMet) {
                $row.removeClass('setting-field-hidden').show();
            } else {
                $row.addClass('setting-field-hidden').hide();
            }
        },

        getFieldValue: function($field) {
            const type = $field.attr('type');
            const tagName = $field.prop('tagName').toLowerCase();

            if (type === 'checkbox') {
                if ($field.length > 1) {
                    // Checkbox group
                    return $field.filter(':checked').map(function() {
                        return $(this).val();
                    }).get();
                }
                return $field.is(':checked') ? $field.val() : '';
            }

            if (type === 'radio') {
                return $field.filter(':checked').val() || '';
            }

            if (tagName === 'select' && $field.prop('multiple')) {
                return $field.val() || [];
            }

            return $field.val();
        },

        checkCondition: function(current, expected, operator) {
            switch (operator) {
                case '=':
                case '==':
                    return current == expected;
                case '===':
                    return current === expected;
                case '!=':
                case '!==':
                    return current != expected;
                case '>':
                    return parseFloat(current) > parseFloat(expected);
                case '>=':
                    return parseFloat(current) >= parseFloat(expected);
                case '<':
                    return parseFloat(current) < parseFloat(expected);
                case '<=':
                    return parseFloat(current) <= parseFloat(expected);
                case 'in':
                    expected = Array.isArray(expected) ? expected : [expected];
                    return expected.indexOf(current) !== -1;
                case 'not_in':
                    expected = Array.isArray(expected) ? expected : [expected];
                    return expected.indexOf(current) === -1;
                case 'contains':
                    if (Array.isArray(current)) {
                        return current.indexOf(expected) !== -1;
                    }
                    return String(current).indexOf(expected) !== -1;
                case 'not_contains':
                    if (Array.isArray(current)) {
                        return current.indexOf(expected) === -1;
                    }
                    return String(current).indexOf(expected) === -1;
                case 'empty':
                    return !current || (Array.isArray(current) && current.length === 0);
                case 'not_empty':
                    return current && (!Array.isArray(current) || current.length > 0);
                default:
                    return current == expected;
            }
        },

        /**
         * Select2 Initialization
         */
        initSelect2: function() {
            const self = this;

            $('[data-select2="true"]').each(function() {
                const $select = $(this);
                const options = {
                    width: '100%',
                    allowClear: $select.data('allow-clear') === 'true',
                    placeholder: $select.data('placeholder') || ''
                };

                // AJAX configuration using REST API
                if ($select.data('ajax') === 'true') {
                    options.ajax = {
                        url: settingFieldsData.restUrl + 'ajax',
                        dataType: 'json',
                        delay: 250,
                        headers: {
                            'X-WP-Nonce': settingFieldsData.restNonce
                        },
                        data: function(params) {
                            const data = {
                                settings_id: settingFieldsData.settingsId,
                                field_key: $select.data('field-key'),
                                field_type: $select.data('field-type') || 'ajax',
                                search: params.term,
                                page: params.page || 1
                            };
                            
                            if ($select.data('post-type')) {
                                data.post_type = $select.data('post-type');
                            }
                            if ($select.data('taxonomy')) {
                                data.taxonomy = $select.data('taxonomy');
                            }
                            if ($select.data('role')) {
                                data.role = $select.data('role');
                            }
                            
                            return data;
                        },
                        processResults: function(data, params) {
                            params.page = params.page || 1;
                            
                            // Map value/label to id/text for Select2
                            const results = (data.results || []).map(function(item) {
                                return {
                                    id: item.value,
                                    text: item.label
                                };
                            });
                            
                            return {
                                results: results,
                                pagination: {
                                    more: data.pagination && data.pagination.more
                                }
                            };
                        },
                        cache: true
                    };
                    options.minimumInputLength = parseInt($select.data('minimum-input')) || 2;
                }

                // Tags support
                if ($select.data('tags') === 'true') {
                    options.tags = true;
                }

                // Maximum selection
                if ($select.data('maximum-selection-length')) {
                    options.maximumSelectionLength = parseInt($select.data('maximum-selection-length'));
                }

                $select.select2(options);
            });
        },

        /**
         * Color Picker
         */
        initColorPicker: function() {
            $('.setting-fields-color-picker').each(function() {
                const $input = $(this);
                const options = {
                    defaultColor: $input.data('default-color') || false,
                    change: function(event, ui) {
                        $input.trigger('change');
                    }
                };

                // Alpha support
                if ($input.data('alpha-enabled') === 'true') {
                    options.palettes = true;
                }

                // Custom palettes
                if ($input.data('palettes')) {
                    options.palettes = $input.data('palettes');
                }

                $input.wpColorPicker(options);
            });
        },

        /**
         * Code Editor
         */
        initCodeEditor: function() {
            if (typeof wp.codeEditor === 'undefined') return;

            $('.setting-fields-code-editor').each(function() {
                const $textarea = $(this);
                const mime = $textarea.data('mime') || 'text/html';

                wp.codeEditor.initialize($textarea, {
                    codemirror: {
                        mode: mime,
                        lineNumbers: true,
                        indentUnit: 4,
                        tabSize: 4,
                        lineWrapping: true
                    }
                });
            });
        },

        /**
         * Range Slider
         */
        initRangeSlider: function() {
            $('.setting-fields-range').on('input', function() {
                const $input = $(this);
                const $value = $('.setting-fields-range-value[data-target="' + $input.attr('id') + '"]');
                $value.text($input.val());
            });
        },

        /**
         * Image Fields
         */
        initImageFields: function() {
            const self = this;

            // Select/Change Image
            $(document).on('click', '.setting-fields-image-select, .setting-fields-image-change', function(e) {
                e.preventDefault();
                const $field = $(this).closest('.setting-fields-image-field');
                self.openMediaFrame($field, 'image');
            });

            // Remove Image
            $(document).on('click', '.setting-fields-image-remove', function(e) {
                e.preventDefault();
                const $field = $(this).closest('.setting-fields-image-field');
                $field.find('.setting-fields-image-value').val('').trigger('change');
                $field.find('.setting-fields-image-preview').addClass('hidden').find('img').attr('src', '');
                $field.find('.setting-fields-image-select').removeClass('hidden');
                $field.find('.setting-fields-image-change, .setting-fields-image-remove').addClass('hidden');
            });
        },

        /**
         * File Fields
         */
        initFileFields: function() {
            const self = this;

            // Select/Change File
            $(document).on('click', '.setting-fields-file-select, .setting-fields-file-change', function(e) {
                e.preventDefault();
                const $field = $(this).closest('.setting-fields-file-field');
                self.openMediaFrame($field, 'file');
            });

            // Remove File
            $(document).on('click', '.setting-fields-file-remove', function(e) {
                e.preventDefault();
                const $field = $(this).closest('.setting-fields-file-field');
                $field.find('.setting-fields-file-value').val('').trigger('change');
                $field.find('.setting-fields-file-preview').addClass('hidden');
                $field.find('.setting-fields-file-name').text('').attr('href', '');
                $field.find('.setting-fields-file-select').removeClass('hidden');
                $field.find('.setting-fields-file-change, .setting-fields-file-remove').addClass('hidden');
            });
        },

        /**
         * Open Media Frame
         */
        openMediaFrame: function($field, type) {
            const self = this;
            const library = $field.data('library') || (type === 'image' ? 'image' : 'all');

            let frameOptions = {
                title: type === 'image' ? settingFieldsData.i18n.selectImage : settingFieldsData.i18n.selectFile,
                button: {
                    text: type === 'image' ? settingFieldsData.i18n.useImage : settingFieldsData.i18n.useFile
                },
                multiple: false
            };

            if (library !== 'all') {
                frameOptions.library = { type: library };
            }

            const frame = wp.media(frameOptions);

            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();

                if (type === 'image') {
                    const url = attachment.sizes && attachment.sizes.thumbnail 
                        ? attachment.sizes.thumbnail.url 
                        : attachment.url;
                    
                    $field.find('.setting-fields-image-value').val(attachment.id).trigger('change');
                    $field.find('.setting-fields-image-preview').removeClass('hidden').find('img').attr('src', url);
                    $field.find('.setting-fields-image-select').addClass('hidden');
                    $field.find('.setting-fields-image-change, .setting-fields-image-remove').removeClass('hidden');
                } else {
                    $field.find('.setting-fields-file-value').val(attachment.id).trigger('change');
                    $field.find('.setting-fields-file-preview').removeClass('hidden');
                    $field.find('.setting-fields-file-name').text(attachment.filename).attr('href', attachment.url);
                    $field.find('.setting-fields-file-select').addClass('hidden');
                    $field.find('.setting-fields-file-change, .setting-fields-file-remove').removeClass('hidden');
                }
            });

            frame.open();
        },

        /**
         * Gallery Fields
         */
        initGalleryFields: function() {
            const self = this;

            // Add Images
            $(document).on('click', '.setting-fields-gallery-add', function(e) {
                e.preventDefault();
                const $field = $(this).closest('.setting-fields-gallery-field');
                self.openGalleryFrame($field);
            });

            // Remove Image
            $(document).on('click', '.setting-fields-gallery-remove', function(e) {
                e.preventDefault();
                $(this).closest('.setting-fields-gallery-item').remove();
            });

            // Sortable
            $('.setting-fields-gallery-items').sortable({
                items: '.setting-fields-gallery-item',
                cursor: 'move',
                opacity: 0.65,
                placeholder: 'setting-fields-gallery-placeholder'
            });
        },

        openGalleryFrame: function($field) {
            const frame = wp.media({
                title: settingFieldsData.i18n.selectImages,
                button: { text: settingFieldsData.i18n.useImages },
                library: { type: 'image' },
                multiple: true
            });

            frame.on('select', function() {
                const attachments = frame.state().get('selection').toJSON();
                const $items = $field.find('.setting-fields-gallery-items');
                const name = $field.closest('td').find('input[type="hidden"]').first().attr('name').replace('[]', '');

                attachments.forEach(function(attachment) {
                    const url = attachment.sizes && attachment.sizes.thumbnail 
                        ? attachment.sizes.thumbnail.url 
                        : attachment.url;

                    const $item = $('<div class="setting-fields-gallery-item" data-id="' + attachment.id + '">' +
                        '<img src="' + url + '" alt="" />' +
                        '<input type="hidden" name="' + name + '[]" value="' + attachment.id + '" />' +
                        '<button type="button" class="setting-fields-gallery-remove">' +
                        '<span class="dashicons dashicons-no-alt"></span>' +
                        '</button></div>');

                    $items.append($item);
                });
            });

            frame.open();
        },

        /**
         * Repeater
         */
        initRepeater: function() {
            const self = this;

            // Add Row
            $(document).on('click', '.setting-fields-repeater-add', function(e) {
                e.preventDefault();
                const $repeater = $(this).closest('.setting-fields-repeater');
                self.addRepeaterRow($repeater);
            });

            // Remove Row
            $(document).on('click', '.setting-fields-repeater-remove', function(e) {
                e.preventDefault();
                if (confirm(settingFieldsData.i18n.confirmRemove)) {
                    $(this).closest('.setting-fields-repeater-row').remove();
                }
            });

            // Toggle Row
            $(document).on('click', '.setting-fields-repeater-toggle', function(e) {
                e.preventDefault();
                $(this).closest('.setting-fields-repeater-row').toggleClass('setting-fields-repeater-row--collapsed');
            });

            // Sortable
            $('.setting-fields-repeater[data-sortable="true"] .setting-fields-repeater-rows').sortable({
                handle: '.setting-fields-repeater-sort',
                items: '.setting-fields-repeater-row',
                cursor: 'move',
                opacity: 0.65,
                placeholder: 'setting-fields-repeater-placeholder',
                update: function() {
                    self.reindexRepeater($(this).closest('.setting-fields-repeater'));
                }
            });
        },

        addRepeaterRow: function($repeater) {
            const template = $repeater.find('.setting-fields-repeater-template').html();
            const $rows = $repeater.find('.setting-fields-repeater-rows');
            const newIndex = $rows.find('.setting-fields-repeater-row').length;

            const newRow = template.replace(/\{\{INDEX\}\}/g, newIndex);
            
            if ($repeater.hasClass('setting-fields-repeater--table')) {
                $rows.append(newRow);
            } else {
                $rows.append(newRow);
            }

            // Re-initialize any special fields in the new row
            const $newRow = $rows.find('.setting-fields-repeater-row').last();
            this.initRowFields($newRow);
        },

        initRowFields: function($row) {
            // Initialize Select2 in new row
            $row.find('[data-select2="true"]').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        width: '100%',
                        allowClear: $(this).data('allow-clear') === 'true',
                        placeholder: $(this).data('placeholder') || ''
                    });
                }
            });

            // Initialize Color Picker in new row
            $row.find('.setting-fields-color-picker').each(function() {
                if (!$(this).closest('.wp-picker-container').length) {
                    $(this).wpColorPicker();
                }
            });
        },

        reindexRepeater: function($repeater) {
            const name = $repeater.data('name');
            const id = $repeater.data('id');

            $repeater.find('.setting-fields-repeater-row').each(function(index) {
                $(this).attr('data-index', index);
                
                $(this).find('[name]').each(function() {
                    const currentName = $(this).attr('name');
                    const newName = currentName.replace(/\[\d+\]/, '[' + index + ']');
                    $(this).attr('name', newName);
                });

                $(this).find('[id]').each(function() {
                    const currentId = $(this).attr('id');
                    const newId = currentId.replace(/_\d+_/, '_' + index + '_');
                    $(this).attr('id', newId);
                });
            });
        },

        /**
         * Button Group
         */
        initButtonGroup: function() {
            $(document).on('change', '.setting-fields-button-group input[type="radio"]', function() {
                const $group = $(this).closest('.setting-fields-button-group');
                $group.find('label').removeClass('button-primary');
                $(this).next('label').addClass('button-primary');
            });
        },

        /**
         * Dimensions Link
         */
        initDimensions: function() {
            $(document).on('click', '.setting-fields-dimensions-link', function(e) {
                e.preventDefault();
                const $field = $(this).closest('.setting-fields-dimensions-field');
                const isLinked = $field.attr('data-linked') === 'true';
                
                $field.attr('data-linked', !isLinked ? 'true' : 'false');
                $(this).find('.dashicons')
                    .toggleClass('dashicons-admin-links', !isLinked)
                    .toggleClass('dashicons-editor-unlink', isLinked);
            });

            // Sync linked values
            $(document).on('input', '.setting-fields-dimensions-field[data-linked="true"] input[type="number"]', function() {
                const $field = $(this).closest('.setting-fields-dimensions-field');
                const value = $(this).val();
                $field.find('.setting-fields-dimensions-inputs input[type="number"]').val(value);
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        SettingFields.init();
    });

})(jQuery);
