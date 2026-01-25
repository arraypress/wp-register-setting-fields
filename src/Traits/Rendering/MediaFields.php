<?php
/**
 * Media Fields Rendering Trait
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Traits\Rendering;

/**
 * Trait MediaFields
 *
 * Renders media-related field types.
 */
trait MediaFields {

	/**
	 * Render an image upload field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value (attachment ID).
	 *
	 * @return void
	 */
	protected function render_image( array $field, string $name, string $id, $value ): void {
		$value         = absint( $value );
		$preview_size  = $field['preview_size'] ?? 'thumbnail';
		$library       = $field['library'] ?? 'image';
		$return_format = $field['return_format'] ?? 'id';

		// Get image URL if we have a value
		$image_url = '';
		if ( $value > 0 ) {
			$image = wp_get_attachment_image_src( $value, $preview_size );
			if ( $image ) {
				$image_url = $image[0];
			}
		}

		$has_image = ! empty( $image_url );
		?>
		<div class="setting-fields-image-field" data-library="<?php echo esc_attr( $library ); ?>">
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>"
			       value="<?php echo esc_attr( $value ); ?>" class="setting-fields-image-value"/>

			<div class="setting-fields-image-preview <?php echo $has_image ? '' : 'hidden'; ?>">
				<img src="<?php echo esc_url( $image_url ); ?>" alt=""/>
			</div>

			<div class="setting-fields-image-actions">
				<button type="button"
				        class="button setting-fields-image-select <?php echo $has_image ? 'hidden' : ''; ?>">
					<?php esc_html_e( 'Select Image', 'setting-fields' ); ?>
				</button>
				<button type="button"
				        class="button setting-fields-image-change <?php echo $has_image ? '' : 'hidden'; ?>">
					<?php esc_html_e( 'Change Image', 'setting-fields' ); ?>
				</button>
				<button type="button"
				        class="button setting-fields-image-remove <?php echo $has_image ? '' : 'hidden'; ?>">
					<?php esc_html_e( 'Remove', 'setting-fields' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a file upload field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value (attachment ID).
	 *
	 * @return void
	 */
	protected function render_file( array $field, string $name, string $id, $value ): void {
		$value         = absint( $value );
		$library       = $field['library'] ?? 'all';
		$allowed_types = $field['allowed_types'] ?? '';

		// Get file info if we have a value
		$file_name = '';
		$file_url  = '';
		if ( $value > 0 ) {
			$file_url  = wp_get_attachment_url( $value );
			$file_name = basename( get_attached_file( $value ) );
		}

		$has_file = ! empty( $file_url );
		?>
		<div class="setting-fields-file-field" data-library="<?php echo esc_attr( $library ); ?>"
		     data-allowed-types="<?php echo esc_attr( $allowed_types ); ?>">
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>"
			       value="<?php echo esc_attr( $value ); ?>" class="setting-fields-file-value"/>

			<div class="setting-fields-file-preview <?php echo $has_file ? '' : 'hidden'; ?>">
				<span class="dashicons dashicons-media-default"></span>
				<a href="<?php echo esc_url( $file_url ); ?>" target="_blank"
				   class="setting-fields-file-name"><?php echo esc_html( $file_name ); ?></a>
			</div>

			<div class="setting-fields-file-actions">
				<button type="button"
				        class="button setting-fields-file-select <?php echo $has_file ? 'hidden' : ''; ?>">
					<?php esc_html_e( 'Select File', 'setting-fields' ); ?>
				</button>
				<button type="button"
				        class="button setting-fields-file-change <?php echo $has_file ? '' : 'hidden'; ?>">
					<?php esc_html_e( 'Change File', 'setting-fields' ); ?>
				</button>
				<button type="button"
				        class="button setting-fields-file-remove <?php echo $has_file ? '' : 'hidden'; ?>">
					<?php esc_html_e( 'Remove', 'setting-fields' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a gallery field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value (array of attachment IDs).
	 *
	 * @return void
	 */
	protected function render_gallery( array $field, string $name, string $id, $value ): void {
		$values       = is_array( $value ) ? array_filter( array_map( 'absint', $value ) ) : [];
		$preview_size = $field['preview_size'] ?? 'thumbnail';
		$max_items    = $field['max'] ?? 0;
		$min_items    = $field['min'] ?? 0;
		?>
		<div class="setting-fields-gallery-field" data-max="<?php echo esc_attr( $max_items ); ?>"
		     data-min="<?php echo esc_attr( $min_items ); ?>">
			<div class="setting-fields-gallery-items">
				<?php foreach ( $values as $attachment_id ) :
					$image = wp_get_attachment_image_src( $attachment_id, $preview_size );
					if ( ! $image ) {
						continue;
					}
					?>
					<div class="setting-fields-gallery-item" data-id="<?php echo esc_attr( $attachment_id ); ?>">
						<img src="<?php echo esc_url( $image[0] ); ?>" alt=""/>
						<input type="hidden" name="<?php echo esc_attr( $name ); ?>[]"
						       value="<?php echo esc_attr( $attachment_id ); ?>"/>
						<button type="button" class="setting-fields-gallery-remove">
							<span class="dashicons dashicons-no-alt"></span>
						</button>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="setting-fields-gallery-actions">
				<button type="button" class="button setting-fields-gallery-add">
					<?php esc_html_e( 'Add Images', 'setting-fields' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Render an oEmbed preview field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value (URL).
	 *
	 * @return void
	 */
	protected function render_oembed( array $field, string $name, string $id, $value ): void {
		$value = esc_url( $value );

		$extra = [
			'type'  => 'url',
			'value' => $value,
			'class' => 'regular-text setting-fields-oembed-input ' . ( $field['class'] ?? '' ),
		];

		$attrs = $this->build_input_attrs( $field, $name, $id, $extra );
		?>
		<div class="setting-fields-oembed-field">
			<input<?php echo $attrs; ?> />

			<div class="setting-fields-oembed-preview">
				<?php
				if ( ! empty( $value ) ) {
					$embed = wp_oembed_get( $value );
					if ( $embed ) {
						echo $embed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
				}
				?>
			</div>
		</div>
		<?php
	}

}
