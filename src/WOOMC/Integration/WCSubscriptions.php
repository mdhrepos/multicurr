<?php
/**
 * Integration.
 * Plugin Name: WooCommerce Subscriptions
 * Plugin URI: https://woocommerce.com/products/woocommerce-subscriptions/
 *
 * @since 1.3.0
 * @since 2.0.0 Own class
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration;

use TIVWP_140\Env;
use WOOMC\App;
use WOOMC\DAO\Factory;

/**
 * Class WCSubscriptions
 *
 * @package WOOMC\Integration
 */
class WCSubscriptions extends AbstractIntegration {

	/**
	 * If `$product->get_type()` returns one of these values, then it's "my product".
	 *
	 * @var string[]
	 */
	const MY_PRODUCT_TYPES = array(
		'subscription',
		'subscription_variation',
		'variable-subscription',
	);


	/**
	 * Is the product "mine"?
	 *
	 * @param \WC_Product $product The Product object.
	 *
	 * @return bool
	 */
	protected function is_my_product( $product ) {
		return $product && is_a( $product, 'WC_Product' ) && $product->is_type( self::MY_PRODUCT_TYPES );
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		if ( Env::on_front() ) {
			add_filter(
				'woocommerce_multicurrency_get_props_filters',
				array(
					$this,
					'filter__woocommerce_multicurrency_get_props_filters',
				)
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

			\add_filter(
				'woocommerce_subscriptions_product_sign_up_fee',
				array(
					$this,
					'filter__woocommerce_subscriptions_product_sign_up_fee',
				),
				App::HOOK_PRIORITY_EARLY,
				2
			);
		}

		if ( Env::in_wp_admin() ) {
			\add_filter(
				'woocommerce_multicurrency_custom_pricing_meta_keys',
				array(
					$this,
					'filter__woocommerce_multicurrency_custom_pricing_meta_keys',
				),
				App::HOOK_PRIORITY_EARLY,
				2
			);
		}

	}

	/**
	 * Additional tags to hook to the product price conversion.
	 *
	 * @since 1.3.0
	 *
	 * @param string[] $filter_tags The array of filter tags (the filter names).
	 *
	 * @return string[]
	 */
	public function filter__woocommerce_multicurrency_get_props_filters( $filter_tags ) {

		/**
		 * These values are stored in metas, but the {@see \WC_Data::get_meta} hook
		 * is similar to {@see \WC_Data::get_prop}.
		 */
		$filter_tags[] = 'woocommerce_product_variation_get__subscription_price';

		return $filter_tags;
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
		 * Turn off conversion when a Subscription is being switched.
		 * Otherwise, WCS compare old and new prices in different currencies (they switch one of the filters).
		 *
		 * @since 1.15.0
		 */
		if ( Env::is_functions_in_backtrace(
			array(
				array( 'WC_Subscriptions_Switcher', 'calculate_prorated_totals' ),
			)
		) ) {
			return $value;
		}

		/**
		 * If the product has "changes", use them.
		 * They can appear, for instance, with NameYourPrice.
		 *
		 * @since 2.3.0
		 */
		$changes = $product->get_changes();
		if ( ! empty( $changes['price'] ) ) {
			return $this->price_controller->convert( $changes['price'] );
		}

		/**
		 * A "hack": to avoid double conversion, let's get the subscription metas ourselves and convert.
		 *
		 * @since 2.0.0
		 */
		$price_meta_key = $price_type ? $price_type : '_price';

		$price = \get_post_meta( $product->get_id(), $price_meta_key, true );
		if ( (string) $value !== (string) $price ) {
			return $this->price_controller->convert( $price );
		}

		// Default: we do not interfere. Let the calling method continue.
		return $pre_value;
	}

	/**
	 * Convert subscription sign-up fee.
	 *
	 * @since 2.0.0
	 * @since 2.2.0 Check for per-product pricing.
	 *
	 * @param string|int|float $value   The price.
	 * @param \WC_Product|null $product The product object.
	 *
	 * @return float|int|string
	 */
	public function filter__woocommerce_subscriptions_product_sign_up_fee( $value, $product ) {

		if ( Factory::getDao()->isAllowPricePerProduct() ) {
			$currency     = \get_woocommerce_currency();
			$custom_value = $product->get_meta( '_subscription_sign_up_fee_' . $currency );
			if ( $custom_value ) {
				return $custom_value;
			}
		}

		return $this->price_controller->convert( $value, $product );
	}

	/**
	 * Meta keys to keep custom pricing values.
	 *
	 * @since 2.0.0
	 * @since 2.4.0 Product object passed instead of product type.
	 *
	 * @param string[]    $keys    Array of meta keys.
	 * @param \WC_Product $product The product object.
	 *
	 * @return string[]
	 */
	public function filter__woocommerce_multicurrency_custom_pricing_meta_keys( $keys, $product ) {
		if ( 'subscription' === $product->get_type() ) {
			$keys = array(
				'_subscription_price_'       => __( 'Regular price', 'woocommerce' ),
				'_sale_price_'               => __( 'Sale price', 'woocommerce' ),
				'_subscription_sign_up_fee_' => __( 'Subscription sign-up fee', 'woocommerce-subscriptions' ),
			);
		}

		return $keys;
	}
}
