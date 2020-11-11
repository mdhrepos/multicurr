<?php
/**
 * Integration.
 * Plugin Name: WooCommerce Stripe Gateway
 * Plugin URI: https://wordpress.org/plugins/woocommerce-gateway-stripe/
 *
 * @since 1.18.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration\Gateways;

use TIVWP_140\Env;

/**
 * Class WCStripe
 *
 * @package WOOMC\Integration\Gateways
 */
class WCStripe extends AbstractGateways {

	/**
	 * List of Stripe gateways.
	 *
	 * @see \WC_Stripe::add_gateways
	 * @var string[]
	 */
	const GATEWAYS = array(
		'alipay',
		'bancontact',
		'eps',
		'giropay',
		'ideal',
		'multibanco',
		'p24',
		'sepa',
		'sofort',
	);

	/**
	 * Setup actions and filters.
	 *
	 * @scope in_wp_admin
	 *
	 * @return void
	 */
	public function setup_hooks() {

		$this->disable_store_currency_admin_notices();
	}

	/**
	 * Trick to enable gateways regardless of the base currency.
	 * (In fact, they are enabled, just show annoying admin error message).
	 */
	protected function disable_store_currency_admin_notices() {
		foreach ( self::GATEWAYS as $gateway ) {
			add_filter( "wc_stripe_{$gateway}_supported_currencies", array( $this, 'active_currency_as_array' ) );
		}
	}
}
