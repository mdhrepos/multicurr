<?php
/**
 * Integration.
 * Plugin Name: WooCommerce Payment Gateway Based Fees
 * Plugin URI: https://www.woothemes.com/products/payment-gateway-based-fees/
 *
 * @since 1.19.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration;

use WOOMC\App;

/**
 * Class WCAddFees
 *
 * @package WOOMC\Integration
 */
class WCAddFees extends AbstractIntegration {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		add_filter(
			'wc_add_fees_gateway_fee',
			array( $this, 'filter__wc_add_fees_gateway_fee' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		add_filter(
			'wc_add_fees_maximum_cart_order_value',
			array( $this, 'filter__wc_add_fees_min_max_value' ),
			App::HOOK_PRIORITY_EARLY
		);

		add_filter(
			'wc_add_fees_minimum_cart_order_value',
			array( $this, 'filter__wc_add_fees_min_max_value' ),
			App::HOOK_PRIORITY_EARLY
		);

		add_filter(
			'wc_add_fees_gateway_fee_minimum',
			array( $this, 'filter__wc_add_fees_min_max_value' ),
			App::HOOK_PRIORITY_EARLY
		);

	}

	/**
	 * Convert fixed values.
	 *
	 * @param float|int|string $fee     The fee stored in the option table.
	 * @param array            $gateway The gateway.
	 *
	 * @return float|int|string
	 */
	public function filter__wc_add_fees_gateway_fee( $fee, $gateway ) {

		if ( \WC_Add_Fees::VAL_FIXED === $gateway[ \WC_Add_Fees::OPT_KEY_ADD_VALUE_TYPE ] ) {
			$fee = $this->price_controller->convert( $fee );
		}

		return $fee;
	}

	/**
	 * Convert min/max values.
	 *
	 * @param float|int|string $value The value stored in the option table.
	 *
	 * @return float|int|string
	 */
	public function filter__wc_add_fees_min_max_value( $value ) {
		$value = $this->price_controller->convert( $value );

		return $value;
	}
}
