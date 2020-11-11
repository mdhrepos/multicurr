<?php
/**
 * Convert Standard shipping fees stored in the Options table.
 *
 * @since 1.6.0
 * @since 1.8.0 Hooked early.
 * @since 2.6.7-beta.1 Moved to own class from Price\Controller.
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Shipping;

use WOOMC\AbstractConverter;
use WOOMC\App;

/**
 * Class Shipping\OptionsConverter
 *
 * @package WOOMC\Shipping
 */
class OptionsConverter extends AbstractConverter {

	/**
	 * Setup actions and filters.
	 *
	 * @since 1.6.0
	 * @since 1.8.0 Hooked early.
	 * @return void
	 */
	public function setup_hooks() {

		/**
		 * WPDB.
		 *
		 * @global \wpdb $wpdb
		 */
		global $wpdb;

		/**
		 * Find all shipping methods and their instances in the database.
		 *
		 * @since        2.6.4 Retrieve only enabled methods.
		 * @noinspection SqlResolve
		 */
		$methods = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE is_enabled = 1" );

		// Add filters to get shipping method settings from the Options table.
		foreach ( $methods as $method ) {
			$option_name = sprintf( 'woocommerce_%s_%d_settings', $method->method_id, $method->instance_id );
			\add_filter(
				'option_' . $option_name,
				array(
					$this,
					'filter__shipping_method_costs',
				),
				App::HOOK_PRIORITY_EARLY
			);
		}
	}

	/**
	 * Convert every shipping setting that "looks like" cost or amount.
	 *
	 * @since    1.6.0
	 * @since    1.8.0 added the "_fee" pattern (used by USPS and Table Rate).
	 * @since    1.9.0 convert numbers extracted from string - for the settings with shortcodes such as `1 * [qty]`.
	 * @since    1.17.0 preserve the variable type.
	 * @since    1.17.0 attempt to convert only scalars.
	 * @since    2.6.4 Support commas and min/max_fee.
	 *
	 * @param array $settings Shipping method settings.
	 *
	 * @return array Settings with the amounts converted.
	 *
	 * @internal filter.
	 */
	public function filter__shipping_method_costs( $settings ) {

		// Filter out settings keys by regex.
		$metrics = preg_grep( '/cost|amount|_fee/', array_keys( $settings ) );

		foreach ( $metrics as $metric ) {
			if ( ! empty( $settings[ $metric ] ) && is_scalar( $settings[ $metric ] ) ) {

				$value_type = gettype( $settings[ $metric ] );

				/**
				 * From the flat-rate shipping method description:
				 * $cost_desc = __( 'Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.', 'woocommerce' ) . '<br/><br/>' . __( 'Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent="10" min_fee="20" max_fee=""]</code> for percentage based fees.', 'woocommerce' );
				 */

				// Convert to string.
				settype( $settings[ $metric ], 'string' );

				if ( preg_match( '/(^[\d.,]*)(.*)/', $settings[ $metric ], $matches ) ) {
					list( , $number, $formula ) = $matches;

					$number = $this->convert_localized_number( $number );

					$formula = preg_replace_callback(
						'/(_fee=")([\d,.]+)/',
						array( $this, 'callback__convert_min_max_fees' ),
						$formula
					);

					$settings[ $metric ] = $number . $formula;
				}

				// Restore the original variable type (preg_replace_callback converts to string).
				settype( $settings[ $metric ], $value_type );
			}
		}

		return $settings;
	}

	/**
	 * Callback for {@see filter__shipping_method_costs}.
	 *
	 * @since    2.6.4
	 *
	 * @param array $matches Matches from preg_replace.
	 *
	 * @return string Converted values.
	 *
	 * @internal filter callback.
	 */
	public function callback__convert_min_max_fees( $matches ) {
		return $matches[1] . $this->convert_localized_number( $matches[2] );
	}

	/**
	 * Convert a "localized" number string with comma as the decimal separator.
	 *
	 * @param string $sz The numeric string.
	 *
	 * @return string
	 */
	protected function convert_localized_number( $sz ) {
		$sz = str_replace( ',', '.', $sz );

		return $this->convert( $sz );
	}
}
