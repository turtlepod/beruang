<?php
/**
 * Unit tests for icon helper functions.
 *
 * @package Beruang
 */

namespace BeruangBudget\Tests\Unit;

use WP_Mock\Tools\TestCase;

/**
 * Tests for beruang_get_icons() and beruang_get_icon().
 */
class IconHelpersTest extends TestCase {

	// -----------------------------------------------------------------------
	// beruang_get_icons()
	// -----------------------------------------------------------------------

	/**
	 * @covers ::beruang_get_icons
	 */
	public function test_get_icons_returns_array(): void {
		$icons = \BeruangBudget\beruang_get_icons();
		$this->assertIsArray( $icons );
	}

	/**
	 * @covers ::beruang_get_icons
	 */
	public function test_get_icons_has_all_expected_keys(): void {
		$icons    = \BeruangBudget\beruang_get_icons();
		$expected = array( 'close', 'calc', 'list', 'filter', 'add', 'edit', 'note', 'trash', 'backspace', 'transfer' );
		foreach ( $expected as $key ) {
			$this->assertArrayHasKey( $key, $icons, "Expected icon key '$key' not found." );
		}
	}

	/**
	 * @covers ::beruang_get_icons
	 */
	public function test_get_icons_has_backspace_icon(): void {
		$icons = \BeruangBudget\beruang_get_icons();
		$this->assertArrayHasKey( 'backspace', $icons );
		$this->assertStringContainsString( '<svg', $icons['backspace'] );
	}

	/**
	 * @covers ::beruang_get_icons
	 */
	public function test_get_icons_has_transfer_icon(): void {
		$icons = \BeruangBudget\beruang_get_icons();
		$this->assertArrayHasKey( 'transfer', $icons );
		$this->assertStringContainsString( '<svg', $icons['transfer'] );
	}

	/**
	 * @covers ::beruang_get_icons
	 */
	public function test_get_icons_values_are_non_empty_strings(): void {
		foreach ( \BeruangBudget\beruang_get_icons() as $name => $markup ) {
			$this->assertIsString( $markup, "Icon '$name' value should be a string." );
			$this->assertNotEmpty( $markup, "Icon '$name' value should not be empty." );
		}
	}

	/**
	 * @covers ::beruang_get_icons
	 */
	public function test_get_icons_values_contain_svg_tag(): void {
		foreach ( \BeruangBudget\beruang_get_icons() as $name => $markup ) {
			$this->assertStringContainsString( '<svg', $markup, "Icon '$name' should contain an <svg> tag." );
		}
	}

	// -----------------------------------------------------------------------
	// beruang_get_icon()  /  beruang_icon()
	// -----------------------------------------------------------------------

	/**
	 * @covers ::beruang_get_icon
	 */
	public function test_get_icon_unknown_name_returns_empty_string(): void {
		$result = \BeruangBudget\beruang_get_icon( 'does-not-exist' );
		$this->assertSame( '', $result );
	}

	/**
	 * @covers ::beruang_get_icon
	 */
	public function test_get_icon_returns_non_empty_svg_for_known_name(): void {
		$result = \BeruangBudget\beruang_get_icon( 'close' );
		$this->assertNotEmpty( $result );
		$this->assertStringContainsString( '<svg', $result );
	}

	/**
	 * @covers ::beruang_get_icon
	 */
	public function test_get_icon_injects_default_class(): void {
		$result = \BeruangBudget\beruang_get_icon( 'close' );
		$this->assertStringContainsString( 'class="beruang-icon beruang-icon-close"', $result );
	}

	/**
	 * @covers ::beruang_get_icon
	 */
	public function test_get_icon_appends_custom_class(): void {
		$result = \BeruangBudget\beruang_get_icon( 'close', array( 'class' => 'my-custom-class' ) );
		$this->assertStringContainsString( 'beruang-icon-close my-custom-class', $result );
	}

	/**
	 * @covers ::beruang_get_icon
	 */
	public function test_get_icon_replaces_width_when_size_provided(): void {
		$result = \BeruangBudget\beruang_get_icon( 'close', array( 'size' => '32' ) );
		$this->assertStringContainsString( 'width="32"', $result );
		$this->assertStringNotContainsString( 'width="20"', $result );
	}

	/**
	 * @covers ::beruang_get_icon
	 */
	public function test_get_icon_replaces_height_when_size_provided(): void {
		$result = \BeruangBudget\beruang_get_icon( 'close', array( 'size' => '32' ) );
		$this->assertStringContainsString( 'height="32"', $result );
		$this->assertStringNotContainsString( 'height="20"', $result );
	}

	/**
	 * @covers ::beruang_get_icon
	 */
	public function test_get_icon_injects_extra_attrs(): void {
		$result = \BeruangBudget\beruang_get_icon(
			'close',
			array(
				'attrs' => array( 'aria-hidden' => 'true' ),
			)
		);
		$this->assertStringContainsString( 'aria-hidden="true"', $result );
	}
}
