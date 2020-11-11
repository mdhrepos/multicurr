<?php
/**
 * Update currency rates.
 *
 * @since 1.0.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Rate;

use TIVWP_140\Logger\Message;
use WOOMC\DAO\Factory;
use WOOMC\Log;
use WOOMC\Rate\Provider\ProviderAbstract;

/**
 * Class Rate\Updater
 */
class Updater {

	/**
	 * Update rates.
	 *
	 * @since 1.0.0
	 * @since 1.20.0 Do not need parameters.
	 *
	 * @return int Number of rates received (array size).
	 * @throws \Exception Caught.
	 */
	public function update() {

		$rates = array();

		try {
			/**
			 * Disable rates update in wp-config.
			 *
			 * @since 1.20.0
			 */
			if ( defined( 'WOOMC_RATE_UPDATES_DISABLED' ) && WOOMC_RATE_UPDATES_DISABLED ) {
				throw new \Exception( 'WOOMC_RATE_UPDATES_DISABLED is set to True.' );
			}

			// May throw an exception.
			$provider = $this->getProvider();

			$rates = $provider->retrieve_rates();
			if ( ! $rates ) {
				throw new \Exception( 'Rates not retrieved.' );
			}

			Storage::save_rates( $rates );
			Factory::getDao()->saveRatesTimestamp( $provider->getTimestamp() );
			Log::info( new Message( array( 'Rates updated', 'Provider: ' . $provider::id() ) ) );

		} catch ( \Exception $exception ) {
			Log::error( $exception );
		}

		return count( $rates );
	}

	/**
	 * Get the current rates provider object.
	 *
	 * @since 1.20.0
	 *
	 * @return ProviderAbstract
	 * @throws \Exception Caught.
	 */
	protected function getProvider() {

		$provider_id = Factory::getDao()->getRatesProviderID();
		if ( ! $provider_id ) {
			throw new \Exception( 'Rates provider not set.' );
		}

		$class_name = __NAMESPACE__ . '\\Provider\\' . $provider_id;

		/**
		 * The Provider object.
		 *
		 * @var ProviderAbstract $provider
		 */
		$provider = new $class_name();
		$provider->configure( array( 'credentials' => Factory::getDao()->getRatesProviderCredentials() ) );

		return $provider;
	}

}
