<?php
/**
 * Order Query.
 *
 * @since 1.1.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace TIVWP_140\WC\Order;

/**
 * Class Order\Query
 */
class Query {

	/**
	 * Default query args.
	 *
	 * @var array
	 */
	protected $query_args = array(
		'return'        => 'ids',
		'limit'         => '-1',
		'orderby'       => 'none',
		'no_found_rows' => true,
		'cache_results' => false,
	);

	/**
	 * Set the Query argument.
	 * Also works as a workaround for PHPCS warning about slow query.
	 *
	 * @param string           $k Key.
	 * @param string|int|array $v Value.
	 */
	public function set_query_arg( $k, $v ) {
		$this->query_args[ $k ] = $v;
	}

	/**
	 * Return only paid orders.
	 *
	 * @return self
	 */
	public function restrict_to_paid() {
		$this->query_args['status'] = \wc_get_is_paid_statuses();

		return $this;
	}

	/**
	 * Return only orders with specific User ID in the customer meta.
	 *
	 * @param int $user_id The User ID.
	 *
	 * @return self
	 */
	public function restrict_to_user_id( $user_id ) {
		$this->set_query_arg( 'customer_id', $user_id );

		return $this;
	}

	/**
	 * Return only orders made after certain date/hour.
	 *
	 * @param int    $value How many units (1,2,365).
	 * @param string $units Which units (years, months, etc.).
	 *
	 * @return self
	 */
	public function restrict_to_after_date( $value, $units ) {

		if ( $value < 1 || ! in_array( $units, array( 'years', 'months', 'days', 'hours' ), true ) ) {
			return $this;
		}

		// Today  minus expiration.
		$date = new \DateTime( "-{$value} {$units}" );

		$this->set_query_arg( 'date_query', array(
			'after' => array(
				'year'  => (int) $date->format( 'Y' ),
				'month' => (int) $date->format( 'm' ),
				'day'   => (int) $date->format( 'd' ),
				'hour'  => (int) $date->format( 'H' ),
			),
		) );

		return $this;
	}

	/**
	 * Run the query and return the product IDs found.
	 *
	 * @return int[] Product IDs.
	 */
	public function get_product_ids() {

		$product_ids = array();

		$order_ids = \wc_get_orders( $this->query_args );

		foreach ( $order_ids as $order_id ) {
			$order_info  = new Info( $order_id );
			$order_items = $order_info->get_items();
			foreach ( $order_items as $order_item ) {
				$product_ids[] = $order_item->get_product_id();
			}
		}

		return $product_ids;
	}
}
