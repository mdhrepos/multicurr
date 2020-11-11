<?php
/**
 * WooCommerce Environment
 *
 * @since 1.1.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace TIVWP_140\WC;

use TIVWP_140\Env;

/**
 * Class WCEnv
 */
class WCEnv {

	/**
	 * Return the method of defining customer location: base or geolocation.
	 *
	 * @return string
	 */
	public static function customer_location_method() {
		return \get_option( 'woocommerce_default_customer_address', 'base' );
	}

	/**
	 * True if geolocation of user is enabled.
	 *
	 * @return bool
	 */
	public static function is_geolocation_enabled() {
		return in_array( self::customer_location_method(), array( 'geolocation', 'geolocation_ajax' ), true );
	}

	/**
	 * Return colon-separated country:state of the Store.
	 *
	 * @return bool|mixed
	 * @example CA:ON
	 */
	public static function store_country_state() {
		return \get_option( 'woocommerce_default_country', '' );
	}

	/**
	 * Returns country:state of the Store as a 'location' array.
	 *
	 * @return string[]
	 * @example array( 'country' => 'CA', 'state'   => 'ON' )
	 */
	public static function store_location() {
		return \wc_format_country_state_string( \apply_filters( 'woocommerce_customer_default_location', self::store_country_state() ) );
	}

	/**
	 * True if browser signature looks like a robot.
	 *
	 * @return bool
	 */
	public static function is_a_bot() {
		return (bool) preg_match( '/bot|spider|crawl/', \wc_get_user_agent() );
	}

	/**
	 * Returns REST URL prefix (default is 'wp-json'.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	public static function rest_url_prefix() {
		$url_prefix = 'wp-json';
		if ( function_exists( 'rest_get_url_prefix' ) ) {
			$url_prefix = \rest_get_url_prefix();
		}

		return $url_prefix;
	}

	/**
	 * True if the current request is a REST API (wp-json) call.
	 *
	 * @return bool
	 */
	public static function is_rest_api_call() {

		$request_uri = Env::request_uri();

		if ( ! $request_uri ) {
			// Something abnormal.
			return false;
		}

		// True if 'wp-json' is in the URL.
		return false !== strpos( $request_uri, self::rest_url_prefix() );
	}

	/**
	 * True if the current request is a REST in new WC Admin ("Analytics").
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	public static function is_analytics_request() {

		$request_uri = Env::request_uri();

		if ( ! $request_uri ) {
			// Something abnormal.
			return false;
		}

		$rest_namespace  = 'wc-analytics';
		$rest_url_prefix = self::rest_url_prefix();

		// True if 'wp-json/wc-analytics' is in the URL.
		return false !== stripos( $request_uri, $rest_url_prefix . '/' . $rest_namespace );
	}
}
