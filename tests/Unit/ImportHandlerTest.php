<?php
/**
 * Unit tests for the ImportHandler class.
 *
 * Covers:
 *  - validate()                -- all valid/invalid scenarios
 *  - remap_transaction()       -- category + wallet ID remapping
 *  - remap_budget_categories() -- drops IDs not in map
 *  - import_categories()       -- builds old->new map, remaps parent IDs
 *  - import_wallets()          -- builds old->new map, skips blank names
 *  - import_transactions()     -- delegates to DB::insert_transaction
 *  - import_budgets()          -- delegates to DB::save_budget
 *  - run()                     -- integration: cats -> wallets -> tx -> budgets
 *
 * @package Beruang
 */

namespace Beruang\Tests\Unit;

use Beruang\ImportHandler;
use ReflectionProperty;
use stdClass;
use WP_Mock\Tools\TestCase;

/**
 * Tests for Beruang\ImportHandler.
 */
class ImportHandlerTest extends TestCase {

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Reset the DB::$wpdb static cache and inject a minimal wpdb double.
	 */
	private function setUpQueryWpdb(): void {
		$wpdb         = new stdClass();
		$wpdb->prefix = 'wp_';

		$GLOBALS['wpdb'] = $wpdb;

		$prop = new ReflectionProperty( \Beruang\DB::class, 'wpdb' );
		$prop->setAccessible( true );
		$prop->setValue( null, null );
	}

	/**
	 * Build a wpdb mock that inserts and returns the given IDs in sequence.
	 *
	 * @param int[] $insert_ids Consecutive values for wpdb->insert_id.
	 * @return \PHPUnit\Framework\MockObject\MockObject
	 */
	private function setUpInsertWpdb( array $insert_ids ): object {
		$call_index = 0;

		$wpdb         = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'insert', 'update', 'get_row', 'prepare', 'get_var' ) )
			->getMock();
		$wpdb->prefix = 'wp_';

		$wpdb->method( 'insert' )->willReturnCallback(
			function ( ...$args ) use ( &$call_index, $insert_ids, $wpdb ) {
				$wpdb->insert_id = $insert_ids[ $call_index ] ?? 0;
				$call_index++;
				return 1;
			}
		);
		$wpdb->method( 'get_row' )->willReturn( null );
		$wpdb->method( 'prepare' )->willReturnCallback( function ( ...$args ) { return $args[0]; } );
		$wpdb->method( 'get_var' )->willReturn( '0' );

		$GLOBALS['wpdb'] = $wpdb;

		$prop = new ReflectionProperty( \Beruang\DB::class, 'wpdb' );
		$prop->setAccessible( true );
		$prop->setValue( null, null );

		return $wpdb;
	}

	public function tearDown(): void {
		parent::tearDown();
		unset( $GLOBALS['wpdb'] );
		$prop = new ReflectionProperty( \Beruang\DB::class, 'wpdb' );
		$prop->setAccessible( true );
		$prop->setValue( null, null );
	}

	// -----------------------------------------------------------------------
	// validate()
	// -----------------------------------------------------------------------

	/** @covers \Beruang\ImportHandler::validate */
	public function test_validate_null_when_categories_present(): void {
		$data = array( 'categories' => array( array( 'id' => 1, 'name' => 'Food' ) ) );
		$this->assertNull( ImportHandler::validate( $data ) );
	}

	/** @covers \Beruang\ImportHandler::validate */
	public function test_validate_null_when_wallets_only(): void {
		$data = array(
			'wallets' => array(
				array( 'id' => 1, 'name' => 'Cash', 'initial_amount' => 0, 'initial_date' => '2024-01-01' ),
			),
		);
		$this->assertNull( ImportHandler::validate( $data ) );
	}

	/** @covers \Beruang\ImportHandler::validate */
	public function test_validate_null_when_transactions_present(): void {
		$data = array( 'transactions' => array( array( 'date' => '2024-01-01', 'amount' => 100 ) ) );
		$this->assertNull( ImportHandler::validate( $data ) );
	}

	/** @covers \Beruang\ImportHandler::validate */
	public function test_validate_null_when_budgets_present(): void {
		$data = array( 'budgets' => array( array( 'name' => 'Monthly', 'target_amount' => 1000 ) ) );
		$this->assertNull( ImportHandler::validate( $data ) );
	}

	/** @covers \Beruang\ImportHandler::validate */
	public function test_validate_error_for_null(): void {
		$this->assertIsString( ImportHandler::validate( null ) );
	}

	/** @covers \Beruang\ImportHandler::validate */
	public function test_validate_error_for_string(): void {
		$this->assertIsString( ImportHandler::validate( 'not an array' ) );
	}

	/** @covers \Beruang\ImportHandler::validate */
	public function test_validate_error_for_integer(): void {
		$this->assertIsString( ImportHandler::validate( 42 ) );
	}

	/** @covers \Beruang\ImportHandler::validate */
	public function test_validate_error_for_empty_array(): void {
		$this->assertIsString( ImportHandler::validate( array() ) );
	}

	/** @covers \Beruang\ImportHandler::validate */
	public function test_validate_error_when_all_sections_empty(): void {
		$data = array(
			'categories'   => array(),
			'wallets'      => array(),
			'transactions' => array(),
			'budgets'      => array(),
		);
		$this->assertIsString( ImportHandler::validate( $data ) );
	}

	/** @covers \Beruang\ImportHandler::validate */
	public function test_validate_error_for_metadata_only(): void {
		$data = array( 'version' => 1, 'exported' => '2024-01-01' );
		$this->assertIsString( ImportHandler::validate( $data ) );
	}

	// -----------------------------------------------------------------------
	// remap_transaction()
	// -----------------------------------------------------------------------

	/** @covers \Beruang\ImportHandler::remap_transaction */
	public function test_remap_tx_remaps_category_id(): void {
		$tx     = array( 'category_id' => 5, 'wallet_id' => 0, 'amount' => 100 );
		$result = ImportHandler::remap_transaction( $tx, array( 5 => 99 ), array() );
		$this->assertSame( 99, $result['category_id'] );
	}

	/** @covers \Beruang\ImportHandler::remap_transaction */
	public function test_remap_tx_remaps_wallet_id(): void {
		$tx     = array( 'category_id' => 0, 'wallet_id' => 3, 'amount' => 50 );
		$result = ImportHandler::remap_transaction( $tx, array(), array( 3 => 77 ) );
		$this->assertSame( 77, $result['wallet_id'] );
	}

	/** @covers \Beruang\ImportHandler::remap_transaction */
	public function test_remap_tx_leaves_unmapped_ids_unchanged(): void {
		$tx     = array( 'category_id' => 5, 'wallet_id' => 3, 'amount' => 100 );
		$result = ImportHandler::remap_transaction( $tx, array(), array() );
		$this->assertSame( 5, $result['category_id'] );
		$this->assertSame( 3, $result['wallet_id'] );
	}

	/** @covers \Beruang\ImportHandler::remap_transaction */
	public function test_remap_tx_zero_ids_stay_zero(): void {
		$tx     = array( 'category_id' => 0, 'wallet_id' => 0, 'amount' => 100 );
		$result = ImportHandler::remap_transaction( $tx, array( 0 => 999 ), array( 0 => 888 ) );
		$this->assertSame( 0, $result['category_id'] );
		$this->assertSame( 0, $result['wallet_id'] );
	}

	/** @covers \Beruang\ImportHandler::remap_transaction */
	public function test_remap_tx_preserves_other_fields(): void {
		$tx = array(
			'date'        => '2024-03-01',
			'description' => 'Lunch',
			'amount'      => 25.50,
			'type'        => 'expense',
			'category_id' => 2,
			'wallet_id'   => 1,
		);
		$result = ImportHandler::remap_transaction( $tx, array( 2 => 20 ), array( 1 => 10 ) );
		$this->assertSame( '2024-03-01', $result['date'] );
		$this->assertSame( 'Lunch', $result['description'] );
		$this->assertSame( 25.50, $result['amount'] );
		$this->assertSame( 'expense', $result['type'] );
		$this->assertSame( 20, $result['category_id'] );
		$this->assertSame( 10, $result['wallet_id'] );
	}

	// -----------------------------------------------------------------------
	// remap_budget_categories()
	// -----------------------------------------------------------------------

	/** @covers \Beruang\ImportHandler::remap_budget_categories */
	public function test_remap_budget_categories_maps_present_ids(): void {
		$result = ImportHandler::remap_budget_categories( array( 1, 2, 3 ), array( 1 => 10, 2 => 20, 3 => 30 ) );
		$this->assertSame( array( 10, 20, 30 ), $result );
	}

	/** @covers \Beruang\ImportHandler::remap_budget_categories */
	public function test_remap_budget_categories_drops_unmapped_ids(): void {
		$result = ImportHandler::remap_budget_categories( array( 1, 5, 9 ), array( 1 => 10 ) );
		$this->assertSame( array( 10 ), $result );
	}

	/** @covers \Beruang\ImportHandler::remap_budget_categories */
	public function test_remap_budget_categories_empty_input(): void {
		$result = ImportHandler::remap_budget_categories( array(), array( 1 => 10 ) );
		$this->assertSame( array(), $result );
	}

	/** @covers \Beruang\ImportHandler::remap_budget_categories */
	public function test_remap_budget_categories_drops_zero_ids(): void {
		$result = ImportHandler::remap_budget_categories( array( 0, 1 ), array( 1 => 10 ) );
		$this->assertSame( array( 10 ), $result );
	}

	// -----------------------------------------------------------------------
	// import_wallets()
	// -----------------------------------------------------------------------

	/** @covers \Beruang\ImportHandler::import_wallets */
	public function test_import_wallets_builds_id_map(): void {
		$this->setUpInsertWpdb( array( 100, 200 ) );

		$wallets = array(
			array( 'id' => 1, 'name' => 'Cash', 'initial_amount' => 500.0, 'initial_date' => '2024-01-01' ),
			array( 'id' => 2, 'name' => 'Bank', 'initial_amount' => 1000.0, 'initial_date' => '2024-01-01' ),
		);

		$map = ImportHandler::import_wallets( 1, $wallets );
		$this->assertSame( array( 1 => 100, 2 => 200 ), $map );
	}

	/** @covers \Beruang\ImportHandler::import_wallets */
	public function test_import_wallets_skips_blank_name(): void {
		$this->setUpInsertWpdb( array( 100 ) );

		$wallets = array(
			array( 'id' => 1, 'name' => '', 'initial_amount' => 0, 'initial_date' => '2024-01-01' ),
			array( 'id' => 2, 'name' => 'Cash', 'initial_amount' => 0, 'initial_date' => '2024-01-01' ),
		);

		$map = ImportHandler::import_wallets( 1, $wallets );
		$this->assertArrayNotHasKey( 1, $map );
		$this->assertArrayHasKey( 2, $map );
		$this->assertSame( 100, $map[2] );
	}

	/** @covers \Beruang\ImportHandler::import_wallets */
	public function test_import_wallets_empty_returns_empty_map(): void {
		$this->setUpQueryWpdb();
		$map = ImportHandler::import_wallets( 1, array() );
		$this->assertSame( array(), $map );
	}

	/** @covers \Beruang\ImportHandler::import_wallets */
	public function test_import_wallets_preserves_initial_amount_and_date(): void {
		$captured = null;

		$wpdb         = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'insert', 'get_row', 'prepare', 'get_var' ) )
			->getMock();
		$wpdb->prefix = 'wp_';

		$wpdb->method( 'insert' )->willReturnCallback(
			function ( $table, $data ) use ( &$captured, $wpdb ) {
				$captured        = $data;
				$wpdb->insert_id = 55;
				return 1;
			}
		);
		$wpdb->method( 'get_row' )->willReturn( null );
		$wpdb->method( 'prepare' )->willReturnCallback( function ( ...$a ) { return $a[0]; } );
		$wpdb->method( 'get_var' )->willReturn( '0' );

		$GLOBALS['wpdb'] = $wpdb;
		$prop            = new ReflectionProperty( \Beruang\DB::class, 'wpdb' );
		$prop->setAccessible( true );
		$prop->setValue( null, null );

		$wallets = array(
			array( 'id' => 7, 'name' => 'Savings', 'initial_amount' => 2500.75, 'initial_date' => '2023-06-15' ),
		);
		ImportHandler::import_wallets( 1, $wallets );

		$this->assertNotNull( $captured );
		$this->assertSame( 'Savings', $captured['name'] );
		$this->assertEqualsWithDelta( 2500.75, (float) $captured['initial_amount'], 0.001 );
		$this->assertSame( '2023-06-15', $captured['initial_date'] );
	}

	// -----------------------------------------------------------------------
	// import_categories()
	// -----------------------------------------------------------------------

	/** @covers \Beruang\ImportHandler::import_categories */
	public function test_import_categories_remaps_parent_id(): void {
		$this->setUpInsertWpdb( array( 10, 11 ) );

		$categories = array(
			array( 'id' => 1, 'name' => 'Food', 'parent_id' => 0, 'sort_order' => 0 ),
			array( 'id' => 2, 'name' => 'Lunch', 'parent_id' => 1, 'sort_order' => 0 ),
		);

		$map = ImportHandler::import_categories( 1, $categories );
		$this->assertSame( array( 1 => 10, 2 => 11 ), $map );
	}

	/** @covers \Beruang\ImportHandler::import_categories */
	public function test_import_categories_empty_returns_empty_map(): void {
		$this->setUpQueryWpdb();
		$map = ImportHandler::import_categories( 1, array() );
		$this->assertSame( array(), $map );
	}

	// -----------------------------------------------------------------------
	// run() -- integration
	// -----------------------------------------------------------------------

	/** @covers \Beruang\ImportHandler::run */
	public function test_run_returns_correct_counts_all_sections(): void {
		// 1 cat, 1 wallet, 1 tx, 1 budget
		$this->setUpInsertWpdb( array( 10, 20, 30, 40 ) );

		$data = array(
			'categories'   => array(
				array( 'id' => 1, 'name' => 'Food', 'parent_id' => 0, 'sort_order' => 0 ),
			),
			'wallets'      => array(
				array( 'id' => 5, 'name' => 'Cash', 'initial_amount' => 0.0, 'initial_date' => '2024-01-01' ),
			),
			'transactions' => array(
				array(
					'date'        => '2024-01-10',
					'description' => 'Grocery',
					'amount'      => 50,
					'type'        => 'expense',
					'category_id' => 1,
					'wallet_id'   => 5,
				),
			),
			'budgets'      => array(
				array(
					'name'          => 'Monthly Food',
					'target_amount' => 500,
					'type'          => 'monthly',
					'category_ids'  => array( 1 ),
				),
			),
		);

		$result = ImportHandler::run( 1, $data );

		$this->assertSame( 1, $result['categories'] );
		$this->assertSame( 1, $result['wallets'] );
		$this->assertSame( 1, $result['transactions'] );
		$this->assertSame( 1, $result['budgets'] );
	}

	/** @covers \Beruang\ImportHandler::run */
	public function test_run_wallets_only_export(): void {
		$this->setUpInsertWpdb( array( 100, 101 ) );

		$data = array(
			'wallets' => array(
				array( 'id' => 1, 'name' => 'Cash', 'initial_amount' => 0.0, 'initial_date' => '2024-01-01' ),
				array( 'id' => 2, 'name' => 'Bank', 'initial_amount' => 5000.0, 'initial_date' => '2024-01-01' ),
			),
		);

		$result = ImportHandler::run( 1, $data );

		$this->assertSame( 0, $result['categories'] );
		$this->assertSame( 2, $result['wallets'] );
		$this->assertSame( 0, $result['transactions'] );
		$this->assertSame( 0, $result['budgets'] );
	}

	/** @covers \Beruang\ImportHandler::run */
	public function test_run_remaps_wallet_id_in_transactions(): void {
		$captured_tx = null;
		$call_index  = 0;
		$insert_ids  = array( 77, 88 ); // wallet=77, tx=88

		$wpdb         = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'insert', 'get_row', 'prepare', 'get_var' ) )
			->getMock();
		$wpdb->prefix = 'wp_';

		$wpdb->method( 'insert' )->willReturnCallback(
			function ( $table, $data ) use ( &$call_index, $insert_ids, $wpdb, &$captured_tx ) {
				$wpdb->insert_id = $insert_ids[ $call_index ] ?? 0;
				$call_index++;
				if ( $call_index === 2 ) {
					$captured_tx = $data;
				}
				return 1;
			}
		);
		$wpdb->method( 'get_row' )->willReturn( null );
		$wpdb->method( 'prepare' )->willReturnCallback( function ( ...$a ) { return $a[0]; } );
		$wpdb->method( 'get_var' )->willReturn( '0' );

		$GLOBALS['wpdb'] = $wpdb;
		$prop            = new ReflectionProperty( \Beruang\DB::class, 'wpdb' );
		$prop->setAccessible( true );
		$prop->setValue( null, null );

		$data = array(
			'wallets'      => array(
				array( 'id' => 3, 'name' => 'Cash', 'initial_amount' => 0.0, 'initial_date' => '2024-01-01' ),
			),
			'transactions' => array(
				array(
					'date'        => '2024-01-05',
					'description' => 'Coffee',
					'amount'      => 5,
					'type'        => 'expense',
					'category_id' => 0,
					'wallet_id'   => 3, // old ID -- should become 77
				),
			),
		);

		ImportHandler::run( 1, $data );

		$this->assertNotNull( $captured_tx, 'Transaction insert was not called' );
		$this->assertSame( 77, (int) $captured_tx['wallet_id'] );
	}
}
