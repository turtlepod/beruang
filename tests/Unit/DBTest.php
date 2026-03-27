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
	// sum_net_amount
	// -----------------------------------------------------------------------

	/**
	 * Inject a $wpdb mock that supports prepare() and get_var().
	 *
	 * @param string $prefix     DB table prefix.
	 * @param string $get_var_return Value get_var() should return.
	 * @return object The mock.
	 */
	private function setUpQueryWpdb( string $prefix = 'wp_', string $get_var_return = '0' ): object {
		$wpdb         = $this->getMockBuilder( \stdClass::class )
			->addMethods( [ 'prepare', 'get_var' ] )
			->getMock();
		$wpdb->prefix = $prefix;
		$wpdb->method( 'prepare' )->willReturnCallback(
			function ( $query ) {
				return $query;
			}
		);
		$wpdb->method( 'get_var' )->willReturn( $get_var_return );

		$GLOBALS['wpdb'] = $wpdb;

		$prop = new ReflectionProperty( DB::class, 'wpdb' );
		$prop->setAccessible( true );
		$prop->setValue( null, null );

		return $wpdb;
	}

	/**
	 * @covers \Beruang\DB::sum_net_amount
	 */
	public function test_sum_net_amount_returns_float(): void {
		$this->setUpQueryWpdb( 'wp_', '150.50' );
		$result = DB::sum_net_amount( 1, '2024-01-01', '2024-01-31' );
		$this->assertSame( 150.50, $result );
	}

	/**
	 * @covers \Beruang\DB::sum_net_amount
	 */
	public function test_sum_net_amount_returns_zero_when_no_transactions(): void {
		$this->setUpQueryWpdb( 'wp_', '0' );
		$result = DB::sum_net_amount( 1, '2024-01-01', '2024-01-31' );
		$this->assertSame( 0.0, $result );
	}

	/**
	 * Net amount can be negative when income exceeds expenses.
	 *
	 * @covers \Beruang\DB::sum_net_amount
	 */
	public function test_sum_net_amount_returns_negative_when_income_exceeds_expenses(): void {
		$this->setUpQueryWpdb( 'wp_', '-50.00' );
		$result = DB::sum_net_amount( 1, '2024-01-01', '2024-01-31' );
		$this->assertSame( -50.0, $result );
	}

	/**
	 * @covers \Beruang\DB::sum_net_amount
	 */
	public function test_sum_net_amount_with_category_ids_calls_prepare(): void {
		$wpdb = $this->setUpQueryWpdb( 'wp_', '200.00' );
		$wpdb->expects( $this->once() )
			->method( 'prepare' )
			->with( $this->stringContains( 'category_id IN' ) )
			->willReturn( 'SELECT ...' );
		$result = DB::sum_net_amount( 1, '2024-01-01', '2024-01-31', [ 3, 7 ] );
		$this->assertSame( 200.0, $result );
	}

	/**
	 * @covers \Beruang\DB::sum_net_amount
	 */
	public function test_sum_net_amount_without_category_ids_omits_category_filter(): void {
		$wpdb = $this->setUpQueryWpdb( 'wp_', '75.00' );
		$wpdb->expects( $this->once() )
			->method( 'prepare' )
			->with( $this->logicalNot( $this->stringContains( 'category_id IN' ) ) )
			->willReturn( 'SELECT ...' );
		DB::sum_net_amount( 1, '2024-01-01', '2024-01-31' );
	}

	// -----------------------------------------------------------------------
	// get_categories_flat (public static)
	// -----------------------------------------------------------------------

	/**
	 * Inject a $wpdb mock that supports prepare() and get_results().
	 *
	 * @param array $get_results_sequence  Consecutive return values for get_results().
	 * @return object The mock.
	 */
	private function setUpCategoriesWpdb( array $get_results_sequence ): object {
		$wpdb         = $this->getMockBuilder( \stdClass::class )
			->addMethods( [ 'prepare', 'get_results' ] )
			->getMock();
		$wpdb->prefix = 'wp_';
		$wpdb->method( 'prepare' )->willReturnCallback(
			function ( $query ) {
				return $query;
			}
		);
		$wpdb->method( 'get_results' )->willReturnOnConsecutiveCalls( ...$get_results_sequence );

		$GLOBALS['wpdb'] = $wpdb;

		$prop = new ReflectionProperty( DB::class, 'wpdb' );
		$prop->setAccessible( true );
		$prop->setValue( null, null );

		return $wpdb;
	}

	/**
	 * @covers \Beruang\DB::get_categories_flat
	 */
	public function test_get_categories_flat_returns_empty_array_when_no_categories(): void {
		$this->setUpCategoriesWpdb( [ null ] );
		$result = DB::get_categories_flat( 1 );
		$this->assertSame( [], $result );
	}

	/**
	 * @covers \Beruang\DB::get_categories_flat
	 */
	public function test_get_categories_flat_returns_empty_array_when_get_results_returns_empty(): void {
		$this->setUpCategoriesWpdb( [ [] ] );
		$result = DB::get_categories_flat( 1 );
		$this->assertSame( [], $result );
	}

	/**
	 * @covers \Beruang\DB::get_categories_flat
	 */
	public function test_get_categories_flat_returns_top_level_category_with_depth_zero(): void {
		// get_categories_flat fetches ALL categories in one query then builds depth in PHP.
		$this->setUpCategoriesWpdb(
			[
				[ [ 'id' => 1, 'name' => 'Food', 'parent_id' => 0, 'sort_order' => 0 ] ],
			]
		);
		$result = DB::get_categories_flat( 1 );
		$this->assertCount( 1, $result );
		$this->assertSame( 'Food', $result[0]['name'] );
		$this->assertSame( 0, $result[0]['depth'] );
	}

	/**
	 * @covers \Beruang\DB::get_categories_flat
	 */
	public function test_get_categories_flat_returns_child_category_with_incremented_depth(): void {
		// All categories are fetched in a single query; flatten_categories assigns depth.
		$this->setUpCategoriesWpdb(
			[
				[
					[ 'id' => 1, 'name' => 'Food', 'parent_id' => 0, 'sort_order' => 0 ],
					[ 'id' => 2, 'name' => 'Lunch', 'parent_id' => 1, 'sort_order' => 0 ],
				],
			]
		);
		$result = DB::get_categories_flat( 1 );
		$this->assertCount( 2, $result );
		$this->assertSame( 'Food', $result[0]['name'] );
		$this->assertSame( 0, $result[0]['depth'] );
		$this->assertSame( 'Lunch', $result[1]['name'] );
		$this->assertSame( 1, $result[1]['depth'] );
	}

	/**
	 * @covers \Beruang\DB::get_categories_flat
	 */
	public function test_get_categories_flat_multiple_top_level_categories(): void {
		$this->setUpCategoriesWpdb(
			[
				[
					[ 'id' => 1, 'name' => 'Food', 'parent_id' => 0, 'sort_order' => 0 ],
					[ 'id' => 2, 'name' => 'Transport', 'parent_id' => 0, 'sort_order' => 1 ],
				],
			]
		);
		$result = DB::get_categories_flat( 1 );
		$this->assertCount( 2, $result );
		$names = array_column( $result, 'name' );
		$this->assertContains( 'Food', $names );
		$this->assertContains( 'Transport', $names );
		foreach ( $result as $cat ) {
			$this->assertSame( 0, $cat['depth'] );
		}
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
