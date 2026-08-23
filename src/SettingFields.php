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
use ArrayPress\RegisterSettingFields\Traits\SettingsActions;
use ArrayPress\RegisterSettingFields\Traits\EmailActions;
use ArrayPress\RegisterSettingFields\Traits\DataPortability;

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
    use SettingsActions;
    use EmailActions;
    use DataPortability;

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

        // Reset button
            'reset_button'  => false,

        // Export/Import
            'export_import' => false,

        // Branded header options
            'logo'          => '',
		'header_title'  => '',
		'header_badge'  => '',
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

        // Register REST API if we have fields that need it
        if ( $this->has_rest_fields() || $this->config['reset_button'] || $this->config['export_import'] ) {
            RestApi::register();
        }

        $this->init_hooks();
    }

    /**
     * Check if any fields require REST API endpoints.
     *
     * Includes relational fields (post, page, taxonomy, user) which use AJAX,
     * email_editor fields which use preview/send-test endpoints,
     * and action_button fields which use the action endpoint.
     *
     * @return bool
     */
    protected function has_rest_fields(): bool {
        $rest_types = [ 'ajax', 'post', 'page', 'taxonomy', 'user', 'email_editor', 'action_button', 'license' ];

        foreach ( $this->fields as $field ) {
            if ( in_array( $field['type'] ?? '', $rest_types, true ) ) {
                return true;
            }

            // Check nested fields
            if ( ! empty( $field['sub_fields'] ) ) {
                foreach ( $field['sub_fields'] as $sub_field ) {
                    if ( in_array( $sub_field['type'] ?? '', $rest_types, true ) ) {
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
                <?php
                // For pages not under Settings, WordPress doesn't auto-display
                // the "Settings saved" notice after options.php redirect.
                // Manually add it when settings-updated is present.
                if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
                    add_settings_error(
                            $this->config['option_group'],
                            'settings_updated',
                            __( 'Settings saved.', 'setting-fields' ),
                            'updated'
                    );
                }
                settings_errors( $this->config['option_group'] );
                ?>
            </div>

            <form method="post" action="options.php" class="setting-fields-form">
                <?php
                settings_fields( $this->config['option_group'] );

                $this->render_fields_for_tab( $current_tab );

                $this->render_footer_actions();
                ?>
            </form>

            <?php if ( $this->config['export_import'] ) : ?>
                <div class="setting-fields-export-import-result" style="display: none;">
                    <span class="dashicons setting-fields-export-import-icon"></span>
                    <span class="setting-fields-export-import-message"></span>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render footer action buttons (submit only).
     *
     * Reset and export/import actions are rendered in the header.
     *
     * @return void
     */
    protected function render_footer_actions(): void {
        if ( ! $this->config['submit_button'] ) {
            return;
        }

        submit_button();
    }

    /**
     * Render header action buttons (reset, export, import).
     *
     * Displayed in the header branding row, right-aligned.
     *
     * @param string $current_tab Current active tab.
     *
     * @return void
     */
    protected function render_header_actions( string $current_tab ): void {
        $has_reset  = $this->config['reset_button'];
        $has_export = $this->config['export_import'];

        if ( ! $has_reset && ! $has_export ) {
            return;
        }

        echo '<div class="setting-fields-header__actions">';

        if ( $has_reset ) {
            $reset_label = ! empty( $this->tabs )
                    /* translators: %s: label of the settings tab being reset */
                    ? sprintf( __( 'Reset %s', 'setting-fields' ), $this->tabs[ $current_tab ]['label'] ?? __( 'Tab', 'setting-fields' ) )
                    : __( 'Reset to Defaults', 'setting-fields' );
            ?>
            <button type="button"
                    class="button setting-fields-reset-btn"
                    data-tab="<?php echo esc_attr( $current_tab ); ?>"
                    data-confirm="<?php echo esc_attr( __( 'Are you sure you want to reset these settings to their defaults? This cannot be undone.', 'setting-fields' ) ); ?>">
                <?php echo esc_html( $reset_label ); ?>
            </button>
            <?php
        }

        if ( $has_export ) {
            ?>
            <button type="button" class="button setting-fields-export-btn">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e( 'Export Settings', 'setting-fields' ); ?>
            </button>

            <div class="setting-fields-import-wrap">
                <input type="file"
                        accept=".json"
                        class="setting-fields-import-file"
                        id="<?php echo esc_attr( $this->id ); ?>_import_file"
                        style="display:none;"/>
                <button type="button" class="button setting-fields-import-btn">
                    <span class="dashicons dashicons-upload"></span>
                    <?php esc_html_e( 'Import Settings', 'setting-fields' ); ?>
                </button>
            </div>
            <?php
        }

        echo '</div>';
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
        $header_badge = $this->config['header_badge'] ?? '';

        $has_title = ! empty( $header_title );
        $has_tabs  = ! empty( $this->tabs );

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
                    <?php if ( ! empty( $header_badge ) ) : ?>
                        <?php self::render_header_badge( $header_badge ); ?>
                    <?php endif; ?>

                    <?php $this->render_header_actions( $current_tab ); ?>
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
     * Render the header badge.
     *
     * Outputs an inline badge next to the header title. Supports:
     * 1. String — rendered with default styling
     * 2. Array — with 'text' and optional 'class' keys
     * 3. Callable — full control over output
     *
     * @param string|array|callable $badge Badge configuration.
     *
     * @return void
     * @since 2.0.0
     */
    private static function render_header_badge( $badge ): void {
        if ( is_callable( $badge ) ) {
            // Returns markup this library assembled and escaped as it built it.
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo call_user_func( $badge );

            return;
        }

        if ( is_array( $badge ) ) {
            $text  = $badge['text'] ?? '';
            $class = $badge['class'] ?? '';

            if ( empty( $text ) ) {
                return;
            }

            printf(
                    '<span class="setting-fields-header__badge %s">%s</span>',
                    esc_attr( $class ),
                    esc_html( $text )
            );

            return;
        }

        if ( is_string( $badge ) && ! empty( $badge ) ) {
            printf(
                    '<span class="setting-fields-header__badge">%s</span>',
                    esc_html( $badge )
            );
        }
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
     * Wraps the entire section (title, description, table) in a container
     * div with a data-section attribute. This allows the conditional logic
     * JS to hide entire sections when all their fields are hidden.
     *
     * If the section has a badge with an active (non-disabled) state, or
     * has 'disabled' => true, all child fields inherit the disabled state
     * automatically.
     *
     * @param string $section_key Section key.
     * @param array  $section     Section config.
     * @param array  $fields      Fields in this section.
     *
     * @return void
     */
    protected function render_section( string $section_key, array $section, array $fields ): void {
        // Resolve badge (returns null if badge is disabled/hidden)
        $badge = isset( $section['badge'] ) ? self::resolve_badge( $section['badge'] ) : null;

        // Section is disabled if explicitly set OR if badge is active (visible)
        $is_disabled = ! empty( $section['disabled'] ) || $badge !== null;

        $classes = 'setting-fields-section';
        if ( $is_disabled ) {
            $classes .= ' setting-fields-section--disabled';
        }

        echo '<div class="' . esc_attr( $classes ) . '" data-section="' . esc_attr( $section_key ) . '">';

        if ( ! empty( $section['title'] ) || $badge !== null ) {
            echo '<div class="setting-fields-section-header">';

            if ( ! empty( $section['title'] ) ) {
                echo '<h2 class="setting-fields-section-title">' . esc_html( $section['title'] ) . '</h2>';
            }

            if ( $badge !== null ) {
                self::render_badge( $badge );
            }

            echo '</div>';
        }

        if ( ! empty( $section['description'] ) ) {
            echo '<p class="setting-fields-section-description">' . esc_html( $section['description'] ) . '</p>';
        }

        echo '<table class="form-table" role="presentation">';
        foreach ( $fields as $field_key => $field ) {
            // Cascade disabled state from section to fields
            if ( $is_disabled ) {
                $field['disabled'] = true;
            }

            $this->render_field_row( $field_key, $field );
        }
        echo '</table>';

        echo '</div>';
    }

    /**
     * Resolve a badge configuration and check if it should render.
     *
     * Normalizes string shorthand to array, resolves callable 'disabled'
     * values, and returns null if the badge is disabled. When the badge
     * has a 'disabled' key that resolves truthy, the badge is hidden
     * and the associated field/section should become editable.
     *
     * @param string|array $badge Badge configuration.
     *
     * @return array|null Normalized badge config, or null if disabled.
     */
    private static function resolve_badge( $badge ): ?array {
        // Normalize string shorthand to array
        if ( is_string( $badge ) ) {
            $badge = [ 'text' => $badge ];
        }

        if ( ! is_array( $badge ) || empty( $badge['text'] ) ) {
            return null;
        }

        // Resolve the disabled condition
        $disabled = $badge['disabled'] ?? false;
        if ( is_callable( $disabled ) ) {
            $disabled = (bool) call_user_func( $disabled );
        }

        // If disabled resolves truthy, badge should not render
        if ( $disabled ) {
            return null;
        }

        return $badge;
    }

    /**
     * Render an inline badge.
     *
     * Outputs a small pill badge next to a field label or section title.
     * Supports a simple string or a full config array with text, url,
     * class, and icon options.
     *
     * Use resolve_badge() first to check if the badge should render
     * and to normalize the configuration.
     *
     * @param array $badge Normalized badge configuration.
     *
     * @return void
     */
    private static function render_badge( array $badge ): void {
        $text  = $badge['text'];
        $url   = $badge['url'] ?? '';
        $class = $badge['class'] ?? '';
        $icon  = $badge['icon'] ?? '';

        $badge_class = 'setting-fields-badge';
        if ( ! empty( $class ) ) {
            $badge_class .= ' ' . $class;
        }

        $inner = '';

        if ( ! empty( $icon ) ) {
            $inner .= '<span class="dashicons ' . esc_attr( $icon ) . '"></span> ';
        }

        $inner .= esc_html( $text );

        if ( ! empty( $url ) ) {
            printf(
                    '<a href="%s" class="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                    esc_url( $url ),
                    esc_attr( $badge_class ),
                    // Returns markup this library assembled and escaped as it built it.
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    $inner
            );
        } else {
            printf(
                    '<span class="%s">%s</span>',
                    esc_attr( $badge_class ),
                    // Returns markup this library assembled and escaped as it built it.
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    $inner
            );
        }
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

        // Resolve badge (returns null if badge is disabled/hidden)
        $badge = isset( $field['badge'] ) ? self::resolve_badge( $field['badge'] ) : null;

        // When badge is active (visible), disable the field
        if ( $badge !== null ) {
            $field['disabled'] = true;
        }

        // Build conditional logic data attributes
        $row_attrs = $this->get_conditional_attributes( $field );

        // Check if field is disabled due to constant
        $is_from_constant = $this->is_encrypted_field( $field ) && $this->has_field_constant( $field_key, $field );

        // Hidden fields render only the input, no table row
        $type = $field['type'] ?? 'text';
        if ( $type === 'hidden' ) {
            $this->render_field( $field_key, $field, $field_name, $field_id, $value );
            return;
        }

        // Message, HTML, separator, heading fields get full-width rendering
        if ( in_array( $type, [ 'message', 'html', 'separator', 'heading' ], true ) ) {
            ?>
            <tr<?php echo esc_attr( $row_attrs ); ?> class="setting-fields-row-fullwidth">
                <td colspan="2">
                    <?php $this->render_field( $field_key, $field, $field_name, $field_id, $value ); ?>
                </td>
            </tr>
            <?php
            return;
        }

        ?>
        <tr<?php echo esc_attr( $row_attrs ); ?>>
            <th scope="row">
                <?php if ( ! empty( $field['label'] ) ) : ?>
                    <label for="<?php echo esc_attr( $field_id ); ?>">
                        <?php echo esc_html( $field['label'] ); ?>
                        <?php if ( ! empty( $field['required'] ) ) : ?>
                            <span class="required">*</span>
                        <?php endif; ?>
                        <?php if ( $badge !== null ) : ?>
                            <?php self::render_badge( $badge ); ?>
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
                    // Returns markup this library assembled and escaped as it built it.
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
     * @param mixed  $fallback Default value.
     *
     * @return mixed
     */
    public function get_config( string $key, $fallback = null ) {
        return $this->config[ $key ] ?? $fallback;
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
     * @param mixed  $fallback   Default value.
     *
     * @return mixed
     */
    public function get_value( string $field_key, $fallback = null ) {
        $field = $this->fields[ $field_key ] ?? null;

        if ( ! $field ) {
            return $fallback;
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

        return $field['default'] ?? $fallback;
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
     * Supports dot notation for nested field paths (e.g., 'parent.child'
     * to access a sub_field within a repeater or group field).
     *
     * @param string $field_key Field key, optionally with dot notation.
     *
     * @return array|null
     */
    public function get_field( string $field_key ): ?array {
        if ( str_contains( $field_key, '.' ) ) {
            $parts  = explode( '.', $field_key, 2 );
            $parent = $this->fields[ $parts[0] ] ?? null;

            if ( ! $parent || ! isset( $parent['sub_fields'] ) ) {
                return null;
            }

            return $parent['sub_fields'][ $parts[1] ] ?? null;
        }

        return $this->fields[ $field_key ] ?? null;
    }
}