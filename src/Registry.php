<?php
/**
 * Setting Fields Registry
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields;

/**
 * Class Registry
 *
 * Singleton registry for managing multiple settings pages.
 */
class Registry {

	/**
	 * Singleton instance.
	 *
	 * @var Registry|null
	 */
	private static ?Registry $instance = null;

	/**
	 * Registered settings pages.
	 *
	 * @var array<string, SettingFields>
	 */
	private array $settings = [];

	/**
	 * Get singleton instance.
	 *
	 * @return Registry
	 */
	public static function instance(): Registry {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor for singleton.
	 */
	private function __construct() {
	}

	/**
	 * Register a new settings page.
	 *
	 * @param string $id     Unique identifier.
	 * @param array  $config Configuration array.
	 *
	 * @return SettingFields
	 */
	public function register( string $id, array $config ): SettingFields {
		$settings = new SettingFields( $id, $config );
		$this->settings[ $id ] = $settings;

		return $settings;
	}

	/**
	 * Get a registered settings page.
	 *
	 * @param string $id Settings ID.
	 *
	 * @return SettingFields|null
	 */
	public function get( string $id ): ?SettingFields {
		return $this->settings[ $id ] ?? null;
	}

	/**
	 * Check if a settings page is registered.
	 *
	 * @param string $id Settings ID.
	 *
	 * @return bool
	 */
	public function has( string $id ): bool {
		return isset( $this->settings[ $id ] );
	}

	/**
	 * Get all registered settings pages.
	 *
	 * @return array<string, SettingFields>
	 */
	public function all(): array {
		return $this->settings;
	}

	/**
	 * Unregister a settings page.
	 *
	 * @param string $id Settings ID.
	 *
	 * @return bool
	 */
	public function unregister( string $id ): bool {
		if ( isset( $this->settings[ $id ] ) ) {
			unset( $this->settings[ $id ] );

			return true;
		}

		return false;
	}

}
