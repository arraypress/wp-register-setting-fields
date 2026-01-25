<?php
/**
 * Setting Fields Main Class
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields;

use ArrayPress\RegisterSettingFields\Traits\AssetManager;
use ArrayPress\RegisterSettingFields\Traits\ConfigParser;
use ArrayPress\RegisterSettingFields\Traits\FieldRenderer;
use ArrayPress\RegisterSettingFields\Traits\FieldSanitizer;
use ArrayPress\RegisterSettingFields\Traits\SettingsRegistration;
use ArrayPress\RegisterSettingFields\Traits\TabManager;
use ArrayPress\RegisterSettingFields\Traits\ConditionalLogic;

/**
 * Class SettingFields
 *
 * Main class for registering WordPress settings pages with fields.
 */
class SettingFields {

	use AssetManager;
	use ConfigParser;
	use FieldRenderer;
	use FieldSanitizer;
	use SettingsRegistration;
	use TabManager;
	use ConditionalLogic;

	/**
	 * Unique identifier for this settings group.
	 *
	 * @var string
	 */
	protected string $id;

	/**
	 * Configuration array.
	 *
	 * @var array
	 */
	protected array $config;

	/**
	 * Parsed fields array.
	 *
	 * @var array
	 */
	protected array $fields = [];

	/**
	 * Parsed tabs array.
	 *
	 * @var array
	 */
	protected array $tabs = [];

	/**
	 * Parsed sections array.
	 *
	 * @var array
	 */
	protected array $sections = [];

	/**
	 * Current option values.
	 *
	 * @var array
	 */
	protected array $values = [];

	/**
	 * Settings page hook suffix.
	 *
	 * @var string
	 */
	protected string $hook_suffix = '';

	/**
	 * Default configuration values.
	 *
	 * @var array
	 */
	protected array $defaults = [
		'page_title'    => 'Settings',
		'menu_title'    => 'Settings',
		'menu_slug'     => '',
		'capability'    => 'manage_options',
		'parent_slug'   => '',
		'icon'          => 'dashicons-admin-generic',
		'position'      => null,
		'option_name'   => '',
		'option_group'  => '',
		'tabs'          => [],
		'sections'      => [],
		'fields'        => [],
		'show_title'    => true,
		'show_tabs'     => true,
		'submit_button' => true,
		// Branded header options
		'logo'          => '',        // URL to logo image
		'header_title'  => '',        // Title next to logo (defaults to page_title)
		'header_class'  => '',        // Additional CSS class for header
	];

	/**
	 * Constructor.
	 *
	 * @param string $id     Unique identifier for this settings group.
	 * @param array  $config Configuration array.
	 */
	public function __construct( string $id, array $config ) {
		$this->id     = sanitize_key( $id );
		$this->config = wp_parse_args( $config, $this->defaults );

		// Set defaults based on ID if not provided
		if ( empty( $this->config['menu_slug'] ) ) {
			$this->config['menu_slug'] = $this->id;
		}
		if ( empty( $this->config['option_name'] ) ) {
			$this->config['option_name'] = $this->id;
		}
		if ( empty( $this->config['option_group'] ) ) {
			$this->config['option_group'] = $this->id . '_group';
		}

		$this->parse_config();
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @return void
	 */
	protected function init_hooks(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_assets' ] );
	}

	/**
	 * Register the admin menu page.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		if ( ! empty( $this->config['parent_slug'] ) ) {
			$this->hook_suffix = add_submenu_page(
				$this->config['parent_slug'],
				$this->config['page_title'],
				$this->config['menu_title'],
				$this->config['capability'],
				$this->config['menu_slug'],
				[ $this, 'render_page' ]
			);
		} else {
			$this->hook_suffix = add_menu_page(
				$this->config['page_title'],
				$this->config['menu_title'],
				$this->config['capability'],
				$this->config['menu_slug'],
				[ $this, 'render_page' ],
				$this->config['icon'],
				$this->config['position']
			);
		}
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( $this->config['capability'] ) ) {
			return;
		}

		// Load current values
		$this->values = get_option( $this->config['option_name'], [] );
		if ( ! is_array( $this->values ) ) {
			$this->values = [];
		}

		// Get current tab
		$current_tab = $this->get_current_tab();

		// Check if we have a branded header
		$has_branded_header = ! empty( $this->config['logo'] );

		?>
		<div class="wrap setting-fields-wrap<?php echo $has_branded_header ? ' setting-fields-wrap--branded' : ''; ?>" data-setting-id="<?php echo esc_attr( $this->id ); ?>">

			<?php if ( $has_branded_header ) : ?>
				<?php $this->render_branded_header(); ?>
			<?php elseif ( $this->config['show_title'] ) : ?>
				<h1><?php echo esc_html( $this->config['page_title'] ); ?></h1>
			<?php endif; ?>

			<?php settings_errors( $this->config['option_group'] ); ?>

			<?php if ( $this->config['show_tabs'] && ! empty( $this->tabs ) ) : ?>
				<?php $this->render_tabs( $current_tab ); ?>
			<?php endif; ?>

			<form method="post" action="options.php" class="setting-fields-form">
				<?php
				settings_fields( $this->config['option_group'] );

				// Render fields for current tab
				$this->render_fields_for_tab( $current_tab );

				if ( $this->config['submit_button'] ) {
					submit_button();
				}
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the branded header with logo and title.
	 *
	 * @return void
	 */
	protected function render_branded_header(): void {
		$logo_url     = $this->config['logo'];
		$header_title = ! empty( $this->config['header_title'] ) ? $this->config['header_title'] : $this->config['page_title'];
		$header_class = 'setting-fields-header';

		if ( ! empty( $this->config['header_class'] ) ) {
			$header_class .= ' ' . $this->config['header_class'];
		}

		?>
		<div class="<?php echo esc_attr( $header_class ); ?>">
			<div class="setting-fields-header-branding">
				<?php if ( $logo_url ) : ?>
					<img src="<?php echo esc_url( $logo_url ); ?>" alt="" class="setting-fields-header-logo">
				<?php endif; ?>
				<h1 class="setting-fields-header-title"><?php echo esc_html( $header_title ); ?></h1>
			</div>
		</div>
		<?php
	}

	/**
	 * Render fields for a specific tab.
	 *
	 * @param string $tab Tab key.
	 *
	 * @return void
	 */
	protected function render_fields_for_tab( string $tab ): void {
		$tab_fields = $this->get_fields_for_tab( $tab );

		if ( empty( $tab_fields ) ) {
			return;
		}

		// Group fields by section
		$sections = $this->get_sections_for_tab( $tab );

		if ( ! empty( $sections ) ) {
			foreach ( $sections as $section_key => $section ) {
				$section_fields = array_filter( $tab_fields, function ( $field ) use ( $section_key ) {
					return isset( $field['section'] ) && $field['section'] === $section_key;
				} );

				if ( ! empty( $section_fields ) ) {
					$this->render_section( $section_key, $section, $section_fields );
				}
			}

			// Render fields without a section
			$unsectioned_fields = array_filter( $tab_fields, function ( $field ) {
				return empty( $field['section'] );
			} );

			if ( ! empty( $unsectioned_fields ) ) {
				echo '<table class="form-table" role="presentation">';
				foreach ( $unsectioned_fields as $field_key => $field ) {
					$this->render_field_row( $field_key, $field );
				}
				echo '</table>';
			}
		} else {
			echo '<table class="form-table" role="presentation">';
			foreach ( $tab_fields as $field_key => $field ) {
				$this->render_field_row( $field_key, $field );
			}
			echo '</table>';
		}
	}

	/**
	 * Render a section with its fields.
	 *
	 * @param string $section_key Section key.
	 * @param array  $section     Section config.
	 * @param array  $fields      Fields in this section.
	 *
	 * @return void
	 */
	protected function render_section( string $section_key, array $section, array $fields ): void {
		if ( ! empty( $section['title'] ) ) {
			echo '<h2 class="setting-fields-section-title">' . esc_html( $section['title'] ) . '</h2>';
		}

		if ( ! empty( $section['description'] ) ) {
			echo '<p class="setting-fields-section-description">' . esc_html( $section['description'] ) . '</p>';
		}

		echo '<table class="form-table" role="presentation">';
		foreach ( $fields as $field_key => $field ) {
			$this->render_field_row( $field_key, $field );
		}
		echo '</table>';
	}

	/**
	 * Render a single field row.
	 *
	 * @param string $field_key Field key.
	 * @param array  $field     Field config.
	 *
	 * @return void
	 */
	protected function render_field_row( string $field_key, array $field ): void {
		$field_name = $this->config['option_name'] . '[' . $field_key . ']';
		$field_id   = $this->config['option_name'] . '_' . $field_key;
		$value      = $this->values[ $field_key ] ?? ( $field['default'] ?? '' );

		// Add the field key to the field config so renderers can access it
		$field['_key'] = $field_key;

		// Build conditional logic data attributes
		$row_attrs = $this->get_conditional_attributes( $field );

		// Message and HTML fields get full-width rendering (no label column)
		$type = $field['type'] ?? 'text';
		if ( in_array( $type, [ 'message', 'html' ], true ) ) {
			?>
			<tr<?php echo $row_attrs; ?> class="setting-fields-row-fullwidth">
				<td colspan="2">
					<?php $this->render_field( $field_key, $field, $field_name, $field_id, $value ); ?>
				</td>
			</tr>
			<?php
			return;
		}

		?>
		<tr<?php echo $row_attrs; ?>>
			<th scope="row">
				<?php if ( ! empty( $field['label'] ) ) : ?>
					<label for="<?php echo esc_attr( $field_id ); ?>">
						<?php echo esc_html( $field['label'] ); ?>
						<?php if ( ! empty( $field['required'] ) ) : ?>
							<span class="required">*</span>
						<?php endif; ?>
					</label>
				<?php endif; ?>
			</th>
			<td>
				<?php
				$this->render_field( $field_key, $field, $field_name, $field_id, $value );

				if ( ! empty( $field['description'] ) ) {
					echo '<p class="description">' . wp_kses_post( $field['description'] ) . '</p>';
				}
				?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Get the settings ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Get a specific config value.
	 *
	 * @param string $key     Config key.
	 * @param mixed  $default Default value.
	 *
	 * @return mixed
	 */
	public function get_config( string $key, $default = null ) {
		return $this->config[ $key ] ?? $default;
	}

	/**
	 * Get the option name.
	 *
	 * @return string
	 */
	public function get_option_name(): string {
		return $this->config['option_name'];
	}

	/**
	 * Get all current values.
	 *
	 * @return array
	 */
	public function get_values(): array {
		if ( empty( $this->values ) ) {
			$this->values = get_option( $this->config['option_name'], [] );
			if ( ! is_array( $this->values ) ) {
				$this->values = [];
			}
		}

		return $this->values;
	}

	/**
	 * Get a specific field value.
	 *
	 * @param string $field_key Field key.
	 * @param mixed  $default   Default value.
	 *
	 * @return mixed
	 */
	public function get_value( string $field_key, $default = null ) {
		$values = $this->get_values();

		if ( isset( $values[ $field_key ] ) ) {
			return $values[ $field_key ];
		}

		// Check field default
		if ( isset( $this->fields[ $field_key ]['default'] ) ) {
			return $this->fields[ $field_key ]['default'];
		}

		return $default;
	}

	/**
	 * Get all field configurations.
	 *
	 * @return array
	 */
	public function get_fields(): array {
		return $this->fields;
	}

	/**
	 * Get a specific field configuration.
	 *
	 * @param string $field_key Field key.
	 *
	 * @return array|null
	 */
	public function get_field( string $field_key ): ?array {
		return $this->fields[ $field_key ] ?? null;
	}

}
