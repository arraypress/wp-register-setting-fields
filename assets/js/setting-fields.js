/**
 * Setting Fields JavaScript
 *
 * Handles all interactive functionality for the WordPress settings framework
 * including conditional logic, media uploads, repeaters, email editor,
 * reset, export/import, and various field type initializations.
 *
 * @package ArrayPress\WP\Register\SettingFields
 */

(function ($) {
    'use strict';

    const SettingFields = {

        /**
         * Initialize all field functionality.
         *
         * Entry point called on document ready. Initializes each
         * subsystem in the correct order.
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
            this.initLicense();
            this.initActionButton();
            this.initReset();
            this.initExportImport();
        },

        /**
         * Reposition WordPress admin notices into the settings notice container.
         *
         * Moves any stray .notice, .updated, or .error elements that WordPress
         * injects outside our container into the designated notices area.
         */
        repositionNotices: function () {
            const $wrap = $('.setting-fields-wrap');
            const $noticesContainer = $wrap.find('.setting-fields-notices');

            if (!$noticesContainer.length) return;

            $wrap.find('.notice, .updated, .error').not('.setting-fields-notices .notice, .setting-fields-notices .updated, .setting-fields-notices .error').each(function () {
                $(this).appendTo($noticesContainer);
            });

            $wrap.siblings('.notice, .updated, .error').each(function () {
                $(this).appendTo($noticesContainer);
            });
        },

        /**
         * Reposition the screen-meta-links (Help/Screen Options) into the header.
         *
         * Moves the WordPress screen-meta-links element inside our custom header
         * and positions it absolutely so it aligns with the header layout.
         */
        repositionScreenMetaLinks: function () {
            const $header = $('.setting-fields-header');
            const $screenMetaLinks = $('#screen-meta-links');

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
         * Initialize mobile tab navigation.
         *
         * Adds toggle behavior for the responsive tab dropdown,
         * closes on outside click, tab selection, and escape key.
         */
        initMobileTabs: function () {
            $(document).on('click', '.setting-fields-tabs-toggle', function (e) {
                e.preventDefault();
                $(this).closest('.setting-fields-header__tabs').toggleClass('is-open');
            });

            $(document).on('click', function (e) {
                if (!$(e.target).closest('.setting-fields-header__tabs').length) {
                    $('.setting-fields-header__tabs').removeClass('is-open');
                }
            });

            $(document).on('click', '.setting-fields-header__tabs .setting-fields-tab', function () {
                $('.setting-fields-header__tabs').removeClass('is-open');
            });

            $(document).on('keydown', function (e) {
                if (e.key === 'Escape') {
                    $('.setting-fields-header__tabs').removeClass('is-open');
                }
            });
        },

        /**
         * Initialize conditional logic for field visibility.
         *
         * Evaluates data-conditions on field rows to show/hide them
         * based on other field values. Re-evaluates on any form change
         * and updates section visibility accordingly.
         */
        initConditionalLogic: function () {
            const self = this;
            const $rows = $('tr[data-conditions]');

            if (!$rows.length) return;

            $rows.each(function () {
                self.evaluateConditions($(this));
            });

            self.updateSectionVisibility();

            $('.setting-fields-form').on('change', 'input, select, textarea', function () {
                $rows.each(function () {
                    self.evaluateConditions($(this));
                });
                self.updateSectionVisibility();
            });
        },

        /**
         * Evaluate visibility conditions for a single field row.
         *
         * Parses the data-conditions attribute and checks each condition
         * against current form values. All conditions must be met (AND logic).
         *
         * @param {jQuery} $row The table row element with data-conditions.
         */
        evaluateConditions: function ($row) {
            const self = this;
            const conditions = $row.data('conditions');

            if (!conditions || !conditions.length) return;

            let allMet = true;

            conditions.forEach(function (condition) {
                const $field = $('[name*="[' + condition.field + ']"]');
                if (!$field.length) return;

                const currentValue = self.getFieldValue($field);
                const conditionMet = self.checkCondition(currentValue, condition.value, condition.operator);

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
         * Update section visibility based on child field visibility.
         *
         * Checks each .setting-fields-section container and hides the entire
         * section (title, description, table) if all field rows within it are
         * conditionally hidden.
         */
        updateSectionVisibility: function () {
            $('.setting-fields-section').each(function () {
                const $section = $(this);
                const $table = $section.find('table.form-table');

                if (!$table.length) return;

                const $allRows = $table.find('tr').not('.setting-fields-row-fullwidth');
                const $visibleRows = $allRows.filter(':not(.setting-field-hidden)');
                const $conditionalFullWidth = $table.find('tr.setting-fields-row-fullwidth[data-conditions]');
                const $visibleFullWidth = $conditionalFullWidth.filter(':not(.setting-field-hidden)');
                const totalRows = $allRows.length + $conditionalFullWidth.length;
                const visibleCount = $visibleRows.length + $visibleFullWidth.length;

                if (totalRows > 0 && visibleCount === 0) {
                    $section.addClass('setting-fields-section-hidden').hide();
                } else {
                    $section.removeClass('setting-fields-section-hidden').show();
                }
            });
        },

        /**
         * Get the current value of a form field.
         *
         * Handles checkboxes (single and group), radio buttons,
         * multi-selects, and standard inputs.
         *
         * @param {jQuery} $field The form field element(s).
         * @returns {string|Array} The current field value.
         */
        getFieldValue: function ($field) {
            const type = $field.attr('type');
            const tagName = $field.prop('tagName').toLowerCase();

            if (type === 'checkbox') {
                if ($field.length > 1) {
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

        /**
         * Check if a condition is met between current and expected values.
         *
         * Supports operators: =, ==, ===, !=, !==, >, >=, <, <=,
         * in, not_in, contains, not_contains, empty, not_empty.
         *
         * @param {*}      current  The current field value.
         * @param {*}      expected The expected value from the condition.
         * @param {string} operator The comparison operator.
         * @returns {boolean} Whether the condition is met.
         */
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
         * Initialize clipboard copy buttons.
         *
         * Handles click-to-copy functionality with visual feedback,
         * using the Clipboard API with a fallback for older browsers.
         */
        initClipboard: function () {
            $(document).on('click', '.setting-fields-clipboard-btn', function (e) {
                e.preventDefault();

                const $btn = $(this);
                const text = $btn.data('clipboard-text');
                const originalLabel = $btn.data('label');
                const copiedLabel = $btn.data('copied-label');
                const $btnText = $btn.find('.setting-fields-clipboard-btn-text');
                const $icon = $btn.find('.dashicons');

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function () {
                        showCopied();
                    }).catch(function () {
                        fallbackCopy(text);
                    });
                } else {
                    fallbackCopy(text);
                }

                /**
                 * Fallback copy using a temporary textarea and execCommand.
                 *
                 * @param {string} str The text to copy.
                 */
                function fallbackCopy(str) {
                    const $temp = $('<textarea>');
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
                        const success = document.execCommand('copy');
                        if (success) {
                            showCopied();
                        }
                    } catch (err) {
                        // Silent fail
                    }
                    $temp.remove();
                }

                /**
                 * Show copied state with icon change and auto-reset after 2 seconds.
                 */
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
         * Initialize license key fields.
         *
         * Handles activate/deactivate button clicks via the dedicated
         * REST license endpoint. Updates status badge, expiry display,
         * action URL, input state, hidden inputs, and button inline
         * without page reload.
         */
        initLicense: function () {
            $(document).on('click', '.setting-fields-license-btn', function (e) {
                e.preventDefault();

                const $btn = $(this);
                const $container = $btn.closest('.setting-fields-license');
                const action = $btn.data('action');
                const fieldKey = $container.data('field-key');
                const $input = $container.find('.setting-fields-license-key');
                const $result = $container.find('.setting-fields-license-result');
                const $resultIcon = $result.find('.setting-fields-license-result-icon');
                const $resultMsg = $result.find('.setting-fields-license-result-message');
                const key = $input.val();

                // Require a key for activation
                if (action === 'activate' && !key.trim()) {
                    $resultIcon.attr('class', 'dashicons dashicons-warning setting-fields-license-result-icon');
                    $resultMsg.text(settingFieldsData.i18n.licenseKeyRequired || 'Please enter a license key.');
                    $result.removeClass('setting-fields-license-result--success').addClass('setting-fields-license-result--error').show();
                    return;
                }

                const settingsId = $container.closest('.setting-fields-wrap').data('setting-id');
                const originalLabel = $btn.data('label');
                const loadingLabel = $btn.data('loading-label');

                // Loading state
                $btn.prop('disabled', true).text(loadingLabel);
                $result.hide();

                $.ajax({
                    url: settingFieldsData.restUrl + 'license',
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': settingFieldsData.restNonce
                    },
                    data: {
                        settings_id: settingsId,
                        field_key: fieldKey,
                        key: key,
                        action: action
                    },
                    success: function (response) {
                        const success = response.success !== false;
                        const message = response.message || '';

                        // Show result message
                        if (success) {
                            $resultIcon.attr('class', 'dashicons dashicons-yes-alt setting-fields-license-result-icon');
                            $result.removeClass('setting-fields-license-result--error').addClass('setting-fields-license-result--success');
                        } else {
                            $resultIcon.attr('class', 'dashicons dashicons-warning setting-fields-license-result-icon');
                            $result.removeClass('setting-fields-license-result--success').addClass('setting-fields-license-result--error');
                        }
                        $resultMsg.text(message);
                        $result.show();

                        // Update status if returned
                        if (response.status) {
                            updateLicenseStatus($container, response.status, response.expiry || '');
                        }

                        // Update URL if returned
                        if (response.url !== undefined && response.url !== null) {
                            updateLicenseUrl($container, response.status || $container.attr('data-status'), response.url, response.url_label || '');
                        } else {
                            // Re-evaluate URL visibility based on new status
                            updateLicenseUrl($container, response.status || $container.attr('data-status'));
                        }
                    },
                    error: function (xhr) {
                        const message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : (settingFieldsData.i18n.errorLoading || 'An error occurred.');
                        $resultIcon.attr('class', 'dashicons dashicons-warning setting-fields-license-result-icon');
                        $resultMsg.text(message);
                        $result.removeClass('setting-fields-license-result--success').addClass('setting-fields-license-result--error').show();
                    },
                    complete: function () {
                        $btn.prop('disabled', false).text(originalLabel);
                    }
                });
            });

            /**
             * Update the license field status, badge, expiry, input state, and button.
             *
             * @param {jQuery} $container The license field container.
             * @param {string} status     New status: active, inactive, expired, invalid.
             * @param {string} expiry     New expiry date string.
             */
            function updateLicenseStatus($container, status, expiry) {
                const statusLabels = {
                    inactive: settingFieldsData.i18n.licenseInactive || 'Inactive',
                    active: settingFieldsData.i18n.licenseActive || 'Active',
                    expired: settingFieldsData.i18n.licenseExpired || 'Expired',
                    invalid: settingFieldsData.i18n.licenseInvalid || 'Invalid'
                };

                const isActive = status === 'active';

                // Update data attribute
                $container.attr('data-status', status);

                // Update badge
                const $badge = $container.find('.setting-fields-license-badge');
                $badge
                    .removeClass('setting-fields-license-badge--inactive setting-fields-license-badge--active setting-fields-license-badge--expired setting-fields-license-badge--invalid')
                    .addClass('setting-fields-license-badge--' + status);
                $badge.find('.setting-fields-license-badge-text').text(statusLabels[status] || status);

                // Update expiry
                const $expiry = $container.find('.setting-fields-license-expiry');
                if (expiry && (status === 'active' || status === 'expired')) {
                    const expiryText = (settingFieldsData.i18n.licenseExpires || 'Expires: ') + expiry;
                    if ($expiry.length) {
                        $expiry.text(expiryText).show();
                    } else {
                        $container.find('.setting-fields-license-status-row').append(
                            '<span class="setting-fields-license-expiry">' + expiryText + '</span>'
                        );
                    }
                } else {
                    $expiry.hide();
                }

                // Update hidden inputs
                $container.find('.setting-fields-license-status-input').val(status);
                $container.find('.setting-fields-license-expiry-input').val(expiry);

                // Update input readonly state
                $container.find('.setting-fields-license-key').prop('readonly', isActive);

                // Replace button
                const activateLabel = $container.data('activate-label');
                const deactivateLabel = $container.data('deactivate-label');
                const activateLoading = $container.data('activate-loading');
                const deactivateLoading = $container.data('deactivate-loading');

                const $oldBtn = $container.find('.setting-fields-license-btn');
                const btnClass = isActive
                    ? 'button setting-fields-license-btn'
                    : 'button button-primary setting-fields-license-btn';
                const btnLabel = isActive ? deactivateLabel : activateLabel;
                const btnLoading = isActive ? deactivateLoading : activateLoading;
                const btnAction = isActive ? 'deactivate' : 'activate';

                const $newBtn = $('<button type="button"></button>')
                    .attr('class', btnClass)
                    .attr('data-action', btnAction)
                    .attr('data-label', btnLabel)
                    .attr('data-loading-label', btnLoading)
                    .text(btnLabel);

                $oldBtn.replaceWith($newBtn);
            }

            /**
             * Update the action URL visibility, href, and label.
             *
             * Shows the link only when status is expired or invalid.
             * If a dynamic URL is provided by the callback response, it
             * overrides the static config URL.
             *
             * @param {jQuery} $container The license field container.
             * @param {string} status     Current status.
             * @param {string} url        Optional dynamic URL override.
             * @param {string} urlLabel   Optional dynamic label override.
             */
            function updateLicenseUrl($container, status, url, urlLabel) {
                const $statusRow = $container.find('.setting-fields-license-status-row');
                let $link = $container.find('.setting-fields-license-url');
                const showUrl = (status === 'expired' || status === 'invalid');

                // Determine the URL to use: dynamic override > static config
                const finalUrl = url || $container.data('url') || '';
                const finalLabel = urlLabel || $container.data('url-label') || '';

                if (showUrl && finalUrl) {
                    if ($link.length) {
                        // Update existing link
                        $link.attr('href', finalUrl).show();
                        if (finalLabel) {
                            $link.contents().first().replaceWith(finalLabel);
                        }
                    } else {
                        // Create new link
                        $link = $('<a></a>')
                            .attr('class', 'setting-fields-license-url')
                            .attr('href', finalUrl)
                            .attr('target', '_blank')
                            .attr('rel', 'noopener noreferrer')
                            .html(finalLabel + ' <span class="dashicons dashicons-external"></span>');
                        $statusRow.append($link);
                    }
                } else {
                    $link.hide();
                }
            }
        },

        /**
         * Initialize action button fields.
         *
         * Handles click events on action buttons that trigger server-side
         * callbacks via REST API. Shows loading state, confirmation dialogs,
         * and displays success/error results.
         */
        initActionButton: function () {
            $(document).on('click', '.setting-fields-action-btn', function (e) {
                e.preventDefault();

                const $btn = $(this);
                const $wrapper = $btn.closest('.setting-fields-action-button');
                const fieldKey = $wrapper.data('field-key');
                const confirmMsg = $wrapper.data('confirm');
                const successIcon = $wrapper.data('success-icon') || 'dashicons-yes-alt';
                const errorIcon = $wrapper.data('error-icon') || 'dashicons-warning';

                if (confirmMsg && !confirm(confirmMsg)) {
                    return;
                }

                const settingsId = $wrapper.closest('.setting-fields-wrap').data('setting-id');

                let inputValue = '';
                const $input = $wrapper.find('.setting-fields-action-input');
                if ($input.length) {
                    inputValue = $input.val();
                }

                const $btnText = $btn.find('.setting-fields-action-btn-text');
                const $icon = $btn.find('.setting-fields-action-icon');
                const $result = $wrapper.find('.setting-fields-action-result');
                const $resultIcon = $result.find('.setting-fields-action-result-icon');
                const $resultMsg = $result.find('.setting-fields-action-result-message');
                const originalLabel = $btn.data('label');
                const loadingLabel = $btn.data('loading-label');
                const originalIconClass = $icon.attr('class').replace('setting-fields-action-icon', '').replace('spin', '').trim();

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
                        const message = xhr.responseJSON && xhr.responseJSON.message
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
         * Initialize all Select2 enhanced dropdowns.
         *
         * Finds all .setting-fields-select2 elements and initializes them,
         * skipping any that are already initialized.
         */
        initSelect2: function () {
            const self = this;

            $('.setting-fields-select2').each(function () {
                const $select = $(this);

                if ($select.hasClass('select2-hidden-accessible')) {
                    return;
                }

                self.initSingleSelect2($select);
            });
        },

        /**
         * Initialize a single Select2 instance.
         *
         * Configures AJAX search, tags, placeholder, and maximum selection
         * based on data attributes. Hydrates existing values for AJAX selects.
         *
         * @param {jQuery} $select The select element to enhance.
         */
        initSingleSelect2: function ($select) {
            const options = {
                width: '100%',
                allowClear: $select.data('allow-clear') === 'true' || $select.data('allow-clear') === true,
                placeholder: $select.data('placeholder') || ''
            };

            if ($select.data('ajax') === 'true' || $select.data('ajax') === true) {
                options.ajax = {
                    url: settingFieldsData.restUrl + 'ajax',
                    dataType: 'json',
                    delay: 250,
                    headers: {
                        'X-WP-Nonce': settingFieldsData.restNonce
                    },
                    data: function (params) {
                        const data = {
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
                        const results = (data.results || data || []).map(function (item) {
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

            if ($select.data('tags') === 'true' || $select.data('tags') === true) {
                options.tags = true;
            }

            if ($select.data('maximum-selection-length')) {
                options.maximumSelectionLength = parseInt($select.data('maximum-selection-length'));
            }

            $select.select2(options);

            if ($select.data('ajax') === 'true' || $select.data('ajax') === true) {
                const currentValues = $select.val();
                if (currentValues && currentValues.length) {
                    let ids = Array.isArray(currentValues) ? currentValues : [currentValues];
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
         * Hydrate Select2 with labels for pre-selected values.
         *
         * Fetches display labels from the REST API for values that were
         * stored as IDs, then updates the Select2 options.
         *
         * @param {jQuery} $select The Select2 element.
         * @param {Array}  ids     Array of selected value IDs.
         */
        hydrateSelect2: function ($select, ids) {
            const data = {
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
                const results = response.results || response;

                $select.empty();

                results.forEach(function (item) {
                    const option = new Option(item.label, item.value, true, true);
                    $select.append(option);
                });

                $select.trigger('change.select2');
            });
        },

        /**
         * Initialize WordPress color picker fields.
         *
         * Enhances .setting-fields-color-picker inputs with wpColorPicker,
         * supporting alpha channels and custom palette colors.
         */
        initColorPicker: function () {
            $('.setting-fields-color-picker').each(function () {
                const $input = $(this);
                const options = {
                    defaultColor: $input.data('default-color') || false,
                    change: function (event, ui) {
                        $input.val(ui.color.toString()).trigger('change');
                    },
                };

                if ($input.data('alpha-enabled') === 'true') {
                    options.palettes = true;
                }

                if ($input.data('palettes')) {
                    options.palettes = $input.data('palettes');
                }

                $input.wpColorPicker(options);
            });
        },

        /**
         * Initialize CodeMirror-based code editor fields.
         *
         * Uses wp.codeEditor to enhance textareas with syntax highlighting
         * based on the data-mime attribute (e.g., text/html, application/json).
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
         * Initialize range slider fields.
         *
         * Adds CSS custom property for progress fill and syncs the
         * displayed value label on input change.
         */
        initRangeSlider: function () {
            const updateRangeProgress = function ($input) {
                const min = parseFloat($input.attr('min')) || 0;
                const max = parseFloat($input.attr('max')) || 100;
                const val = parseFloat($input.val()) || 0;
                const progress = ((val - min) / (max - min)) * 100;
                $input.css('--range-progress', progress + '%');
            };

            $('.setting-fields-range').each(function () {
                updateRangeProgress($(this));
            });

            $('.setting-fields-range').on('input', function () {
                const $input = $(this);
                const $value = $('.setting-fields-range-value[data-target="' + $input.attr('id') + '"]');
                $value.text($input.val());
                updateRangeProgress($input);
            });
        },

        /**
         * Initialize image upload fields.
         *
         * Handles select, change, and remove actions for image fields
         * using the WordPress media frame.
         */
        initImageFields: function () {
            const self = this;

            $(document).on('click', '.setting-fields-image-select, .setting-fields-image-change', function (e) {
                e.preventDefault();
                const $field = $(this).closest('.setting-fields-image-field');
                self.openMediaFrame($field, 'image');
            });

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
         * Initialize file upload fields.
         *
         * Handles select, change, and remove actions for generic file fields
         * using the WordPress media frame.
         */
        initFileFields: function () {
            const self = this;

            $(document).on('click', '.setting-fields-file-select, .setting-fields-file-change', function (e) {
                e.preventDefault();
                const $field = $(this).closest('.setting-fields-file-field');
                self.openMediaFrame($field, 'file');
            });

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
         * Open the WordPress media frame for image or file selection.
         *
         * Configures the frame based on the field's data-library attribute
         * and updates the field UI on selection.
         *
         * @param {jQuery} $field The field container element.
         * @param {string} type   Either 'image' or 'file'.
         */
        openMediaFrame: function ($field, type) {
            const library = $field.data('library') || (type === 'image' ? 'image' : '');

            const frameOptions = {
                title: type === 'image' ? settingFieldsData.i18n.selectImage : settingFieldsData.i18n.selectFile,
                button: {
                    text: type === 'image' ? settingFieldsData.i18n.useImage : settingFieldsData.i18n.useFile
                },
                multiple: false,
                library: {}
            };

            if (library && library !== 'all') {
                frameOptions.library.type = library;
            }

            const frame = wp.media(frameOptions);

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
         * Initialize gallery (multi-image) fields.
         *
         * Handles adding images via the media frame, removing individual
         * images, and drag-to-reorder via jQuery UI sortable.
         */
        initGalleryFields: function () {
            const self = this;

            $(document).on('click', '.setting-fields-gallery-add', function (e) {
                e.preventDefault();
                const $field = $(this).closest('.setting-fields-gallery-field');
                self.openGalleryFrame($field);
            });

            $(document).on('click', '.setting-fields-gallery-remove', function (e) {
                e.preventDefault();
                $(this).closest('.setting-fields-gallery-item').remove();
            });

            if ($.fn.sortable) {
                $('.setting-fields-gallery-items').sortable({
                    items: '.setting-fields-gallery-item',
                    cursor: 'move',
                    opacity: 0.65,
                    placeholder: 'setting-fields-gallery-placeholder'
                });
            }
        },

        /**
         * Open the WordPress media frame for multi-image gallery selection.
         *
         * Allows selecting multiple images and appends them to the gallery
         * items container with hidden inputs for form submission.
         *
         * @param {jQuery} $field The gallery field container element.
         */
        openGalleryFrame: function ($field) {
            const library = $field.data('library') || 'image';

            const frame = wp.media({
                title: settingFieldsData.i18n.selectImages,
                button: {text: settingFieldsData.i18n.useImages},
                library: {type: library},
                multiple: true
            });

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
         * Initialize repeater fields.
         *
         * Handles adding/removing rows, collapsing/expanding row content,
         * and drag-to-reorder via jQuery UI sortable with automatic reindexing.
         */
        initRepeater: function () {
            const self = this;

            $(document).on('click', '.setting-fields-repeater-add', function (e) {
                e.preventDefault();
                const $repeater = $(this).closest('.setting-fields-repeater');
                self.addRepeaterRow($repeater);
            });

            $(document).on('click', '.setting-fields-repeater-remove', function (e) {
                e.preventDefault();
                if (confirm(settingFieldsData.i18n.confirmRemove)) {
                    const $row = $(this).closest('.setting-fields-repeater-row');
                    const $repeater = $row.closest('.setting-fields-repeater');

                    $row.remove();

                    const $rows = $repeater.find('.setting-fields-repeater-rows');
                    if ($rows.find('.setting-fields-repeater-row').length === 0) {
                        $rows.find('.setting-fields-repeater-empty').show();
                    }
                }
            });

            $(document).on('click', '.setting-fields-repeater-row-header', function (e) {
                if ($(e.target).closest('.setting-fields-repeater-remove, .setting-fields-repeater-sort, button, input, select, textarea').length) {
                    return;
                }

                e.preventDefault();
                const $row = $(this).closest('.setting-fields-repeater-row');
                const $icon = $(this).find('.setting-fields-repeater-toggle .dashicons');

                $row.toggleClass('setting-fields-repeater-row--collapsed');

                if ($row.hasClass('setting-fields-repeater-row--collapsed')) {
                    $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
                } else {
                    $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
                }
            });

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

        /**
         * Add a new row to a repeater field.
         *
         * Clones the row template, replaces index placeholders,
         * appends to the rows container, and initializes any
         * special fields (Select2, color pickers) in the new row.
         *
         * @param {jQuery} $repeater The repeater container element.
         */
        addRepeaterRow: function ($repeater) {
            const template = $repeater.find('.setting-fields-repeater-template').html();
            const $rows = $repeater.find('.setting-fields-repeater-rows');
            const newIndex = $rows.find('.setting-fields-repeater-row').length;

            $rows.find('.setting-fields-repeater-empty').hide();

            const newRow = template.replace(/\{\{INDEX\}\}/g, newIndex);
            $rows.append(newRow);

            const $newRow = $rows.find('.setting-fields-repeater-row').last();
            this.initRowFields($newRow);
        },

        /**
         * Initialize special fields within a newly added repeater row.
         *
         * Re-initializes Select2 and color picker instances that need
         * fresh initialization after DOM insertion.
         *
         * @param {jQuery} $row The newly added repeater row element.
         */
        initRowFields: function ($row) {
            const self = this;

            $row.find('.setting-fields-select2').each(function () {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    self.initSingleSelect2($(this));
                }
            });

            $row.find('.setting-fields-color-picker').each(function () {
                if (!$(this).closest('.wp-picker-container').length) {
                    $(this).wpColorPicker();
                }
            });
        },

        /**
         * Reindex all repeater rows after sorting.
         *
         * Updates data-index attributes, input name arrays, and element
         * IDs to reflect the new visual order.
         *
         * @param {jQuery} $repeater The repeater container element.
         */
        reindexRepeater: function ($repeater) {
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
         * Initialize button group (radio-as-buttons) fields.
         *
         * Toggles the button-primary class on the active label when
         * the underlying radio input changes.
         */
        initButtonGroup: function () {
            $(document).on('change', '.setting-fields-button-group input[type="radio"]', function () {
                const $group = $(this).closest('.setting-fields-button-group');
                $group.find('label').removeClass('button-primary');
                $group.find('input:checked').each(function () {
                    $(this).next('label').addClass('button-primary');
                });
            });

            $(document).on('click', '.setting-fields-button-group label', function () {
                const $label = $(this);
                const $input = $label.prev('input[type="radio"]');

                if ($input.length && !$input.prop('checked')) {
                    $input.prop('checked', true).trigger('change');
                }
            });
        },

        /**
         * Initialize dimension fields with linked/unlinked value sync.
         *
         * Toggles linked state on button click and syncs all dimension
         * inputs to the same value when linked.
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

            $(document).on('input', '.setting-fields-dimensions-field[data-linked="true"] input[type="number"]', function () {
                const $field = $(this).closest('.setting-fields-dimensions-field');
                const value = $(this).val();
                $field.find('.setting-fields-dimensions-inputs input[type="number"]').val(value);
            });
        },

        /**
         * Get current values from an email editor instance.
         *
         * Reads subject, title, subtitle, and message from the editor
         * fields for use in preview and send-test AJAX requests.
         *
         * @param {jQuery} $editor The email editor wrapper element.
         * @returns {Object} Editor values with settings_id, field_key, subject, title, subtitle, message.
         */
        getEmailEditorValues: function ($editor) {
            const fieldId = $editor.data('field-id');
            const editorId = fieldId + '_message';
            let message = '';

            if (typeof tinyMCE !== 'undefined' && tinyMCE.get(editorId)) {
                message = tinyMCE.get(editorId).getContent();
            } else {
                message = $('#' + editorId).val();
            }

            return {
                settings_id: $editor.closest('.setting-fields-wrap').data('setting-id'),
                field_key: $editor.data('field-key'),
                subject: $editor.find('.setting-fields-email-subject-input').val(),
                title: $editor.find('.setting-fields-email-title-input').val() || '',
                subtitle: $editor.find('.setting-fields-email-subtitle-input').val() || '',
                message: message
            };
        },

        /**
         * Initialize email editor functionality.
         *
         * Handles collapsible editor toggle, enable/disable switch,
         * merge tag modal (open, close, search, insert), email preview
         * via REST API, and send test email via REST API.
         */
        initEmailEditor: function () {
            const self = this;

            // Collapsible toggle
            $(document).on('click', '.setting-fields-email-configure', function (e) {
                e.preventDefault();

                const $editor = $(this).closest('.setting-fields-email-editor');
                const $icon = $(this).find('.dashicons');

                $editor.toggleClass('setting-fields-email-editor--collapsed');

                if ($editor.hasClass('setting-fields-email-editor--collapsed')) {
                    $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
                } else {
                    $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
                }
            });

            // Enable/disable toggle
            $(document).on('change', '.setting-fields-email-enable-checkbox', function () {
                const $editor = $(this).closest('.setting-fields-email-editor');
                const $content = $editor.find('.setting-fields-email-content');

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

                const $btn = $(this);
                const target = $btn.data('target');
                const editorId = $btn.data('editor-id');

                let $editor;
                if (editorId) {
                    $editor = $('.setting-fields-email-editor[data-field-id="' + editorId + '"]');
                }

                if (!$editor || !$editor.length) {
                    $editor = $btn.closest('.setting-fields-email-editor');
                }

                if (!$editor || !$editor.length) {
                    return;
                }

                const $modal = $editor.find('.setting-fields-merge-tags-modal');

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

            // Search/filter tags in modal
            $(document).on('input', '.setting-fields-tag-search', function () {
                const query = $(this).val().toLowerCase();
                const $modal = $(this).closest('.setting-fields-merge-tags-modal');

                $modal.find('.setting-fields-tag-item').each(function () {
                    const tag = $(this).data('tag').toLowerCase();
                    const label = $(this).data('label').toLowerCase();
                    const desc = $(this).find('.setting-fields-tag-desc').text().toLowerCase();

                    if (tag.indexOf(query) > -1 || label.indexOf(query) > -1 || desc.indexOf(query) > -1) {
                        $(this).removeClass('hidden');
                    } else {
                        $(this).addClass('hidden');
                    }
                });
            });

            // Insert selected tag into target field
            $(document).on('click', '.setting-fields-tag-item', function (e) {
                e.preventDefault();

                const tag = $(this).data('tag');
                const $modal = $(this).closest('.setting-fields-merge-tags-modal');
                const $editor = $modal.closest('.setting-fields-email-editor');
                const target = $modal.data('insert-target');
                const fieldId = $editor.data('field-id');

                if (target === 'subject') {
                    const $input = $editor.find('.setting-fields-email-subject-input');
                    self.insertAtCursor($input[0], tag);
                } else if (target === 'title') {
                    const $input = $editor.find('.setting-fields-email-title-input');
                    self.insertAtCursor($input[0], tag);
                } else if (target === 'subtitle') {
                    const $input = $editor.find('.setting-fields-email-subtitle-input');
                    self.insertAtCursor($input[0], tag);
                } else {
                    const editorId = fieldId + '_message';

                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get(editorId)) {
                        const editor = tinyMCE.get(editorId);
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

            // Preview email via REST endpoint
            $(document).on('click', '.setting-fields-email-preview', function (e) {
                e.preventDefault();

                const $editor = $(this).closest('.setting-fields-email-editor');
                const data = self.getEmailEditorValues($editor);

                const $btn = $(this);
                $btn.prop('disabled', true);

                $.ajax({
                    url: settingFieldsData.restUrl + 'email/preview',
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': settingFieldsData.restNonce
                    },
                    data: data,
                    success: function (response) {
                        if (response.html) {
                            self.openPreviewWindow(response.html);
                        } else {
                            alert('Preview failed: No HTML returned');
                        }
                    },
                    error: function (xhr) {
                        const msg = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Preview request failed';
                        alert(msg);
                    },
                    complete: function () {
                        $btn.prop('disabled', false);
                    }
                });
            });

            // Send test email via REST endpoint
            $(document).on('click', '.setting-fields-email-send-test', function (e) {
                e.preventDefault();

                const $editor = $(this).closest('.setting-fields-email-editor');
                const $emailInput = $editor.find('.setting-fields-email-test-input');
                const email = $emailInput.val();

                if (!email || !email.includes('@')) {
                    $emailInput.focus();
                    alert('Please enter a valid email address');
                    return;
                }

                const data = self.getEmailEditorValues($editor);
                data.email = email;

                const $btn = $(this);
                const originalHtml = $btn.html();
                $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Sending...');

                $.ajax({
                    url: settingFieldsData.restUrl + 'email/send-test',
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': settingFieldsData.restNonce
                    },
                    data: data,
                    success: function (response) {
                        alert(response.message || 'Test email sent successfully!');
                    },
                    error: function (xhr) {
                        const msg = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Failed to send test email';
                        alert(msg);
                    },
                    complete: function () {
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });
        },

        /**
         * Insert text at the current cursor position in an input or textarea.
         *
         * Preserves existing content before and after the cursor, places the
         * new text, and moves the cursor to after the insertion.
         *
         * @param {HTMLElement} element The input or textarea DOM element.
         * @param {string}      text    The text to insert.
         */
        insertAtCursor: function (element, text) {
            if (!element) return;

            const startPos = element.selectionStart || 0;
            const endPos = element.selectionEnd || 0;
            const value = element.value || '';

            element.value = value.substring(0, startPos) + text + value.substring(endPos);
            element.selectionStart = element.selectionEnd = startPos + text.length;
            element.focus();

            $(element).trigger('change');
        },

        /**
         * Open a new browser window with email preview HTML.
         *
         * @param {string} html The complete HTML document to display.
         */
        openPreviewWindow: function (html) {
            const win = window.open('', 'email_preview', 'width=700,height=600,scrollbars=yes');
            win.document.write(html);
            win.document.close();
        },

        /**
         * Escape HTML entities for safe display in the DOM.
         *
         * @param {string} text The raw text to escape.
         * @returns {string} The escaped HTML string.
         */
        escapeHtml: function (text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * Initialize sortable list fields.
         *
         * Enables drag-to-reorder via jQuery UI sortable and toggle
         * buttons to activate/deactivate individual list items.
         */
        initSortable: function () {
            const $sortables = $('.setting-fields-sortable-list');

            if (!$sortables.length) return;

            if ($.fn.sortable) {
                $sortables.sortable({
                    handle: '.setting-fields-sortable-handle',
                    placeholder: 'setting-fields-sortable-placeholder',
                    update: function () {
                        const $list = $(this);
                        $list.find('.setting-fields-sortable-item').each(function () {
                            const $input = $(this).find('input[type="hidden"]');
                            $input.attr('name', $input.attr('name'));
                        });
                    }
                });
            }

            $(document).on('click', '.setting-fields-sortable-toggle', function (e) {
                e.preventDefault();

                const $item = $(this).closest('.setting-fields-sortable-item');
                const $input = $item.find('input[type="hidden"]');
                const $icon = $(this).find('.dashicons');

                if ($item.hasClass('setting-fields-sortable-item--active')) {
                    $item.removeClass('setting-fields-sortable-item--active');
                    $input.prop('disabled', true);
                    $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                    $(this).attr('title', settingFieldsData.i18n?.enable || 'Enable');
                } else {
                    $item.addClass('setting-fields-sortable-item--active');
                    $input.prop('disabled', false);
                    $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                    $(this).attr('title', settingFieldsData.i18n?.disable || 'Disable');
                }
            });
        },

        /**
         * Initialize collapsible group fields.
         *
         * Toggles collapsed state on header or toggle button click,
         * updating the chevron icon direction.
         */
        initCollapsibleGroups: function () {
            $(document).on('click', '.setting-fields-group-toggle', function (e) {
                e.preventDefault();

                const $group = $(this).closest('.setting-fields-group');
                const $icon = $(this).find('.dashicons');

                $group.toggleClass('setting-fields-group--collapsed');

                if ($group.hasClass('setting-fields-group--collapsed')) {
                    $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
                } else {
                    $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
                }
            });

            $(document).on('click', '.setting-fields-group-header', function (e) {
                if (!$(e.target).closest('.setting-fields-group-toggle').length) {
                    $(this).find('.setting-fields-group-toggle').trigger('click');
                }
            });
        },

        /**
         * Initialize reset button functionality.
         *
         * Handles click on the reset button with a confirm dialog,
         * then calls the REST reset endpoint and reloads the page.
         */
        initReset: function () {
            $(document).on('click', '.setting-fields-reset-btn', function (e) {
                e.preventDefault();

                const $btn = $(this);
                const confirmMsg = $btn.data('confirm');

                if (confirmMsg && !confirm(confirmMsg)) {
                    return;
                }

                const settingsId = $btn.closest('.setting-fields-wrap').data('setting-id');
                const tab = $btn.data('tab') || '';
                const originalText = $btn.text();

                $btn.prop('disabled', true).text(settingFieldsData.i18n.resetting || 'Resetting…');

                $.ajax({
                    url: settingFieldsData.restUrl + 'reset',
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': settingFieldsData.restNonce
                    },
                    data: {
                        settings_id: settingsId,
                        tab: tab
                    },
                    success: function (response) {
                        if (response.success) {
                            window.location.reload();
                        }
                    },
                    error: function (xhr) {
                        const message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : (settingFieldsData.i18n.resetFailed || 'Reset failed.');
                        alert(message);
                    },
                    complete: function () {
                        $btn.prop('disabled', false).text(originalText);
                    }
                });
            });
        },

        /**
         * Initialize export/import functionality.
         *
         * Handles export button click (downloads JSON via REST endpoint),
         * import button click (triggers file input), and file selection
         * (reads JSON and posts to REST import endpoint).
         */
        initExportImport: function () {
            const self = this;

            // Export: fetch JSON from REST and trigger download
            $(document).on('click', '.setting-fields-export-btn', function (e) {
                e.preventDefault();

                const $btn = $(this);
                const $wrap = $btn.closest('.setting-fields-wrap');
                const settingsId = $wrap.data('setting-id');
                const $result = $wrap.find('.setting-fields-export-import-result');
                const $icon = $result.find('.setting-fields-export-import-icon');
                const $message = $result.find('.setting-fields-export-import-message');

                $btn.prop('disabled', true);

                $.ajax({
                    url: settingFieldsData.restUrl + 'export',
                    method: 'GET',
                    headers: {
                        'X-WP-Nonce': settingFieldsData.restNonce
                    },
                    data: {
                        settings_id: settingsId
                    },
                    success: function (response) {
                        // Trigger file download
                        const json = JSON.stringify(response, null, 2);
                        const blob = new Blob([json], {type: 'application/json'});
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');

                        a.href = url;
                        a.download = settingsId + '-settings-' + self.getDateStamp() + '.json';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);

                        $icon.attr('class', 'dashicons dashicons-yes-alt setting-fields-export-import-icon');
                        $message.text(settingFieldsData.i18n.exportSuccess || 'Settings exported successfully.');
                        $result.removeClass('setting-fields-export-import--error')
                            .addClass('setting-fields-export-import--success').show();
                    },
                    error: function (xhr) {
                        const message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : (settingFieldsData.i18n.exportFailed || 'Export failed.');
                        $icon.attr('class', 'dashicons dashicons-warning setting-fields-export-import-icon');
                        $message.text(message);
                        $result.removeClass('setting-fields-export-import--success')
                            .addClass('setting-fields-export-import--error').show();
                    },
                    complete: function () {
                        $btn.prop('disabled', false);
                    }
                });
            });

            // Import: trigger hidden file input
            $(document).on('click', '.setting-fields-import-btn', function (e) {
                e.preventDefault();
                $(this).closest('.setting-fields-import-wrap')
                    .find('.setting-fields-import-file').trigger('click');
            });

            // Import: read selected file and POST to REST endpoint
            $(document).on('change', '.setting-fields-import-file', function () {
                const file = this.files[0];
                if (!file) return;

                const $wrap = $(this).closest('.setting-fields-wrap');
                const settingsId = $wrap.data('setting-id');
                const $result = $wrap.find('.setting-fields-export-import-result');
                const $icon = $result.find('.setting-fields-export-import-icon');
                const $message = $result.find('.setting-fields-export-import-message');
                const $importBtn = $wrap.find('.setting-fields-import-btn');

                if (!file.name.endsWith('.json')) {
                    $icon.attr('class', 'dashicons dashicons-warning setting-fields-export-import-icon');
                    $message.text(settingFieldsData.i18n.importInvalidFile || 'Please select a valid JSON file.');
                    $result.removeClass('setting-fields-export-import--success')
                        .addClass('setting-fields-export-import--error').show();
                    return;
                }

                const confirmMsg = settingFieldsData.i18n.importConfirm
                    || 'Are you sure you want to import settings? This will overwrite current values for any matching fields.';
                if (!confirm(confirmMsg)) {
                    // Reset file input
                    $(this).val('');
                    return;
                }

                $importBtn.prop('disabled', true);

                const reader = new FileReader();

                reader.onload = function (e) {
                    let parsed;
                    try {
                        parsed = JSON.parse(e.target.result);
                    } catch (err) {
                        $icon.attr('class', 'dashicons dashicons-warning setting-fields-export-import-icon');
                        $message.text(settingFieldsData.i18n.importInvalidJson || 'File contains invalid JSON.');
                        $result.removeClass('setting-fields-export-import--success')
                            .addClass('setting-fields-export-import--error').show();
                        $importBtn.prop('disabled', false);
                        return;
                    }

                    $.ajax({
                        url: settingFieldsData.restUrl + 'import',
                        method: 'POST',
                        headers: {
                            'X-WP-Nonce': settingFieldsData.restNonce
                        },
                        contentType: 'application/json',
                        data: JSON.stringify({
                            settings_id: settingsId,
                            data: parsed
                        }),
                        success: function (response) {
                            $icon.attr('class', 'dashicons dashicons-yes-alt setting-fields-export-import-icon');
                            $message.text(response.message || (settingFieldsData.i18n.importSuccess || 'Settings imported. Reloading…'));
                            $result.removeClass('setting-fields-export-import--error')
                                .addClass('setting-fields-export-import--success').show();

                            // Reload after short delay so user sees the success message
                            setTimeout(function () {
                                window.location.reload();
                            }, 1000);
                        },
                        error: function (xhr) {
                            const message = xhr.responseJSON && xhr.responseJSON.message
                                ? xhr.responseJSON.message
                                : (settingFieldsData.i18n.importFailed || 'Import failed.');
                            $icon.attr('class', 'dashicons dashicons-warning setting-fields-export-import-icon');
                            $message.text(message);
                            $result.removeClass('setting-fields-export-import--success')
                                .addClass('setting-fields-export-import--error').show();
                        },
                        complete: function () {
                            $importBtn.prop('disabled', false);
                        }
                    });
                };

                reader.readAsText(file);

                // Reset file input so the same file can be selected again
                $(this).val('');
            });
        },

        /**
         * Generate a date stamp string for export filenames.
         *
         * @returns {string} Date in YYYY-MM-DD format.
         */
        getDateStamp: function () {
            const d = new Date();
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return year + '-' + month + '-' + day;
        }
    };

    $(document).ready(function () {
        SettingFields.init();
    });

})(jQuery);