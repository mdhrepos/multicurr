<?php
/**
 * Integration.
 * Plugin Name: WooCommerce Accommodation Bookings.
 * Plugin URI: https://woocommerce.com/products/woocommerce-accommodation-bookings/
 *
 * @since   1.13.0
 * Copyright (c) 2019. TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration\Booking;

use TIVWP_140\Env;

/**
 * Class WCAccommodationBooking
 */
class WCAccommodationBooking extends ABookingIntegration {

	/**
	 * The PHP class of the product I am working with.
	 *
	 * @return string
	 */
	protected function my_product_class() {
		return 'WC_Product_Accommodation_Booking';
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		if ( Env::in_wp_admin() ) {
			return;
		}

		parent::setup_hooks();
	}

	/**
	 * Short-circuit the price conversion for Bookings in some specific cases.
	 *
	 * @param false|string|int|float                 $pre_value  Initially passed as "false". May return the actual value.
	 * @param string|int|float                       $value      The price.
	 * @param \WC_Product_Accommodation_Booking|null $product    The product object.
	 * @param string                                 $price_type Regular, Sale, etc.
	 *
	 * @return string|int|float|false
	 *
	 * @internal filter.
	 */
	public function filter__woocommerce_multicurrency_pre_product_get_price( $pre_value, $value, $product = null, $price_type = '' ) {

		if ( false !== $pre_value ) {
			// A previous filter already set the `$pre_value`. We do not disturb.
			return $pre_value;
		}

		if ( ! $this->is_my_product( $product ) ) {
			// Not my business.
			return $pre_value;
		}

		/**
		 * Do not convert when added to Cart (values are already converted).
		 */
		if ( Env::is_functions_in_backtrace(
			array(
				array( 'WC_Cart_Totals', 'get_items_from_cart' ),
				array( 'WC_Cart', 'calculate_totals' ),
				array( 'WC_Cart', 'get_product_price' ),
				array( 'WC_Cart', 'get_product_subtotal' ),
			)
		)
		) {
			return $value;
		}

		// Default: we do not interfere. Let the calling method continue.
		return $pre_value;
	}

}
