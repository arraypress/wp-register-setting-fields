<?php
/**
 * Encryption Trait
 *
 * Provides transparent encryption/decryption for sensitive field values.
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Traits;

use Exception;
use RuntimeException;

/**
 * Trait Encryption
 *
 * Handles field-level encryption with constant fallback support.
 */
trait Encryption {

	/**
	 * Encryption algorithm.
	 *
	 * @var string
	 */
	private string $encryption_algorithm = 'aes-256-cbc';

	/**
	 * Encryption key (derived).
	 *
	 * @var string|null
	 */
	private ?string $encryption_key = null;

	/**
	 * Prefix for encrypted values to identify them.
	 *
	 * @var string
	 */
	private string $encryption_prefix = '';

	/**
	 * Whether encryption is enabled.
	 *
	 * @var bool
	 */
	private bool $encryption_enabled = false;

	/**
	 * Cache of decrypted values to avoid repeated decryption.
	 *
	 * @var array
	 */
	private array $decrypted_cache = [];

	/**
	 * Initialize encryption support.
	 *
	 * @param array $config Encryption configuration.
	 *
	 * @return void
	 */
	protected function init_encryption( array $config ): void {
		$encryption_config = $config['encryption'] ?? [];

		// Check if any fields have encryption enabled
		$has_encrypted_fields = $this->has_encrypted_fields();

		// Enable encryption if explicitly configured OR if any fields are marked encrypted
		$this->encryption_enabled = ( $encryption_config['enabled'] ?? $has_encrypted_fields );

		if ( ! $this->encryption_enabled ) {
			return;
		}

		// Validate environment
		$this->validate_encryption_environment();

		// Build the encryption prefix from settings ID (ensure we have a valid prefix)
		$prefix = ! empty( $encryption_config['prefix'] ) ? $encryption_config['prefix'] : $this->id;
		$this->encryption_prefix = $this->build_encryption_prefix( $prefix );

		// Get or derive the encryption key
		$custom_key           = $encryption_config['key'] ?? null;
		$this->encryption_key = $custom_key
			? hash( 'sha256', $custom_key, true )
			: $this->get_wordpress_encryption_key();
	}

	/**
	 * Check if any fields have encryption enabled.
	 *
	 * @return bool
	 */
	protected function has_encrypted_fields(): bool {
		foreach ( $this->fields as $field ) {
			if ( ! empty( $field['encrypted'] ) ) {
				return true;
			}

			// Check sub-fields in groups/repeaters
			if ( ! empty( $field['sub_fields'] ) ) {
				foreach ( $field['sub_fields'] as $sub_field ) {
					if ( ! empty( $sub_field['encrypted'] ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Build the encryption prefix for identifying encrypted values.
	 *
	 * @param string $prefix Base prefix.
	 *
	 * @return string
	 */
	protected function build_encryption_prefix( string $prefix ): string {
		$sanitized = preg_replace( '/[^a-z0-9_]/', '', strtolower( $prefix ) );

		return '$ENC$' . strtoupper( $sanitized ) . '$';
	}

	/**
	 * Validate that the environment supports encryption.
	 *
	 * @return void
	 * @throws RuntimeException If OpenSSL is not available.
	 */
	protected function validate_encryption_environment(): void {
		if ( ! extension_loaded( 'openssl' ) ) {
			throw new RuntimeException(
				'OpenSSL extension is required for field encryption. Please enable it in your PHP configuration.'
			);
		}

		if ( ! in_array( $this->encryption_algorithm, openssl_get_cipher_methods(), true ) ) {
			throw new RuntimeException(
				"Encryption algorithm '{$this->encryption_algorithm}' is not supported by your OpenSSL installation."
			);
		}
	}

	/**
	 * Get the WordPress-based encryption key.
	 *
	 * @return string
	 * @throws RuntimeException If no key source is available.
	 */
	protected function get_wordpress_encryption_key(): string {
		// Check for custom encryption key constant first
		if ( defined( 'WP_ENCRYPTION_KEY' ) && ! empty( constant( 'WP_ENCRYPTION_KEY' ) ) ) {
			return hash( 'sha256', constant( 'WP_ENCRYPTION_KEY' ), true );
		}

		// Fall back to WordPress salts
		$salts = [
			defined( 'AUTH_KEY' ) ? AUTH_KEY : '',
			defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : '',
			defined( 'LOGGED_IN_KEY' ) ? LOGGED_IN_KEY : '',
			defined( 'NONCE_KEY' ) ? NONCE_KEY : '',
		];

		$combined = implode( '', $salts );

		// Try wp_salt() as last resort
		if ( empty( $combined ) && function_exists( 'wp_salt' ) ) {
			$combined = wp_salt() . wp_salt( 'secure_auth' );
		}

		if ( empty( $combined ) ) {
			throw new RuntimeException(
				'Cannot generate encryption key: WordPress salts not available. ' .
				'Consider defining WP_ENCRYPTION_KEY in wp-config.php.'
			);
		}

		return hash( 'sha256', $combined, true );
	}

	/**
	 * Encrypt a value.
	 *
	 * @param string $value Value to encrypt.
	 *
	 * @return string|null Encrypted value with prefix, or null on failure.
	 */
	protected function encrypt_value( string $value ): ?string {
		if ( empty( $value ) || ! $this->encryption_enabled ) {
			return $value;
		}

		// Don't double-encrypt
		if ( $this->is_encrypted_value( $value ) ) {
			return $value;
		}

		try {
			$iv_length = openssl_cipher_iv_length( $this->encryption_algorithm );
			$iv        = random_bytes( $iv_length );

			$encrypted = openssl_encrypt(
				$value,
				$this->encryption_algorithm,
				$this->encryption_key,
				OPENSSL_RAW_DATA,
				$iv
			);

			if ( $encrypted === false ) {
				return null;
			}

			return $this->encryption_prefix . base64_encode( $iv . $encrypted );
		} catch ( Exception $e ) {
			// Log error but don't expose details
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Setting Fields Encryption Error: ' . $e->getMessage() );
			}

			return null;
		}
	}

	/**
	 * Decrypt a value.
	 *
	 * @param string $value Value to decrypt.
	 *
	 * @return string|null Decrypted value, or null on failure.
	 */
	protected function decrypt_value( string $value ): ?string {
		if ( empty( $value ) ) {
			return $value;
		}

		// Check if value is actually encrypted
		if ( ! $this->is_encrypted_value( $value ) ) {
			return $value;
		}

		// Ensure we have an encryption key (lazy initialization)
		if ( $this->encryption_key === null ) {
			try {
				$this->encryption_key = $this->get_wordpress_encryption_key();
			} catch ( Exception $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Setting Fields: Cannot decrypt - no encryption key: ' . $e->getMessage() );
				}
				return null;
			}
		}

		// Check cache first
		$cache_key = md5( $value );
		if ( isset( $this->decrypted_cache[ $cache_key ] ) ) {
			return $this->decrypted_cache[ $cache_key ];
		}

		try {
			// Extract the base64 data (removes any $ENC$PREFIX$ pattern)
			$encrypted_data = $this->extract_encrypted_data( $value );
			$data           = base64_decode( $encrypted_data );

			if ( $data === false ) {
				return null;
			}

			$iv_length = openssl_cipher_iv_length( $this->encryption_algorithm );
			$iv        = substr( $data, 0, $iv_length );
			$encrypted = substr( $data, $iv_length );

			$decrypted = openssl_decrypt(
				$encrypted,
				$this->encryption_algorithm,
				$this->encryption_key,
				OPENSSL_RAW_DATA,
				$iv
			);

			if ( $decrypted === false ) {
				return null;
			}

			// Cache the result
			$this->decrypted_cache[ $cache_key ] = $decrypted;

			return $decrypted;
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Setting Fields Decryption Error: ' . $e->getMessage() );
			}

			return null;
		}
	}

	/**
	 * Check if a value is encrypted.
	 *
	 * Detects any value with the $ENC$..$ pattern, not just the current prefix.
	 * This ensures decryption works even if the settings ID changes.
	 *
	 * @param string $value Value to check.
	 *
	 * @return bool
	 */
	protected function is_encrypted_value( string $value ): bool {
		// Check for the generic encryption pattern: $ENC$...$ followed by base64
		return preg_match( '/^\$ENC\$[A-Z0-9_]*\$/', $value ) === 1;
	}

	/**
	 * Extract the encrypted data from a value (strips any $ENC$...$ prefix).
	 *
	 * @param string $value Encrypted value with prefix.
	 *
	 * @return string The base64-encoded encrypted data.
	 */
	protected function extract_encrypted_data( string $value ): string {
		// Remove the $ENC$PREFIX$ pattern and return just the base64 data
		return preg_replace( '/^\$ENC\$[A-Z0-9_]*\$/', '', $value );
	}

	/**
	 * Check if a field should be encrypted.
	 *
	 * @param array $field Field configuration.
	 *
	 * @return bool
	 */
	protected function is_encrypted_field( array $field ): bool {
		return ! empty( $field['encrypted'] ) && $this->encryption_enabled;
	}

	/**
	 * Get constant name for a field.
	 *
	 * @param string $field_key Field key.
	 * @param array  $field     Field configuration.
	 *
	 * @return string
	 */
	protected function get_field_constant_name( string $field_key, array $field ): string {
		// Use explicit constant if defined
		if ( ! empty( $field['constant'] ) ) {
			return $field['constant'];
		}

		// Build constant from option name and field key
		$option_name = $this->config['option_name'] ?? $this->id;

		return strtoupper( $option_name . '_' . $field_key );
	}

	/**
	 * Check if a field has a constant defined.
	 *
	 * @param string $field_key Field key.
	 * @param array  $field     Field configuration.
	 *
	 * @return bool
	 */
	protected function has_field_constant( string $field_key, array $field ): bool {
		$constant_name = $this->get_field_constant_name( $field_key, $field );

		return defined( $constant_name ) && ! empty( constant( $constant_name ) );
	}

	/**
	 * Get field value from constant.
	 *
	 * @param string $field_key Field key.
	 * @param array  $field     Field configuration.
	 *
	 * @return string|null
	 */
	protected function get_field_constant_value( string $field_key, array $field ): ?string {
		$constant_name = $this->get_field_constant_name( $field_key, $field );

		if ( defined( $constant_name ) && ! empty( constant( $constant_name ) ) ) {
			return constant( $constant_name );
		}

		return null;
	}

	/**
	 * Encrypt a field value during sanitization.
	 *
	 * @param string $field_key Field key.
	 * @param array  $field     Field configuration.
	 * @param mixed  $value     Value to potentially encrypt.
	 *
	 * @return mixed
	 */
	protected function maybe_encrypt_field_value( string $field_key, array $field, $value ) {
		// Don't encrypt if constant is defined (value won't be saved)
		if ( $this->has_field_constant( $field_key, $field ) ) {
			return '';
		}

		// Only encrypt string values for encrypted fields
		if ( $this->is_encrypted_field( $field ) && is_string( $value ) && ! empty( $value ) ) {
			$encrypted = $this->encrypt_value( $value );

			return $encrypted !== null ? $encrypted : $value;
		}

		return $value;
	}

	/**
	 * Decrypt a field value during retrieval.
	 *
	 * @param string $field_key Field key.
	 * @param array  $field     Field configuration.
	 * @param mixed  $value     Value to potentially decrypt.
	 *
	 * @return mixed
	 */
	protected function maybe_decrypt_field_value( string $field_key, array $field, $value ) {
		// Check constant first - takes priority
		$constant_value = $this->get_field_constant_value( $field_key, $field );
		if ( $constant_value !== null ) {
			return $constant_value;
		}

		// Handle group fields - decrypt sub-fields
		if ( ( $field['type'] ?? '' ) === 'group' && is_array( $value ) && ! empty( $field['sub_fields'] ) ) {
			$decrypted_group = [];
			foreach ( $field['sub_fields'] as $sub_key => $sub_field ) {
				$sub_value = $value[ $sub_key ] ?? ( $sub_field['default'] ?? '' );
				$decrypted_group[ $sub_key ] = $this->maybe_decrypt_field_value( $sub_key, $sub_field, $sub_value );
			}
			return $decrypted_group;
		}

		// Handle repeater fields - decrypt sub-fields in each row
		if ( ( $field['type'] ?? '' ) === 'repeater' && is_array( $value ) && ! empty( $field['sub_fields'] ) ) {
			$decrypted_repeater = [];
			foreach ( $value as $row_index => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$decrypted_row = [];
				foreach ( $field['sub_fields'] as $sub_key => $sub_field ) {
					$sub_value = $row[ $sub_key ] ?? ( $sub_field['default'] ?? '' );
					$decrypted_row[ $sub_key ] = $this->maybe_decrypt_field_value( $sub_key, $sub_field, $sub_value );
				}
				$decrypted_repeater[ $row_index ] = $decrypted_row;
			}
			return $decrypted_repeater;
		}

		// Decrypt if this is an encrypted field with an encrypted value
		if ( ! empty( $field['encrypted'] ) && is_string( $value ) && ! empty( $value ) ) {
			// Check if the value looks encrypted (has our prefix pattern)
			if ( $this->is_encrypted_value( $value ) ) {
				$decrypted = $this->decrypt_value( $value );
				return $decrypted !== null ? $decrypted : $value;
			}
		}

		return $value;
	}

	/**
	 * Check if a field's value comes from a constant.
	 *
	 * @param string $field_key Field key.
	 *
	 * @return bool
	 */
	public function is_from_constant( string $field_key ): bool {
		$field = $this->fields[ $field_key ] ?? null;

		if ( ! $field ) {
			return false;
		}

		return $this->has_field_constant( $field_key, $field );
	}

	/**
	 * Get detailed value information including source.
	 *
	 * @param string $field_key Field key.
	 * @param mixed  $default   Default value.
	 *
	 * @return array{value: mixed, source: string, is_encrypted: bool, constant_name: string|null}
	 */
	public function get_value_info( string $field_key, $default = null ): array {
		$field = $this->fields[ $field_key ] ?? null;

		if ( ! $field ) {
			return [
				'value'         => $default,
				'source'        => 'default',
				'is_encrypted'  => false,
				'constant_name' => null,
			];
		}

		$is_encrypted  = $this->is_encrypted_field( $field );
		$constant_name = $is_encrypted ? $this->get_field_constant_name( $field_key, $field ) : null;

		// Check constant first
		$constant_value = $this->get_field_constant_value( $field_key, $field );
		if ( $constant_value !== null ) {
			return [
				'value'         => $constant_value,
				'source'        => 'constant',
				'is_encrypted'  => false, // Constants aren't encrypted
				'constant_name' => $constant_name,
			];
		}

		// Get from database
		$values = $this->get_values();
		if ( isset( $values[ $field_key ] ) ) {
			$raw_value       = $values[ $field_key ];
			$is_stored_encrypted = is_string( $raw_value ) && $this->is_encrypted_value( $raw_value );

			return [
				'value'         => $this->maybe_decrypt_field_value( $field_key, $field, $raw_value ),
				'source'        => 'database',
				'is_encrypted'  => $is_stored_encrypted,
				'constant_name' => $constant_name,
			];
		}

		// Return default
		return [
			'value'         => $field['default'] ?? $default,
			'source'        => 'default',
			'is_encrypted'  => false,
			'constant_name' => $constant_name,
		];
	}

	/**
	 * Get the encryption status description for a field (for admin UI).
	 *
	 * @param string $field_key Field key.
	 *
	 * @return string
	 */
	public function get_encryption_status( string $field_key ): string {
		$field = $this->fields[ $field_key ] ?? null;

		if ( ! $field || ! $this->is_encrypted_field( $field ) ) {
			return '';
		}

		if ( $this->has_field_constant( $field_key, $field ) ) {
			$constant_name = $this->get_field_constant_name( $field_key, $field );

			return sprintf(
				'<span class="setting-fields-encryption-status setting-fields-encryption-constant" title="%s"><span class="dashicons dashicons-lock"></span> %s <code>%s</code></span>',
				esc_attr__( 'Value is defined as a constant in wp-config.php', 'setting-fields' ),
				esc_html__( 'Defined in constant:', 'setting-fields' ),
				esc_html( $constant_name )
			);
		}

		return sprintf(
			'<span class="setting-fields-encryption-status setting-fields-encryption-database" title="%s"><span class="dashicons dashicons-lock"></span> %s</span>',
			esc_attr__( 'Value is stored encrypted in the database', 'setting-fields' ),
			esc_html__( 'Stored encrypted', 'setting-fields' )
		);
	}

	/**
	 * Clear the decryption cache.
	 *
	 * @return void
	 */
	public function clear_encryption_cache(): void {
		$this->decrypted_cache = [];
	}

	/**
	 * Re-encrypt all encrypted fields with a new key.
	 *
	 * Useful when rotating encryption keys.
	 *
	 * @param string $new_key New encryption key.
	 *
	 * @return bool Whether re-encryption was successful.
	 */
	public function rotate_encryption_key( string $new_key ): bool {
		if ( ! $this->encryption_enabled ) {
			return false;
		}

		$values      = get_option( $this->config['option_name'], [] );
		$old_key     = $this->encryption_key;
		$new_key_hash = hash( 'sha256', $new_key, true );

		$updated_values = [];

		foreach ( $this->fields as $field_key => $field ) {
			if ( ! $this->is_encrypted_field( $field ) ) {
				$updated_values[ $field_key ] = $values[ $field_key ] ?? '';
				continue;
			}

			// Skip if constant is defined
			if ( $this->has_field_constant( $field_key, $field ) ) {
				$updated_values[ $field_key ] = '';
				continue;
			}

			$raw_value = $values[ $field_key ] ?? '';

			if ( empty( $raw_value ) ) {
				$updated_values[ $field_key ] = '';
				continue;
			}

			// Decrypt with old key
			$decrypted = $this->decrypt_value( $raw_value );

			if ( $decrypted === null ) {
				// Couldn't decrypt - keep original
				$updated_values[ $field_key ] = $raw_value;
				continue;
			}

			// Encrypt with new key
			$this->encryption_key = $new_key_hash;
			$this->clear_encryption_cache();

			$encrypted = $this->encrypt_value( $decrypted );

			if ( $encrypted !== null ) {
				$updated_values[ $field_key ] = $encrypted;
			} else {
				// Re-encryption failed - restore old key and keep original
				$this->encryption_key = $old_key;
				$updated_values[ $field_key ] = $raw_value;
			}
		}

		// Restore new key and save
		$this->encryption_key = $new_key_hash;

		return update_option( $this->config['option_name'], $updated_values );
	}

}