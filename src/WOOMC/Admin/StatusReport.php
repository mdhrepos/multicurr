<?php
/**
 * WooCommerce Status Report.
 *
 * @since   1.13.0
 * @package WOOMC\Admin
 */

namespace WOOMC\Admin;

use TIVWP_140\WC\AbstractStatusReport;
use TIVWP_140\WC\WCEnv;
use WOOMC\Currency\Detector;
use WOOMC\DAO\WP;

/**
 * Class StatusReport
 */
class StatusReport extends AbstractStatusReport {

	/**
	 * Add or modify the report key-value options array.
	 *
	 * @since 2.6.7
	 *
	 * @param array $options The options to report.
	 *
	 * @return array
	 */
	public function filter__tivwp_wc_status_report_options( $options ) {

		static $truncate_at = 100;

		// Truncate long rates string.
		if ( strlen( $options['rates'] ) > $truncate_at ) {
			$options['rates_count'] = 'array(' . count( \maybe_unserialize( $options['rates'] ) ) . ')';
		}
		$options['rates'] = substr( $options['rates'], 0, $truncate_at ) . '...';

		// Cookie (of the admin who reports).
		$options[ 'cookie_' . Detector::COOKIE_FORCED_CURRENCY ] = Detector::currency_from_cookie();

		// Some WC settings.
		$options['woocommerce_prices_include_tax'] = \get_option( 'woocommerce_prices_include_tax', '-' );
		$options['woocommerce_tax_display_cart']   = \get_option( 'woocommerce_tax_display_cart', '-' );
		$options['woocommerce_tax_display_shop']   = \get_option( 'woocommerce_tax_display_shop', '-' );

		// Geolocation settings.
		$options['customer_location_method'] = WCEnv::customer_location_method();

		// MaxMind: has key?
		$option_maxmind                 = \maybe_unserialize( \get_option( 'woocommerce_maxmind_geolocation_settings', '' ) );
		$options['maxmind_license_set'] = empty( $option_maxmind['license_key'] ) ? 'no' : 'yes';

		return $options;
	}

	/**
	 * Render the report.
	 *
	 * @internal action.
	 */
	public function action__woocommerce_system_status_report() {

		$label         = 'Multicurrency';
		$option_prefix = WP::OPTIONS_PREFIX;

		\add_filter( 'tivwp_wc_status_report_options', array( $this, 'filter__tivwp_wc_status_report_options' ) );

		$this->do_report( $label, $option_prefix );
	}
}
