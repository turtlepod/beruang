<?php
/**
 * Shared list-table trait for Beruang list tables.
 *
 * Avoids code duplication across the four admin list-table classes.
 *
 * @package Beruang
 */

namespace BeruangBudget;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait providing shared WP_List_Table overrides.
 */
trait Beruang_List_Table_Trait {

	/**
	 * Get table CSS classes.
	 *
	 * @return string[]
	 */
	protected function get_table_classes() {
		return array( 'widefat', 'fixed', 'striped', 'table-view-list', $this->_args['plural'] );
	}

	/**
	 * Get column headers (columns, hidden, sortable, primary).
	 *
	 * @return array
	 */
	protected function get_column_info() {
		if ( isset( $this->_column_headers ) && is_array( $this->_column_headers ) ) {
			return $this->_column_headers;
		}
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$primary               = $this->get_default_primary_column_name();
		$this->_column_headers = array( $columns, $hidden, $sortable, $primary );
		return $this->_column_headers;
	}
}
