<?php
/**
 * Product information.
 *
 * @since 1.13.0
 * @since 1.19.0 Renamed to Info
 *
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Product;

use WOOMC\DAO\Factory;

/**
 * Class Product\Info
 */
class Info {

	/**
	 * Return this if garbage was passed to {@see classname()}.
	 *
	 * @since 1.13.0
	 *
	 * @var string
	 */
	const NOT_AN_OBJECT = 'NOT_AN_OBJECT';

	/**
	 * The WC Product object.
	 *
	 * @since 1.19.0
	 *
	 * @var \WC_Product
	 */
	protected $product;

	/**
	 * Is custom pricing enabled in the settings?
	 *
	 * @since 1.20.1
	 * @var bool
	 */
	protected $custom_pricing_enabled = true;

	/**
	 * Info constructor.
	 *
	 * @since 1.19.0
	 *
	 * @param \WC_Product $product The product object.
	 */
	public function __construct( \WC_Product $product ) {
		$this->product = $product;
		if ( ! Factory::getDao()->isAllowPricePerProduct() ) {
			// Custom pricing per product is not allowed in Settings.
			$this->custom_pricing_enabled = false;
		}

	}

	/**
	 * Get the class of product object.
	 *
	 * @since 1.13.0
	 *
	 * @param mixed $product Can be anything: product, its ID, null, etc.
	 *
	 * @return string The class.
	 */
	public static function classname( $product ) {
		$product_class = self::NOT_AN_OBJECT;

		if ( null !== $product ) {
			if ( is_numeric( $product ) ) {
				// Product ID passed.
				$product = \wc_get_product( $product );
			}
			if ( is_object( $product ) ) {
				$product_class = get_class( $product );
			}
		}

		return $product_class;

	}

	/**
	 * Check if the product has a sale schedule.
	 *
	 * @since 1.19.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return bool
	 */
	public function has_sale_schedule( $context = 'edit' ) {

		return $this->product->get_date_on_sale_from( $context ) && $this->product->get_date_on_sale_to( $context );
	}

	/**
	 * Check if the product is currently on sale schedule.
	 * Checks the dates only and ignores the prices.
	 *
	 * @since 1.19.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return bool
	 */
	public function is_on_sale_schedule( $context = 'edit' ) {

		$on_sale = false;

		$current_time = time();

		if (
			$this->has_sale_schedule( $context )
			&& $this->product->get_date_on_sale_from( $context )->getTimestamp() <= $current_time
			&& $this->product->get_date_on_sale_to( $context )->getTimestamp() >= $current_time
		) {
			$on_sale = true;
		}

		return $on_sale;
	}

	/**
	 * Check if the product has custom pricing for the specified currency.
	 *
	 * @since 1.19.0
	 *
	 * @param string $currency The currency code. Default is the currently selected currency.
	 * @param string $context  What the value is for. Valid values are view and edit.
	 *
	 * @return bool
	 */
	public function is_custom_priced( $currency = '', $context = 'edit' ) {

		/**
		 * Bail out if price per product is not allowed in Settings.
		 *
		 * @since 1.19.1
		 */
		if ( ! $this->custom_pricing_enabled ) {
			return false;
		}

		$currency = $currency ? $currency : \get_woocommerce_currency();

		foreach ( array( '_regular_price_', '_sale_price_' ) as $custom_price_meta_key ) {
			if ( $this->product->get_meta( $custom_price_meta_key . $currency, true, $context ) ) {
				return true;
			}
		}

		return false;

	}

	/**
	 * Get the "raw" regular price.
	 *
	 * @since 1.20.1
	 *
	 * @return string
	 * @noinspection PhpUnused
	 */
	public function get_raw_regular_price() {

		return \get_post_meta( $this->product->get_id(), '_regular_price', true );
	}

	/**
	 * Get the "raw" sale price.
	 *
	 * @since 1.20.1
	 *
	 * @return string
	 */
	public function get_raw_sale_price() {

		return \get_post_meta( $this->product->get_id(), '_sale_price', true );
	}

	/**
	 * Get the custom regular price.
	 *
	 * @since 1.20.1
	 *
	 * @param string $currency The currency code. Default is the currently selected currency.
	 * @param string $context  What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_custom_regular_price( $currency = '', $context = 'edit' ) {

		if ( ! $this->custom_pricing_enabled ) {
			// Custom pricing per product is not allowed in Settings.
			return '';
		}

		$currency = $currency ? $currency : \get_woocommerce_currency();

		return $this->product->get_meta( '_regular_price_' . $currency, true, $context );
	}

	/**
	 * Get the custom sale price.
	 *
	 * @since 1.20.1
	 *
	 * @param string $currency The currency code. Default is the currently selected currency.
	 * @param string $context  What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_custom_sale_price( $currency = '', $context = 'edit' ) {

		if ( ! $this->custom_pricing_enabled ) {
			// Custom pricing per product is not allowed in Settings.
			return '';
		}

		if ( $this->has_sale_schedule() && ! $this->is_on_sale_schedule() ) {
			// Has a schedule but we are not within it.
			return '';
		}

		$currency = $currency ? $currency : \get_woocommerce_currency();

		return $this->product->get_meta( '_sale_price_' . $currency, true, $context );
	}
}
