<?php
/**
 * Setting Fields
 *
 * @package     ArrayPress\RegisterSettingFields
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterSettingFields;

use ArrayPress\FieldKit\Assets;
use ArrayPress\FieldKit\Context\ArrayContext;
use ArrayPress\FieldKit\Context\ConstantContext;
use ArrayPress\FieldKit\Context\EncryptedContext;
use ArrayPress\FieldKit\Context\OptionContext;
use ArrayPress\FieldKit\Contracts\Context;
use ArrayPress\FieldKit\Field;
use ArrayPress\FieldKit\FieldSet;
use ArrayPress\FieldKit\Support\Badge;
use ArrayPress\FieldKit\Support\Tooltip;
use ArrayPress\FieldKit\Support\PageHeader;

/**
 * Registers a tabbed settings page backed by a single option.
 *
 * Rendering, sanitizing, conditional logic, accessibility, the search
 * endpoint and the action endpoint all come from wp-field-kit. What is left
 * here is what is genuinely about a settings *page*: the menu entry, the
 * tabs, WordPress's Settings API wiring, and export, import and reset.
 *
 * Every write funnels through `sanitize()`, because that is what
 * `update_option()` calls. Import and reset therefore do not sanitize
 * anything themselves — they hand `update_option()` the array they want and
 * the registered callback does the rest.
 */
class SettingFields {

	/**
	 * This settings page's identifier.
	 *
	 * @var string
	 */
	private string $id;

	/**
	 * Page configuration.
	 *
	 * @var array<string, mixed>
	 */
	private array $config;

	/**
	 * Field configuration, keyed by field key.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $fields;

	/**
	 * Tabs, keyed by slug.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $tabs;

	/**
	 * Sections, keyed by slug.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $sections;

	/**
	 * Where values are read from, for rendering and for get_value().
	 *
	 * @var Context
	 */
	private Context $context;

	/**
	 * The option underneath the decorators.
	 *
	 * @var OptionContext
	 */
	private OptionContext $options;

	/**
	 * Field sets, keyed by tab.
	 *
	 * @var array<string, FieldSet>
	 */
	private array $sets = [];

	/**
	 * The screen's hook suffix, once the menu is registered.
	 *
	 * @var string
	 */
	private string $hook_suffix = '';

	/**
	 * Construct.
	 *
	 * @param string               $id     Settings page identifier.
	 * @param array<string, mixed> $config Page configuration.
	 */
	public function __construct( string $id, array $config ) {
		self::declare_config_keys();

		$this->id       = $id;
		$this->config   = $this->defaults( $config );
		$this->fields   = (array) ( $config['fields'] ?? [] );
		$this->tabs     = $this->parse_tabs( (array) ( $config['tabs'] ?? [] ) );
		$this->sections = (array) ( $config['sections'] ?? [] );
		$this->fields   = $this->apply_section_badges( $this->fields );

		$this->options = new OptionContext( (string) $this->config['option_name'] );
		$this->context = $this->decorate( $this->options );

		Registry::register( $id, $this );

		// Not deferred to admin_init, as core's own convention would have it.
		// The sanitize callback registered here is the gate every write to
		// this option passes through, including one made from cron or from a
		// webhook, and a gate that is only in place on admin screens is not
		// one. Nothing in register_setting() needs the admin to be loaded.
		$this->register_settings();

		// Built now, not when a tab renders. The set's constructor is what
		// registers a field's search source and its action handlers, and the
		// request that searches or presses a button is never the request that
		// drew the control — so a set built lazily registers them only on the
		// renders where nobody needs them. An action button on a settings
		// page came back "Unknown action." every time it was pressed.
		//
		// The whole-page set rather than a tab's: the names are the option
		// name and the field key, with no tab in them, so one set covers
		// every tab. It stores configuration and registers callbacks; the
		// fields themselves are still built on demand.
		$this->set();

		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
		add_filter( 'screen_settings', [ $this, 'render_screen_tools' ], 10, 2 );
		add_filter( 'admin_body_class', [ $this, 'body_class' ] );
		add_action( 'admin_post_' . $this->action_slug( 'export' ), [ $this, 'handle_export' ] );
		add_action( 'admin_post_' . $this->action_slug( 'import' ), [ $this, 'handle_import' ] );
		add_action( 'admin_post_' . $this->action_slug( 'reset' ), [ $this, 'handle_reset' ] );
	}

	/**
	 * Copy a badged section's badge onto each of its fields.
	 *
	 * A section badge locks the whole section. Resolving it here rather than
	 * at render time means every path downstream — rendering, sanitizing,
	 * saving — sees an ordinary badged field and needs no special case. In
	 * particular the save path skips a locked field, and a section that
	 * locked only visually would have had its values cleared by the next save
	 * because a disabled control sends nothing.
	 *
	 * A field's own badge wins: it is the more specific statement.
	 *
	 * @param array<string, array<string, mixed>> $fields Field configuration.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function apply_section_badges( array $fields ): array {
		foreach ( $this->sections as $slug => $section ) {
			if ( null === Badge::resolve( $section['badge'] ?? null ) ) {
				continue;
			}

			foreach ( $fields as $key => $field ) {
				if ( (string) ( $field['section'] ?? '' ) !== (string) $slug || isset( $field['badge'] ) ) {
					continue;
				}

				$fields[ $key ]['badge'] = $section['badge'];
			}
		}

		return $fields;
	}

	/**
	 * Wrap a store in this page's decorators.
	 *
	 * Order matters: a constant stands in for a value that would otherwise be
	 * decrypted on the way out, so it has to sit above encryption.
	 *
	 * @param Context $store The store to wrap.
	 *
	 * @return Context
	 */
	private function decorate( Context $store ): Context {
		return new ConstantContext(
			new EncryptedContext( $store ),
			(string) $this->config['constant_prefix']
		);
	}

	/**
	 * Merge the page configuration with its defaults.
	 *
	 * @param array<string, mixed> $config Supplied configuration.
	 *
	 * @return array<string, mixed>
	 */
	private function defaults( array $config ): array {
		$config = array_merge(
			[
				'page_title'      => $this->id,
				'menu_title'      => $this->id,
				'menu_slug'       => $this->id,
				'parent_slug'     => '',
				'capability'      => 'manage_options',
				'option_name'     => $this->id,
				'option_group'    => $this->id . '_group',
				'constant_prefix' => '',
				'header_title'    => '',
				'logo'            => '',
				'badge'           => '',
				'icon'            => 'dashicons-admin-generic',
				'position'        => null,
				'submit_button'   => true,
				'reset_button'    => false,
				'export_import'   => false,
				'help_tabs'       => [],
				'help_sidebar'    => '',
			],
			$config
		);

		if ( '' === (string) $config['constant_prefix'] ) {
			$config['constant_prefix'] = $config['option_name'] . '_';
		}

		return $config;
	}

	/**
	 * Normalize the tab configuration.
	 *
	 * Accepts both `'slug' => 'Label'` and the full array form, because both
	 * appear in existing configuration.
	 *
	 * @param array<string, mixed> $tabs Raw tabs.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function parse_tabs( array $tabs ): array {
		$parsed = [];

		foreach ( $tabs as $slug => $tab ) {
			$parsed[ (string) $slug ] = is_string( $tab )
				? [ 'label' => $tab ]
				: array_merge( [ 'label' => ucfirst( (string) $slug ) ], (array) $tab );
		}

		return $parsed;
	}

	/**
	 * Register the menu entry.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		$this->hook_suffix = (string) (
			'' !== (string) $this->config['parent_slug']
				? add_submenu_page(
					(string) $this->config['parent_slug'],
					(string) $this->config['page_title'],
					(string) $this->config['menu_title'],
					(string) $this->config['capability'],
					(string) $this->config['menu_slug'],
					[ $this, 'render_page' ]
				)
				: add_menu_page(
					(string) $this->config['page_title'],
					(string) $this->config['menu_title'],
					(string) $this->config['capability'],
					(string) $this->config['menu_slug'],
					[ $this, 'render_page' ],
					(string) $this->config['icon'],
					$this->config['position']
				)
		);

		if ( [] !== (array) $this->config['help_tabs'] || '' !== (string) $this->config['help_sidebar'] ) {
			add_action( 'load-' . $this->hook_suffix, [ $this, 'register_help_tabs' ] );
		}
	}

	/**
	 * Mark the screen as one using the kit's page header.
	 *
	 * The header only spans the screen if `#wpcontent`'s left padding is
	 * removed, and core does that with a body class rather than on the header
	 * itself. The class name comes from the kit so the rule and the class
	 * cannot drift apart.
	 *
	 * @param string $classes Existing body classes.
	 *
	 * @return string
	 */
	public function body_class( string $classes ): string {
		if ( '' === $this->hook_suffix || get_current_screen()?->id !== $this->hook_suffix ) {
			return $classes;
		}

		return trim( $classes . ' ' . PageHeader::body_class() );
	}

	/**
	 * Register the help tabs.
	 *
	 * @return void
	 */
	public function register_help_tabs(): void {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		foreach ( (array) $this->config['help_tabs'] as $tab_id => $tab ) {
			$screen->add_help_tab(
				[
					'id'      => (string) $tab_id,
					'title'   => (string) ( $tab['title'] ?? $tab_id ),
					'content' => (string) ( $tab['content'] ?? '' ),
				]
			);
		}

		if ( '' !== (string) $this->config['help_sidebar'] ) {
			$screen->set_help_sidebar( (string) $this->config['help_sidebar'] );
		}
	}

	/**
	 * Register the option with WordPress's Settings API.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			(string) $this->config['option_group'],
			(string) $this->config['option_name'],
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize' ],
				'default'           => [],
			]
		);
	}

	/**
	 * Produce the array to store from a submitted or supplied one.
	 *
	 * `update_option()` runs `sanitize_option()` before it compares values, so
	 * this is the single gate every write passes through — the form's, an
	 * import's, a reset's, and any that consuming code makes itself. Two
	 * consequences shape it.
	 *
	 * It must not write anything. A `update_option()` call from in here would
	 * re-enter this method from inside itself, so the values are collected in
	 * an array rather than staged in the option's own context.
	 *
	 * And it must be safe to run twice on its own output, because a caller is
	 * free to hand back what it just read. That is why an already-encrypted
	 * value passes through the encrypting context untouched.
	 *
	 * @param mixed $input Values to sanitize.
	 *
	 * @return array<string, mixed>
	 */
	public function sanitize( mixed $input ): array {
		$input = is_array( $input ) ? $input : [];

		// A submission carries one tab, so the fields it does not carry must
		// be left alone rather than read as cleared. Anything else — an
		// import, a reset, a direct update_option() — is a write of the whole
		// value and is sanitized against every field.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- options.php verifies the nonce before the option is sanitized.
		$tab = isset( $_POST[ $this->tab_input() ] ) ? sanitize_key( wp_unslash( $_POST[ $this->tab_input() ] ) ) : '';
		$tab = isset( $this->tabs[ $tab ] ) ? $tab : '';

		$collector = new ArrayContext( $this->seed( $tab ) );

		$set = new FieldSet(
			$this->fields_governed_by( $tab, $input ),
			$this->decorate( $collector ),
			(string) $this->config['option_name']
		);

		// Re-slashed because the Settings API unslashes before it gets here
		// and the field set unslashes at its own boundary. Without this the
		// values are unslashed twice and every backslash a user typed is
		// eaten by the save that stores it.
		$set->save( wp_slash( $input ) );

		return $this->ordered_like_stored( $collector->values() );
	}

	/**
	 * Put a sanitized array back into the order the option is stored in.
	 *
	 * Cosmetic in the database and not cosmetic above it. `update_option()`
	 * compares the new value with the old before deciding to write, and an
	 * array comparison is order-sensitive, so a pass that reordered the keys
	 * reported a change on every save that changed nothing — a write, a cache
	 * invalidation and an `updated_option` firing each time anything touched
	 * the option.
	 *
	 * @param array<string, mixed> $values Sanitized values.
	 *
	 * @return array<string, mixed>
	 */
	private function ordered_like_stored( array $values ): array {
		$ordered = [];

		foreach ( array_keys( $this->options->values() ) as $key ) {
			if ( array_key_exists( $key, $values ) ) {
				$ordered[ $key ] = $values[ $key ];
			}
		}

		// Anything the store did not already hold keeps the order it was
		// collected in, which is the order the fields are configured in.
		return $ordered + $values;
	}

	/**
	 * Which fields a sanitize pass governs.
	 *
	 * A form submission governs every field on the tab it came from, present
	 * or not: an unticked checkbox and an emptied select are both absent from
	 * a submission, and reading absence as "leave it alone" is how unticking
	 * a box never saves.
	 *
	 * A write that is not a submission governs only the keys it carries. The
	 * same reasoning inverted: nothing about `update_option( $option, [ 'a'
	 * => 1 ] )` says the caller has an opinion on the other forty fields, and
	 * treating absence as "off" there wrote a 0 for every checkbox and number
	 * field on the page on any write from a cron job or a helper.
	 *
	 * @param string               $tab   The tab being saved, or empty for a
	 *                                    whole-value write.
	 * @param array<string, mixed> $input The values being sanitized.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function fields_governed_by( string $tab, array $input ): array {
		if ( '' !== $tab ) {
			return $this->fields_for_tab( $tab );
		}

		return array_intersect_key( $this->fields, $input );
	}

	/**
	 * The values a sanitize pass starts from.
	 *
	 * @param string $tab The tab being saved, or empty for a whole-value write.
	 *
	 * @return array<string, mixed>
	 */
	private function seed( string $tab ): array {
		$stored = $this->options->values();

		if ( '' !== $tab ) {
			return $stored;
		}

		// A whole-value write governs every field, so no field carries over.
		// Keys the option holds that are not fields do: another plugin's
		// filter may have put them there and this is not the place to drop
		// them.
		return array_diff_key( $stored, $this->fields );
	}

	/**
	 * The field set for one tab.
	 *
	 * @param string $tab Restrict to one tab, or empty for every field.
	 *
	 * @return FieldSet
	 */
	private function set( string $tab = '' ): FieldSet {
		return $this->sets[ $tab ] ??= new FieldSet(
			'' === $tab ? $this->fields : $this->fields_for_tab( $tab ),
			$this->context,
			(string) $this->config['option_name']
		);
	}

	/**
	 * The fields belonging to one tab.
	 *
	 * A field with no tab of its own belongs to the first, so a page that
	 * grows tabs later does not lose the fields it already had.
	 *
	 * @param string $tab Tab slug.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function fields_for_tab( string $tab ): array {
		if ( [] === $this->tabs ) {
			return $this->fields;
		}

		$first = (string) array_key_first( $this->tabs );

		return array_filter(
			$this->fields,
			static fn( $field ) => (string) ( $field['tab'] ?? $first ) === $tab
		);
	}

	/**
	 * The tab currently being viewed.
	 *
	 * @return string
	 */
	private function current_tab(): string {
		// A tab is a view: it changes nothing and carries no nonce, exactly
		// as core's own tabbed screens read theirs.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$requested = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		return isset( $this->tabs[ $requested ] ) ? $requested : (string) array_key_first( $this->tabs );
	}

	/**
	 * The name of the hidden input carrying the submitted tab.
	 *
	 * Deliberately outside the option's own array: it is a fact about the
	 * submission, not a value to store.
	 *
	 * @return string
	 */
	private function tab_input(): string {
		return sanitize_key( $this->id . '_tab' );
	}

	/**
	 * Enqueue the kit, on this settings screen only.
	 *
	 * @param string $hook_suffix The current screen's hook.
	 *
	 * @return void
	 */
	public function enqueue( string $hook_suffix ): void {
		if ( '' === $this->hook_suffix || $hook_suffix !== $this->hook_suffix ) {
			return;
		}

		( new Assets() )->enqueue( $this->set( $this->current_tab() )->dependencies() );
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( (string) $this->config['capability'] ) ) {
			wp_die( esc_html__( 'You are not allowed to view this page.', 'arraypress' ) );
		}

		$tab = $this->current_tab();

		// Outside .wrap, exactly as options-privacy.php renders it. Inside,
		// .wrap's own margins inset the header and it stops looking like
		// core's — which is the whole point of using core's markup.
		//
		// It ends in the <hr class="wp-header-end"> that common.js moves
		// admin notices to.
		$header = PageHeader::render(
			[
				'title'   => (string) ( '' !== (string) $this->config['header_title'] ? $this->config['header_title'] : $this->config['page_title'] ),
				'logo'    => (string) $this->config['logo'],
				'badge'   => $this->config['badge'],
				'tabs'    => $this->tab_links(),
				'current' => $tab,
			]
		);

		echo $header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped as it is built.

		echo '<div class="wrap field-kit__settings">';

		// No argument: core adds its own "Settings saved." under the `general`
		// slug, and asking for this page's slug alone would hide it.
		settings_errors();

		printf( '<form method="post" action="%s">', esc_url( admin_url( 'options.php' ) ) );

		// Emits option_page, action and the nonce — and, through
		// wp_nonce_field(), the _wp_http_referer that sends the redirect back
		// to this tab rather than to the first one.
		settings_fields( (string) $this->config['option_group'] );

		printf(
			'<input type="hidden" name="%s" value="%s" />',
			esc_attr( $this->tab_input() ),
			esc_attr( $tab )
		);

		$this->render_sections( $tab );

		if ( $this->config['submit_button'] ) {
			submit_button();
		}

		echo '</form></div>';
	}

	/**
	 * Render one tab's sections and fields.
	 *
	 * @param string $tab Tab slug.
	 *
	 * @return void
	 */
	private function render_sections( string $tab ): void {
		$set    = $this->set( $tab );
		$fields = $set->fields();
		$shown  = [];

		foreach ( $this->sections as $slug => $section ) {
			$in_section = array_filter(
				$fields,
				static fn( Field $field ) => (string) $field->get( 'section', '' ) === (string) $slug
			);

			if ( [] === $in_section ) {
				continue;
			}

			$shown = array_merge( $shown, array_keys( $in_section ) );

			$badge = Badge::resolve( $section['badge'] ?? null );

			printf(
				'<h2 class="title">%s%s</h2>',
				esc_html( (string) ( $section['title'] ?? $slug ) ),
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped as it is built.
				null === $badge ? '' : Badge::render( $badge )
			);

			if ( '' !== (string) ( $section['description'] ?? '' ) ) {
				printf( '<p class="description">%s</p>', wp_kses_post( (string) $section['description'] ) );
			}

			$this->render_table( $set, $in_section );
		}

		// A field no section claimed still has to appear, or a typo in a
		// section name silently removes it from the page.
		$loose = array_diff_key( $fields, array_flip( $shown ) );

		if ( [] !== $loose ) {
			$this->render_table( $set, $loose );
		}
	}

	/**
	 * Render a set of fields as a settings table.
	 *
	 * @param FieldSet $set    The field set.
	 * @param Field[]  $fields The fields to render.
	 *
	 * @return void
	 */
	private function render_table( FieldSet $set, array $fields ): void {
		echo '<table class="form-table" role="presentation"><tbody>';

		foreach ( $fields as $field ) {
			$type = $field->type();

			// A heading, a notice or a separator is not a control, and an
			// email editor is a panel — neither belongs in a cell built for
			// one control beside one label.
			if ( $type->spans_row() ) {
				printf(
					'<tr class="field-kit__settings-row field-kit__spans-row"><td colspan="2">%s</td></tr>',
					$set->render_field( $field, '', false ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the kit escapes as it builds.
				);

				continue;
			}

			// A self-labelling control already carries its own <label for>,
			// and a group of controls has no single element to point at, so
			// both get plain text rather than a second label.
			// The badge belongs beside the heading, and the heading is here —
			// the renderer is told to draw none, so it draws no badge either.
			$badge = Badge::for_field( $field );

			// Beside the heading, which this class draws rather than the kit.
			$tooltip = Tooltip::for_field( $field );

			$header = $type->is_self_labelling() || $type->is_grouped()
				? sprintf(
					'<span class="field-kit__row-label">%s%s</span>',
					esc_html( $field->label() ),
					$badge . $tooltip // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped as it is built.
				)
				: sprintf(
					'<label for="%s">%s</label>%s',
					esc_attr( $field->input_id() ),
					esc_html( $field->label() ),
					$badge . $tooltip // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped as it is built.
				);

			printf(
				'<tr class="field-kit__settings-row"><th scope="row">%s</th><td>%s</td></tr>',
				$header, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_html/esc_attr above.
				// The header cell is the visible heading here, so the kit
				// draws none: a group keeps its legend, hidden, so the
				// grouping is still announced without appearing twice.
				$set->render_field( $field, '', false ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the kit escapes as it builds.
			);
		}

		echo '</tbody></table>';
	}

	/**
	 * The tab links for the header.
	 *
	 * @return array<string, array{label: string, url: string}>
	 */
	private function tab_links(): array {
		$links = [];

		foreach ( $this->tabs as $slug => $tab ) {
			$links[ $slug ] = [
				'label' => (string) $tab['label'],
				'url'   => $this->page_url( [ 'tab' => $slug ] ),
			];
		}

		return $links;
	}

	/**
	 * This page's URL.
	 *
	 * @param array<string, string> $args Extra query arguments.
	 *
	 * @return string
	 */
	private function page_url( array $args = [] ): string {
		return add_query_arg(
			array_merge( [ 'page' => (string) $this->config['menu_slug'] ], array_filter( $args ) ),
			admin_url( '' !== (string) $this->config['parent_slug'] ? (string) $this->config['parent_slug'] : 'admin.php' )
		);
	}

	/**
	 * Render the export, import and reset controls into Screen Options.
	 *
	 * They used to sit in the header, where a file input and three buttons
	 * across a centred title looked exactly as bad as it sounds. Screen
	 * Options is a panel WordPress already provides, it opens next to Help,
	 * and it is where a screen's occasional controls belong — not beside its
	 * title, which is read on every visit.
	 *
	 * Hooked on `screen_settings` rather than printed: that filter is also
	 * what makes the toggle appear at all, since core only shows Screen
	 * Options when something has put content in it.
	 *
	 * @param string    $settings Existing panel markup.
	 * @param \WP_Screen $screen   The current screen.
	 *
	 * @return string
	 */
	public function render_screen_tools( string $settings, $screen ): string {
		if ( '' === $this->hook_suffix || ! is_object( $screen ) || $this->hook_suffix !== ( $screen->id ?? '' ) ) {
			return $settings;
		}

		$tools = '';

		if ( $this->config['export_import'] ) {
			$tools .= $this->tool(
				__( 'Export', 'arraypress' ),
				__( 'Download this page\'s settings as a JSON file. Encrypted values are left out — they cannot be read on another site.', 'arraypress' ),
				$this->action_form( 'export', __( 'Export settings', 'arraypress' ), 'button' )
			);

			$tools .= $this->tool(
				__( 'Import', 'arraypress' ),
				__( 'Read a settings file back. Every value is sanitized by its own field, and anything that is not a field on this page is ignored.', 'arraypress' ),
				$this->import_form()
			);
		}

		if ( $this->config['reset_button'] ) {
			$tools .= $this->tool(
				__( 'Reset', 'arraypress' ),
				__( 'Restore every setting on the tab you are viewing to its default.', 'arraypress' ),
				$this->action_form(
					'reset',
					__( 'Reset this tab', 'arraypress' ),
					'button button-link-delete',
					__( 'Reset every setting on this tab to its default? This cannot be undone.', 'arraypress' )
				)
			);
		}

		return '' === $tools
			? $settings
			: $settings . sprintf( '<div class="field-kit__screen-tools">%s</div>', $tools );
	}

	/**
	 * One labelled group inside the Screen Options panel.
	 *
	 * @param string $title       Group heading.
	 * @param string $description What it does.
	 * @param string $controls    The controls themselves.
	 *
	 * @return string
	 */
	private function tool( string $title, string $description, string $controls ): string {
		// A fieldset with a legend, because these are groups of controls and
		// a legend is the only heading a group gets announced by. The legend
		// has to be the fieldset's first child, so the description goes after
		// it rather than wrapping it.
		return sprintf(
			'<fieldset class="field-kit__screen-tool">' .
			'<legend class="field-kit__screen-tool-title">%s</legend>' .
			'<p class="description">%s</p>' .
			'<div class="field-kit__screen-tool-controls">%s</div>' .
			'</fieldset>',
			esc_html( $title ),
			esc_html( $description ),
			$controls
		);
	}

	/**
	 * A single-button form posting to admin-post.
	 *
	 * A form rather than a link, because these change state and a link that
	 * changes state can be followed by a prefetch or a crawler.
	 *
	 * @param string $action  Action slug.
	 * @param string $label   Button label.
	 * @param string $classes Button classes.
	 * @param string $confirm Optional confirmation prompt.
	 *
	 * @return string
	 */
	private function action_form( string $action, string $label, string $classes, string $confirm = '' ): string {
		return sprintf(
			'<form method="post" action="%s"%s>%s' .
			'<input type="hidden" name="action" value="%s" />' .
			'<input type="hidden" name="tab" value="%s" />' .
			'<button type="submit" class="%s">%s</button></form>',
			esc_url( admin_url( 'admin-post.php' ) ),
			'' === $confirm ? '' : sprintf( ' onsubmit="return confirm(%s)"', esc_attr( (string) wp_json_encode( $confirm ) ) ),
			wp_nonce_field( $this->action_slug( $action ), '_wpnonce', false, false ),
			esc_attr( $this->action_slug( $action ) ),
			esc_attr( $this->current_tab() ),
			esc_attr( $classes ),
			esc_html( $label )
		);
	}

	/**
	 * The import form, which needs a file input.
	 *
	 * @return string
	 */
	private function import_form(): string {
		// core's own import form, from wp_import_upload_form(): a
		// .wp-upload-form, a *visible* label saying what to choose, the size
		// limit beside it, and a plain file input. A file input is what core
		// uses — hiding one behind a styled button is the part that would not
		// be core-like.
		//
		// Numbered throughout: the id appears twice, and mixing numbered with
		// unnumbered placeholders advances two separate counters.
		$bytes = wp_max_upload_size();

		return sprintf(
			'<form method="post" enctype="multipart/form-data" action="%1$s" class="wp-upload-form">%2$s' .
			'<input type="hidden" name="action" value="%3$s" />' .
			'<input type="hidden" name="max_file_size" value="%7$d" />' .
			'<p><label for="%4$s-import">%5$s</label> <span class="description">(%8$s)</span></p>' .
			'<p><input type="file" id="%4$s-import" name="import" accept="application/json,.json" required /></p>' .
			'<p class="submit"><button type="submit" class="button">%6$s</button></p></form>',
			esc_url( admin_url( 'admin-post.php' ) ),
			wp_nonce_field( $this->action_slug( 'import' ), '_wpnonce', false, false ),
			esc_attr( $this->action_slug( 'import' ) ),
			esc_attr( $this->id ),
			esc_html__( 'Choose a settings file from your computer:', 'arraypress' ),
			esc_html__( 'Import', 'arraypress' ),
			(int) $bytes,
			sprintf(
				/* translators: %s: maximum allowed upload size */
				esc_html__( 'Maximum size: %s', 'arraypress' ),
				esc_html( size_format( $bytes ) )
			)
		);
	}

	/**
	 * The admin-post action name for one operation.
	 *
	 * @param string $action Operation name.
	 *
	 * @return string
	 */
	private function action_slug( string $action ): string {
		return sanitize_key( $this->id . '_' . $action );
	}

	/**
	 * Send the settings as a JSON download.
	 *
	 * @return void
	 */
	public function handle_export(): void {
		$this->authorize( 'export' );

		$payload = (string) wp_json_encode( $this->export_payload(), JSON_PRETTY_PRINT );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $this->id . '-' . gmdate( 'Y-m-d' ) . '.json' ) . '"' );
		header( 'Content-Length: ' . strlen( $payload ) );

		echo $payload; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON, which escaping would corrupt.

		exit;
	}

	/**
	 * What an export writes.
	 *
	 * Separate from sending it because a method that ends in exit() cannot
	 * be asserted on, and what is left out of this is the part worth
	 * asserting on.
	 *
	 * @return array<string, mixed>
	 */
	public function export_payload(): array {
		$values = $this->options->values();

		// Encrypted values are left out rather than exported. The key comes
		// from this site's salts, so they cannot be read anywhere else — and
		// exporting them would put a credential in a file for no benefit.
		foreach ( $this->fields as $key => $field ) {
			if ( ! empty( $field['encrypted'] ) ) {
				unset( $values[ (string) $key ] );
			}
		}

		return [
			'id'      => $this->id,
			'version' => 1,
			'values'  => $values,
		];
	}

	/**
	 * Read settings back from an uploaded file.
	 *
	 * @return void
	 */
	public function handle_import(): void {
		$this->authorize( 'import' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in authorize().
		$file = isset( $_FILES['import'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_FILES['import'] ) ) : [];

		// is_uploaded_file() as well as the error code: it is what makes the
		// path PHP wrote the only one that can be read here.
		if ( UPLOAD_ERR_OK !== (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) || ! is_uploaded_file( (string) ( $file['tmp_name'] ?? '' ) ) ) {
			$this->redirect_with_notice( 'import_failed', __( 'No file was uploaded.', 'arraypress' ), 'error' );
		}

		$this->apply_import(
			json_decode( (string) file_get_contents( (string) $file['tmp_name'] ), true ) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local upload, not a remote request.
		);
	}

	/**
	 * Apply a decoded import and redirect.
	 *
	 * Separate from reading the upload so the decisions can be exercised
	 * without a real one — `is_uploaded_file()` is true of nothing a test can
	 * create, and weakening that check to make the test easier would trade a
	 * security property for a convenience.
	 *
	 * @param mixed $decoded The decoded file contents.
	 *
	 * @return void
	 */
	public function apply_import( mixed $decoded ): void {
		if ( ! is_array( $decoded ) || ! isset( $decoded['values'] ) || ! is_array( $decoded['values'] ) ) {
			$this->redirect_with_notice( 'import_failed', __( 'That file is not a settings export.', 'arraypress' ), 'error' );
		}

		if ( (string) ( $decoded['id'] ?? '' ) !== $this->id ) {
			$this->redirect_with_notice(
				'import_failed',
				sprintf(
					/* translators: 1: the page the file was exported from, 2: this page */
					__( 'That file is for "%1$s", not "%2$s".', 'arraypress' ),
					(string) ( $decoded['id'] ?? '' ),
					$this->id
				),
				'error'
			);
		}

		// Not written straight to the option: an uploaded file is untrusted,
		// and the registered sanitize callback is what makes every value pass
		// through its own field type. A key that is not a field is dropped
		// there rather than becoming part of the option.
		update_option( (string) $this->config['option_name'], $decoded['values'] );

		$this->redirect_with_notice( 'imported', __( 'Settings imported.', 'arraypress' ), 'success' );
	}

	/**
	 * Reset a tab's settings to their defaults.
	 *
	 * @return void
	 */
	public function handle_reset(): void {
		$this->authorize( 'reset' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in authorize().
		$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : '';
		$tab = isset( $this->tabs[ $tab ] ) ? $tab : '';

		$values = $this->options->values();

		foreach ( array_keys( '' === $tab ? $this->fields : $this->fields_for_tab( $tab ) ) as $key ) {
			unset( $values[ (string) $key ] );
		}

		// A field with no stored value renders its configured default, so
		// removing the keys is the reset. This runs through sanitize() like
		// every other write, and survives it because an already-encrypted
		// value is not encrypted again.
		update_option( (string) $this->config['option_name'], $values );

		$this->redirect_with_notice( 'reset', __( 'Settings reset.', 'arraypress' ), 'success' );
	}

	/**
	 * Verify the nonce and capability for an admin-post action.
	 *
	 * @param string $action Operation name.
	 *
	 * @return void
	 */
	private function authorize( string $action ): void {
		check_admin_referer( $this->action_slug( $action ) );

		if ( ! current_user_can( (string) $this->config['capability'] ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'arraypress' ) );
		}
	}

	/**
	 * Record a notice and go back to the settings page.
	 *
	 * @param string $code    Notice code.
	 * @param string $message Notice message.
	 * @param string $type    Notice type.
	 *
	 * @return void
	 */
	private function redirect_with_notice( string $code, string $message, string $type ): void {
		add_settings_error( (string) $this->config['option_name'], $code, $message, $type );

		// Where get_settings_errors() looks after a redirect, and the reason
		// the URL below carries settings-updated: without it the transient is
		// never read and the notice is silently dropped.
		set_transient( 'settings_errors', get_settings_errors(), 30 );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in authorize().
		$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : '';

		wp_safe_redirect(
			$this->page_url(
				[
					'tab'              => $tab,
					'settings-updated' => 'true',
				]
			)
		);

		exit;
	}

	/**
	 * Get this page's identifier.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Read a page configuration value.
	 *
	 * @param string $key      Configuration key.
	 * @param mixed  $fallback Returned when absent.
	 *
	 * @return mixed
	 */
	public function get_config( string $key, mixed $fallback = null ): mixed {
		return $this->config[ $key ] ?? $fallback;
	}

	/**
	 * Get the screen's hook suffix, once the menu has been registered.
	 *
	 * Empty before `admin_menu` has run. Public because a consumer hooking
	 * this screen — `load-{$hook}`, `admin_footer-{$hook}` — has no other way
	 * to name it, and guessing the form WordPress builds is how a hook ends
	 * up silently attached to nothing.
	 *
	 * @return string
	 */
	public function get_hook_suffix(): string {
		return $this->hook_suffix;
	}

	/**
	 * Get the option name these settings live under.
	 *
	 * @return string
	 */
	public function get_option_name(): string {
		return (string) $this->config['option_name'];
	}

	/**
	 * Get every stored value, as stored.
	 *
	 * Encrypted values are ciphertext here. Read those through get_value(),
	 * which goes through the decorators.
	 *
	 * @return array<string, mixed>
	 */
	public function get_values(): array {
		return $this->options->values();
	}

	/**
	 * Get one field's value, decrypted, or the constant standing in for it.
	 *
	 * @param string $field_key Field key.
	 * @param mixed  $fallback  Returned when the field has no value and no default.
	 *
	 * @return mixed
	 */
	public function get_value( string $field_key, mixed $fallback = null ): mixed {
		$field = $this->set()->field( $field_key );

		if ( null === $field ) {
			return $fallback;
		}

		$value = $field->value();

		return null === $value || '' === $value ? $fallback : $value;
	}

	/**
	 * Get the field configuration.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return $this->fields;
	}

	/**
	 * Get one field's configuration.
	 *
	 * @param string $field_key Field key.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_field( string $field_key ): ?array {
		return $this->fields[ $field_key ] ?? null;
	}

	/**
	 * Tell the kit which configuration this library reads.
	 *
	 * Which tab and which section of this page a field belongs to. Both are
	 * about the page's layout rather than the field, so they are read here.
	 * Without this the kit would report each of them as configuration
	 * nothing reads — which is the warning it exists to give, aimed at the
	 * wrong thing.
	 *
	 * @return void
	 */
	private static function declare_config_keys(): void {
		static $declared = false;

		if ( $declared ) {
			return;
		}

		$declared = true;

		Field::allow_config_keys( [ 'section', 'tab' ] );
	}
}
