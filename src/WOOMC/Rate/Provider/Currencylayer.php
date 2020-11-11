<?php
/**
 * Rate provider: CurrencyLayer.
 *
 * @since 1.0.0
 */

namespace WOOMC\Rate\Provider;

/**
 * Class Provider\Currencylayer
 */
class Currencylayer extends ProviderAbstract {

	/**
	 * The provider ID.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public static function id() {
		return 'Currencylayer';
	}

	/**
	 * The credentials label (App ID, API key, etc.).
	 *
	 * @return string
	 */
	public static function credentials_label() {
		return 'API Access Key';
	}

	/**
	 * Configure.
	 *
	 * @param array $settings The settings array.
	 */
	public function configure( array $settings ) {
		$this->url_get_rates = 'http://apilayer.net/api/live?access_key=';
		$this->section_rates = 'quotes';

		parent::configure( $settings );
	}


	/**
	 * Remove the "USD" prefix from the currency codes.
	 * 'USDAED' becomes 'AED'.
	 *
	 * @param array $rates The rates array.
	 *
	 * @return array
	 * @example
	 * "USDAED" => 3.672982,
	 * "USDAFN"=> 57.8936,
	 * "USDALL"=> 126.1652,
	 *
	 * Alternative way:
	 * $sanitized_rates = array();
	 * array_walk( $rates, function ( $rate, $code ) use ( &$sanitized_rates ) {
	 * $sanitized_rates[ substr( $code, 3 ) ] = $rate;
	 * } );
	 */
	protected function sanitize_rates( array $rates ) {

		return array_combine(
			array_map(
				function ( $code ) {
					return substr( $code, 3 );
				},
				array_keys( $rates )
			),
			$rates
		);
	}
}
