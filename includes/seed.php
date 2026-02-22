<?php
/**
 * Seed dummy data for Beruang: categories, budgets, budget-categories, and transactions.
 *
 * @package Beruang
 */

namespace Beruang;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate dummy categories, budgets, budget-category links, and transactions for a user.
 *
 * @param int $user_id   User ID to seed data for.
 * @param int $tx_count  Number of transactions to generate.
 * @return array{ categories: int, budgets: int, transactions: int } Counts of created items.
 */
function seed_dummy_data( $user_id, $tx_count = 1000 ) {
	$user_id = absint( $user_id );
	if ( ! $user_id || ! get_userdata( $user_id ) ) {
		return array(
			'categories'   => 0,
			'budgets'      => 0,
			'transactions' => 0,
		);
	}
	$descriptions = array(
		'Groceries at supermarket',
		'Coffee shop',
		'Restaurant dinner',
		'Fast food',
		'Online shopping',
		'Fuel station',
		'Uber ride',
		'Public transport',
		'Electricity bill',
		'Water bill',
		'Internet subscription',
		'Phone bill',
		'Netflix',
		'Spotify',
		'Movie tickets',
		'Gym membership',
		'Pharmacy',
		'Haircut',
		'Book purchase',
		'Salary',
		'Freelance payment',
		'Dividend',
		'Refund',
		'Cash withdrawal',
		'Transfer received',
		'Gift',
		'Petrol',
		'Parking fee',
		'Toll road',
		'Snacks',
		'Lunch',
		'Breakfast',
		'Household supplies',
		'Clothing',
		'Electronics',
	);
	$categories   = array( 'Food', 'Transport', 'Utilities', 'Entertainment', 'Shopping', 'Groceries', 'Health', 'Income', 'Personal', 'Other' );
	$cats_by_name = array();
	$flat         = DB::get_categories_flat( $user_id, false );
	foreach ( $flat as $c ) {
		$cats_by_name[ $c['name'] ] = (int) $c['id'];
	}
	$cat_ids = array();
	foreach ( $categories as $name ) {
		if ( isset( $cats_by_name[ $name ] ) ) {
			$cat_ids[] = $cats_by_name[ $name ];
		} else {
			$id = DB::save_category(
				$user_id,
				array(
					'name'       => $name,
					'parent_id'  => 0,
					'sort_order' => 0,
				),
				0
			);
			if ( $id ) {
				$cat_ids[]             = $id;
				$cats_by_name[ $name ] = $id;
			}
		}
	}
	if ( empty( $cat_ids ) ) {
		return array(
			'categories'   => 0,
			'budgets'      => 0,
			'transactions' => 0,
		);
	}
	$budget_data = array(
		array(
			'name'          => 'Food',
			'target_amount' => 2000000,
			'type'          => 'monthly',
			'cats'          => array( 'Food', 'Groceries' ),
		),
		array(
			'name'          => 'Transport',
			'target_amount' => 500000,
			'type'          => 'monthly',
			'cats'          => array( 'Transport' ),
		),
		array(
			'name'          => 'Utilities',
			'target_amount' => 800000,
			'type'          => 'monthly',
			'cats'          => array( 'Utilities' ),
		),
		array(
			'name'          => 'Entertainment',
			'target_amount' => 500000,
			'type'          => 'monthly',
			'cats'          => array( 'Entertainment' ),
		),
	);
	foreach ( $budget_data as $b ) {
		$cat_ids_for_budget = array();
		foreach ( $b['cats'] as $cname ) {
			if ( isset( $cats_by_name[ $cname ] ) ) {
				$cat_ids_for_budget[] = $cats_by_name[ $cname ];
			}
		}
		DB::save_budget(
			$user_id,
			array(
				'name'          => $b['name'],
				'target_amount' => $b['target_amount'],
				'type'          => $b['type'],
				'category_ids'  => $cat_ids_for_budget,
			),
			0
		);
	}
	$start_date = strtotime( '-12 months' );
	$end_date   = time();
	$inserted   = 0;
	for ( $i = 0; $i < $tx_count; $i++ ) {
		$date_ts   = $start_date + (int) ( ( $end_date - $start_date ) * ( wp_rand( 0, 10000 ) / 10000 ) );
		$date      = gmdate( 'Y-m-d', $date_ts );
		$time      = sprintf( '%02d:%02d:00', wp_rand( 6, 22 ), wp_rand( 0, 59 ) );
		$desc      = $descriptions[ array_rand( $descriptions ) ];
		$is_income = in_array( $desc, array( 'Salary', 'Freelance payment', 'Dividend', 'Refund', 'Transfer received', 'Gift' ), true ) || wp_rand( 1, 20 ) === 1;
		$amount    = $is_income ? wp_rand( 500000, 15000000 ) / 100 : wp_rand( 5000, 500000 ) / 100;
		$cat_id    = $cat_ids[ array_rand( $cat_ids ) ];
		$type      = $is_income ? 'income' : 'expense';
		$ok        = DB::insert_transaction(
			$user_id,
			array(
				'date'        => $date,
				'time'        => $time,
				'description' => $desc . ' #' . ( $i + 1 ),
				'category_id' => $cat_id,
				'amount'      => round( $amount, 2 ),
				'type'        => $type,
			)
		);
		if ( $ok ) {
			++$inserted;
		}
	}
	return array(
		'categories'   => count( $cat_ids ),
		'budgets'      => count( $budget_data ),
		'transactions' => $inserted,
	);
}
