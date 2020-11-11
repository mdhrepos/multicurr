<?php
/**
 * LEGACY Public methods - deprecated.
 *
 * @since      1.17.0
 *
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 * @deprecated 2.0.0 {@see \WOOMC\API}
 */

namespace WOOMC\MultiCurrency;

/**
 * Class API
 *
 * @package WOOMC\MultiCurrency
 */
class API {

	/**
	 * Deprecated since this version.
	 *
	 * @var string
	 */
	const DEPRECATED_SINCE = '2.0';

	/**
	 * Part of the namespace to be removed.
	 *
	 * @var string
	 */
	const DEPRECATED_NAMESPACE = '\MultiCurrency';

	/**
	 * Currency conversion.
	 *
	 * @since      1.17.0
	 *
	 * @param int|float|string $value The value to convert.
	 * @param string           $to    Currency to convert to.
	 * @param string           $from  Currency to convert from.
	 *
	 * @return int|float|string
	 * @deprecated 2.0.0
	 */
	public static function convert( $value, $to, $from ) {
		\wc_deprecated_function( __METHOD__, self::DEPRECATED_SINCE, str_replace( self::DEPRECATED_NAMESPACE, '', __METHOD__ ) );

		return \WOOMC\API::convert( $value, $to, $from );
	}

	/**
	 * Raw currency conversion.
	 *
	 * @since      1.17.0
	 *
	 * @param int|float|string $value The value to convert.
	 * @param string           $to    Currency to convert to.
	 * @param string           $from  Currency to convert from.
	 *
	 * @return int|float|string
	 * @deprecated 2.0.0
	 */
	public static function convert_raw( $value, $to, $from ) {
		\wc_deprecated_function( __METHOD__, self::DEPRECATED_SINCE, str_replace( self::DEPRECATED_NAMESPACE, '', __METHOD__ ) );

		return \WOOMC\API::convert_raw( $value, $to, $from );
	}

	/**
	 * The base ("Store") currency, unfiltered.
	 *
	 * @since      1.19.0
	 * @return string
	 * @deprecated 2.0.0
	 */
	public static function default_currency() {
		\wc_deprecated_function( __METHOD__, self::DEPRECATED_SINCE, str_replace( self::DEPRECATED_NAMESPACE, '', __METHOD__ ) );

		return \WOOMC\API::default_currency();
	}

	/**
	 * List of enabled currency codes, including the store currency.
	 *
	 * @since      1.19.0
	 * @return string[]
	 * @deprecated 2.0.0
	 */
	public static function enabled_currencies() {
		\wc_deprecated_function( __METHOD__, self::DEPRECATED_SINCE, str_replace( self::DEPRECATED_NAMESPACE, '', __METHOD__ ) );

		return \WOOMC\API::enabled_currencies();
	}

	/**
	 * List of enabled currency codes, excluding the store currency.
	 *
	 * @since      1.19.0
	 * @return array
	 * @deprecated 2.0.0
	 */
	public static function extra_currencies() {
		\wc_deprecated_function( __METHOD__, self::DEPRECATED_SINCE, str_replace( self::DEPRECATED_NAMESPACE, '', __METHOD__ ) );

		return \WOOMC\API::extra_currencies();
	}

}
