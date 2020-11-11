<?php
/**
 * Integration.
 * Plugin Name: WooCommerce Bookings.
 * Plugin URI: https://woocommerce.com/products/woocommerce-bookings/
 *
 * @since 1.3.0
 * @since 1.13.0 in its own class.
 * Copyright (c) 2019. TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration\Booking;

use TIVWP_140\Env;

/**
 * Class WCBookings
 */
class WCBookings extends ABookingIntegration {

	/**
	 * The PHP class of the product I am working with.
	 *
	 * @return string
	 */
	protected function my_product_class() {
		return 'WC_Product_Booking';
	}

	/**
	 * Is this my product?
	 *
	 * @since 2.3.0 Override the parent method to work with the OWCProductBooking class.
	 *
	 * @param \WC_Product $product The Product object.
	 *
	 * @return bool
	 */
	protected function is_my_product( $product ) {
		return method_exists( $product, 'get_type' ) && 'booking' === $product->get_type();
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

		// Base price on the single product page.
		// Base prices on the catalog pages.
		\add_filter(
			'woocommerce_multicurrency_get_props_filters',
			array( $this, 'filter__woocommerce_multicurrency_get_props_filters' )
		);

		parent::setup_hooks();

	}

	/**
	 * Additional tags to hook to the product price conversion.
	 *
	 * @since 1.3.0
	 * @since 1.17.2 Convert `display_cost`.
	 *
	 * @param string[] $filter_tags The array of filter tags (the filter names).
	 *
	 * @return string[]
	 */
	public function filter__woocommerce_multicurrency_get_props_filters( $filter_tags ) {
		/**
		 * These filters are called in the methods below.
		 *
		 * @see \WC_Product_Booking::get_block_cost
		 * @see \WC_Product_Booking::get_cost
		 * @see \WC_Product_Booking::get_display_cost
		 * @see \WC_Product_Booking_Person_Type::get_cost
		 * @see \WC_Product_Booking_Person_Type::get_block_cost
		 */
		$filter_tags[] = 'woocommerce_product_get_block_cost';
		$filter_tags[] = 'woocommerce_product_get_cost';
		$filter_tags[] = 'woocommerce_product_get_display_cost';
		$filter_tags[] = 'woocommerce_product_booking_person_type_get_block_cost';
		$filter_tags[] = 'woocommerce_product_booking_person_type_get_cost';

		return $filter_tags;
	}

	/**
	 * Short-circuit the price conversion in some specific cases.
	 *
	 * @param false|string|int|float   $pre_value  Initially passed as "false". May return the actual value.
	 * @param string|int|float         $value      The price.
	 * @param \WC_Product_Booking|null $product    The product object.
	 * @param string                   $price_type Regular, Sale, etc.
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
		 * In {@see \WC_Product_Booking::get_price_html},
		 * we need to convert the `$base_price` calculation,
		 * but not convert `$display_price_suffix` and `$original_price_suffix`
		 * because only one of the has filter, but we need either both or none.
		 */
		if (
			Env::is_function_in_backtrace( array( 'WC_Product_Booking', 'get_price_html' ) )
			&& ! Env::is_function_in_backtrace( array( 'WC_Bookings_Cost_Calculation', 'calculated_base_cost' ) )
		) {
			return $value;
		}

		/**
		 * Do not convert when added to Cart (values are already converted).
		 *
		 * @since 2.3.0 Removed `array( 'WC_Cart', 'calculate_totals' )`.
		 */
		if ( Env::is_functions_in_backtrace(
			array(
				array( 'WC_Cart_Totals', 'get_items_from_cart' ),
				array( 'WC_Cart', 'get_product_price' ),
				array( 'WC_Cart', 'get_product_subtotal' ),
			)
		)
		) {
			return $value;
		}

		/**
		 * When Dynamic Pricing loads the cart, it receives the price from "changes".
		 * The "changes" keeps the price of the product before the currency has switched.
		 * As a result, it shows a fake discount: "was 200 JPY and now 1 CAD".
		 *
		 * @since 1.17.1
		 * @see   \WC_Dynamic_Pricing::on_cart_loaded_from_session()
		 */
		if ( Env::is_functions_in_backtrace(
			array(
				array( 'WC_Dynamic_Pricing', 'on_cart_loaded_from_session' ),
			)
		)
		) {
			return $product->get_cost( 'edit' );
		}

		// Default: we do not interfere. Let the calling method continue.
		return $pre_value;
	}

}
