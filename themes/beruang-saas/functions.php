<?php
/**
 * Theme functions and definitions.
 *
 * @package BeruangSaaS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', 'beruang_saas_setup' );
add_action( 'init', 'beruang_saas_load_textdomain' );

/**
 * Load theme text domain.
 */
function beruang_saas_load_textdomain() {
	load_theme_textdomain( 'beruang-saas', get_template_directory() . '/languages' );
}

/**
 * Theme setup.
 */
function beruang_saas_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'automatic-feed-links' );
	add_filter( 'show_admin_bar', '__return_false' );
}

add_action( 'wp_enqueue_scripts', 'beruang_saas_enqueue_assets' );
add_action( 'wp_enqueue_scripts', 'beruang_saas_enqueue_account_assets', 20 );

/**
 * Enqueue theme scripts and styles.
 */
function beruang_saas_enqueue_assets() {
	$theme_ver = wp_get_theme()->get( 'Version' );
	$theme_dir = get_template_directory();
	$theme_uri = get_template_directory_uri();

	// CSS.
	$css_asset_file = $theme_dir . '/dist/css/theme-style.asset.php';
	$css_deps       = array();
	$css_ver        = $theme_ver;
	if ( file_exists( $css_asset_file ) ) {
		$css_asset = include $css_asset_file;
		$css_deps  = $css_asset['dependencies'] ?? array();
		$css_ver   = $css_asset['version'] ?? $css_ver;
	}
	wp_enqueue_style(
		'beruang-saas',
		$theme_uri . '/dist/css/theme-style.css',
		$css_deps,
		$css_ver
	);

	// JS.
	$js_asset_file = $theme_dir . '/dist/js/theme-script.asset.php';
	$js_deps       = array();
	$js_ver        = $theme_ver;
	if ( file_exists( $js_asset_file ) ) {
		$js_asset = include $js_asset_file;
		$js_deps  = $js_asset['dependencies'] ?? array();
		$js_ver   = $js_asset['version'] ?? $js_ver;
	}
	wp_enqueue_script(
		'beruang-saas',
		$theme_uri . '/dist/js/theme-script.js',
		$js_deps,
		$js_ver,
		true
	);
}

/**
 * Enqueue password strength meter on the account page template.
 */
function beruang_saas_enqueue_account_assets() {
	if ( ! is_page_template( 'templates/account.php' ) ) {
		return;
	}
	wp_enqueue_script( 'password-strength-meter' );
	wp_add_inline_script(
		'password-strength-meter',
		'(function(){
			var passField = document.getElementById("account-password");
			var strengthEl = document.getElementById("account-password-strength");
			if (!passField || !strengthEl) return;
			var labels = [
				' . wp_json_encode( __( 'Very weak', 'beruang-saas' ) ) . ',
				' . wp_json_encode( __( 'Weak', 'beruang-saas' ) ) . ',
				' . wp_json_encode( __( 'Medium', 'beruang-saas' ) ) . ',
				' . wp_json_encode( __( 'Strong', 'beruang-saas' ) ) . ',
				' . wp_json_encode( __( 'Very strong', 'beruang-saas' ) ) . '
			];
			passField.addEventListener("input", function () {
				var val = this.value;
				if (!val) {
					strengthEl.textContent = "";
					strengthEl.className = "account-password-strength";
					return;
				}
				if (typeof wp !== "undefined" && wp.passwordStrength && wp.passwordStrength.meter) {
					var strength = wp.passwordStrength.meter(val, [], val);
					strengthEl.textContent = labels[strength] || "";
					strengthEl.className = "account-password-strength strength-" + strength;
				}
			});
			var toggleBtn = document.getElementById("account-pw-toggle");
			var eyeOpen   = document.getElementById("account-pw-eye-open");
			var eyeClosed = document.getElementById("account-pw-eye-closed");
			if (toggleBtn) {
				toggleBtn.addEventListener("click", function () {
					var isPassword = passField.type === "password";
					passField.type = isPassword ? "text" : "password";
					toggleBtn.setAttribute("aria-pressed", isPassword ? "true" : "false");
					toggleBtn.setAttribute("aria-label", isPassword ? ' . wp_json_encode( __( 'Hide password', 'beruang-saas' ) ) . ' : ' . wp_json_encode( __( 'Show password', 'beruang-saas' ) ) . ');
					if (eyeOpen)   eyeOpen.style.display   = isPassword ? "none" : "";
					if (eyeClosed) eyeClosed.style.display = isPassword ? "" : "none";
				});
			}
		}());'
	);
}

/**
 * SVG icon definitions used by the theme.
 *
 * @return array<string, string>
 */
function beruang_saas_get_icons() {
	return array(
		'close'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="20" height="20"><path fill="rgb(0, 0, 0)" d="M183.1 137.4C170.6 124.9 150.3 124.9 137.8 137.4C125.3 149.9 125.3 170.2 137.8 182.7L275.2 320L137.9 457.4C125.4 469.9 125.4 490.2 137.9 502.7C150.4 515.2 170.7 515.2 183.2 502.7L320.5 365.3L457.9 502.6C470.4 515.1 490.7 515.1 503.2 502.6C515.7 490.1 515.7 469.8 503.2 457.3L365.8 320L503.1 182.6C515.6 170.1 515.6 149.8 503.1 137.3C490.6 124.8 470.3 124.8 457.8 137.3L320.5 274.7L183.1 137.4z"/></svg>',
		'hamburger' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="20" height="20"><path fill="currentColor" d="M96 160C96 142.3 110.3 128 128 128L512 128C529.7 128 544 142.3 544 160C544 177.7 529.7 192 512 192L128 192C110.3 192 96 177.7 96 160zM96 320C96 302.3 110.3 288 128 288L512 288C529.7 288 544 302.3 544 320C544 337.7 529.7 352 512 352L128 352C110.3 352 96 337.7 96 320zM544 480C544 497.7 529.7 512 512 512L128 512C110.3 512 96 497.7 96 480C96 462.3 110.3 448 128 448L512 448C529.7 448 544 462.3 544 480z"/></svg>',
		'profile'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="20" height="20"><path fill="currentColor" d="M470.5 463.6C451.4 416.9 405.5 384 352 384L288 384C234.5 384 188.6 416.9 169.5 463.6C133.9 426.3 112 375.7 112 320C112 205.1 205.1 112 320 112C434.9 112 528 205.1 528 320C528 375.7 506.1 426.2 470.5 463.6zM430.4 496.3C398.4 516.4 360.6 528 320 528C279.4 528 241.6 516.4 209.5 496.3C216.8 459.6 249.2 432 288 432L352 432C390.8 432 423.2 459.6 430.5 496.3zM320 576C461.4 576 576 461.4 576 320C576 178.6 461.4 64 320 64C178.6 64 64 178.6 64 320C64 461.4 178.6 576 320 576zM320 304C297.9 304 280 286.1 280 264C280 241.9 297.9 224 320 224C342.1 224 360 241.9 360 264C360 286.1 342.1 304 320 304zM232 264C232 312.6 271.4 352 320 352C368.6 352 408 312.6 408 264C408 215.4 368.6 176 320 176C271.4 176 232 215.4 232 264z"/></svg>',
	);
}

/**
 * Return SVG markup for a theme icon.
 *
 * @param string $name Icon name.
 * @param array  $args Optional args.
 * @return string
 */
function beruang_saas_get_icon( $name, $args = array() ) {
	$icons = beruang_saas_get_icons();

	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	$args = wp_parse_args(
		$args,
		array(
			'class' => '',
			'size'  => '',
			'attrs' => array(),
		)
	);

	$svg   = $icons[ $name ];
	$class = trim( 'beruang-saas-icon beruang-saas-icon-' . $name . ' ' . $args['class'] );

	if ( ! empty( $args['size'] ) ) {
		$size = esc_attr( $args['size'] );
		$svg  = preg_replace( '/width="[^"]+"/', 'width="' . $size . '"', $svg );
		$svg  = preg_replace( '/height="[^"]+"/', 'height="' . $size . '"', $svg );
	}

	$svg = preg_replace( '/<svg/', '<svg class="' . esc_attr( $class ) . '"', $svg );

	foreach ( $args['attrs'] as $key => $value ) {
		$svg = preg_replace( '/<svg/', '<svg ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"', $svg );
	}

	return $svg;
}

/**
 * Echo SVG markup for a theme icon.
 *
 * @param string $name Icon name.
 * @param array  $args Optional args.
 */
function beruang_saas_icon( $name, $args = array() ) {
	echo beruang_saas_get_icon( $name, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
