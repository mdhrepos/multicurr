<?php
/**
 * Integration.
 * Standard PayPal gateway.
 *
 * @since 1.18.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration\Gateways;

/**
 * Class WCGatewayPaypal
 *
 * @package WOOMC\Integration\Gateways
 */
class WCGatewayPaypal extends AbstractGateways {

	/**
	 * Setup actions and filters.
	 *
	 * @scope in_wp_admin
	 * @return void
	 */
	public function setup_hooks() {

		// Trick to enable gateway regardless of the base currency.
		// Need it only in admin. At the front, must use the actual list of currencies to show PayPal at checkout.
		\add_filter( 'woocommerce_paypal_supported_currencies', array( $this, 'active_currency_as_array' ) );
	}
}
