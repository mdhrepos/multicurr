<?php
/**
 * TableRate.php
 * Support the WooCommerce Table Rate Shipping extension.
 *
 * @since   1.8.0
 * Copyright (c) 2018. TIV.NET INC. All Rights Reserved.
 * @package WOOMC\Integration\Shipping
 */

namespace WOOMC\Integration\Shipping;

use TIVWP_140\InterfaceHookable;
use WOOMC\Price;

/**
 * Class Integration\Shipping\TableRate
 */
class TableRate implements InterfaceHookable {

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
			'woocommerce_table_rate_query_rates_args',
			array(
				$this,
				'filter__woocommerce_table_rate_query_rates_args',
			)
		);

		add_filter(
			'woocommerce_table_rate_query_rates',
			array(
				$this,
				'filter__woocommerce_table_rate_query_rates',
			)
		);

		/**
		 * Convert rates retrieved by @see \WC_Shipping_Table_Rate::get_shipping_rates.
		 *
		 * @since 1.14.4
		 */
		add_filter(
			'woocommerce_table_rate_get_shipping_rates',
			array(
				$this,
				'filter__woocommerce_table_rate_query_rates',
			)
		);

	}

	/**
	 * Prepare comparison criteria.
	 *
	 * @see      \WC_Shipping_Table_Rate::query_rates uses a DB query to check the applicability
	 * of a certain rate. Because the criteria are "hard-stored" and retrieved from DB with no filtering,
	 * we need to convert the amount back to the default currency before comparing against it.
	 *
	 * @note     The converted-back amount might differ from the original amount because of rounding and adjustments.
	 *
	 * @param array $args The arguments. We need only the 'price'.
	 *
	 * @return array
	 *
	 * The method @internal filter.
	 */
	public function filter__woocommerce_table_rate_query_rates_args( $args ) {

		if ( isset( $args['price'] ) ) {
			$args['price'] = $this->price_controller->convert_back( $args['price'] );
		}

		return $args;
	}

	/**
	 * Convert the rates.
	 *
	 * @param \stdClass $rates The Rates object.
	 *
	 * @return \stdClass
	 *
	 * @internal filter.
	 */
	public function filter__woocommerce_table_rate_query_rates( $rates ) {

		foreach ( $rates as &$rate ) {
			foreach (
				array(
					'rate_cost',
					'rate_cost_per_item',
					'rate_cost_per_weight_unit',
				) as $type
			) {
				if ( ! empty( $rate->$type ) ) {
					$rate->$type = $this->price_controller->convert( $rate->$type );
				}
			}
		}
		unset( $rate );

		return $rates;
	}
}


/**
 * UNUSED. This is covered by @see \WOOMC\Price\Controller::setup_shipping_hooks.
 * //protected function convert_instance_settings() {
 * //
 * //    /** @global \wpdb $wpdb
 *
 * //    global $wpdb;
 * //
 * //    // Find all Table Rate settings in the database.
 * //    $option_names = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", 'woocommerce_table_rate_%_settings' ) );
 * //    // Add filters to get the settings from the Options table.
 * //    foreach ( $option_names as $option_name ) {
 * //        add_filter( 'option_' . $option_name, array( $this, 'filter__instance_settings' ) );
 * //    }
 * //
 * //}
 * //
 * //public function filter__instance_settings( $settings ) {
 * //    foreach (
 * //        array(
 * //            'handling_fee',
 * //            'max_cost',
 * //            'max_shipping_cost',
 * //            'min_cost',
 * //            'order_handling_fee',
 * //        ) as $setting
 * //    ) {
 * //        if ( ! empty( $settings[ $setting ] ) ) {
 * //            $settings[ $setting ] = $this->price_controller->convert( $settings[ $setting ] );
 * //        }
 * //    }
 * //
 * //    return $settings;
 * //}
 */
