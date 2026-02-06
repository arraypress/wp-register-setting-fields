<?php
/**
 * Complex Fields Rendering Trait
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Traits\Rendering;

/**
 * Trait ComplexFields
 *
 * Renders complex composite field types.
 */
trait ComplexFields {

    /**
     * Render a clipboard field (read-only value with copy button).
     *
     * Useful for webhook URLs, shortcodes, API endpoints, or any
     * read-only string that users need to copy.
     *
     * Field config options:
     * - value          (string) The value to display and copy (required).
     * - button_label   (string) Copy button text. Default: 'Copy'.
     * - copied_label   (string) Text shown after copying. Default: 'Copied!'.
     * - display        (string) Display style: 'code' (default) or 'input'.
     * - url            (bool)   Whether to format value as a URL. Default: false.
     *
     * @param array  $field Field configuration.
     * @param string $name  Input name.
     * @param string $id    Input id.
     * @param mixed  $value Current value (unused — value comes from field config).
     *
     * @return void
     */
    protected function render_clipboard( array $field, string $name, string $id, $value ): void {
        $clipboard_value = $field['value'] ?? $value ?? '';
        $button_label    = $field['button_label'] ?? __( 'Copy', 'setting-fields' );
        $copied_label    = $field['copied_label'] ?? __( 'Copied!', 'setting-fields' );
        $display         = $field['display'] ?? 'code';
        $is_url          = $field['url'] ?? false;

        $display_value = $is_url ? esc_url( $clipboard_value ) : esc_html( $clipboard_value );
        ?>
        <div class="setting-fields-clipboard" data-field-id="<?php echo esc_attr( $id ); ?>">
            <?php if ( $display === 'input' ) : ?>
                <input type="text"
                       id="<?php echo esc_attr( $id ); ?>"
                       value="<?php echo esc_attr( $clipboard_value ); ?>"
                       class="regular-text setting-fields-clipboard-value"
                       readonly />
            <?php else : ?>
                <code class="setting-fields-clipboard-value setting-fields-clipboard-code"
                      id="<?php echo esc_attr( $id ); ?>"><?php echo $display_value; ?></code>
            <?php endif; ?>

            <button type="button"
                    class="button setting-fields-clipboard-btn"
                    data-clipboard-target="#<?php echo esc_attr( $id ); ?>"
                    data-clipboard-text="<?php echo esc_attr( $clipboard_value ); ?>"
                    data-label="<?php echo esc_attr( $button_label ); ?>"
                    data-copied-label="<?php echo esc_attr( $copied_label ); ?>">
                <span class="dashicons dashicons-clipboard"></span>
                <span class="setting-fields-clipboard-btn-text"><?php echo esc_html( $button_label ); ?></span>
            </button>
        </div>
        <?php
    }

    /**
     * Render an action button field.
     *
     * Fires a REST API request to execute a server-side callback and
     * displays the result. Useful for connection tests, license activation,
     * cache clearing, or any on-demand server action.
     *
     * Field config options:
     * - button_label     (string)   Button text. Default: 'Run'.
     * - loading_label    (string)   Text during request. Default: 'Processing...'.
     * - action_callback  (callable) Server-side callback. Receives array with settings_id, field_key, input_value.
     *                               Must return ['success' => bool, 'message' => string].
     * - confirm          (string)   If set, shows a confirmation dialog with this message before executing.
     * - icon             (string)   Dashicon class for the button. Default: 'dashicons-update'.
     * - success_icon     (string)   Dashicon for success state. Default: 'dashicons-yes-alt'.
     * - error_icon       (string)   Dashicon for error state. Default: 'dashicons-warning'.
     * - button_class     (string)   Additional CSS class for the button. Default: '' (uses 'button button-secondary').
     * - show_input       (bool)     Show an inline text input before the button. Default: false.
     * - input_placeholder (string)  Placeholder for the optional input.
     * - input_type       (string)   Input type for the optional input. Default: 'text'.
     *
     * @param array  $field Field configuration.
     * @param string $name  Input name.
     * @param string $id    Input id.
     * @param mixed  $value Current value.
     *
     * @return void
     */
    protected function render_action_button( array $field, string $name, string $id, $value ): void {
        $button_label   = $field['button_label'] ?? __( 'Run', 'setting-fields' );
        $loading_label  = $field['loading_label'] ?? __( 'Processing...', 'setting-fields' );
        $icon           = $field['icon'] ?? 'dashicons-update';
        $success_icon   = $field['success_icon'] ?? 'dashicons-yes-alt';
        $error_icon     = $field['error_icon'] ?? 'dashicons-warning';
        $button_class   = $field['button_class'] ?? '';
        $confirm        = $field['confirm'] ?? '';
        $show_input     = $field['show_input'] ?? false;
        $input_placeholder = $field['input_placeholder'] ?? '';
        $input_type     = $field['input_type'] ?? 'text';
        $field_key      = $field['_key'] ?? '';

        $btn_class = 'button ' . ( $button_class ?: 'button-secondary' ) . ' setting-fields-action-btn';
        ?>
        <div class="setting-fields-action-button"
             data-field-id="<?php echo esc_attr( $id ); ?>"
             data-field-key="<?php echo esc_attr( $field_key ); ?>"
             data-success-icon="<?php echo esc_attr( $success_icon ); ?>"
             data-error-icon="<?php echo esc_attr( $error_icon ); ?>"
             <?php if ( $confirm ) : ?>data-confirm="<?php echo esc_attr( $confirm ); ?>"<?php endif; ?>>

            <?php if ( $show_input ) : ?>
                <input type="<?php echo esc_attr( $input_type ); ?>"
                       class="regular-text setting-fields-action-input"
                       id="<?php echo esc_attr( $id ); ?>_input"
                       name="<?php echo esc_attr( $name ); ?>"
                       value="<?php echo esc_attr( $value ); ?>"
                       placeholder="<?php echo esc_attr( $input_placeholder ); ?>" />
            <?php endif; ?>

            <button type="button"
                    class="<?php echo esc_attr( $btn_class ); ?>"
                    data-label="<?php echo esc_attr( $button_label ); ?>"
                    data-loading-label="<?php echo esc_attr( $loading_label ); ?>">
                <span class="dashicons <?php echo esc_attr( $icon ); ?> setting-fields-action-icon"></span>
                <span class="setting-fields-action-btn-text"><?php echo esc_html( $button_label ); ?></span>
            </button>

            <span class="setting-fields-action-result" style="display: none;">
				<span class="dashicons setting-fields-action-result-icon"></span>
				<span class="setting-fields-action-result-message"></span>
			</span>
        </div>
        <?php
    }

    /**
     * Render a link field.
     *
     * @param array  $field Field configuration.
     * @param string $name  Input name.
     * @param string $id    Input id.
     * @param mixed  $value Current value.
     *
     * @return void
     */
    protected function render_link( array $field, string $name, string $id, $value ): void {
        $value = wp_parse_args( (array) $value, [
                'url'    => '',
                'text'   => '',
                'target' => '_self',
        ] );

        ?>
        <div class="setting-fields-link-field">
            <div class="setting-fields-link-row">
                <label for="<?php echo esc_attr( $id ); ?>_url"><?php esc_html_e( 'URL', 'setting-fields' ); ?></label>
                <input type="url"
                       name="<?php echo esc_attr( $name ); ?>[url]"
                       id="<?php echo esc_attr( $id ); ?>_url"
                       value="<?php echo esc_url( $value['url'] ); ?>"
                       class="regular-text"
                       placeholder="https://"/>
            </div>

            <div class="setting-fields-link-row">
                <label for="<?php echo esc_attr( $id ); ?>_text"><?php esc_html_e( 'Link Text', 'setting-fields' ); ?></label>
                <input type="text"
                       name="<?php echo esc_attr( $name ); ?>[text]"
                       id="<?php echo esc_attr( $id ); ?>_text"
                       value="<?php echo esc_attr( $value['text'] ); ?>"
                       class="regular-text"/>
            </div>

            <div class="setting-fields-link-row">
                <label>
                    <input type="checkbox"
                           name="<?php echo esc_attr( $name ); ?>[target]"
                           value="_blank"
                            <?php checked( $value['target'], '_blank' ); ?> />
                    <?php esc_html_e( 'Open in new tab', 'setting-fields' ); ?>
                </label>
            </div>
        </div>
        <?php
    }

    /**
     * Render a dimensions field.
     *
     * @param array  $field Field configuration.
     * @param string $name  Input name.
     * @param string $id    Input id.
     * @param mixed  $value Current value.
     *
     * @return void
     */
    protected function render_dimensions( array $field, string $name, string $id, $value ): void {
        $value = wp_parse_args( (array) $value, [
                'top'    => '',
                'right'  => '',
                'bottom' => '',
                'left'   => '',
                'unit'   => $field['default_unit'] ?? 'px',
        ] );

        $units      = $field['units'] ?? [ 'px', 'em', 'rem', '%' ];
        $show_sides = $field['sides'] ?? [ 'top', 'right', 'bottom', 'left' ];
        $linked     = $field['linked'] ?? false;

        $labels = [
                'top'    => __( 'Top', 'setting-fields' ),
                'right'  => __( 'Right', 'setting-fields' ),
                'bottom' => __( 'Bottom', 'setting-fields' ),
                'left'   => __( 'Left', 'setting-fields' ),
        ];

        ?>
        <div class="setting-fields-dimensions-field" data-linked="<?php echo $linked ? 'true' : 'false'; ?>">
            <div class="setting-fields-dimensions-inputs">
                <?php foreach ( $show_sides as $side ) : ?>
                    <div class="setting-fields-dimension-input">
                        <label for="<?php echo esc_attr( $id ); ?>_<?php echo esc_attr( $side ); ?>">
                            <?php echo esc_html( $labels[ $side ] ); ?>
                        </label>
                        <input type="number"
                               name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $side ); ?>]"
                               id="<?php echo esc_attr( $id ); ?>_<?php echo esc_attr( $side ); ?>"
                               value="<?php echo esc_attr( $value[ $side ] ); ?>"
                               class="small-text"
                               step="<?php echo esc_attr( $field['step'] ?? 1 ); ?>"
                               <?php if ( isset( $field['min'] ) ) : ?>min="<?php echo esc_attr( $field['min'] ); ?>"<?php endif; ?>
                               <?php if ( isset( $field['max'] ) ) : ?>max="<?php echo esc_attr( $field['max'] ); ?>"<?php endif; ?>
                        />
                    </div>
                <?php endforeach; ?>

                <?php if ( count( $units ) > 1 ) : ?>
                    <div class="setting-fields-dimension-unit">
                        <label for="<?php echo esc_attr( $id ); ?>_unit">
                            <?php esc_html_e( 'Unit', 'setting-fields' ); ?>
                        </label>
                        <select name="<?php echo esc_attr( $name ); ?>[unit]" id="<?php echo esc_attr( $id ); ?>_unit">
                            <?php foreach ( $units as $unit ) : ?>
                                <option value="<?php echo esc_attr( $unit ); ?>" <?php selected( $value['unit'], $unit ); ?>>
                                    <?php echo esc_html( $unit ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else : ?>
                    <div class="setting-fields-dimension-unit">
                        <label><?php esc_html_e( 'Unit', 'setting-fields' ); ?></label>
                        <input type="hidden" name="<?php echo esc_attr( $name ); ?>[unit]"
                               value="<?php echo esc_attr( $units[0] ); ?>"/>
                        <span class="setting-fields-dimension-unit-static"><?php echo esc_html( $units[0] ); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ( $linked !== false ) : ?>
                    <div class="setting-fields-dimension-link">
                        <label>&nbsp;</label>
                        <button type="button" class="button-link setting-fields-dimensions-link"
                                title="<?php esc_attr_e( 'Link values', 'setting-fields' ); ?>">
                            <span class="dashicons dashicons-admin-links"></span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render a separator/divider field.
     *
     * @param array  $field Field configuration.
     * @param string $name  Input name.
     * @param string $id    Input id.
     * @param mixed  $value Current value.
     *
     * @return void
     */
    protected function render_separator( array $field, string $name, string $id, $value ): void {
        $title       = $field['title'] ?? '';
        $description = $field['description'] ?? '';

        ?>
        <div class="setting-fields-separator">
            <div class="setting-fields-separator-wrap">
                <hr class="setting-fields-separator-line"/>
                <?php if ( ! empty( $title ) ) : ?>
                    <span class="setting-fields-separator-title"><?php echo esc_html( $title ); ?></span>
                    <hr class="setting-fields-separator-line"/>
                <?php endif; ?>
            </div>
            <?php if ( ! empty( $description ) ) : ?>
                <p class="setting-fields-separator-description"><?php echo wp_kses_post( $description ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render a heading field.
     *
     * @param array  $field Field configuration.
     * @param string $name  Input name.
     * @param string $id    Input id.
     * @param mixed  $value Current value.
     *
     * @return void
     */
    protected function render_heading( array $field, string $name, string $id, $value ): void {
        $title       = $field['title'] ?? $field['label'] ?? '';
        $description = $field['description'] ?? '';
        $level       = $field['level'] ?? 'h3';

        // Sanitize heading level
        $allowed_levels = [ 'h2', 'h3', 'h4', 'h5', 'h6' ];
        if ( ! in_array( $level, $allowed_levels, true ) ) {
            $level = 'h3';
        }

        ?>
        <div class="setting-fields-heading">
        <<?php echo $level; ?>
        class="setting-fields-heading-title"><?php echo esc_html( $title ); ?></<?php echo $level; ?>>
        <?php if ( ! empty( $description ) ) : ?>
            <p class="setting-fields-heading-description"><?php echo wp_kses_post( $description ); ?></p>
        <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render an email editor field with merge tags.
     *
     * Supports two modes:
     *
     * 1. Integrated mode (recommended): Set 'email_group' and 'email_template' to
     *    connect with wp-register-emails. Merge tags, defaults, preview, and sending
     *    are handled automatically by the email library.
     *
     * 2. Standalone mode: Provide 'merge_tags', 'default_subject', 'default_body',
     *    and optionally 'preview_callback' / 'send_callback' directly on the field.
     *
     * Additional options:
     * - show_enable       (bool)   Show enable/disable toggle. Default: false.
     * - show_recipient    (bool)   Show recipient email field. Default: false.
     * - default_recipient (string) Default recipient email. Default: admin email.
     * - show_preview      (bool)   Show preview button. Default: true.
     * - show_send_test    (bool)   Show send test button. Default: true.
     * - collapsible       (bool)   Wrap in collapsible card. Default: false.
     * - collapsed         (bool)   Start collapsed. Default: true.
     * - title             (string) Card title.
     * - card_description  (string) Card description.
     * - rows              (int)    Editor rows. Default: 15.
     *
     * @param array  $field Field configuration.
     * @param string $name  Input name.
     * @param string $id    Input id.
     * @param mixed  $value Current value.
     *
     * @return void
     */
    protected function render_email_editor( array $field, string $name, string $id, $value ): void {

        // Detect integration mode
        $email_group    = $field['email_group'] ?? '';
        $email_template = $field['email_template'] ?? '';
        $has_email_lib  = $email_group && $email_template && function_exists( 'get_email_template_tags' );

        // Resolve defaults and merge tags from email library or field config
        if ( $has_email_lib ) {
            $template_tags = get_email_template_tags( $email_group, $email_template );
            $merge_tags    = $this->convert_email_tags_to_merge_tags( $template_tags );

            // Get template defaults via the registry
            $template_defaults = $this->get_email_template_defaults( $email_group, $email_template );
            $default_enabled   = $template_defaults['enabled'] ?? true;
            $default_subject   = $template_defaults['subject'] ?? '';
            $default_body      = $template_defaults['message'] ?? '';
        } else {
            $merge_tags      = $field['merge_tags'] ?? [];
            $default_enabled = $field['default_enabled'] ?? true;
            $default_subject = $field['default_subject'] ?? '';
            $default_body    = $field['default_body'] ?? '';
        }

        $value = wp_parse_args( (array) $value, [
                'enabled'   => $default_enabled,
                'subject'   => $default_subject,
                'body'      => $default_body,
                'recipient' => $field['default_recipient'] ?? get_option( 'admin_email' ),
        ] );

        $show_enable    = $field['show_enable'] ?? false;
        $show_recipient = $field['show_recipient'] ?? false;
        $show_preview   = $field['show_preview'] ?? true;
        $show_send_test = $field['show_send_test'] ?? true;

        // Collapsible options
        $collapsible      = $field['collapsible'] ?? false;
        $collapsed        = $field['collapsed'] ?? true;
        $card_title       = $field['title'] ?? $field['label'] ?? '';
        $card_description = $field['card_description'] ?? '';

        // Build data attributes for JS
        $data_attrs = [
                'field-id'  => $id,
                'field-key' => $field['_key'] ?? '',
        ];

        if ( $has_email_lib ) {
            $data_attrs['email-group']    = $email_group;
            $data_attrs['email-template'] = $email_template;
        }

        // Legacy callback support (standalone mode)
        $preview_callback = $field['preview_callback'] ?? null;
        $send_callback    = $field['send_callback'] ?? null;

        if ( $preview_callback && is_string( $preview_callback ) ) {
            $data_attrs['preview-action'] = $preview_callback;
        }

        if ( $send_callback && is_string( $send_callback ) ) {
            $data_attrs['send-action'] = $send_callback;
        }

        $data_string = '';
        foreach ( $data_attrs as $key => $val ) {
            $data_string .= sprintf( ' data-%s="%s"', esc_attr( $key ), esc_attr( $val ) );
        }

        $wrapper_class = 'setting-fields-email-editor';
        if ( $collapsible ) {
            $wrapper_class .= ' setting-fields-email-editor--collapsible';
            if ( $collapsed ) {
                $wrapper_class .= ' setting-fields-email-editor--collapsed';
            }
        }

        ?>
        <div class="<?php echo esc_attr( $wrapper_class ); ?>"<?php echo $data_string; ?>>

            <?php if ( $collapsible ) : ?>
                <!-- Collapsible Header -->
                <div class="setting-fields-email-header">
                    <div class="setting-fields-email-header-content">
                        <?php if ( $card_title ) : ?>
                            <h4 class="setting-fields-email-title"><?php echo esc_html( $card_title ); ?></h4>
                        <?php endif; ?>
                        <?php if ( $card_description ) : ?>
                            <p class="setting-fields-email-description"><?php echo esc_html( $card_description ); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="setting-fields-email-header-actions">
                        <button type="button" class="button setting-fields-email-configure">
                            <?php esc_html_e( 'Configure', 'setting-fields' ); ?>
                            <span class="dashicons dashicons-arrow-<?php echo $collapsed ? 'down' : 'up'; ?>-alt2"></span>
                        </button>
                        <?php if ( $show_enable ) : ?>
                            <label class="setting-fields-toggle"
                                   title="<?php esc_attr_e( 'Enable/Disable this email', 'setting-fields' ); ?>">
                                <input type="hidden" name="<?php echo esc_attr( $name ); ?>[enabled]" value="0"/>
                                <input type="checkbox"
                                       name="<?php echo esc_attr( $name ); ?>[enabled]"
                                       id="<?php echo esc_attr( $id ); ?>_enabled"
                                       value="1"
                                       class="setting-fields-toggle-input setting-fields-email-enable-checkbox"
                                        <?php checked( $value['enabled'], true ); ?> />
                                <span class="setting-fields-toggle-slider"></span>
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="setting-fields-email-body-wrap">
                <?php if ( $show_enable && ! $collapsible ) : ?>
                    <!-- Enable/Disable Toggle (non-collapsible mode) -->
                    <div class="setting-fields-email-enable">
                        <label class="setting-fields-toggle">
                            <input type="hidden" name="<?php echo esc_attr( $name ); ?>[enabled]" value="0"/>
                            <input type="checkbox"
                                   name="<?php echo esc_attr( $name ); ?>[enabled]"
                                   id="<?php echo esc_attr( $id ); ?>_enabled"
                                   value="1"
                                   class="setting-fields-toggle-input setting-fields-email-enable-checkbox"
                                    <?php checked( $value['enabled'], true ); ?> />
                            <span class="setting-fields-toggle-slider"></span>
                        </label>
                        <span class="setting-fields-toggle-label"><?php esc_html_e( 'Enable this email', 'setting-fields' ); ?></span>
                    </div>
                <?php endif; ?>

                <div class="setting-fields-email-content<?php echo $show_enable && ! $value['enabled'] ? ' setting-fields-email-disabled' : ''; ?>">

                    <?php if ( $show_recipient ) : ?>
                        <!-- Recipient Email -->
                        <div class="setting-fields-email-recipient">
                            <label for="<?php echo esc_attr( $id ); ?>_recipient">
                                <?php esc_html_e( 'Recipient', 'setting-fields' ); ?>
                            </label>
                            <input type="email"
                                   name="<?php echo esc_attr( $name ); ?>[recipient]"
                                   id="<?php echo esc_attr( $id ); ?>_recipient"
                                   value="<?php echo esc_attr( $value['recipient'] ); ?>"
                                   class="regular-text setting-fields-email-recipient-input"
                                   placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"/>
                            <p class="description"><?php esc_html_e( 'Email address where this notification is sent.', 'setting-fields' ); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Subject Line -->
                    <div class="setting-fields-email-subject">
                        <label for="<?php echo esc_attr( $id ); ?>_subject">
                            <?php esc_html_e( 'Subject', 'setting-fields' ); ?>
                        </label>
                        <div class="setting-fields-email-subject-wrap">
                            <input type="text"
                                   name="<?php echo esc_attr( $name ); ?>[subject]"
                                   id="<?php echo esc_attr( $id ); ?>_subject"
                                   value="<?php echo esc_attr( $value['subject'] ); ?>"
                                   class="large-text setting-fields-email-subject-input"/>
                            <?php if ( ! empty( $merge_tags ) ) : ?>
                                <button type="button" class="button setting-fields-insert-tag-btn" data-target="subject"
                                        title="<?php esc_attr_e( 'Insert merge tag', 'setting-fields' ); ?>">
                                    <span class="dashicons dashicons-shortcode"></span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Body Editor -->
                    <div class="setting-fields-email-body">
                        <label for="<?php echo esc_attr( $id ); ?>_body">
                            <?php esc_html_e( 'Message', 'setting-fields' ); ?>
                        </label>
                        <?php
                        // Add merge tags button next to media buttons via filter
                        $editor_id = $id . '_body';
                        $field_id  = $id;

                        if ( ! empty( $merge_tags ) ) {
                            add_action( 'media_buttons', function ( $eid ) use ( $editor_id, $field_id ) {
                                if ( $eid === $editor_id ) {
                                    echo '<button type="button" class="button setting-fields-insert-tag-btn" data-target="body" data-editor-id="' . esc_attr( $field_id ) . '" title="' . esc_attr__( 'Insert merge tag', 'setting-fields' ) . '">';
                                    echo '<span class="dashicons dashicons-shortcode"></span> ';
                                    echo esc_html__( 'Insert Tag', 'setting-fields' );
                                    echo '</button>';
                                }
                            }, 20 );
                        }

                        wp_editor( $value['body'], $editor_id, [
                                'textarea_name' => $name . '[body]',
                                'textarea_rows' => $field['rows'] ?? 15,
                                'media_buttons' => true,
                                'teeny'         => false,
                                'quicktags'     => true,
                        ] );
                        ?>
                    </div>

                    <!-- Action Buttons -->
                    <?php if ( $show_preview || $show_send_test ) : ?>
                        <div class="setting-fields-email-actions">
                            <?php if ( $show_preview ) : ?>
                                <button type="button" class="button setting-fields-email-preview">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php esc_html_e( 'Preview', 'setting-fields' ); ?>
                                </button>
                            <?php endif; ?>
                            <?php if ( $show_send_test ) : ?>
                                <div class="setting-fields-email-test-wrap">
                                    <input type="email"
                                           class="setting-fields-email-test-input"
                                           placeholder="<?php esc_attr_e( 'test@example.com', 'setting-fields' ); ?>"
                                           value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>"/>
                                    <button type="button" class="button setting-fields-email-send-test">
                                        <span class="dashicons dashicons-email"></span>
                                        <?php esc_html_e( 'Send Test', 'setting-fields' ); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                </div>

                <?php if ( ! empty( $merge_tags ) ) : ?>
                    <!-- Merge Tags Modal -->
                    <div class="setting-fields-merge-tags-modal" style="display: none;">
                        <div class="setting-fields-modal-overlay"></div>
                        <div class="setting-fields-modal-content">
                            <div class="setting-fields-modal-header">
                                <h3><?php esc_html_e( 'Insert Merge Tag', 'setting-fields' ); ?></h3>
                                <button type="button" class="setting-fields-modal-close">&times;</button>
                            </div>
                            <div class="setting-fields-modal-search">
                                <input type="text" class="setting-fields-tag-search"
                                       placeholder="<?php esc_attr_e( 'Search tags...', 'setting-fields' ); ?>"/>
                            </div>
                            <div class="setting-fields-modal-body">
                                <div class="setting-fields-tags-grid">
                                    <?php foreach ( $merge_tags as $tag => $tag_config ) :
                                        $tag_label = is_array( $tag_config ) ? ( $tag_config['label'] ?? $tag ) : $tag_config;
                                        $tag_description = is_array( $tag_config ) ? ( $tag_config['description'] ?? '' ) : '';
                                        $tag_value = is_array( $tag_config ) ? ( $tag_config['tag'] ?? $tag ) : $tag;
                                        ?>
                                        <button type="button"
                                                class="setting-fields-tag-item"
                                                data-tag="<?php echo esc_attr( $tag_value ); ?>"
                                                data-label="<?php echo esc_attr( $tag_label ); ?>"
                                                <?php if ( $tag_description ) : ?>title="<?php echo esc_attr( $tag_description ); ?>"<?php endif; ?>>
                                            <span class="setting-fields-tag-code"><?php echo esc_html( $tag_value ); ?></span>
                                            <span class="setting-fields-tag-label"><?php echo esc_html( $tag_label ); ?></span>
                                            <?php if ( $tag_description ) : ?>
                                                <span class="setting-fields-tag-desc"><?php echo esc_html( $tag_description ); ?></span>
                                            <?php endif; ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div><!-- .setting-fields-email-body-wrap -->
        </div><!-- .setting-fields-email-editor -->
        <?php
    }

    /**
     * Convert email library tags to merge tags format for the editor UI.
     *
     * @param array $template_tags Tags from get_email_template_tags().
     *
     * @return array Merge tags in '{tag}' => ['label' => '', 'description' => ''] format.
     */
    protected function convert_email_tags_to_merge_tags( array $template_tags ): array {
        $merge_tags = [];

        foreach ( $template_tags as $tag ) {
            $tag_key = '{' . ( $tag['name'] ?? '' ) . '}';

            $merge_tags[ $tag_key ] = [
                    'label'       => $tag['label'] ?? $tag['name'] ?? '',
                    'description' => $tag['description'] ?? '',
            ];
        }

        return $merge_tags;
    }

    /**
     * Get default settings from an email template registration.
     *
     * Uses Registry::get_template() then Template::get_settings() which
     * returns defaults when no stored settings exist yet.
     *
     * @param string $group    Email group/prefix.
     * @param string $template Template name.
     *
     * @return array Array with 'enabled', 'subject', 'message' keys.
     */
    protected function get_email_template_defaults( string $group, string $template ): array {
        if ( ! class_exists( '\\ArrayPress\\RegisterEmails\\Registry\\Registry' ) ) {
            return [];
        }

        $registry     = \ArrayPress\RegisterEmails\Registry\Registry::get_instance();
        $template_obj = $registry->get_template( $group, $template );

        if ( ! $template_obj ) {
            return [];
        }

        return $template_obj->get_settings();
    }

    /**
     * Render a sortable list field.
     *
     * @param array  $field Field configuration.
     * @param string $name  Input name.
     * @param string $id    Input id.
     * @param mixed  $value Current value.
     *
     * @return void
     */
    protected function render_sortable( array $field, string $name, string $id, $value ): void {
        $value   = is_array( $value ) ? array_values( $value ) : [];
        $options = $field['options'] ?? [];

        // If options provided, use them; otherwise just render the saved values
        if ( ! empty( $options ) ) {
            // Merge saved order with available options
            $all_items    = [];
            $saved_lookup = array_flip( $value );

            // First add saved items in their order
            foreach ( $value as $v ) {
                if ( isset( $options[ $v ] ) || in_array( $v, $options, true ) ) {
                    $all_items[] = $v;
                }
            }

            // Then add remaining options not yet in list
            foreach ( $options as $key => $label ) {
                $item_value = is_numeric( $key ) ? $label : $key;
                if ( ! in_array( $item_value, $all_items, true ) ) {
                    $all_items[] = $item_value;
                }
            }
        } else {
            $all_items = $value;
        }

        ?>
        <div class="setting-fields-sortable" data-field-id="<?php echo esc_attr( $id ); ?>">
            <ul class="setting-fields-sortable-list">
                <?php foreach ( $all_items as $item ) :
                    $label = $options[ $item ] ?? $item;
                    $is_active = in_array( $item, $value, true );
                    ?>
                    <li class="setting-fields-sortable-item<?php echo $is_active ? ' setting-fields-sortable-item--active' : ''; ?>"
                        data-value="<?php echo esc_attr( $item ); ?>">
                        <span class="setting-fields-sortable-handle dashicons dashicons-menu"></span>
                        <span class="setting-fields-sortable-label"><?php echo esc_html( $label ); ?></span>
                        <input type="hidden"
                               name="<?php echo esc_attr( $name ); ?>[]"
                               value="<?php echo esc_attr( $item ); ?>"
                                <?php echo $is_active ? '' : 'disabled'; ?> />
                        <button type="button" class="setting-fields-sortable-toggle"
                                title="<?php echo $is_active ? esc_attr__( 'Disable', 'setting-fields' ) : esc_attr__( 'Enable', 'setting-fields' ); ?>">
                            <span class="dashicons <?php echo $is_active ? 'dashicons-visibility' : 'dashicons-hidden'; ?>"></span>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    /**
     * Render a custom field using a callback.
     *
     * @param array  $field Field configuration.
     * @param string $name  Input name.
     * @param string $id    Input id.
     * @param mixed  $value Current value.
     *
     * @return void
     */
    protected function render_custom( array $field, string $name, string $id, $value ): void {
        if ( ! empty( $field['callback'] ) && is_callable( $field['callback'] ) ) {
            call_user_func( $field['callback'], $field, $name, $id, $value );
        } elseif ( ! empty( $field['render_callback'] ) && is_callable( $field['render_callback'] ) ) {
            call_user_func( $field['render_callback'], $field, $name, $id, $value );
        } else {
            echo '<p class="description">' . esc_html__( 'Custom field callback not defined.', 'setting-fields' ) . '</p>';
        }
    }

}