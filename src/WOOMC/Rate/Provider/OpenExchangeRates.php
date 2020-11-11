<?php
/**
 * Rate provider: OpenExchangeRates.
 *
 * @since 1.0.0
 */

namespace WOOMC\Rate\Provider;

/**
 * Class Provider\OpenExchangeRates
 */
class OpenExchangeRates extends ProviderAbstract {

	/**
	 * The provider ID.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public static function id() {
		return 'OpenExchangeRates';
	}

	/**
	 * The credentials label (App ID, API key, etc.).
	 *
	 * @inheritdoc
	 */
	public static function credentials_label() {
		return 'App ID';
	}

	/**
	 * This method must be called to pass the credentials.
	 *
	 * @param array $settings The array of settings.
	 *
	 * @return void
	 */
	public function configure( array $settings ) {
		$this->url_get_rates = 'https://openexchangerates.org/api/latest.json?app_id=';
		$this->section_rates = 'rates';

		parent::configure( $settings );
	}
}
