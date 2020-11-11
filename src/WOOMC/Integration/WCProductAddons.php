<?php
/**
 * Integration.
 * Plugin Name: WooCommerce Product Add-ons.
 * Plugin URI: https://woocommerce.com/products/product-add-ons/
 *
 * @since   1.6.0
 * @since   1.13.0 Refactored and moved to a separate class.
 *
 * @package WOOMC\Integration
 * Copyright (c) 2019. TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration;

use TIVWP_140\Env;
use TIVWP_140\InterfaceHookable;
use WOOMC\App;
use WOOMC\Price;


/**
 * Class WCProductAddons
 *
 * @package WOOMC\Integration
 */
class WCProductAddons implements InterfaceHookable {

	/**
	 * DI: Price Controller.
	 *
	 * @var Price\Controller
	 */
	protected $price_controller;

	/**
	 * Constructor.
	 *
	 * @param Price\Controller $price_controller The Price controller instance.
	 */
	public function __construct( Price\Controller $price_controller ) {
		$this->price_controller = $price_controller;
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		add_filter(
			'woocommerce_product_addons_option_price_raw',
			array( $this, 'filter__woocommerce_product_addons_option_price_raw' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		add_filter(
			'woocommerce_product_addons_price_raw',
			array( $this, 'filter__woocommerce_product_addons_price_raw' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		add_filter(
			'woocommerce_get_price_excluding_tax',
			array( $this, 'filter__product_addons' ),
			App::HOOK_PRIORITY_EARLY
		);

		add_filter(
			'woocommerce_get_price_including_tax',
			array( $this, 'filter__product_addons' ),
			App::HOOK_PRIORITY_EARLY
		);

	}

	/**
	 * Convert addon prices. For the checkbox, radio, etc.
	 *
	 * @since    1.6.0
	 * @since    1.14.1 Fix: do not convert percentage-based addons.
	 *
	 * @param float|int|string $option_price Cost of the add-on option.
	 * @param string[]         $option       The option.
	 *
	 * @return float|int|string
	 *
	 * @internal filter
	 */
	public function filter__woocommerce_product_addons_option_price_raw( $option_price, $option ) {
		if ( in_array( $option['price_type'], array( 'flat_fee', 'quantity_based' ), true ) ) {
			$option_price = $this->price_controller->convert( $option_price );
		}

		return $option_price;
	}

	/**
	 * Convert addon prices. For the add-ons that are not "multiple choice".
	 *
	 * @since    1.15.0
	 *
	 * @param float|int|string $addon_price Cost of the add-on.
	 * @param string[]         $addon       The add-on.
	 *
	 * @return float|int|string
	 *
	 * @internal filter
	 */
	public function filter__woocommerce_product_addons_price_raw( $addon_price, $addon ) {
		if ( in_array( $addon['price_type'], array( 'flat_fee', 'quantity_based' ), true ) ) {
			$addon_price = $this->price_controller->convert( $addon_price );
		}

		return $addon_price;
	}

	/**
	 * Filter Product Add-ons display prices. For special cases only: the Cart page
	 *
	 * @note     As of POA Version: 3.0.5, there is a bug in the function
	 *
	 * @since    1.6.0
	 *
	 * @param int|float|string $price The price.
	 *
	 * @return float|int|string
	 *
	 * @see      \WC_Product_Addons_Helper::get_product_addon_price_for_display.
	 * `if ( ( is_cart() || is_checkout() ) && null !== $cart_item ) {` is wrong
	 * because it does not consider the mini-cart widget.
	 *
	 * @internal filter.
	 */
	public function filter__product_addons( $price ) {

		// Only if called by certain functions.
		if (
		Env::is_functions_in_backtrace(
			array(
				array( 'WC_Product_Addons_Cart', 'get_item_data' ),
				array( 'Product_Addon_Display', 'totals' ),
			)
		)
		) {
			/**
			 * Only if the price was not retrieved from the Product (and therefore already converted),
			 * but passed as a parameter to...
			 *
			 * @see \wc_get_price_excluding_tax
			 * @see \wc_get_price_including_tax
			 */
			$called_by = Env::get_hook_caller();
			if ( ! empty( $called_by['args'][1]['price'] ) ) {
				$price = $this->price_controller->convert( $price );
			}
		}

		return $price;
	}
}
