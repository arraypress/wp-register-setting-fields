<?php
/**
 * Nested Fields Rendering Trait
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Traits\Rendering;

/**
 * Trait NestedFields
 *
 * Renders nested/grouped field types.
 */
trait NestedFields {

	/**
	 * Render a group field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_group( array $field, string $name, string $id, $value ): void {
		$sub_fields  = $field['sub_fields'] ?? [];
		$value       = is_array( $value ) ? $value : [];
		$layout      = $field['layout'] ?? 'block'; // block, row, table
		$collapsible = $field['collapsible'] ?? false;
		$collapsed   = $field['collapsed'] ?? false; // Start collapsed
		$title       = $field['title'] ?? $field['label'] ?? '';
		$description = $field['group_description'] ?? '';

		$class = 'setting-fields-group';
		if ( $layout === 'row' ) {
			$class .= ' setting-fields-group--row';
		} elseif ( $layout === 'table' ) {
			$class .= ' setting-fields-group--table';
		}

		if ( $collapsible ) {
			$class .= ' setting-fields-group--collapsible';
			if ( $collapsed ) {
				$class .= ' setting-fields-group--collapsed';
			}
		}

		?>
		<div class="<?php echo esc_attr( $class ); ?>" data-collapsible="<?php echo $collapsible ? 'true' : 'false'; ?>">
			<?php if ( $collapsible ) : ?>
				<div class="setting-fields-group-header">
					<div class="setting-fields-group-header-content">
						<?php if ( $title ) : ?>
							<h4 class="setting-fields-group-title"><?php echo esc_html( $title ); ?></h4>
						<?php endif; ?>
						<?php if ( $description ) : ?>
							<p class="setting-fields-group-description"><?php echo esc_html( $description ); ?></p>
						<?php endif; ?>
					</div>
					<button type="button" class="setting-fields-group-toggle">
						<span class="dashicons dashicons-arrow-<?php echo $collapsed ? 'down' : 'up'; ?>-alt2"></span>
					</button>
				</div>
			<?php endif; ?>

			<div class="setting-fields-group-content">
				<?php if ( $layout === 'table' ) : ?>
				<table class="form-table">
					<?php endif; ?>

					<?php foreach ( $sub_fields as $sub_key => $sub_field ) :
						$sub_name  = $name . '[' . $sub_key . ']';
						$sub_id    = $id . '_' . $sub_key;
						$sub_value = $value[ $sub_key ] ?? ( $sub_field['default'] ?? '' );
						?>

						<?php if ( $layout === 'table' ) : ?>
						<tr>
							<th scope="row">
								<?php if ( ! empty( $sub_field['label'] ) ) : ?>
									<label for="<?php echo esc_attr( $sub_id ); ?>">
										<?php echo esc_html( $sub_field['label'] ); ?>
									</label>
								<?php endif; ?>
							</th>
							<td>
								<?php
								$this->render_field( $sub_key, $sub_field, $sub_name, $sub_id, $sub_value );
								if ( ! empty( $sub_field['description'] ) ) {
									echo '<p class="description">' . wp_kses_post( $sub_field['description'] ) . '</p>';
								}
								?>
							</td>
						</tr>
					<?php else : ?>
						<div class="setting-fields-group-field">
							<?php if ( ! empty( $sub_field['label'] ) ) : ?>
								<label for="<?php echo esc_attr( $sub_id ); ?>" class="setting-fields-group-label">
									<?php echo esc_html( $sub_field['label'] ); ?>
								</label>
							<?php endif; ?>

							<div class="setting-fields-group-input">
								<?php $this->render_field( $sub_key, $sub_field, $sub_name, $sub_id, $sub_value ); ?>
							</div>

							<?php if ( ! empty( $sub_field['description'] ) ) : ?>
								<p class="description"><?php echo wp_kses_post( $sub_field['description'] ); ?></p>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php endforeach; ?>

					<?php if ( $layout === 'table' ) : ?>
				</table>
			<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a repeater field.
	 *
	 * @param array  $field Field configuration.
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 *
	 * @return void
	 */
	protected function render_repeater( array $field, string $name, string $id, $value ): void {
		$sub_fields    = $field['sub_fields'] ?? [];
		$value         = is_array( $value ) ? $value : [];
		$min           = $field['min'] ?? 0;
		$max           = $field['max'] ?? 0;
		$layout        = $field['layout'] ?? 'table'; // table, block, row
		$button_label  = $field['button_label'] ?? __( 'Add Row', 'setting-fields' );
		$collapsed     = $field['collapsed'] ?? false;
		$sortable      = $field['sortable'] ?? true;

		$class = 'setting-fields-repeater';
		$class .= ' setting-fields-repeater--' . $layout;

		?>
		<div class="<?php echo esc_attr( $class ); ?>"
		     data-min="<?php echo esc_attr( $min ); ?>"
		     data-max="<?php echo esc_attr( $max ); ?>"
		     data-name="<?php echo esc_attr( $name ); ?>"
		     data-id="<?php echo esc_attr( $id ); ?>"
		     data-sortable="<?php echo $sortable ? 'true' : 'false'; ?>">

			<?php if ( $layout === 'table' ) : ?>
				<table class="setting-fields-repeater-table widefat">
					<thead>
					<tr>
						<?php if ( $sortable ) : ?>
							<th class="setting-fields-repeater-sort-col"></th>
						<?php endif; ?>
						<?php foreach ( $sub_fields as $sub_key => $sub_field ) : ?>
							<th><?php echo esc_html( $sub_field['label'] ?? ucfirst( $sub_key ) ); ?></th>
						<?php endforeach; ?>
						<th class="setting-fields-repeater-actions-col"></th>
					</tr>
					</thead>
					<tbody class="setting-fields-repeater-rows">
					<?php if ( empty( $value ) ) : ?>
						<tr class="setting-fields-repeater-empty">
							<td colspan="<?php echo count( $sub_fields ) + ( $sortable ? 2 : 1 ); ?>">
								<?php esc_html_e( 'No items yet. Click the button below to add one.', 'setting-fields' ); ?>
							</td>
						</tr>
					<?php endif; ?>
					<?php
					foreach ( $value as $index => $row ) {
						$this->render_repeater_row_table( $sub_fields, $name, $id, $index, $row, $sortable );
					}
					?>
					</tbody>
				</table>
			<?php else : ?>
				<div class="setting-fields-repeater-rows">
					<?php
					foreach ( $value as $index => $row ) {
						$this->render_repeater_row_block( $sub_fields, $name, $id, $index, $row, $layout, $collapsed, $sortable );
					}
					?>
				</div>
			<?php endif; ?>

			<div class="setting-fields-repeater-footer">
				<button type="button" class="button setting-fields-repeater-add">
					<?php echo esc_html( $button_label ); ?>
				</button>
			</div>

			<!-- Row template for JS -->
			<script type="text/html" class="setting-fields-repeater-template">
				<?php
				if ( $layout === 'table' ) {
					$this->render_repeater_row_table( $sub_fields, $name, $id, '{{INDEX}}', [], $sortable );
				} else {
					$this->render_repeater_row_block( $sub_fields, $name, $id, '{{INDEX}}', [], $layout, $collapsed, $sortable );
				}
				?>
			</script>
		</div>
		<?php
	}

	/**
	 * Render a repeater row in table layout.
	 *
	 * @param array  $sub_fields Sub-fields configuration.
	 * @param string $name       Base input name.
	 * @param string $id         Base input id.
	 * @param mixed  $index      Row index.
	 * @param array  $row        Row values.
	 * @param bool   $sortable   Whether rows are sortable.
	 *
	 * @return void
	 */
	protected function render_repeater_row_table( array $sub_fields, string $name, string $id, $index, array $row, bool $sortable ): void {
		?>
		<tr class="setting-fields-repeater-row" data-index="<?php echo esc_attr( $index ); ?>">
			<?php if ( $sortable ) : ?>
				<td class="setting-fields-repeater-sort">
					<span class="dashicons dashicons-menu"></span>
				</td>
			<?php endif; ?>

			<?php foreach ( $sub_fields as $sub_key => $sub_field ) :
				$sub_name  = $name . '[' . $index . '][' . $sub_key . ']';
				$sub_id    = $id . '_' . $index . '_' . $sub_key;
				$sub_value = $row[ $sub_key ] ?? ( $sub_field['default'] ?? '' );
				?>
				<td class="setting-fields-repeater-cell">
					<?php $this->render_field( $sub_key, $sub_field, $sub_name, $sub_id, $sub_value ); ?>
				</td>
			<?php endforeach; ?>

			<td class="setting-fields-repeater-actions">
				<button type="button" class="button-link setting-fields-repeater-remove"
				        title="<?php esc_attr_e( 'Remove', 'setting-fields' ); ?>">
					<span class="dashicons dashicons-trash"></span>
				</button>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a repeater row in block layout.
	 *
	 * @param array  $sub_fields Sub-fields configuration.
	 * @param string $name       Base input name.
	 * @param string $id         Base input id.
	 * @param mixed  $index      Row index.
	 * @param array  $row        Row values.
	 * @param string $layout     Layout type (block or row).
	 * @param bool   $collapsed  Whether to show collapsed.
	 * @param bool   $sortable   Whether rows are sortable.
	 *
	 * @return void
	 */
	protected function render_repeater_row_block( array $sub_fields, string $name, string $id, $index, array $row, string $layout, bool $collapsed, bool $sortable ): void {
		$row_class = 'setting-fields-repeater-row setting-fields-repeater-row--' . $layout;
		if ( $collapsed ) {
			$row_class .= ' setting-fields-repeater-row--collapsed';
		}
		?>
		<div class="<?php echo esc_attr( $row_class ); ?>" data-index="<?php echo esc_attr( $index ); ?>">
			<div class="setting-fields-repeater-row-header">
				<?php if ( $sortable ) : ?>
					<span class="setting-fields-repeater-sort dashicons dashicons-menu"></span>
				<?php endif; ?>

				<?php if ( $collapsed ) : ?>
					<span class="setting-fields-repeater-row-title">
                        <?php echo esc_html( sprintf( __( 'Row %s', 'setting-fields' ), (int) $index + 1 ) ); ?>
                    </span>
					<button type="button" class="button-link setting-fields-repeater-toggle">
						<span class="dashicons dashicons-arrow-down-alt2"></span>
					</button>
				<?php endif; ?>

				<button type="button" class="button-link setting-fields-repeater-remove"
				        title="<?php esc_attr_e( 'Remove', 'setting-fields' ); ?>">
					<span class="dashicons dashicons-trash"></span>
				</button>
			</div>

			<div class="setting-fields-repeater-row-content">
				<?php foreach ( $sub_fields as $sub_key => $sub_field ) :
					$sub_name  = $name . '[' . $index . '][' . $sub_key . ']';
					$sub_id    = $id . '_' . $index . '_' . $sub_key;
					$sub_value = $row[ $sub_key ] ?? ( $sub_field['default'] ?? '' );
					?>
					<div class="setting-fields-repeater-field">
						<?php if ( ! empty( $sub_field['label'] ) ) : ?>
							<label for="<?php echo esc_attr( $sub_id ); ?>" class="setting-fields-repeater-label">
								<?php echo esc_html( $sub_field['label'] ); ?>
							</label>
						<?php endif; ?>

						<div class="setting-fields-repeater-input">
							<?php $this->render_field( $sub_key, $sub_field, $sub_name, $sub_id, $sub_value ); ?>
						</div>

						<?php if ( ! empty( $sub_field['description'] ) ) : ?>
							<p class="description"><?php echo wp_kses_post( $sub_field['description'] ); ?></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

}
