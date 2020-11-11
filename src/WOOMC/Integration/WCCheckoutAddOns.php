<?php
/**
 * Integration.
 * Plugin Name: WooCommerce Checkout Add-Ons
 * Plugin URI: https://woocommerce.com/products/woocommerce-checkout-add-ons/
 *
 * @since 1.13.0
 */

namespace WOOMC\Integration;

use TIVWP_140\InterfaceHookable;
use WOOMC\Price;


/**
 * Class Integration\WCCheckoutAddOns
 */
class WCCheckoutAddOns implements InterfaceHookable {

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

		/**
		 * Use the version 2 - compatible hook.
		 *
		 * @since 1.17.0
		 */
		add_filter( 'option_wc_checkout_add_ons', array( $this, 'filter__option_wc_checkout_add_ons' ) );

	}

	/**
	 * Convert prices at the DB options retrieval.
	 *
	 * @noinspection PhpUndefinedNamespaceInspection
	 * @see          \SkyVerge\WooCommerce\Checkout_Add_Ons\Add_Ons\Data_Store_Options::get_add_ons_data
	 * @see          \WC_Checkout_Add_Ons::get_add_ons (Version 1.x)
	 *
	 * @since        1.17.0
	 *
	 * @param array $add_ons_data The options array.
	 *
	 * @return array
	 */
	public function filter__option_wc_checkout_add_ons( $add_ons_data ) {

		if ( ! is_array( $add_ons_data ) ) {
			return $add_ons_data;
		}

		/**
		 * Cache the converted data for one session.
		 * Useful when there are several add-ons.
		 *
		 * @var array
		 */
		static $cache = array();

		if ( ! $cache ) {

			foreach ( $add_ons_data as $id => &$data ) {

				if ( ! empty( $data['options'] ) && is_array( $data['options'] ) ) {
					foreach ( $data['options'] as &$option ) {
						$this->convert_add_on_data( $option );
					}
				} else {
					$this->convert_add_on_data( $data );
				}
			}
			$cache = $add_ons_data;
		}

		return $cache;
	}

	/**
	 * Convert the cost ("adjustment") of the add-on.
	 *
	 * @since 1.17.0
	 *
	 * @param array $data The add-on data.
	 *
	 * @return void
	 */
	protected function convert_add_on_data( &$data ) {

		if ( ! empty( $data['adjustment'] ) && 'fixed' === $data['adjustment_type'] ) {
			// Version 2.x.
			$data['adjustment'] = $this->price_controller->convert( $data['adjustment'] );
		} elseif ( ! empty( $data['cost'] ) && 'fixed' === $data['cost_type'] ) {
			// Version 1.x.
			$data['cost'] = $this->price_controller->convert( $data['cost'] );
		}
	}

}
