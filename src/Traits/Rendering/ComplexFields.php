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
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_email_editor( array $field, string $name, string $id, $value ): void {
		$value = wp_parse_args( (array) $value, [
			'subject' => $field['default_subject'] ?? '',
			'body'    => $field['default_body'] ?? '',
		] );

		$merge_tags     = $field['merge_tags'] ?? [];
		$show_preview   = $field['show_preview'] ?? true;
		$show_send_test = $field['show_send_test'] ?? true;

		?>
		<div class="setting-fields-email-editor" data-field-id="<?php echo esc_attr( $id ); ?>">
			
			<!-- Subject Line -->
			<div class="setting-fields-email-subject">
				<label for="<?php echo esc_attr( $id ); ?>_subject">
					<?php esc_html_e( 'Subject', 'setting-fields' ); ?>
				</label>
				<input type="text"
				       name="<?php echo esc_attr( $name ); ?>[subject]"
				       id="<?php echo esc_attr( $id ); ?>_subject"
				       value="<?php echo esc_attr( $value['subject'] ); ?>"
				       class="large-text" />
			</div>

			<!-- Body Editor -->
			<div class="setting-fields-email-body">
				<label for="<?php echo esc_attr( $id ); ?>_body">
					<?php esc_html_e( 'Message', 'setting-fields' ); ?>
				</label>
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

			<!-- Merge Tags -->
			<?php if ( ! empty( $merge_tags ) ) : ?>
				<div class="setting-fields-email-merge-tags">
					<p class="setting-fields-merge-tags-label">
						<strong><?php esc_html_e( 'Available Merge Tags:', 'setting-fields' ); ?></strong>
						<span class="setting-fields-merge-tags-help"><?php esc_html_e( 'Click a tag to insert it into the editor.', 'setting-fields' ); ?></span>
					</p>
					<div class="setting-fields-merge-tags-list">
						<?php foreach ( $merge_tags as $tag => $label ) : ?>
							<button type="button" 
							        class="setting-fields-merge-tag button-secondary" 
							        data-tag="<?php echo esc_attr( $tag ); ?>"
							        data-editor="<?php echo esc_attr( $id ); ?>_body"
							        title="<?php echo esc_attr( $label ); ?>">
								<code><?php echo esc_html( $tag ); ?></code>
								<span><?php echo esc_html( $label ); ?></span>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<!-- Action Buttons -->
			<?php if ( $show_preview || $show_send_test ) : ?>
				<div class="setting-fields-email-actions">
					<?php if ( $show_preview ) : ?>
						<button type="button" class="button setting-fields-email-preview" data-field-id="<?php echo esc_attr( $id ); ?>">
							<span class="dashicons dashicons-visibility"></span>
							<?php esc_html_e( 'Preview', 'setting-fields' ); ?>
						</button>
					<?php endif; ?>
					<?php if ( $show_send_test ) : ?>
						<button type="button" class="button setting-fields-email-send-test" data-field-id="<?php echo esc_attr( $id ); ?>">
							<span class="dashicons dashicons-email"></span>
							<?php esc_html_e( 'Send Test Email', 'setting-fields' ); ?>
						</button>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

}
