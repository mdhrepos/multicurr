<?php
/**
 * Price Formatter.
 *
 * @since 1.0.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Price;

use TIVWP_140\Env;
use TIVWP_140\InterfaceHookable;
use WOOMC\App;
use WOOMC\DAO\Factory;
use WOOMC\Locale;

/**
 * Class Formatter
 */
class Formatter implements InterfaceHookable {

	/**
	 * Array of price formats per currency.
	 *
	 * @var string[]
	 */
	protected $currency_to_price_format;

	/**
	 * DI: Locale.
	 *
	 * @since 2.1.0
	 *
	 * @var Locale
	 */
	protected $locale;

	/**
	 * Price\Formatter constructor.
	 *
	 * @codeCoverageIgnore
	 *
	 * @param Locale $locale Locale object.
	 */
	public function __construct( Locale $locale ) {
		$this->locale = $locale;
		$this->setCurrencyToPriceFormat( Factory::getDao()->getCurrencyToPriceFormat() );
	}

	/**
	 * Setter.
	 *
	 * @param string[] $currency_to_price_format Array of "currency-to-price-format".
	 */
	public function setCurrencyToPriceFormat( $currency_to_price_format ) {
		$this->currency_to_price_format = $currency_to_price_format;
	}


	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 * @codeCoverageIgnore
	 */
	public function setup_hooks() {

		\add_filter(
			'woocommerce_price_format',
			array( $this, 'filter__woocommerce_price_format' ),
			App::HOOK_PRIORITY_EARLY
		);

		if ( ! Env::on_front() ) {
			return;
		}

		/**
		 * Filter get_option( 'woocommerce_price_decimal_sep' ) );
		 *
		 * @since 2.1.0
		 */
		\add_filter(
			'wc_get_price_decimal_separator',
			array( $this->locale, 'getDecimalSeparator' )
		);

		/**
		 * Filter get_option( 'woocommerce_price_thousand_sep' )
		 *
		 * @since 2.1.0
		 */
		\add_filter(
			'wc_get_price_thousand_separator',
			array( $this->locale, 'getThousandSeparator' )
		);

	}

	/**
	 * If we have a format for the current WC currency, return it.
	 *
	 * @param string $format The currency format to filter.
	 *
	 * @return string
	 */
	public function filter__woocommerce_price_format( $format ) {
		$format_of_currency = $this->get_format( $this->get_woocommerce_currency() );

		return $format_of_currency ? $format_of_currency : $format;
	}

	/**
	 * If we have a format for this currency, return it.
	 *
	 * @param string $currency The currency code.
	 *
	 * @return string
	 */
	protected function get_format( $currency ) {
		return empty( $this->currency_to_price_format[ $currency ] )
			? ''
			: $this->currency_to_price_format[ $currency ];
	}

	/**
	 * Wrapper for PHPUnit mocking.
	 *
	 * @return string
	 * @codeCoverageIgnore
	 */
	protected function get_woocommerce_currency() {
		return \get_woocommerce_currency();
	}
}
