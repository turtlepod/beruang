<?php
/**
 * Unit tests for the DB class.
 *
 * @package Beruang
 */

namespace Beruang\Tests\Unit;

use Beruang\DB;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;
use WP_Mock\Tools\TestCase;

/**
 * Tests for Beruang\DB.
 *
 * Covers:
 *  - Table-name helpers (table_category, table_wallet, etc.)
 *  - Private normalize_wallet_date()
 *  - Private normalize_wallet_id()
 */
class DBTest extends TestCase {

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Reset the cached static $wpdb property on DB and inject a fake $wpdb.
	 *
	 * @param string $prefix Database table prefix. Default 'wp_'.
	 */
	private function setUpWpdb( string $prefix = 'wp_' ): void {
		$wpdb         = new stdClass();
		$wpdb->prefix = $prefix;

		$GLOBALS['wpdb'] = $wpdb;

		// The DB class caches $wpdb in a private static property.
		// Reset it so the next call re-reads $GLOBALS['wpdb'].
		$prop = new ReflectionProperty( DB::class, 'wpdb' );
		$prop->setAccessible( true );
		$prop->setValue( null, null );
	}

	/**
	 * Invoke a private static method on DB and return the result.
	 *
	 * @param string $method Method name.
	 * @param array  $args   Arguments to pass.
	 * @return mixed
	 */
	private function callPrivateStatic( string $method, array $args = [] ): mixed {
		$ref = new ReflectionMethod( DB::class, $method );
		$ref->setAccessible( true );
		return $ref->invokeArgs( null, $args );
	}

	/**
	 * Tear down: clean up global wpdb and reset the DB static cache.
	 */
	public function tearDown(): void {
		parent::tearDown();
		unset( $GLOBALS['wpdb'] );
		// Reset cached wpdb so subsequent test suites start clean.
		$prop = new ReflectionProperty( DB::class, 'wpdb' );
		$prop->setAccessible( true );
		$prop->setValue( null, null );
	}

	// -----------------------------------------------------------------------
	// Table-name helpers
	// -----------------------------------------------------------------------

	/**
	 * @covers \Beruang\DB::table_category
	 */
	public function test_table_category_uses_wpdb_prefix(): void {
		$this->setUpWpdb( 'wp_' );
		$this->assertSame( 'wp_beruang_category', DB::table_category() );
	}

	/**
	 * @covers \Beruang\DB::table_wallet
	 */
	public function test_table_wallet_uses_wpdb_prefix(): void {
		$this->setUpWpdb( 'mysite_' );
		$this->assertSame( 'mysite_beruang_wallet', DB::table_wallet() );
	}

	/**
	 * @covers \Beruang\DB::table_transaction
	 */
	public function test_table_transaction_uses_wpdb_prefix(): void {
		$this->setUpWpdb( 'wp_' );
		$this->assertSame( 'wp_beruang_transaction', DB::table_transaction() );
	}

	/**
	 * @covers \Beruang\DB::table_budget
	 */
	public function test_table_budget_uses_wpdb_prefix(): void {
		$this->setUpWpdb( 'wp_' );
		$this->assertSame( 'wp_beruang_budget', DB::table_budget() );
	}

	/**
	 * @covers \Beruang\DB::table_budget_category
	 */
	public function test_table_budget_category_uses_wpdb_prefix(): void {
		$this->setUpWpdb( 'wp_' );
		$this->assertSame( 'wp_beruang_budget_category', DB::table_budget_category() );
	}

	// -----------------------------------------------------------------------
	// normalize_wallet_date (private static)
	// -----------------------------------------------------------------------

	/**
	 * @covers \Beruang\DB::normalize_wallet_date
	 */
	public function test_normalize_wallet_date_valid_date_is_returned_unchanged(): void {
		$result = $this->callPrivateStatic( 'normalize_wallet_date', array( '2024-03-15' ) );
		$this->assertSame( '2024-03-15', $result );
	}

	/**
	 * @covers \Beruang\DB::normalize_wallet_date
	 */
	public function test_normalize_wallet_date_another_valid_date(): void {
		$result = $this->callPrivateStatic( 'normalize_wallet_date', array( '2000-01-01' ) );
		$this->assertSame( '2000-01-01', $result );
	}

	/**
	 * @covers \Beruang\DB::normalize_wallet_date
	 */
	public function test_normalize_wallet_date_empty_string_falls_back_to_date_format(): void {
		$result = $this->callPrivateStatic( 'normalize_wallet_date', array( '' ) );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}$/', $result );
	}

	/**
	 * @covers \Beruang\DB::normalize_wallet_date
	 */
	public function test_normalize_wallet_date_null_falls_back_to_date_format(): void {
		$result = $this->callPrivateStatic( 'normalize_wallet_date', array( null ) );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}$/', $result );
	}

	/**
	 * @covers \Beruang\DB::normalize_wallet_date
	 */
	public function test_normalize_wallet_date_no_hyphens_falls_back(): void {
		$result = $this->callPrivateStatic( 'normalize_wallet_date', array( '20240315' ) );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}$/', $result );
	}

	/**
	 * @covers \Beruang\DB::normalize_wallet_date
	 */
	public function test_normalize_wallet_date_partial_format_falls_back(): void {
		$result = $this->callPrivateStatic( 'normalize_wallet_date', array( '2024-03' ) );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}$/', $result );
	}

	/**
	 * @covers \Beruang\DB::normalize_wallet_date
	 */
	public function test_normalize_wallet_date_non_date_string_falls_back(): void {
		$result = $this->callPrivateStatic( 'normalize_wallet_date', array( 'not-a-date' ) );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}$/', $result );
	}

	/**
	 * @covers \Beruang\DB::normalize_wallet_date
	 */
	public function test_normalize_wallet_date_integer_input_falls_back(): void {
		$result = $this->callPrivateStatic( 'normalize_wallet_date', array( 20240315 ) );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}$/', $result );
	}

	// -----------------------------------------------------------------------
	// normalize_wallet_id (private static)
	// -----------------------------------------------------------------------

	/**
	 * @covers \Beruang\DB::normalize_wallet_id
	 */
	public function test_normalize_wallet_id_positive_integer_is_returned(): void {
		$result = $this->callPrivateStatic( 'normalize_wallet_id', array( 5 ) );
		$this->assertSame( 5, $result );
	}

	/**
	 * @covers \Beruang\DB::normalize_wallet_id
	 */
	public function test_normalize_wallet_id_positive_string_is_cast_to_int(): void {
		$result = $this->callPrivateStatic( 'normalize_wallet_id', array( '42' ) );
		$this->assertSame( 42, $result );
	}

	/**
	 * @covers \Beruang\DB::normalize_wallet_id
	 */
	public function test_normalize_wallet_id_zero_returns_null(): void {
		$result = $this->callPrivateStatic( 'normalize_wallet_id', array( 0 ) );
		$this->assertNull( $result );
	}

	/**
	 * @covers \Beruang\DB::normalize_wallet_id
	 */
	public function test_normalize_wallet_id_zero_string_returns_null(): void {
		$result = $this->callPrivateStatic( 'normalize_wallet_id', array( '0' ) );
		$this->assertNull( $result );
	}

	/**
	 * absint(-3) = 3 which is > 0, so the method returns 3, not null.
	 *
	 * @covers \Beruang\DB::normalize_wallet_id
	 */
	public function test_normalize_wallet_id_negative_returns_absint(): void {
		$result = $this->callPrivateStatic( 'normalize_wallet_id', array( -3 ) );
		$this->assertSame( 3, $result );
	}

	/**
	 * @covers \Beruang\DB::normalize_wallet_id
	 */
	public function test_normalize_wallet_id_null_returns_null(): void {
		$result = $this->callPrivateStatic( 'normalize_wallet_id', array( null ) );
		$this->assertNull( $result );
	}

	/**
	 * @covers \Beruang\DB::normalize_wallet_id
	 */
	public function test_normalize_wallet_id_empty_string_returns_null(): void {
		$result = $this->callPrivateStatic( 'normalize_wallet_id', array( '' ) );
		$this->assertNull( $result );
	}
}
