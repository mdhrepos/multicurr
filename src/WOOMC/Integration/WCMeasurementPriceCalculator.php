<?php
/**
 * Integration.
 * Plugin Name: WooCommerce Measurement Price Calculator
 * Plugin URI: http://www.woocommerce.com/products/measurement-price-calculator/
 *
 * @since 1.18.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration;

use WOOMC\App;

/**
 * Class WCMeasurementPriceCalculator
 *
 * @package WOOMC\Integration
 */
class WCMeasurementPriceCalculator extends AbstractIntegration {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		/**
		 * When added to Cart.
		 *
		 * @todo This hook is probably wrong. Gives incorrect results when more than one item is added to the cart.
		 */
		0 && add_filter(
			'wc_measurement_price_calculator_calculate_price',
			function (
				$price,
				$product,
				/**
				 * Unused.
				 *
				 * @noinspection PhpUnusedParameterInspection
				 */
				$measurement_needed_value,
				/**
				 * Unused.
				 *
				 * @noinspection PhpUnusedParameterInspection
				 */
				$measurement_needed_value_unit
			) {
				return $this->price_controller->convert( $price, $product );
			},
			App::HOOK_PRIORITY_EARLY,
			4
		);

		/**
		 * When displayed formatted price in the Cart.
		 *
		 * @see  \WC_Price_Calculator_Cart::get_cart_widget_item_price_html()
		 * @todo The hook does not exist yet. See the proposed code below.
		 */
		1 && add_filter(
			'wc_measurement_price_calculator_cart_widget_item_price',
			function ( $price ) {
				return $this->price_controller->convert( $price );
			},
			App::HOOK_PRIORITY_EARLY,
			1
		);
	}

	/**
	 * Proposed changes to the method.
	 * Returns the price HTML for the given cart item, to display in the
	 * cart Widget
	 *
	 * @since 3.0
	 *
	 * @param string $price_html    the price html.
	 * @param array  $cart_item     the cart item.
	 * @param string $cart_item_key the unique cart item hash.
	 *
	 * @return string the price html
	 */
	public function proposed_get_cart_widget_item_price_html( $price_html, $cart_item, $cart_item_key ) {

		// If this is a pricing calculator item, and WooCommerce Product Addons hasn't already altered the price.
		if ( empty( $cart_item['addons'] ) && isset( $cart_item['pricing_item_meta_data']['_price'] ) ) {

			// Let 3rd party extensions (such as Multi-Currency) alter the price.
			$price = $cart_item['pricing_item_meta_data']['_price'];
			$price = apply_filters( 'wc_measurement_price_calculator_cart_widget_item_price', $price );

			$price_html = wc_price( (float) $price );
		}

		// Default.
		return $price_html;
	}

}
