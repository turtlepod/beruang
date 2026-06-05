<?php
/**
 * Features previously provided by Beruang Pro — now built into Beruang.
 *
 * Covers:
 *   - User locale on frontend
 *   - [beruang_install_button] shortcode
 *   - Theme directory registration (themes/beruang-saas)
 *   - Theme Settings admin page (drawer content, account page)
 *
 * @package Beruang
 */

namespace Beruang;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Constants ────────────────────────────────────────────────────────────────

const THEME_SETTINGS_OPTION = 'beruang_pro_theme_settings';
const THEME_SETTINGS_GROUP  = 'beruang_pro_theme_settings_group';
const THEME_ADMIN_SLUG      = 'beruang-theme-settings';

// ── Bootstrap ─────────────────────────────────────────────────────────────────

add_filter( 'determine_locale', __NAMESPACE__ . '\\use_logged_in_user_locale_on_frontend', 20 );
add_action( 'plugins_loaded', __NAMESPACE__ . '\\pro_on_plugins_loaded' );
add_action( 'plugins_loaded', __NAMESPACE__ . '\\pro_admin_setup' );

/**
 * Fires on plugins_loaded: register install-button shortcode and theme directory.
 */
function pro_on_plugins_loaded() {
	add_action( 'init', __NAMESPACE__ . '\\pro_register_shortcodes' );

	$themes_dir = BERUANG_PLUGIN_DIR . 'themes';
	if ( is_dir( $themes_dir ) ) {
		register_theme_directory( $themes_dir );
	}
}

// ── Locale ────────────────────────────────────────────────────────────────────

/**
 * Use the logged-in user's locale for frontend requests.
 *
 * WordPress applies user locale automatically in wp-admin, but not on
 * regular frontend requests. This aligns frontend locale with the profile
 * language setting.
 *
 * @param string $locale The currently determined locale.
 * @return string
 */
function use_logged_in_user_locale_on_frontend( $locale ) {
	if ( is_admin() || ! is_user_logged_in() ) {
		return $locale;
	}

	$user_locale = get_user_locale();
	return ! empty( $user_locale ) ? $user_locale : $locale;
}

// ── Shortcodes ────────────────────────────────────────────────────────────────

/**
 * Register pro shortcodes.
 */
function pro_register_shortcodes() {
	add_shortcode( 'beruang_install_button', __NAMESPACE__ . '\\shortcode_install_button' );
}

/**
 * Render install app button/link shortcode.
 *
 * Usage:
 * [beruang_install_button]
 * [beruang_install_button label="Install App" tag="a"]
 *
 * @param array<string, string> $atts Shortcode attrs.
 * @return string
 */
function shortcode_install_button( $atts ) {
	if ( ! get_option( 'beruang_pwa_enabled', false ) ) {
		return '';
	}

	$atts = shortcode_atts(
		array(
			'label' => __( 'Install App', 'beruang' ),
			'tag'   => 'button',
			'class' => '',
		),
		$atts,
		'beruang_install_button'
	);

	$tag          = 'a' === strtolower( $atts['tag'] ) ? 'a' : 'button';
	$label        = wp_strip_all_tags( (string) $atts['label'] );
	$custom_class = sanitize_html_class( (string) $atts['class'] );
	$class_attr   = trim( 'beruang-install-app-btn ' . $custom_class );

	if ( 'a' === $tag ) {
		return sprintf(
			'<a href="#" class="%1$s" data-beruang-install-app="true">%2$s</a>',
			esc_attr( $class_attr ),
			esc_html( $label )
		);
	}

	return sprintf(
		'<button type="button" class="%1$s" data-beruang-install-app="true">%2$s</button>',
		esc_attr( $class_attr ),
		esc_html( $label )
	);
}

// ── Theme Settings Admin ───────────────────────────────────────────────────────

/**
 * Hook admin menu and settings registration.
 */
function pro_admin_setup() {
	add_action( 'admin_menu', __NAMESPACE__ . '\\pro_admin_register_menu', 20 );
	add_action( 'admin_init', __NAMESPACE__ . '\\register_theme_settings' );
}

/**
 * Add Theme Settings submenu under Beruang admin menu.
 */
function pro_admin_register_menu() {
	add_submenu_page(
		'beruang',
		__( 'Theme Settings', 'beruang' ),
		__( 'Theme Settings', 'beruang' ),
		'manage_options',
		THEME_ADMIN_SLUG,
		__NAMESPACE__ . '\\render_theme_settings_page'
	);
}

/**
 * Register theme settings fields.
 */
function register_theme_settings() {
	register_setting(
		THEME_SETTINGS_GROUP,
		THEME_SETTINGS_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_theme_settings',
			'default'           => array(),
		)
	);

	add_settings_section(
		'beruang_theme_settings_section',
		__( 'Theme Settings', 'beruang' ),
		'__return_empty_string',
		THEME_ADMIN_SLUG
	);

	add_settings_field(
		'drawer_content',
		__( 'Content in Site Drawer', 'beruang' ),
		__NAMESPACE__ . '\\render_drawer_content_field',
		THEME_ADMIN_SLUG,
		'beruang_theme_settings_section'
	);

	add_settings_field(
		'account_page_id',
		__( 'Account Page', 'beruang' ),
		__NAMESPACE__ . '\\render_account_page_field',
		THEME_ADMIN_SLUG,
		'beruang_theme_settings_section'
	);
}

/**
 * Sanitize theme settings.
 *
 * @param mixed $input Raw settings input.
 * @return array<string, mixed>
 */
function sanitize_theme_settings( $input ) {
	$input = is_array( $input ) ? $input : array();

	return array(
		'drawer_content'  => isset( $input['drawer_content'] ) ? wp_kses_post( $input['drawer_content'] ) : '',
		'account_page_id' => isset( $input['account_page_id'] ) ? absint( $input['account_page_id'] ) : 0,
	);
}

/**
 * Get all theme settings.
 *
 * @return array<string, mixed>
 */
function get_theme_settings() {
	$defaults = array(
		'drawer_content'  => '',
		'account_page_id' => 0,
	);

	$settings = get_option( THEME_SETTINGS_OPTION, array() );

	if ( ! is_array( $settings ) ) {
		return $defaults;
	}

	return wp_parse_args( $settings, $defaults );
}

/**
 * Render drawer content settings field.
 */
function render_drawer_content_field() {
	$settings = get_theme_settings();
	wp_editor(
		$settings['drawer_content'],
		'beruang_drawer_content',
		array(
			'textarea_name' => THEME_SETTINGS_OPTION . '[drawer_content]',
			'textarea_rows' => 8,
			'media_buttons' => false,
		)
	);
}

/**
 * Render account page settings field.
 */
function render_account_page_field() {
	$settings   = get_theme_settings();
	$field_name = THEME_SETTINGS_OPTION . '[account_page_id]';
	$none_label = __( 'Select a page', 'beruang' );

	$dropdown = wp_dropdown_pages(
		array(
			'name'              => $field_name, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- API argument, not direct output.
			'id'                => 'beruang-account-page-id',
			'show_option_none'  => $none_label, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- API argument, not direct output.
			'option_none_value' => '0',
			'selected'          => absint( $settings['account_page_id'] ),
			'echo'              => 0,
		)
	);

	if ( is_string( $dropdown ) ) {
		echo $dropdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core-generated select markup.
	}
}

/**
 * Render Theme Settings page.
 */
function render_theme_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Theme Settings', 'beruang' ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( THEME_SETTINGS_GROUP );
			do_settings_sections( THEME_ADMIN_SLUG );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Get site drawer content for theme rendering.
 *
 * @return string
 */
function get_theme_drawer_content() {
	$settings = get_theme_settings();
	return is_string( $settings['drawer_content'] ) ? $settings['drawer_content'] : '';
}

/**
 * Get account page ID for theme rendering.
 *
 * @return int
 */
function get_theme_account_page_id() {
	$settings = get_theme_settings();
	return absint( $settings['account_page_id'] );
}

/**
 * Get account page URL for theme rendering.
 *
 * @return string
 */
function get_theme_account_page_url() {
	$page_id = get_theme_account_page_id();
	if ( $page_id <= 0 ) {
		return '';
	}

	$url = get_permalink( $page_id );
	return $url ? $url : '';
}
