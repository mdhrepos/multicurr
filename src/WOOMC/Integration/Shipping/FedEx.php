<?php
/**
 * FedEx.php
 * Support the WooCommerce Shipping extension.
 *
 * @since   1.9.0
 * Copyright (c) 2018. TIV.NET INC. All Rights Reserved.
 * @package WOOMC\Integration\Shipping
 */

namespace WOOMC\Integration\Shipping;

use TIVWP_140\Logger\Message;
use WOOMC\App;
use WOOMC\Log;

/**
 * Data and method specific to this shipping method.
 */
class FedEx extends AbstractController {

	/**
	 * Method ID.
	 *
	 * @var string
	 */
	const METHOD_ID = 'fedex';

	/**
	 * If true, the shipping method needs store currency and unconverted product prices.
	 *
	 * @since 2.6.0
	 *
	 * @var bool
	 */
	const CALCULATE_IN_STORE_CURRENCY = true;

	/**
	 * Setup actions and filters.
	 *
	 * @since        2.5.4 Additional filters for FedEx.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		parent::setup_hooks();

		\add_filter( 'woocommerce_shipping_fedex_check_store_currency', '__return_false' );

		\add_filter(
			'woocommerce_shipping_fedex_rate',
			array( $this, 'filter__woocommerce_shipping_fedex_rate' ),
			App::HOOK_PRIORITY_LATE,
			4
		);
	}

	/**
	 * DO NOT Filter the rate cost.
	 *
	 * @since        2.5.4
	 *
	 * @param float|int|string  $cost                 The shipping rate cost.
	 * @param \WC_Shipping_Rate $shipping_rate_object The shipping rate object.
	 *
	 * @return float|int|string
	 * @internal     filter.
	 */
	public function filter__woocommerce_shipping_rate_cost( $cost, $shipping_rate_object ) {
		return $cost;
	}

	/**
	 * DO NOT Filter the taxes.
	 *
	 * @since        2.5.4
	 *
	 * @param float[]|int[]|string[] $taxes                The shipping rate taxes array.
	 * @param \WC_Shipping_Rate      $shipping_rate_object The shipping rate object.
	 *
	 * @return float[]|int[]|string[]
	 * @internal     filter.
	 */
	public function filter__woocommerce_shipping_rate_taxes( $taxes, $shipping_rate_object ) {
		return $taxes;
	}

	/**
	 * Convert rate.
	 *
	 * @since        2.5.4
	 *
	 * @param array              $rate_data              The rate data.
	 *                                                   'id'    => $rate_id,
	 *                                                   'label' => $rate_name,
	 *                                                   'cost'  => $rate_cost,
	 *                                                   'sort'  => $sort,
	 *
	 * @param string             $currency               The currency code.
	 * @param \stdClass          $details                Unused.
	 * @param \WC_Shipping_Fedex $shipping_method_object FedEx shipping object.
	 *
	 * @return array
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function filter__woocommerce_shipping_fedex_rate( $rate_data, $currency, $details, $shipping_method_object ) {

		$converted_cost = $this->price_controller->convert_raw( $rate_data['cost'], null, '', $currency );

		if ( \WC_Log_Levels::DEBUG === Log::threshold() ) {
			Log::debug( new Message( implode( '|', array(
				'Shipping Gateway=' . self::METHOD_ID,
				'Method=' . $rate_data['label'],
				'Cost Currency=' . $currency,
				'Cost=' . $rate_data['cost'],
				'Active Currency=' . \apply_filters( 'woocommerce_multicurrency_active_currency', '' ),
				'Converted Cost=' . $converted_cost,
			) ) ) );
		}

		$rate_data['cost'] = $converted_cost;

		return $rate_data;
	}

	/**
	 * Returns true if my shipping method class is in the trace.
	 *
	 * @since 2.5.4
	 * @return bool
	 */
	public static function is_called_by_my_method() {
		$dbt           = 'debug_backtrace';
		$steps_with_me = array_filter( $dbt( DEBUG_BACKTRACE_IGNORE_ARGS ),
			function ( $step ) {
				return isset( $step['class'] ) && 'WC_Shipping_Fedex' === $step['class'];
			}
		);

		return (bool) count( $steps_with_me );

	}

}
