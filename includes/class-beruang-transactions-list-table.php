<?php
/**
 * WP_List_Table for admin transactions list.
 *
 * @package Beruang
 */

namespace Beruang;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- GET for sorting/filtering in admin list.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Transactions list table for admin page.
 */
class Transactions_List_Table extends \WP_List_Table {

	/**
	 * User ID filter. 0 = all users.
	 *
	 * @var int
	 */
	protected $user_filter = 0;

	/**
	 * Constructor.
	 *
	 * @param int $user_filter Optional user ID to filter by. 0 for all users.
	 */
	public function __construct( $user_filter = 0 ) {
		$this->user_filter = absint( $user_filter );
		$screen            = get_current_screen();
		parent::__construct(
			array(
				'singular' => 'transaction',
				'plural'   => 'transactions',
				'ajax'     => false,
				'screen'   => $screen ? $screen->id : 'beruang_page_beruang-transactions',
			)
		);
	}

	/**
	 * Always use list view to avoid layout issues with excerpt/grid modes.
	 *
	 * @return string[]
	 */
	protected function get_table_classes() {
		return array( 'widefat', 'fixed', 'striped', 'table-view-list', $this->_args['plural'] );
	}

	/**
	 * Override column info to ensure our columns are always used (avoids filter conflicts).
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

	/**
	 * Get table columns.
	 *
	 * @return array<string, string>
	 */
	public function get_columns() {
		return array(
			'description' => __( 'Description', 'beruang' ),
			'id'          => __( 'ID', 'beruang' ),
			'user_id'     => __( 'User ID', 'beruang' ),
			'date'        => __( 'Date', 'beruang' ),
			'time'        => __( 'Time', 'beruang' ),
			'category_id' => __( 'Category ID', 'beruang' ),
			'amount'      => __( 'Amount', 'beruang' ),
			'type'        => __( 'Type', 'beruang' ),
			'actions'     => __( 'Actions', 'beruang' ),
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array<string, array>
	 */
	protected function get_sortable_columns() {
		return array(
			'description' => array( 'description', false ),
			'id'          => array( 'id', false ),
			'user_id'     => array( 'user_id', false ),
			'date'        => array( 'date', true ),
			'category_id' => array( 'category_id', false ),
			'amount'      => array( 'amount', false ),
			'type'        => array( 'type', false ),
		);
	}

	/**
	 * Prepare items for display.
	 */
	public function prepare_items() {
		global $wpdb;

		$table        = DB::table_transaction();
		$where        = '1=1';
		$values       = array();
		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		if ( $this->user_filter > 0 ) {
			$where   .= ' AND user_id = %d';
			$values[] = $this->user_filter;
		}
		if ( ! empty( $_GET['category_id'] ) ) {
			$cat_id   = absint( $_GET['category_id'] );
			$where   .= ' AND category_id = %d';
			$values[] = $cat_id;
		}
		if ( ! empty( $_GET['type'] ) && in_array( sanitize_key( wp_unslash( $_GET['type'] ) ), array( 'expense', 'income' ), true ) ) {
			$where   .= ' AND type = %s';
			$values[] = sanitize_key( wp_unslash( $_GET['type'] ) );
		}

		$orderby = 'date';
		$order   = 'DESC';
		if ( ! empty( $_GET['orderby'] ) ) {
			$allowed = array( 'id', 'user_id', 'date', 'time', 'description', 'category_id', 'amount', 'type' );
			$col     = sanitize_key( wp_unslash( $_GET['orderby'] ) );
			if ( in_array( $col, $allowed, true ) ) {
				$orderby = $col;
			}
			if ( 'description' === $col ) {
				$orderby = 'description';
			}
		}
		if ( ! empty( $_GET['order'] ) && 'ASC' === strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) ) {
			$order = 'ASC';
		}

		if ( 'date' === $orderby ) {
			$order_sql = sanitize_sql_orderby( "date $order, time $order, id DESC" );
			$order_sql = $order_sql ? $order_sql : 'date DESC, time DESC, id DESC';
		} else {
			$order_sql = sanitize_sql_orderby( $orderby . ' ' . $order . ', id DESC' );
			$order_sql = $order_sql ? $order_sql : 'date DESC, time DESC, id DESC';
		}

		$values_limit = array_merge( $values, array( $per_page, $offset ) );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Dynamic table/where for admin list.
		$total = (int) $wpdb->get_var(
			$values
				? $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE $where", $values )
				: "SELECT COUNT(*) FROM $table WHERE $where"
		);
		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE $where ORDER BY $order_sql LIMIT %d OFFSET %d",
				$values_limit
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		$this->items = is_array( $items ) ? $items : array();
		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total / $per_page ),
			)
		);
	}

	/**
	 * Render description column.
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	protected function column_description( $item ) {
		return ! empty( $item['description'] ) ? esc_html( $item['description'] ) : '—';
	}

	/**
	 * Render actions column (Edit link).
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	protected function column_actions( $item ) {
		$edit_url = add_query_arg(
			array(
				'page'    => ADMIN_SLUG . '-transactions',
				'edit'    => $item['id'],
				'user_id' => $this->user_filter ? $this->user_filter : null,
			),
			admin_url( 'admin.php' )
		);
		return '<a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'beruang' ) . '</a>';
	}

	/**
	 * Default column output.
	 *
	 * @param array  $item        Row data.
	 * @param string $column_name Column key.
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		$currency = get_option( 'beruang_currency', 'IDR' );
		$dec      = get_option( 'beruang_decimal_sep', ',' );
		$thou     = get_option( 'beruang_thousands_sep', '.' );

		switch ( $column_name ) {
			case 'id':
				return (string) $item['id'];
			case 'user_id':
				return (string) $item['user_id'];
			case 'date':
				return esc_html( $item['date'] );
			case 'time':
				return esc_html( $item['time'] ?? '—' );
			case 'category_id':
				return (string) $item['category_id'];
			case 'amount':
				$amount = number_format( (float) $item['amount'], (int) get_option( 'beruang_decimal_places', 2 ), $dec, $thou );
				return esc_html( $amount . ' ' . $currency );
			case 'type':
				return esc_html( $item['type'] );
			default:
				return '';
		}
	}

	/**
	 * Message when no items found.
	 */
	public function no_items() {
		esc_html_e( 'No transactions.', 'beruang' );
	}

	/**
	 * Displays the filter UI in the tablenav (user, category, type dropdowns + Filter button).
	 *
	 * @param string $which The location: 'top' or 'bottom'.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		$user_ids      = DB::get_transaction_user_ids();
		$selected_user = $this->user_filter;
		$selected_cat  = isset( $_GET['category_id'] ) ? absint( $_GET['category_id'] ) : 0;
		$selected_type = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
		if ( $selected_type && ! in_array( $selected_type, array( 'expense', 'income' ), true ) ) {
			$selected_type = '';
		}
		?>
		<div class="alignleft actions">
			<label for="filter-by-user" class="screen-reader-text"><?php esc_html_e( 'Filter by user', 'beruang' ); ?></label>
			<select name="user_id" id="filter-by-user">
				<option value="0" <?php selected( $selected_user, 0 ); ?>><?php esc_html_e( 'All users', 'beruang' ); ?></option>
				<?php
				foreach ( $user_ids as $uid ) {
					$user  = get_userdata( $uid );
					$label = $user ? $user->display_name . ' (#' . $uid . ')' : '#' . $uid;
					printf( '<option value="%d" %s>%s</option>', (int) $uid, selected( $selected_user, $uid, false ), esc_html( $label ) );
				}
				?>
			</select>
			<?php
			if ( $selected_user > 0 ) {
				$categories = DB::get_categories_flat( $selected_user, true );
				?>
				<label for="filter-by-category" class="screen-reader-text"><?php esc_html_e( 'Filter by category', 'beruang' ); ?></label>
				<select name="category_id" id="filter-by-category">
					<option value="0" <?php selected( $selected_cat, 0 ); ?>><?php esc_html_e( 'All categories', 'beruang' ); ?></option>
					<?php
					foreach ( $categories as $c ) {
						$indent = str_repeat( '— ', (int) ( $c['depth'] ?? 0 ) );
						printf( '<option value="%d" %s>%s</option>', (int) $c['id'], selected( $selected_cat, (int) $c['id'], false ), esc_html( $indent . $c['name'] ) );
					}
					?>
				</select>
				<?php
			}
			?>
			<label for="filter-by-type" class="screen-reader-text"><?php esc_html_e( 'Filter by type', 'beruang' ); ?></label>
			<select name="type" id="filter-by-type">
				<option value="" <?php selected( $selected_type, '' ); ?>><?php esc_html_e( 'All types', 'beruang' ); ?></option>
				<option value="expense" <?php selected( $selected_type, 'expense' ); ?>><?php esc_html_e( 'Expense', 'beruang' ); ?></option>
				<option value="income" <?php selected( $selected_type, 'income' ); ?>><?php esc_html_e( 'Income', 'beruang' ); ?></option>
			</select>
			<?php submit_button( __( 'Filter', 'beruang' ), '', 'filter_action', false, array( 'id' => 'beruang-transactions-query-submit' ) ); ?>
		</div>
		<?php
	}
}
