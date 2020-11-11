<?php
/**
 * Rate Storage.
 *
 * @since 1.0.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Rate;

use TIVWP_140\InterfaceHookable;
use WOOMC\App;
use WOOMC\Settings\Panel;

/**
 * Class Rate\Storage
 */
class Storage implements InterfaceHookable {

	/**
	 * Options table key.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'woocommerce_multicurrency_rates';

	/**
	 * Fake rates for Unit Testing.
	 *
	 * @since 1.17.0
	 */
	const TEST_RATES = array(
		'AUD' => 0.25,
		'CAD' => 2.0,
		'EUR' => 0.5,
		'GBP' => 0.3333,
		'JPY' => 0.01,
		'USD' => 1.0,
	);

	/**
	 * Currency rates in the format (string) CODE => (float) RATE
	 *
	 * @var  float[]
	 */
	protected $rates;

	/**
	 * Getter for `rates`.
	 *
	 * @return float[]
	 */
	public function getRates() {
		return $this->rates;
	}

	/**
	 * Setter for `rates`.
	 *
	 * @param float[] $rates The array of currency rates.
	 */
	public function setRates( $rates ) {
		$this->rates = $rates;
	}

	/**
	 * Rate\Storage constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->load_rates();
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		/**
		 * After saving the settings, reload the rates.
		 * Hook later than {@see Panel::save_fields}.
		 */
		add_action(
			'woocommerce_update_options_' . Panel::TAB_SLUG,
			array( $this, 'load_rates' ),
			App::HOOK_PRIORITY_LATER
		);
	}

	/**
	 * Load rates from the options table.
	 *
	 * @return void
	 */
	public function load_rates() {

		if ( defined( 'DOING_PHPUNIT' ) ) {
			$this->setRates( self::TEST_RATES );
		} else {

			$rates = \get_option( self::OPTION_NAME, array() );

			/**
			 * Filter to adjust rates loaded from the options table.
			 *
			 * @since 2.5.0
			 *
			 * @param float[] $rates The array of currency rates.
			 */
			$rates = \apply_filters( 'woocommerce_multicurrency_loaded_rates', $rates );

			$this->setRates( $rates );
		}
	}

	/**
	 * Save rates to the options table.
	 *
	 * @since 1.20.0
	 *
	 * @param array $rates The currency rates array.
	 *
	 * @return void
	 */
	public static function save_rates( array $rates ) {
		\update_option( self::OPTION_NAME, $rates );
	}

	/**
	 * Get the rate of the specified currency against the default currency.
	 *
	 * Note: the rates retrieved from OpenExchangeRates are against the "base" currency.
	 *
	 * @link https://docs.openexchangerates.org/docs/changing-base-currency
	 *
	 * @example
	 * If our default currency is GBP and we want prices in CAD:
	 * CAD/GBP = CAD/base * base/GBP = CAD/base / GBP/base
	 *
	 * @param string $from The "from" currency code.
	 * @param string $to   The "to" currency code.
	 *
	 * @return float
	 */
	public function get_rate( $from, $to ) {

		/**
		 * Avoid fatal if invalid parameters.
		 *
		 * @since 1.16.0
		 */
		if ( ! is_string( $from ) || ! is_string( $to ) ) {
			return 1.0;
		}

		/**
		 * Short circuit.
		 *
		 * @since 1.16.0
		 */
		if ( $from === $to ) {
			return 1.0;
		}

		// Sanitize.

		if ( empty( $this->rates[ $from ] ) ) {
			$this->rates[ $from ] = 1;
		}
		if ( empty( $this->rates[ $to ] ) ) {
			$this->rates[ $to ] = 1;
		}

		return $this->rates[ $from ] / $this->rates[ $to ];
	}

	/**
	 * List of currencies we have rates for.
	 *
	 * @return string[]
	 */
	/**
	 * Unused.
	 *
	 * @noinspection PhpUnused
	 */
	public function get_currencies() {
		return array_keys( $this->rates );
	}

	/**
	 * List of WooCommerce currencies filtered by those we have rates.
	 * We do not use a filter because we need it only for our lists.
	 *
	 * @return array
	 */
	public function woocommerce_currencies_with_rates() {

		// All WC's currencies in the form Code => Name.
		$woocommerce_currencies = \get_woocommerce_currencies();

		/**
		 * With the FixedRates provider we have all rates.
		 *
		 * @since 1.15.0
		 */
		if ( CurrentProvider::isFixedRates() ) {
			return $woocommerce_currencies;
		}

		// The currencies we have rates.
		$rates = $this->getRates();

		// Remove those currencies that we do not have rates.
		return array_intersect_key( $woocommerce_currencies, $rates );
	}
}
