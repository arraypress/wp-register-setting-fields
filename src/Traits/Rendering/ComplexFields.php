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
						<input type="number"
						       name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $side ); ?>]"
						       id="<?php echo esc_attr( $id ); ?>_<?php echo esc_attr( $side ); ?>"
						       value="<?php echo esc_attr( $value[ $side ] ); ?>"
						       class="small-text"
						       step="<?php echo esc_attr( $field['step'] ?? 1 ); ?>"
							<?php if ( isset( $field['min'] ) ) : ?>min="<?php echo esc_attr( $field['min'] ); ?>"<?php endif; ?>
							<?php if ( isset( $field['max'] ) ) : ?>max="<?php echo esc_attr( $field['max'] ); ?>"<?php endif; ?>
						/>
						<label for="<?php echo esc_attr( $id ); ?>_<?php echo esc_attr( $side ); ?>">
							<?php echo esc_html( $labels[ $side ] ); ?>
						</label>
					</div>
				<?php endforeach; ?>

				<?php if ( count( $units ) > 1 ) : ?>
					<div class="setting-fields-dimension-unit">
						<select name="<?php echo esc_attr( $name ); ?>[unit]" id="<?php echo esc_attr( $id ); ?>_unit">
							<?php foreach ( $units as $unit ) : ?>
								<option value="<?php echo esc_attr( $unit ); ?>" <?php selected( $value['unit'], $unit ); ?>>
									<?php echo esc_html( $unit ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php else : ?>
					<input type="hidden" name="<?php echo esc_attr( $name ); ?>[unit]"
					       value="<?php echo esc_attr( $units[0] ); ?>"/>
					<span class="setting-fields-dimension-unit-label"><?php echo esc_html( $units[0] ); ?></span>
				<?php endif; ?>

				<?php if ( $linked !== false ) : ?>
					<button type="button" class="button-link setting-fields-dimensions-link"
					        title="<?php esc_attr_e( 'Link values', 'setting-fields' ); ?>">
						<span class="dashicons dashicons-admin-links"></span>
					</button>
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
				<hr class="setting-fields-separator-line" />
				<?php if ( ! empty( $title ) ) : ?>
					<span class="setting-fields-separator-title"><?php echo esc_html( $title ); ?></span>
					<hr class="setting-fields-separator-line" />
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
			<<?php echo $level; ?> class="setting-fields-heading-title"><?php echo esc_html( $title ); ?></<?php echo $level; ?>>
			<?php if ( ! empty( $description ) ) : ?>
				<p class="setting-fields-heading-description"><?php echo wp_kses_post( $description ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render an email editor field with merge tags.
	 *
	 * Supports:
	 * - Optional enable/disable toggle
	 * - Subject line
	 * - TinyMCE body editor
	 * - Merge tags with modal picker (searchable)
	 * - Preview callback
	 * - Send test callback
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_email_editor( array $field, string $name, string $id, $value ): void {
		$value = wp_parse_args( (array) $value, [
			'enabled' => $field['default_enabled'] ?? true,
			'subject' => $field['default_subject'] ?? '',
			'body'    => $field['default_body'] ?? '',
		] );

		$merge_tags       = $field['merge_tags'] ?? [];
		$show_enable      = $field['show_enable'] ?? false;
		$show_preview     = $field['show_preview'] ?? true;
		$show_send_test   = $field['show_send_test'] ?? true;
		$preview_callback = $field['preview_callback'] ?? null;
		$send_callback    = $field['send_callback'] ?? null;

		// Build data attributes for JS callbacks
		$data_attrs = [
			'field-id' => $id,
			'field-key' => $field['_key'] ?? '',
		];

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

		?>
		<div class="setting-fields-email-editor"<?php echo $data_string; ?>>
			
			<?php if ( $show_enable ) : ?>
				<!-- Enable/Disable Toggle -->
				<div class="setting-fields-email-enable">
					<label class="setting-fields-toggle-wrap">
						<input type="hidden" name="<?php echo esc_attr( $name ); ?>[enabled]" value="0" />
						<input type="checkbox"
						       name="<?php echo esc_attr( $name ); ?>[enabled]"
						       id="<?php echo esc_attr( $id ); ?>_enabled"
						       value="1"
						       class="setting-fields-email-enable-checkbox"
							<?php checked( $value['enabled'], true ); ?> />
						<span class="setting-fields-toggle-slider"></span>
						<span class="setting-fields-toggle-label"><?php esc_html_e( 'Enable this email', 'setting-fields' ); ?></span>
					</label>
				</div>
			<?php endif; ?>

			<div class="setting-fields-email-content<?php echo $show_enable && ! $value['enabled'] ? ' setting-fields-email-disabled' : ''; ?>">
				
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
						       class="large-text setting-fields-email-subject-input" />
						<?php if ( ! empty( $merge_tags ) ) : ?>
							<button type="button" class="button setting-fields-insert-tag-btn" data-target="subject" title="<?php esc_attr_e( 'Insert merge tag', 'setting-fields' ); ?>">
								<span class="dashicons dashicons-shortcode"></span>
							</button>
						<?php endif; ?>
					</div>
				</div>

				<!-- Body Editor -->
				<div class="setting-fields-email-body">
					<div class="setting-fields-email-body-header">
						<label for="<?php echo esc_attr( $id ); ?>_body">
							<?php esc_html_e( 'Message', 'setting-fields' ); ?>
						</label>
						<?php if ( ! empty( $merge_tags ) ) : ?>
							<button type="button" class="button setting-fields-insert-tag-btn" data-target="body" title="<?php esc_attr_e( 'Insert merge tag', 'setting-fields' ); ?>">
								<span class="dashicons dashicons-shortcode"></span>
								<?php esc_html_e( 'Insert Tag', 'setting-fields' ); ?>
							</button>
						<?php endif; ?>
					</div>
					<?php
					wp_editor( $value['body'], $id . '_body', [
						'textarea_name' => $name . '[body]',
						'textarea_rows' => $field['rows'] ?? 15,
						'media_buttons' => $field['media_buttons'] ?? false,
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
							<button type="button" class="button setting-fields-email-send-test">
								<span class="dashicons dashicons-email"></span>
								<?php esc_html_e( 'Send Test Email', 'setting-fields' ); ?>
							</button>
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
							<input type="text" class="setting-fields-tag-search" placeholder="<?php esc_attr_e( 'Search tags...', 'setting-fields' ); ?>" />
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
									        data-label="<?php echo esc_attr( $tag_label ); ?>">
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
		</div>
		<?php
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
					$label     = $options[ $item ] ?? $item;
					$is_active = in_array( $item, $value, true );
					?>
					<li class="setting-fields-sortable-item<?php echo $is_active ? ' setting-fields-sortable-item--active' : ''; ?>" data-value="<?php echo esc_attr( $item ); ?>">
						<span class="setting-fields-sortable-handle dashicons dashicons-menu"></span>
						<span class="setting-fields-sortable-label"><?php echo esc_html( $label ); ?></span>
						<input type="hidden"
						       name="<?php echo esc_attr( $name ); ?>[]"
						       value="<?php echo esc_attr( $item ); ?>"
							<?php echo $is_active ? '' : 'disabled'; ?> />
						<button type="button" class="setting-fields-sortable-toggle" title="<?php echo $is_active ? esc_attr__( 'Disable', 'setting-fields' ) : esc_attr__( 'Enable', 'setting-fields' ); ?>">
							<span class="dashicons <?php echo $is_active ? 'dashicons-visibility' : 'dashicons-hidden'; ?>"></span>
						</button>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

}
