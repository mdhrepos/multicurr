<?php
/**
 * Rate Provider abstract class.
 *
 * @since 1.0.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Rate\Provider;

use TIVWP_140\Logger\Message;
use WOOMC\DAO\Factory;
use WOOMC\Log;

/**
 * Abstract Provider Class
 */
abstract class ProviderAbstract {

	/**
	 * Provider's API URL.
	 *
	 * @var string
	 */
	protected $url_get_rates;

	/**
	 * Rates section in the data received from the provider.
	 *
	 * @var string
	 */
	protected $section_rates;

	/**
	 * Timestamp section in the data received from the provider.
	 *
	 * @var string
	 */
	protected $section_timestamp = 'timestamp';

	/**
	 * The credentials.
	 *
	 * @var mixed
	 */
	protected $credentials;

	/**
	 * Formatted rates timestamp.
	 *
	 * @var string
	 */
	protected $timestamp;

	/**
	 * The provider ID.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public static function id() {
		return '';
	}

	/**
	 * The credentials label (App ID, API key, etc.).
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public static function credentials_label() {
		return '';
	}

	/**
	 * Getter for $this->credentials.
	 *
	 * @return mixed
	 */
	public function getCredentials() {
		return $this->credentials;
	}

	/**
	 * Setter for $this->credentials.
	 *
	 * @param mixed $credentials The credentials.
	 */
	protected function setCredentials( $credentials ) {
		$this->credentials = $credentials;
	}

	/**
	 * Getter for $this->timestamp.
	 *
	 * @return string
	 */
	public function getTimestamp() {
		return $this->timestamp;
	}

	/**
	 * Getter for $this->timestamp.
	 *
	 * @param string $timestamp The timestamp.
	 */
	public function setTimestamp( $timestamp ) {
		$this->timestamp = $timestamp;
	}

	/**
	 * This method must be called to pass the credentials.
	 * The derived classes also must set the URL and additional parameters.
	 *
	 * @param array $settings The array of settings.
	 *
	 * @return void
	 */
	public function configure( array $settings ) {
		if ( isset( $settings['credentials'] ) ) {
			$this->setCredentials( $settings['credentials'] );
		}
	}

	/**
	 * Call the provider API to retrieve the rates.
	 *
	 * @since 1.15.0 Logging.
	 * @since 1.18.3 Log response body if error and if debug.
	 *
	 * @return float[]
	 * @throws \Exception Caught.
	 */
	public function retrieve_rates() {

		Log::info( new Message( array( 'Retrieving rates', static::id() ) ) );

		$rates = array();

		try {
			$credentials = $this->getCredentials();
			if ( ! $credentials ) {
				throw new \Exception( 'No credentials' );
			}

			$remote_get_response = \wp_safe_remote_get( $this->url_get_rates . $credentials );
			if ( \is_wp_error( $remote_get_response ) ) {
				throw new \Exception( $remote_get_response->get_error_message() );
			}

			$response_code = \wp_remote_retrieve_response_code( $remote_get_response );
			if ( 200 !== $response_code ) {
				throw new \Exception( 'HTTP failure response from provider: ' . \wp_remote_retrieve_response_message( $remote_get_response ) );
			}

			$response_body = \wp_remote_retrieve_body( $remote_get_response );
			if ( ! $response_body ) {
				throw new \Exception( 'No data received from provider' );
			}

			0 && Log::debug( 'Rates response - body: ' . $response_body );

			/**
			 * No warning about missing ext-json.
			 *
			 * @noinspection PhpComposerExtensionStubsInspection
			 */
			$response_array = json_decode( $response_body, true );
			if ( empty( $response_array[ $this->section_rates ] ) ) {
				throw new \Exception( 'Invalid data received from provider: ' . $response_body );
			}

			$rates = $this->sanitize_rates( $response_array[ $this->section_rates ] );
			$this->setTimestamp( $response_array[ $this->section_timestamp ] );

			Factory::getDao()->setRatesRetrievalStatus( true );

			Log::info( new Message( 'Rates retrieved successfully' ) );

		} catch ( \Exception $e ) {
			Factory::getDao()->setRatesRetrievalStatus( false );
			Log::error( $e );
		}

		return $rates;

	}

	/**
	 * Stub for sanitizing rates.
	 *
	 * @param array $rates The array of ratings.
	 *
	 * @return array
	 */
	protected function sanitize_rates( array $rates ) {
		return $rates;
	}

}
