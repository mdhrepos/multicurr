<?php
/**
 * CanadaPost.php
 * Support the WooCommerce Shipping extension.
 *
 * @package WOOMC\Integration\Shipping
 * @since   1.9.0
 * Copyright (c) 2018. TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration\Shipping;

/**
 * Data and methods specific to this shipping extension.
 */
class CanadaPost extends AbstractController {

	/**
	 * Method ID to use in {@see is_my_method()}.
	 *
	 * @var string
	 */
	const METHOD_ID = 'canada_post';

	/**
	 * Always convert from CAD because the online rates are returned in CAD, regardless the default currency of the Store.
	 *
	 * @var string
	 */
	const CONVERT_FROM = 'CAD';

	/**
	 * Filter the shipping rate cost.
	 *
	 * @param float|int|string  $cost                 The cost.
	 * @param \WC_Shipping_Rate $shipping_rate_object The Shipping Rate instance.
	 *
	 * @return float|int|string
	 */
	public function filter__woocommerce_shipping_rate_cost( $cost, $shipping_rate_object ) {
		if ( $this->is_my_method( $shipping_rate_object ) ) {
			$cost = $this->price_controller->convert( $cost, null, '', self::CONVERT_FROM );
		}

		return $cost;
	}

	/**
	 * Filter the shipping rate taxes.
	 *
	 * @param float[]|int[]|string[] $taxes                The taxes array.
	 * @param \WC_Shipping_Rate      $shipping_rate_object The Shipping Rate instance.
	 *
	 * @return array|float[]|int[]|string[]
	 */
	public function filter__woocommerce_shipping_rate_taxes( $taxes, $shipping_rate_object ) {
		if ( $this->is_my_method( $shipping_rate_object ) ) {
			$taxes = $this->price_controller->convert_array( $taxes, '', self::CONVERT_FROM );
		}

		return $taxes;
	}

}
