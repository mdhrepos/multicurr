<?php
/**
 * Reports page.
 *
 * @since 1.7.0
 * Copyright (c) 2018. TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Reports;

use TIVWP_140\Env;
use TIVWP_140\InterfaceHookable;
use WOOMC\App;

/**
 * Class Page
 *
 * @package WOOMC\Reports
 */
class Page implements InterfaceHookable {

	/**
	 * The selected currency.
	 *
	 * @var string
	 */
	protected $selected_currency = '';

	/**
	 * Page constructor.
	 *
	 * @param string $selected_currency The currency code.
	 */
	public function __construct( $selected_currency ) {
		$this->selected_currency = $selected_currency;
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		\add_action( 'wc_reports_tabs', array( $this, 'currency_selector' ), App::HOOK_PRIORITY_LATE );

		\add_filter(
			'woocommerce_reports_get_order_report_query',
			array( $this, 'query_filter' )
		);

		\add_filter(
			'woocommerce_customer_get_total_spent',
			array( $this, 'prevent_caching_customer_spent' )
		);

		\add_filter(
			'woocommerce_customer_get_total_spent_query',
			array( $this, 'filter_total_spent_query' )
		);
	}

	/**
	 * Add a tab with the currency selector dropdown.
	 *
	 * @internal
	 */
	public function currency_selector() {
		?>
		<div class="nav-tab" style="padding: 2px 5px;">
			<?php CurrencySelector::render( $this->selected_currency ); ?>
		</div>
		<?php
	}

	/**
	 * Restrict the reports query to the selected currency.
	 *
	 * @param array $query The SQL query parts.
	 *
	 * @return array
	 *
	 * @internal Filter.
	 */
	public function query_filter( $query ) {

		/**
		 * WPDB.
		 *
		 * @var \wpdb $wpdb
		 */
		global $wpdb;

		$query['join']  .= " LEFT JOIN {$wpdb->postmeta} AS meta_currency ON meta_currency.post_id = posts.ID ";
		$query['where'] .= $wpdb->prepare(
			' AND meta_currency.meta_key = %s AND meta_currency.meta_value = %s ',
			'_order_currency',
			$this->selected_currency
		);

		return $query;
	}

	/**
	 * A way to check if we are in the admin.php?page=wc-reports&tab=customers&report=customer_list
	 *
	 * @since 2.1.0
	 *
	 * @return bool
	 */
	protected function is_report_customer_list() {
		return Env::is_function_in_backtrace( array( 'WC_Report_Customer_List', 'column_default' ) );
	}

	/**
	 * Prevent caching of the Total Customer Spent value so we always run the (filtered) DB query.
	 *
	 * @since    2.1.0
	 *
	 * @param $spent
	 *
	 * @return string
	 * @internal Filter.
	 */
	public function prevent_caching_customer_spent( $spent ) {
		if ( $this->is_report_customer_list() ) {
			$spent = '';
		}

		return $spent;
	}

	/**
	 * Restrict the Total Spent query to the selected currency.
	 *
	 * @since    2.1.0
	 *
	 * @param string $sql The SQL statement.
	 *
	 * @return string
	 * @internal Filter.
	 */
	public function filter_total_spent_query( $sql ) {

		if ( ! $this->is_report_customer_list() ) {
			return $sql;
		}

		/**
		 * WPDB.
		 *
		 * @var \wpdb $wpdb
		 */
		global $wpdb;

		 // Insert additional JOIN before WHERE.
		$sql = str_replace( 'WHERE', "LEFT JOIN {$wpdb->postmeta} AS meta_currency ON meta_currency.post_id = posts.ID\n WHERE", $sql );

		// Append the filter by currency.
		$sql .= $wpdb->prepare( ' AND meta_currency.meta_key = %s AND meta_currency.meta_value = %s ',
			'_order_currency',
			$this->selected_currency );

		return $sql;
	}
}
