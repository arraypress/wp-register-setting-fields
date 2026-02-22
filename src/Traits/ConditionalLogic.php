<?php
/**
 * Conditional Logic Trait
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields\Traits;

/**
 * Trait ConditionalLogic
 *
 * Handles conditional logic for showing/hiding fields.
 */
trait ConditionalLogic {

	/**
	 * Get conditional logic data attributes for a field row.
	 *
	 * @param array $field Field configuration.
	 *
	 * @return string HTML attributes string.
	 */
	protected function get_conditional_attributes( array $field ): string {
		if ( empty( $field['depends'] ) ) {
			return '';
		}

		$conditions = $this->normalize_conditions( $field['depends'] );

		if ( empty( $conditions ) ) {
			return '';
		}

		return sprintf(
			' data-conditions="%s" style="display:none;"',
			esc_attr( wp_json_encode( $conditions ) )
		);
	}

	/**
	 * Normalize conditions to a consistent array format.
	 *
	 * @param array $depends The depends configuration.
	 *
	 * @return array
	 */
	protected function normalize_conditions( array $depends ): array {
		if ( empty( $depends ) ) {
			return [];
		}

		// Check if it's a simple key => value format
		// e.g., ['enable_feature' => 1]
		$first_key = array_key_first( $depends );

		if ( is_string( $first_key ) && ! in_array( $first_key, [ 'field', 'value', 'operator' ], true ) ) {
			// Simple format - convert to array of conditions
			$conditions = [];
			foreach ( $depends as $field => $value ) {
				$conditions[] = [
					'field'    => $field,
					'value'    => $value,
					'operator' => is_array( $value ) ? 'in' : '=',
				];
			}

			return $conditions;
		}

		// Check if it's a single condition array
		// e.g., ['field' => 'enable_feature', 'value' => 1]
		if ( isset( $depends['field'] ) ) {
			return [ $this->normalize_single_condition( $depends ) ];
		}

		// Array of condition arrays
		// e.g., [['field' => 'a', 'value' => 1], ['field' => 'b', 'value' => 2]]
		return array_map( [ $this, 'normalize_single_condition' ], $depends );
	}

	/**
	 * Normalize a single condition.
	 *
	 * @param array $condition Single condition array.
	 *
	 * @return array
	 */
	protected function normalize_single_condition( array $condition ): array {
		$default_operator = is_array( $condition['value'] ?? '' ) ? 'in' : '=';

		return [
			'field'    => $condition['field'] ?? '',
			'value'    => $condition['value'] ?? '',
			'operator' => $condition['operator'] ?? $default_operator,
		];
	}

	/**
	 * Check if conditions are met for a field.
	 *
	 * @param array $field  Field configuration.
	 * @param array $values Current values.
	 *
	 * @return bool
	 */
	public function check_conditions( array $field, array $values ): bool {
		if ( empty( $field['depends'] ) ) {
			return true;
		}

		$conditions = $this->normalize_conditions( $field['depends'] );

		foreach ( $conditions as $condition ) {
			$field_key     = $condition['field'];
			$expected      = $condition['value'];
			$operator      = $condition['operator'];
			$current_value = $values[ $field_key ] ?? null;

			if ( ! $this->evaluate_condition( $current_value, $expected, $operator ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Evaluate a single condition.
	 *
	 * @param mixed  $current  Current field value.
	 * @param mixed  $expected Expected value.
	 * @param string $operator Comparison operator.
	 *
	 * @return bool
	 */
	protected function evaluate_condition( $current, $expected, string $operator ): bool {
		switch ( $operator ) {
			case '=':
			case '==':
				return $current == $expected;

			case '===':
				return $current === $expected;

			case '!=':
			case '!==':
				return $current != $expected;

			case '>':
				return $current > $expected;

			case '>=':
				return $current >= $expected;

			case '<':
				return $current < $expected;

			case '<=':
				return $current <= $expected;

			case 'in':
				$expected = is_array( $expected ) ? $expected : [ $expected ];

				return in_array( $current, $expected, false );

			case 'not_in':
				$expected = is_array( $expected ) ? $expected : [ $expected ];

				return ! in_array( $current, $expected, false );

			case 'contains':
				if ( is_array( $current ) ) {
					return in_array( $expected, $current, false );
				}

				return str_contains( (string) $current, (string) $expected );

			case 'not_contains':
				if ( is_array( $current ) ) {
					return ! in_array( $expected, $current, false );
				}

				return ! str_contains( (string) $current, (string) $expected );

			case 'empty':
				return empty( $current );

			case 'not_empty':
				return ! empty( $current );

			default:
				return $current == $expected;
		}
	}

}