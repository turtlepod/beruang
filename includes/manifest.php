<?php
/**
 * Web App Manifest support for PWA installability.
 *
 * @package Beruang
 */

namespace Beruang;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register manifest hooks.
 */
function manifest_setup() {
	add_action( 'init', __NAMESPACE__ . '\manifest_register_rewrite' );
	add_filter( 'query_vars', __NAMESPACE__ . '\manifest_query_vars' );
	add_action( 'template_redirect', __NAMESPACE__ . '\manifest_serve', 1 );
	add_action( 'wp_head', __NAMESPACE__ . '\manifest_head_tags', 1 );
}

/**
 * Register rewrite rule for manifest.json.
 */
function manifest_register_rewrite() {
	add_rewrite_rule( '^manifest\.json$', 'index.php?beruang_manifest=1', 'top' );
}

/**
 * Add beruang_manifest to query vars.
 *
 * @param array<string> $vars Query vars.
 * @return array<string>
 */
function manifest_query_vars( $vars ) {
	$vars[] = 'beruang_manifest';
	return $vars;
}

/**
 * Serve manifest JSON when requested.
 */
function manifest_serve() {
	if ( ! get_query_var( 'beruang_manifest' ) ) {
		return;
	}

	if ( ! get_option( 'beruang_pwa_enabled', false ) ) {
		status_header( 404 );
		nocache_headers();
		echo '';
		exit;
	}

	$manifest = manifest_get_data();
	status_header( 200 );
	header( 'Content-Type: application/manifest+json; charset=' . get_bloginfo( 'charset' ) );
	header( 'X-Content-Type-Options: nosniff' );
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded manifest.
	echo wp_json_encode( $manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	exit;
}

/**
 * Output manifest link and theme-color meta in head when PWA enabled.
 */
function manifest_head_tags() {
	if ( ! get_option( 'beruang_pwa_enabled', false ) ) {
		return;
	}

	$manifest_url = home_url( '/manifest.json' );
	printf(
		"<link rel=\"manifest\" href=\"%s\">\n",
		esc_url( $manifest_url )
	);

	$theme_color = get_option( 'beruang_pwa_theme_color', '#2271b1' );
	if ( $theme_color ) {
		printf(
			"<meta name=\"theme-color\" content=\"%s\">\n",
			esc_attr( $theme_color )
		);
	}
}

/**
 * Build manifest data array.
 *
 * @return array<string, mixed>
 */
function manifest_get_data() {
	$app_name   = get_option( 'beruang_pwa_app_name', '' );
	$short_name = get_option( 'beruang_pwa_short_name', '' );
	if ( empty( $app_name ) ) {
		$app_name = get_bloginfo( 'name' );
	}
	if ( empty( $short_name ) ) {
		$short_name = function_exists( 'mb_substr' ) ? mb_substr( $app_name, 0, 12 ) : substr( $app_name, 0, 12 );
	}

	$start_url = home_url( '/' );
	$icons     = manifest_get_icons();

	$data = array(
		'name'             => $app_name,
		'short_name'       => $short_name,
		'start_url'        => $start_url,
		'display'          => 'standalone',
		'background_color' => '#ffffff',
		'theme_color'      => get_option( 'beruang_pwa_theme_color', '#2271b1' ),
	);

	if ( ! empty( $icons ) ) {
		$data['icons'] = $icons;
	}

	return $data;
}

/**
 * Get icon entries for manifest.
 *
 * @return array<int, array{src: string, sizes: string, type: string}>
 */
function manifest_get_icons() {
	$icon_192 = get_site_icon_url( 192 );
	$icon_512 = get_site_icon_url( 512 );

	if ( $icon_192 && $icon_512 ) {
		return array(
			array(
				'src'   => $icon_192,
				'sizes' => '192x192',
				'type'  => 'image/png',
			),
			array(
				'src'   => $icon_512,
				'sizes' => '512x512',
				'type'  => 'image/png',
			),
		);
	}

	// Fallback to plugin default icons.
	$base = BERUANG_PLUGIN_URL . 'assets/images/';
	return array(
		array(
			'src'   => $base . 'icon-192.png',
			'sizes' => '192x192',
			'type'  => 'image/png',
		),
		array(
			'src'   => $base . 'icon-512.png',
			'sizes' => '512x512',
			'type'  => 'image/png',
		),
	);
}
