<?php
/**
 * User Profile.
 *
 * @since 2.7.0-rc.1
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\User;

use TIVWP_140\Env;
use TIVWP_140\InterfaceHookable;
use TIVWP_140\Logger\Message;
use WOOMC\API;
use WOOMC\App;
use WOOMC\Cookie;
use WOOMC\Currency\Detector;
use WOOMC\Log;

/**
 * Class Profile
 *
 * @package WOOMC\User
 */
class Profile implements InterfaceHookable {

	/**
	 * User meta key.
	 *
	 * @var string
	 */
	const META_KEY_CURRENCY = 'woocommerce_multicurrency_currency';

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		if ( Env::on_front() ) {

			\add_action(
				'woocommerce_multicurrency_currency_changed',
				array( $this, 'action__woocommerce_multicurrency_currency_changed' )
			);

			\add_action(
				'wp_login',
				array( $this, 'action__wp_login' ),
				App::HOOK_PRIORITY_LATE,
				2
			);

		} else {
			\add_filter(
				'woocommerce_customer_meta_fields',
				array( $this, 'filter__woocommerce_customer_meta_fields' )
			);
		}
	}

	/**
	 * Act on currency change.
	 *
	 * @param string $currency The currency code.
	 *
	 * @return void
	 * @internal action.
	 */
	public function action__woocommerce_multicurrency_currency_changed( $currency ) {

		$user_id = \get_current_user_id();
		if ( $user_id ) {
			$this->store_currency( $user_id, $currency );
		}
	}

	/**
	 * Store or update currency in the user's profile.
	 *
	 * @param int    $user_id  The user ID.
	 * @param string $currency The currency code.
	 *
	 * @return void
	 */
	protected function store_currency( $user_id, $currency ) {

		$result = \update_user_meta( $user_id, self::META_KEY_CURRENCY, $currency );
		if ( is_numeric( $result ) ) {
			Log::info( new Message(
				array( 'User profile', 'Currency saved', 'user_id', $user_id, 'currency', $currency ) ) );
		} elseif ( true === $result ) {
			Log::info( new Message(
				array( 'User profile', 'Currency updated', 'user_id', $user_id, 'currency', $currency ) ) );
		}
	}

	/**
	 * Act on login.
	 *
	 * @param string   $user_login Username.
	 * @param \WP_User $user       User object.
	 */
	public function action__wp_login( $user_login, $user ) {

		$user_id = $user->ID;
		Log::info( new Message(
			array( 'Login', 'user_id', $user_id, 'user_login', $user_login )
		) );

		$this->force_profile_currency( $user_id );
	}

	/**
	 * Retrieve currency from the user profile and set it as active if the Cart is empty.
	 *
	 * @param int $user_id User ID.
	 */
	protected function force_profile_currency( $user_id ) {

		if ( ! empty( \WC()->cart->cart_contents ) ) {
			Log::debug( new Message(
				array( 'Cart is not empty', 'Not retrieving currency from profile' )
			) );

			return;
		}

		$currency = \get_user_meta( $user_id, self::META_KEY_CURRENCY, true );
		if ( $currency ) {

			if ( in_array( $currency, API::enabled_currencies(), true ) ) {

				Log::info( new Message(
					array( 'Setting currency from User profile', 'user_id', $user_id, 'currency', $currency )
				) );
				// TODO: refactor. Cookie is a property of user (whether logged in or not). Not a property of Detector.
				Cookie::set( Detector::COOKIE_FORCED_CURRENCY, $currency, YEAR_IN_SECONDS, true );
			} else {
				Log::debug( new Message(
					array( 'Profile currency is not in the list of enabled. Not forcing.', 'user_id', $user_id, 'currency', $currency )
				) );
			}
		}
	}

	/**
	 * Show our meta(s) on the user profile page.
	 *
	 * @param array $show_fields Array of fields already added by WooCommerce.
	 *
	 * @return array
	 */
	public function filter__woocommerce_customer_meta_fields( $show_fields ) {

		$show_fields[] = array(
			'title'  => __( 'WooCommerce Multi-currency', 'woocommerce-multicurrency' ),
			'fields' => array(
				self::META_KEY_CURRENCY => array(
					'label'       => __( 'Preferred Currency', 'woocommerce-multicurrency' ),
					'description' => __( 'Currency to set active upon login', 'woocommerce-multicurrency' ),
					'type'        => 'select',
					'class'       => self::META_KEY_CURRENCY,
					'options'     => array( '' => '' ) + API::currency_names(),
				),
			),
		);

		return $show_fields;
	}
}
