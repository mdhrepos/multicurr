<?php
/**
 * User.
 *
 * @since   1.4.0
 * @package WOOMC
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC;

use TIVWP_140\Country;
use TIVWP_140\WC\WCEnv;
use TIVWP_140\Env;
use TIVWP_140\InterfaceHookable;
use TIVWP_140\Logger\Message;
use WOOMC\DAO\Factory;
use WOOMC\User\Profile;

/**
 * Class User
 */
class User extends \WC_Data implements InterfaceHookable {

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'multicurrency_user';

	/**
	 * Data array.
	 *
	 * @var array
	 */
	protected $data = array(
		'currency' => '',
		'country'  => '',
	);

	/**
	 * Key for storing data.
	 *
	 * @var string
	 */
	protected $storage_key = '';

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		$user_profile = new Profile();
		$user_profile->setup_hooks();

		if ( Env::on_front() ) {
			/**
			 * Initialize the User.
			 *
			 * @since 1.12.0 Fix: Hooked to 'woocommerce_init'. Otherwise, WC()->countries is not initialized yet.
			 */
			\add_action( 'woocommerce_init', array( $this, 'init' ), App::HOOK_PRIORITY_EARLY );
		}
	}

	/**
	 * Initialize the User.
	 *
	 * @internal Action.
	 */
	public function init() {

		$this->storage_key = 'woocommerce_' . $this->object_type;

		// Try to retrieve the stored data.
		$this->retrieve();

		// If not retrieved, get the data and store.
		if ( ! $this->get_currency() ) {
			if ( ! WCEnv::is_a_bot() && WCEnv::is_geolocation_enabled() ) {
				$this->geolocate();
			}
			$this->store();
		}
	}

	/**
	 * Get user data by location.
	 * Actual if Geolocation is enabled in Woo settings.
	 *
	 * @return void
	 */
	protected function geolocate() {

		$ip_address = \WC_Geolocation::get_ip_address();

		if ( ! filter_var(
			$ip_address,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		) ) {
			Log::debug( new Message( implode( '|', array( 'IP', $ip_address, 'Invalid or reserved' ) ) ) );

			return;
		}

		$location = \WC_Geolocation::geolocate_ip( $ip_address, true, true );
		if ( empty( $location['country'] ) ) {
			Log::debug( new Message( implode( '|', array( 'IP', $ip_address, 'Cannot geolocate' ) ) ) );

			return;
		}

		$this->set_country( $location['country'] );

		$country_obj = new Country( $this->get_country() );
		$currency    = $country_obj->getCurrency();
		if ( $currency ) {
			$this->set_currency( $currency );
		} else {
			Log::error( new Message( 'Unknown currency|Country=' . $this->get_country() ) );
		}

		if ( ! \is_admin()
			 && ! WCEnv::is_rest_api_call()
			 && \WC_Log_Levels::DEBUG === Factory::getDao()->getLogLevel()
		) {
			Log::debug( new Message( implode( '|', array(
				'IP',
				$ip_address,
				'Country',
				$location['country'],
				'Currency',
				$this->get_currency( 'edit' ),
			) ) ) );
		}
	}

	/**
	 * Store the user data.
	 *
	 * @since   1.4.0
	 * @since   2.0.0 Use WC session instead of cookie.
	 */
	protected function store() {

		if ( ! \WC()->session ) {
			return;
		}

		\WC()->session->set( $this->storage_key, $this->data );
	}

	/**
	 * Retrieve the user data.
	 *
	 * @since   1.4.0
	 * @since   2.0.0 Use WC session instead of cookie.
	 */
	protected function retrieve() {

		if ( ! \WC()->session ) {
			return;
		}

		$retrieved_data = \WC()->session->get( $this->storage_key, $this->data );
		foreach ( array_keys( $this->data ) as $key ) {
			if ( isset( $retrieved_data[ $key ] ) ) {
				$this->set_prop( $key, $retrieved_data[ $key ] );
			}
		}

	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get currency.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_currency( $context = 'view' ) {
		return $this->get_prop( 'currency', $context );
	}

	/**
	 * Get country.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_country( $context = 'view' ) {
		return $this->get_prop( 'country', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set currency.
	 *
	 * @param string $value Currency.
	 */
	public function set_currency( $value ) {
		$this->set_prop( 'currency', \wc_clean( $value ) );
	}

	/**
	 * Set country.
	 *
	 * @param string $value Country.
	 */
	public function set_country( $value ) {
		$this->set_prop( 'country', \wc_clean( $value ) );
	}
}
