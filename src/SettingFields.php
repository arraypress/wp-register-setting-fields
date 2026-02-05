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
use ArrayPress\RegisterSettingFields\Traits\Encryption;
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
    use Encryption;
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
        'body_class'    => '',
        'position'      => null,
        'option_name'   => '',
        'option_group'  => '',
        'tabs'          => [],
        'sections'      => [],
        'fields'        => [],
        'submit_button' => true,

        // Branded header options
        'logo'          => '',
        'header_title'  => '',
        'header_class'  => '',

        // Help screen options
        'help_tabs'     => [],
        'help_sidebar'  => '',

        // Encryption options
        'encryption'    => [
            'enabled' => null,
            'key'     => null,
            'prefix'  => '',
        ],
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

        // Initialize encryption after fields are parsed
        $this->init_encryption( $this->config );

        // Register with the central registry
        Registry::register( $this->id, $this );

        // Register REST API if we have AJAX fields
        if ( $this->has_ajax_fields() ) {
            RestApi::register();
        }

        $this->init_hooks();
    }

    /**
     * Check if any fields require AJAX.
     *
     * All relational fields (post, page, taxonomy, user) now use AJAX by default,
     * plus the custom 'ajax' type for custom callbacks.
     *
     * @return bool
     */
    protected function has_ajax_fields(): bool {
        $ajax_types = [ 'ajax', 'post', 'page', 'taxonomy', 'user' ];

        foreach ( $this->fields as $field ) {
            if ( in_array( $field['type'] ?? '', $ajax_types, true ) ) {
                return true;
            }

            // Check nested fields
            if ( ! empty( $field['sub_fields'] ) ) {
                foreach ( $field['sub_fields'] as $sub_field ) {
                    if ( in_array( $sub_field['type'] ?? '', $ajax_types, true ) ) {
                        return true;
                    }
                }
            }
        }

        return false;
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

        // Add body class for styling
        add_filter( 'admin_body_class', [ $this, 'add_body_class' ] );

        // Fix menu highlight for submenu pages
        if ( ! empty( $this->config['parent_slug'] ) ) {
            add_filter( 'parent_file', [ $this, 'fix_parent_menu_highlight' ] );
            add_filter( 'submenu_file', [ $this, 'fix_submenu_highlight' ] );
        }
    }

    /**
     * Add custom body class to the reports page.
     *
     * @param string $classes Space-separated list of body classes.
     *
     * @return string
     */
    public function add_body_class( string $classes ): string {
        $screen = get_current_screen();

        if ( ! $screen || $screen->id !== $this->hook_suffix ) {
            return $classes;
        }

        $classes .= ' settings';
        $classes .= ' settings-' . $this->id;

        if ( ! empty( $this->config['body_class'] ) ) {
            $classes .= ' ' . sanitize_html_class( $this->config['body_class'] );
        }

        return $classes;
    }

    /**
     * Fix parent menu highlight for settings pages.
     *
     * @param string $parent_file The parent file.
     *
     * @return string
     */
    public function fix_parent_menu_highlight( string $parent_file ): string {
        global $plugin_page;

        if ( $plugin_page === $this->config['menu_slug'] ) {
            return $this->config['parent_slug'];
        }

        return $parent_file;
    }

    /**
     * Fix submenu highlight for settings pages.
     *
     * @param string|null $submenu_file The submenu file.
     *
     * @return string|null
     */
    public function fix_submenu_highlight( ?string $submenu_file ): ?string {
        global $plugin_page;

        if ( $plugin_page === $this->config['menu_slug'] ) {
            return $this->config['menu_slug'];
        }

        return $submenu_file;
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

        if ( ! empty( $this->config['help_tabs'] ) || ! empty( $this->config['help_sidebar'] ) ) {
            add_action( 'load-' . $this->hook_suffix, [ $this, 'register_help_tabs' ] );
        }
    }

    /**
     * Register help tabs for the settings screen.
     *
     * @return void
     */
    public function register_help_tabs(): void {
        $screen = get_current_screen();

        if ( ! $screen ) {
            return;
        }

        if ( ! empty( $this->config['help_tabs'] ) ) {
            foreach ( $this->config['help_tabs'] as $tab_id => $tab ) {
                $screen->add_help_tab( [
                        'id'       => $this->id . '_' . $tab_id,
                        'title'    => $tab['title'] ?? $tab_id,
                        'content'  => $tab['content'] ?? '',
                        'callback' => $tab['callback'] ?? null,
                        'priority' => $tab['priority'] ?? 10,
                ] );
            }
        }

        if ( ! empty( $this->config['help_sidebar'] ) ) {
            $screen->set_help_sidebar( $this->config['help_sidebar'] );
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

        // Load current values (with decryption)
        $this->values = $this->get_values();

        // Get current tab
        $current_tab = $this->get_current_tab();

        // Render header outside .wrap (matches RegisterTables pattern)
        $this->render_header( $current_tab );

        ?>
        <div class="wrap setting-fields-wrap" data-setting-id="<?php echo esc_attr( $this->id ); ?>">

            <div class="setting-fields-notices">
                <?php settings_errors( $this->config['option_group'] ); ?>
            </div>

            <form method="post" action="options.php" class="setting-fields-form">
                <?php
                settings_fields( $this->config['option_group'] );

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
     * Render the modern header with optional logo and integrated tabs.
     *
     * Rendered outside .wrap to match RegisterTables/EDD pattern.
     *
     * @param string $current_tab Current active tab.
     *
     * @return void
     */
    protected function render_header( string $current_tab ): void {
        $logo_url     = $this->config['logo'] ?? '';
        $header_title = ! empty( $this->config['header_title'] )
                ? $this->config['header_title']
                : $this->config['page_title'];

        $has_title = ! empty( $header_title );
        $has_tabs  = ! empty( $this->tabs );

        // Don't render header if nothing to show
        if ( ! $logo_url && ! $has_title && ! $has_tabs ) {
            echo '<hr class="wp-header-end">';

            return;
        }

        ?>
        <div class="setting-fields-header">
            <div class="setting-fields-header__inner">
                <div class="setting-fields-header__branding">
                    <?php if ( $logo_url ) : ?>
                        <img src="<?php echo esc_url( $logo_url ); ?>" alt="" class="setting-fields-header__logo">
                        <?php if ( $has_title ) : ?>
                            <span class="setting-fields-header__separator">/</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ( $has_title ) : ?>
                        <h1 class="setting-fields-header__title"><?php echo esc_html( $header_title ); ?></h1>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ( $has_tabs ) : ?>
                <div class="setting-fields-header__tabs">
                    <button type="button" class="setting-fields-tabs-toggle">
                        <span class="setting-fields-tabs-current">
                            <?php
                            $current_label = $this->tabs[ $current_tab ]['label'] ?? '';
                            $current_icon  = $this->tabs[ $current_tab ]['icon'] ?? '';
                            if ( $current_icon ) {
                                echo '<span class="dashicons ' . esc_attr( $current_icon ) . '"></span> ';
                            }
                            echo esc_html( $current_label );
                            ?>
                        </span>
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <?php $this->render_tabs( $current_tab ); ?>
                </div>
            <?php endif; ?>
        </div>
        <hr class="wp-header-end">
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

        // Add the field key to the field config
        $field['_key'] = $field_key;

        // Build conditional logic data attributes
        $row_attrs = $this->get_conditional_attributes( $field );

        // Check if field is disabled due to constant
        $is_from_constant = $this->is_encrypted_field( $field ) && $this->has_field_constant( $field_key, $field );

        // Message, HTML, separator, heading fields get full-width rendering
        $type = $field['type'] ?? 'text';
        if ( in_array( $type, [ 'message', 'html', 'separator', 'heading' ], true ) ) {
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
                        <?php if ( ! empty( $field['tooltip'] ) ) : ?>
                            <span class="setting-fields-tooltip">
                                <span class="dashicons dashicons-info"></span>
                                <span class="setting-fields-tooltip-content"><?php echo esc_html( $field['tooltip'] ); ?></span>
                            </span>
                        <?php endif; ?>
                    </label>
                <?php endif; ?>
            </th>
            <td>
                <?php
                // Render the field (potentially disabled if from constant)
                if ( $is_from_constant ) {
                    $field['readonly'] = true;
                    $field['disabled'] = true;
                }

                $this->render_field( $field_key, $field, $field_name, $field_id, $value );

                // Show encryption status for encrypted fields
                if ( $this->is_encrypted_field( $field ) ) {
                    echo $this->get_encryption_status( $field_key );
                }

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
     * Get all current values (with decryption applied).
     *
     * @return array
     */
    public function get_values(): array {
        $raw_values = get_option( $this->config['option_name'], [] );

        if ( ! is_array( $raw_values ) ) {
            $raw_values = [];
        }

        // Apply decryption to encrypted fields
        $decrypted_values = [];
        foreach ( $this->fields as $field_key => $field ) {
            $raw_value                      = $raw_values[ $field_key ] ?? ( $field['default'] ?? '' );
            $decrypted_values[ $field_key ] = $this->maybe_decrypt_field_value( $field_key, $field, $raw_value );
        }

        $this->values = $decrypted_values;

        return $this->values;
    }

    /**
     * Get a specific field value (with decryption applied).
     *
     * @param string $field_key Field key.
     * @param mixed  $default   Default value.
     *
     * @return mixed
     */
    public function get_value( string $field_key, $default = null ) {
        $field = $this->fields[ $field_key ] ?? null;

        if ( ! $field ) {
            return $default;
        }

        // Check constant first for encrypted fields
        if ( $this->is_encrypted_field( $field ) ) {
            $constant_value = $this->get_field_constant_value( $field_key, $field );
            if ( $constant_value !== null ) {
                return $constant_value;
            }
        }

        // Get from database
        $raw_values = get_option( $this->config['option_name'], [] );

        if ( isset( $raw_values[ $field_key ] ) ) {
            return $this->maybe_decrypt_field_value( $field_key, $field, $raw_values[ $field_key ] );
        }

        return $field['default'] ?? $default;
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