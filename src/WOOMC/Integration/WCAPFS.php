<?php
/**
 * Integration.
 *
 * Plugin Name: WooCommerce All Products For Subscriptions
 * Plugin URI: https://woocommerce.com/product/all-products-for-woocommerce-subscriptions
 * Original name: "WooCommerce Subscribe All The Things" (WCS_ATT).
 * Original URI: https://github.com/Prospress/woocommerce-subscribe-all-the-things
 *
 * @since 1.4.0
 * @since 1.15.0 The extension is published on WooCommerce under a new name.
 * @since 2.6.7-beta.1 Moved to own class.
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration;

use WOOMC\App;

/**
 * Class WCAPFS
 *
 * @package WOOMC\Integration
 */
class WCAPFS extends AbstractIntegration {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		// Convert subscription scheme.
		\add_filter(
			'wcsatt_subscription_scheme_prices',
			array( $this, 'filter__wcsatt_subscription_scheme_prices' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		// Disable the conversion in certain circumstances.
		\add_filter(
			'woocommerce_multicurrency_pre_product_get_price',
			array(
				$this,
				'filter__woocommerce_multicurrency_pre_product_get_price',
			),
			App::HOOK_PRIORITY_EARLY,
			4
		);

	}

	/**
	 * WCS_ATT adds its own filters to the prices - {@see \WCS_ATT_Product_Price_Filters::add},
	 * so our previously converted prices are ineffective when the subscription scheme prices
	 * are calculated. Thus, we convert the prices again, using this filter.
	 *
	 * @since 2.7.0 Convert only fixed prices, not calculated as percents ("inherited").
	 *
	 * @param array           $prices Prices.
	 * @param \WCS_ATT_Scheme $scheme Scheme.
	 *
	 * @return array
	 */
	public function filter__wcsatt_subscription_scheme_prices( $prices, $scheme ) {

		if ( 'inherit' === $scheme->get_pricing_mode() ) {
			// No conversion. It's a percent.
			return $prices;
		}

		return $this->price_controller->convert_array( $prices );
	}

	/**
	 * Short-circuit the price conversion in some specific cases.
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
		 * A hack to fix the combination of APFS and Dynamic Pricing.
		 *
		 * @since 2.6.7-beta.1
		 */
		if (
			$product
			&& class_exists( 'WC_Dynamic_Pricing', false )
			&& class_exists( 'WCS_ATT_Product', false )
			&& \WCS_ATT_Product::is_subscription( $product )
			&& $product->get_changes() ) {
			return $value;
		}

		// Default: we do not interfere. Let the calling method continue.
		return $pre_value;
	}
}
