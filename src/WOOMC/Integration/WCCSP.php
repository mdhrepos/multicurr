<?php
/**
 * Integration.
 * Plugin Name: Customer Specific Pricing For WooCommerce
 * Plugin URI: https://wisdmlabs.com/woocommerce-user-specific-pricing-extension/
 *
 * @since  1.19.0
 * Author: WisdmLabs
 */

namespace WOOMC\Integration;

use TIVWP_140\InterfaceHookable;
use WOOMC\API;
use WOOMC\Price;

/**
 * Class Integration\WCCSP
 */
class WCCSP implements InterfaceHookable {

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

		\add_filter( 'wdm_csp_qty_prices_filter', array( $this, 'returnConvertedSpecificPrices' ), 99, 2 );

		\add_filter( 'csp_filter_cart_discount_limits', array( $this, 'returnConvertedCartDiscountLimits' ) );

		\add_filter( 'wdm_csp_convert_price_value', array( $this, 'convertPriceValue' ) );

		/**
		 * The filters below if applied without WCCSP would break the conversion completely.
		 */
		\add_filter( 'woocommerce_multicurrency_pre_product_get_price', array( $this, 'dontApplyInCart' ), 99, 4 );

		\add_filter(
			'woocommerce_multicurrency_get_props_filters',
			array( $this, 'dontApplyVariablePriceInCart' ),
			99,
			1
		);
	}

	/**
	 * This function restricts the currency conversion when CSP is active.
	 * This is implemented to avoid repeated conversions in the WooCommerce cart.
	 *
	 * @param string[] $filter_tags The filter tags.
	 *
	 * @return string[]
	 */
	public function dontApplyVariablePriceInCart( $filter_tags ) {

		// Do not convert variation price because it's converted by WCCSP somewhere already.
		foreach ( array_keys( $filter_tags, 'woocommerce_product_variation_get_price', true ) as $key ) {
			unset( $filter_tags[ $key ] );
		}

		return $filter_tags;
	}

	/**
	 * This function restricts the currency conversion when CSP is active.
	 * This is implemented to avoid repeated conversions in the WooCommerce cart.
	 *
	 * @param bool             $pre_value  Unused.
	 * @param string|int|float $value      The price.
	 * @param \WC_Product|null $product    The product object.
	 * @param string           $price_type Regular, Sale, etc.
	 *
	 * @return string|int|float
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function dontApplyInCart( $pre_value, $value, $product, $price_type = '' ) {
		return $value;
	}


	/**
	 * Return converted specific prices.
	 *
	 * @param array       $merged_prices Merged prices.
	 * @param \WC_Product $product       Product object.
	 *
	 * @return mixed
	 */
	public function returnConvertedSpecificPrices( $merged_prices, $product ) {
		$default_currency = API::default_currency();
		$new_prices       = $merged_prices;
		if ( \get_woocommerce_currency() !== $default_currency ) {
			foreach ( $merged_prices as $qty => $value ) {
				$price              = $this->price_controller->convert( $value, $product );
				$new_prices[ $qty ] = $price;
			}
		}

		return $new_prices;
	}

	/**
	 * ReturnConvertedCartDiscountLimits.
	 *
	 * @param array $cart_rules Cart rules.
	 *
	 * @return array
	 */
	public function returnConvertedCartDiscountLimits( $cart_rules ) {
		$default_currency = API::default_currency();
		$converted_rules  = array();
		if ( \get_woocommerce_currency() !== $default_currency ) {
			foreach ( $cart_rules as $rule ) {
				$rule['min']       = $this->price_controller->convert( $rule['min'] );
				$rule['max']       = $this->price_controller->convert( $rule['max'] );
				$converted_rules[] = $rule;
			}

			return $converted_rules;
		}

		return $cart_rules;
	}

	/**
	 * ConvertPriceValue.
	 *
	 * @param float|int|string $value Price value.
	 *
	 * @return float|int|string
	 */
	public function convertPriceValue( $value ) {
		return $this->price_controller->convert( $value );
	}

}
