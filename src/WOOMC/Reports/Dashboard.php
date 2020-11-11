<?php
/**
 * Reports dashboard.
 *
 * @since 1.7.0
 * Copyright (c) 2018. TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Reports;

use TIVWP_140\InterfaceHookable;
use TIVWP_140\SQL;
use WOOMC\App;

/**
 * Class Dashboard
 *
 * @package WOOMC\Reports
 */
class Dashboard implements InterfaceHookable {

	/**
	 * The selected currency.
	 *
	 * @var string
	 */
	protected $selected_currency = '';

	/**
	 * Dashboard constructor.
	 *
	 * @param string $selected_currency The selected currency.
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
		add_action(
			'woocommerce_after_dashboard_status_widget',
			array(
				$this,
				'currency_selector',
			),
			App::HOOK_PRIORITY_LATE
		);

		add_filter(
			'woocommerce_reports_get_order_report_query',
			array(
				$this,
				'query_filter',
			)
		);

		/**
		 * Not sure if we need to filter the top seller by currency.
		 */
		if ( 0 ) {
			add_filter(
				'woocommerce_dashboard_status_widget_top_seller_query',
				array(
					$this,
					'query_filter',
				)
			);
		}

	}

	/**
	 * Currency selector on dashboard widget.
	 */
	public function currency_selector() {
		// The empty A tag is needed for the icon.
		?>
		<li class="sales-this-month">
			<a>
				<strong><?php CurrencySelector::render( $this->selected_currency ); ?></strong>(
				<?php
				// translators: %s: net sales.
				printf( esc_html__( '%s net sales this month', 'woocommerce' ), '' );
				?>
				)
			</a>
		</li>
		<?php
	}

	/**
	 * Adjust queries used in the dashboard widget.
	 *
	 * @param array $query The query.
	 *
	 * @return array
	 *
	 * @internal Filter.
	 */
	public function query_filter( $query ) {
		$sql_in = $this->order_ids_csv( $this->selected_currency );

		if ( $sql_in ) {
			$query['where'] .= ' AND posts.ID IN (' . $sql_in . ') ';
		} else {
			// Shortcut the SQl with a false condition is no orders in this currency.
			$query['where'] .= ' AND 1=0 ';
		}

		return $query;
	}

	/**
	 * Comma-separated list of order IDs for use in an SQL IN(...) statement.
	 *
	 * @param string $currency Currency code.
	 *
	 * @return string
	 */
	protected function order_ids_csv( $currency ) {

		/**
		 * Cache.
		 *
		 * @var string[] $cached_sql
		 */
		static $cached_sql = array();

		if ( ! isset( $cached_sql[ $currency ] ) ) {

			/**
			 * WPDB.
			 *
			 * @var \wpdb $wpdb
			 */
			global $wpdb;

			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s",
					'_order_currency',
					$this->selected_currency
				)
			);

			$cached_sql[ $currency ] = SQL::in( $ids, '%d' );
		}

		return $cached_sql[ $currency ];
	}
}
