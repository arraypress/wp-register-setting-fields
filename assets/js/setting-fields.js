/**
 * Setting Fields JavaScript
 *
 * @package ArrayPress\WP\Register\SettingFields
 */

(function ($) {
    'use strict';

    const SettingFields = {

        /**
         * Initialize all functionality
         */
        init: function () {
            this.repositionScreenMetaLinks();
            this.repositionNotices();
            this.initMobileTabs();
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
            this.initEmailEditor();
            this.initSortable();
            this.initCollapsibleGroups();
            this.initClipboard();
            this.initActionButton();
        },

        /**
         * Move any stray notices into our notices container
         */
        repositionNotices: function () {
            var $wrap = $('.setting-fields-wrap');
            var $noticesContainer = $wrap.find('.setting-fields-notices');

            if (!$noticesContainer.length) return;

            // Move any notices that appear before or after the header into our container
            $wrap.find('.notice, .updated, .error').not('.setting-fields-notices .notice, .setting-fields-notices .updated, .setting-fields-notices .error').each(function () {
                $(this).appendTo($noticesContainer);
            });

            // Also catch notices WordPress injects at wrap level
            $wrap.siblings('.notice, .updated, .error').each(function () {
                $(this).appendTo($noticesContainer);
            });
        },

        /**
         * Move screen-meta-links inside our header for proper layout
         */
        repositionScreenMetaLinks: function() {
            var $header = $('.setting-fields-header');
            var $screenMetaLinks = $('#screen-meta-links');

            if ($header.length && $screenMetaLinks.length) {
                $screenMetaLinks.css({
                    'float': 'none',
                    'position': 'absolute',
                    'right': '20px',
                    'top': '-26px',
                    'margin': '0',
                    'height': '80px',
                    'display': 'flex',
                    'align-items': 'center'
                });
                $header.css('position', 'relative');
                $header.prepend($screenMetaLinks);
            }
        },

        /**
         * Mobile Tabs Toggle
         */
        initMobileTabs: function () {
            // Toggle dropdown
            $(document).on('click', '.setting-fields-tabs-toggle', function (e) {
                e.preventDefault();
                $(this).closest('.setting-fields-header__tabs').toggleClass('is-open');
            });

            // Close when clicking outside
            $(document).on('click', function (e) {
                if (!$(e.target).closest('.setting-fields-header__tabs').length) {
                    $('.setting-fields-header__tabs').removeClass('is-open');
                }
            });

            // Close when a tab is clicked
            $(document).on('click', '.setting-fields-header__tabs .setting-fields-tab', function () {
                $('.setting-fields-header__tabs').removeClass('is-open');
            });

            // Close on escape key
            $(document).on('keydown', function (e) {
                if (e.key === 'Escape') {
                    $('.setting-fields-header__tabs').removeClass('is-open');
                }
            });
        },

        /**
         * Conditional Logic
         */
        initConditionalLogic: function () {
            const self = this;
            const $rows = $('tr[data-conditions]');

            if (!$rows.length) return;

            // Initial check
            $rows.each(function () {
                self.evaluateConditions($(this));
            });

            // Update section visibility after initial evaluation
            self.updateSectionVisibility();

            // Listen for changes
            $('.setting-fields-form').on('change', 'input, select, textarea', function () {
                $rows.each(function () {
                    self.evaluateConditions($(this));
                });

                // Update section visibility after conditions change
                self.updateSectionVisibility();
            });
        },

        evaluateConditions: function ($row) {
            const self = this;
            const conditions = $row.data('conditions');
            if (!conditions || !conditions.length) return;

            let allMet = true;

            conditions.forEach(function (condition) {
                const $field = $('[name*="[' + condition.field + ']"]');
                if (!$field.length) return;

                let currentValue = self.getFieldValue($field);
                let conditionMet = self.checkCondition(currentValue, condition.value, condition.operator);

                if (!conditionMet) {
                    allMet = false;
                }
            });

            if (allMet) {
                $row.removeClass('setting-field-hidden').show();
            } else {
                $row.addClass('setting-field-hidden').hide();
            }
        },

        /**
         * Update section visibility based on whether all child fields are hidden.
         *
         * Checks each .setting-fields-section container and hides the entire
         * section (title, description, table) if all field rows within it are
         * conditionally hidden.
         */
        updateSectionVisibility: function () {
            $('.setting-fields-section').each(function () {
                var $section = $(this);
                var $table = $section.find('table.form-table');

                if (!$table.length) return;

                // Get all field rows (exclude full-width layout rows like separators/headings
                // unless they also have conditions)
                var $allRows = $table.find('tr').not('.setting-fields-row-fullwidth');
                var $visibleRows = $allRows.filter(':not(.setting-field-hidden)');

                // Also check full-width rows that have conditions
                var $conditionalFullWidth = $table.find('tr.setting-fields-row-fullwidth[data-conditions]');
                var $visibleFullWidth = $conditionalFullWidth.filter(':not(.setting-field-hidden)');

                // Total countable rows = regular rows + conditional full-width rows
                var totalRows = $allRows.length + $conditionalFullWidth.length;
                var visibleCount = $visibleRows.length + $visibleFullWidth.length;

                if (totalRows > 0 && visibleCount === 0) {
                    $section.addClass('setting-fields-section-hidden').hide();
                } else {
                    $section.removeClass('setting-fields-section-hidden').show();
                }
            });
        },

        getFieldValue: function ($field) {
            const type = $field.attr('type');
            const tagName = $field.prop('tagName').toLowerCase();

            if (type === 'checkbox') {
                if ($field.length > 1) {
                    // Checkbox group
                    return $field.filter(':checked').map(function () {
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

        checkCondition: function (current, expected, operator) {
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
         * Clipboard Field
         */
        initClipboard: function () {
            $(document).on('click', '.setting-fields-clipboard-btn', function (e) {
                e.preventDefault();

                var $btn = $(this);
                var text = $btn.data('clipboard-text');
                var originalLabel = $btn.data('label');
                var copiedLabel = $btn.data('copied-label');
                var $btnText = $btn.find('.setting-fields-clipboard-btn-text');
                var $icon = $btn.find('.dashicons');

                // Copy to clipboard
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function () {
                        showCopied();
                    }).catch(function () {
                        fallbackCopy(text);
                    });
                } else {
                    fallbackCopy(text);
                }

                function fallbackCopy(str) {
                    var $temp = $('<textarea>');
                    $temp.css({
                        position: 'fixed',
                        left: '-9999px',
                        top: '-9999px',
                        opacity: 0
                    });
                    $('body').append($temp);
                    $temp.val(str);
                    $temp[0].focus();
                    $temp[0].select();
                    try {
                        var success = document.execCommand('copy');
                        if (success) {
                            showCopied();
                        }
                    } catch (err) {
                        // Silent fail
                    }
                    $temp.remove();
                }

                function showCopied() {
                    $btnText.text(copiedLabel);
                    $icon.removeClass('dashicons-clipboard').addClass('dashicons-yes');
                    $btn.addClass('setting-fields-clipboard-btn--copied');

                    setTimeout(function () {
                        $btnText.text(originalLabel);
                        $icon.removeClass('dashicons-yes').addClass('dashicons-clipboard');
                        $btn.removeClass('setting-fields-clipboard-btn--copied');
                    }, 2000);
                }
            });
        },

        /**
         * Action Button Field
         */
        initActionButton: function () {
            $(document).on('click', '.setting-fields-action-btn', function (e) {
                e.preventDefault();

                var $btn = $(this);
                var $wrapper = $btn.closest('.setting-fields-action-button');
                var fieldKey = $wrapper.data('field-key');
                var confirmMsg = $wrapper.data('confirm');
                var successIcon = $wrapper.data('success-icon') || 'dashicons-yes-alt';
                var errorIcon = $wrapper.data('error-icon') || 'dashicons-warning';

                // Confirmation dialog
                if (confirmMsg && !confirm(confirmMsg)) {
                    return;
                }

                // Get settings ID from closest form wrapper
                var settingsId = $wrapper.closest('.setting-fields-wrap').data('setting-id');

                // Get optional input value
                var inputValue = '';
                var $input = $wrapper.find('.setting-fields-action-input');
                if ($input.length) {
                    inputValue = $input.val();
                }

                var $btnText = $btn.find('.setting-fields-action-btn-text');
                var $icon = $btn.find('.setting-fields-action-icon');
                var $result = $wrapper.find('.setting-fields-action-result');
                var $resultIcon = $result.find('.setting-fields-action-result-icon');
                var $resultMsg = $result.find('.setting-fields-action-result-message');

                var originalLabel = $btn.data('label');
                var loadingLabel = $btn.data('loading-label');
                var originalIconClass = $icon.attr('class').replace('setting-fields-action-icon', '').replace('spin', '').trim();

                // Loading state
                $btn.prop('disabled', true);
                $btnText.text(loadingLabel);
                $icon.attr('class', 'dashicons dashicons-update setting-fields-action-icon spin');
                $result.hide();

                $.ajax({
                    url: settingFieldsData.restUrl + 'action',
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': settingFieldsData.restNonce
                    },
                    data: {
                        settings_id: settingsId,
                        field_key: fieldKey,
                        input_value: inputValue
                    },
                    success: function (response) {
                        $resultIcon.attr('class', 'dashicons ' + successIcon + ' setting-fields-action-result-icon setting-fields-action-result--success');
                        $resultMsg.text(response.message || 'Success')
                            .removeClass('setting-fields-action-result--error')
                            .addClass('setting-fields-action-result--success');
                        $result.show();
                    },
                    error: function (xhr) {
                        var message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Action failed';
                        $resultIcon.attr('class', 'dashicons ' + errorIcon + ' setting-fields-action-result-icon setting-fields-action-result--error');
                        $resultMsg.text(message)
                            .removeClass('setting-fields-action-result--success')
                            .addClass('setting-fields-action-result--error');
                        $result.show();
                    },
                    complete: function () {
                        $btn.prop('disabled', false);
                        $btnText.text(originalLabel);
                        $icon.attr('class', originalIconClass + ' setting-fields-action-icon');
                    }
                });
            });
        },

        /**
         * Select2 Initialization
         */
        initSelect2: function () {
            var self = this;

            $('.setting-fields-select2').each(function () {
                var $select = $(this);

                // Skip if already initialized
                if ($select.hasClass('select2-hidden-accessible')) {
                    return;
                }

                self.initSingleSelect2($select);
            });
        },

        /**
         * Initialize a single Select2 field
         */
        initSingleSelect2: function ($select) {
            var self = this;

            var options = {
                width: '100%',
                allowClear: $select.data('allow-clear') === 'true' || $select.data('allow-clear') === true,
                placeholder: $select.data('placeholder') || ''
            };

            // AJAX configuration using REST API
            if ($select.data('ajax') === 'true' || $select.data('ajax') === true) {
                options.ajax = {
                    url: settingFieldsData.restUrl + 'ajax',
                    dataType: 'json',
                    delay: 250,
                    headers: {
                        'X-WP-Nonce': settingFieldsData.restNonce
                    },
                    data: function (params) {
                        var data = {
                            settings_id: settingFieldsData.settingsId,
                            field_key: $select.data('field-key'),
                            field_type: $select.data('field-type') || 'ajax',
                            search: params.term || ''
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
                    processResults: function (data) {
                        var results = (data.results || data || []).map(function (item) {
                            return {
                                id: item.value,
                                text: item.label
                            };
                        });

                        return {results: results};
                    },
                    cache: true
                };
                options.minimumInputLength = 0;
            }

            // Tags support
            if ($select.data('tags') === 'true' || $select.data('tags') === true) {
                options.tags = true;
            }

            // Maximum selection
            if ($select.data('maximum-selection-length')) {
                options.maximumSelectionLength = parseInt($select.data('maximum-selection-length'));
            }

            $select.select2(options);

            // Hydrate existing values for AJAX selects
            if ($select.data('ajax') === 'true' || $select.data('ajax') === true) {
                var currentValues = $select.val();
                if (currentValues && currentValues.length) {
                    var ids = Array.isArray(currentValues) ? currentValues : [currentValues];
                    ids = ids.filter(function (id) {
                        return id && id !== '';
                    });

                    if (ids.length > 0) {
                        this.hydrateSelect2($select, ids);
                    }
                }
            }
        },

        /**
         * Hydrate Select2 with labels for existing values
         */
        hydrateSelect2: function ($select, ids) {
            var data = {
                settings_id: settingFieldsData.settingsId,
                field_key: $select.data('field-key'),
                field_type: $select.data('field-type') || 'ajax',
                include: ids.join(',')
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

            $.ajax({
                url: settingFieldsData.restUrl + 'ajax',
                data: data,
                headers: {
                    'X-WP-Nonce': settingFieldsData.restNonce
                }
            }).done(function (response) {
                var results = response.results || response;

                $select.empty();

                results.forEach(function (item) {
                    var option = new Option(item.label, item.value, true, true);
                    $select.append(option);
                });

                $select.trigger('change.select2');
            });
        },

        /**
         * Color Picker
         */
        initColorPicker: function () {
            $('.setting-fields-color-picker').each(function () {
                const $input = $(this);
                const options = {
                    defaultColor: $input.data('default-color') || false,
                    change: function (event, ui) {
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
        initCodeEditor: function () {
            if (typeof wp.codeEditor === 'undefined') return;

            $('.setting-fields-code-editor').each(function () {
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
        initRangeSlider: function () {
            var updateRangeProgress = function ($input) {
                var min = parseFloat($input.attr('min')) || 0;
                var max = parseFloat($input.attr('max')) || 100;
                var val = parseFloat($input.val()) || 0;
                var progress = ((val - min) / (max - min)) * 100;
                $input.css('--range-progress', progress + '%');
            };

            // Initialize all range sliders
            $('.setting-fields-range').each(function () {
                updateRangeProgress($(this));
            });

            // Update on input
            $('.setting-fields-range').on('input', function () {
                var $input = $(this);
                var $value = $('.setting-fields-range-value[data-target="' + $input.attr('id') + '"]');
                $value.text($input.val());
                updateRangeProgress($input);
            });
        },

        /**
         * Image Fields
         */
        initImageFields: function () {
            const self = this;

            // Select/Change Image
            $(document).on('click', '.setting-fields-image-select, .setting-fields-image-change', function (e) {
                e.preventDefault();
                const $field = $(this).closest('.setting-fields-image-field');
                self.openMediaFrame($field, 'image');
            });

            // Remove Image
            $(document).on('click', '.setting-fields-image-remove', function (e) {
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
        initFileFields: function () {
            const self = this;

            // Select/Change File
            $(document).on('click', '.setting-fields-file-select, .setting-fields-file-change', function (e) {
                e.preventDefault();
                const $field = $(this).closest('.setting-fields-file-field');
                self.openMediaFrame($field, 'file');
            });

            // Remove File
            $(document).on('click', '.setting-fields-file-remove', function (e) {
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
        openMediaFrame: function ($field, type) {
            const self = this;
            const library = $field.data('library') || (type === 'image' ? 'image' : '');

            let frameOptions = {
                title: type === 'image' ? settingFieldsData.i18n.selectImage : settingFieldsData.i18n.selectFile,
                button: {
                    text: type === 'image' ? settingFieldsData.i18n.useImage : settingFieldsData.i18n.useFile
                },
                multiple: false,
                library: {}
            };

            // Set library type filter
            if (library && library !== 'all') {
                frameOptions.library.type = library;
            }

            const frame = wp.media(frameOptions);

            // Force the library to filter when opened
            frame.on('open', function () {
                if (library && library !== 'all') {
                    frame.state().get('library').props.set({type: library});
                }
            });

            frame.on('select', function () {
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
        initGalleryFields: function () {
            const self = this;

            // Add Images
            $(document).on('click', '.setting-fields-gallery-add', function (e) {
                e.preventDefault();
                const $field = $(this).closest('.setting-fields-gallery-field');
                self.openGalleryFrame($field);
            });

            // Remove Image
            $(document).on('click', '.setting-fields-gallery-remove', function (e) {
                e.preventDefault();
                $(this).closest('.setting-fields-gallery-item').remove();
            });

            // Sortable
            if ($.fn.sortable) {
                $('.setting-fields-gallery-items').sortable({
                    items: '.setting-fields-gallery-item',
                    cursor: 'move',
                    opacity: 0.65,
                    placeholder: 'setting-fields-gallery-placeholder'
                });
            }
        },

        openGalleryFrame: function ($field) {
            const library = $field.data('library') || 'image';

            const frame = wp.media({
                title: settingFieldsData.i18n.selectImages,
                button: {text: settingFieldsData.i18n.useImages},
                library: {type: library},
                multiple: true
            });

            // Force the library to filter when opened
            frame.on('open', function () {
                frame.state().get('library').props.set({type: library});
            });

            frame.on('select', function () {
                const attachments = frame.state().get('selection').toJSON();
                const $items = $field.find('.setting-fields-gallery-items');
                const name = $field.data('name');

                attachments.forEach(function (attachment) {
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
        initRepeater: function () {
            const self = this;

            // Add Row
            $(document).on('click', '.setting-fields-repeater-add', function (e) {
                e.preventDefault();
                const $repeater = $(this).closest('.setting-fields-repeater');
                self.addRepeaterRow($repeater);
            });

            // Remove Row
            $(document).on('click', '.setting-fields-repeater-remove', function (e) {
                e.preventDefault();
                if (confirm(settingFieldsData.i18n.confirmRemove)) {
                    var $row = $(this).closest('.setting-fields-repeater-row');
                    var $repeater = $row.closest('.setting-fields-repeater');

                    $row.remove();

                    // Show empty state if no rows left
                    var $rows = $repeater.find('.setting-fields-repeater-rows');
                    if ($rows.find('.setting-fields-repeater-row').length === 0) {
                        $rows.find('.setting-fields-repeater-empty').show();
                    }
                }
            });

            // Toggle Row - click on header (but not on buttons/inputs inside it)
            $(document).on('click', '.setting-fields-repeater-row-header', function (e) {
                // Don't toggle if clicking on remove button, sort handle, or other interactive elements
                if ($(e.target).closest('.setting-fields-repeater-remove, .setting-fields-repeater-sort, button, input, select, textarea').length) {
                    return;
                }

                e.preventDefault();
                var $row = $(this).closest('.setting-fields-repeater-row');
                var $icon = $(this).find('.setting-fields-repeater-toggle .dashicons');

                $row.toggleClass('setting-fields-repeater-row--collapsed');

                // Update icon
                if ($row.hasClass('setting-fields-repeater-row--collapsed')) {
                    $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
                } else {
                    $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
                }
            });

            // Sortable
            if ($.fn.sortable) {
                $('.setting-fields-repeater[data-sortable="true"] .setting-fields-repeater-rows').sortable({
                    handle: '.setting-fields-repeater-sort',
                    items: '.setting-fields-repeater-row',
                    cursor: 'move',
                    opacity: 0.65,
                    placeholder: 'setting-fields-repeater-placeholder',
                    update: function () {
                        self.reindexRepeater($(this).closest('.setting-fields-repeater'));
                    }
                });
            }
        },

        addRepeaterRow: function ($repeater) {
            const template = $repeater.find('.setting-fields-repeater-template').html();
            const $rows = $repeater.find('.setting-fields-repeater-rows');
            const newIndex = $rows.find('.setting-fields-repeater-row').length;

            // Hide empty state row if present
            $rows.find('.setting-fields-repeater-empty').hide();

            const newRow = template.replace(/\{\{INDEX\}\}/g, newIndex);
            $rows.append(newRow);

            // Re-initialize any special fields in the new row
            const $newRow = $rows.find('.setting-fields-repeater-row').last();
            this.initRowFields($newRow);
        },

        initRowFields: function ($row) {
            var self = this;

            // Initialize Select2 in new row
            $row.find('.setting-fields-select2').each(function () {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    self.initSingleSelect2($(this));
                }
            });

            // Initialize Color Picker in new row
            $row.find('.setting-fields-color-picker').each(function () {
                if (!$(this).closest('.wp-picker-container').length) {
                    $(this).wpColorPicker();
                }
            });
        },

        reindexRepeater: function ($repeater) {
            const name = $repeater.data('name');
            const id = $repeater.data('id');

            $repeater.find('.setting-fields-repeater-row').each(function (index) {
                $(this).attr('data-index', index);

                $(this).find('[name]').each(function () {
                    const currentName = $(this).attr('name');
                    const newName = currentName.replace(/\[\d+\]/, '[' + index + ']');
                    $(this).attr('name', newName);
                });

                $(this).find('[id]').each(function () {
                    const currentId = $(this).attr('id');
                    const newId = currentId.replace(/_\d+_/, '_' + index + '_');
                    $(this).attr('id', newId);
                });
            });
        },

        /**
         * Button Group
         */
        initButtonGroup: function () {
            // Handle radio change
            $(document).on('change', '.setting-fields-button-group input[type="radio"]', function () {
                const $group = $(this).closest('.setting-fields-button-group');
                $group.find('label').removeClass('button-primary');
                $group.find('input:checked').each(function () {
                    $(this).next('label').addClass('button-primary');
                });
            });

            // Handle label click as backup
            $(document).on('click', '.setting-fields-button-group label', function (e) {
                const $label = $(this);
                const $input = $label.prev('input[type="radio"]');

                if ($input.length && !$input.prop('checked')) {
                    $input.prop('checked', true).trigger('change');
                }
            });
        },

        /**
         * Dimensions Link
         */
        initDimensions: function () {
            $(document).on('click', '.setting-fields-dimensions-link', function (e) {
                e.preventDefault();
                const $field = $(this).closest('.setting-fields-dimensions-field');
                const isLinked = $field.attr('data-linked') === 'true';

                $field.attr('data-linked', !isLinked ? 'true' : 'false');
                $(this).find('.dashicons')
                    .toggleClass('dashicons-admin-links', !isLinked)
                    .toggleClass('dashicons-editor-unlink', isLinked);
            });

            // Sync linked values
            $(document).on('input', '.setting-fields-dimensions-field[data-linked="true"] input[type="number"]', function () {
                const $field = $(this).closest('.setting-fields-dimensions-field');
                const value = $(this).val();
                $field.find('.setting-fields-dimensions-inputs input[type="number"]').val(value);
            });
        },

        /**
         * Email Editor - Merge Tags Modal & Callbacks
         */
        initEmailEditor: function () {
            var self = this;

            // Configure button toggle (collapsible email editor)
            $(document).on('click', '.setting-fields-email-configure', function (e) {
                e.preventDefault();

                var $editor = $(this).closest('.setting-fields-email-editor');
                var $icon = $(this).find('.dashicons');

                $editor.toggleClass('setting-fields-email-editor--collapsed');

                if ($editor.hasClass('setting-fields-email-editor--collapsed')) {
                    $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
                } else {
                    $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
                }
            });

            // Enable/disable toggle
            $(document).on('change', '.setting-fields-email-enable-checkbox', function () {
                var $editor = $(this).closest('.setting-fields-email-editor');
                var $content = $editor.find('.setting-fields-email-content');

                if ($(this).is(':checked')) {
                    $content.removeClass('setting-fields-email-disabled');
                } else {
                    $content.addClass('setting-fields-email-disabled');
                }
            });

            // Open merge tags modal
            $(document).on('click', '.setting-fields-insert-tag-btn', function (e) {
                e.preventDefault();
                e.stopPropagation();

                var $btn = $(this);
                var target = $btn.data('target'); // 'subject' or 'body'
                var editorId = $btn.data('editor-id'); // May be set on body button

                // Find the editor container - try data attribute first, then DOM traversal
                var $editor;
                if (editorId) {
                    $editor = $('.setting-fields-email-editor[data-field-id="' + editorId + '"]');
                }

                if (!$editor || !$editor.length) {
                    $editor = $btn.closest('.setting-fields-email-editor');
                }

                if (!$editor || !$editor.length) {
                    return;
                }

                var $modal = $editor.find('.setting-fields-merge-tags-modal');

                if (!$modal.length) {
                    return;
                }

                $modal.data('insert-target', target);
                $modal.show();
                $modal.find('.setting-fields-tag-search').val('').focus();
                $modal.find('.setting-fields-tag-item').removeClass('hidden');
            });

            // Close modal
            $(document).on('click', '.setting-fields-modal-close, .setting-fields-modal-overlay', function (e) {
                e.preventDefault();
                $(this).closest('.setting-fields-merge-tags-modal').hide();
            });

            // Close modal on escape
            $(document).on('keydown', function (e) {
                if (e.key === 'Escape') {
                    $('.setting-fields-merge-tags-modal').hide();
                }
            });

            // Search tags
            $(document).on('input', '.setting-fields-tag-search', function () {
                var query = $(this).val().toLowerCase();
                var $modal = $(this).closest('.setting-fields-merge-tags-modal');

                $modal.find('.setting-fields-tag-item').each(function () {
                    var tag = $(this).data('tag').toLowerCase();
                    var label = $(this).data('label').toLowerCase();
                    var desc = $(this).find('.setting-fields-tag-desc').text().toLowerCase();

                    if (tag.indexOf(query) > -1 || label.indexOf(query) > -1 || desc.indexOf(query) > -1) {
                        $(this).removeClass('hidden');
                    } else {
                        $(this).addClass('hidden');
                    }
                });
            });

            // Insert tag from modal
            $(document).on('click', '.setting-fields-tag-item', function (e) {
                e.preventDefault();

                var tag = $(this).data('tag');
                var $modal = $(this).closest('.setting-fields-merge-tags-modal');
                var $editor = $modal.closest('.setting-fields-email-editor');
                var target = $modal.data('insert-target');
                var fieldId = $editor.data('field-id');

                if (target === 'subject') {
                    // Insert into subject input
                    var $input = $editor.find('.setting-fields-email-subject-input');
                    self.insertAtCursor($input[0], tag);
                } else {
                    // Insert into TinyMCE editor
                    var editorId = fieldId + '_body';

                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get(editorId)) {
                        var editor = tinyMCE.get(editorId);
                        if (!editor.isHidden()) {
                            editor.execCommand('mceInsertContent', false, tag);
                        } else {
                            self.insertAtCursor($('#' + editorId)[0], tag);
                        }
                    } else {
                        self.insertAtCursor($('#' + editorId)[0], tag);
                    }
                }

                $modal.hide();
            });

            // Preview button - uses REST endpoint
            $(document).on('click', '.setting-fields-email-preview', function (e) {
                e.preventDefault();

                var $editor = $(this).closest('.setting-fields-email-editor');
                var fieldKey = $editor.data('field-key');
                var fieldId = $editor.data('field-id');
                var settingsId = $editor.closest('.setting-fields-wrap').data('setting-id');

                // Get current values
                var subject = $editor.find('.setting-fields-email-subject-input').val();
                var editorId = fieldId + '_body';
                var body = '';

                if (typeof tinyMCE !== 'undefined' && tinyMCE.get(editorId)) {
                    body = tinyMCE.get(editorId).getContent();
                } else {
                    body = $('#' + editorId).val();
                }

                var $btn = $(this);
                $btn.prop('disabled', true);

                $.ajax({
                    url: settingFieldsData.restUrl + 'email/preview',
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': settingFieldsData.restNonce
                    },
                    data: {
                        settings_id: settingsId,
                        field_key: fieldKey,
                        subject: subject,
                        body: body
                    },
                    success: function (response) {
                        if (response.html) {
                            self.openPreviewWindow(response.html);
                        } else {
                            alert('Preview failed: No HTML returned');
                        }
                    },
                    error: function (xhr) {
                        var message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Preview request failed';
                        alert(message);
                    },
                    complete: function () {
                        $btn.prop('disabled', false);
                    }
                });
            });

            // Send test email button - uses REST endpoint
            $(document).on('click', '.setting-fields-email-send-test', function (e) {
                e.preventDefault();

                var $editor = $(this).closest('.setting-fields-email-editor');
                var fieldKey = $editor.data('field-key');
                var fieldId = $editor.data('field-id');
                var settingsId = $editor.closest('.setting-fields-wrap').data('setting-id');

                // Get email from inline input
                var $emailInput = $editor.find('.setting-fields-email-test-input');
                var email = $emailInput.val();

                if (!email || !email.includes('@')) {
                    $emailInput.focus();
                    alert('Please enter a valid email address');
                    return;
                }

                // Get current values
                var subject = $editor.find('.setting-fields-email-subject-input').val();
                var editorId = fieldId + '_body';
                var body = '';

                if (typeof tinyMCE !== 'undefined' && tinyMCE.get(editorId)) {
                    body = tinyMCE.get(editorId).getContent();
                } else {
                    body = $('#' + editorId).val();
                }

                var $btn = $(this);
                var originalHtml = $btn.html();
                $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Sending...');

                $.ajax({
                    url: settingFieldsData.restUrl + 'email/send-test',
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': settingFieldsData.restNonce
                    },
                    data: {
                        settings_id: settingsId,
                        field_key: fieldKey,
                        email: email,
                        subject: subject,
                        body: body
                    },
                    success: function (response) {
                        alert(response.message || 'Test email sent successfully!');
                    },
                    error: function (xhr) {
                        var message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Failed to send test email';
                        alert(message);
                    },
                    complete: function () {
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });
        },

        /**
         * Insert text at cursor position in input/textarea
         */
        insertAtCursor: function (element, text) {
            if (!element) return;

            var startPos = element.selectionStart || 0;
            var endPos = element.selectionEnd || 0;
            var value = element.value || '';

            element.value = value.substring(0, startPos) + text + value.substring(endPos);
            element.selectionStart = element.selectionEnd = startPos + text.length;
            element.focus();

            // Trigger change event
            $(element).trigger('change');
        },

        /**
         * Open preview in new window
         */
        openPreviewWindow: function (html) {
            var win = window.open('', 'email_preview', 'width=700,height=600,scrollbars=yes');
            win.document.write(html);
            win.document.close();
        },

        /**
         * Escape HTML for safe display
         */
        escapeHtml: function (text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * Sortable Field
         */
        initSortable: function () {
            var $sortables = $('.setting-fields-sortable-list');

            if (!$sortables.length) return;

            // Initialize jQuery UI sortable
            if ($.fn.sortable) {
                $sortables.sortable({
                    handle: '.setting-fields-sortable-handle',
                    placeholder: 'setting-fields-sortable-placeholder',
                    update: function (event, ui) {
                        // Reorder hidden inputs to match visual order
                        var $list = $(this);
                        $list.find('.setting-fields-sortable-item').each(function (index) {
                            var $input = $(this).find('input[type="hidden"]');
                            $input.attr('name', $input.attr('name')); // Force refresh
                        });
                    }
                });
            }

            // Toggle item active state
            $(document).on('click', '.setting-fields-sortable-toggle', function (e) {
                e.preventDefault();

                var $item = $(this).closest('.setting-fields-sortable-item');
                var $input = $item.find('input[type="hidden"]');
                var $icon = $(this).find('.dashicons');

                if ($item.hasClass('setting-fields-sortable-item--active')) {
                    // Deactivate
                    $item.removeClass('setting-fields-sortable-item--active');
                    $input.prop('disabled', true);
                    $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                    $(this).attr('title', settingFieldsData.i18n?.enable || 'Enable');
                } else {
                    // Activate
                    $item.addClass('setting-fields-sortable-item--active');
                    $input.prop('disabled', false);
                    $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                    $(this).attr('title', settingFieldsData.i18n?.disable || 'Disable');
                }
            });
        },

        /**
         * Collapsible Groups
         */
        initCollapsibleGroups: function () {
            // Toggle group collapse
            $(document).on('click', '.setting-fields-group-toggle', function (e) {
                e.preventDefault();

                var $group = $(this).closest('.setting-fields-group');
                var $icon = $(this).find('.dashicons');

                $group.toggleClass('setting-fields-group--collapsed');

                if ($group.hasClass('setting-fields-group--collapsed')) {
                    $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
                } else {
                    $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
                }
            });

            // Also allow clicking on header (but not toggle button which handles itself)
            $(document).on('click', '.setting-fields-group-header', function (e) {
                if (!$(e.target).closest('.setting-fields-group-toggle').length) {
                    $(this).find('.setting-fields-group-toggle').trigger('click');
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function () {
        SettingFields.init();
    });

})(jQuery);