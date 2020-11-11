<?php
/**
 * Orders Report Table
 *
 * @since 1.16.0
 *
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Order;

use TIVWP_140\Env;
use WOOMC\Log;
use WOOMC\Rate;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class ReportTable
 *
 * @package WOOMC\Order
 */
class ReportTable extends \WP_List_Table {

	/**
	 * Nonce name.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	const NONCE_NAME = 'woomc_nonce_name_orders';

	/**
	 * Nonce action.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	const NONCE_ACTION = 'woomc_nonce_action_orders';

	/**
	 * The Order post type.
	 *
	 * @var string
	 */
	const POST_TYPE = 'shop_order';

	/**
	 * Prefix for the converted field names.
	 *
	 * @var string
	 */
	const PREFIX_CONVERTED = 'converted__';

	/**
	 * Marker for the converted fields headings.
	 */
	const MARKER = '*';

	/**
	 * Rows per page - min.
	 *
	 * @var int
	 */
	const PER_PAGE_MIN = 1;

	/**
	 * Rows per page - max.
	 *
	 * @var int
	 */
	const PER_PAGE_MAX = 200;

	/**
	 * Rows per page - default.
	 *
	 * @var int
	 */
	const PER_PAGE_DEFAULT = 20;

	/**
	 * Max items.
	 *
	 * @var int
	 */
	protected $max_items = 0;

	/**
	 * DI.
	 *
	 * @var Rate\Storage
	 */
	protected $rate_storage;

	/**
	 * Store currency - now. Used as default if not in the order meta.
	 *
	 * @var string
	 */
	protected $store_currency_now;

	/**
	 * Rows per page.
	 *
	 * @var int
	 */
	protected $per_page = self::PER_PAGE_DEFAULT;

	/**
	 * Totals.
	 *
	 * @var float[]
	 */
	protected $totals = array();

	/**
	 * To access the array in {@see get_columns}
	 *
	 * @var string[]
	 */
	protected $columns = array();

	/**
	 * Constructor.
	 *
	 * @param Rate\Storage $rate_storage Rate Storage instance.
	 */
	public function __construct( $rate_storage ) {

		$this->rate_storage       = $rate_storage;
		$this->store_currency_now = \get_option( 'woocommerce_currency' );

		$requested_per_page = (int) Env::http_get( 'per_page' );
		if ( $requested_per_page ) {
			$this->per_page = min( max( self::PER_PAGE_MIN, $requested_per_page ), self::PER_PAGE_MAX );
		}

		parent::__construct(
			array(
				'singular' => 'order',
				'plural'   => 'orders',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Output the report.
	 */
	public function output_report() {

		$this->prepare_items();

		?>
		<div id="poststuff" class="woocommerce-reports-wide">
			<?php $this->display(); ?>
		</div>

		<h2><?php \esc_html_e( 'This page totals:', 'woocommerce-multicurrency' ); ?></h2>
		<table class="widefat striped fixed" style="max-width: 30em">
			<?php foreach ( $this->totals as $column_name => $total ) { ?>
				<tr>
					<th class="alignleft"><?php echo \esc_html( $this->columns[ $column_name ] ); ?></th>
					<td class="alignright"><?php echo \wp_kses_post( wc_price( $total ) ); ?></td>
				</tr>
			<?php } ?>
		</table>
		<?php
	}

	/**
	 * Get column value.
	 *
	 * @param array  $item        Item.
	 * @param string $column_name Column name.
	 */
	public function column_default( $item, $column_name ) {

		$values = $this->calculate( (int) $item['ID'] );

		if ( self::PREFIX_CONVERTED === substr( $column_name, 0, strlen( self::PREFIX_CONVERTED ) ) ) {
			// Calculated value.
			$actual_column = substr( $column_name, strlen( self::PREFIX_CONVERTED ) );
			$value         = $values[ $actual_column ] * $values['rate'];

			// Totals on this page.
			if ( isset( $this->totals[ $column_name ] ) ) {
				$this->totals[ $column_name ] += $value;
			} else {
				$this->totals[ $column_name ] = $value;
			}
		} else {
			// Regular value.
			$value = $values[ $column_name ];
		}

		echo \esc_html( $value );
	}

	/**
	 * Calculate the table row values.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array
	 */
	protected function calculate( $order_id ) {

		// Cache: this method is called for each column in the row.
		static $id;
		static $values;
		if ( $id !== $order_id ) {

			$id = $order_id;

			$order = \wc_get_order( $order_id );
			if ( ! $order ) {
				Log::error( new \Exception( 'Cannot find order with ID=', $order_id ) );

				$values = array();
			} else {

				$store_currency = $order->get_meta( Meta::PREFIX . 'store_currency', true, 'edit' );
				if ( ! $store_currency ) {
					$store_currency = $this->store_currency_now;
				}

				$values = array(
					'ID'                 => $order->get_id(),
					'date'               => \wc_format_datetime( $order->get_date_created() ),
					'status'             => \wc_get_order_status_name( $order->get_status() ),
					'order_currency'     => $order->get_currency(),
					'store_currency'     => $store_currency,
					'order_total'        => $order->get_total( 'edit' ),
					'order_tax'          => $order->get_total_tax( 'edit' ),
					'order_shipping'     => $order->get_shipping_total( 'edit' ),
					'order_shipping_tax' => $order->get_shipping_tax( 'edit' ),
				);

				$values['rate'] = (float) ( $order->get_meta( Meta::PREFIX . 'rate' ) );
				if ( ! $values['rate'] ) {
					$values['rate'] = $this->rate_storage->get_rate( $values['store_currency'], $values['order_currency'] );
				}
			}
		}

		return $values;

	}


	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns() {

		$this->columns = array(
			'ID'                                          => __( 'Order', 'woocommerce' ),
			'date'                                        => __( 'Date', 'woocommerce' ),
			'status'                                      => __( 'Status', 'woocommerce' ),
			'order_currency'                              => __( 'Currency', 'woocommerce' ),
			'order_total'                                 => __( 'Order Total', 'woocommerce' ),
			'order_tax'                                   => __( 'Tax amount', 'woocommerce' ),
			'order_shipping'                              => __( 'Shipping', 'woocommerce' ),
			'order_shipping_tax'                          => __( 'Shipping tax amount', 'woocommerce' ),
			'store_currency'                              => __( 'Store Currency', 'woocommerce-multicurrency' ),
			'rate'                                        => __( 'Rate', 'woocommerce-multicurrency' ),
			self::PREFIX_CONVERTED . 'order_total'        => __( 'Order Total', 'woocommerce' ) . self::MARKER,
			self::PREFIX_CONVERTED . 'order_tax'          => __( 'Tax amount', 'woocommerce' ) . self::MARKER,
			self::PREFIX_CONVERTED . 'order_shipping'     => __( 'Shipping', 'woocommerce' ) . self::MARKER,
			self::PREFIX_CONVERTED . 'order_shipping_tax' => __( 'Shipping tax amount', 'woocommerce' ) . self::MARKER,
		);

		return $this->columns;
	}

	/**
	 * Prepare customer list items.
	 */
	public function prepare_items() {

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
		$current_page          = \absint( $this->get_pagenum() );

		$this->get_items( $current_page, $this->per_page );

		/**
		 * Pagination.
		 */
		$this->set_pagination_args(
			array(
				'total_items' => $this->max_items,
				'per_page'    => $this->per_page,
				'total_pages' => ceil( $this->max_items / $this->per_page ),
			)
		);
	}

	/**
	 * Retrieve the items from DB.
	 *
	 * @param int $current_page Current page.
	 * @param int $per_page     Results per page.
	 */
	public function get_items( $current_page, $per_page ) {

		/**
		 * Global.
		 *
		 * @var \wpdb $wpdb
		 */
		global $wpdb;

		if ( isset( $_GET['order_status'] ) || isset( $_GET['m'] ) ) {
			\check_admin_referer( self::NONCE_ACTION, self::NONCE_NAME );
		}

		$requested_status = isset( $_GET['order_status'] ) ? \sanitize_text_field( $_GET['order_status'] ) : '';

		$requested_month = isset( $_GET['m'] ) ? \sanitize_text_field( $_GET['m'] ) : '';

		$filter = '';
		if ( $requested_status ) {
			$filter .= $wpdb->prepare( 'AND post_status = %s', $requested_status );
		}
		if ( (int) $requested_month ) {
			$year  = \absint( substr( $requested_month, 0, 4 ) );
			$month = \absint( substr( $requested_month, 4, 2 ) );

			$filter .= $wpdb->prepare( ' AND MONTH( post_date ) = %d AND YEAR( post_date ) = %d', $month, $year );
		}

		$this->items = $wpdb->get_results(
			$wpdb->prepare( "SELECT SQL_CALC_FOUND_ROWS ID FROM {$wpdb->posts} WHERE post_type = %s", self::POST_TYPE ) .
			' ' . $filter . ' ' .
			$wpdb->prepare( 'ORDER BY ID DESC LIMIT %d, %d', ( $current_page - 1 ) * $per_page, $per_page ),
			ARRAY_A
		);

		$this->max_items = $wpdb->get_var( 'SELECT FOUND_ROWS();' );

	}

	/**
	 * Extra navigation controls.
	 *
	 * @param string $which Top or bottom.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' === $which ) {

			?>
			<form>
				<input
						type="hidden" name="page"
						value="<?php echo \esc_attr( ReportPage::MENU_SLUG_ORDERS_REPORT ); ?>"/>
				<div class="alignleft actions">
					<?php $this->months_dropdown( self::POST_TYPE ); ?>
				</div>
				<div class="alignleft actions">
					<label for="order_status">
						<?php \esc_html_e( 'Status:' ); ?>
					</label>
				</div>
				<div class="alignleft actions">
					<?php $this->status_dropdown(); ?>
				</div>
				<div class="alignleft actions">
					<label for="per_page">
						<?php \esc_html_e( 'Number of items per page:' ); ?>
					</label>
				</div>
				<div class="alignleft actions">
					<input
							type="number" step="1" min="<?php echo \esc_attr( self::PER_PAGE_MIN ); ?>"
							max="<?php echo \esc_attr( self::PER_PAGE_MAX ); ?>" id="per_page" name="per_page"
							value="<?php echo \esc_attr( $this->per_page ); ?>">
				</div>
				<div class="alignleft actions">
					<?php \wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>
					<?php \submit_button( \__( 'Apply Filters' ), 'large', false, false ); ?>
				</div>
			</form>
			<?php
		}
	}

	/**
	 * Displays the order status dropdown filter
	 */
	public function status_dropdown() {

		$statuses         = \wc_get_order_statuses();
		$requested_status = Env::http_get( 'order_status' );
		?>

		<select id="order_status" name="order_status" class="wc-enhanced-select">
			<option value="">
				<?php \esc_html_e( 'All', 'woocommerce' ); ?>
			</option>
			<?php
			foreach ( $statuses as $status => $status_name ) {
				echo '<option value="' . \esc_attr( $status ) . '" ' . \selected( $status, $requested_status, false ) . '>' . \esc_html( $status_name ) . '</option>';
			}
			?>
		</select>
		<?php
	}

}
