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

}
