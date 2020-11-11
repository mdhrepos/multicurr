<?php
/**
 * RoyalMail.php
 * Support the WooCommerce Shipping extension.
 *
 * @since   1.9.0
 * Copyright (c) 2018. TIV.NET INC. All Rights Reserved.
 * @package WOOMC\Integration\Shipping
 */

namespace WOOMC\Integration\Shipping;

use TIVWP_140\InterfaceHookable;
use WOOMC\App;
use WOOMC\Price;

/**
 * Class RoyalMail
 */
class RoyalMail implements InterfaceHookable {

	/**
	 * The method ID.
	 *
	 * @var string
	 */
	const METHOD_ID = 'royal_mail';

	/**
	 * DI: Price Controller.
	 *
	 * @var Price\Controller
	 */
	protected $price_controller;

	/**
	 * Constructor.
	 *
	 * @param Price\Controller $price_controller The Price Controller instance.
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
			'woocommerce_shipping_rate_cost',
			array(
				$this,
				'filter__woocommerce_shipping_rate_cost',
			),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		add_filter(
			'woocommerce_shipping_rate_taxes',
			array(
				$this,
				'filter__woocommerce_shipping_rate_taxes',
			),
			App::HOOK_PRIORITY_EARLY,
			2
		);

	}

	/**
	 * Check if the shipping rate object's method ID is relevant to this class.
	 *
	 * @param \WC_Shipping_Rate $shipping_rate_object The shipping rate object.
	 *
	 * @return bool
	 */
	protected function is_my_method( $shipping_rate_object ) {
		return $shipping_rate_object instanceof \WC_Shipping_Rate && self::METHOD_ID === $shipping_rate_object->get_method_id();
	}

	/**
	 * Filter the rate cost.
	 *
	 * @param float|int|string  $cost                 The shipping rate cost.
	 * @param \WC_Shipping_Rate $shipping_rate_object The shipping rate object.
	 *
	 * @return float|int|string
	 *
	 * @internal filter.
	 */
	public function filter__woocommerce_shipping_rate_cost( $cost, $shipping_rate_object ) {
		if ( $this->is_my_method( $shipping_rate_object ) ) {
			$cost = $this->price_controller->convert( $cost );
		}

		return $cost;
	}

	/**
	 * Filter the taxes.
	 *
	 * @param float[]|int[]|string[] $taxes                The shipping rate taxes array.
	 * @param \WC_Shipping_Rate      $shipping_rate_object The shipping rate object.
	 *
	 * @return float[]|int[]|string[]
	 *
	 * @internal filter.
	 */
	public function filter__woocommerce_shipping_rate_taxes( $taxes, $shipping_rate_object ) {
		if ( $this->is_my_method( $shipping_rate_object ) ) {
			$taxes = $this->price_controller->convert_array( $taxes );
		}

		return $taxes;
	}
}
