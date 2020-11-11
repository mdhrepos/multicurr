<?php
/**
 * Public methods.
 *
 * @since 1.17.0
 *
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC;

use WOOMC\DAO\Factory;

/**
 * Class API
 *
 * @package WOOMC
 */
class API {

	/**
	 * Currency conversion.
	 *
	 * @since 1.17.0
	 *
	 * @param int|float|string $value The value to convert.
	 * @param string           $to    Currency to convert to.
	 * @param string           $from  Currency to convert from.
	 *
	 * @return int|float|string
	 */
	public static function convert( $value, $to, $from ) {

		$rate_storage  = new Rate\Storage();
		$price_rounder = new Price\Rounder();
		$calculator    = new Price\Calculator( $rate_storage, $price_rounder );

		return $calculator->calculate( $value, $to, $from );
	}

	/**
	 * Raw currency conversion.
	 *
	 * @since 1.17.0
	 *
	 * @param int|float|string $value The value to convert.
	 * @param string           $to    Currency to convert to.
	 * @param string           $from  Currency to convert from.
	 *
	 * @return int|float|string
	 */
	public static function convert_raw( $value, $to, $from ) {

		$rate_storage  = new Rate\Storage();
		$price_rounder = new Price\Rounder();
		$calculator    = new Price\Calculator( $rate_storage, $price_rounder );

		return $calculator->calculate_raw( $value, $to, $from );
	}

	/**
	 * The base ("Store") currency, unfiltered.
	 *
	 * @since 1.19.0
	 * @return string
	 */
	public static function default_currency() {
		return \get_option( 'woocommerce_currency' );
	}

	/**
	 * List of enabled currency codes, including the store currency.
	 *
	 * @since 1.19.0
	 * @return string[]
	 */
	public static function enabled_currencies() {
		return Factory::getDao()->getEnabledCurrencies();
	}

	/**
	 * List of enabled currency codes, excluding the store currency.
	 *
	 * @since 1.19.0
	 * @return array
	 */
	public static function extra_currencies() {
		return array_diff( self::enabled_currencies(), (array) self::default_currency() );
	}

	/**
	 * All WC's currencies in the form Code => Name.
	 *
	 * @since 2.5.0
	 * @return string[]
	 */
	public static function currency_names() {
		return \get_woocommerce_currencies();
	}

}
