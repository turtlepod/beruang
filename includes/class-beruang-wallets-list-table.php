<?php
/**
 * WP_List_Table for admin wallets list.
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
 * Wallets list table for admin page.
 */
class Wallets_List_Table extends \WP_List_Table {

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
				'singular' => 'wallet',
				'plural'   => 'wallets',
				'ajax'     => false,
				'screen'   => $screen ? $screen->id : 'beruang_page_beruang-wallets',
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
			'id'      => __( 'ID', 'beruang' ),
			'user_id' => __( 'User ID', 'beruang' ),
			'name'    => __( 'Name', 'beruang' ),
			'actions' => __( 'Actions', 'beruang' ),
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array<string, array>
	 */
	protected function get_sortable_columns() {
		return array(
			'id'      => array( 'id', false ),
			'user_id' => array( 'user_id', false ),
			'name'    => array( 'name', false ),
		);
	}

	/**
	 * Prepare items for display.
	 */
	public function prepare_items() {
		global $wpdb;

		$table        = DB::table_wallet();
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
			$allowed = array( 'id', 'user_id', 'name' );
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
	 * Render actions column (Edit, Delete links).
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	protected function column_actions( $item ) {
		$user_filter = $this->user_filter ? $this->user_filter : (int) $item['user_id'];
		$edit_url    = add_query_arg(
			array(
				'page'    => ADMIN_SLUG . '-wallets',
				'user_id' => $user_filter,
				'edit'    => $item['id'],
			),
			admin_url( 'admin.php' )
		);
		$del_url     = wp_nonce_url(
			add_query_arg(
				array(
					'page'    => ADMIN_SLUG . '-wallets',
					'user_id' => $user_filter,
					'delete'  => $item['id'],
				),
				admin_url( 'admin.php' )
			),
			'beruang_delete_wallet_' . $item['id']
		);
		return '<a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'beruang' ) . '</a> | <a href="' . esc_url( $del_url ) . '" class="submitdelete">' . esc_html__( 'Delete', 'beruang' ) . '</a>';
	}

	/**
	 * Default column output.
	 *
	 * @param array  $item        Row data.
	 * @param string $column_name Column key.
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
				return (string) $item['id'];
			case 'user_id':
				return (string) $item['user_id'];
			case 'name':
				return ! empty( $item['name'] ) ? esc_html( $item['name'] ) : '—';
			default:
				return '';
		}
	}

	/**
	 * Message when no items found.
	 */
	public function no_items() {
		esc_html_e( 'No wallets.', 'beruang' );
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
		$user_ids      = DB::get_wallet_user_ids();
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
			<?php submit_button( __( 'Filter', 'beruang' ), '', 'filter_action', false, array( 'id' => 'beruang-wallets-query-submit' ) ); ?>
		</div>
		<?php
	}
}
