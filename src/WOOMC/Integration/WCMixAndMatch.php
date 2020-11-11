<?php
/**
 * Integration.
 * Plugin Name: Mix and Match Products
 * Plugin URI: https://woocommerce.com/products/woocommerce-mix-and-match-products/
 *
 * @since   1.16.0
 * @package WOOMC\Integration
 * Author:  Kathy Darling
 */

namespace WOOMC\Integration;

use TIVWP_140\Env;
use WOOMC\App;

/**
 * Class WCMixAndMatch
 */
class WCMixAndMatch extends AbstractIntegration {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		if ( ! Env::in_wp_admin() ) {
			// Disable the conversion in certain circumstances.
			add_filter(
				'woocommerce_multicurrency_pre_product_get_price',
				array(
					$this,
					'filter__woocommerce_multicurrency_pre_product_get_price',
				),
				App::HOOK_PRIORITY_EARLY,
				4
			);
		}
	}

	/**
	 * Short-circuit the price conversion for child products in some specific cases.
	 *
	 * @param false|string|int|float $pre_value  Initially passed as "false". May return the actual value.
	 * @param string|int|float       $value      The price.
	 * @param \WC_Product|null       $product    The product object.
	 * @param string                 $price_type Regular, Sale, etc.
	 *
	 * @return string|int|float|false
	 *
	 * @internal     filter.
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function filter__woocommerce_multicurrency_pre_product_get_price( $pre_value, $value, $product = null, $price_type = '' ) {

		if ( false !== $pre_value ) {
			// A previous filter already set the `$pre_value`. We do not disturb.
			return $pre_value;
		}

		/**
		 * Don't convert prices when calculating the child prices.
		 * Will need to think of a way to target products only when they are "bundled".
		 */
		if ( Env::is_functions_in_backtrace(
			array(
				array( 'WC_Product_Mix_and_Match', 'maybe_apply_discount_to_child' ),
			)
		)
		) {
			return $value;
		}

		// Default: we do not interfere. Let the calling method continue.
		return $pre_value;
	}

}
