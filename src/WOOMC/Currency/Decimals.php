<?php
/**
 * Decimals.php
 *
 * @since   1.5.0
 * Copyright (c) 2018. TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Currency;

use TIVWP_140\Env;
use TIVWP_140\InterfaceHookable;
use WOOMC\App;

/**
 * Class Currency\Decimals.
 */
class Decimals implements InterfaceHookable {

	/**
	 * Default number of price decimals.
	 * Same as hard-coded in {@see \wc_get_price_decimals}.
	 *
	 * @since 1.18.0
	 * @var int
	 */
	const DEFAULT_NUMBER_OF_DECIMALS = 2;

	/**
	 * Decimals of currency.
	 *
	 * @since   1.5.0
	 *
	 * @var array {
	 * @type string The currency code.
	 * @type int The number of decimals.
	 * }
	 */
	protected static $decimals_of_currency = array(
		'BIF' => 0,
		'CLP' => 0,
		'CVE' => 0,
		'DJF' => 0,
		'GNF' => 0,
		'ISK' => 0,
		'JPY' => 0,
		'KMF' => 0,
		'KRW' => 0,
		'PYG' => 0,
		'RWF' => 0,
		'UGX' => 0,
		'UYI' => 0,
		'VND' => 0,
		'VUV' => 0,
		'XAF' => 0,
		'XOF' => 0,
		'XPF' => 0,
		'BHD' => 3,
		'IQD' => 3,
		'JOD' => 3,
		'KWD' => 3,
		'LYD' => 3,
		'OMR' => 3,
		'TND' => 3,
		'CLF' => 4,
		'HUF' => 0,
		'TWD' => 0,
	);

	/**
	 * Default decimals set in the General settings.
	 *
	 * @var int
	 */
	protected static $store_base_decimals = self::DEFAULT_NUMBER_OF_DECIMALS;

	/**
	 * Constructor.
	 *
	 * @since   1.5.0
	 * @since   2.0.0 Do not need to pass Detector.
	 */
	public function __construct() {
		self::$store_base_decimals = (int) \get_option( 'woocommerce_price_num_decimals', self::DEFAULT_NUMBER_OF_DECIMALS );
	}

	/**
	 * Setup actions and filters.
	 *
	 * @since   1.5.0
	 * @return void
	 */
	public function setup_hooks() {
		if ( ! Env::on_front() ) {
			return;
		}

		$this->filter_the_decimals();

	}

	/**
	 * Filter the decimals.
	 *
	 * @since 1.5.0
	 * @since 1.16.0 Use 'wc_get_price_decimals' tag instead of 'pre_option_woocommerce_price_num_decimals'.
	 * @since 1.18.0 Revert to 'pre_option_...' because many extensions do not use 'wc_get_...'.
	 * @since 2.0.0 Filter always, do not compare with the store currency.
	 */
	protected function filter_the_decimals() {

		\add_filter( // 'wc_get_price_decimals',
			'pre_option_woocommerce_price_num_decimals',
			array(
				$this,
				'decimals_of_active_currency',
			),
			App::HOOK_PRIORITY_EARLY,
			0
		);

	}

	/**
	 * Returns the number of decimals of the currently active currency.
	 *
	 * @since    1.5.0
	 * @since    2.0.0 Do not use the initial currency from Detector and get it each time.
	 *           Fixes the problem with wrong decimals when no forced currency is set.
	 *
	 * @return int
	 *
	 * @internal filter.
	 */
	public function decimals_of_active_currency() {
		return self::get_price_decimals( \get_woocommerce_currency() );
	}

	/**
	 * Returns the number of decimals of the currency.
	 *
	 * @since 1.16.0
	 * @since 1.18.0 Use hard-coded default of 2 instead of reading from the options.
	 * @since 1.18.1 Do not return decimals higher than the one set in the General Settings.
	 *
	 * @param string $currency The currency code.
	 *
	 * @return int
	 */
	public static function get_price_decimals( $currency ) {

		// Decimals of the currency is either explicitly set or is default.
		$decimals_of_currency = isset( self::$decimals_of_currency[ $currency ] ) ? self::$decimals_of_currency[ $currency ] : self::DEFAULT_NUMBER_OF_DECIMALS;

		// Return the decimals value, but keep it not higher than the one set in the General Settings.
		return min( self::$store_base_decimals, $decimals_of_currency );
	}
}
