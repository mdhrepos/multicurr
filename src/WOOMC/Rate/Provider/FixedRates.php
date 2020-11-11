<?php
/**
 * Rate provider: CurrencyLayer.
 *
 * @since 1.15.0
 */

namespace WOOMC\Rate\Provider;

use WOOMC\DAO\Factory;

/**
 * Class Provider\FixedRates
 */
class FixedRates extends ProviderAbstract {

	/**
	 * The provider ID.
	 *
	 * @return string
	 */
	public static function id() {
		return 'FixedRates';
	}

	/**
	 * The label for "credentials" input field.
	 *
	 * @inheritdoc
	 */
	public static function credentials_label() {
		return ': keep this blank';
	}

	/**
	 * Retrieve rates.
	 *
	 * @inheritdoc
	 */
	public function retrieve_rates() {

		$dao = Factory::getDao();

		// Rates updated now.
		$this->setTimestamp( time() );

		$dao->setRatesRetrievalStatus( true );

		// Rates are manually entered on the Settings page.
		$rates = array();
		foreach ( $dao->getEnabledCurrencies() as $enabled_currency ) {
			$rates[ $enabled_currency ] = $dao->getFixedRate( $enabled_currency );
		}

		return $rates;

	}
}
