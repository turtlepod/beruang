<?php
/**
 * WP_List_Table for admin budgets list.
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
 * Budgets list table for admin page.
 */
class Budgets_List_Table extends \WP_List_Table {

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
				'singular' => 'budget',
				'plural'   => 'budgets',
				'ajax'     => false,
				'screen'   => $screen ? $screen->id : 'beruang_page_beruang-budgets',
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
			'id'            => __( 'ID', 'beruang' ),
			'user_id'       => __( 'User ID', 'beruang' ),
			'name'          => __( 'Name', 'beruang' ),
			'target_amount' => __( 'Target', 'beruang' ),
			'type'          => __( 'Type', 'beruang' ),
			'category_ids'  => __( 'Category IDs', 'beruang' ),
			'actions'       => __( 'Actions', 'beruang' ),
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array<string, array>
	 */
	protected function get_sortable_columns() {
		return array(
			'id'            => array( 'id', false ),
			'user_id'       => array( 'user_id', false ),
			'name'          => array( 'name', false ),
			'target_amount' => array( 'target_amount', false ),
			'type'          => array( 'type', false ),
		);
	}

	/**
	 * Prepare items for display.
	 */
	public function prepare_items() {
		global $wpdb;

		$table        = DB::table_budget();
		$bc_table     = DB::table_budget_category();
		$where        = '1=1';
		$values       = array();
		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		if ( $this->user_filter > 0 ) {
			$where   .= ' AND user_id = %d';
			$values[] = $this->user_filter;
		}

		$orderby = 'name';
		$order   = 'ASC';
		if ( ! empty( $_GET['orderby'] ) ) {
			$allowed = array( 'id', 'user_id', 'name', 'target_amount', 'type' );
			$col     = sanitize_key( wp_unslash( $_GET['orderby'] ) );
			if ( in_array( $col, $allowed, true ) ) {
				$orderby = $col;
			}
		}
		if ( ! empty( $_GET['order'] ) && 'ASC' === strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) ) {
			$order = 'ASC';
		} else {
			$order = 'DESC';
		}

		$order_sql = sanitize_sql_orderby( $orderby . ' ' . $order . ', id ASC' );
		$order_sql = $order_sql ? $order_sql : 'name ASC, id ASC';

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

		// Fetch category_ids for each budget in this page.
		if ( ! empty( $items ) ) {
			$budget_ids   = array_map( 'absint', wp_list_pluck( $items, 'id' ) );
			$ids_imploded = implode( ',', $budget_ids );
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table from API, IDs are absint.
			$bc_rows = $wpdb->get_results(
				"SELECT budget_id, category_id FROM $bc_table WHERE budget_id IN ($ids_imploded)",
				ARRAY_A
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$by_budget = array();
			foreach ( $bc_rows as $row ) {
				$bid = (int) $row['budget_id'];
				if ( ! isset( $by_budget[ $bid ] ) ) {
					$by_budget[ $bid ] = array();
				}
				$by_budget[ $bid ][] = (int) $row['category_id'];
			}
			foreach ( $items as &$item ) {
				$item['category_ids'] = isset( $by_budget[ (int) $item['id'] ] ) ? $by_budget[ (int) $item['id'] ] : array();
			}
			unset( $item );
		}

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
	 * Render actions column (Edit link).
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	protected function column_actions( $item ) {
		$user_filter = $this->user_filter ? $this->user_filter : (int) $item['user_id'];
		$edit_url    = add_query_arg(
			array(
				'page'    => ADMIN_SLUG . '-budgets',
				'user_id' => $user_filter,
				'edit'    => $item['id'],
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
			case 'name':
				return ! empty( $item['name'] ) ? esc_html( $item['name'] ) : '—';
			case 'target_amount':
				$amount = number_format( (float) $item['target_amount'], (int) get_option( 'beruang_decimal_places', 2 ), $dec, $thou );
				return esc_html( $amount . ' ' . $currency );
			case 'type':
				return esc_html( $item['type'] );
			case 'category_ids':
				$ids = isset( $item['category_ids'] ) && is_array( $item['category_ids'] ) ? $item['category_ids'] : array();
				return esc_html( implode( ', ', $ids ) );
			default:
				return '';
		}
	}

	/**
	 * Message when no items found.
	 */
	public function no_items() {
		esc_html_e( 'No budgets.', 'beruang' );
	}

	/**
	 * Displays the filter UI in the tablenav (user dropdown + Filter button).
	 *
	 * @param string $which The location: 'top' or 'bottom'.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		$user_ids      = DB::get_budget_user_ids();
		$selected_user = $this->user_filter;
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
			<?php submit_button( __( 'Filter', 'beruang' ), '', 'filter_action', false, array( 'id' => 'beruang-budgets-query-submit' ) ); ?>
		</div>
		<?php
	}
}
