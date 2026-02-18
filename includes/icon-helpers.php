<?php
/**
 * SVG icon helper functions for Mowi plugin.
 *
 * @package Mowi
 */

namespace Mowi;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SVG icon definitions.
 *
 * @return array<string, string> Icon name => SVG markup.
 */
function mowi_get_icons() {
	return array(
		'calc' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 640 640"><path fill="currentColor" d="M192 64C156.7 64 128 92.7 128 128L128 512C128 547.3 156.7 576 192 576L448 576C483.3 576 512 547.3 512 512L512 128C512 92.7 483.3 64 448 64L192 64zM224 128L416 128C433.7 128 448 142.3 448 160L448 192C448 209.7 433.7 224 416 224L224 224C206.3 224 192 209.7 192 192L192 160C192 142.3 206.3 128 224 128zM240 296C240 309.3 229.3 320 216 320C202.7 320 192 309.3 192 296C192 282.7 202.7 272 216 272C229.3 272 240 282.7 240 296zM320 320C306.7 320 296 309.3 296 296C296 282.7 306.7 272 320 272C333.3 272 344 282.7 344 296C344 309.3 333.3 320 320 320zM448 296C448 309.3 437.3 320 424 320C410.7 320 400 309.3 400 296C400 282.7 410.7 272 424 272C437.3 272 448 282.7 448 296zM216 416C202.7 416 192 405.3 192 392C192 378.7 202.7 368 216 368C229.3 368 240 378.7 240 392C240 405.3 229.3 416 216 416zM344 392C344 405.3 333.3 416 320 416C306.7 416 296 405.3 296 392C296 378.7 306.7 368 320 368C333.3 368 344 378.7 344 392zM424 416C410.7 416 400 405.3 400 392C400 378.7 410.7 368 424 368C437.3 368 448 378.7 448 392C448 405.3 437.3 416 424 416zM192 488C192 474.7 202.7 464 216 464L328 464C341.3 464 352 474.7 352 488C352 501.3 341.3 512 328 512L216 512C202.7 512 192 501.3 192 488zM424 464C437.3 464 448 474.7 448 488C448 501.3 437.3 512 424 512C410.7 512 400 501.3 400 488C400 474.7 410.7 464 424 464z"/></svg>',
		'list' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 640 640"><path fill="currentColor" d="M112 208C138.5 208 160 186.5 160 160C160 133.5 138.5 112 112 112C85.5 112 64 133.5 64 160C64 186.5 85.5 208 112 208zM256 128C238.3 128 224 142.3 224 160C224 177.7 238.3 192 256 192L544 192C561.7 192 576 177.7 576 160C576 142.3 561.7 128 544 128L256 128zM256 288C238.3 288 224 302.3 224 320C224 337.7 238.3 352 256 352L544 352C561.7 352 576 337.7 576 320C576 302.3 561.7 288 544 288L256 288zM256 448C238.3 448 224 462.3 224 480C224 497.7 238.3 512 256 512L544 512C561.7 512 576 497.7 576 480C576 462.3 561.7 448 544 448L256 448zM112 528C138.5 528 160 506.5 160 480C160 453.5 138.5 432 112 432C85.5 432 64 453.5 64 480C64 506.5 85.5 528 112 528zM160 320C160 293.5 138.5 272 112 272C85.5 272 64 293.5 64 320C64 346.5 85.5 368 112 368C138.5 368 160 346.5 160 320z"/></svg>',
	);
}

/**
 * Output an SVG icon.
 *
 * @param string   $name Icon name (e.g. 'calc', 'manage').
 * @param array    $args Optional. {
 *     @type string $class   CSS class for the wrapper.
 *     @type string $size   Width/height, e.g. '20' or '1.25em'.
 *     @type array  $attrs  Additional attributes for the SVG.
 * }
 */
function mowi_icon( $name, $args = array() ) {
	$icons = mowi_get_icons();
	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	$defaults = array(
		'class' => '',
		'size'  => '',
		'attrs' => array(),
	);
	$args     = wp_parse_args( $args, $defaults );

	$svg = $icons[ $name ];
	$class = trim( 'mowi-icon mowi-icon-' . $name . ' ' . $args['class'] );

	// Inject size if provided.
	if ( ! empty( $args['size'] ) ) {
		$size = esc_attr( $args['size'] );
		$svg  = preg_replace( '/width="[^"]+"/', 'width="' . $size . '"', $svg );
		$svg  = preg_replace( '/height="[^"]+"/', 'height="' . $size . '"', $svg );
	}

	// Inject class.
	$svg = preg_replace( '/<svg/', '<svg class="' . esc_attr( $class ) . '"', $svg );

	// Merge extra attrs.
	foreach ( $args['attrs'] as $k => $v ) {
		$svg = preg_replace( '/<svg/', '<svg ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"', $svg );
	}

	echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return an SVG icon markup.
 *
 * @param string $name Icon name.
 * @param array  $args Same as mowi_icon().
 * @return string SVG markup.
 */
function mowi_get_icon( $name, $args = array() ) {
	ob_start();
	mowi_icon( $name, $args );
	return ob_get_clean();
}
