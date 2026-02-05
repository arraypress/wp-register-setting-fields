<?php
/**
 * Tab Manager Trait
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Traits;

/**
 * Trait TabManager
 *
 * Handles tab navigation and rendering.
 */
trait TabManager {

	/**
	 * Get the current active tab.
	 *
	 * @return string
	 */
	protected function get_current_tab(): string {
		if ( empty( $this->tabs ) ) {
			return '';
		}

		$current = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : '';

		// Validate tab exists
		if ( ! empty( $current ) && isset( $this->tabs[ $current ] ) ) {
			return $current;
		}

		// Return first tab as default
		return array_key_first( $this->tabs );
	}

	/**
	 * Get the URL for a specific tab.
	 *
	 * @param string $tab Tab key.
	 *
	 * @return string
	 */
	protected function get_tab_url( string $tab ): string {
		return add_query_arg( [
			'page' => $this->config['menu_slug'],
			'tab'  => $tab,
		], admin_url( 'admin.php' ) );
	}

	/**
	 * Render the tab navigation.
	 *
	 * @param string $current_tab Currently active tab.
	 *
	 * @return void
	 */
	protected function render_tabs( string $current_tab ): void {
		if ( empty( $this->tabs ) ) {
			return;
		}

		echo '<nav class="setting-fields-tabs-nav">';

		foreach ( $this->tabs as $tab_key => $tab ) {
			$active_class = ( $tab_key === $current_tab ) ? ' setting-fields-tab-active' : '';
			$url          = $this->get_tab_url( $tab_key );

			printf(
				'<a href="%s" class="setting-fields-tab%s">%s%s</a>',
				esc_url( $url ),
				esc_attr( $active_class ),
				! empty( $tab['icon'] ) ? '<span class="dashicons ' . esc_attr( $tab['icon'] ) . '"></span> ' : '',
				esc_html( $tab['label'] )
			);
		}

		echo '</nav>';
	}

	/**
	 * Check if the current page has multiple tabs.
	 *
	 * @return bool
	 */
	protected function has_tabs(): bool {
		return ! empty( $this->tabs );
	}

	/**
	 * Get all tabs.
	 *
	 * @return array
	 */
	public function get_tabs(): array {
		return $this->tabs;
	}

	/**
	 * Get a specific tab configuration.
	 *
	 * @param string $tab Tab key.
	 *
	 * @return array|null
	 */
	public function get_tab( string $tab ): ?array {
		return $this->tabs[ $tab ] ?? null;
	}

}
